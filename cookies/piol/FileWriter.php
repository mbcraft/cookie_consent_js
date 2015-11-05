<?php
/**
 * 
 * Author and Copyright : 
 * Marco Bagnaresi - MBCRAFT di Marco Bagnaresi
 * http://www.mbcraft.it
 * 
 * Version : 2.4.1
 * 
 * This file contains the FileWriter class.
 */
namespace Piol {

    /**
     * This class is used to write inside a file. It extends FileReader and provides
     * additional write operations. It has support for write, writeln, printf and writing
     * CSV lines.
     * methods. It also enable you to change the default line ending.
     * 
     * To obtain a FileWriter for a given file use the File::openWriter() instance method.
     */
    class FileWriter extends FileReader {

        /**
         * Constant for carriage return.
         */
        const CR = "\r";
        /**
         * Constant for line feed.
         */
        const LF = "\n";
        /**
         * 
         * @var boolean This variable contains the default line ending setting for all future FileWriter instances.
         * 
         * @internal 
         */
        private static $default_line_ending_crlf = true;
        /**
         * 
         * @var boolean This variable contains the line ending setting for this FileWriter instance. 
         * 
         * @internal
         */
        private $line_ending_crlf = null;
        
        /**
         * 
         * Sets the default line ending mode for all file writers.
         * 
         * @param boolean $default_line_ending_crlf true if CRLF should be used, false for CR.
         * 
         * @api
         */
        public static function setDefaultLineEndingModeCrlf($default_line_ending_crlf) {
            self::$default_line_ending_crlf = $default_line_ending_crlf;
        }

        /**
         * 
         * Gets the default line ending mode for all file writers.
         * 
         * @return boolean true if CRLF should be used, false for CR.
         * 
         * @api
         */
        public static function getDefaultLineEndingModeCrlf() {
            return self::$default_line_ending_crlf;
        }
        
        /**
         * 
         * Sets the line ending mode for this file writer. 
         * 
         * @param boolean $line_ending_mode true for CRLF, false for CR.
         * 
         * @api
         */
        public function setLineEndingModeCrlf($line_ending_mode) {
            $this->line_ending_crlf = $line_ending_mode;
        }
        
        /**
         * 
         * Gets the line ending mode for this file writer. If not set uses the default.
         * 
         * @return boolean returns true if CRLF should be used, false for CR.
         * 
         * @api
         */
        public function getLineEndingModeCrlf() {
            if ($this->line_ending_crlf===null) {
                $this->line_ending_crlf = self::getDefaultLineEndingModeCrlf();
            }
            return $this->line_ending_crlf;
        }
        
        /**
         * 
         * Writes values to this writer following the printf rules. All additional parameters
         * after 'format' are treated as data.
         * 
         * @param type $format The format to use, following the printf rules.
         *
         * @throws IOException if this writer is already closed.
         * 
         * @api
         */
        public function printf($format) {
            $this->checkClosed();

            $args = func_get_args();
            $printf_args = array_slice($args, 1);

            $p = 'fprintf($this->my_handle,$format';
            $i = 0;
            foreach ($printf_args as $arg) {

                $p.=',$printf_args[' . $i . ']';
                $i++;
            }
            $p.=");";
            eval($p);
        }

        /**
         * 
         * Writes a string inside this file at the current position. No characters are added.
         * 
         * @param string $string The string to write.
         * @return the number of bytes written, or a negative value if an error occurred.
         * 
         * @throws IOException if this writer is already closed.
         * 
         * @api
         */
        public function write($string) {
            $this->checkClosed();

            return fwrite($this->my_handle, $string);
        }

        /**
         * 
         * Writes a string to this file, ending it with CRLF characters.
         * 
         * @param type $string the string to write
         * 
         * @throws IOException if this writer is already closed.
         * 
         * @api
         */
        public function writeln($string) {
            $this->checkClosed();
            
            $line_end = $this->getLineEndingModeCrlf() ? self::CR.self::LF : self::CR;

            fwrite($this->my_handle, $string . $line_end);
        }

        /**
         * 
         * Writes a line into this file following the CSV (comma separated values) file convention.
         * 
         * @param array $values the ordered array of values to write.
         * @param string $delimiter the one string character to use as a delimiter.
         * @param string $enclosure the one string character to use as an enclosure.
         * @param string $escape the one string character to use as an escape character.
         * 
         * @throws IOException if this writer is already closed.
         * 
         * @api
         */
        public function writeCSV($values, $delimiter = ",", $enclosure='"', $escape='\\') {
            $this->checkChar($delimiter, "The delimiter is not a valid character.");
            $this->checkChar($enclosure, "The enclosure is not a valid character.");
            $this->checkChar($escape, "The escape is not a valid character.");
            
            $this->checkClosed();
            
            $final_string = "";

            foreach ($values as $v) {
                
                if (is_string($v)) {
                    $st_value = "".$v;
                    $v1 = str_replace($escape, $escape.$escape, $st_value);
                    $v2 = str_replace($enclosure, $escape.$enclosure, $st_value);
                } else {
                    $v2 = $v;
                }
                $final_string.= $enclosure;
                $final_string.= $v2;
                $final_string.= $enclosure.$delimiter;
            }
            
            $this->writeln(substr($final_string,0,strlen($final_string)-1));
        }

        /**
         * 
         * Truncate this file at the specified size.
         * 
         * @param long $size the maximum size of the file.
         * @return true if the operation was succesfull, false otherwise.
         * 
         * @throws IOException if this writer is already closed.
         * 
         * @api
         */
        public function truncate($size) {
            $this->checkClosed();

            return ftruncate($this->my_handle, $size);
        }

    }

}
?>