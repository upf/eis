<?php

// **** EIS ****
// eis device configuration
// available into global var: $eis_dev_conf
// upf, May2013

// deviceID (the name of the directory where it is) and implementation version
$eis_dev_conf["ID"]=basename(substr(dirname(__FILE__),0,-8));
$eis_dev_conf["version"]="0.0.2";		
$eis_dev_conf["date"]="2013-06-06";		
$eis_dev_conf["author"]="upf";

// device dir absolute path (terminated without /)
$eis_dev_conf["path"]=$eis_conf["path"]."/devices/".$eis_dev_conf["ID"];

// prefix for database tables owned by this device
$eis_dev_conf["tablepfx"]=str_replace(".","_",$eis_dev_conf["ID"]);

// realtime interface communication port (on 127.0.0.1)
// must be different for each device installed in a host
$eis_dev_conf["ifport"]=40000;

// device class (functional type, see documentation)
$eis_dev_conf["class"]="master";

// device short description
$eis_dev_conf["description"]="standard simulation master device";

// device electrical type: "load", "generator", "load&gen" or "virtual"
$eis_dev_conf["type"]="virtual";


// -- device initial status array -- //

// these value will be used during device initialization
// status variables are permanent from an execution to another
// put here any variable that you want to have as permanent
$eis_dev_conf["status"]=array(
	// simulation vars
	"devicescan"=> array(),				// list of device=>host found in the last scan
	"sim_id"  	=> "",					// current simulation id
	"sim_name"  => "empty simulation",	// current simulation name
	"sim_meteo" => "",					// current simulation meteo data ID
	"sim_price" => "",					// current simulation price data ID
	"sim_hour"  => 0,					// current simulation start hour (0-24)
	"sim_step"  => 10,					// current simulation step in minutes
	"sim_type"  => "off-grid",			// current simulation type: off-grid or grid-connected
	"sim_devices"=> array(),			// current simulation active devices  deviceID=>array of device info
	"sim_startime"=> 0,					// current simulation start timestamp
	"sim_endtime"=> 0					// current simulation end timestamp
);


?>
