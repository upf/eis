<?php

// **** EIS ****
// eis device lib
// upf, May2013

// needs eis system library
require_once("/etc/eis.conf");
include($eis["path"]."/system/eis_system_lib.php");

// open database connection
$eis_mysqli = new mysqli($eis["dbserver"],$eis["user"],$eis["password"],$eis["dbname"]);
if ($eis_mysqli->connect_errno) uhm_call_return_error(0,$eis_mysqli->connect_error);

// open UDP socket for real time interface communication
//$eis_socket = stream_socket_client("udp://127.0.0.1:".$eis_device["ifport"], $errno, $errstr);
$eis_socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
if (!$eis_socket) eis_log(1,"system:cannotOpenInterface --> .$errno - $errstr");

// save dafault configuration
$eis_device_conf=$eis_device;


//////// device status functions

// load the device status array from database
// return true on success, false on failure
// database must be connected before this call
function eis_load_status() {
	global $eis_device,$eis_mysqli;
	eis_clear_error();
	// prepare and make query, checking for errors
	$query="SELECT * FROM status WHERE deviceID='".$eis_device["ID"]."'";
	if (!($result=$eis_mysqli->query($query))) return eis_error("system:cannotLoadStatus",$eis_mysqli->error);
	$row=$result->fetch_array(MYSQLI_ASSOC);
	if (($result->num_rows!=1) or (!array_key_exists("status",$row))) return eis_error("system:wrongStoredStatus",print_r($row));
	// decode status array
	$eis_device["status"]=eis_decode($row["status"]);
	// everything is ok, return true
	return true;
}

// save the device status array to database
// return true on success, false on failure
// database must be connected before this call
function eis_save_status() {
	global $eis_device,$eis_mysqli,$eis_socket;
	eis_clear_error();
	// encode status array
	$status=eis_encode($eis_device["status"]);
    $query="SELECT * FROM status WHERE deviceID='".$eis_device["ID"]."'";			
    if (!$eis_mysqli->query($query)) return eis_error("system:cannotStoreStatus",$eis_mysqli->error);
    if ($eis_mysqli->affected_rows)
    	$eis_mysqli->query("UPDATE status SET status='$status' WHERE deviceID='".$eis_device["ID"]."'");
    else
    	$eis_mysqli->query("INSERT INTO status VALUES ('".$eis_device["ID"]."','$status')");
	@socket_sendto($eis_socket,"reload",6,0,'127.0.0.1',$eis_device["ifport"]);

	return true;
}

// reload dafault configuration 
function eis_default_status() {
	global $eis_device,$eis_device_conf;
	$eis_device=$eis_device_conf;
}

//////// device command and signal execution functions

// exec predefined and device specific commands
// return a return message in standard eis format
function eis_exec($calldata) {
	global $eis,$eis_device;
	$callparam=$calldata["param"];
	if ($calldata["cmd"]!="init")
		if (!eis_load_status()) return array("error"=>$eis["error"],"returnpar"=>array("errordata"=>$eis["errmsg"]));
	// check if device is enabled
	if (!$eis_device["status"]["enabled"]) return array("error"=>"system:notEnabled","returnpar"=>array("errordata"=>""));
	switch ($calldata["cmd"]) {
		// ping command: does nothing and returns the calling parameters
		case "ping":
			$returnmsg=array("error"=>null, "returnpar"=>$callparam);	
			break;
		// delay command: does nothing, simply delays for "duration" seconds (default 10)
		case "delay":
			$duration=10;
			if (isset($callparam["duration"])) $duration=$callparam["duration"];
			sleep($duration);
			$returnmsg=array("error"=>null, "returnpar"=>array());
			break;
		// getlog command: returns a "getlog" param containing and array of the last 10 log lines
		// or the last "numrow" lines if the "numrow" call parameter exists and is positive
		case "getlog":
			$numrow=10;
			$log=array();
			$s=0;
			if (array_key_exists("numrow",$callparam) and $callparam["numrow"]>0) $numrow=$callparam["numrow"];
			if ($log=file($eis["logfile"])) 
				if(sizeof($log)>$numrow) $s=sizeof($log)-$numrow;
			$returnmsg=array("error"=>null, "returnpar"=>array("getlog"=>array_slice($log,$s,$numrow)));
			break;
		// init command: initializes system and device, returns an error on failure
		// requires "timestamp" call parameter containing the initial simulation timestamp
		// in case of success returns device configuration array
		// device is enable and log file cleared
		case "init":
			if (!array_key_exists("timestamp",$callparam)) return array("error"=>"system:timestampMissing","returnpar"=>array("errordata"=>""));
			eis_default_status();
			$eis_device["status"]["masterurl"]=$calldata["from"];
			$eis_device["status"]["timestamp"]=$callparam["timestamp"];
			if (!eis_device_init()) return array("error"=>$eis["error"],"returnpar"=>array("errordata"=>$eis["errmsg"]));
			file_put_contents($eis["logfile"],""); 
			$returnmsg=array("error"=>null, "returnpar"=>$eis_device);
			break;
		// getstatus command: return current device status (a field list can also be specified), returns an error on failure
		case "getstatus":
			$retstatus=array();
			if (array_key_exists("fields",$callparam)) {
				$fields=explode(",",$callparam["fields"]);
    			foreach($fields as $value) 
					if (array_key_exists($value,$eis_device["status"])) $retstatus[$value]=$eis_device["status"][$value];
			}
			else 
				$retstatus=$eis_device["status"];
			$returnmsg=array("error"=>null, "returnpar"=>$retstatus);
			break;
		// setstatus command: set status variables as declared in calldata input parameters
		case "setstatus":
    		foreach($callparam as $key=>$value) 
				if (array_key_exists($key,$eis_device["status"])) $eis_device["status"][$key]=$value;
			$returnmsg=array("error"=>null, "returnpar"=>array());
			break;
		// getconfig command: return current device configuration, returns an error on failure
		case "getconfig":
			$returnmsg=array("error"=>null, "returnpar"=>$eis_device);
			break;
		// other device specific commands
		default:
			$returnmsg=eis_device_exec($calldata);
	}
	if (!eis_save_status())  return array("error"=>$eis["error"],"returnpar"=>array("errordata"=>$eis["errmsg"]));
	return $returnmsg;
}

// process predefined and device specific signals
// return nothing
function eis_signal($calldata) {
	global $eis_device;
	eis_load_status();
	switch ($calldata["cmd"]) {
		// enable signal: enable (turn on) the device
		case "enable":
			$eis_device["status"]["enabled"]=true;
			eis_log(3,"system:enabled");
			break;
		// disable signal: disable (turn off) the device
		case "disable":
			$eis_device["status"]["enabled"]=false;
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
