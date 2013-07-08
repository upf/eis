<?php

// **** EIS ****
// eis device implementation
// upf, May2013


//////// required functions 

// device installation function
// return true on success, false on failure
function eis_device_install($callparam) {
	global $eis_conf,$eis_dev_conf,$eis_dev_status;
	// put specific device installation code here
	// in case of error, return eis_error(your_error,your_error_msg)
	// device status will be saved after this call
	return true;
}

// device simulation initialization function
// return true on success, false on failure
function eis_device_init($callparam) {
	global $eis_conf,$eis_dev_status,$eis_dev_conf;
	// put specific device simulation initialization code here
	// in case of error, return eis_error(your_error,your_error_msg)
	// device status will be saved after this call
	return true;
}

// device simulation step function
// return true on success, false on failure
function eis_device_simulate($callparam) {
	global $eis_conf,$eis_dev_status,$eis_dev_conf;
	if (!array_key_exists("cpower",$callparam)) return eis_error("system:parameterMissing","cpower");
	if (!array_key_exists("gpower",$callparam)) return eis_error("system:parameterMissing","gpower");
	$timestamp=$callparam["timestamp"];
	$timestep=$eis_dev_status["sim_step"]*60;
	// update energy in kWh
	for ($p=1; $p<4;$p++) {
		// compute powers from input parameters
		if ($callparam["gpower"][$p]>$callparam["cpower"][$p]) {
			$eis_dev_status["cpower".$p] = $callparam["gpower"][$p]-$callparam["cpower"][$p];
			$eis_dev_status["gpower".$p] = 0;
		}
		else {
			$eis_dev_status["gpower".$p] = $callparam["cpower"][$p]-$callparam["gpower"][$p];
			$eis_dev_status["cpower".$p] = 0;
		}	
		// compute energy in kWh for the current timestep
		$genergy = $eis_dev_status["gpower".$p]*$timestep/3600000.0;
		$cenergy = $eis_dev_status["cpower".$p]*$timestep/3600000.0;
		// update energy counters
		$eis_dev_status["cenergy".$p] = $eis_dev_status["cenergy".$p] + $cenergy;
		$eis_dev_status["genergy".$p] = $eis_dev_status["genergy".$p] + $genergy;
		// update cost counters
		$eis_dev_status["total_sell"] = $eis_dev_status["total_sell"] + $cenergy*sell_price($timestamp);
		$eis_dev_status["total_buy"] = $eis_dev_status["total_buy"] + $genergy*buy_price($timestamp);
	}
	return true;
}


// poweron signal device specific  code
function eis_device_poweron() {
	global $eis_conf,$eis_dev_conf,$eis_dev_status,$eis_mysqli;
	// ************ to be implemented 
	return true;
}

// poweroff signal device specific  code
function eis_device_poweroff() {
	global $eis_conf,$eis_dev_conf,$eis_dev_status,$eis_mysqli;
	// ************ to be implemented 
	return true;
}


// exec device specific commands
// return a return message in standard eis format
function eis_device_exec($calldata) {
	global $eis_conf,$eis_dev_status,$eis_dev_conf;
	$callparam=$calldata["param"];
	switch ($calldata["cmd"]) {
		case "getpriceinfo":
			$returnmsg=eis_ok_msg(array("priceinfo"=>array("two_prices"=>"Italian bioraria","constant_rate"=>"constant rate price plan")));
			break;
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

// get sell price at the current time with the selected plan
function sell_price($timestamp) {
	global $eis_conf,$eis_dev_status,$eis_dev_conf;
	return $eis_dev_status["price_sell"];
}

// get buy price at the current time with the selected plan
function buy_price($timestamp) {
	global $eis_conf,$eis_dev_status,$eis_dev_conf;
	return $eis_dev_status["price_buy"];
}

?>
