<?php

// **** EIS ****
// eis device configuration
// available into global var: $eis_dev_conf
// upf, May2013

/////////// mandatory configuration fields ///////////

// deviceID (the name of the directory where it is) and implementation version
$eis_dev_conf["ID"]=basename(substr(dirname(__FILE__),0,-8));
$eis_dev_conf["version"]="0.0.3";
$eis_dev_conf["date"]="2013-07-27";
$eis_dev_conf["author"]="upf";

// device dir absolute path (terminated without /)
$eis_dev_conf["path"]=$eis_conf["path"]."/devices/".$eis_dev_conf["ID"];

// prefix for database tables owned by this device
$eis_dev_conf["tablepfx"]=str_replace(".","_",$eis_dev_conf["ID"]);

// realtime interface communication port (on 127.0.0.1)
// must be different for each device installed in a host
$eis_dev_conf["ifport"]=40100;

// device class (functional type)
$eis_dev_conf["class"]="meteo_station";

// device short description
$eis_dev_conf["description"]="virtual meteo station like Davis Vantage Pro 2";

// device electrical type (load, generator, load&gen)
$eis_dev_conf["type"]="virtual";


/////////// available configurations ///////////

// config = specific configuration description
date_default_timezone_set($eis_conf["timezone"]);
$eis_dev_conf["configurations"]=array(
	// default configuration
	"default" => array (
		"config"=>"default configuration",
		// available meteo data sets
		"dataIDs" => array(
			"synthetic"=>array("start"=>1366149600,"duration"=>1,"location"=>"somewhere","description"=>"1 working day synthetic data, sunny with wind"),
			"random"=>array("start"=>strtotime(date("Y-m-d")),"duration"=>1,"location"=>"------","description"=>"1 day, random data")
		)
	)
);


// device status array
// these value will be used during device initialization
$eis_dev_conf["status"]=array(
	// meteo data ID
	"sim_meteo"	 => "synthetic",	//  meteo data ID
	// current meteo variables
	"temperature"=> 0,			// Celsius
	"humidity"   => 0,			// %
	"windspeed"  => 0,			// m/s
	"winddir"    => 0,			// degree 0-360
	"pressure"   => 0,			// mbar
	"radiation"  => 0, 			// w/m2
	// previous timestep meteo data array (needed for interpolation)
	"oldata"	 => array()
);



?>
