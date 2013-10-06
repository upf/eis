<?php

// **** EIS ****
// eis system lib
// upf, May2013


// init timezone
date_default_timezone_set($eis_conf["timezone"]);


//////// mysql management

// open database connection
$eis_mysqli = new mysqli($eis_conf["dbserver"],$eis_conf["user"],$eis_conf["password"],$eis_conf["dbname"]);
if ($eis_mysqli->connect_errno) die("system:databaseFailure -- ".$eis_mysqli->connect_error);

// execute mysql statements contained into the passed file (usually a mysql dump file)
// return true on success, false on failure
function eis_mysql_exec($dumpfile) {
	global $eis_mysqli;
	eis_clear_error();
	$statements=explode(";",file_get_contents($dumpfile));
	foreach ($statements as $query) {
		$query=trim($query);
		if ($query=="") continue;
		if (!$eis_mysqli->query($query))
			return eis_error("system:cannotQueryDatabase",$eis_mysqli->error);
	}
}


//////// system functions

// return on array of local devices information or false on failure
// $mode=="all" return all devices, else only installed devices are returned
function eis_getdevices($mode) {
	global $eis_mysqli;
	eis_clear_error();
	$devdb=array();
	if (!($result=$eis_mysqli->query("SELECT * FROM devices"))) return eis_error("system:cannotReadDatabase",$eis_mysqli->error);
	while ($data=$result->fetch_array(MYSQLI_ASSOC)) {
		if ($mode!="all" and $data["installed"]!="yes") continue;
		$data["configurations"]=eis_decode($data["configurations"]);
		$devdb[$data["id"]]=$data;
	}
	return $devdb;
}

//////// log functions

// logger function, level=0 fatal (exit script)  level=1 severe  level=2 warning  level=3 info 
function eis_log($level,$description) {
	global $eis_conf,$eis_dev_conf,$eis_mysqli;
	if (isset($eis_dev_conf)) $from=$eis_dev_conf["ID"]; else $from="eis_system";
	$strlevel=array("fatal","severe","warning","info");
	// write log to database
	$query="INSERT INTO log VALUES (".time().",'$from','".$strlevel[$level]."','$description')";
	if (!$eis_mysqli->query($query)) die("system:cannotLog \"$description\": ".$eis_mysqli->error);
	// if fatal, die immediately
	$strlog=date("Y-m-d H:i:s")." -- ".$from." -- ".$strlevel[$level]." -- ".$description;
	if ($level==0) die ("ERR 0\n <br> $strlog\n");
}

// clear logs and return
function eis_log_clear() {
	global $eis_mysqli;
	if (!$eis_mysqli->query("DELETE FROM log")) die("system:cannotLog: ".$eis_mysqli->error);
}

// return last $lines log records as array, relative to the device $device ("" == all logs)
function eis_log_get($device,$lines) {
	global $eis_mysqli;
	$lines=intval($lines);
	if ($lines<0) $lines=50;
	if ($device=="") $where=""; else $where="WHERE device='$device'";
	$result=$eis_mysqli->query("SELECT * FROM log $where ORDER BY timestamp DESC LIMIT $lines");
	$log=array();
	while ($l=$result->fetch_array(MYSQLI_ASSOC))
		$log[]=date("Y-m-d H:i:s",$l["timestamp"])." -- ".$l["device"]." -- ".$l["level"]." -- ".$l["message"];
	return $log;
}


//////// error reporting functions

// global vars for testing error condition ($eis_error) and get error message ($eis_errmsg)
$eis_error=false;
$eis_errmsg="";

// set error condition, set $eis_error to $error and $eis_errmsg to $errmsg		
// always return false
function eis_error($error,$errmsg) {
	global $eis_error,$eis_errmsg;
	$eis_error=$error;
	$eis_errmsg=$errmsg;
	if ($errmsg!="") $errmsg=" --> ".$errmsg;
	//eis_log(1,$error.$errmsg);
	return false;
}

// clear error condition set $eis_error to false and $eis_errmsg to ""
function eis_clear_error() {
	global $eis_error,$eis_errmsg;
	$eis_error=false;
	$eis_errmsg="";
}


//////// encoding/decoding functions

// return the input message encoded
function eis_encode($message) {
	global $eis_conf;
	if ($eis_conf["base64"])
		return base64_encode(json_encode($message));
	else
		return json_encode($message);
}

// return the input message decoded 
function eis_decode($message) {
	global $eis_conf;
	if ($eis_conf["base64"])
		return json_decode(base64_decode($message),true);
	else
		return json_decode($message,true);
}


//////// eis call receiving functions
		
// send back a prepared return message as an HTTP response
// HTTP call is terminated after this function call
// WARMING: no output must be sent before this call !
// in case of error signaled inside $returnmsg, log error and die
function eis_send_returnmsg($returnmsg) {
	// encode message
	$returndata=eis_encode($returnmsg);
	// send data, no header must be output before these
	header("Content-Type: application/json");
	header("Connection: close");
	$size=mb_strlen($returndata);
	$tsize=$size+13;
	header("Content-Length: $tsize");
	print "eis".sprintf("%10u",$size).$returndata;
	flush();
	// check and log errors
	if ($returnmsg["error"]) {
		if ($returnmsg["returnpar"]["errordata"]!="") $errmsg=" --> ".$returnmsg["returnpar"]["errordata"]; else $errmsg="";
		eis_log(1,$returnmsg["error"].$errmsg);
		die();
	}
}

// return an eis-formatted OK call return message ($returnpar = array of return parameters)
function eis_ok_msg($returnpar) {
	if (!is_array($returnpar)) $returnpar=array();
	return array("error"=>null,"returnpar"=>$returnpar);
}

// return an eis-formatted error call return message
function eis_error_msg($errcode,$errdata) {
	return array("error"=>$errcode,"returnpar"=>array("errordata"=>$errdata));
}

// extract, check and return the call data parameter array
// if errors, send back an HTTP error message and DIE
function eis_getcalldata () {
	// get POST data
	$rawcalldata=file_get_contents('php://input');
	// decode data
	$calldata=eis_decode($rawcalldata);
	// check call data
	if (!is_array($calldata)) eis_send_returnmsg(eis_error_msg("system:callData",$rawcalldata));
	if (!array_key_exists("timestamp",$calldata)) eis_send_returnmsg(eis_error_msg("system:callDataField","timestamp"));
	if (!array_key_exists("from",$calldata)) eis_send_returnmsg(eis_error_msg("system:callDataField","from"));
	if (!array_key_exists("type",$calldata)) eis_send_returnmsg(eis_error_msg("system:callDataField","type"));
	if (!array_key_exists("cmd",$calldata)) eis_send_returnmsg(eis_error_msg("system:callDataField","cmd"));
	if (!array_key_exists("param",$calldata)) eis_send_returnmsg(eis_error_msg("system:callDataField","param"));
	// everything is ok, return calldata
	return $calldata;
}


//////// eis call sending functions
	
// device call function, accept all the call parameters, return true=ok/false=error and the "returnmsg" array
// in case of error, error code and error message can be found into global vars $eis_error and $eis_errmsg	 
function eis_call($url,$timestamp,$from,$type,$cmd,$param,&$returnmsg) {
	global $eis_conf;
	eis_clear_error();
	// prepare data
	$calldata=array("timestamp"=>$timestamp,"from"=>$from,"type"=>$type,"cmd"=>$cmd,"param"=>$param);
	$to=$url;
	// check if host is alive
	if(substr($url,-1)=="/") $url=$url."control.php"; else $url=$url."/control.php";
	if (!($p=parse_url($url,PHP_URL_PORT))) $p=80;
	if (($st=@fsockopen(parse_url($url,PHP_URL_HOST),$p,$errno,$errstr,$eis_conf["atimeout"]))!==false)
		fclose($st);
	else 
		return eis_error("system:hostNotAlive",$url);
	// encode data
	$calldata=eis_encode($calldata);
	// make the POST call and get return data back
   	$options=array("method"=>"POST","content"=>$calldata,"timeout"=>$eis_conf["timeout"],
   		"header"=>"Content-Type: application/json\r\nAccept: application/json\r\nCache-Control: no-cache,must-revalidate\r\n");
   	$ctx=stream_context_create(array("http"=>$options));
   	@$fp=fopen($url,'rb',false,$ctx);
   	if (!$fp) return eis_error("system:HTTPcall","no response or timeout from $url");
   	// check if the message is an eis message
 	if (!($token=@stream_get_contents($fp,3))) return eis_error("system:HTTPcall",stream_get_meta_data($fp)); 
	if ($token!="eis") {
		// false, return raw data
		if (!($returndata=@stream_get_contents($fp))) return eis_error("system:HTTPcall",stream_get_meta_data($fp));
		$returndata=$token.$returndata;
	}
	else {
		// true, get message size and read it
		if (!($len=@stream_get_contents($fp,10))) return eis_error("system:HTTPcall",stream_get_meta_data($fp));
		$len=intval($len);
		if (!($returndata=@stream_get_contents($fp,$len))) return eis_error("system:HTTPcall",stream_get_meta_data($fp));
	}
 	@fclose($fp);
    // decode return data
	$returnmsg=eis_decode($returndata);
	if (!is_array($returnmsg) or !array_key_exists("error",$returnmsg) or !array_key_exists("returnpar",$returnmsg))
		return eis_error("system:callRetData",$returndata);
	// check if the call returned an error
	if ($returnmsg["error"]) return eis_error($returnmsg["error"],$returnmsg["returnpar"]["errordata"]);
	// everything is ok, return true
	return true;
}


?>
