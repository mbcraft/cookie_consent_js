<?php
/**
 * 
 * Author and Copyright : 
 * Marco Bagnaresi - MBCRAFT di Marco Bagnaresi
 * http://www.mbcraft.it
 * 
 * Version : 2.4.1
 * 
 * This file contains the Dir class definition.
 */
namespace Piol {

    /**
     * This class enable you to easily work with directories. It contains a lot of easy to use methods
     * for dealing with directories and its content (file listing) as well as methods for copying, moving 
     * and deleting content, as well as methods for checking parent and ancestor dirs.
     */
    class Dir extends __FileSystemElement {

        /**
         * Default exclude mode for list methods is "no hidden files".
         * 
         *
         */
        const DEFAULT_EXCLUDES = "NO_HIDDEN_FILES";
        /**
         * Mode for excluding hidden files for list methods.
         */
        const NO_HIDDEN_FILES = "NO_HIDDEN_FILES";
        /**
         * Pattern used for excluding hidden files.
         * 
         * @internal
         */
        const NO_HIDDEN_FILES_PATTERN = "/\A\..*\Z/";
        /**
         * Mode for showing hidden files for list methods.
         */
        const SHOW_HIDDEN_FILES = "SHOW_HIDDEN_FILES";
        /**
         * Pattern used for showing hidden files.
         * 
         * @internal
         */
        const SHOW_HIDDEN_FILES_PATTERN = "/\A[\.][\.]?\Z/";
        
        /**
         * Constant value used as a parameter for list/find methods to list only files.
         */
        const MODE_FILES_ONLY = 0x01;
        /**
         * Constant value used as a parameter for list/find methods to list only directories.
         */
        const MODE_FOLDERS_ONLY = 0x02;
        /**
         * Constant value used as a parameter for list/find methods to list both files and directories.
         */
        const MODE_FILES_AND_FOLDERS = 0x03;

        /**
         * 
         * Construct a directory instance, using a path relative to PIOL_ROOT_PATH.
         * 
         * @param string $path the path of this directory, relative to PIOL_ROOT_PATH as a root.
         * 
         * @api
         */
        public function __construct($path) {
            if ($path == "")
                $new_path = DS;
            //replace \ with /
            $new_path = str_replace("\\", DS, $path);

            if (substr($new_path, strlen($new_path) - 1, 1) != DS)
                $new_path = $new_path . DS;

            parent::__construct($new_path);
        }

        /**
         * 
         * Returns a valid \Piol\Dir instance.
         * 
         * @param \Piol\Dir|string $dir_or_path a dir instance or string path to convert
         * @return \Piol\Dir a valid dir instance
         * @throws IOException if a valid dir instance or a valid directory path is not provided as a parameter.
         * 
         * @api
         */
        public static function asDir($dir_or_path) {
            if ($dir_or_path instanceof Dir)
                return $dir_or_path;
            if (FileSystemUtils::isDir($dir_or_path))
                return new Dir($dir_or_path);
            throw new IOException("The specified parameter is not a valid directory path or Dir instance.");
        }

        /**
         * 
         * Visits this directory and all its subfolders using the visitor pattern.
         * The parameter should be an object of a class with a public 'visit' function, accepting a \Piol\Dir instance
         * as the only parameter. The function should return a boolean value, where true means 'visit my subfolders' and false means 'does not visit my subfolders'.
         * 
         * @param object $visitor the visitor instance.
         * 
         * @api
         */
        public function visit($visitor) {
            $cont = $visitor->visit($this);

            if ($cont) {
                $all_folders = $this->listFolders();

                foreach ($all_folders as $fold) {
                    $fold->visit($visitor);
                }
            }
        }

        /**
         * 
         * Returns the level of this directory, where level is the number of folders separating it
         * from the PIOL_ROOT_PATH.
         * Eg. :
         * / --> 0
         * /test/ --> 1
         * /test/js/mooo/ --> 3
         * 
         * @return int the level of this directory (deepness).
         * 
         * @api
         */
        public function getLevel() {
            preg_match_all("/\//", $this->__path, $matches);
            return count($matches[0]) - 1;
        }

        /**
         * 
         * Creates this directory if it doesn't exists, or updates the last access time
         * if it already exists.
         * 
         * @api
         */
        public function touch() {
            if (!$this->exists()) {
                @mkdir($this->__full_path, self::getDefaultPermissionsOctal(), true);
            } else
                touch($this->__full_path);
        }

        /**
         * 
         * Renames this directory without moving it.
         * 
         * @param string $new_name The new name to assign to this folder.
         * @return boolean true if the operation was succesfull, false otherwise.
         * @throws IOException if the name contains invalid characters (path components).
         * 
         * @api
         */
        public function rename($new_name) {
            
            FileSystemUtils::checkValidFilename($new_name);

            $parent_dir = $this->getParentDir();

            $target_path = $parent_dir->getPath() . "/" . $new_name;

            $target_dir = new Dir($target_path);
            if ($target_dir->exists())
                return false;

            return rename($this->__full_path, $target_dir->getFullPath());
        }
        
        /**
         * 
         * Returns the name of this directory, without other path components.
         * Same as getName().
         * 
         * @return string the name of this directory.
         * 
         * @api
         */
        public function getFullName() {
            return $this->getName();
        }

        /**
         * 
         * Returns the name of this directory, without other path components.
         * 
         * @return string the name of this directory.
         * 
         * @api
         */
        public function getName() {

            return basename($this->__full_path);
        }

        /**
         * 
         * Checks if this directory is ancestor or same of the specified parameter.
         * 
         * @param \Piol\Dir|string $subdir a directory path or Dir instance.
         * @return boolean true if the parameter is an ancestor or points to the same directory of this instance, false otherwise.
         * @throws IOException If the parameter is not a valid directory inside PIOL_ROOT_PATH.
         * 
         * @api
         */
        public function isAncestorOrSame($subdir) {

            $d = self::asDir($subdir);

            while (strlen($d->getFullPath()) >= strlen($this->getFullPath())) {

                if ($this->equals($subdir))
                    return true;
                else
                    return $this->isAncestorOrSame($d->getParentDir());
            }
            return false;
        }

        /**
         * 
         * Creates a new random subdirectory inside this one, returning it as a Dir instance
         * pointing to it. 
         * 
         * @return \Piol\Dir the created subdirectory.
         * @throws IOException If the specified name is already used for another kind of file system element (it should never happen) or it's not possible to create the directory.
         * 
         * @api
         */
        public function newRandomSubdir() {
            return $this->newSubdir($this->randomHexName());
        }

        /**
         * 
         * Creates a new subdirectory inside this one, or returns an existing one, 
         * returning a \Piol\Dir instance pointing to it.
         * 
         * @param string $name The name of the subdirectory to create.
         * @return \Piol\Dir The existing subdirectory.
         * @throws IOException If the specified name is already used for another kind of file system element or it's not possible to create the directory.
         * 
         * @api
         */
        public function newSubdir($name) {
            FileSystemUtils::checkValidFilename($name);
            
            if (FileSystemUtils::isDir($this->__path . DS . $name)) {
                //directory already exists
                //echo "Directory already exists : ".$this->__full_path."/".$name;
                return new Dir($this->__path . DS . $name);
            }
            if (FileSystemUtils::isFile($this->__path . DS . $name)) {
                throw new IOException();
            }
            //directory or files do not exists

            $result = @mkdir($this->__full_path . $name, __FileSystemElement::getDefaultPermissionsOctal(), true);

            if ($result == true)
                return new Dir($this->__path . $name);
            else {
                throw new IOException("Unable to create dir : " . $this->__full_path . $name);
            }
        }

        /**
         * 
         * Checks if this directory is empty or has nested elements inside.
         * 
         * @return boolean true if this directory is empty, false otherwise.
         * @throws IOException If the path does not point to an existing directory.
         * 
         * @api
         */
        public function isEmpty() {
            if (!$this->exists())
                throw new IOException("This instance does not point to an existing directory.");

            $results = $this->listElements();
            return count(array_merge($results[0], $results[1])) === 0;
        }

        /**
         * 
         * List all the files inside this directory, excluding the ones that matches at least one of the specified patterns.
         * 
         * @param array $myExcludes an array of regexp patterns used to match excluded elements against their full name.
         * @param bool $deep if true searches inside all the contained folders, otherwise only in this one.
         * @return array an array of two arrays, with all the files that matched and all the directories that matched, 
         * as a \Piol\File and \Piol\Dir instances.
         * 
         * @api
         */
        public function listFiles($myExcludes = self::DEFAULT_EXCLUDES, $deep = false) {
            $results = $this->listElements(self::MODE_FILES_ONLY, $myExcludes, $deep);
            return $results[0];
        }

        /**
         * 
         * List all the folders inside this directory, excluding the ones that matches at least one of the specified patterns.
         * 
         * @param array $myExcludes an array of regexp patterns used to match excluded elements against their full name.
         * @param bool $deep if true searches inside all the contained folders, otherwise only in this one.
         * @return array an array of two arrays, with all the files that matched and all the directories that matched, 
         * as a \Piol\File and \Piol\Dir instances.
         * 
         * @api
         */
        public function listFolders($myExcludes = self::DEFAULT_EXCLUDES, $deep = false) {
            $results = $this->listElements(self::MODE_FOLDERS_ONLY, $myExcludes, $deep);
            return $results[1];
        }

        /**
         * 
         * List all the elements inside this directory, excluding the ones that matches at least one of the specified patterns.
         * 
         * @param int $mode Only the following values, specified as constants inside this class, can be used :
         *  MODE_FILES_ONLY, MODE_FOLDERS_ONLY, MODE_FILES_AND_FOLDERS.
         * @param array $myExcludes an array of regexp patterns used to match excluded elements against their full name.
         * @param bool $deep if true searches inside all the contained folders, otherwise only in this one.
         * @return type array an array of two arrays, with all the files that matched and all the directories that matched, 
         * as a \Piol\File and \Piol\Dir instances.
         * 
         * @api
         */
        public function listElements($mode = self::MODE_FILES_AND_FOLDERS, $myExcludes = self::DEFAULT_EXCLUDES, $deep = false) {
            $excludesSet = false;

            if (!$excludesSet && $myExcludes === self::NO_HIDDEN_FILES) {
                $excludesSet = true;
                $excludes = array(self::NO_HIDDEN_FILES_PATTERN);
            }

            if (!$excludesSet && $myExcludes === self::SHOW_HIDDEN_FILES) {
                $excludesSet = true;
                $excludes = array(self::SHOW_HIDDEN_FILES_PATTERN);
            }
            if (!$excludesSet)
                $excludes = $myExcludes;

            $all_results = scandir($this->__full_path);

            $all_files = array();
            $all_dirs = array();

            foreach ($all_results as $element) {

                if ($element == "." || $element == "..")    //always skip . and ..
                    continue;

                $skip = false;
                foreach ($excludes as $pt) {
                    if (preg_match($pt, $element)) {
                        $skip = true;
                    }
                }

                $partial_path = $this->__path . $element;
                //è da saltare?
                if (!$skip) {

                    if (($mode & self::MODE_FOLDERS_ONLY) === self::MODE_FOLDERS_ONLY && FileSystemUtils::isDir($partial_path)) {
                        $all_dirs[] = new Dir($partial_path);
                    } else
                    if (($mode & self::MODE_FILES_ONLY) === self::MODE_FILES_ONLY && FileSystemUtils::isFile($partial_path)) {
                        $all_files[] = new File($partial_path);
                        continue;
                    }
                }

                if ($deep && FileSystemUtils::isDir($partial_path)) {
                    $d = new Dir($partial_path);
                    $partial_results = $d->listElements($mode, $myExcludes, true);
                    $all_files = array_merge($all_files, $partial_results[0]);
                    $all_dirs = array_merge($all_dirs, $partial_results[1]);
                }
            }

            return array($all_files, $all_dirs);
        }

        /**
         * 
         * Find all elements starting with the specified string.
         * 
         * @param int $mode Only the following values, specified as constants inside this class, can be used :
         *  MODE_FILES_ONLY, MODE_FOLDERS_ONLY, MODE_FILES_AND_FOLDERS.
         * @param string $string the string used for comparison with the starting part of the full name of the
         * nested elements.
         * @param bool $deep if true searches inside all the contained folders, otherwise only in this one.
         * @return type array an array of two arrays, with all the files that matched and all the directories that matched, 
         * as a \Piol\File and \Piol\Dir instances.
         * 
         * @api
         */
        public function findElementsStartingWith($mode, $string, $deep = false) {
            $escaped = $this->escapePatternCharacters($string . "*");
            return $this->findMatchingElements($mode, "/\A" . $escaped . "/", $deep);
        }

        /**
         * 
         * Find all elements ending with the specified string.
         * 
         * @param int $mode Only the following values, specified as constants inside this class, can be used :
         *  MODE_FILES_ONLY, MODE_FOLDERS_ONLY, MODE_FILES_AND_FOLDERS.
         * @param string $string the string used for comparison with the ending part of the full names of the 
         * nested elements.
         * @param boolean $deep if true searches inside all the contained folders, otherwise only in this one.
         * @return array an array of two arrays, with all the files that matched and all the directories that matched, 
         * as a \Piol\File and \Piol\Dir instances.
         * 
         * @api
         */
        public function findElementsEndingWith($mode, $string, $deep = false) {
            $escaped = $this->escapePatternCharacters("*" . $string);
            return $this->findMatchingElements($mode, "/" . $escaped . "\Z/", $deep);
        }

        /**
         * 
         * Escapes a pattern character for easy start/end matching.
         * 
         * @param string $string an easy pattern string
         * @return string the escaped pattern 
         * 
         * @internal
         */
        private function escapePatternCharacters($string) {
            $escaped = str_replace('\\', '\\\\', $string);
            $escaped = str_replace(".", "\.", $escaped);
            $escaped = str_replace("[", "\[", $escaped);
            $escaped = str_replace("]", "\]", $escaped);
            $escaped = str_replace("{", "\{", $escaped);
            $escaped = str_replace("}", "\}", $escaped);
            $escaped = str_replace("-", "\-", $escaped);
            $escaped = str_replace("_", "\_", $escaped);
            $escaped = str_replace("+", "\+", $escaped);
            $escaped = str_replace("*", ".*", $escaped);
            $escaped = str_replace("?", ".?", $escaped);
            return $escaped;
        }

        /**
         * 
         * Finds all matching elements inside this folder.
         * 
         * @param int $mode Only the following values, specified as constants inside this class, can be used :
         *  MODE_FILES_ONLY, MODE_FOLDERS_ONLY, MODE_FILES_AND_FOLDERS.
         * @param array $myIncludes an array of regexp pattern of the elements to match using their full names.
         * @param boolean $deep if true the search is done recursively on all subfolders, otherwise only inside this one.
         * @return array an array of two arrays, with all the files that matched and all the directories that matched, 
         * as a \Piol\File and \Piol\Dir instances.
         * 
         * @api
         */
        public function findMatchingElements($mode, $myIncludes, $deep = false) {

            if (is_array($myIncludes))
                $includes = $myIncludes;
            else
                $includes = array($myIncludes);

            $all_results = scandir($this->__full_path);

            $all_files = array();
            $all_dirs = array();

            foreach ($all_results as $element) {

                if ($element == "." || $element == "..")    //always skip . and ..
                    continue;

                $include = false;
                $done = false;
                foreach ($includes as $pt) {
                    if (!$done && preg_match($pt, $element)) {
                        $include = true;
                        $done = true;
                    }
                }

                $partial_path = $this->__path . $element;

                //è da aggiungere?
                if ($include) {

                    if (($mode & self::MODE_FILES_ONLY) === self::MODE_FILES_ONLY && FileSystemUtils::isFile($partial_path)) {
                        $all_files[] = new File($partial_path);

                        continue;
                    }

                    if (($mode & self::MODE_FOLDERS_ONLY) === self::MODE_FOLDERS_ONLY && FileSystemUtils::isDir($partial_path)) {
                        $all_dirs[] = new Dir($partial_path);
                    }
                }

                if ($deep && FileSystemUtils::isDir($partial_path)) {
                    $d = new Dir($partial_path);
                    $partial_results = $d->findMatchingElements($mode, $myIncludes, true);
                    $all_files = array_merge($all_files, $partial_results[0]);
                    $all_dirs = array_merge($all_dirs, $partial_results[1]);
                }
            }

            return array($all_files, $all_dirs);
        }

        /**
         * 
         * Creates a new File instance inside this directory.
         * 
         * @param string $name the name for this file.
         * @return \Piol\File The file instance.
         * 
         * @api
         */
        public function newFile($name) {
            FileSystemUtils::checkValidFilename($name);
            
            return new File($this->__path . DS . $name);
        }

        /**
         * 
         * Deletes this folder.
         * 
         * @return boolean if the delete operation was fully successfull, false otherwise.
         * 
         * @api
         */
        public function delete() {

            $results = $this->listElements(Dir::MODE_FILES_AND_FOLDERS, Dir::SHOW_HIDDEN_FILES);
            $all_content = array_merge($results[0], $results[1]);
            foreach ($all_content as $elem) {
                $elem->delete();
            }

            return @rmdir($this->__full_path);
        }

        /**
         * 
         * Checks if this directory has exactly only one subdirectory (and no files).
         * 
         * @return boolean true if this folder contains exactly one file and nothing else, false otherwise.
         * 
         * @api
         */
        public function hasOnlyOneSubdir() {
            $content = $this->listElements();
            if (count($content[0]) == 0 && count($content[1]) == 1) {
                $dir_elem = $content[1][0];
                if ($dir_elem->isDir())
                    return true;
            }
            return false;
        }

        /**
         * 
         * Gives this directory a new random name.
         * 
         * @return boolean if the operation was succesfull, false otherwise.
         * 
         * @api
         */
        public function randomRename() {
            return $this->rename($this->randomHexName());
        }

        /**
         * 
         * Returns the only subfolder inside this directory, as a \Piol\Dir instance.
         * 
         * @return \Piol\Dir the unique subdirectory found inside this one.
         * @throws IOException If there is not exactly one subdirectory inside this one.
         * 
         * @api
         */
        public function getUniqueSubdir() {
            $content = $this->listElements();
            if (count($content[0]) == 0 && count($content[1]) == 1) {
                $dir_elem = $content[1][0];
                if ($dir_elem->isDir())
                    return $dir_elem;
                throw new IOException("L'elemento presente all'interno della directory non è una cartella.");
            }
            throw new IOException("Errore nell'esecuzione del metodo getUniqueSubdir. Numero elementi:" . count($content));
        }

        /**
         * 
         * Returns true if this folder has AT LEAST ONE subfolder.
         * 
         * @return boolean true if it has at least one subfolder, false otherwise.
         * 
         * @api
         */
        public function hasSubdirs() {
            $content = $this->listFolders();
            foreach ($content as $f) {
                if ($f->isDir())
                    return true;
            }
            return false;
        }

        /**
         * 
         * Copies this directory and all its content to a new path.
         * 
         * @param \Piol\Dir|string $path the path where to copy this folder and all its content.
         * @param string $new_name An aoptional new name for the copied folder.
         * 
         * @api
         */
        public function copy($path, $new_name = null) {
            $target_dir = Dir::asDir($path);

            if ($new_name == null)
                $new_name = $this->getName();
            else
                FileSystemUtils::checkValidFilename($new_name);

            $copy_dir = $target_dir->newSubdir($new_name);

            $results = $this->listElements(Dir::MODE_FILES_AND_FOLDERS);
            $all_elements = array_merge($results[0], $results[1]);
            foreach ($all_elements as $elem) {
                $elem->copy($copy_dir);
            }
            
        }

        /**
         * 
         * Checks if this directory is a parent of the provided child.
         * 
         * @param \Piol\File|\Piol\Dir $child
         * @return boolean true if the parameter is a direct child of this directory, false otherwise.
         * 
         * @api
         */
        public function isParentOf($child) {
            if (!($child instanceof __FileSystemElement))
                throw new IOException("The provided element is not a valid \Piol\File or \Piol\Dir.");

            $path_p = $this->getFullPath();
            $path_c = $child->getFullPath();

            //for windows dirs
            $parent_path_c = dirname($path_c) . DIRECTORY_SEPARATOR;
            $parent_path_c = str_replace("\\", DS, $parent_path_c);

            return $parent_path_c == $path_p;
        }

        /**
         * 
         * Returns an associative array of data about this directory. The following fields are set :
         * full_path, path, name, type, empty, permissions.
         * 
         * @return array an associative array of data about this directory.
         * 
         * @api
         */
        public function getInfo() {
            $result = array();

            $result["full_path"] = $this->getFullPath();
            $result["path"] = $this->getPath();
            $result["name"] = $this->getName();
            $result["type"] = "dir";
            $result["empty"] = $this->isEmpty();
            $result["permissions"] = $this->getPermissions();

            return $result;
        }

        /**
         * 
         * Sets the permissions on this directory using a 9 [rwx-] character string.
         * This operation is not guaranteed to succeed.
         * 
         * @param string $rwx_permissions The string permissions to set for this directory. 
         * @param boolean $recursive defaults to false, sets the permissions to all the child file system elements.
         * @return boolean true if the operation was succesfull, false otherwise.
         * 
         * @api
         */
        public function setPermissions($rwx_permissions, $recursive = false) {
            $r = $this->setPermissionsRwx($rwx_permissions);

            if ($recursive) {
                foreach ($this->listFolders() as $folder) {
                    $rr = Dir::asDir($folder)->setPermissions($rwx_permissions, $recursive);
                    $r = $r && $r;
                }

                foreach ($this->listFiles() as $file) {
                    $rr = File::asFile($file)->setPermissionsRwx($rwx_permissions);
                    $r = $r && $r;
                }
            }

            return $r;
        }

        /**
         * 
         * Checks if this directory has all the permissions specified in the parameter string.
         * 
         * @param string $rwx_permissions The 9 [rwx-] character string permissions to check.
         * @return boolean true if all the permissions checked are set for this directory, false otherwise.
         * 
         * @api
         */
        public function hasPermissions($rwx_permissions) {
            return $this->hasPermissionsRwx($rwx_permissions);
        }

        /**
         * 
         * Returns the permissions for this Directory as a 9 [rwx-] character string.
         * 
         * @return string the permissions for this directory.
         * 
         * @api
         */
        public function getPermissions() {
            return $this->getPermissionsRwx();
        }

        /**
         * 
         * Returns a random hex name.
         * 
         * @return string the random hex name.
         * 
         * @internal
         */
        private function randomHexName() {
            return dechex(rand(100000000, 9999999999));
        }
        
    }

}

?>