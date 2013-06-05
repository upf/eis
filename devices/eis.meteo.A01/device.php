<?php

// **** EIS ****
// eis device implementation
// upf, May2013

// standard includes
require_once("/etc/eis.conf");
include("device_conf.php");
include($eis["path"]."/system/eis_device_lib.php");


//////// required functions 

// device initialization function
// return true on success, false on failure
function eis_device_init() {
	global $eis,$eis_device,$eis_device_conf;
	eis_clear_error();
	// put specific device initialization code here
	// in case of error, return eis_error(your_error,your_error_msg)
	// device status will be saved after this call

	return true;
}

// exec device specific commands
// return a return message in standard eis format
function eis_device_exec($calldata) {
	global $eis,$eis_device,$eis_device_conf;
	$callparam=$calldata["param"];
	switch ($calldata["cmd"]) {
		case "simulate":
			if (!array_key_exists("timestamp",$callparam)) return array("error"=>"system:timestampMissing","returnpar"=>array("errordata"=>""));
			$timestep=$callparam["timestamp"]-$eis_device["status"]["timestamp"];
			// update energy in kWh
			$eis_device["status"]["cenergy1"]= $eis_device["status"]["cenergy1"] + $eis_device["status"]["cpower1"]*$timestep/36000000.0;
			// get and update meteo values
			if ($eis_device["status"]["power"]) {
				$eis_device["status"]["temperature"]=rand(-5,40);
				$eis_device["status"]["humidity"]=rand(0,100);
				$eis_device["status"]["windspeed"]=rand(0,30);
				$eis_device["status"]["winddir"]=rand(0,360);
				$eis_device["status"]["barometer"]=rand(950,1100);
				$eis_device["status"]["radiation"]=rand(0,1100);
			}
			// update timestamp
			$eis_device["status"]["timestamp"]=$callparam["timestamp"];
			// return updated status
			return array("error"=>null, "returnpar"=>$eis_device["status"]);
			break;		
		default:
			// manage unknown command
			$returnmsg=array("error"=>"system:unknownCommand","returnpar"=>array("errordata"=>$calldata["cmd"]));
	}
	return $returnmsg;
}


// process device specific signals
// return nothing
function eis_device_signal($calldata) {
	global $eis,$eis_device,$eis_device_conf;
	$callparam=$calldata["param"];
	switch ($calldata["cmd"]) {
		case "poweron":
				// set power to the default init value
				$eis_device["status"]["power"]=true;
				$eis_device["status"]["cpower1"]=$eis_device_conf["status"]["cpower1"];
				break;
		case "poweroff":
				// set power to zero
				$eis_device["status"]=$eis_device_conf["status"];
				$eis_device["status"]["power"]=false;
				$eis_device["status"]["cpower1"]=0;
				break;
		// put other signals and related code here
		// case "mysignal":
				// your signal code here
				// in case of error, use eis_log() to log error
				// break;

		default:
			// manage unknown command
			eis_log(1,"system:unknownSignal --> ".$calldata["cmd"]);
	}
}


//////// more device specific functions 

// write them here


?>
