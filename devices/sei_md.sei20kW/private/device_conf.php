<?php

// **** EIS ****
// eis device configuration
// available into global var: $eis_dev_conf
// upf, May2013

/////////// mandatory configuration fields ///////////

// deviceID (the name of the directory where it is) and implementation version
$eis_dev_conf["ID"]=basename(substr(dirname(__FILE__),0,-8));
$eis_dev_conf["version"]="0.0.1";		
$eis_dev_conf["date"]="2013-06-11";		
$eis_dev_conf["author"]="vitofusco";

// device dir absolute path (terminated without /)
$eis_dev_conf["path"]=$eis_conf["path"]."/devices/".$eis_dev_conf["ID"];

// prefix for database tables owned by this device
$eis_dev_conf["tablepfx"]=str_replace(".","_",$eis_dev_conf["ID"]);

// realtime interface communication port (on 127.0.0.1)
// must be different for each device installed in a host
$eis_dev_conf["ifport"]=40200;

// device class (functional type, see documentation)
$eis_dev_conf["class"]="wind_plant";

// device short description
$eis_dev_conf["description"]="S.E.I. 20 kW wind turbine";

// device electrical type: "load", "generator" or "load&gen"
$eis_dev_conf["type"]="generator";


/////////// available configurations ///////////

// config = specific configuration description
// gpowerX = max generated power for phase X (in watts)
$eis_dev_conf["configurations"]=array(
	// default configuration
	"default" => array (
		"config"=>"default configuration",
		"gpower1"=>6666,
		"gpower2"=>6666,
		"gpower3"=>6666,
	)
);

// -- device initial status array -- //

// these value will be used during device initialization
// status variables are permanent from an execution to another
// put here any variable that you want to have as permanent
$eis_dev_conf["status"]=array(
	// status vars
	"gline"		=> "unprotected",		// connection type
	"gpower1"	=> 0,					// current power on load line 1
	"gpower2"	=> 0,					// current power on load line 2
	"gpower3"	=> 0,					// current power on load line 3
	"genergy1"	=> 0,					// current total energy drained by load line 1
	"genergy2"	=> 0,					// current total energy drained by load line 2
	"genergy3"	=> 0					// current total energy drained by load line 3	
);


?>
