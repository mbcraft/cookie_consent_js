<?php
/**
 * 
 * Author and Copyright : 
 * Marco Bagnaresi - MBCRAFT di Marco Bagnaresi
 * http://www.mbcraft.it
 * 
 * Version : 2.4.1
 *
 */

$my_dir = "/cookies";

//core classes are loaded
require_once("piol/PiolLite.php");

//cookies logging stuff
require_once("CookiesLogInputReader.php");

//loads cookies logging configuration

$prop_file = new \Piol\File($my_dir."/config.ini");

if (!$prop_file->exists()) throw new \Piol\IOException("config.ini not found.");

$props = \Piol\PropertiesUtils::readFromFile($prop_file,false);

//reads the parameter to be logged

$user_action = \Cookies\CookiesLogInputReader::readUserActionFromRequest($props);

$effect = \Cookies\CookiesLogInputReader::readEffectFromRequest($props);

//needed as dependency
require_once("drivers/ICookieLogDriver.php");
// initialize the log driver : it will be used for both logging and 
// intrusion detection
//loads the driver class name
$log_driver = $props["cookies_log_driver"];
//loads the driver class
require_once("drivers/".$log_driver.".php");

$namespaced_log_driver = "\\Cookies\\".$log_driver;

//creates an instance of the driver
$driver_instance = new $namespaced_log_driver;

//initializes the driver
$driver_instance->setup($props);

//checks if the parameters are ok for logging

if (\Cookies\CookiesLogInputReader::doLogging($user_action,$effect)) {
    //logs the cookies preference change action
    $driver_instance->log($user_action,$effect);
} else {
    //log the intrusion effort
    $driver_instance->intrusion_detected();
}