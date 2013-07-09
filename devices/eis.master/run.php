<?php
// **** EIS ****
// eis master simulation page
// upf, Jun2013

// required includes
require_once("/etc/eis_conf.php");
include($eis_conf["path"]."/system/eis_interface_lib.php");
include("private/master_lib.php");

// current page url
$page="http://".$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"];

// load simulation with the given simulation id (sim_id)
if (!isset($_REQUEST["sim_id"])) die("no simulation ID, cannot proceed");
$sim_id=$_REQUEST["sim_id"];
if (!eis_master_loadsim($sim_id)) die("cannot load simulation $sim_id: $eis_error -- $eis_errmsg");

// load device info for this simulation
$devinfo=$eis_dev_status["sim_devices"];

// scan device for classes and lines
$protected=array();
$unprotected=array();
foreach ($devinfo as $d=>$i) {
	if ($i["class"]=="meteo_station") $meteo=$d;
	if ($i["class"]=="grid") $grid=$d;
	if ($i["class"]=="auxiliary_generator") $auxgen=$d;
	if ($i["class"]=="electrical_storage") $storage=$d;
	if ($i["cline"]=="protected" or $i["gline"]=="protected") $protected[]=$d;
	if ($i["cline"]=="unprotected" or $i["gline"]=="unprotected") $unprotected[]=$d;
}

// initialize simulation
if (isset($_REQUEST["init"])) {
	$initerr=array();
	$inputpar=array("timestamp"=>$eis_dev_status["sim_startime"],"sim_meteo"=>$eis_dev_status["sim_meteo"],
		"sim_price"=>$eis_dev_status["sim_price"],"sim_id"=>$eis_dev_status["sim_id"],"sim_type"=>$eis_dev_status["sim_type"],
		"sim_step"=>$eis_dev_status["sim_step"]);
	reset($devinfo);
	foreach ($devinfo as $d=>$i) {
		$inputpar["cline"]=$i["cline"];
		$inputpar["gline"]=$i["gline"];
		if (!eis_dev_call($d."@".$devinfo[$d]["host"],"exec","init",$inputpar,$outpar)) $initerr[$d]="$eis_error -- $eis_errmsg";
	}
	if (sizeof($initerr)) {
		foreach ($initerr as $d=>$err) print "<b>error initializing $d: $err</b><br>\n";
		die();
	}
}

// do one step of simulation
if (isset($_REQUEST["simulate"])) {
	// initialize vars
	$devstatus=array();
	$pcpower=array(1=>0,2=>0,3=>0);	// protected line load powers
	$pgpower=array(1=>0,2=>0,3=>0);	// protected line gen powers
	$ucpower=array(1=>0,2=>0,3=>0);	// unprotected line load powers
	$ugpower=array(1=>0,2=>0,3=>0);	// unprotected line gen powers
	$timestamp=$_REQUEST["simulate"];
	// call meteo device
   	$fields=array("temperature","humidity","windspeed","winddir","pressure","radiation");
   	$i=$devinfo[$meteo];
	if (eis_dev_call($meteo."@".$i["host"],"exec","simulate",array("timestamp"=>$timestamp),$outpar)) {
		foreach ($fields as $f) $devstatus["meteo"][$f]=$outpar[$f];
		$devstatus["meteo"]["windspeed"]=$devstatus["meteo"]["windspeed"]*3.6; // Km/h from m/s
		$dg=$devstatus["meteo"]["winddir"];
		if ($dg>338 or $dg<=23) $dir='N';
		if ($dg>23 and $dg<=68) $dir='NE';
		if ($dg>68 and $dg<=103) $dir='E';
		if ($dg>103 and $dg<=158) $dir='SE';
		if ($dg>158 and $dg<=203) $dir='S';
		if ($dg>203 and $dg<=248) $dir='SW';
		if ($dg>248 and $dg<=293) $dir='W';
		if ($dg>293 and $dg<=338) $dir='NW';
		$devstatus["meteo"]["winddir"]=$dir." (".$devstatus["meteo"]["winddir"].")";
	}
   	if ($eis_error) $devstatus["meteo"]["error"]="<font color='red'>".$eis_error."</font>"; else $devstatus["meteo"]["error"]="-----";
	if ($eis_error) { print json_encode($devstatus); die(); } // if meteo fails, skip simulation step
	// call all load devices on protected line
    foreach ($protected as $d) {
 	   	$i=$devinfo[$d];
	   	if ($i["type"]=="generator" or $i["class"]=="storage" or $i["type"]=="virtual") continue;
		if (eis_dev_call($d."@".$i["host"],"exec","simulate",
					array("timestamp"=>$timestamp,"meteo"=>$devstatus["meteo"],"blackout"=>$eis_dev_status["pl_blackout"]),$r)) {
			$devstatus[$d]["cpower"]=$r["cpower1"].",".$r["cpower2"].",".$r["cpower3"];
			$devstatus[$d]["cenergy"]=number_format($r["cenergy1"],4).",".number_format($r["cenergy2"],4).",".number_format($r["cenergy3"],4);
			for ($p=1; $p<4; $p++) if (array_key_exists("cpower".$p,$r)) $pcpower[$p]+=$r["cpower".$p];
		}
   		if ($eis_error) $devstatus[$d]["error"]="<font color='red'>".$eis_error."</font>"; else $devstatus[$d]["error"]="-----";
	}
	// call all generator devices on protected line
    reset($protected);
    foreach ($protected as $d) {
	   	$i=$devinfo[$d];
	   	if ($i["type"]=="load" or $i["class"]=="storage" or $i["type"]=="virtual") continue;
		if (eis_dev_call($d."@".$i["host"],"exec","simulate",
				array("timestamp"=>$timestamp,"meteo"=>$devstatus["meteo"],"cpower"=>$pcpower,"blackout"=>$eis_dev_status["pl_blackout"]),$r)) {
			$devstatus[$d]["gpower"]=$r["gpower1"].",".$r["gpower2"].",".$r["gpower3"];
			$devstatus[$d]["genergy"]=number_format($r["genergy1"],4).",".number_format($r["genergy2"],4).",".number_format($r["genergy3"],4);
			for ($p=1; $p<4; $p++) if (array_key_exists("gpower".$p,$r)) $pgpower[$p]+=$r["gpower".$p];
		}
   		if ($eis_error) $devstatus[$d]["error"]="<font color='red'>".$eis_error."</font>"; else $devstatus[$d]["error"]="-----";
    }
	// call storage device if exists
	if (isset($storage)) {
	   	$i=$devinfo[$storage];
		if (eis_dev_call($storage."@".$i["host"],"exec","simulate",
				array("timestamp"=>$timestamp,"meteo"=>$devstatus["meteo"],"cpower"=>$pcpower,"gpower"=>$pgpower),$r)) {
			$devstatus["storage"]=$r;
			for ($p=1; $p<4; $p++) if (array_key_exists("cpower".$p,$devstatus[$d])) $ucpower[$p]+=$devstatus[$d]["cpower".$p];
		}
   		if ($eis_error) $devstatus["storage"]["error"]="<font color='red'>".$eis_error."</font>"; else $devstatus["storage"]["error"]="-----";
    }
	// call all load devices on unprotected line
    foreach ($unprotected as $d) {
 	   	$i=$devinfo[$d];
	   	if ($i["type"]=="generator" or in_array($i["class"],array("electrical_storage","grid","auxiliary_generator")) or $i["type"]=="virtual") continue;
		if (eis_dev_call($d."@".$i["host"],"exec","simulate",
					array("timestamp"=>$timestamp,"meteo"=>$devstatus["meteo"],"blackout"=>$eis_dev_status["ul_blackout"]),$r)) {
			$devstatus[$d]["cpower"]=$r["cpower1"].",".$r["cpower2"].",".$r["cpower3"];
			$devstatus[$d]["cenergy"]=number_format($r["cenergy1"],4).",".number_format($r["cenergy2"],4).",".number_format($r["cenergy3"],4);
			for ($p=1; $p<4; $p++) if (array_key_exists("cpower".$p,$r)) $ucpower[$p]+=$r["cpower".$p];
		}
   		if ($eis_error) $devstatus[$d]["error"]="<font color='red'>".$eis_error."</font>"; else $devstatus[$d]["error"]="-----";
    }
	// call all generator devices on unprotected line
    reset($unprotected);
    foreach ($unprotected as $d) {
 	   	$i=$devinfo[$d];
	   	if ($i["type"]=="load" or in_array($i["class"],array("electrical_storage","grid","auxiliary_generator")) or $i["type"]=="virtual") continue;
		if (eis_dev_call($d."@".$i["host"],"exec","simulate",
				array("timestamp"=>$timestamp,"meteo"=>$devstatus["meteo"],"cpower"=>$ucpower,"blackout"=>$eis_dev_status["ul_blackout"]),$r)) {
			$devstatus[$d]["gpower"]=$r["gpower1"].",".$r["gpower2"].",".$r["gpower3"];
			$devstatus[$d]["genergy"]=number_format($r["genergy1"],4).",".number_format($r["genergy2"],4).",".number_format($r["genergy3"],4);
			for ($p=1; $p<4; $p++) if (array_key_exists("gpower".$p,$r)) $ugpower[$p]+=$r["gpower".$p];
		}
   		if ($eis_error) $devstatus[$d]["error"]="<font color='red'>".$eis_error."</font>"; else $devstatus[$d]["error"]="-----";
    }
	// call grid device if exists
	if (isset($grid)) {
	   	$i=$devinfo[$grid];
		if (eis_dev_call($grid."@".$i["host"],"exec","simulate",
				array("timestamp"=>$timestamp,"meteo"=>$devstatus["meteo"],"cpower"=>$ucpower,"gpower"=>$ugpower),$r)) {
			$devstatus["grid"]["gpower"]=$r["gpower1"].",".$r["gpower2"].",".$r["gpower3"];
			$devstatus["grid"]["genergy"]=number_format($r["genergy1"],4).",".number_format($r["genergy2"],4).",".number_format($r["genergy3"],4);
	 		$devstatus["grid"]["cpower"]=$r["cpower1"].",".$r["cpower2"].",".$r["cpower3"];
			$devstatus["grid"]["cenergy"]=number_format($r["cenergy1"],4).",".number_format($r["cenergy2"],4).",".number_format($r["cenergy3"],4);
			$devstatus["grid"]["price_sell"]=$r["price_sell"];
			$devstatus["grid"]["price_buy"]=$r["price_buy"];
			$devstatus["grid"]["tpower"]=$r["gpower1"]+$r["gpower2"]+$r["gpower3"]-$r["cpower1"]-$r["cpower2"]-$r["cpower3"];
			$devstatus["grid"]["tenergy"]=number_format($r["genergy1"]+$r["genergy2"]+$r["genergy3"]-$r["cenergy1"]-$r["cenergy2"]-$r["cenergy3"],4);
			$devstatus["grid"]["total_sell"]=number_format($r["total_sell"],4);
			$devstatus["grid"]["total_buy"]=number_format($r["total_buy"],4);
			$devstatus["grid"]["total_money"]=number_format($r["total_buy"]-$r["total_sell"],4);
			if ($r["gridstatus"]=="ok") $c="green"; else $c="red";
			$devstatus["grid"]["gridstatus"]="<font color='$c'>".$r["gridstatus"]."</font>";
			if ($r["gridstatus"]=="ok") $eis_dev_status["ul_blackout"]=false; else $eis_dev_status["ul_blackout"]=true;
  		}
		if ($eis_error) $devstatus["grid"]["error"]="<font color='red'>".$eis_error."</font>"; else $devstatus["grid"]["error"]="-----";
    }
	// call aux generator device if exists
	if (isset($auxdev)) {
 	   	$i=$devinfo[$auxdev];
		if (eis_dev_call($auxdev."@".$i["host"],"exec","simulate",
				array("timestamp"=>$timestamp,"meteo"=>$devstatus["meteo"],"cpower"=>$ucpower,"gpower"=>$ugpower),$r)) {
			$devstatus["auxdev"]=$r;
			if ($r["auxgenstatus"]!="ok") $eis_dev_status["ul_blackout"]=true; else $eis_dev_status["ul_blackout"]=false;
		}
   		if ($eis_error) $devstatus["auxgen"]["error"]="<font color='red'>".$eis_error."</font>"; else $devstatus["auxgen"]["error"]="-----";
    }
	// call energy manager device if exists
	// *********** to be implemented **********

    // return update status
	print json_encode($devstatus);
	// save status
	if (!eis_save_status()) die ($eis_error." --> ".$eis_errmsg);
	die();
}

//////////// main page output

// save change(s) in status
if (!eis_save_status()) die ($eis_error." --> ".$eis_errmsg);

// print page headers
print eis_page_header("eis master run","",null);
print "<b>run simulation:</b> <i>".$eis_dev_status["sim_name"]." (id=$sim_id, type=".$eis_dev_status["sim_type"].")</i><br>";

// control panel
print "<br><table><tr>
	<td><div id='led'><img width='80' height='30' src='../lib/off.png'></div></td>
	<td> <button onClick='start()'>start</button> </td>
	<td> <button onClick='step()'>step</button> </td>
	<td> <button id='stop' onClick='stop()'>stop</button> </td>
	<td> <div id='timestamp'></div> </td>
	</tr></table>\n";

// meteo device data table
$headers=array("","Temperature (C')","Humidity (%)","Wind speed (Km/h)","Wind dir (deg)","Pressure (mBar)","Radiation (W/m2)","Error");
$rows=array(array("@Current values","meteo_temperature","meteo_humidity","meteo_windspeed","meteo_winddir","meteo_pressure","meteo_radiation","meteo_error"));
$link="<a href='".eis_dev_geturl($meteo,$devinfo[$meteo]["host"])."' target='_blank'>$meteo</a>";
print_datatable("Meteo data <i>($link using ".$eis_dev_status["sim_meteo"]." data)</i>",$headers,$rows);

// grid device data table (if exists)
if (isset($grid)) {
	$headers=array("BuyPrice (EU)","Buy (W)","Buy (KWh)","Cost (EU)","SellPrice (EU)","Sell (W)",
					"Sell (KWh)","Revenue (EU)","Connection","Error");
	$rows=array(array("grid_price_buy","grid_gpower","grid_genergy","grid_total_buy",
			    		"grid_price_sell","grid_cpower","grid_cenergy","grid_total_sell","grid_gridstatus","grid_error"));
	$link="<a href='".eis_dev_geturl($grid,$devinfo[$grid]["host"])."' target='_blank'>$grid</a>";
	print_datatable("Grid data <i>($link using ".$eis_dev_status["sim_price"]." prices)</i>",$headers,$rows);
	$headers=array("Total grid power (W)","Total grid energy (kWh)","Total cost");
	$rows=array(array("grid_tpower","grid_tenergy","grid_total_money"));
	print_datatable("",$headers,$rows);	
}

// aux generator device data table (if exists)
if (isset($auxgen)) {
	$headers=array("","Price (EU)","Power (W)","Energy (KWh)","Cost (EU)");
	$rows=array(
		array("@Buy","auxgen_bprice","auxgen_gpower","auxgen_genergy","auxgen_bcost"),
		array("@Total","@ ","auxgen_tpower","auxgen_tenergy","grid_tcost")
	);
	$link="<a href='".eis_dev_geturl($auxgen,$devinfo[$auxgen]["host"])."' target='_blank'>$auxgen</a>";
	print_datatable("Auxiliary generator data <i>($link)</i>",$headers,$rows);
}

// unprotected line device data table (if exists)
if (sizeof($unprotected)) {
	$headers=array("Device","Host","Class","Type","cPower (W)","cEnergy (KWh)","gPower (W)","gEnergy (KWh)","Error");
	$rows=array();
	foreach ($unprotected as $d)
		if (!in_array($devinfo[$d]["class"],array("storage","grid","auxiliary_generator","master","meteo_station")))
			$rows[]=array("@<a href='".eis_dev_geturl($d,$devinfo[$d]["host"])."' target='_blank'>$d</a>","@".$devinfo[$d]["host"],
				"@".$devinfo[$d]["class"],"@".$devinfo[$d]["type"],$d."_cpower",$d."_cenergy",$d."_gpower",$d."_genergy",$d."_error");
	print_datatable("Unprotected Line data",$headers,$rows);
}

// storage device data table (if exists)
if (isset($storage)) {
	$headers=array("","Assorbed power (W)","Assorbed Energy (KWh)","Generated power (W)","Generated Energy (KWh)","Bypass (%)","Charge (%)");
	$rows=array(
		array("@Protected line","@ ","@ ","storage_cpower","storage_cenergy","@ ","@ "),
		array("@Unprotected line","storage_cpower","storage_cenergy","@ ","@ ","storage_bupass","@ "),
		array("@Battery","storage_bcpower","storage_bcenergy","storage_bgpower","storage_gcenergy","storage_bbpass","storage_bcharge")
	);
	$link="<a href='".eis_dev_geturl($storage,$devinfo[$storage]["host"])."' target='_blank'>$storage</a>";
	print_datatable("Storage data <i>($link)</i>",$headers,$rows);
}

// protected line device data table (if exists)
if (sizeof($protected)) {
	$headers=array("Device","Host","Class","Type","cPower (W)","cEnergy (KWh)","gPower (W)","gEnergy (KWh)","Error");
	$rows=array();
	foreach ($protected as $d)
		if (!in_array($devinfo[$d]["class"],array("storage","grid","auxiliary_generator","master","meteo_station")))
			$rows[]=array("@<a href='".eis_dev_geturl($d,$devinfo[$d]["host"])."' target='_blank'>$d</a>","@".$devinfo[$d]["host"],
				"@".$devinfo[$d]["class"],"@".$devinfo[$d]["type"],$d."_cpower",$d."_cenergy",$d."_gpower",$d."_genergy",$d."_error");
	print_datatable("Protected Line data",$headers,$rows);
}



// utility function for printing a table with its title, headers and fields
// if a field starts with @ it is treated as data, else it is treated as an ID of an empty div 
function print_datatable($title,$headers,$rows) {
	print "<br><b>$title</b><br>\n";
	print "	<table border=1><tr>";
	foreach ($headers as $f) print "<th>&nbsp $f &nbsp</th>";
	print "</tr>\n";
	foreach ($rows as $row) {
		print "<tr>";
		foreach ($row as $j=>$f) {
			if ($j==0) $a="left"; else $a="center";
			if ($f[0]=="@") $c=substr($f,1); else $c="<div id='$f'></div>";
			print "<td style='text-align:$a'>&nbsp $c &nbsp</td>";
		}
		print "</tr>";
	}
	print "</table>\n";
}


?>

<script>
	var state="stopped";
	var onestep=false;
	var timeinit=<?php print $eis_dev_status["sim_startime"];?>;
	var timestamp=timeinit;
	var timestep=<?php print $eis_dev_status["sim_step"]*60;?>;
	var timend=<?php print $eis_dev_status["sim_endtime"];?>;
	var httpchan;
	var page="<?php print $page;?>";

	// create an http channel for page calling
	if (window.XMLHttpRequest)
		httpchan=new XMLHttpRequest();  // for IE7+, Firefox, Chrome, Opera, Safari
    else 
        httpchan=new ActiveXObject("Microsoft.XMLHTTP");  // for IE6, IE5
    // register callback function
    httpchan.onreadystatechange=updatepage;

    // set main execution cycle
	window.setInterval('dostep()', 1000);

	// do one step of simulation
	function dostep() {
		var strtime,perc;
		if (state=="ready") {
			state="running";
			document.getElementById('led').innerHTML = '<img width="80" height="30" src="../lib/on.png">';
			timestamp+=timestep;
			if (timestamp>=timend) {
				state="end";
				strtime="<font color='red'>end of simulation</font>";
			}
			else {
				perc=Math.round((timestamp-timeinit)*100/(timend-timeinit));
				strtime="("+perc+"%)";
			}
			document.getElementById('timestamp').innerHTML = "&nbsp&nbsp"+getfdate(timestamp)+" &nbsp&nbsp"+strtime;
		    // call back php page through the http channel
		    httpchan.open("GET",page+"?sim_id=<?php print $sim_id;?>&simulate="+timestamp,true);
		    httpchan.send();
		}
		else
			document.getElementById('led').innerHTML = '<img width="80" height="30" src="../lib/off.png">';
	}

	//manage stop button
	function stop() {
		if (state=="end") return;
		state="stopped";
	}
	// manage start button
	function start() {
		if (state=="end") return;
		if (state!="running") state="ready";
	}
	// manage one step button
	function step() {
		if (state=="end") return;
		if (state!="running") {
			state="ready";
			onestep=true;
		}
	}

	// update page
	function updatepage() {
        var i,d,status,dstatus,div;
        if (httpchan.readyState==4 && httpchan.status==200) {
       		dstatus = eval( "("+httpchan.responseText+")" );
      		for (d in dstatus) {
       			status=dstatus[d];
       			for (i in status) {
       				div=document.getElementById(d+'_'+i);
       				if (div) div.innerHTML = "&nbsp "+status[i]+" &nbsp";
       			}
      		}
  			if (!onestep) {
				if (state!="end") state="ready";
			}
			else {
				state="stopped";
				onestep=false;
			}
		}
	}

    // return a formatted data string froma UNIX timestamp
    function getfdate(timestamp) {
        var d = new Date(timestamp*1000);
        var m=new Array("Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec");
        return d.getDate()+"-"+m[d.getMonth()]+"-"+d.getFullYear()+" &nbsp "+d.getHours()+":"+d.getMinutes()+":"+d.getSeconds();
    }


</script>

  </body>
</html>

