<?php

// **** EIS ****
// eis device realtime interface
// upf, May-Jun2013

// required includes
require_once("/etc/eis_conf.php");
include($eis_conf["path"]."/system/eis_interface_lib.php");

// initialization
$thispage="http://".$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"];
$thisdevice=$eis_dev_conf["ID"]."@".$_SERVER["SERVER_NAME"];

// process callbacks from page
if (eis_callback("phase")) {
    eis_dev_call($thisdevice,"exec","connect",array("phase"=>$_REQUEST["phase"]),$outputpar);
    die();
}

// create a realtime interface handler
// if custom widgets are defined, requires the Javascript function "eis_updatepage(status)" be defined into the page
eis_realtime_handler();

// use widgets
eis_realtime_widgets();


//////////// page creation ////////////

// output page
print eis_page_header($eis_dev_conf["ID"],"");
print "<h3>".eis_widget_timestamp("Simulation time: ")."</h3>";
print eis_widget_enable().eis_spaces(6).eis_widget_poweron();

// full/half power buttons
print eis_spaces(6).eis_widget_led("fullpower","orange");
print eis_widget_signal("full power","fullpower").eis_widget_signal("half power","halfpower");

// cline status
print eis_spaces(6).eis_widget_blackout();

// power gauges
print "<br><br>".eis_widget_cpower(""); 

// print gauges and labels table for cline (only for loads) and select radio buttons
print "<table style='width:100%'><tr>\n";
for($i=1;$i<4;$i++)
    print "<td style='text-align:center'><input type='radio' id='phase$i' onclick=\"eis_callback('phase','$i');\"> connect to phase $i</td>\n";
print "</tr></table><br>\n";


//////////// page initialization ////////////

// set phase connection
print "<script>\n"; 
print "document.getElementById('phase".$eis_dev_status["connected"]."').checked=true;\n";
print "</script>\n";

?> 


<script>
//////////// page realtime update ////////////

    // update page using new device status values
    // required by eis_realtime_handler();
    function eis_updatepage(status) {
        var i,s,c;
        var e=0.0;
        for (i in status)
            switch(i) {
                case "connected":
                    for(p=1;p<4;p++)
                        if(p==status[i])
                            document.getElementById('phase'+p).checked=true;
                        else
                            document.getElementById('phase'+p).checked=false;
                    break;
           }
     }

</script>


</body>
</html>
