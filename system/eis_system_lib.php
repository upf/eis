<?php

// **** EIS ****
// eis system lib
// upf, May2013

require_once("/etc/eis.conf");

// init timezone
date_default_timezone_set($eis["timezone"]);



//////// log functions

// logger function, level=0 fatal (exit script)  level=1 severe  level=2 warning  level=3 info 
function eis_log($level,$description) {
	global $eis, $eis_device;
	if (isset($eis_device)) $from=$eis_device["ID"]; else $from="eis_system";
	$strlevel=array("fatal","severe","warning","info");
	// put here everything is needed instead of printing
	$strlog=date("Y-m-d H:i:s")." -- ".$from." -- ".$strlevel[$level]." -- ".$description;
	// write to file
	$handle = fopen($eis["logfile"],"a");
	fwrite ($handle,$strlog."\n");
	fclose ($handle);
	if ($level==0) 
		die ("ERR 0\n <br> $strlog\n");
}


//////// error reporting functions

// set error condition, i.e. set the global vars $eis["error"] and $eis["errmsg"]
// always return false
function eis_error($error,$errmsg) {
	global $eis;
	$eis["error"]=$error;
	$eis["errmsg"]=$errmsg;
	eis_log(1,$error." --> ".$errmsg);
	return false;
}

// clear error condition
function eis_clear_error() {
	global $eis;
	$eis["error"]=false;
	$eis["errmsg"]="";
}


//////// encoding/decoding functions

// return the input message encoded
function eis_encode($message) {
	global $eis;
	if ($eis["base64"])
		return base64_encode(json_encode($message));
	else
		return json_encode($message);
}

// return the input message decoded 
function eis_decode($message) {
	global $eis;
	if ($eis["base64"])
		return json_decode(base64_decode($message),true);
	else
		return json_decode($message,true);
}


//////// eis call receiving functions
		
// send back a prepared return message as an HTTP response
// HTTP call is terminated after this function call
// WARMING: no output must be sent before this call !
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
}

// (wrapper of eis_send_returnmsg) send back an OK return message
function eis_send_ok_msg($returnmsg) {
	eis_send_returnmsg($returnmsg);
}

// (wrapper of eis_send_returnmsg) send back an error return message and DIE
function eis_send_error_msg($errcode,$errdata) {
	eis_send_returnmsg(array("error"=>$errcode, "returnpar"=>array("errordata"=>$errdata)));
	eis_log(1,$errcode." --> ".$errdata);
	die();
}

// extract, check and return the call data parameter array
// if errors, send back an HTTP error message and DIE
function eis_getcalldata () {
	// get POST data
	$rawcalldata=file_get_contents('php://input');
	// decode data
	$calldata=eis_decode($rawcalldata);
	// check call data
	if (!is_array($calldata)) eis_send_error_msg("system:callData",$rawcalldata);
	if (!array_key_exists("timestamp",$calldata)) eis_send_error_msg("system:callDataField","timestamp");
	if (!array_key_exists("from",$calldata)) eis_send_error_msg("system:callDataField","from");
	if (!array_key_exists("type",$calldata)) eis_send_error_msg("system:callDataField","type");
	if (!array_key_exists("cmd",$calldata)) eis_send_error_msg("system:callDataField","cmd");
	if (!array_key_exists("param",$calldata)) eis_send_error_msg("system:callDataField","param");
	// everything is ok, return calldata
	return $calldata;
}


//////// eis call sending functions
	
// device call function, accept all the call parameters, return true=ok/false=error and the "returnmsg" array
// in case of error, error code and error message can be found into global vars $eis["error"] and $eis["errmsg"] 
function eis_call($url,$timestamp,$from,$type,$cmd,$param,&$returnmsg) {
	global $eis;
	eis_clear_error();
	// prepare data
	$calldata=array("timestamp"=>$timestamp,"from"=>$from,"type"=>$type,"cmd"=>$cmd,"param"=>$param);
	// check if host is alive
	if (!($p=parse_url($url,PHP_URL_PORT))) $p=80;
	if (($st=@fsockopen(parse_url($url,PHP_URL_HOST),$p,$errno,$errstr,1))!==false)
		fclose($st);
	else 
		return eis_error("system:hostNotAlive",$url);
	// encode data
	$calldata=eis_encode($calldata);
	// make the POST call and get return data back
   	$options=array("method"=>"POST","content"=>$calldata,"timeout"=>$eis["timeout"],
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