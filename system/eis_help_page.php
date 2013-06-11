<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
 <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>help</title>
 </head>
    <body>
<?php

// **** EIS ****
// eis standard device help page implementation
// upf, Jun2013

// print headers
print "<h1>".$eis_dev_conf["ID"]."</h1>\n";
print "<i><b>".$eis_dev_conf["description"].":</b>&nbsp&nbsp version: ".$eis_dev_conf["version"]."&nbsp&nbsp date: ".$eis_dev_conf["date"].
        "&nbsp&nbsp author: ".$eis_dev_conf["author"]."&nbsp&nbsp class: ".$eis_dev_conf["class"]."&nbsp&nbsp type: ".$eis_dev_conf["type"]."\n";


// check if an help file exists
if (!file_exists($eis_dev_conf["path"]."/private/help.txt"))
    die("<br><br><b>no help available</b>");

// print help in standard format
$help=file($eis_dev_conf["path"]."/private/help.txt");
foreach ($help as $line) {
    $line=trim($line);
    // skip comments and blank lines
    if ($line[0]=="#" or $line=="") continue;
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


?>

<br>
</body>
</html>
