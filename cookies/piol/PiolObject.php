<?php
/**
 * 
 * Author and Copyright : 
 * Marco Bagnaresi - MBCRAFT di Marco Bagnaresi
 * http://www.mbcraft.it
 * 
 * Version : 2.4.1
 * 
 * This class is used to give meaningful errors to user who mistype method calls on 
 * Piol library.
 */
namespace Piol {
    class PiolObject {
        function __call($name, $arguments) {
            $message = "Method ".$name." not found.";
            echo $message;
            throw new Exception($message);
        }
        
        static function __callStatic($name, $arguments) {
            $message = "Method ".$name." not found.";
            echo $message;
            throw new Exception($message);
        }
    }
}

