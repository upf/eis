<?php

// **** EIS ****
// eis device configuration
// available into global var: $eis_device
// upf, May2013

// deviceID (the name of the directory where it is) and implementation version
$eis_device["ID"]=basename(dirname(__FILE__));
$eis_device["version"]="0.0.1";
$eis_device["date"]="2013-06-02";
$eis_device["author"]="upf";

// device dir absolute path (terminated without /)
$eis_device["path"]=$eis["path"]."/".$eis_device["ID"];

// realtime interface communication port (on 127.0.0.1)
// must be different for each device installed in a host
$eis_device["ifport"]=31033;

// device class (functional type)
$eis_device["class"]="meteo_station";

// device short description
$eis_device["description"]="a virtual meteo station";

// device electrical type (load, generator, both)
$eis_device["type"]="load";

// electrical connection load line type (protected or unprotected)
$eis_device["cline"]="unprotected";

// max consumed power for each phase (in watts)
// set to zero for unused phase(s)
$eis_device["cpower1"]=15;
$eis_device["cpower2"]=0;
$eis_device["cpower3"]=0;

// device status array
// these value will be used during device initialization
$eis_device["status"]=array(
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
	"barometer"  => 1000,
	"radiation"  => 0
	
);



?>
