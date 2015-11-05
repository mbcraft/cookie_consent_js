<?php
/**
 * 
 * Author and Copyright : 
 * Marco Bagnaresi - MBCRAFT di Marco Bagnaresi
 * http://www.mbcraft.it
 * 
 * Version : 2.4.1
 * 
 * This file contains a custom ini parsing function and the PropertiesUtils class.
 */
namespace Piol {
    
    /**
     * 
     * This custom function processes string data for properties.
     * 
     * @param string $string the string of data to process.
     * @param boolean $process_sections true if the properties are within sections, false otherwise.
     * 
     * @internal
     */
    function my_parse_ini_string($string, $process_sections) {
        $array = array();

        $lines = explode("\n", $string);

        $current_section = null;

        foreach ($lines as $line) {

            if ($process_sections) {
                $section_line = preg_match("/\[(?P<section>[\w\s\d\'\"-\.]+)\]/", $line, $match);

                if ($section_line) {
                    $current_section = $match['section'];
                    $array[$current_section] = array();
                }
            }

            $statement = false;

            if ($process_sections && !$section_line || !$process_sections) {
                $statement = preg_match("/\A(?!;)(?P<key>[\w+\.\-]+?)\s*=\s*(?P<value>.+?)\s*\Z/", $line, $match);

                if ($statement) {
                    $key = $match['key'];
                    $value = $match['value'];

                    # Remove quote
                    if (preg_match("/\A\".+\"\Z/", $value) || preg_match("/\A'.+'\Z/", $value)) {
                        $value = mb_substr($value, 1, mb_strlen($value) - 2);
                    }

                    if ($current_section == null)
                        $array[$key] = $value;
                    else
                        $array[$current_section][$key] = $value;
                }
            }
        }
        return $array;
    }

    /**
     * This class contains helper methods for dealing with properties file. It actually contains
     * some basic string and file ini operations :
     * - read and write properties to file
     * - read and write properties to string
     * - add and remove properties from a file
     */
    class PropertiesUtils extends PiolObject {

        /**
         * 
         * Adds an entry to a properties file.
         * 
         * @param \Piol\File|string $file the file instance or the string path pointing to the properties file.
         * @param boolean $has_sections true if the properties file contains sections, false otherwise
         * @param string $key
         * @param type $entry_or_value
         * 
         * @api
         */
        public static function addEntriesToFile($file, $has_sections, $entries) {
            $properties = PropertiesUtils::readFromFile($file, $has_sections);
            foreach ($entries as $key => $value) {
                $properties[$key] = $value;
            }
            
            PropertiesUtils::saveToFile($file, $properties, $has_sections);
        }

        /**
         * 
         * Removes a property from a file. If the file does not exist it is created.
         * If the property is not found nothing is removed.
         * 
         * @param \Piol\File|string $file the file instance or the string path pointing to the properties file.
         * @param boolean $has_sections true if the file has sections, false otherwise.
         * @param string $key the key to remove
         * 
         * @api
         */
        public static function removeEntriesFromFile($file, $has_sections, $entries) {
            $properties = PropertiesUtils::readFromFile($file, $has_sections);
            if (is_string($entries)) {
                unset($properties[$entries]);
            } else {
                foreach ($entries as $i => $key) {
                    unset($properties[$key]);
                }
            }
            PropertiesUtils::saveToFile($file, $properties, $has_sections);
        }
                
        /**
         * 
         * Reads properties found inside a string.
         * 
         * @param string $string the string containing the properties data
         * @param boolean $process_sections true if sections must be processed inside properties file
         * @return array an array of properties, or nested arrays if sections are found and processed inside the string
         * 
         * @api
         */
        public static function readFromString($string, $process_sections) {
            return my_parse_ini_string($string, $process_sections);
        }

        /**
         * 
         * Reads all the properties from a file, optionally processing sections.
         * If the file does not exists, it returns an empty array.
         * 
         * @param \Piol\File|string $file the file instance or the string path pointing to the properties file.
         * @param boolean $process_sections true if sections must be processed inside the file, false otherwise.
         * @return array an array of properties, or nested arrays if sections are found and processed inside the properties file.
         * 
         * Some help found from this question :
         * http://stackoverflow.com/questions/13069401/parsing-ini-file-with-php-when-semi-colon-is-included-in-the-value
         * 
         * @api
         */
        public static function readFromFile($file, $process_sections) {
            $real_file = File::asFile($file);
            
            if ($real_file->exists())
                return parse_ini_file($file->getFullPath(), $process_sections, INI_SCANNER_RAW);
            else
                return array();
        }

        /**
         * 
         * Returns the properties data encoded as a string, ready for file writing.
         * 
         * @param array $properties properties data as array or nested arrays if contained in sections.
         * @param boolean $process_sections true if properties sections must be processed inside data, false otherwise.
         * @return string all the properties encoded as a string.
         * @throws IOException if the properties are null
         * 
         * @api
         */
        public static function saveToString($properties, $process_sections) {
            if ($properties === null)
                throw new IOException("Le properties sono nulle!");

            $tmp = '';
            if ($process_sections) {
                foreach ($properties as $section => $values) {
                    $tmp .= "[$section]\n";
                    foreach ($values as $key => $val) {
                        if (is_array($val)) {
                            foreach ($val as $k => $v) {
                                $tmp .= "{$key}[$k] = \"$v\"\n";
                            }
                        } else
                            $tmp .= "$key = \"$val\"\n";
                    }
                    $tmp .= "\n";
                }
            }
            else {
                foreach ($properties as $key => $val) {
                    $tmp .= "$key = \"$val\"\n";
                }
            }
            return $tmp;
        }

        /**
         * 
         * Save the provided properties to a file. Supports sections inside the properties data.
         * 
         * @param \Piol\File|string $file the file instance or string path pointing to the properties file.
         * @param mixed $properties properties data as array or nested arrays if contained in sections.
         * @param boolean $process_sections true if sections must be processed inside properties data, false otherwise
         * 
         * @api
         */
        public static function saveToFile($file, $properties, $process_sections) {
            $prop_string = self::saveToString($properties, $process_sections);
            
            $real_file = File::asFile($file);
            
            if (!$real_file->exists()) {
                $real_file->touch();
            }
            $real_file->setContent($prop_string);
        }

    }

}
?>