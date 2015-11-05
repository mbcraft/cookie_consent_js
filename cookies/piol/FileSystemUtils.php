<?php
/**
 * 
 * Author and Copyright : 
 * Marco Bagnaresi - MBCRAFT di Marco Bagnaresi
 * http://www.mbcraft.it
 * 
 * Version : 2.4.1
 * 
 * This file contains the FileSystemUtils class. It contains only static helper methods
 * used for checking file system stuff.
 * 
 * Some help from :
 * http://stackoverflow.com/questions/1976007/what-characters-are-forbidden-in-windows-and-linux-directory-names
 * http://stackoverflow.com/users/22437/dour-high-arch
 * https://msdn.microsoft.com/en-us/library/aa365247
 */

namespace Piol {

    /**
     * This class contains various file system utility static methods : checking for total
     * and available disk space, file name validation and file and directory identification.
     */
    class FileSystemUtils extends PiolObject {
        
        /**
         * Checks if the constant PIOL_ROOT_PATH is defined. If not it is defined 
         * using the $_SERVER["SCRIPT_NAME"] and $_SERVER["SCRIPT_FILENAME"] environment variables.
         */
        public static function checkPiolRootPath() {
            if (!defined("PIOL_ROOT_PATH"))
            {
                //imposta l'include path in modo assoluto per la root del sito
                $real_script_name = str_replace("\\",DIRECTORY_SEPARATOR,$_SERVER['SCRIPT_NAME']);
                $piol_root_path = str_replace($real_script_name, "", $_SERVER['SCRIPT_FILENAME']);
                define("PIOL_ROOT_PATH",$piol_root_path);
            }
        }

        /**
         * 
         * Returns true if the specified name is the current folder path specifier, false otherwise.
         * 
         * @param string $name the name to check
         * @return boolean true if it is the current dir specifier (.), false otherwise.
         * 
         * @api
         */
        public static function isCurrentDirName($name) {
            return $name == DOT;
        }

        /**
         * 
         * Returns true if the specified name is the parent folder path specifier, false otherwise.
         * 
         * @param string $name the name to check.
         * @return boolean true if it is the parent dir specifier (..), false otherwise.
         * 
         * @api
         */
        public static function isParentDirName($name) {
            return $name == DOT.DOT;
        }
        
        /**
         * 
         * Checks if the specified filename is a valid filename.
         * 
         * @param string $filename the name to check
         * 
         * @api
         */
        public static function isValidFilename($filename) {
            static $FORBIDDEN_NAMES = ["COM","PRN","AUX","NUL","COM1","COM2","COM3","COM4","COM5","COM6","COM7","COM8","COM9","LPT1","LPT2","LPT3","LPT4","LPT5","LPT6","LPT7","LPT8","LPT9"];
            foreach ($FORBIDDEN_NAMES as $prefix) {
                if (($filename == $prefix) || (strpos($filename,$prefix.".")===0)) 
                        return false;
            }
            $result = preg_match("/.*[\<\>\:\"\/\\\|\?\*]+.*/",$filename) || self::isCurrentDirName($filename) || self::isParentDirName($filename);
            return !$result;
        }
        
        /**
         * 
         * Checks if a file name is valid. If not, throws an IOException.
         * 
         * @param string $filename The name to check
         * @throws IOException If the name is not valid.
         * 
         * @api
         */
        public static function checkValidFilename($filename) {
            if (!self::isValidFilename($filename)) {
                throw new IOException("The filename is not valid : ".$filename);
            }
        }

        /**
         * 
         * Checks if the specified path is a valid file path.
         * 
         * @param string $path the path to check as a string
         * @return boolean true if it is a valid file path, false otherwise.
         * 
         * @api
         */
        public static function isFile($path) {
            return is_file(PIOL_ROOT_PATH . $path);
        }

        /**
         * 
         * Checks if the specified path is a valid directory path.
         * 
         * @param string $path the path to check as a string
         * @return boolean true if it is a valid directory path, false otherwise.
         * 
         * @api
         */
        public static function isDir($path) {
            return is_dir(PIOL_ROOT_PATH . $path);
        }

        /**
         * 
         * Returns the available disk space inside the PIOL_ROOT_PATH.
         * 
         * @return long the available number of bytes of disk space.
         * 
         * @api
         */
        public static function getFreeDiskSpace() {
            return disk_free_space(PIOL_ROOT_PATH);
        }

        /**
         * 
         * Returns the total disk space inside the PIOL_ROOT_PATH.
         * 
         * @return long the total number of bytes of disk space.
         * 
         * @api
         */
        public static function getTotalDiskSpace() {
            return disk_total_space(PIOL_ROOT_PATH);
        }
        
    }

}

?>