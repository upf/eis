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
		$eis_dev_status["cenergy".$p] += $eis_dev_status["cpower".$p]*$timestep/3600000.0;
	return true;
}

// poweron signal device specific  code
function eis_device_poweron() {
	global $eis_conf,$eis_dev_conf,$eis_dev_status,$eis_mysqli;
	// set the powers to their defaults
	if ($eis_dev_status["fullpower"]) $powerlevel=1; else $powerlevel=0.5; 
	for ($p=1; $p<4;$p++)
		if ($p==$eis_dev_status["connected"]) 
			$eis_dev_status["cpower".$p] = $eis_dev_conf["cpower".$p]*$powerlevel;
	return true;
}

// poweroff signal device specific code
function eis_device_poweroff() {
	global $eis_conf,$eis_dev_conf,$eis_dev_status,$eis_mysqli;
	// set the powers to zero
	for ($p=1; $p<4;$p++) $eis_dev_status["cpower".$p] = 0;
	return true;
}


// exec device specific commands
// return a return message in standard eis format
function eis_device_exec($calldata) {
	global $eis_conf,$eis_dev_conf,$eis_dev_status;
	$callparam=$calldata["param"];
	switch ($calldata["cmd"]) {
		case "connect":
			if (!array_key_exists("phase",$callparam)) return eis_error_msg("system:parameterMissing","phase");
			if ($callparam["phase"]<1 or $callparam["phase"]>3) $phase=1; else $phase=$callparam["phase"];
			$eis_dev_status["connected"]=$phase;
			if ($eis_dev_status["fullpower"]) $powerlevel=1; else $powerlevel=0.5; 
			for($p=1;$p<4;$p++)
				if ($p==$phase)
					$eis_dev_status["cpower$p"]=$eis_dev_conf["cpower".$p]*$powerlevel;
				else
					$eis_dev_status["cpower$p"]=0;
			$returnmsg=
				eis_ok_msg(array("cpower1"=>$eis_dev_status["cpower1"],"cpower2"=>$eis_dev_status["cpower2"],"cpower3"=>$eis_dev_status["cpower3"]));
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
	global $eis_conf,$eis_dev_conf,$eis_dev_status;
	$callparam=$calldata["param"];
	switch ($calldata["cmd"]) {
		// set power level to 1
		case "halfpower":
			$eis_dev_status["fullpower"]=false;
			$p=$eis_dev_status["connected"];
			$eis_dev_status["cpower".$p] = $eis_dev_conf["cpower".$p]*0.5;
			break;
		// set power level to 2
		case "fullpower":
			$eis_dev_status["fullpower"]=true;
			$p=$eis_dev_status["connected"];
			$eis_dev_status["cpower".$p] = $eis_dev_conf["cpower".$p];
			break;
		default:
			// manage unknown command
			eis_log(1,"system:unknownSignal --> ".$calldata["cmd"]);
	}
}


//////// more device specific functions 

// write them here


?>
