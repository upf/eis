<?php

// **** EIS ****
// eis device implementation
// upf, May2013


//////// required functions 

// device installation function
// return true on success, false on failure
function eis_device_install($callparam) {
	global $eis_conf,$eis_dev_conf,$eis_dev_status,$eis_mysqli;
	return true;
}

// device simulation initialization function
// return true on success, false on failure
function eis_device_init($callparam) {
	global $eis_conf,$eis_dev_status,$eis_dev_conf;
	return true;
}

// device simulation step function
// return true on success, false on failure
function eis_device_simulate($callparam) {
	global $eis_conf,$eis_dev_status,$eis_dev_conf,$eis_mysqli;
	if (!array_key_exists("cpower",$callparam)) return eis_error("system:parameterMissing","cpower");
	if (!array_key_exists("gpower",$callparam)) return eis_error("system:parameterMissing","gpower");
	$timestamp=$callparam["timestamp"];
	$timestep=$eis_dev_status["sim_step"]*60;
	// for each phase
	for ($p=1; $p<4;$p++) {
		// if disconnected set power to zero
		if ($eis_dev_status["glinestatus"]!="ok") 
			$eis_dev_status["gpower".$p] = 0;
		else 
			$eis_dev_status["gpower".$p] = $callparam["cpower"][$p]-$callparam["gpower"][$p];
		// compute energy in kWh for the current timestep
		$genergy = $eis_dev_status["gpower".$p]*$timestep/3600000.0;
		// update energy counters
		$eis_dev_status["genergy".$p] = $eis_dev_status["genergy".$p] + $genergy;
		// update cost counter
		$eis_dev_status["total_cost"] = $eis_dev_status["total_cost"] + $genergy*$eis_dev_conf["fuelprice"]/$eis_dev_conf["conversion"];
		// check connection overload and overgen
		if ($eis_dev_status["gpower".$p]>$eis_dev_conf["gpower".$p]) $eis_dev_status["glinestatus"]="overload";
		if ($eis_dev_status["gpower".$p]<0) $eis_dev_status["glinestatus"]="overgen";
		if ($eis_dev_status["glinestatus"]!="ok") $eis_dev_status["power"]=false;
	}
	return true;
}


// poweron signal device specific  code
function eis_device_poweron() {
	global $eis_conf,$eis_dev_conf,$eis_dev_status,$eis_mysqli;
	$eis_dev_status["glinestatus"]="ok"; 
	return true;
}

// poweroff signal device specific  code
function eis_device_poweroff() {
	global $eis_conf,$eis_dev_conf,$eis_dev_status,$eis_mysqli;
	$eis_dev_status["glinestatus"]="disconnected"; 
	for ($p=1; $p<4;$p++) $eis_dev_status["gpower".$p] = 0;
	return true;
}


// exec device specific commands
// return a return message in standard eis format
function eis_device_exec($calldata) {
	global $eis_conf,$eis_dev_status,$eis_dev_conf;
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
	global $eis_conf,$eis_dev_status,$eis_dev_conf;
	$callparam=$calldata["param"];
	switch ($calldata["cmd"]) {

		default:
			// manage unknown command
			eis_log(1,"system:unknownSignal --> ".$calldata["cmd"]);
	}
}


//////// more device specific functions 




?>
