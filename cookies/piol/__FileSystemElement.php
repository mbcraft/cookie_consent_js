<?php
/**
 * 
 * Author and Copyright : 
 * Marco Bagnaresi - MBCRAFT di Marco Bagnaresi
 * http://www.mbcraft.it
 * 
 * Version : 2.4.1
 * 
 * This file contains the __FileSystemElement class definition, as well as some
 * constant definitions used in the whole library.
 */
namespace Piol {

    require_once("FileSystemUtils.php");

    if (!defined("DS"))
        define("DS", "/");
    
    if (!defined("DOT"))
        define("DOT", ".");
    

    /**
     *  
     * This class model a generic file system element. It is used as parent class
     * for File and Dir classes. It has a lot of common logic for dealing with path, permissions
     * and other file system elements metadata. 
     * It also has methods for automatically work with file system elements storage related data.
     * 
     * @internal
     */
    abstract class __FileSystemElement extends PiolObject {

        /**
         * 
         * @var string This variable contains the full absolute path of this element (file or directory)
         * 
         * @internal
         */
        protected $__full_path;
        /**
         * 
         * 
         * @var string This variable contains the relative path of this element (file or directory)
         * 
         * @internal
         */
        protected $__path;
        
        /**
         * 
         * @var string This variable contains the 9 [rwx-] character string for this element (file or directory).
         * 
         * @internal 
         */
        private static $defaultPermissionsRwx = "rwxrwxrwx";

        /**
         * Transform the provided rwx- permission string to the octal value.
         * 
         * @param string $rwx_permissions The permission string.
         * @return int the octal value for the permission string.
         * 
         * @api
         */
        public static function toOctalPermissions($rwx_permissions) {
            $mode = 0;
            if ($rwx_permissions[0] == 'r')
                $mode += 0400;
            if ($rwx_permissions[1] == 'w')
                $mode += 0200;
            if ($rwx_permissions[2] == 'x')
                $mode += 0100;
            else if ($rwx_permissions[2] == 's')
                $mode += 04100;
            else if ($rwx_permissions[2] == 'S')
                $mode += 04000;

            if ($rwx_permissions[3] == 'r')
                $mode += 040;
            if ($rwx_permissions[4] == 'w')
                $mode += 020;
            if ($rwx_permissions[5] == 'x')
                $mode += 010;
            else if ($rwx_permissions[5] == 's')
                $mode += 02010;
            else if ($rwx_permissions[5] == 'S')
                $mode += 02000;

            if ($rwx_permissions[6] == 'r')
                $mode += 04;
            if ($rwx_permissions[7] == 'w')
                $mode += 02;
            if ($rwx_permissions[8] == 'x')
                $mode += 01;
            else if ($rwx_permissions[8] == 't')
                $mode += 01001;
            else if ($rwx_permissions[8] == 'T')
                $mode += 01000;

            return $mode;
        }

        /**
         * Transform the provided octal permission value to the rwx- format.
         * 
         * @param int $octal_permissions The octal permission value.
         * @return string the rwx permission string, as a 9 [rwx-] character string.
         * 
         * @api
         */
        public static function toRwxPermissions($octal_permissions) {
            $info = "";

            // Owner
            $info .= (($octal_permissions & 0x0100) ? 'r' : '-');
            $info .= (($octal_permissions & 0x0080) ? 'w' : '-');
            $info .= (($octal_permissions & 0x0040) ?
                            (($octal_permissions & 0x0800) ? 's' : 'x' ) :
                            (($octal_permissions & 0x0800) ? 'S' : '-'));

            // Group
            $info .= (($octal_permissions & 0x0020) ? 'r' : '-');
            $info .= (($octal_permissions & 0x0010) ? 'w' : '-');
            $info .= (($octal_permissions & 0x0008) ?
                            (($octal_permissions & 0x0400) ? 's' : 'x' ) :
                            (($octal_permissions & 0x0400) ? 'S' : '-'));

            // World
            $info .= (($octal_permissions & 0x0004) ? 'r' : '-');
            $info .= (($octal_permissions & 0x0002) ? 'w' : '-');
            $info .= (($octal_permissions & 0x0001) ?
                            (($octal_permissions & 0x0200) ? 't' : 'x' ) :
                            (($octal_permissions & 0x0200) ? 'T' : '-'));

            return $info;
        }

        /**
         * 
         * 
         * Sets the default permissions when creating new elements as an octal value.
         * 
         * @param int $perms The permissions octal.
         * 
         * @api
         */
        public static function setDefaultPermissionsOctal($perms) {
            self::$defaultPermissionsRwx = self::toRwxPermissions($perms);
        }

        /**
         * 
         * Returns the default permissions used when creating new elements as an octet string.
         * 
         * @return int The full octal value for the permissions.
         * 
         * @api
         */
        public static function getDefaultPermissionsOctal() {
            return self::toOctalPermissions(self::$defaultPermissionsRwx);
        }

        /**
         * 
         * Sets the default rwx permissions used when creating new elements.
         * 
         * @param string $perms The 9 character [rwx-] permissions string.
         * 
         * @api
         */
        public static function setDefaultPermissionsRwx($perms) {
            self::$defaultPermissionsRwx = $perms;
        }

        /**
         * 
         * Returns the default rwx permissions used when creating new elements.
         * Defaults to all permissions enabled.
         * 
         * @return string the rwx permissions string.
         * 
         * @api
         */
        public static function getDefaultPermissionsRwx() {
            return self::$defaultPermissionsRwx;
        }

        /**
         * 
         * Construct a new __FileSystemElement. This element has all the path management and is
         * always relative to the PIOL_ROOT_PATH.
         * @param string $path The string path.
         * @throws IOException If something goes wrong.
         * 
         * @internal
         */
        protected function __construct($path) {

            FileSystemUtils::checkPiolRootPath();
            //SAFETY NET, rimuovo tutti i .. all'interno del percorso.
            $path = str_replace(DS . "..", "", $path);
            //pulizia doppie barre dai percorsi
            $path = str_replace("//", DS, $path);

            if (strpos($path, PIOL_ROOT_PATH) === 0) {
                throw new IOException("Errore : path contenente la root del sito.");
            } else
                $fp = PIOL_ROOT_PATH . $path;

            //DISABILITATO, IL LINK DEL FRAMEWORK CREA GROSSI PROBLEMI
            //non posso determinare il path se esco dal PIOL_ROOT_PATH (è ovvio)
            //la chiamata a realpath potrebbe far cambiare sia la seconda parte dell'url che la prima parte
            //IN QUESTO MODO CREO UNA JAIL ALL'INTERNO DEL SITO.  
            /*
              if (file_exists($fp))
              {
              $fp = realpath ($fp);
              if (strpos($path,PIOL_ROOT_PATH)!==0)
              $fp = PIOL_ROOT_PATH;
              }

             */

            $this->__full_path = $fp;
            $this->__full_path = str_replace(DIRECTORY_SEPARATOR, DS, $this->__full_path);

            $this->__path = substr($fp, strlen(PIOL_ROOT_PATH), strlen($fp) - strlen(PIOL_ROOT_PATH));
            $this->__path = str_replace(DIRECTORY_SEPARATOR, DS, $this->__path);
        }

        /**
         * 
         * Checks if this element is equal to another provided element : it has the same class and points to the same path.
         * 
         * @param \Piol\File|\Piol\Dir $file_or_dir The File or Dir instance to check.
         * @return boolean true if the provided object is of the same class and points to the same path, false otherwise.
         * 
         * @api
         */
        public function equals($file_or_dir) {
            if ($file_or_dir instanceof __FileSystemElement)
                return (get_class($this) == get_class($file_or_dir)) && ($this->getFullPath() == $file_or_dir->getFullPath());
            else
                return false;
        }

        /**
         * 
         * Checks if this element is a child of the specified directory.
         * 
         * @param \Piol\Dir|string $parent The parent directory.
         * @return boolean true if this directory is a direct parent of this one, false otherwise.
         * 
         * @api
         */
        public function isChildOf($parent) {
            $p = Dir::asDir($parent);

            $path_p = $p->getFullPath();
            $path_c = $this->getFullPath();

            //for windows dirs
            $parent_path_c = dirname($path_c) . DIRECTORY_SEPARATOR;
            $parent_path_c = str_replace("\\", DS, $parent_path_c);

            return $parent_path_c == $path_p;
        }

        /**
         * 
         * Checks if this element is a directory.
         * 
         * @return boolean true if this element points to a directory, false otherwise.
         * 
         * @api
         */
        public function isDir() {
            return is_dir($this->__full_path);
        }

        /**
         * 
         * Checks if this element is a file.
         * 
         * @return boolean true if this element points to a file, false otherwise.
         * 
         * @api
         */
        public function isFile() {
            return is_file($this->__full_path);
        }

        /**
         * 
         * Returns true if the path of this element points to an existing file or directory, 
         * false otherwise.
         * 
         * @return boolean true if this element exists in the file system, false otherwise.
         * 
         * @api
         */
        public function exists() {
            return file_exists($this->__full_path);
        }

        /**
         * 
         * Returns the last access time of this element, as Unix timestamp.
         * 
         * @return int The last access time of this element, as Unix timestamp.
         * 
         * @api
         */
        public function getLastAccessTime() {
            clearstatcache(true, $this->__full_path);
            return fileatime($this->__full_path);
        }

        /**
         * 
         * Returns the modification time of this element, as Unix timestamp.
         * 
         * @return int The modification time of this element, as Unix timestamp.
         * 
         * @api
         */
        public function getModificationTime() {
            clearstatcache(true, $this->__full_path);
            return filemtime($this->__full_path);
        }

        /**
         * 
         * Sets the permissions on this element using a 9 character [rwx-] string.
         * 
         * @param string $rwx_permissions The permissions string.
         * @return boolean true if this operation was succesfull, false otherwise.
         * 
         * @internal
         */
        protected function setPermissionsRwx($rwx_permissions) {
            $octal_permissions = self::toOctalPermissions($rwx_permissions);

            $result = chmod($this->__full_path, $octal_permissions);

            if (DIRECTORY_SEPARATOR == DS)
                return $result;
            else {
                $real_permissions = $this->getPermissionsRwx();

                return strcmp($rwx_permissions, $real_permissions) == 0;
            }
        }

        /**
         * 
         * Returns true if this element has all the permissions specified in the parameter.
         * If a particolar check is not required a - can be used.
         * Ex. : rw-r-----
         * 
         * @param string $rwx_permissions The permission to check as a 9 character [rwx-] string. 
         * @return boolean true if all the permissions needed are present in this element, false otherwise.
         * 
         * @internal
         */
        protected function hasPermissionsRwx($rwx_permissions) {

            $current_perms = $this->getPermissionsRwx();

            for ($i = 0; $i < strlen($current_perms); $i++) {
                if ($rwx_permissions[$i] !== "-")
                    if ($rwx_permissions[$i] !== $current_perms[$i])
                        return false;
            }
            return true;
        }

        /**
         * 
         * Returns the full permission string (user/group/other) in rwx format for this element.
         * 
         * @return string the full rwx permission string.
         * 
         * @internal
         */
        protected function getPermissionsRwx() {
            clearstatcache(true, $this->__full_path);
            $perms = fileperms($this->__full_path);

            return self::toRwxPermissions($perms);
        }

        /*
         * Rinomina l'elemento lasciando invariata la sua posizione (cartella padre).
         * */

        /**
         * 
         * Renames this element.
         * 
         * @param string $new_name The new full name of this element.
         * 
         * @api
         */
        public abstract function rename($new_name);

        /**
         * 
         * Moves this element and all its content to the specified target directory.
         * 
         * @param \Piol\Dir|string $target_dir The target directory as string path or Dir instance.
         * @param string $new_name The optional new full name of the moved element.
         * @return boolean true if this operation was succesfull, false otherwise.
         * 
         * @api
         */
        public function moveTo($target_dir, $new_name = null) {
            $target = Dir::asDir($target_dir);

            if ($new_name != null) {
                $name = $new_name;
            } else {
                if ($this->isDir()) {
                    $name = $this->getName();
                } else {
                    $name = $this->getFullName();
                }
            }


            if ($this->isDir()) {
                $dest = new Dir($target->getPath() . DS . $name);
            } else {
                $dest = new File($target->getPath() . DS . $name);
            }

            $target->touch();

            return rename($this->getFullPath(), $dest->getFullPath());
        }

        /**
         * 
         * Copies this element and all its content to the specified location.
         * 
         * @param \Piol\Dir|string $location The location as a string path or Dir instance.
         * 
         * @api
         */
        public abstract function copy($location);

        /**
         * 
         * Returns the full absolute path of this element.
         * 
         * @return string the full path of this element.
         * 
         * @api
         */
        public function getFullPath() {
            return $this->__full_path;
        }

        /**
         * 
         * Returns the relative path of this element.
         * 
         * @param \Piol\Dir|string $ancestor An optional ancestor Dir or string path for returning a shortened path.
         * @return string The string path
         * @throws IOException If the ancestor path is not a prefix of the relative path.
         * 
         * @api
         */
        public function getPath($ancestor = null) {
            if ($ancestor == null)
                return $this->__path;
            else {
                $relative_dir = Dir::asDir($ancestor);

                $path = $relative_dir->getPath();

                if (strpos($this->__path, $path) === 0) {
                    return DS . substr($this->__path, strlen($path));
                } else
                    throw new IOException("Il percorso non comincia col percorso specificato : " . $this->__path . " non comincia con " . $path);
            }
        }

        /**
         * 
         * Checks if this element has storage data. Default uses properties storage.
         * 
         * @param string $storage_type The type of storage to check.
         * 
         * @return boolean true if this element has stored properties, false otherwise.
         * 
         * @api
         */
        public function hasAttachedStorage($storage_type = "ini") {
            return $this->getAttachedStorage($storage_type)->exists();
        }

        /**
         * 
         * Deletes all the storage data for this element. Default deletes properties storage.
         * 
         * @param string $storage_type The type of storage to delete.
         * 
         * @api
         */
        public function deleteAttachedStorage($storage_type = "ini") {
            $this->getAttachedStorage($storage_type)->delete();
        }

        /**
         * 
         * Returns the storage data of this element, using the standard protected storage.
         * Default uses properties storage.
         * Files are clustered by parent directory.
         * 
         * @param string $storage_type The type of storage to get.
         * 
         * @return \Piol\Storage\PropertiesStorage the properties storage for this element.
         * 
         * @api
         */
        public function getAttachedStorage($storage_type = "ini") {
            if ($this->getPath()==="/")
                $path_md5 = md5("");
            else
                $path_md5 = md5($this->getParentDir()->getPath());

            $folder_name = "_" . substr($path_md5, 0, 1);
            
            $file_name = md5($this->getFullName());
            return StorageFactory::getByExtension($folder_name, $file_name, $storage_type);
        }

        /**
         * Return the relative path of this element.
         * 
         * @return string the path of this element as a string.
         * 
         * @api
         */
        public function __toString() {
            return $this->getPath();
        }

        /**
         * 
         * Returns true if this user has READ permissions on this element.
         * 
         * @return boolean true if this element is READABLE.
         */
        function isReadable() {
            return $this->hasPermissions("r--------");
        }

        /**
         *
         * Returns true if this user has READ and WRITE permissions on this element, false otherwise.
         * 
         * @return boolean true if this element is READABLE and WRITABLE.
         */
        function isWritable() {
            return $this->hasPermissions("rw-------");
        }

        /**
         * 
         * Returns the path relative to the PIOL_ROOT_PATH. If the path is not child of PIOL_ROOT_PATH
         * an exception in thrown.
         * 
         * @param string $full_path The path to transform.
         * @return string the relative path. 
         * @throws IOException If the path is not child of the PIOL_ROOT_PATH
         * 
         * @api
         */
        public static function toRelativePath($full_path) {
            $full_path1 = str_replace(DS . "..", "", $full_path);
            //pulizia doppie barre dai percorsi
            $full_path2 = str_replace("//", DS, $full_path1);
            if (strpos($full_path2, PIOL_ROOT_PATH) !== 0) {

                throw new IOException("Errore : il path non è relativo alla jail.");
            } else {
                $path1 = substr($full_path2, strlen(PIOL_ROOT_PATH), strlen($full_path2) - strlen(PIOL_ROOT_PATH));
                $path2 = str_replace("\\", DS, $path1);
                return $path2;
            }
        }
        
        /**
         * 
         * Returns the longest available name for this element, without any path part (directories).
         * 
         * @return string The full long name of this element.
         * 
         * @api
         */
        public abstract function getFullName();

        /**
         * 
         * Return the name of this element, without any extension (if available) or path part (directory).
         * 
         * @return string The name of this element.
         * 
         * @api
         */
        public abstract function getName();

        /**
         * 
         * Return informations about this element in an associative array.
         * The following fields are provided : 
         * 
         * full_path, path, name, type, permissions (both File and Dir).
         * empty : only Dir.
         * extension, full_extension, mime_type, size, size_auto : only File.
         * 
         * @return array An array with informations about this element.
         * 
         * @api
         */
        public abstract function getInfo();

        /**
         * 
         * Returns true if this element is empty or contains no elements, false otherwise.
         * For directories : no files inside.
         * For files : size is equal to zero.
         * 
         * @return boolean true if this element is empty, false otherwise.
         * 
         * @api
         */
        public abstract function isEmpty();

        /**
         * 
         * Returns the parent directory as a Dir instance of this element.
         * 
         * @return \Piol\Dir The parent directory
         * 
         * @api
         */
        public function getParentDir() {
            $parent_path = dirname($this->__path);
            $parent_path = str_replace("\\", DS, $parent_path);
            return new Dir($parent_path);
        }

    }

}
?>