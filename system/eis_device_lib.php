<?php

// **** EIS ****
// eis device lib
// upf, May2013

// needs eis system library
include($eis_conf["path"]."/system/eis_system_lib.php");

// open database connection
$eis_mysqli = new mysqli($eis_conf["dbserver"],$eis_conf["user"],$eis_conf["password"],$eis_conf["dbname"]);
if ($eis_mysqli->connect_errno) eis_send_returnmsg(eis_error_msg("system:databaseFailure",$eis_mysqli->connect_error));

// open UDP socket for real time interface communication
//$eis_socket = stream_socket_client("udp://127.0.0.1:".$eis_dev_conf["ifport"], $errno, $errstr);
$eis_socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
if (!$eis_socket) eis_log(1,"system:cannotOpenInterface --> .$errno - $errstr");

// init device status
$eis_dev_status=$eis_dev_conf["status"];


//////// device status functions

// load the device status array from database
// return true on success, false on failure
// database must be connected before this call
function eis_load_status() {
	global $eis_dev_conf,$eis_dev_status,$eis_mysqli;
	eis_clear_error();
	// prepare and make query, checking for errors
	$query="SELECT * FROM status WHERE deviceID='".$eis_dev_conf["ID"]."'";
	if (!($result=$eis_mysqli->query($query))) return eis_error("system:cannotLoadStatus",$eis_mysqli->error);
	$row=$result->fetch_array(MYSQLI_ASSOC);
	if (($result->num_rows!=1) or (!array_key_exists("status",$row))) return eis_error("system:wrongStoredStatus",print_r($row,true));
	// decode status array
	$eis_dev_status=eis_decode($row["status"]);
	// everything is ok, return true
	return true;
}

// save the device status array to database
// return true on success, false on failure
// database must be connected before this call
function eis_save_status() {
	global $eis_dev_conf,$eis_dev_status,$eis_mysqli,$eis_socket;
	eis_clear_error();
	// encode status array
	$status=eis_encode($eis_dev_status);
    $query="SELECT * FROM status WHERE deviceID='".$eis_dev_conf["ID"]."'";			
    if (!$eis_mysqli->query($query)) return eis_error("system:cannotStoreStatus",$eis_mysqli->error);
    if ($eis_mysqli->affected_rows)
    	$eis_mysqli->query("UPDATE status SET status='$status' WHERE deviceID='".$eis_dev_conf["ID"]."'");
    else
    	$eis_mysqli->query("INSERT INTO status VALUES ('".$eis_dev_conf["ID"]."','$status')");
	@socket_sendto($eis_socket,"reload",6,0,'127.0.0.1',$eis_dev_conf["ifport"]);
	return true;
}

// reload dafault configuration 
function eis_default_status() {
	global $eis_dev_conf,$eis_dev_status;
	$eis_dev_status=$eis_dev_conf["status"];
}

//////// device command and signal execution functions

// exec predefined and device specific commands
// return a return message in standard eis format
function eis_exec($calldata) {
	global $eis_conf,$eis_dev_conf,$eis_dev_status;
	$callparam=$calldata["param"];
	// if init do not load status
	if ($calldata["cmd"]!="init") 
		if (!eis_load_status()) return eis_error_msg($eis_conf["error"],$eis_conf["errmsg"]);
	// check if device is enabled
	if (!$eis_dev_status["enabled"] and $calldata["cmd"]!="init") return eis_error_msg("system:notEnabled","");
	// process command
	switch ($calldata["cmd"]) {
		// ping command: does nothing and returns the calling parameters
		case "ping":
			$returnmsg=eis_ok_msg($callparam);	
			break;
		// delay command: does nothing, simply delays for "duration" seconds (default 10)
		case "delay":
			$duration=10;
			if (isset($callparam["duration"])) $duration=$callparam["duration"];
			sleep($duration);
			$returnmsg=eis_ok_msg(null);
			break;
		// getlog command: returns a "getlog" param containing and array of the last 10 log lines
		// or the last "numrow" lines if the "numrow" call parameter exists and is positive
		case "getlog":
			$numrow=10;
			$log=array();
			$s=0;
			if (array_key_exists("numrow",$callparam) and $callparam["numrow"]>0) $numrow=$callparam["numrow"];
			if ($log=file($eis_conf["logfile"])) 
				if(sizeof($log)>$numrow) $s=sizeof($log)-$numrow;
			$returnmsg=eis_ok_msg(array("getlog"=>array_slice($log,$s,$numrow)));
			break;
		// init command: initializes system and device, returns an error on failure
		// requires "timestamp" call parameter containing the initial simulation timestamp
		// in case of success returns device configuration array
		// device is enable and log file cleared
		case "init":
			if (!array_key_exists("timestamp",$callparam)) return eis_error_msg("system:timestampMissing","");
			eis_default_status();
			$eis_dev_status["masterurl"]=$calldata["from"];
			$eis_dev_status["timestamp"]=$callparam["timestamp"];
			if (!eis_device_init()) return eis_error_msg($eis_conf["error"],$eis_conf["errmsg"]);
			eis_log_reset();
			$r=$eis_dev_conf;
			$r["status"]=$eis_dev_status;
			$returnmsg=eis_ok_msg($r);
			break;
		// getstatus command: return current device status (a field list can also be specified), returns an error on failure
		case "getstatus":
			$retstatus=array();
			if (array_key_exists("fields",$callparam)) {
				$fields=explode(",",$callparam["fields"]);
    			foreach($fields as $value) 
					if (array_key_exists($value,$eis_dev_status)) $retstatus[$value]=$eis_dev_status[$value];
			}
			else 
				$retstatus=$eis_dev_status;
			$returnmsg=eis_ok_msg($retstatus);
			break;
		// setstatus command: set status variables as declared in calldata input parameters
		case "setstatus":
    		foreach($callparam as $key=>$value) 
				if (array_key_exists($key,$eis_dev_status)) $eis_dev_status[$key]=$value;
			$returnmsg=eis_ok_msg(null);
			break;
		// getconfig command: return current device configuration, returns an error on failure
		case "getconfig":
			$returnmsg=eis_ok_msg($eis_dev_conf);
			break;
		// help command: returns the device help (if any) in a field named "help"
		case "help":
			$hstr="\n**** General ****\nversion: ".$eis_dev_conf["version"]."\ndate: ".$eis_dev_conf["date"].
				"\nauthor: ".$eis_dev_conf["author"]."\nclass: ".$eis_dev_conf["class"].
				"\ntype: ".$eis_dev_conf["type"]."\ndescription: ".$eis_dev_conf["description"]."\n";
			if (file_exists($eis_dev_conf["path"]."/private/help.txt")) {
				$help=file($eis_dev_conf["path"]."/private/help.txt");
 				foreach ($help as $line) {
    				$line=ltrim($line);
    				if ($line[0]=="#") continue;
    				$line=str_replace("{**", "\n**** ", $line);
        			$line=str_replace("**}", " ****", $line);
        			$line=str_replace("[**", "\n[", $line);
        			$line=str_replace("**]", "]", $line);
        			$line=str_replace("(**", "\n(", $line);
        			$line=str_replace("**)", ")", $line);
    				$hstr=$hstr.$line;
    			}
			}
			else
				$hstr=$hstr."\nno further help available\n";
			$returnmsg=eis_ok_msg(array("help"=>$hstr));
			break;
		// other device specific commands
		default:
			$returnmsg=eis_device_exec($calldata);
	}
	if (!eis_save_status())  return eis_error_msg($eis_conf["error"],$eis_conf["errmsg"]);
	return $returnmsg;
}

// process predefined and device specific signals
// return nothing
function eis_signal($calldata) {
	global $eis_conf,$eis_dev_conf,$eis_dev_status;
	eis_load_status();
	switch ($calldata["cmd"]) {
		// enable signal: enable (turn on) the device
		case "enable":
			$eis_dev_status["enabled"]=true;
			eis_log(3,"system:enabled");
			break;
		// disable signal: disable (turn off) the device
		case "disable":
			$eis_dev_status["enabled"]=false;
			eis_log(3,"system:disabled");
			break;
		// other device specific signals
		default:
			eis_device_signal($calldata);
		}
	eis_save_status(); 
	return $returnmsg;
}



?>
