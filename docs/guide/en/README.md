# English

### Overview

This software package is composed of three parts : cookie management with consent banner and cookies
preferences, automatic user recognition (human.js) and server side cookies preference change
action logging ('log_cookies.php' and 'cookies' folder). In the test folder you can find working example of the features 
of this library.

NOTE : some features doesn't work locally if you use Internet Explorer. The library should
be cross browser when used online. 

## Cookies

The cookie library can be used to manage cookies and to handle the cookies policy for a web site.
A php web server is mandatory for the cookies preference change logging.
It needs the cookies.css to be loaded for banner styles using a <link> tag in the page header.

Basically, you have many applications that can use cookies. Every application is composed of
javascript's, css's and html to be OPTIONALLY loaded from the web server (based on the cookie preferences).
So, after loading the library in the page header, at the beginning of the page you open a script tag
and setup all the 'applications' that uses cookies. Basically here you will declare all non-technical
cookies applications, since technical ones are always needed.

eg:
`
cookies.setupApplicationCookies("an_app", "Sample description.",[{id:"js_l_1",path:"js/jslib.js",type:"js"},{id:"css_1",type:"css",path:"css/my.css"},{id:"an_app_html_placeholder",type:"html",path:"/test/html/part.html"},{id:"js_l_2",path:"js/jslib2.js",type:"js"}]);
cookies.setupApplicationCookies("my_cookie_app", "This is a sample description.",[{id:"css_2",type:"css",path:"css/my.css"}]);
cookies.setupApplicationCookies("another_app", "Another description for another application.",[{id:"js_l_3",path:"js/jslib.js",type:"js"}]);
`
The method 'cookies.setupApplicationCookies' takes 3 parameters.
The first one is an unique identifier of an application. It is used shown in the cookie preferences
table. The second one is the application description, this is also shown in the preferences table.
The third field is an array of loading specs (can be empty).
Each spec is an object {} with the following fields :

id -> string id of this loading spec. If type is 'html' it's also the id of the html element in which place loaded content.
type -> can be 'js', 'css' or 'html'. If 'js' or 'css' the resource is added to the page header, otherwise it is placed inside an element of the page identified by its id.
path -> the path of the resource to load. The resource is loaded synchronously with a GET request.
(media) -> if type is 'css' you can also append a 'media' specified that is added as an attribute to the css link tag.

You are not required to provide specs : if you do, resources are loaded in this way. 
If a js or css file is required by more than one application, it is loaded only once (if it has the same path).
You are free to load resources the way you want (eg. using jquery). I advice you to put all the setupApplicationCookies
calls just before the opening <body> tag.

To check which 'cookie applications' are enabled, simply use the method :

`cookies.getEnabledApplicationCookies()`

it returns an array of 'cookie application id'. (first parameter of cookies.setupApplicationCookies method).

To show the banner showing the short cookie policy, you can use the method :
`
cookies.showCookieBanner("Short cookie policy html here", "accept button label here");
`     

It takes two parameter : the first one is the html of the short cookie policy, the second one is the text label of the 'Accept' button.
When you call this method, the banner is shown only if :

- no cookie preference table was rendered in this page
- cookies were not already accepted

Also, if cookies were already accepted all application cookies are loaded by order. So you can safely
call this method at the bottom of each page.

Also, you can render a cookie preferences table by calling the following method :
`
cookies.showCookiePreferencesTable("enabled_label","application_label","description_label");
`

It takes three parameters : 

- 'enabled' column label.
- 'application id' column label.
- 'description' column label.

This will allow localization of the table.
The table will have three columns : in the first one the check box will enable the user to enable/disable
an application. When you show the table, banner is not shown (if it is called before this call) and
so cookie applications are not auto-loaded.

If you want to force auto-loading, you can call :
`
cookies.loadEnabledApplications();
`

The server side part ('cookies' folder) must be copied in a specific folder, and the file
log_cookies.php must be copied in the web site root folder. If the folder is renamed, you must
edit the include in the log_cookies.php file. The root folder must be writable by php
since the file logger actually creates files and directories.
If the folder names are not changed then the default settings should work correctly
out of the box. Actually only logging on file is supported. It is possible to implement different types of logging
by implementing the ICookieLogDriver interface.


## HumanJS

The second part of the library is used for checking if someone is human (non-bot). It actually uses mouse motion, clicks (both single and double), key presses, mouse scroll and elapsed time to calculate a score.
It uses cookies to remember human recognition score between pages of the same web site.
After user is recognized (score is greater than a fixed value) as human all event handlers used are unregistered. It also saves the
screen size inside cookies ('humanjs_screen_width' and 'humanjs_screen_height'). If
the user is verified a cookie named 'humanjs_status' with value 'verified' is written.

It has no external dependencies, contains cookies.js (see credits below).

To use the library it's necessary to include it in your page (using use the *script* tag, for example) :

`<script type='text/javascript' src='/js/human.js'></script>`

calls can be used after inclusion (in another *script* tag, for example) :

`
<form name="my_form" method="POST" action="">
    <!-- 
    ...
    form labels and fields 
    ...
    -->
    <!-- the submit button is hidden, it's shown only when a user is recognized as a human -->
    <input id='submit_button' style="display:hidden;" type='submit' name="Invia" value="Invia" />
</form>
<script type='text/javascript'>

        humanjs.setup(function() {
            document.getElementById("submit_button").style.display = "block";
        }, false);

</script>
`

In the previous example the 'Send' form button  is shown only when the user is recognized as human by the library.
A better strategy could include also setting the _action_ attribute of the form to a valid value.


##### Parameters :

* *callback* : function to be called when the user is recognized as human. If _null_ no callback will be invoked (but the user will be set as verified anyway).
* *forceCallback* : if _true_ executes callback if user is already verified when setup is called.



`humanjs.setupWithId(element_id,callback,forceCallback);`

##### Parameters :

* *element_id* : string id of the element to attach user trackers. Events are captured only
inside that element.
* *callback* : function to be called when the user is recognized as human. If _null_ no callback will be invoked (but the user will be set as verified anyway).
* *forceCallback* : if _true_ executes callback if user is already verified when setup is called.
 
`humanjs.setupWithElement(element,callback,forcecallback);`
 
Same as above but takes an html javascript _element_ as argument. Events are captured only
inside that element.

`humanjs.isVerified();`

Returns _true_ if user agent is recognized as verified human, _false_ otherwise.

`humanjs.resetVerified();`

Reset status as not human user not verified.
Once a human user is recognized all listeners are removed.
Values inside the library are preset to reasonable values.

## Licenza

See the license conditions inside the docs/license folder inside this package.


## Credits

Some parts from Cookies.js (embedded in human.js) - a BIG thanks to https://github.com/ScottHamper/Cookies/blob/master/src/cookies.js

Copyright by : MBCRAFT di Marco Bagnaresi - http://www.mbcraft.it
