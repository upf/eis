<?php

// **** EIS ****
// eis device implementation
// upf, May2013


//////// required functions 

// device installation function
// return true on success, false on failure
function eis_device_install($callparam) {
	global $eis_conf,$eis_dev_conf,$eis_dev_status,$eis_mysqli;
	// create the required mysql table(s)
	$table=$eis_dev_conf["tablepfx"]."_prices";
	$eis_mysqli->query("DROP TABLE $table");
	$query="CREATE TABLE IF NOT EXISTS `$table` (
		  `priceID` varchar(64) NOT NULL,
		  `description` varchar(256) NOT NULL,
		  `prices` text NOT NULL
		) ENGINE=InnoDB DEFAULT CHARSET=latin1";
	if (!$eis_mysqli->query($query)) return eis_error($eis_dev_conf["ID"].":cannotCreateDBTable",$eis_mysqli->error);
	// load price plan data into mysql
	foreach($eis_dev_conf["priceIDs"] as $id=>$d) {
		$prices=array("buy"=>array(),"sell"=>array());
		$count=0;
		$data=file($eis_dev_conf["path"]."/private/$id.prices");
		foreach ($data as $line) {
		    $line=trim($line);
		    // skip comments and blank lines
		    if ($line=="" or $line[0]=="#") continue;
		    // read prices
		    $values=explode(";",$line);
		    $count++;
		    // first 7 are buy prices
		    if ($count<=7) $prices["buy"][$count]=$values;
		    // second 7 are sell prices
		    if ($count>7) $prices["sell"][$count-7]=$values;
		}
		// save data into table
		$query="INSERT INTO ".$eis_dev_conf["tablepfx"]."_prices VALUES ('$id','$d','".json_encode($prices)."')";
		if (!$eis_mysqli->query($query)) return eis_error($eis_dev_conf["ID"].":cannotSavePricedata",$eis_mysqli->error);
	}
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
		// if disconnected set powers to zero
		if ($eis_dev_status["gridstatus"]!="ok") {
			$eis_dev_status["cpower".$p] = 0;
			$eis_dev_status["gpower".$p] = 0;
		}
		else {
			// else compute powers from input parameters
			if ($callparam["gpower"][$p]>$callparam["cpower"][$p]) {
				$eis_dev_status["cpower".$p] = $callparam["gpower"][$p]-$callparam["cpower"][$p];
				$eis_dev_status["gpower".$p] = 0;
			}
			else {
				$eis_dev_status["gpower".$p] = $callparam["cpower"][$p]-$callparam["gpower"][$p];
				$eis_dev_status["cpower".$p] = 0;
			}
		}	
		// compute energy in kWh for the current timestep
		$genergy = $eis_dev_status["gpower".$p]*$timestep/3600000.0;
		$cenergy = $eis_dev_status["cpower".$p]*$timestep/3600000.0;
		// update energy counters
		$eis_dev_status["cenergy".$p] = $eis_dev_status["cenergy".$p] + $cenergy;
		$eis_dev_status["genergy".$p] = $eis_dev_status["genergy".$p] + $genergy;
		// update prices
		$query="SELECT * FROM ".$eis_dev_conf["tablepfx"]."_prices WHERE priceID='".$eis_dev_conf["price"]."'";
		if (!($result=$eis_mysqli->query($query))) return eis_error($eis_dev_conf["ID"].":cannotLoadPricedata",$eis_mysqli->error);
		if (($result->num_rows!=1)) return eis_error($eis_dev_conf["ID"].":wrongStoredPricedata",print_r($row,true));
		$row=$result->fetch_array(MYSQLI_ASSOC);
		$prices=json_decode($row["prices"],true);
		$dayweek=date("w",$timestamp);
		if ($dayweek==0) $dayweek=7;
		$hour=date("G",$timestamp);
		$eis_dev_status["price_buy"]=$prices["buy"][$dayweek][$hour];
		$eis_dev_status["price_sell"]=$prices["sell"][$dayweek][$hour];
		// update cost counters
		$eis_dev_status["total_sell"] = $eis_dev_status["total_sell"] + $cenergy*$eis_dev_status["price_buy"];
		$eis_dev_status["total_buy"] = $eis_dev_status["total_buy"] + $genergy*$eis_dev_status["price_sell"];
		// check connection overload and overgen
		if ($eis_dev_status["gpower".$p]>$eis_dev_conf["gpower".$p]) $eis_dev_status["gridstatus"]="overload";
		if ($eis_dev_status["cpower".$p]>$eis_dev_conf["cpower".$p]) $eis_dev_status["gridstatus"]="overgen";
		if ($eis_dev_status["gridstatus"]!="ok") $eis_dev_status["power"]=false;
	}
	return true;
}


// poweron signal device specific  code
function eis_device_poweron() {
	global $eis_conf,$eis_dev_conf,$eis_dev_status,$eis_mysqli;
	$eis_dev_status["gridstatus"]="ok"; 
	return true;
}

// poweroff signal device specific  code
function eis_device_poweroff() {
	global $eis_conf,$eis_dev_conf,$eis_dev_status,$eis_mysqli;
	$eis_dev_status["gridstatus"]="disconnected"; 
	return true;
}


// exec device specific commands
// return a return message in standard eis format
function eis_device_exec($calldata) {
	global $eis_conf,$eis_dev_status,$eis_dev_conf;
	$callparam=$calldata["param"];
	switch ($calldata["cmd"]) {
		case "getpriceinfo":
			$returnmsg=eis_ok_msg(array("priceinfo"=>$eis_dev_conf["priceIDs"]));
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




?>
