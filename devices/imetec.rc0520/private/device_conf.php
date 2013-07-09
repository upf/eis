<?php

// **** EIS ****
// eis device configuration
// available into global var: $eis_dev_conf
// upf, May2013

// deviceID (the name of the directory where it is) and implementation version
$eis_dev_conf["ID"]=basename(substr(dirname(__FILE__),0,-8));
$eis_dev_conf["version"]="0.0.1";		// 3 numbers separated by full stop
$eis_dev_conf["date"]="2013-07-07";		// yyyy-mm-dd format
$eis_dev_conf["author"]="upf";

// device dir absolute path (terminated without /)
$eis_dev_conf["path"]=$eis_conf["path"]."/devices/".$eis_dev_conf["ID"];

// prefix for database tables owned by this device
$eis_dev_conf["tablepfx"]=str_replace(".","_",$eis_dev_conf["ID"]);

// realtime interface communication port (on 127.0.0.1)
// must be different for each device installed in a host
$eis_dev_conf["ifport"]=41000;

// device class (functional type, see documentation)
$eis_dev_conf["class"]="bathroom_heater";

// device short description
$eis_dev_conf["description"]="1-phase portable bathroom electric heater with 2 power levels (1 or 2 kW)";

// device electrical type: "load", "generator" or "load&gen"
$eis_dev_conf["type"]="load";

// max consumed power for each phase (in watts)
// set to zero for unused phase(s)
$eis_dev_conf["cpower1"]=5000;	
$eis_dev_conf["cpower2"]=5000;
$eis_dev_conf["cpower3"]=5000;

// -- device initial status array -- //

// these value will be used during device initialization
// status variables are permanent from an execution to another
// put here any variable that you want to have as permanent
$eis_dev_conf["status"]=array(
	"cline"		=> "unprotected",		// default electrical consuption connection type: "protected" or "unprotected"
	"cpower1"	=> 1000,				// default consumption power (watt) on phase 1
	"cpower2"	=> 0,					// same for phase 2
	"cpower3"	=> 0,					// same for phase 3
	"cenergy1"	=> 0, 					// total energy (kWh) consumed by phase 1 from the beginning of simulation
	"cenergy2"	=> 0, 					// same for phase 2
	"cenergy3"	=> 0, 					// same for phase 3
	"connected" => 1, 					// phase (1,2,or 3) where it is connected
	"powerlevel"=> 0.5 					// power level (from 0.0 to 1.0) default half power mode
);


?>
