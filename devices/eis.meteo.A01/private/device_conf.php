<?php

// **** EIS ****
// eis device configuration
// available into global var: $eis_dev_conf
// upf, May2013

// deviceID (the name of the directory where it is) and implementation version
$eis_dev_conf["ID"]=basename(substr(dirname(__FILE__),0,-8));
$eis_dev_conf["version"]="0.0.1";
$eis_dev_conf["date"]="2013-06-02";
$eis_dev_conf["author"]="upf";

// device dir absolute path (terminated without /)
$eis_dev_conf["path"]=$eis_conf["path"]."/devices/".$eis_dev_conf["ID"];

// realtime interface communication port (on 127.0.0.1)
// must be different for each device installed in a host
$eis_dev_conf["ifport"]=31033;

// device class (functional type)
$eis_dev_conf["class"]="meteo_station";

// device short description
$eis_dev_conf["description"]="a virtual meteo station";

// device electrical type (load, generator, load&gen)
$eis_dev_conf["type"]="load";

// electrical connection load line type (protected or unprotected)
$eis_dev_conf["cline"]="unprotected";

// max consumed power for each phase (in watts)
// set to zero for unused phase(s)
$eis_dev_conf["cpower1"]=15;
$eis_dev_conf["cpower2"]=0;
$eis_dev_conf["cpower3"]=0;

// device status array
// these value will be used during device initialization
$eis_dev_conf["status"]=array(
	// system vars
	"timestamp" => 0,					// simulation timestamp
	"masterurl" => "",					// url of the simulation master
	"enabled"   => true, 				// device default enable/disable status
	"power"     => true, 				// device default power status (true=on, false=off)
	// device specific vars
	"cpower1"	=> 15,					// current power on load line 1
	"cenergy1"	=> 0, 					// current total energy drained by load line 1
	// meteo specific
	"temperature"=> 0,
	"humidity"   => 0,
	"windspeed"  => 0,
	"winddir"    => 0,
	"pressure"  => 0,
	"radiation"  => 0
	
);



?>
