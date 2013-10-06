<?php

// **** EIS ****
// eis standard device help page implementation
// upf, Jun2013


// print page headers
print eis_page_header("help ".$eis_dev_conf["ID"],"");
print "<b>help for <i>\"".$eis_dev_conf["description"]."\":</b> &nbsp&nbsp version: ".$eis_dev_conf["version"]."&nbsp&nbsp date: ".$eis_dev_conf["date"].
        "&nbsp&nbsp author: ".$eis_dev_conf["author"]."&nbsp&nbsp class: ".$eis_dev_conf["class"]."&nbsp&nbsp type: ".$eis_dev_conf["type"]."\n";


// check if an help file exists
if (!file_exists($eis_dev_conf["path"]."/private/help.txt"))
    die("<br><br><b>no help available</b>");

// print help in standard format
$help=file($eis_dev_conf["path"]."/private/help.txt");
foreach ($help as $line) {
    $line=trim($line);
    // skip comments and blank lines
    if ($line=="" or $line[0]=="#") continue;
    // process sections
    if (strpos($line,"{**")!==false) {
        $line=str_replace("{**", "<br><h2>", $line);
        $line=str_replace("**}", "</h2>", $line);
        print $line."\n";
        continue;
    }
    // process commands
    if (strpos($line,"[**")!==false) {
        $line=str_replace("[**", "<h3>[", $line);
        $line=str_replace("**]", "]</h3>", $line);
        print $line."\n";
        continue;
    }
    // process signals
    if (strpos($line,"(**")!==false) {
        $line=str_replace("(**", "<h3>(", $line);
        $line=str_replace("**)", ")</h3>", $line);
        print $line."\n";
        continue;
    }
    print "<i>".$line."</i><br>\n";
}

// close page
print "</body></html>\n";

?>
