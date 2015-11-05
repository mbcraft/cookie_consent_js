<?php
/**
 * 
 * Author and Copyright : 
 * Marco Bagnaresi - MBCRAFT di Marco Bagnaresi
 * http://www.mbcraft.it
 * 
 * Version : 2.4.1
 * 
 * This file contains the File class definition.
 */
namespace Piol {

    /**
     * This class enable you to work with files, providing a lot of easy-to-use methods
     * for dealing with name, extension, temporary files, content and content hashing, 
     * as well as methods for moving, renaming and copying the file around.
     */
    class File extends __FileSystemElement {

        /**
         * Default path for temporary files, relative to PIOL_ROOT_PATH, is "/tmp/".
         */
        const DEFAULT_TMP_PATH = "/tmp/";

        /**
         *
         * @var string Contains the default temporary file path. 
         */
        private static $tmp_file_path = self::DEFAULT_TMP_PATH;

        /**
         * 
         * Sets the directory for creating temporary files as \Piol\File instances.
         * It is relative to PIOL_ROOT_PATH since it is converted to a valid \Piol\Dir instance.
         * Defaults to PIOL_ROOT_PATH."/tmp/".
         * 
         * @param \Piol\Dir|string $dir The directory as a string path or Dir instance used for creating temporary files.
         * @throws IOException If the directory does not exists or does not have read and write permissions.
         * 
         * @api
         */
        public static function setTmpFileDir($dir) {
            $d = Dir::asDir($dir);
            if ($d->exists() && $d->hasPermissions("rw-------")) {
                self::$tmp_file_path = $d->getPath();
            } else
                throw new IOException("Temporary dir must have read and write permissions.");
        }

        /**
         * 
         * Returns the current directory used for creating temporary files.
         * 
         * @return \Piol\Dir the directory for creating temporary files.
         * 
         * @api
         */
        public static function getTmpFileDir() {
            return Dir::asDir(self::$tmp_file_path);
        }

        /**
         * 
         * Construct a \Piol\File instance.
         * 
         * @param string $path The path of the file.
         * 
         * @api
         */
        public function __construct($path) {
            $new_path = str_replace("\\", DS, $path);

            parent::__construct($new_path);
        }

        /**
         * 
         * Ensures that a File object is used always returning a \Piol\File instance.
         * 
         * @param \Piol\File|string $file_or_path The string or path pointing to the file system file.
         * @return \Piol\File The \Piol\File instance.
         * @throws IOException if the specified parameter is not a valid path or \Piol\File instance.
         * 
         * @api
         */
        public static function asFile($file_or_path) {
            if ($file_or_path instanceof File)
                return $file_or_path;
            if (FileSystemUtils::isFile($file_or_path))
                return new File($file_or_path);
            throw new IOException("The specified parameter is not a valid file path or \Piol\File instance : " . $file_or_path);
        }

        /**
         * 
         * Returns the name of this file, witout any extension parts. (after dots).
         * 
         * @return string the name of this file.
         * 
         * @api
         */
        public function getName() {
            $result = pathinfo($this->__full_path);
            return $result['filename'];
        }

        /**
         * 
         * Renames this file. Rename does not allow moving the file.
         * 
         * @param string $new_name the new file name.
         * @return boolean true if the operation was succesfull, false otherwise
         * @throws IOException if the name contains invalid characters.
         * 
         * @api
         */
        public function rename($new_name) {
            FileSystemUtils::checkValidFilename($new_name);

            $this_dir = $this->getParentDir();

            $target_path = $this_dir->getPath() . DS . $new_name;

            $target_file = new File($target_path);
            if ($target_file->exists())
                return false;

            return rename($this->__full_path, $target_file->getFullPath());
        }

        /**
         * 
         * Returns the full file name without path components of this file.
         * eg. : "myfile.txt" or "phpdata.php.inc".
         *
         * @return string the full file name of this file.
         * 
         * @api
         */
        public function getFullName() {
            $result = pathinfo($this->__full_path);
            return $result['filename'] . "." . $result['extension'];
        }

        /**
         * 
         * Gets the last extension of this file.
         * 
         * Eg. : ".jpg" or ".inc".
         * 
         * @return string the last extension of this file.
         * 
         * @api
         */
        public function getLastExtension() {
            $result = pathinfo($this->__full_path);

            return $result['extension'];
        }

        /**
         * 
         * Returns the full extension of this file, from the first dot (included) to the end
         * of the file name.
         * 
         * @return string the full extension of this file, eg. ".php.inc".
         * 
         * @api
         */
        public function getFullExtension() {
            $filename = $this->getFullName();
            $matches = array();
            preg_match("/\.(.+)/", $filename, $matches);
            return $matches[1];
        }

        /**
         * 
         * Returns the content of this file.
         * 
         * @return string the full content of this file.
         * 
         * @api
         */
        public function getContent() {
            return file_get_contents($this->__full_path);
        }

        /**
         * 
         * Returns the sha1 hash of the content of this file.
         * 
         * @return string the sha1 hash content of this file.
         * 
         * @api
         */
        public function getContentHash() {
            return sha1_file($this->__full_path);
        }

        /**
         * 
         * Sets the content of this file, using an exclusive lock to prevent collisions.
         * 
         * @param mixed $content a string, an array or a stream resource.
         * @return int the number of bytes written into the file, or FALSE if an error occurred.
         * 
         * @api
         */
        public function setContent($content) {
            return file_put_contents($this->__full_path, $content, LOCK_EX);
        }

        /**
         * 
         * Returns the size of this file.
         * 
         * @return int the size of this file.
         * @throws IOException If this file doesn't exist.
         * 
         * @api
         */
        public function getSize() {
            if (!$this->exists())
                throw new IOException("The file does not exists!");

            clearstatcache(true, $this->__full_path);
            return filesize($this->__full_path);
        }

        /**
         * 
         * Deletes this file.
         * 
         * @return boolean if the delete operation was succesfull, false otherwise.
         * 
         * @api
         */
        public function delete() {
            return @unlink($this->__full_path);
        }

        /**
         * 
         * Returns true if this file is empty, false otherwise.
         * 
         * @return boolean true if this file is empty, false otherwise.
         * @throws IOException If this file doesn't exist.
         * 
         * @api
         */
        public function isEmpty() {
            if (!$this->exists())
                throw new IOException("The file does not exists!");

            return $this->getSize() == 0;
        }

        /**
         * 
         * Copies this file to the target folder.
         * 
         * @param \Piol\Dir|string $target_dir The target dir or string path to copy this file into.
         * @param string $new_name An optional new name for the copied file.
         * @return boolean true if this operation was succesfull, false otherwise.
         * 
         * @api
         */
        public function copy($target_dir, $new_name = null) {
            $t_dir = Dir::asDir($target_dir);
            if ($new_name == null)
                $n_name = $this->getFullName();
            else {
                FileSystemUtils::checkValidFilename($new_name);
                $n_name = $new_name;
            }
            return copy($this->__full_path, $t_dir->__full_path . DS . $n_name);
        }

        /**
         * 
         * Matches the filename of this File with the given pattern.
         * 
         * @param string $pattern A regexp pattern to match this filename
         * @return boolean true is the pattern matches, false otherwise.
         * 
         * @api
         */
        public function filenameMatches($pattern) {
            return preg_match($pattern, $this->getFullName()) != 0;
        }

        /**
         * 
          * Touches this file : if it does not exist it is created, if it already exists
         * its last access time is updated.
         * 
         * @api
         */
        public function touch() {
            touch($this->__full_path);
        }

        /**
         * 
         * Opens a FileReader for this file. 
         * @see \Piol\FileReader
         * 
         * @return \Piol\FileReader|null the opened FileReader, or null if a shared lock cannot be obtained for this file.
         * @throws IOException If this file doesn't exist or can't be opened.
         * 
         * @api
         */
        public function openReader() {
            if (!$this->exists())
                throw new IOException("Impossibile aprire il reader al percorso : " . $this->__full_path . ". Il file non esiste!!");

            $handle = fopen($this->__full_path, "r");

            if ($handle === false)
                throw new IOException("Impossibile aprire il reader al percorso : " . $this->__full_path . ".");

            if (flock($handle, LOCK_SH)) {
                return new FileReader($handle);
            } else {
                fclose($this->my_handle);
                return null;
            }
        }
        
        /**
         * 
         * Opens a FileWriter for this file used for logging. 
         * If the file does not exists, it is created.
         * The writer is opened at the end of the file.
         * @see \Piol\FileWriter for more informations.
         * 
         * @return \Piol\FileWriter|null The FileWriter, or null if it's not possible to lock this file for writing.
         * @throws IOException if it's not possible to open this file.
         * 
         * @api
         */
        public function openLogWriter() {
            $handle = fopen($this->__full_path, "a+");

            if ($handle === false)
                throw new IOException("Impossibile aprire il writer al percorso : " . $this->__full_path);

            if (flock($handle, LOCK_EX | LOCK_NB)) {
                return new FileWriter($handle);
            } else {
                fclose($this->my_handle);
                return null;
            }
        }

        /**
         * 
         * Opens a FileWriter for this file. If the file does not exists, it is created.
         * @see \Piol\FileWriter for more informations.
         * 
         * @return \Piol\FileWriter|null The FileWriter, or null if it's not possible to lock this file for writing.
         * @throws IOException if it's not possible to open this file.
         * 
         * @api
         */
        public function openWriter() {
            $handle = fopen($this->__full_path, "c+");

            if ($handle === false)
                throw new IOException("Impossibile aprire il writer al percorso : " . $this->__full_path);

            if (flock($handle, LOCK_EX)) {
                return new FileWriter($handle);
            } else {
                fclose($this->my_handle);
                return null;
            }
        }

        /**
         * 
         * Returns a valid path for an include or require directive for this element.
         * 
         * @return string a valid include or require path for this element.
         * 
         * @internal
         */
        private function getIncludePath() {
            $my_path = $this->getPath();

            $included_file_path = substr($my_path, 1);

            return $included_file_path;
        }

        /**
         * 
         * Includes the content of this file.
         * 
         * @return mixed The result of the include operation from the php directive 'include'.
         * 
         * @api
         */
        public function includeFile() {
            return include($this->getIncludePath());
        }

        /**
         * 
         * Includes the content of this file (using the 'include_once' php directive).
         * 
         * @return mixed the result of the 'include_once' directive.
         * 
         * @api
         */
        public function includeFileOnce() {
            return include_once($this->getIncludePath());
        }

        /**
         * 
         * Requires the content of this file once (using the 'require_once' php directive).
         * 
         * @return mixed the result of the 'require_once' directive.
         * 
         * @api
         */
        public function requireFileOnce() {
            $my_path = $this->getPath();

            $included_file_path = substr($my_path, 1);

            return require_once($included_file_path);
        }

        /**
         * 
         * Creates a new temporary file.
         * 
         * @return \Piol\File The new temporary file instance.
         * 
         * @api
         */
        public static function newTempFile() {
            $full_path = tempnam(Dir::asDir(self::$tmp_file_path)->getFullPath(), "tmp_");
            return new File(File::toRelativePath($full_path));
        }

        /**
         * 
         * Returns an associative array of data about this file. The following fields are returned :
         * full_path, path, name, extension, full_extension, type, mime_type, 
         * size, size_auto, permissions.
         * 
         * @return array an associative array of data about this file.
         * 
         * @api
         */
        public function getInfo() {
            $result = array();

            $result["full_path"] = $this->getFullPath();
            $result["path"] = $this->getPath();
            $result["name"] = $this->getName();
            $result["extension"] = $this->getLastExtension();
            $result["full_extension"] = $this->getFullExtension();
            $result["type"] = "file";
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $result["mime_type"] = finfo_file($finfo, $this->getFullPath());
            finfo_close($finfo);
            $size = $this->getSize();

            $result["size"] = $size;

            $result["size_auto"] = $size . " bytes";
            if ($size > 1024 * 2)
                $result["size_auto"] = ($size / 1024) . " KB";
            if ($size > (1024 ^ 2) * 2)
                $result["size_auto"] = ($size / (1024 ^ 2)) . " MB";
            if ($size > ((1024 ^ 3) * 2))
                $result["size_auto"] = ($size / (1024 ^ 3)) . " GB";
            $result["permissions"] = $this->getPermissions();

            return $result;
        }

        /**
         * 
         * Sets the permissions of this file. This operation is not guaranteed to succeed.
         * 
         * @param string $rwx_permissions The 9 character [rwx-] permission string to set.
         * @return boolean if the operation was succesfull, false otherwise.
         * 
         * @api
         */
        public function setPermissions($rwx_permissions) {
            return $this->setPermissionsRwx($rwx_permissions);
        }

        /**
         * 
         * Checks if this element has the listed permissions.
         * 
         * @param string $rwx_permissions The permissions to check.
         * @return boolean true if this file has all the permissions listed, false otherwise.
         * 
         * @api
         */
        public function hasPermissions($rwx_permissions) {
            return $this->hasPermissionsRwx($rwx_permissions);
        }

        /**
         * 
         * Returns the permissions of this file as a 9 character [rwx-] string.
         * @return string the permission string of this file.
         * 
         * @api
         */
        public function getPermissions() {
            return $this->getPermissionsRwx();
        }

    }

}

?>