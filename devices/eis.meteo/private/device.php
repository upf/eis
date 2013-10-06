<?php

// **** EIS ****
// eis device implementation
// upf, May2013


//////// required functions 

// device installation function
// return true on success, false on failure
function eis_device_install($callparam) {
	global $eis_conf,$eis_dev_conf,$eis_dev_status;
	global $eis_conf,$eis_dev_conf,$eis_dev_status,$eis_mysqli;
	// create the required mysql table(s)
	$table=$eis_dev_conf["tablepfx"]."_meteodata";
	$eis_mysqli->query("DROP TABLE $table");
	$query="CREATE TABLE IF NOT EXISTS `$table` (
		  `timestamp` int(11) NOT NULL,
		  `temperature` float NOT NULL,
		  `humidity` float NOT NULL,
		  `windspeed` float NOT NULL,
		  `winddir` float NOT NULL,
		  `pressure` float NOT NULL,
		  `radiation` float NOT NULL
		) ENGINE=InnoDB DEFAULT CHARSET=latin1";
	if (!$eis_mysqli->query($query)) return eis_error($eis_dev_conf["ID"].":cannotCreateDBTable",$eis_mysqli->error);
	// load meteo data into mysql
	foreach($eis_dev_conf["dataIDs"] as $id=>$d) {
		if ($id=="random") continue;
		$data=file($eis_dev_conf["path"]."/private/$id.meteo");
		foreach ($data as $line) {
		    $line=trim($line);
		    // skip comments and blank lines
		    if ($line=="" or $line[0]=="#") continue;
		    // read meteo data for a given timestamp
		    $m=explode(";",$line);
			// save data into table
			$query="INSERT INTO $table VALUES (".$m[0].",".$m[1].",".$m[2].",".$m[3].",".$m[4].",".$m[5].",".$m[6].")";
			if (!$eis_mysqli->query($query)) return eis_error($eis_dev_conf["ID"].":cannotSaveMeteodata",$eis_mysqli->error);
		}
	}
	return true;
}


// device initialization function
// return true on success, false on failure
function eis_device_init($callparam) {
	global $eis_conf,$eis_dev_status,$eis_dev_conf,$eis_mysqli;
	if (!array_key_exists("sim_meteo",$callparam)) return eis_error("system:parameterMissing","sim_meteo");
	if (!array_key_exists($callparam["sim_meteo"],$eis_dev_conf["dataIDs"]))
		return eis_error($eis_dev_conf["ID"].":unknownMeteodataID",$callparam["sim_meteo"]);
	$eis_dev_status["sim_meteo"]=$callparam["sim_meteo"];
	// load the first data into oldata array for interpolation
	if ($eis_dev_status["sim_meteo"]!="random") {
		$query="SELECT * FROM ".$eis_dev_conf["tablepfx"]."_meteodata WHERE timestamp=".$callparam["timestamp"];
		if (!($result=$eis_mysqli->query($query))) return eis_error($eis_dev_conf["ID"].":cannotLoadMeteodata",$eis_mysqli->error);
		if (($result->num_rows!=1)) return eis_error($eis_dev_conf["ID"].":wrongStoredMeteodata",print_r($row,true));
		$eis_dev_status["oldata"]=$result->fetch_array(MYSQLI_ASSOC);
	}
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
			return eis_ok_msg(array("datainfo"=>$eis_dev_conf["dataIDs"]));
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
	global $eis_conf,$eis_dev_status,$eis_dev_conf,$eis_mysqli;
	if ($eis_dev_status["sim_meteo"]=="random") {
		// returns filtered random data
		$odata=$eis_dev_status["oldata"];
		$a=0.98; // smoothing coefficient
		$eis_dev_status["temperature"]=(rand(-5,40)+$a*$odata["temperature"])/(1+$a);
		$eis_dev_status["humidity"]=(rand(0,100)+$a*$odata["humidity"])/(1+$a);
		$eis_dev_status["windspeed"]=(rand(0,30)+$a*$odata["windspeed"])/(1+$a);
		$eis_dev_status["winddir"]=(rand(0,360)+$a*$odata["winddir"])/(1+$a);
		$eis_dev_status["pressure"]=(rand(950,1100)+$a*$odata["pressure"])/(1+$a);
		$eis_dev_status["radiation"]=(rand(0,1100)+$a*$odata["radiation"])/(1+$a);
		$eis_dev_status["oldata"]["temperature"]=$eis_dev_status["temperature"];
		$eis_dev_status["oldata"]["humidity"]=$eis_dev_status["humidity"];
		$eis_dev_status["oldata"]["windspeed"]=$eis_dev_status["windspeed"];
		$eis_dev_status["oldata"]["winddir"]=$eis_dev_status["winddir"];
		$eis_dev_status["oldata"]["pressure"]=$eis_dev_status["pressure"];
		$eis_dev_status["oldata"]["radiation"]=$eis_dev_status["radiation"];
	}
	else {
		// or returns data from a stored meteo data set with linear interpolation
		$query="SELECT * FROM ".$eis_dev_conf["tablepfx"]."_meteodata WHERE timestamp>=$timestamp LIMIT 1";
		if (!($result=$eis_mysqli->query($query))) return eis_error($eis_dev_conf["ID"].":cannotLoadMeteodata",$eis_mysqli->error);
		$data=$result->fetch_array(MYSQLI_ASSOC);
		// compute linearly interpolated values
		$odata=$eis_dev_status["oldata"];
		$ts=$data["timestamp"]-$odata["timestamp"];
		if ($ts==0) $ts=1;  // only to avoid division by 0 at the beginning
		$eis_dev_status["temperature"]=$odata["temperature"]+($data["temperature"]-$odata["temperature"])*($timestamp-$odata["timestamp"])/$ts;
		$eis_dev_status["humidity"]=$odata["humidity"]+($data["humidity"]-$odata["humidity"])*($timestamp-$odata["timestamp"])/$ts;
		$eis_dev_status["windspeed"]=$odata["windspeed"]+($data["windspeed"]-$odata["windspeed"])*($timestamp-$odata["timestamp"])/$ts;
		$eis_dev_status["winddir"]=$odata["winddir"]+($data["winddir"]-$odata["winddir"])*($timestamp-$odata["timestamp"])/$ts;
		$eis_dev_status["pressure"]=$odata["pressure"]+($data["pressure"]-$odata["pressure"])*($timestamp-$odata["timestamp"])/$ts;
		$eis_dev_status["radiation"]=$odata["radiation"]+($data["radiation"]-$odata["radiation"])*($timestamp-$odata["timestamp"])/$ts;
		// updata previous timestep data
		if ($timestamp==$data["timestamp"]) $eis_dev_status["oldata"]=$data;
	}
}

?>
