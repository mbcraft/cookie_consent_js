<?php
/**
 * 
 * Author and Copyright : 
 * Marco Bagnaresi - MBCRAFT di Marco Bagnaresi
 * http://www.mbcraft.it
 * 
 * Version : 2.4.1
 * 
 * This file contains the FileReader class.
 */
namespace Piol {

    /**
     * This class enables you to easily read data from files, providing methods
     * for reading characters, plain string lines or CSV data, and a scanf like method.
     * It also contains method for moving inside the file.
     * 
     * To obtain a FileReader for a given file use the File::openReader() instance method.
     */
    class FileReader extends PiolObject {

        /**
         * 
         * @var string Contains the unget character. Used for correct line ending parsing. 
         * 
         * @internal
         */
        private $ch = null;
        /**
         * 
         * @var handle The file handle for this FileReader, as returned from the fopen call. 
         * 
         * @internal
         */
        protected $my_handle;
        /**
         * 
         * @var boolean Contains the 'opened' status of this readed. 
         * 
         * @internal
         */
        protected $open;

        /**
         * Constructs a file reader from a given file handle. You should usually not need
         * to call this constructor since instances of FileReader are obtained throught File::openReader()
         * method.
         * 
         * @param handle $handle The handle to use for reading data from this file.
         * 
         * @internal
         */
        function __construct($handle) {
            $this->my_handle = $handle;
            $this->open = true;
        }

        /**
         * 
         * Checks if this file reader is closed. If so, throws an IOException.
         * 
         * @throws IOException if this reader is closed.
         * 
         * @internal
         */
        protected function checkClosed() {
            if (!$this->open)
                throw new IOException("Lo stream risulta essere chiuso!!");
        }

        /**
         * 
         * Checks if this reader is open.
         * 
         * @return boolean true if this file reader is open, false otherwise.
         * 
         * @api
         */
        public function isOpen() {
            return $this->open;
        }

        /**
         * 
         * Reads data from this reader following the 'scanf' parameter convention.
         * 
         * @param string $format The format string of the data to read
         * @return array An array of ordered values readed following the format provided.
         * 
         * @api
         */
        public function scanf($format) {
            $this->checkClosed();

            return fscanf($this->my_handle, $format);
        }

        /**
         * 
         * Reads n bytes from this stream.
         * 
         * @param int $length the number of bytes to read.
         * @return string|FALSE the readed string or FALSE on failure.
         * 
         * @api
         */
        public function read($length) {
            $this->checkClosed();

            return fread($this->my_handle, $length);
        }

        /**
         * 
         * Reads a line from this reader, ended by a CR or CRLF.
         * 
         * @return string the readed line.
         * @throws IOException if this reader is already closed.
         * 
         * @api
         */
        public function readLine() {
            $this->checkClosed();

            $line = "";

            do {

                $c = $this->nextChar();

                if ($c !== null && ord($c) !== 13 && ord($c) !== 10) {
                    $line.=$c;
                } else {
                    if ($c == null)
                        return strlen($line) === 0 ? null : $line;

                    if (ord($c) === 13) {
                        $nc = $this->nextChar();
                        if ($nc !== null && ord($nc) !== 10)
                            $this->ungetChar($nc);
                    }

                    return $line;
                }
            } while (true);
        }

        /**
         * 
         * Pushes a character back.
         * 
         * @param string $ch The caracter to push back.
         * @throws IOException If a character is already in the push back variable.
         * 
         * @internal
         */
        private function ungetChar($ch) {
            if ($this->ch !== null || $ch === null)
                throw new IOException("Can't unget more than one character.");
            $this->ch = $ch;
        }

        /**
         * 
         * Returns the next character readed from the current position. If a character
         * is set in the push back cache, return that character instead.
         * 
         * @return The next readed character.
         * 
         * @internal
         */
        private function nextChar() {
            if ($this->ch !== null) {
                $r = $this->ch;
                $this->ch = null;
                return $r;
            }
            if (feof($this->my_handle))
                return null;
            return fgetc($this->my_handle);
        }

        /**
         * 
         * Reads one character from this file reader.
         * 
         * @return string a string of one character readed from the stream.
         * @throws IOException If  the parameter is not a string of one character.
         * 
         * @api
         */
        public function readChar() {
            $this->checkClosed();

            return $this->nextChar();
        }
        
        /**
         * 
         * Checks if the parameter is a string of exactly one character.
         * 
         * @param string $ch The string to check
         * @param string $message message to use for exception
         * @return boolean true if the parameter is a character, false otherwise.
         * @throws IOException If  the parameter is not a string of one character.
         * 
         * @internal
         */
        protected function checkChar($ch,$message) {
            if (is_string($ch) && strlen($ch)===1) return true;
            else
                throw new IOException($message);
        }

        /**
         * 
         * Reads a line from this file as a CSV (comma separated value) entry.
         * 
         * @param string $delimiter The delimiter char used, defaults to ','.
         * @param string $enclosure The enclosure char used, defaults to '"'.
         * @param string $escape The escape char used, defaults to '\'.
         * @return array an array of ordered fields readed.
         * @throws IOException If the parameters are not valid or the entry contains unexpected characters.
         * 
         * @api
         */
        public function readCSV($delimiter = ",", $enclosure = '"', $escape = '\\') {
            $this->checkChar($delimiter, "The delimiter is not a valid character.");
            $this->checkChar($enclosure, "The enclosure is not a valid character.");
            $this->checkChar($escape, "The escape is not a valid character.");
            
            $this->checkClosed();

            $line = $this->readLine();

            if (strlen(trim($line)) === 0)
                return null;

            $fields = array();
            $current_field = "";
            $i = 0;
            $escaped = false;
            $e = 0;
            while ($i < strlen($line)) {
                $c = $line[$i++];
                //echo $c."\n";
                if ($escaped) {
                    $current_field .= $c;
                    $escaped = false;
                    continue;
                }
                if ($c === $escape) {
                    $escaped = true;
                    continue;
                }
                if (($c === $enclosure) && (strlen($current_field) === 0) && ($e === 0)) {
                    $e++;
                    //skip
                    continue;
                }
                if (($c === $enclosure) && ($e === 1)) {
                    $e++;
                    //skip
                    continue;
                }
                if (($c === $delimiter) && (($e === 2) || ($e === 0 && strlen($current_field) > 0))) {
                    $fields[] = $current_field;
                    $current_field = "";
                    $e = 0;
                    continue;
                }
                if ($e === 2)
                    throw new IOException("Error in CSV data format. Delimiter not found after enclosure.");
                $current_field .= $c;
            }

            $fields[] = $current_field;

            return $fields;
        }

        /**
         * 
         * Moves the read pointer at the initial position of the file, as calling seek(0).
         * 
         * @return boolean true if the operation succeded, false otherwise.
         * @throws IOException if this reader is already closed. 
         * 
         * @api
         */
        public function reset() {
            $this->checkClosed();

            return rewind($this->my_handle);
        }

        /**
         * 
         * Moves the read pointer at the specified location.
         * 
         * @param long $location The location at which point the reader.
         * @return boolean true if the operation succeded, false otherwise.
         * @throws IOException if this reader is already closed. 
         * 
         * @api
         */
        public function seek($location) {
            $this->checkClosed();

            return fseek($this->my_handle, $location, SEEK_SET)==0;
        }

        /**
         * 
         * Skip bytes from the current position of the stream.
         * 
         * @param long $offset The number of bytes to skip.
         * @throws IOException if this reader is already closed.
         * 
         * @api
         */
        public function skip($offset) {
            $this->checkClosed();

            fseek($this->my_handle, $offset, SEEK_CUR);
        }

        /**
         * 
         * Returns the current position inside the opened file.
         * 
         * @return long the byte index of the reader inside the file.
         * @throws IOException if this reader is already closed.
         * 
         * @api
         */
        public function pos() {
            $this->checkClosed();

            return ftell($this->my_handle);
        }

        /**
         * 
         * Checks if the stream is ended.
         * 
         * @return true if the end of the stream is reached, false otherwise.
         * @throws IOException if this reader is already closed.
         * 
         * @api
         */
        public function isEndOfStream() {
            $this->checkClosed();

            return feof($this->my_handle);
        }

        /**
         * 
         * Closes this file reader, releasing the locks acquired.
         * 
         * @throws IOException if this reader is already closed.
         * 
         * @api
         */
        public function close() {
            if ($this->open) {
                fflush($this->my_handle);
                flock($this->my_handle, LOCK_UN);
                fclose($this->my_handle);

                $this->open = false;
                $this->my_handle = null;
            } else
                throw new IOException("Reader/Writer already closed.");
        }


        /**
         * 
         * Returns the file handle of this file, as returned from the fopen call.
         * 
         * @return handle the file handle of this file.
         * 
         * @internal
         */
        private function getHandler() {
            return $this->my_handle;
        }

    }

}

?>