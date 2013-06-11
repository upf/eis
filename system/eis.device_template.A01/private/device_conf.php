<?php

// **** EIS ****
// eis device configuration
// available into global var: $eis_dev_conf
// upf, May2013

// deviceID (the name of the directory where it is) and implementation version
$eis_dev_conf["ID"]=basename(substr(dirname(__FILE__),0,-8));
$eis_dev_conf["version"]="0.0.1";		// 3 numbers separated by full stop
$eis_dev_conf["date"]="2013-05-26";		// yyyy-mm-dd format
$eis_dev_conf["author"]="xxxxxxx";

// device dir absolute path (terminated without /)
$eis_dev_conf["path"]=$eis_conf["path"]."/devices/".$eis_dev_conf["ID"];

// realtime interface communication port (on 127.0.0.1)
// must be different for each device installed in a host
$eis_dev_conf["ifport"]=31032;

// device class (functional type, see documentation)
$eis_dev_conf["class"]="generic_device";

// device short description
$eis_dev_conf["description"]="a generic device for testing";

// device electrical type: "load", "generator" or "load&gen"
$eis_dev_conf["type"]="load&gen";


// -- "load" or "load&gen" electrical consumption section (delete it if type="generator") -- // 

// electrical consuption connection type: "protected" or "unprotected"
$eis_dev_conf["cline"]="unprotected";

// max consumed power for each phase (in watts)
// set to zero for unused phase(s)
$eis_dev_conf["cpower1"]=200;	// monophase example
$eis_dev_conf["cpower2"]=0;
$eis_dev_conf["cpower3"]=0;


// -- "generator" or "load&gen" electrical generation section (delete it if type="load") -- // 

// electrical generation connection type: "protected" or "unprotected"
$eis_dev_conf["gline"]="unprotected";

// max generated power for each phase (in watts)
// set to zero for unused phase(s)
$eis_dev_conf["gpower1"]=500;	// threephase example
$eis_dev_conf["gpower2"]=500;
$eis_dev_conf["gpower3"]=500;


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
	"cpower1"	=> 100,					// consumption power (watt) on phase 1
	"cpower2"	=> 0,					// same for phase 2
	"cpower3"	=> 0,					// same for phase 2
	"cenergy1"	=> 0, 					// total energy (kWh) consumed by phase 1 from the beginning of simulation
	"cenergy2"	=> 0, 					// same for phase 2
	"cenergy3"	=> 0, 					// same for phase 3

	// "generator" or "both" vars (delete them if type="load")
	"gpower1"	=> 100,					// generation power (watt) on phase 1
	"gpower2"	=> 100,					// same for phase 2
	"gpower3"	=> 100,					// same for phase 2
	"genergy1"	=> 0, 					// total energy (kWh) generated by phase 1 from the beginning of simulation
	"genergy2"	=> 0, 					// same for phase 2
	"genergy3"	=> 0 					// same for phase 3

	// add other status vars here as
	// "name" => value
);


?>