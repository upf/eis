<?php

// **** EIS ****
// eis device implementation
// upf, May2013


//////// required functions 

// device installation function
// return true on success, false on failure
function eis_device_install($callparam) {
	global $eis_conf,$eis_dev_conf,$eis_dev_status,$eis_mysqli;
	// put specific device installation code here
	// in case of error, return eis_error(your_error,your_error_msg)
	// device status will be saved after this call
	return true;
}

// device initialization function
// return true on success, false on failure
function eis_device_init($callparam) {
	global $eis_conf,$eis_dev_conf,$eis_dev_status,$eis_mysqli;
	// put specific device simulation initialization code here
	// in case of error, return eis_error(your_error,your_error_msg)
	// device status will be saved after this call
	return true;
}

// device simulation function
// return true on success, false on failure
// in case of error, return eis_error(your_error,your_error_msg)
function eis_device_simulate($callparam) {
	global $eis_conf,$eis_dev_conf,$eis_dev_status,$eis_mysqli;
	$timestamp=$eis_dev_status["timestamp"];
	$timestep=$eis_dev_status["sim_step"]*60;
	// update assorbed energy in kWh
	for ($p=1; $p<4;$p++)
		if (array_key_exists("cpower".$p,$eis_dev_status)) $eis_dev_status["cenergy".$p] += $eis_dev_status["cpower".$p]*$timestep/3600000.0;
	// update generated energy in kWh
	for ($p=1; $p<4;$p++)
		if (array_key_exists("gpower".$p,$eis_dev_status)) $eis_dev_status["genergy".$p] += $eis_dev_status["gpower".$p]*$timestep/3600000.0;
	// update powers
		// constant powers, nothing to do
	return true;
}

// poweron signal device specific  code
function eis_device_poweron() {
	global $eis_conf,$eis_dev_conf,$eis_dev_status,$eis_mysqli;
	// set the powers to theis defaults
	for ($p=1; $p<4;$p++) {
		if (array_key_exists("cpower".$p,$eis_dev_status)) $eis_dev_status["cpower".$p] = $eis_dev_conf["status"]["cpower".$p];
		if (array_key_exists("gpower".$p,$eis_dev_status)) $eis_dev_status["gpower".$p] = $eis_dev_conf["status"]["gpower".$p];
	}
	return true;
}

// poweroff signal device specific  code
function eis_device_poweroff() {
	global $eis_conf,$eis_dev_conf,$eis_dev_status,$eis_mysqli;
	// set the powers to zero
	for ($p=1; $p<4;$p++) {
		if (array_key_exists("cpower".$p,$eis_dev_status)) $eis_dev_status["cpower".$p] = 0;
		if (array_key_exists("gpower".$p,$eis_dev_status)) $eis_dev_status["gpower".$p] = 0;
	}
	return true;
}


// exec device specific commands
// return a return message in standard eis format
function eis_device_exec($calldata) {
	global $eis_conf,$eis_dev_conf,$eis_dev_status;
	$callparam=$calldata["param"];
	switch ($calldata["cmd"]) {
		// put other commands and related code here
		// case "mycommand":
				// your command code here
				// return success: $returnmsg=eis_ok_msg(youroutputparam_array);
				// return failure: $returnmsg=eis_error_msg(yourerror,yourerrormessage);
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
