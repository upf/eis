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
function eis_device_init() {
	global $eis_conf,$eis_dev_conf,$eis_dev_status;
	// put specific device initialization code here
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
	if (!array_key_exists("windspeed",$callparam["meteo"])) return eis_error("system:parameterMissing","meteo['windspeed']");
	if (!array_key_exists("cpower",$callparam)) return eis_error("system:parameterMissing","cpower");
	// update energy in kWh
	$eis_dev_status["genergy1"] = $eis_dev_status["genergy1"] + $eis_dev_status["gpower1"]*$timestep/3600000.0;
	// update generated power	
	if ($eis_dev_status["power"]) {
		$gpower1=compute_power($callparam["meteo"]["windspeed"]);
		// check if is off-grid
		if ($eis_dev_status["sim_type"]=="off-grid" and $callparam["cpower"][1]<$gpower1)
			$eis_dev_status["gpower1"]=$callparam["cpower"][1];
		else
			$eis_dev_status["gpower1"]=$gpower1;
	}
	return true;
}

// poweron signal device specific  code
function eis_device_poweron() {
	global $eis_conf,$eis_dev_conf,$eis_dev_status,$eis_mysqli;
	// do nothing, power will be computed at the next step
	return true;
}

// poweroff signal device specific code
function eis_device_poweroff() {
	global $eis_conf,$eis_dev_conf,$eis_dev_status,$eis_mysqli;
	// set the powers to zero
	$eis_dev_status["gpower1"] = 0;
	return true;
}

// exec device specific commands
// return a return message in standard eis format
function eis_device_exec($calldata) {
	global $eis_conf,$eis_dev_conf,$eis_dev_status;
	$callparam=$calldata["param"];
	switch ($calldata["cmd"]) {

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

// calcola le potenze sulle singole fasi in base alla velocità del vento
function compute_power($speed) {
	global $eis_conf,$eis_dev_status,$eis_dev_conf;
	// return to m/s from km/h
	$speed=$speed/3.6;
	// wind generator parameters
	$cutin=3;
	$cutoff=14;
	$powercurve=array(495,720,1000,1480,1750,2100,2500,2640,2200,500,460,500);
	// check if it is not generating
	if ($speed<$cutin or $speed>$cutoff) return 0;
	// else compute power possibly with interpolation
	$i=floor($speed)-$cutin;
	//if ($speed==($i+$cutin) return $power=$powercurve[$i];
	return $powercurve[$i] + ($powercurve[$i+1]-$powercurve[$i])*($speed-$i-$cutin);
}



?>
