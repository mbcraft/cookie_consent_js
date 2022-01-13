/**
 * 
 * Author and Copyright : 
 * Marco Bagnaresi - MBCRAFT di Marco Bagnaresi
 * http://www.mbcraft.it
 * 
 * Version : 2.5.0
 * 
 * Contains parts from Cookies.js from https://github.com/ScottHamper/Cookies 
 * (THANKS!!!) - Code was public domain
 * 
 * Updated to match the requirements of the cookie law update of 10-06-2021.
 * Removed the part that checked if the client was actually used by a human.
 */

// -----------------------------
// Common functions
// -----------------------------
/**
 * Adds a listener for a specific event to a tag.
 * This function is portable.
 * 
 * @param obj The tag for attaching the listener
 * @param event_name The event name, eg. 'click'
 * @param listener The listener to add
 */
function __addListener(obj, event_name, listener) {
    if (obj.addEventListener) {
        obj.addEventListener(event_name, listener);
    } else if (obj.attachEvent) {
        obj.attachEvent("on" + event_name, listener);
    } else
        throw "Unable to add listener.";
}

/**
 * Removes a listener for a specific event on a specific tag.
 * This function is portable.
 * 
 * @param obj The tag that contains the attached listener on a specific event
 * @param event_name The event name, eg. 'click'
 * @param listener The listener to remove
 */
function __removeListener(obj, event_name, listener) {
    if (obj.removeEventListener) {
        obj.removeEventListener(event_name, listener);
    } else if (obj.detachEvent) {
        obj.detachEvent("on" + event_name, listener);
    } else
        throw "Unable to remove listener."
}

if (cookies!==undefined)
    throw "cookies variable is already defined.";

/*
Libreria di gestione dei cookies.
*/
var cookies = {
    // Used to ensure cookie keys do not collide with
    // built-in `Object` properties
    __minExpireDate: new Date('Thu, 01 Jan 1970 00:00:00 UTC'),
    __maxExpireDate: new Date('Fri, 31 Dec 9999 23:59:59 UTC'),
    defaults: {
        path: '/',
        secure: false,
        expires: new Date('Fri, 31 Dec 9999 23:59:59 UTC')
    },
    has: function(key) {
        var cookieAsArray = cookies.__getKvpFromCookieString();

        return cookieAsArray[key] !== undefined;
    },
    get: function(key) {
        var cookieAsArray = cookies.__getKvpFromCookieString();

        return cookieAsArray[key];
    },
    set: function(key, value, options) {
        options = cookies.__getExtendedOptions(options);
        options.expires = cookies.__getExpiresDate(value === "" ? cookies.__minExpireDate : options.expires);
        document.cookie = cookies.__generateCookieString(key, value, options);
    },
    expire: function(key) {
        cookies.set(key, "", {});
    },
    expireAll : function() {
        var cookiesArray = document.cookie ? document.cookie.split('; ') : [];
        for (var i = 0; i < cookiesArray.length; i++) {
            var cookieKvp = cookies.__getKeyValuePairFromCookie(cookiesArray[i]);
            cookies.expire(cookieKvp.key);
        }
    },
    __getExtendedOptions: function(options) {
        return {
            path: options && options.path || cookies.defaults.path,
            domain: options && options.domain || cookies.defaults.domain,
            expires: options && options.expires || cookies.defaults.expires,
            secure: options && options.secure !== undefined ? options.secure : cookies.defaults.secure
        };
    },
    __isValidDate: function(date) {
        return Object.prototype.toString.call(date) === '[object Date]' && !isNaN(date.getTime());
    },
    __getExpiresDate: function(expires, now) {
        now = now || new Date();

        if (typeof expires === 'number') {
            expires = expires === Infinity ?
                    cookies.__maxExpireDate : new Date(now.getTime() + expires * 1000);
        } else if (typeof expires === 'string') {
            expires = new Date(expires);
        }

        if (expires && !cookies.__isValidDate(expires)) {
            throw new Error('`expires` parameter cannot be converted to a valid Date instance');
        }

        return expires;
    },
    __generateCookieString: function(key, value, options) {
        key = key.replace(/[^#$&+\^`|]/g, encodeURIComponent);
        key = key.replace(/\(/g, '%28').replace(/\)/g, '%29');
        value = (value + '').replace(/[^!#$&-+\--:<-\[\]-~]/g, encodeURIComponent);
        options = options || {};

        var cookieString = key + '=' + value;
        cookieString += options.path ? ';path=' + options.path : '';
        cookieString += options.domain ? ';domain=' + options.domain : '';
        cookieString += options.expires ? ';expires=' + options.expires.toUTCString() : '';
        cookieString += options.secure ? ';secure' : '';

        return cookieString;
    },
    __getKvpFromCookieString: function() {
        var cookieResult = {};
        var cookiesArray = document.cookie ? document.cookie.split('; ') : [];

        for (var i = 0; i < cookiesArray.length; i++) {
            var cookieKvp = cookies.__getKeyValuePairFromCookie(cookiesArray[i]);

            if (cookieResult[cookieKvp.key] === undefined) {
                cookieResult[cookieKvp.key] = cookieKvp.value;
            }
        }

        return cookieResult;
    },
    __getKeyValuePairFromCookie: function(cookieString) {
        // "=" is a valid character in a cookie value according to RFC6265, so cannot `split('=')`
        var separatorIndex = cookieString.indexOf('=');

        // IE omits the "=" when the cookie value is an empty string
        separatorIndex = separatorIndex < 0 ? cookieString.length : separatorIndex;

        return {
            key: decodeURIComponent(cookieString.substr(0, separatorIndex)),
            value: decodeURIComponent(cookieString.substr(separatorIndex + 1))
        };
    },
    areEnabled: function() {
        var testKey = 'test_cookies';
        cookies.set(testKey, 1);
        var areEnabled = cookies.get(testKey) === '1';
        cookies.expire(testKey);
        return areEnabled;
    },
    // ---------------------------------------
    // Cookie policy banner, preferences, etc. 
    // ---------------------------------------
    __cookieVersion: null,
    __cookiePreferencesTableShown: false,
    __cookieApplications: [],
    __loadedCss: [],
    __loadedJs: [],
    areAccepted : function() {
        return this.areEnabled() && this.has("cookies_accepted") && this.get("cookies_accepted") && this.get("cookies_version") == this.__cookieVersion;
    },
    __removeCookieBanner: function() {
        var banner = document.getElementById('cookies_banner');
        if (banner !== null)
            document.body.removeChild(banner);
    },
    //set cookies as accepted (all)
    __acceptCookies: function(ev) {
        cookies.__removeCookieBanner();
        if (!cookies.has("cookies_accepted")) {
            cookies.set("cookies_accepted", "true");
            cookies.set("cookies_version",this.__cookieVersion);
            //when accepted, hide banner and set all 'apps' as accepted
            for (i = 0; i < cookies.__cookieApplications.length; i++) {
                var id = cookies.__cookieApplications[i].id;
                cookies.set("cookies__" + id, "true");
            }
            cookies.loadEnabledApplications();
        }
        
    },
    //sets cookies as not accepted (all)
    __noCookies: function(ev) {
        cookies.__removeCookieBanner();
        cookies.set("cookies_accepted", "false");
        cookies.set("cookies_version",this.__cookieVersion);
    },
    //notify will be async
    __notify: function(user_action, effect) {
        setTimeout(function() {
        var path_and_params = "/log_cookies.php?USER_ACTION="+user_action+"&EFFECT="+effect;
        var xmlhttp;
        if (window.XMLHttpRequest)
            xmlhttp = new XMLHttpRequest();
        else
            xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");

        xmlhttp.open("GET",path_and_params,false);
        xmlhttp.send();
        },10);
    },
    /**
     * This method creates a configuration table for all the registered cookie applications.
     * It puts all the table html inside a div with id 'cookie_applications_placeholder'.
     * 
     * It requires that all calls to setupApplicationCookies are done BEFORE this one.
     * 
     * @param {type} enable_label The localized label for the 'enable' column.
     * @param {type} application_label The localized label for the 'application name' column.
     * @param {type} description_label The localized label for the 'application description' column.
     */
    showCookiePreferencesTable: function(enable_label, application_label, description_label) {        
        var table_el = '<table id="cookie_applications">';
        table_el += '<tr><th>' + enable_label + '</th><th>' + application_label + '</th><th>' + description_label + '</th></tr>';
        for (i = 0; i < cookies.__cookieApplications.length; i++) {
            var app = cookies.__cookieApplications[i];
            table_el += "<tr>";
            table_el += "<td>";
            table_el += "<input type='checkbox' id='enable__" + app.id + "' name='" + app.id + "__enabled' ";
            var checked = cookies.has("cookies__" + app.id) && cookies.get("cookies__" + app.id) === "true";
            if (checked)
                table_el += " checked='checked'";
            else
                table_el += " ";
            table_el += " />";
            table_el += "</td>";
            table_el += "<td>" + app.id + "</td><td>" + app.description + "</td>";
            table_el += "</tr>";
        }
        table_el += '</table>';

        document.getElementById("cookie_applications_placeholder").innerHTML = table_el;

        var f_proto_to_eval = 'var f_proto = function () {\n'+
                'var el = document.getElementById("enable__{my_id}");\n'+
                'if (el.checked) {\n'+
                    'cookies.set("cookies__{my_id}", "true");\n'+
                    'cookies.set("cookies_accepted", "true");\n'+
                    'cookies.__notify("CHECKBOX_YES","ALLOW-{my_id}");\n'+
                '} else {\n'+
                    'cookies.set("cookies__{my_id}", "false");\n'+
                    'cookies.__notify("CHECKBOX_NO","DENY-{my_id}");\n'+
                '}\n'+
            '};';

        //adding events for updating preferences on applications
        for (i = 0; i < cookies.__cookieApplications.length; i++) {
            var app = cookies.__cookieApplications[i];
            my_func = f_proto_to_eval.replace("{my_id}",app.id).replace("{my_id}",app.id).replace("{my_id}",app.id).replace("{my_id}",app.id).replace("{my_id}",app.id);
            eval(my_func);
            __addListener(document.getElementById("enable__" + app.id), "click", f_proto);
        }
        
        this.__cookiePreferencesTableShown = true;
    },
    getEnabledApplicationCookies: function() {
        if (!cookies.has("cookies_accepted") || cookies.get("cookies_accepted")==="false") return [];
        var result = [];
        for (var i=0;i < this.__cookieApplications.length;i++) {
            var id = this.__cookieApplications[i].id;
            if (cookies.has("cookies__" + id) && cookies.get("cookies__" + id)==="true")
                result.push(id);
        }
        return result;
    },
    __getLoadingSpecs : function(id) {
        for (var i=0;i<this.__cookieApplications.length;i++)
            if (this.__cookieApplications[i].id===id)
                return this.__cookieApplications[i].loading_specs;
        throw "The supplied id is not valid.";
    },
    loadEnabledApplications: function() {
        var loadedApplications = this.getEnabledApplicationCookies();
        for (var i=0;i<loadedApplications.length;i++) {
            var specs = this.__getLoadingSpecs(loadedApplications[i]);
            if (specs!==undefined)
                this.__loadWithSpecs(specs);
        }
    },
    __isJsAlreadyLoaded : function(path) {
        for (var i=0;i<this.__loadedJs.length;i++) {
            if (this.__loadedJs[i]===path) {
                alert("JS already loaded : "+path);
                return true;
            }
        }
        return false;
    },
    __isCssAlreadyLoaded : function(path) {
        for (var i=0;i<this.__loadedCss.length;i++) {
            if (this.__loadedCss[i]===path) {
                alert("CSS already loaded : "+path);
                return true;
            }
        }
        return false;
    },
    __loadWithSpecs : function(specs) {
        for (var i=0;i<specs.length;i++) {
            var spec = specs[i];
            switch (spec.type) {
                case "js":if (!this.__isJsAlreadyLoaded(spec.path)) this.__addJsFragment(spec);continue;
                case "css":if (!this.__isCssAlreadyLoaded(spec.path)) this.__addCssFragment(spec);continue;
                case "html":this.__addHtmlFragment(spec);continue;
                default:throw "Spec type not supported.";
            }
        }
    },
    __addJsFragment : function(spec) {
        var scriptNode = document.createElement("script");
        scriptNode.setAttribute("src",spec.path);
        if (spec.id!==null)
            scriptNode.setAttribute("id",spec.id);
        document.getElementsByTagName("head")[0].appendChild(scriptNode);
        this.__loadedJs.push(spec.path);
    },
    __addCssFragment : function(spec) {
        var cssNode = document.createElement("link");
        cssNode.setAttribute("rel","stylesheet");
        cssNode.setAttribute("type","text/css");
        cssNode.setAttribute("href",spec.path);
        if (spec.media!==undefined)
            cssNode.setAttribute("media",spec.media);
        document.getElementsByTagName("head")[0].appendChild(cssNode);
        this.__loadedCss.push(spec.path);
    },
    __addHtmlFragment : function(spec) {
        var xmlhttp;
        if (window.XMLHttpRequest)
            xmlhttp = new XMLHttpRequest();
        else
            xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");

        xmlhttp.open("GET",spec.path,true);
        xmlhttp.send();
        xmlhttp.onreadystatechange = function() {
            if(this.readyState == this.DONE) {
                document.getElementById(spec.id).innerHTML = this.responseText;
            }
        }
    },

    /**
     * Setup the cookie version used. When the cookies used from the application change, you need to increase the version number and
     * update the setup phase for the cookies.
     */
    setupCookieVersion: function(version) {
        cookies.__cookieVersion = version;
        if (!cookies.has("cookies_version") || cookies.get("cookies_version")!=version) {
            cookies.expireAll();
        }
    },

    /**
     * Setup informations for a single 'cookie application'.
     * Configuration information as 'enabled' or 'disabled' are saved into cookies.
     * 
     * Loading_specs is an array of loading 'instructions', eg:
     * [{id:"mylib_js",type:"js",path:"/js/mylib.js"},{id:"mylib_css",type:"css",path:"/css/mylib.css"},{id:"mylib_div",type:"html",path:"/include/part.php"}]
     * 
     * @param {type} id The id of the element that will contain the application html.
     * @param {type} description The localized description of this cookie application.
     * @param {type} loading_specs The array of loading specs for this application
     */
    setupApplicationCookies: function(id, description, loading_specs) {
        this.__cookieApplications.push({id: id, description: description, loading_specs: loading_specs});
        if (!cookies.has("cookies__" + id))
            cookies.set("cookies__" + id, "false");
    },
    /**
     * shows the cookies information banner if no decision about cookies has been made about them.
     * 
     * @param {type} html_text The localized html text (with links to more extended informations if needed) of this banner.
     * @param {type} ok_label The localized label of the 'Accept' button.
     */
    showCookieBanner: function(html_text, ok_label) {

        if (!cookies.__cookiePreferencesTableShown && !cookies.has("cookies_accepted")) {
            cookies.set("cookie_consent_js_screen_width", screen.width);
            cookies.set("cookie_consent_js_screen_height", screen.height);

            var div_el = '<div id="cookies_banner">' +
                    '<div id="cookies_buttons_container">' +
                    '<input id="cookies_no_button" type="button" name="no_cookies" value="X" />' +
                    '<input id="cookies_accept_button" type="button" name="ok_cookies" value="' + ok_label + '" />' +
                    '</div><div id="cookies_text_container"><div id="cookies_padding">' +
                    html_text +
                    '</div></div></div>';

            var my_div = document.createElement("div");
            my_div.innerHTML = div_el;

            //adds the banner as the first element of the page
            document.body.insertBefore(my_div.firstChild, document.body.firstChild);

            var ok_cookies_button = document.getElementById('cookies_accept_button');
            __addListener(ok_cookies_button, "mousedown", cookies.__cookieAcceptButtonTracker);
            __addListener(ok_cookies_button, "click", cookies.__cookieAcceptButtonTracker); //fix for accessibility

            var no_cookies_button = document.getElementById('cookies_no_button');
            __addListener(no_cookies_button, "mousedown", cookies.__cookieCloseButtonTracker);
            __addListener(no_cookies_button, "click", cookies.__cookieCloseButtonTracker); //fix for accessibility
        
        }
        else if (cookies.get("cookies_accepted")==="true") {
            this.loadEnabledApplications();
        }
    },
    deleteAllButton: function() {
        cookies.expireAll();
        cookies.__notify("DELETE_ALL_BUTTON","DELETE-*");
    },
    __cookieCloseButtonTracker: function(ev) {
        cookies.__noCookies(ev);
        cookies.__notify("CLOSE_BUTTON","DENY-*");
    },
    __cookieAcceptButtonTracker: function(ev) {
        cookies.__acceptCookies(ev);
        cookies.__notify("ACCEPT_BUTTON","ALLOW-*");
    }
};

//--- cookie_consent_js END