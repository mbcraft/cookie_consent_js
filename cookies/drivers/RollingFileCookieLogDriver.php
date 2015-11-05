<?php
/**
 * 
 * Author and Copyright : 
 * Marco Bagnaresi - MBCRAFT di Marco Bagnaresi
 * http://www.mbcraft.it
 * 
 * Version : 2.4.1
 * 
 * 
 * RollingFileCookieLogDriver
 * 
 * RollingFileCookieLogDriver utilizza dei file per scrivere delle entries col 
 * log delle azioni relative all'accettazione o al rifiuto dei cookies.
 * Le entries sono nel formato :
 * <IP>:<TIMESTAMP>:<USER ACTION>:<EFFECT>
 * 
 * <IP> : un ip nel classico formato xxx.xxx.xxx.xxx
 * <TIMESTAMP> : un timestamp in formato intero
 * <USER ACTION> : l'azione che l'utente ha effettuato
 * <EFFECT> : che cosa viene fatto. L'effetto è nel formato DENY-<SPEC>
 *            ,ALLOW-<SPEC> oppure DELETE-* dove SPEC può essere * per 
 *            indicare tutti i cookies, oppure indicare il nome di una 
 *            application dichiarata via JavaScript.
 * 
 * Sono presenti all'interno della cartella .cookies_<SECRET>, dove secret
 * è un numero configurato dall'utente nel file config.ini, diversi file
 * nominati in questo modo :
 * 
 * A_<RANDOM>.log
 * B_<RANDOM>.log
 * ...
 * <L>_<RANDOM>.log
 * 
 * che, ogniqualvolta è necessario scrivere un log, vengono aperti in scrittura.
 * Essendo possibile che un altro thread abbia aperto il file in scrittura, viene
 * tentato il file successivo fino a quando non viene trovato un file che può essere
 * utilizzato. A quel punto il log viene scritto e il file chiuso.
 * Se un file supera una certa dimensione (specificata nel config.ini) esso
 * viene copiato per intero in un file a parte e completamente svuotato.
 */

namespace Cookies {

    class RollingFileCookieLogDriver implements ICookieLogDriver {

        const LS = "||";
        
        private $props;

        const COOKIES_LOGS_TMP_FOLDER__KEY = "cookies_logs_tmp_folder";
        const COOKIES_LOGS_TMP_MAX_SIZE__KEY = "cookies_logs_tmp_max_size";
        const COOKIES_LOGS_STORE_FOLDER__KEY = "cookies_logs_store_folder";
        const COOKIES_INTRUSION_LOG_FILE__KEY = "cookies_intrusion_log_file";

        /**
         * Saves the properties readed in the configuration inside this driver
         * for future reuse.
         * 
         * @param array $properties the properties array in the default namespace
         */
        function setup($properties) {
            $this->props = $properties;
        }

        /**
         * Logs a cookie entry to a log file. Creates the log directory if it does not
         * exists. If the file is can't be opened, a new file is created.
         * 
         * @param string $ip The ip of the request
         * @param string $timestamp The timestamp of the request
         * @param string $user_action The user action of the request
         * @param string $effect The effect of the request
         */
        public function log($user_action, $effect) {
                        
            $log_dir = new \Piol\Dir($this->props[self::COOKIES_LOGS_TMP_FOLDER__KEY]);
            $log_dir->touch();

            $log_files = $log_dir->listFiles();

            foreach ($log_files as $log_file) {
                $log_writer = $log_file->openLogWriter();
                if ($log_writer !== null) {
                    $this->save_log($log_writer, $user_action, $effect);
                    $max_log_size = $this->props[self::COOKIES_LOGS_TMP_MAX_SIZE__KEY];
                    if ($log_file->getSize() > $max_log_size) {
                        $this->store_log_file($log_file);
                    }
                    return true;
                }
            }
            //no valid files were found - verified, works

            $num_log_files = count($log_files);
            $new_log_file = $log_dir->newFile(chr($num_log_files + 65) . "_" . rand(1000, 9999) . ".log");

            $log_writer = $new_log_file->openLogWriter();
            if ($log_writer !== null) {
                $this->save_log($log_writer, $user_action, $effect);
                return true;
            } else {
                throw new \Piol\IOException("Error in opening a new log file : " . $new_log_file->getName());
            }
            
        }

        /**
         * Saves the entry with the provided writer and closes it.
         * 
         * @param type $log_writer The writer to use
         * @param type $user_action The action done by the user
         * @param type $effect The effect associated to the user action
         */
        private function save_log($log_writer, $user_action, $effect) {
            $ip = $_SERVER["REMOTE_ADDR"];
            $user_agent = $_SERVER["HTTP_USER_AGENT"];
            $timestamp = time();
            $log_writer->writeln($ip .self::LS.$user_agent.self::LS.$timestamp.self::LS.$user_action.self::LS.$effect);
            $log_writer->close();
        }

        /**
         * Move the log file in another storage location, and empty the current one.
         * 
         * @param File $log_file The log file to move
         */
        private function store_log_file($log_file) {
            $stored_logs_dir = new \Piol\Dir($this->props[self::COOKIES_LOGS_STORE_FOLDER__KEY]);
            $stored_logs_dir->touch();

            $log_file->moveTo($stored_logs_dir, time() . ".log");
        }

        /**
         * Logs an intrusion on the intrusion file.
         * 
         * TESTED
         */
        public function intrusion_detected() {
                
            $intrusion_file = new \Piol\File($this->props[self::COOKIES_INTRUSION_LOG_FILE__KEY]);            
            
            $intrusion_file_dir = $intrusion_file->getParentDir();
            //creates the required directory if needed
            $intrusion_file_dir->touch();

            //open the log file
            $log_writer = $intrusion_file->openLogWriter();
            
            $ip = $_SERVER["REMOTE_ADDR"];

            $user_agent = $_SERVER["HTTP_USER_AGENT"];

            $timestamp = time();

            $get_array_export = var_export($_GET, true);
            
            $log_writer->writeln($ip . self::LS . $user_agent . self::LS . $timestamp . self::LS . $get_array_export);
            
            $log_writer->close();
        }

    }

}