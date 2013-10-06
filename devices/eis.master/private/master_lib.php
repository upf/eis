<?php

// **** EIS ****
// master device specific lib
// upf, Jun2013


// save simulation into db
// simulation = array with keys: id,type,hour,step,meteo,price,name,devices
// return true on success false on failure
function eis_master_savesim($simulation) {
	global $eis_dev_conf,$eis_mysqli;
	eis_clear_error();
	extract($simulation,EXTR_OVERWRITE);
	$query="INSERT INTO ".$eis_dev_conf["tablepfx"]."_simulations VALUES ('$id',".time().",'$type',$hour,$step,'$meteo',
		'$name','".eis_encode($devices)."',$startime,$endtime)";
	if (!$eis_mysqli->query($query)) return eis_error("master:cannotSaveSimulation",$eis_mysqli->error);
	return true;
} 

// load simulation with a given id from db into the master status array
// return true on success, false on failure
function eis_master_loadsim($simulationID) {
	global $eis_dev_conf,$eis_mysqli,$eis_dev_status;
	eis_clear_error();
	$query="SELECT * FROM ".$eis_dev_conf["tablepfx"]."_simulations WHERE simulID='$simulationID'";
	if (!($result=$eis_mysqli->query($query))) return eis_error("master:cannotLoadSimulation",$eis_mysqli->error);
	if (($result->num_rows!=1)) return eis_error("master:wrongStoredSimulation",print_r($row,true));
	$row=$result->fetch_array(MYSQLI_ASSOC);
    // save simulation data into status
    $eis_dev_status["sim_id"]=$row["simulID"];
    $eis_dev_status["sim_name"]=$row["name"];
    $eis_dev_status["sim_meteo"]=$row["meteo"];
    $eis_dev_status["sim_hour"]=$row["starthour"];
    $eis_dev_status["sim_step"]=$row["step"];
    $eis_dev_status["sim_type"]=$row["type"];
    $eis_dev_status["sim_startime"]=$row["startime"];
    $eis_dev_status["sim_endtime"]=$row["endtime"];
    $eis_dev_status["sim_devices"]=eis_decode($row["devices"]);
	return true;
} 

// delete a simulation with a given id from db
// return true on success, false on failure
function eis_master_deletesim($simulationID) {
	global $eis_dev_conf,$eis_mysqli;
	eis_clear_error();
	$query="DELETE FROM ".$eis_dev_conf["tablepfx"]."_simulations WHERE simulID='$simulationID'";
	if (!($result=$eis_mysqli->query($query))) return eis_error("master:cannotDeleteSimulation",$eis_mysqli->error);
	return true;
}


// find all devices, if $network is true search also in LAN else search only in localhost
// for all found device get info from eis system and call it for getting cline and gline information
// return an array as deviceID => deviceinfo, in case of failure deviceID => cline,gline are set to "error"
function eis_master_getdevices($network) {
	global $eis_conf;
	// find local installed devices
	$info=eis_getdevices("");
	foreach($info as $d=>$i) {
		$info[$d]["host"]=$eis_conf["host"];
		$info[$d]["selected"]=false;
	}
    // if requested search also for published devices on the network
    if ($network) {
    	// ************* this part has to be written
    }
	foreach ($info as $d=>$i)
	    if (eis_dev_call($d."@".$i["host"],"exec","getstatus",array("fields"=>"cline,gline,configID"),$outpar)) { 
	        if (array_key_exists("cline",$outpar)) $info[$d]["cline"]=$outpar["cline"]; else $info[$d]["cline"]="-----";
	        if (array_key_exists("gline",$outpar)) $info[$d]["gline"]=$outpar["gline"]; else $info[$d]["gline"]="-----";
	        $info[$d]["configID"]=$outpar["configID"];
	    }
	    else {
	        $info[$d]["cline"]="error";
	        $info[$d]["gline"]="error";
	        $info[$d]["configID"]="error";
	    }
	return $info;
}

// check the devicescan status array for simulation rule violations (see code for explanation).
// only "selected" devices are cosnidered
// return an array of strings describing one violation each or false if no violation found 
function eis_master_ruleviolations(&$sim_type) {
	global $eis_dev_conf,$eis_dev_status;
	$violations=array();
	$devicesinfo=$eis_dev_status["devicescan"];
	// scan device info array and implement some rules
	$grid=$auxgen=$storage=$master=$meteo=0;
	$unprotectedloads=$protected=0;
	foreach ($devicesinfo as $d=>$i) {
		if (!array_key_exists("selected",$i) or !$i["selected"]) continue;
	    if ($i["class"]=="meteo_station")
	    	if ($meteo) 
	    		$violations[]="$d: only 1 'meteo_station' class device is allowed";
	    	else 
	    		$meteo++;
	    if ($i["class"]=="master")
	    	if ($master) 
	    		$violations[]="$d: only 1 'master' class device is allowed";
	    	else 
	    		$master++;
	    if ($i["class"]=="grid")
	    	if ($grid) 
	    		$violations[]="$d: only 1 'grid' class device is allowed";
	    	else {
	    		$grid++;
	    		$sim_type="grid-connected";
	    		if ($i["cline"]=="protected" or $i["gline"]=="protected")
	    			$violations[]="$d 'grid' device must be connected to the unprotected line";
	    	}
	    if ($i["class"]=="electrical_storage") 
	    	if ($storage) 
	    		$violations[]="$storage: only 1 'electrical_storage' class device is allowed";
	    	else {
	    		$storage++;
	    		if ($i["cline"]=="protected" or $i["gline"]=="unprotected")
	    			$violations[]=" $d 'electrical_storage' device must be connected with cline=>unprotected  gline=>protected";
	    	}
	    if ($i["class"]=="auxiliary_generator")
	    	if ($auxgen) 
	    		$violations[]="$auxgen: only 1 'auxiliary_generator' class device is allowed";
	    	else {
	    		$auxgen++;
	    		$sim_type="off-grid";
	    		if ($i["gline"]=="protected")
	    			$violations[]="$d 'auxiliary_generator' device must be connected to the unprotected line";
	    	}
	    if ($i["cline"]=="protected") $protected++; else if ($i["cline"]=="unprotected" and $i["class"]!="electrical_storage") $unprotectedloads++;
	    if ($i["gline"]=="protected") $protected++;
	}
	// implement other rules
	if ($meteo==0) $violations[]="no 'meteo_station' class devices, one is necessary";
	if ($grid==0 and $storage==0) $violations[]="no 'grid' or 'electrical_storage' class devices, one is necessary";
	if ($grid!=0 and $auxgen!=0) $violations[]="both 'grid' and 'auxiliary_generator' class devices, only one can be selected";
	if ($auxgen!=0 and $storage==0) $violations[]="'auxiliary_generator' class devices requires one 'storage' class device";
	if ($protected>0 and $storage==0) $violations[]="$protected device(s) connected to the 'protected' line without one 'electrical_storage' device";
	if ($unprotectedloads>0 and $grid==0) $violations[]="$unprotected load type device(s) connected to the 'unprotected' line without one 'grid' device";
	if (!array_key_exists("selected",$devicesinfo[$eis_dev_conf["ID"]]) or !$devicesinfo[$eis_dev_conf["ID"]]["selected"])
		$violations[]="this master device (".$eis_dev_conf["ID"].") must be enabled";
	if (sizeof($violations)) return $violations; else return false;
}

?>
