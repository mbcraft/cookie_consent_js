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
     * This class is used for fetching the cookies action parameters from the browser request.
     */
    class CookiesLogInputReader {

        static $user_action_values = array("ACCEPT_BUTTON", "CLOSE_BUTTON", "SCROLL", "PAGE", "CHECKBOX_NO", "CHECKBOX_YES", "DELETE_ALL_BUTTON");
        static $effect_prefixes = array("DENY-", "ALLOW-", "DELETE-*");

        /**
         * Reads the USER_ACTION parameter from the input request.
         * 
         * @param array $props The configuration properties, as an array
         * @return The parameter, or null if an intrusion is detected
         */
        static function readUserActionFromRequest($props) {
            $user_action = filter_input(INPUT_GET, "USER_ACTION");
            foreach (self::$user_action_values as $value) {
                if ($value == $user_action)
                    return $user_action;
            }

            return null;
        }

        /**
         * Reads the EFFECT parameter from the input request.
         * 
         * @param array $props The configuration properties, as an array
         * @return The parameter, or null if an intrusion is detected
         */
        static function readEffectFromRequest($props) {
            $effect = filter_input(INPUT_GET, "EFFECT");
            foreach (self::$effect_prefixes as $effect_prefix) {
                if (strpos($effect, $effect_prefix) === 0) {
                    if (strlen($effect) < 40)
                        return $effect;
                    else {
                        return null;
                    }
                }
            }

            return null;
        }

        /**
         * Checks if logging must be actually done if all parameters are not null.
         * 
         * @param string $user_action The user action parameter
         * @param string $effect The effect parameter
         * @return boolean true if logging must be done, false otherwise
         */
        static function doLogging($user_action, $effect) {
            if ($user_action !== null && $effect !== null)
                return true;
            else
                return false;
        }

    }

}