<?php

// **** EIS ****
// eis device configuration
// available into global var: $eis_dev_conf
// upf, May2013

// deviceID (the name of the directory where it is) and implementation version
$eis_dev_conf["ID"]=basename(substr(dirname(__FILE__),0,-8));
$eis_dev_conf["version"]="0.0.1";		
$eis_dev_conf["date"]="2013-13-06";		
$eis_dev_conf["author"]="vitofusco";

// device dir absolute path (terminated without /)
$eis_dev_conf["path"]=$eis_conf["path"]."/devices/".$eis_dev_conf["ID"];

// realtime interface communication port (on 127.0.0.1)
// must be different for each device installed in a host
$eis_dev_conf["ifport"]=3225;

// device class (functional type, see documentation)
$eis_dev_conf["class"]="wind_plant";

// device short description
$eis_dev_conf["description"]="2,5 kW turbine simulator";

// device electrical type: "load", "generator" or "load&gen"
$eis_dev_conf["type"]="generator";


// -- "load" or "load&gen" electrical consumption section (delete it if type="generator") -- // 

// electrical consuption connection type: "protected" or "unprotected"
$eis_dev_conf["cline"]="unprotected";

// max consumed power for each phase (in watts)
// set to zero for unused phase(s)
// 20kW / 3 = 6666.66... watts = 6667
$eis_dev_conf["gpower1"]=2640;
$eis_dev_conf["gpower2"]=0;
$eis_dev_conf["gpower3"]=0;


// -- device initial status array -- //

// these value will be used during device initialization
// status variables are permanent from an execution to another
// put here any variable that you want to have as permanent
$eis_dev_conf["status"]=array(
	// system vars
	"timestamp" => 0,					// simulation timestamp
	"masterurl" => "",		// url of the simulation master
	"enabled"   => true, 				// device default enable/disable status
	"power"     => true, 
	"gpower1"	=> 100,					// current power on load line 1
	"genergy1"	=> 0 ,					// current total energy drained by load line 1
	
);


?>
