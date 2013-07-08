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

// prefix for database tables owned by this device
$eis_dev_conf["tablepfx"]=str_replace(".","_",$eis_dev_conf["ID"]);

// realtime interface communication port (on 127.0.0.1)
// must be different for each device installed in a host
$eis_dev_conf["ifport"]=40100;

// device class (functional type)
$eis_dev_conf["class"]="meteo_station";

// device short description
$eis_dev_conf["description"]="a virtual meteo station";

// device electrical type (load, generator, load&gen)
$eis_dev_conf["type"]="virtual";


// device status array
// these value will be used during device initialization
$eis_dev_conf["status"]=array(
	// meteo specific
	"temperature"=> 0,			// Celsius
	"humidity"   => 0,			// %
	"windspeed"  => 0,			// m/s
	"winddir"    => 0,			// degree 0-360
	"pressure"   => 0,			// mbar
	"radiation"  => 0 			// w/m2
	
);



?>
