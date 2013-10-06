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
	// different initialization for grid-connected or off-grid simulations
	if ($callparam["sim_type"]=="grid-connected") {
		$eis_dev_status["chargebattery"]=true;
		$eis_dev_status["bypass"]=100;
	}
	else {
		$eis_dev_status["chargebattery"]=false;
		$eis_dev_status["bypass"]=0;		
	}
	return true;
}

// device simulation function
// return true on success, false on failure
// in case of error, return eis_error(your_error,your_error_msg)
function eis_device_simulate($callparam) {
	global $eis_conf,$eis_dev_conf,$eis_dev_status,$eis_mysqli;
	$timestamp=$eis_dev_status["timestamp"];
	$timestep=$eis_dev_status["sim_step"]*60;
	// init battery powers  
	$eis_dev_status["bgpower"]=0;
	$eis_dev_status["bcpower"]=0;
	// check if the device is powered off
	if (!$eis_dev_status["power"]) {
		for ($p=1; $p<4;$p++) $eis_dev_status["cpower".$p] = 0;
		for ($p=1; $p<4;$p++) $eis_dev_status["gpower".$p] = 0;
		return true;
	}
	// set battery charger power
	if ($eis_dev_status["chargebattery"]) {
		if ($eis_dev_status["benergy"]==100) {
			if ($eis_dev_status["sim_type"]=="grid-connected") 
				$eis_dev_status["bcpower"]=$eis_dev_conf["bfloatpower"];	// full, use float power when grid-connected
			else {
				$eis_dev_status["chargebattery"]=false;						// full, return to battery only mode when off-grid
				$eis_dev_status["bypass"]=0;		
			}
		}
		else
			$eis_dev_status["bcpower"]=$eis_dev_conf["bchargepower"];  	// not full, use charging power
	}
	// blackout management
	// when entering blackout on unprotected line, use only battery with no charge
	if ($callparam["blackout"] and !$eis_dev_status["blackout"]) {
		$eis_dev_status["bypass"]=0;
		$eis_dev_conf["bcpower"]=0;
		$eis_dev_status["chargebattery"]=false;
		$eis_dev_status["blackout"]=true;
	}
	// when exiting from blackout on unprotected line, use the correct battery parameters
	if (!$callparam["blackout"] and $eis_dev_status["blackout"]) {
		$eis_dev_status["blackout"]=false;
		if ($eis_dev_status["sim_type"]=="grid-connected") {
			$eis_dev_status["chargebattery"]=true;
			$eis_dev_status["bypass"]=100;
		}
		else {
			$eis_dev_status["chargebattery"]=false;
			$eis_dev_status["bypass"]=0;		
		}
	}
	// simulate each phase
	for ($p=1; $p<4;$p++) {
		// if disconnected set gpowers to zero
		if ($eis_dev_status["glinestatus"]!="ok")
			$eis_dev_status["gpower".$p] = 0;
		// else compute gpowers from input parameters
		else {			
			$eis_dev_status["gpower".$p] = $callparam["cpower"][$p]-$callparam["gpower"][$p];
			// check protected line overload and overgen
			if ($eis_dev_status["gpower".$p]>$eis_dev_conf["gpower".$p]) $eis_dev_status["glinestatus"]="overload";
			if ($eis_dev_status["gpower".$p]<0) $eis_dev_status["glinestatus"]="overgen";
			if ($eis_dev_status["glinestatus"]!="ok") for ($p=1; $p<4;$p++) $eis_dev_status["gpower".$p]=0;
		}
		// in off-grid mode bypass values can be only 0 or 100
		if ($eis_dev_status["sim_type"]=="off-grid" and $eis_dev_status["bypass"]) $eis_dev_status["bypass"]=100;
		// take from the unprotected line (bypass%) of the requested protected-line power
		$eis_dev_status["cpower".$p]=intval($eis_dev_status["gpower".$p]*$eis_dev_status["bypass"]/100.0);
		// and take the remaining power from the battery
		$eis_dev_status["bgpower"]+=$eis_dev_status["gpower".$p]-$eis_dev_status["cpower".$p];
		// finally add charging power to the unprotected line
		$eis_dev_status["cpower".$p]+=intval($eis_dev_status["bcpower"]/3.0);
		// update energies in kWh
		$eis_dev_status["genergy".$p] += $eis_dev_status["gpower".$p]*$timestep/3600000.0;
		$eis_dev_status["cenergy".$p] += $eis_dev_status["cpower".$p]*$timestep/3600000.0;
	}	
	// update battery energies
	$bcenergy=$eis_dev_status["bcpower"]*$timestep/3600000.0;
	$bgenergy=$eis_dev_status["bgpower"]*$timestep/3600000.0;
	$eis_dev_status["bcenergy"] += $bcenergy;
	$eis_dev_status["bgenergy"] += $bgenergy;
	$eis_dev_status["benergy"] += ($bcenergy-$bgenergy)*100.0/$eis_dev_conf["bmaxstoredenergy"];
	if ($eis_dev_status["benergy"]<0) $eis_dev_status["benergy"]=0;
	if ($eis_dev_status["benergy"]>100) $eis_dev_status["benergy"]=100;
	// if battery level is too low, bypass and power on charger
	// if blackout on unprotected line, disconnect also protected line
	if ($eis_dev_status["benergy"]<=$eis_dev_conf["bminstoredenergy"]) {
		if ($eis_dev_status["blackout"])
			$eis_dev_status["glinestatus"]="disconnected";
		else {
			$eis_dev_status["chargebattery"]=true;
			$eis_dev_status["bypass"]=100;
			$eis_dev_status["glinestatus"]="ok";
		}
	}
	return true;
}

// poweron signal device specific  code
function eis_device_poweron() {
	global $eis_conf,$eis_dev_conf,$eis_dev_status,$eis_mysqli;
	$eis_dev_status["glinestatus"]="ok"; 
	if ($eis_dev_status["sim_type"]=="grid-connected") {
		$eis_dev_status["chargebattery"]=true;
		$eis_dev_status["bypass"]=100;
	}
	else {
		$eis_dev_status["chargebattery"]=false;
		$eis_dev_status["bypass"]=0;		
	}
	return true;
}

// poweroff signal device specific code
function eis_device_poweroff() {
	global $eis_conf,$eis_dev_conf,$eis_dev_status,$eis_mysqli;
	$eis_dev_status["glinestatus"]="disconnected"; 
	$eis_dev_status["chargebattery"]=false; 
	// set powers to zero
	for ($p=1; $p<4;$p++) $eis_dev_status["cpower".$p] = 0;
	for ($p=1; $p<4;$p++) $eis_dev_status["gpower".$p] = 0;
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
		// switch on the battery charger
		case "chargebatteryon":
			$eis_dev_status["chargebattery"]=true;
			break;
		// switch off the battery charger
		case "chargebatteryoff":
			$eis_dev_status["chargebattery"]=false;
			break;
		default:
			// manage unknown command
			eis_log(1,"system:unknownSignal --> ".$calldata["cmd"]);
	}
}


//////// more device specific functions 

// write them here


?>
