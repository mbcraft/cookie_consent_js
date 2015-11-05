<?php
/**
 * 
 * Author and Copyright : 
 * Marco Bagnaresi - MBCRAFT di Marco Bagnaresi
 * http://www.mbcraft.it
 * 
 * Version : 2.4.1
 * 
 * This file contains all the requires that loads all the library classes. You can require
 * this class and forget all other require and dependency stuff.
 * It also sets the PIOL_ROOT_PATH constant to a reasonable default (the document root
 * of your web site).
 */

namespace Piol {
    
    require_once("PiolObject.php");
    require_once("FileSystemUtils.php");
    
    FileSystemUtils::checkPiolRootPath();

    require_once("IOException.php");
    require_once("__FileSystemElement.php");
    require_once("PropertiesUtils.php");
    require_once("FileReader.php");
    require_once("FileWriter.php");
    require_once("File.php");
    require_once("Dir.php");  
}

