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
	if (!array_key_exists("radiation",$callparam["meteo"])) return eis_error("system:parameterMissing","meteo['radiation']");
	if (!array_key_exists("cpower",$callparam)) return eis_error("system:parameterMissing","cpower");
	// compute generated power	
	if ($eis_dev_status["power"]) {
		$gpower=intval(compute_power($callparam["meteo"])/3);
		for ($p=1;$p<4;$p++) 
			// check if is off-grid or on protected line
			if (($eis_dev_status["sim_type"]=="off-grid" or $eis_dev_status["gline"]=="protected") and $callparam["cpower"][$p]<$gpower)
				$eis_dev_status["gpower$p"]=$callparam["cpower"][$p];
			else
				$eis_dev_status["gpower$p"]=$gpower;
	}
	// update energy in kWh
	for ($p=1;$p<4;$p++)
		$eis_dev_status["genergy$p"] = $eis_dev_status["genergy$p"] + $eis_dev_status["gpower$p"]*$timestep/3600000.0;
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
	for ($p=1;$p<4;$p++) $eis_dev_status["gpower$p"]=0;
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

		default:
			// manage unknown command
			eis_log(1,"system:unknownSignal --> ".$calldata["cmd"]);
	}
}


//////// more device specific functions 

// compute total generated power
function compute_power($meteo) {
	global $eis_conf,$eis_dev_status,$eis_dev_conf;
	$radiation=$meteo["radiation"];
	$temperature=$meteo["temperature"];
	$kw=3*$eis_dev_conf["gpower1"]/1000.0;
	return $radiation*1.1*0.95*(1-0.05-($temperature-25-(25*$radiation/800))*0.5/100)*$kw;
}



?>
