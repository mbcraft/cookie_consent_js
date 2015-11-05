
//calling a previously loaded js function
my_alert_function();
 
var xyz = document.getElementById("xyz");
if (xyz===undefined)
    alert("Unable to find xyz div");
else
    xyz.setAttribute("style","display:none;")