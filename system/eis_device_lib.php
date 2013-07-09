<?php

// **** EIS ****
// eis device lib
// upf, May2013

// needs eis system library
include($eis_conf["path"]."/system/eis_system_lib.php");

// open UDP socket for real time interface communication
$eis_socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
if (!$eis_socket) eis_log(1,"system:cannotOpenInterface --> .$errno - $errstr");

// init predefined device status vars
eis_default_status();



//////// device call functions

// return the complete url of the given $deviceID locate in the given $hostname
function eis_dev_geturl($deviceID,$hostname) {
	global $eis_conf;
	if ($hostname=="") $hostname=$eis_conf["host"];
    return "http://$hostname/eis/$deviceID";
}

// make an eis call to a device and get back the results (wrapper of eis_call)
// $device = deviceID@hostname (if @hostname is not given, localhost is assumed)
// $type = call type, $cmd = command or signal name, $inputpar = input parameter array $outputpar = output parameter array
// return true on success, false on failure (error code and error data are available into $eis_error and $eis_errmsg)
function eis_dev_call($device,$type,$cmd,$inputpar,&$outputpar) {
	global $eis_conf,$eis_dev_conf;
	eis_clear_error();
	$a=explode("@",$device);
	if (sizeof($a)>1) $h=$a[1]; else $h=$eis_conf["host"];
	eis_call(eis_dev_geturl($a[0],$h),time(),eis_dev_geturl($eis_dev_conf["ID"],$eis_conf["host"]),$type,$cmd,$inputpar,$returnmsg);
	$outputpar=$returnmsg["returnpar"];
	if ($returnmsg["error"]) return eis_error($returnmsg["error"],$returnmsg["returnpar"]["errordata"]);
	return true;
}


//////// device status functions

// add and set predefined status vars 
function eis_set_predefined_var_status() {
	global $eis_dev_status;
	$eis_dev_status["power"]=true; 			// device default power status (true=on, false=off)
	$eis_dev_status["enabled"]=true;		// enable/disable status
	$eis_dev_status["masterurl"]="";		// url of the simulation master
	$eis_dev_status["timestamp"]=0;			// simulation current timestamp
	$eis_dev_status["sim_id"]="0000";		// current simulation id
	$eis_dev_status["sim_step"]=10;			// current simulation step in minutes
	$eis_dev_status["sim_type"]="off-grid";	// current simulation type: off-grid or grid-connected
	$eis_dev_status["blackout"]=false;		// current line blackout status
}

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
	if ($result->num_rows==0) return eis_error("system:wrongStoredStatus","no status stored for this device");
	if ($result->num_rows>1) return eis_error("system:wrongStoredStatus","multiple status stored for this device");
	if (!array_key_exists("status",$row)) return eis_error("system:wrongStoredStatus","status field missing in mysql table");
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

// reload default configuration status 
function eis_default_status() {
	global $eis_dev_conf,$eis_dev_status;
	$eis_dev_status=$eis_dev_conf["status"];
	eis_set_predefined_var_status();
}

//////// device command and signal execution functions

// exec predefined and device specific commands
// return a return message in standard eis format
function eis_exec($calldata) {
	global $eis_conf,$eis_dev_conf,$eis_dev_status,$eis_error,$eis_errmsg;
	eis_clear_error();
	$callparam=$calldata["param"];
	// if init do not load status
	if ($calldata["cmd"]!="reset" and $calldata["cmd"]!="install") 
		if (!eis_load_status()) return eis_error_msg($eis_error,$eis_errmsg);
	// check if device is enabled
	if (!$eis_dev_status["enabled"] and $calldata["cmd"]!="reset" and $calldata["cmd"]!="install" and $calldata["cmd"]!="init")
		return eis_error_msg("system:notEnabled",$eis_dev_conf["ID"]);
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
			if (array_key_exists("numrow",$callparam) and $callparam["numrow"]>0) $numrow=$callparam["numrow"];
			$returnmsg=eis_ok_msg(array("getlog"=>eis_log_get($eis_dev_conf["ID"],$numrow)));
			break;
		// install command: execute custom installation code and reset the device
		case "install":
			eis_default_status();
			eis_clear_error();
			if (!eis_device_install($callparam)) return eis_error_msg($eis_error,$eis_errmsg);
			$returnmsg=eis_ok_msg($eis_dev_status);
			break;	
		// reset command: reset the device to its initial installation status
		// save the new status and return device configuration array, logs are cleared
		case "reset":
			eis_default_status();
			$returnmsg=eis_ok_msg($eis_dev_conf);
			break;
		// init command: reset device and initialize simulation
		// requires some simulation parameters
		// in case of success returns actual status array
		case "init":
			if (!array_key_exists("timestamp",$callparam)) return eis_error_msg("system:parameterMissing","timestamp");
			if (!array_key_exists("sim_id",$callparam)) return eis_error_msg("system:parameterMissing","sim_id");
			if (!array_key_exists("sim_step",$callparam)) return eis_error_msg("system:parameterMissing","sim_step");
			if (!array_key_exists("sim_type",$callparam)) return eis_error_msg("system:parameterMissing","sim_type");
			if (!array_key_exists("cline",$callparam)) return eis_error_msg("system:parameterMissing","cline");
			if (!array_key_exists("gline",$callparam)) return eis_error_msg("system:parameterMissing","gline");
			eis_default_status();
			$eis_dev_status["masterurl"]=$calldata["from"];
			reset($callparam);
			foreach ($callparam as $k=>$v) $eis_dev_status[$k]=$v;
			eis_clear_error();
			if (!eis_device_init($callparam)) return eis_error_msg($eis_error,$eis_errmsg);
			$returnmsg=eis_ok_msg($eis_dev_status);
			break;
		// simulate command: do the simulation step at time timestamp, on success return some device dependent data
		case "simulate":
			if (!array_key_exists("timestamp",$callparam)) return eis_error_msg("system:parameterMissing","timestamp");
			// check parameters for loads and generators
			if ($eis_dev_conf["type"]=="load" or $eis_dev_conf["type"]=="generator") {
				if (!array_key_exists("meteo",$callparam)) return eis_error_msg("system:parameterMissing","meteo");
				if (!array_key_exists("blackout",$callparam)) return eis_error_msg("system:parameterMissing","blackout");
				if ($callparam["blackout"] and !$eis_dev_status["blackout"]) {
					eis_signal(array("cmd"=>"poweroff"));
					$eis_dev_status["blackout"]=true;
				}
				if (!$callparam["blackout"] and $eis_dev_status["blackout"]) {
					eis_signal(array("cmd"=>"poweron"));
					$eis_dev_status["blackout"]=false;
				}
			}
			eis_clear_error();
			if (!eis_device_simulate($callparam)) return eis_error_msg($eis_error,$eis_errmsg);
			$eis_dev_status["timestamp"]=$callparam["timestamp"];  // update timestamp
			$returnmsg=eis_ok_msg($eis_dev_status);
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
		// if "status" parameter is set to "current" return current status instead of the default
		case "getconfig":
			$ret=$eis_dev_conf;
			if (array_key_exists("status",$callparam) and $callparam["status"]=="current")
				$ret["status"]=$eis_dev_status;
			$returnmsg=eis_ok_msg($ret);
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
    				if (isset($line[0]) and $line[0]=="#") continue;
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
			if ($returnmsg["error"]) return eis_error_msg($returnmsg["error"],$returnmsg["returnpar"]["errordata"]);
	}
	if (!eis_save_status())  return eis_error_msg($eis_error,$eis_errmsg);
	return $returnmsg;
}

// process predefined and device specific signals
// return nothing
function eis_signal($calldata) {
	global $eis_conf,$eis_dev_conf,$eis_dev_status,$eis_error,$eis_errmsg;
	eis_load_status();
	switch ($calldata["cmd"]) {
		// enable signal: enable the device
		case "enable":
			$eis_dev_status["enabled"]=true;
			eis_log(3,"system:enabled");
			break;
		// disable signal: disable the device
		case "disable":
			$eis_dev_status["enabled"]=false;
			eis_log(3,"system:disabled");
			break;
		// power signal: power on the device
		case "poweron":
			$eis_dev_status["power"]=true;
			eis_clear_error();
			if (!eis_device_poweron())
				eis_log(2,$eis_error."  ".$eis_errmsg);
			break;
		// disable signal: power off the device
		case "poweroff":
			$eis_dev_status["power"]=false;
			eis_clear_error();
			if (!eis_device_poweroff())
				eis_log(2,$eis_error."  ".$eis_errmsg);
			break;
		// other device specific signals
		default:
			eis_device_signal($calldata);
		}
	eis_save_status(); 
	return $returnmsg;
}



?>
