<?php

// **** EIS ****
// eis device implementation
// upf, May2013


//////// required functions 

// device initialization function
// return true on success, false on failure
function eis_device_init() {
	global $eis_conf,$eis_dev_conf,$eis_dev_status;
	eis_clear_error();
	// put specific device initialization code here
	// in case of error, return eis_error(your_error,your_error_msg)
	// device status will be saved after this call

	return true;
}

// exec device specific commands
// return a return message in standard eis format
function eis_device_exec($calldata) {
	global $eis_conf,$eis_dev_conf,$eis_dev_status;
	$callparam=$calldata["param"];
	switch ($calldata["cmd"]) {
		case "simulate":
			if (!array_key_exists("timestamp",$callparam)) return eis_error_msg("system:parameterMissing","timestamp");
			$timestep=$callparam["timestamp"]-$eis_dev_status["timestamp"];
			// update energy in kWh
			$eis_dev_status["cenergy1"] = $eis_dev_status["cenergy1"] + $eis_dev_status["cpower1"]*$timestep/36000000.0;
			// update timestamp
			$eis_dev_status["timestamp"]=$callparam["timestamp"];
			// return updated status
			return eis_ok_msg($eis_dev_status);
			break;		
		// put other commands and related code here
		// case "mycommand":
				// your command code here
				// return success: $returnmsg=array("error"=>null, "returnpar"=>yourreturnarray);
				// return failure: $returnmsg=array("error"=>yourerror,"returnpar"=>array("errordata"=>yourerrormsg));
				// break;

		default:
			// manage unknown command
			$returnmsg=eis_error_msg("system:unknownCommand",$calldata["cmd"]);
	}
	return $returnmsg;
}


// process device specific signals
// return nothing
function eis_device_signal($calldata) {
	global $eis_conf,$eis_dev_conf,$eis_dev_status;
	$callparam=$calldata["param"];
	switch ($calldata["cmd"]) {
		case "poweron":
				// power on and set to the default init value
				$eis_dev_status["power"]=true;
				$eis_dev_status["cpower1"]=$eis_dev_conf["status"]["cpower1"];
				break;
		case "poweroff":
				// power off and set to zero
				$eis_dev_status["power"]=false;
				$eis_dev_status["cpower1"]=0;
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
