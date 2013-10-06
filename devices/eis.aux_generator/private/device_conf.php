<?php

// **** EIS ****
// eis device configuration
// available into global var: $eis_dev_conf
// upf, May2013

/////////// mandatory configuration fields ///////////

// deviceID (the name of the directory where it is) and implementation version
$eis_dev_conf["ID"]=basename(substr(dirname(__FILE__),0,-8));
$eis_dev_conf["version"]="0.0.3";
$eis_dev_conf["date"]="2013-08-08";
$eis_dev_conf["author"]="upf";

// device dir absolute path (terminated without /)
$eis_dev_conf["path"]=$eis_conf["path"]."/devices/".$eis_dev_conf["ID"];

// prefix for database tables owned by this device (terminated without /)
$eis_dev_conf["tablepfx"]=str_replace(".","_",$eis_dev_conf["ID"]);

// realtime interface communication port (on 127.0.0.1)
// must be different for each device installed in a host
$eis_dev_conf["ifport"]=40400;

// device class (functional type)
$eis_dev_conf["class"]="auxiliary_generator";

// device electrical type (load, generator, load&gen)
$eis_dev_conf["type"]="generator";

// device short description
$eis_dev_conf["description"]="configurable gas or diesel auxiliary generator";


/////////// available configurations ///////////

// config = specific configuration description
// gpowerX = max consumption power for phase X (in watts), set to zero for unused phase(s)
// conversion = engine generator efficiency (generated kWh per liter)
// fulkeprice = fuel price (Euro per liter)
$eis_dev_conf["configurations"]=array(
	// gasoline engine small generator with ITA fuel price
	"gas_3kW_1P" => array (
		"config"=>"3kW 1P gasoline generator (average Italian gas price)",
		"gpower1"=>3000,
		"gpower2"=>0,
		"gpower3"=>0,
		"conversion"=>15,
		"fuelprice"=>1.75
	),
	// diesel engine small generator with ITA fuel price
	"diesel_3kW_1P" => array (
		"config"=>"3kW 1P diesel generator (average Italian lightoil price)",
		"gpower1"=>3000,
		"gpower2"=>0,
		"gpower3"=>0,
		"conversion"=>20,
		"fuelprice"=>1.65
	),
	// gasoline engine large generator with ITA fuel price
	"gas_19.8kW" => array (
		"config"=>"19.8kW gasoline generator (average Italian gas price)",
		"gpower1"=>6600,
		"gpower2"=>6600,
		"gpower3"=>6600,
		"conversion"=>15,
		"fuelprice"=>1.75
	),
	// diesel engine large generator with ITA fuel price
	"diesel_19.8kW" => array (
		"config"=>"19.8kW diesel generator (average Italian lightoil price)",
		"gpower1"=>6600,
		"gpower2"=>6600,
		"gpower3"=>6600,
		"conversion"=>20,
		"fuelprice"=>1.65
	)
);

// device status array
// these value will be used during device initialization
$eis_dev_conf["status"]=array(
	// set default configuration
	"configID" => "diesel_3kW_1P",
	// grid connection status var
	"glinestatus"  => "ok",			// "ok","disconnected","overload" (last 2 == no power, blackout)
	// grid consumption vars (generating on unprotected line == buying from grid)
	"gline"		=> "unprotected",		// set always to "unprotected"
	"gpower1"	=> 0,			// current generated power on line 1
	"genergy1"	=> 0, 			// current total generated energy by line 1
	"gpower2"	=> 0,			// current generated power on line 2
	"genergy2"	=> 0, 			// current total generated energy by line 2
	"gpower3"	=> 0,			// current generated power on line 3
	"genergy3"	=> 0, 			// current total generated energy by line 3
	// costs
	"total_cost" => 0			// current total cost for generation
);

?>
