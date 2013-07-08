<?php

// **** EIS ****
// eis device implementation
// upf, May2013


//////// required functions 

// device installation function
// return true on success, false on failure
function eis_device_install($calldata) {
	global $eis_conf,$eis_dev_conf,$eis_dev_status,$eis_mysqli;
	// create the required mysql table(s)
	$table=$eis_dev_conf["tablepfx"]."_simulations";
	$eis_mysqli->query("DROP TABLE $table");
	$query="CREATE TABLE IF NOT EXISTS `$table` (
		  `simulID` varchar(16) NOT NULL,
		  `timestamp` int(10) unsigned NOT NULL,
		  `type` varchar(20) NOT NULL,
		  `starthour` int(10) unsigned NOT NULL,
		  `step` int(10) unsigned NOT NULL,
		  `meteo` varchar(64) NOT NULL,
		  `price` varchar(64) NOT NULL,
		  `name` varchar(256) NOT NULL,
		  `devices` text NOT NULL,
		  `startime` int(10) unsigned NOT NULL,
		  `endtime` int(10) unsigned NOT NULL
		) ENGINE=InnoDB DEFAULT CHARSET=latin1";
	if (!$eis_mysqli->query($query)) return eis_error("eis_master:cannotCreateDBTable",$eis_mysqli->error);
	return true;
}

// device initialization function
// return true on success, false on failure
function eis_device_init($calldata) {
	global $eis_conf,$eis_dev_conf,$eis_dev_status;
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
	// do nothing
	return true;
}

// poweron signal device specific  code
function eis_device_poweron() {
	global $eis_conf,$eis_dev_conf,$eis_dev_status,$eis_mysqli;
	// do nothing
	return true;
}

// poweroff signal device specific  code
function eis_device_poweroff() {
	global $eis_conf,$eis_dev_conf,$eis_dev_status,$eis_mysqli;
	// do nothing
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

		default:
			// manage unknown command
			eis_log(1,"system:unknownSignal --> ".$calldata["cmd"]);
	}
}


//////// more device specific functions 

// write them here


?>
