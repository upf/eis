<?php

// **** EIS ****
// eis device configuration
// available into global var: $eis_dev_conf
// upf, May2013

// deviceID (the name of the directory where it is) and implementation version
$eis_dev_conf["ID"]=basename(substr(dirname(__FILE__),0,-8));
$eis_dev_conf["version"]="0.0.1";		
$eis_dev_conf["date"]="2013-06-06";		
$eis_dev_conf["author"]="upf";

// device dir absolute path (terminated without /)
$eis_dev_conf["path"]=$eis_conf["path"]."/devices/".$eis_dev_conf["ID"];

// realtime interface communication port (on 127.0.0.1)
// must be different for each device installed in a host
$eis_dev_conf["ifport"]=31099;

// device class (functional type, see documentation)
$eis_dev_conf["class"]="master";

// device short description
$eis_dev_conf["description"]="standard simulation master device";

// device electrical type: "load", "generator" or "load&gen"
$eis_dev_conf["type"]="load";


// -- "load" or "load&gen" electrical consumption section (delete it if type="generator") -- // 

// electrical consuption connection type: "protected" or "unprotected"
$eis_dev_conf["cline"]="unprotected";

// max consumed power for each phase (in watts)
// set to zero for unused phase(s)
$eis_dev_conf["cpower1"]=0;
$eis_dev_conf["cpower2"]=0;
$eis_dev_conf["cpower3"]=0;


// -- device initial status array -- //

// these value will be used during device initialization
// status variables are permanent from an execution to another
// put here any variable that you want to have as permanent
$eis_dev_conf["status"]=array(
	// system vars
	"timestamp" => 0,					// simulation timestamp
	"masterurl" => "",					// url of the simulation master
	"enabled"   => true, 				// device default enable/disable status
	"power"     => true, 				// device default power status (true=on, false=off)
	// "load" or "both" vars (delete them if type="generator")
	"cpower1"	=> 0,					// consumption power (watt) on phase 1
	"cenergy1"	=> 0, 					// total energy (kWh) consumed by phase 1 from the beginning of simulation


	// add other status vars here as
	// "name" => value
);


?>
