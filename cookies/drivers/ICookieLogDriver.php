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

namespace Cookies {

    /**
     * This interface is implemented by classes who logs activities for cookies
     */
    interface ICookieLogDriver {

        /**
         * Does the setup of the driver using the properties read from the config
         * file.
         */
        function setup($properties);

        /**
         * Logs the information received about the log action.
         */
        function log($user_action, $effect);

        /**
         * Logs the intrusion detected
         */
        function intrusion_detected();
    }

}