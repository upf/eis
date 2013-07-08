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

// device initialization function
// return true on success, false on failure
function eis_device_init($callparam) {
	global $eis_conf,$eis_dev_status,$eis_dev_conf;
	// put specific device simulation initialization code here
	// in case of error, return eis_error(your_error,your_error_msg)
	// device status will be saved after this call
	return true;
}

// device initialization function
// return true on success, false on failure
function eis_device_simulate($callparam) {
	global $eis_conf,$eis_dev_status,$eis_dev_conf;
	$timestamp=$callparam["timestamp"];
	$timestep=$eis_dev_status["sim_step"]*60;
	// compute new meteo values
	if ($eis_dev_status["power"]) compute_meteo($timestamp);
	return true;
}

// poweron signal device specific  code
function eis_device_poweron() {
	global $eis_conf,$eis_dev_conf,$eis_dev_status,$eis_mysqli;
	// poweron recompute meteo data
	compute_meteo($eis_dev_status["timestamp"]);
	return true;
}

// poweroff signal device specific  code
function eis_device_poweroff() {
	global $eis_conf,$eis_dev_conf,$eis_dev_status,$eis_mysqli;
	// poweroff freezes meteo data
	return true;
}



// exec device specific commands
// return a return message in standard eis format
function eis_device_exec($calldata) {
	global $eis_conf,$eis_dev_status,$eis_dev_conf;
	$callparam=$calldata["param"];
	switch ($calldata["cmd"]) {
		case "getdatainfo":
			return eis_ok_msg(array("datainfo"=>array(
				"sunnyspring"=>array("start"=>1000000,"duration"=>2,"location"=>"vallesina","description"=>"2 sunny spring days"),
				"rainyoctober"=>array("start"=>2000000,"duration"=>2,"location"=>"vallesina","description"=>"2 rainy october days"),
				"mixsummer"=>array("start"=>3000000,"duration"=>3,"location"=>"ancona","description"=>"3 variable weather summer days"),
				"random"=>array("start"=>strtotime(date("Y-m-d")),"duration"=>1,"location"=>"------","description"=>"1 day, random data")
				)));
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

// compute the meteo data at a specific timestamp
function compute_meteo($timestamp) {
	global $eis_conf,$eis_dev_status,$eis_dev_conf;
	// very first implementation, returns random data
	$eis_dev_status["temperature"]=rand(-5,40);
	$eis_dev_status["humidity"]=rand(0,100);
	$eis_dev_status["windspeed"]=rand(0,30);
	$eis_dev_status["winddir"]=rand(0,360);
	$eis_dev_status["pressure"]=rand(950,1100);
	$eis_dev_status["radiation"]=rand(0,1100);
}

?>
