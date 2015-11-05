
function my_alert_function() {
    alert("You have accepted cookies. This text comes from a dinamically loaded JS library.\nThat depends on cookies (an_app).");
}

var greening_span = document.getElementById("greening_span");
if (greening_span===undefined)
    alert("Unable to find greening_span span");
else
    greening_span.setAttribute("style","color:green;");