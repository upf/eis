<?php

// **** EIS ****
// eis master console page
// upf, Jun2013

// required includes
require_once("/etc/eis_conf.php");
include($eis_conf["path"]."/system/eis_interface_lib.php");
include("private/master_lib.php");

// current page url
$page=eis_page_url();

// load device status
if (!eis_load_status()) die ($eis_error." --> ".$eis_errmsg);

// if requested perform a new scan and save results
if (isset($_REQUEST["network"])) $network=$_REQUEST["network"]; else $network=false;
if (isset($_REQUEST["scan"])) 
    $eis_dev_status["devicescan"]=eis_master_getdevices($network);

// if requested change cline or gline value of a specified device@host
if (isset($_REQUEST["device"])) $device=$_REQUEST["device"]; else $device=false;
if (isset($_REQUEST["host"])) $host=$_REQUEST["host"]; else $host=false;
if (isset($_REQUEST["line"])) $line=$_REQUEST["line"]; else $line=false;
if ($device and $host and $line and isset($_REQUEST["value"])) {
    if ($_REQUEST["value"]=="protected") {
        if (eis_dev_call($device."@".$host,"exec","setstatus",array($line=>"protected"),$outputpar))
            $eis_dev_status["devicescan"][$device][$line]="protected"; 
    }
    else
        if (eis_dev_call($device."@".$host,"exec","setstatus",array($line=>"unprotected"),$outputpar))
            $eis_dev_status["devicescan"][$device][$line]="unprotected";
} 

// if requested change configuration of a specified device@host
if (isset($_REQUEST["device"])) $device=$_REQUEST["device"]; else $device=false;
if (isset($_REQUEST["host"])) $host=$_REQUEST["host"]; else $host=false;
if (isset($_REQUEST["configID"])) $configID=$_REQUEST["configID"]; else $configID=false;
if ($device and $host and $configID) {
    if (eis_dev_call($device."@".$host,"exec","setstatus",array("configID"=>$configID),$outputpar))
        $eis_dev_status["devicescan"][$device]["configID"]=$configID; 

}

// add a new device to simulation
if (isset($_REQUEST["insert"])) 
    $eis_dev_status["devicescan"][$_REQUEST["insert"]]["selected"]=true;

// remove a device from simulation
if (isset($_REQUEST["remove"])) 
    $eis_dev_status["devicescan"][$_REQUEST["remove"]]["selected"]=false;

// save change(s) in status
if (!eis_save_status()) die ($eis_error." --> ".$eis_errmsg);

// get information on the scanned devices
$info=$eis_dev_status["devicescan"];

// print page headers
print eis_page_header("eis master create","");
print "<h3>create a new simulation</h3>\n";

// proceed and start a new simulation (if requested)
if (isset($_REQUEST["action"]))
    switch ($_REQUEST["action"]) {
        // proceed with setup
        case "OK proceed":
            // print a table of selected devices
            $headers=array("device","host","class","type","c_line","g_line","configuration");
            $rows=array();
            reset($info);
            foreach ($info as $d=>$i) 
                if ($i["selected"]) {
                    if ($i["class"]=="grid") $sim_type="grid-connected";
                    if ($i["class"]=="auxiliary_generator") $sim_type="off-grid";
                    if ($i["class"]=="meteo_station") $meteo=$d."@".$i["host"];
                    $rows[]=array($d,$i["host"],$i["class"],$i["type"],$i["cline"],$i["gline"],$i["configID"]);
                }
            eis_print_datatable("Selected devices:",$headers,$rows,null);
            // select simulation parameters
            print "<form action='$page'>\n";
            // meteo data
            eis_dev_call($meteo,"exec","getdatainfo",array(),$outputpar);
            if (isset($outputpar["datainfo"])) $meteodata=$outputpar["datainfo"]; else die("<br><b>cannot get meteo data</b>");
            $headers=array("meteoID","start date","duration (days)","location","description");
            $rows=array();
            $n=0;
            foreach ($meteodata as $id => $data) {
                if (!$n) $c="checked"; else $c="";
                $rows[]=array("<input type='radio' name='sim_meteo' value='$id' $c> $id",date("Y-m-d",$data["start"]),$data["duration"],
                                $data["location"],$data["description"]);
                $n++;
            }
            eis_print_datatable("Select meteo data:",$headers,$rows,null);
           print "<p><b>start hour (0-24):</b> <input type='text' name='sim_hour' size=4 value='0'><br>\n";
            print "<p><b>time step (min)&nbsp&nbsp:</b> <select name='sim_step'>";
            $tstep=array(5,10,15,20,30);
            for ($s=0; $s<sizeof($tstep); $s++) {
                print "<option value='".$tstep[$s]."' ";
                print ">".$tstep[$s]."</option>";
            }
            print "</select><br>\n";
            print "<p><b>simulation name:</b> <input type='text' name='sim_name' size=128 value='".date("Y-m-d")." new simulation'><br>\n";
            // ready for starting a new simulation
            print "<br><b>simulation type&nbsp&nbsp: &nbsp<i> $sim_type system</i></b><br>\n";
            print "<input type='hidden' name='sim_type' value='$sim_type'>\n";
            print "<input type='hidden' name='action' value='start'>\n";
            print "<br><input type='submit' value='--> start simulation'>\n";
            print "</form>\n";
            print "<form action='$page'><input type='submit' value='<-- go back'></form>\n";
            die();
        case "start":        
            // *** start simulation
            if (isset($_REQUEST["sim_meteo"])) $sim_meteo=$_REQUEST["sim_meteo"]; else $sim_meteo="random";
             if (isset($_REQUEST["sim_hour"])) $sim_hour=$_REQUEST["sim_hour"]; else $sim_hour=0;
            if (isset($_REQUEST["sim_step"])) $sim_step=$_REQUEST["sim_step"]; else $sim_step=5;
            if (isset($_REQUEST["sim_name"])) $sim_name=$_REQUEST["sim_name"]; else $sim_name=date("Y-m-d")." new simulation";
            if (isset($_REQUEST["sim_type"])) $sim_type=$_REQUEST["sim_type"]; else $sim_type="off-grid";
            // generate simulation ID
            $sim_id=uniqid("");
            // generate device array
            $devices=array();
            reset($info);
            foreach ($info as $d=>$i)
                if ($i["selected"]) {
                    $devices[$d]=array("host"=>$i["host"],"class"=>$i["class"],"type"=>$i["type"],
                    "cline"=>$i["cline"],"gline"=>$i["gline"],"configID"=>$i["configID"]);
                    if ($i["class"]=="meteo_station") $meteo=$d."@".$i["host"];
                }
            // compute start and end timestamps
            if (!eis_dev_call($meteo,"exec","getdatainfo",array(),$outputpar)) die("<br><b>cannot get meteo data</b>");
            $startime=$outputpar["datainfo"][$sim_meteo]["start"] + $sim_hour*3600;
            $endtime=$outputpar["datainfo"][$sim_meteo]["start"] + $outputpar["datainfo"][$sim_meteo]["duration"]*24*3600;
            // save simulation data into db
            $sim=array("id"=>$sim_id,"type"=>$sim_type,"hour"=>$sim_hour,"step"=>$sim_step,"meteo"=>$sim_meteo,
                "name"=>$sim_name,"devices"=>$devices,"startime"=>$startime,"endtime"=>$endtime);
            if (!eis_master_savesim($sim)) die("cannot insert into database: $eis_error -- $eis_errmsg");
            // redirect to the run simulation page
            die('<script type="text/javascript">window.location.href="run.php?sim_id='.$sim_id.'&init";</script>');
    }


// create a new simulation page 

// print a table of found devices
$headers=array("device","select","host","class","type","c_line","g_line","configuration","description");
$rows=array();
$meteo="";
$n=0;
reset($info);
foreach ($info as $d=>$i) {
    if (!array_key_exists("selected",$i)) $info[$d]["selected"]=false;
    extract($i,EXTR_OVERWRITE);
    if ($host=="localhost") $url=eis_dev_geturl($d,""); else $url=eis_dev_geturl($d,$host);
    $configs="";
    foreach($configurations as $k=>$v) 
        if (sizeof($configurations)==1) 
        $configs=$configs."<br>".eis_spaces(1).$k."\n";
        else {
            if ($k==$configID) $c="checked"; else $c="";
            $configs=$configs."<br>".eis_spaces(1)."<input type='radio' name='".$d."_configID' title='".$v["config"]."' onclick=\"selectconf('$d','$host','$k');\" $c> $k\n";
        }
    switch($cline) {
        case "protected": { $cline="<a href='$page?device=$d&host=$host&line=cline&value=unprotected'>protected</a>"; break;}
        case "unprotected":  
            if ($class=="grid" or $class=="electrical_storage") $cline="unprotected";
                else $cline="<a href='$page?device=$d&host=$host&line=cline&value=protected'>unprotected</a>"; 
            break;
        case "error": { $cline="<font color=red>$cline</font>"; break;}
        default: $cline="-----";
    }
    switch($gline) {
        case "protected":
            if ($class=="electrical_storage") $gline="protected";
                else $gline="<a href='$page?device=$d&host=$host&line=gline&value=unprotected'>protected</a>";
            break;
        case "unprotected": 
            if ($class=="grid" or $class=="auxiliary_generator") $gline="unprotected";
                else $gline="<a href='$page?device=$d&host=$host&line=gline&value=protected'>unprotected</a>";
            break;
        case "error": { $gline="<font color=red>$gline</font>"; break;}
        default: $gline="-----";
    }   
    if ($info[$d]["selected"]) $chk="checked"; else $chk="";
    $select="<input type='checkbox' id='ckb$n' onclick=\"selectdev('$d','ckb$n');\" $chk>";
    $rows[]=array("<a href='$url' target='_blank'>$d</a>",$select,$host,$class,$type,$cline,$gline,$configs,$description);
    $n++;
}
print "<form action='$page'>\n";
eis_print_datatable("Select devices:",$headers,$rows,array("configuration"=>"left"));
// mark selected devices
$n=0;
reset($info);
foreach($info as $d=>$i) {
    if (array_key_exists("selected",$i) and $i["selected"])
        print "\n<script>document.getElementById('$n').style.backgroundColor='#888888';</script>\n";
    $n++;
}
// create rescan button
print "<input type='submit' name='scan' value='rescan' /> &nbsp &nbsp <input type='checkbox' name='network' value='1'";
if ($network) print " checked";
print ">scan also LAN network<br>\n";

// check simulation rules
$violations=eis_master_ruleviolations($sim_type);
if ($violations) {
    print "<br><b>$sim_type simulation rule violation(s):</b><br><ul>\n";
    foreach ($violations as $v) 
        print "<li><font color=red><i>$v</i></font></li>\n";
}
else
    print "<br><br><input type='submit' name='action' value='OK proceed' />\n";
print "</form>\n";


?>
<script>
    // useful vars
    var page="<?php print $page; ?>";

    // add or remove device when checked/unchecked
    function selectdev(device,checkbox) {
        if (document.getElementById(checkbox).checked) 
            window.location.href=page+"?insert="+device;
        else 
            window.location.href=page+"?remove="+device;
    }

    // change device configuration
    function selectconf(device,host,configID) {
        window.location.href=page+"?device="+device+"&host="+host+"&configID="+configID;
    }
 
</script>

<br>
</body>
</html>
