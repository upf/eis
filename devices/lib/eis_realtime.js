/*
eis real time interface Javascript:
1) allows to call back an HTTP page with parameters
2) create a framework to update a page in real time using a BOSH like log poll approach
    using a JSON associative array as input (usually the device chenged status).
    This array is passed to a user-defined function "eis_updatepage(thisarray)" which actually updates the page.
    This function MUST be externally defined by the user.
*/


// return a formatted data string from a UNIX timestamp
function eis_date(timestamp) {
    var d = new Date(timestamp*1000);
    return d.getDate()+"-"+(d.getMonth()+1)+"-"+d.getFullYear()+"  "+d.getHours()+":"+d.getMinutes()+":"+d.getSeconds();
}


// realtime processing code (long poll)
var httpchan;
var page='http://'+window.location.hostname+window.location.pathname; 
if (page.charAt(page.length-1)=="/") page=page+'index.php';
if (window.XMLHttpRequest)  
    httpchan=new XMLHttpRequest();  // code for IE7+, Firefox, Chrome, Opera, Safari
else
    httpchan=new ActiveXObject("Microsoft.XMLHTTP");    // code for IE6, IE5

httpchan.onreadystatechange=function() {
    var d;
    if (httpchan.readyState==4 && httpchan.status==200) {
        // process JSON data
        d = eval( "("+httpchan.responseText+")" );
        eis_updatepage(d);
        // and reopen http channel again
        httpchan.open("GET",page+"?realtime=1",true);
        httpchan.send();
    }
}
// open http channel for the first time
httpchan.open("GET",page+"?realtime=1",true);
httpchan.send();


// send back a name-value couple originating from this page
// as a GET request, ignoring the return page content
function eis_callback(name,value) {
    var httpchan2;
    if (window.XMLHttpRequest)
        httpchan2=new XMLHttpRequest(); // code for IE7+, Firefox, Chrome, Opera, Safari
    else 
        httpchan2=new ActiveXObject("Microsoft.XMLHTTP");   // code for IE6, IE5
    // send the GET request with the name-value as parameter
    httpchan2.open("GET",page+"?callback=1&"+name+"="+value,true);
    httpchan2.send();
}

