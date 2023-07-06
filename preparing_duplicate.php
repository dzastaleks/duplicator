<?php 

public static function duplicate_site($data)
{

    global $wpdb;
    $form_message = array();
    $wpdb->hide_errors();

    self::init_log($data);

    $email = $data['email'];
    $domain = $data['domain'];
    $newdomain = $data['newdomain'];
    $path = $data['path'];
    $title = $data['title'];
    $from_site_id = $data['from_site_id'];
    $keep_users = $data['keep_users'];
    $copy_file = $data['copy_files'];
    $public = $data['public'];
    $network_id = $data['network_id'];

    write_log('Start site duplication : from site ' . $from_site_id);
    write_log('Admin email : ' . $email);
    write_log('Domain : ' . $newdomain);
    write_log('Path : ' . $path);
    write_log('Site title : ' . $title);

    $user_id = create_admin($email, $domain);
    var_dump($user_id);
    printf("switch");
    if (is_wp_error($user_id)) {
        $form_message['error'] = $user_id->get_error_message();
        return $form_message;
    }

    // Create new site

    switch_to_blog(1);
    $to_site_id = wpmu_create_blog($newdomain, $path, $title, $user_id, array('public' => $public), $network_id);
    $wpdb->show_errors();

    if (is_wp_error($to_site_id)) {
        $form_message['error'] = $to_site_id->get_error_message();
        return $form_message;
    }

    // User rights adjustments
    if (!is_super_admin($user_id) && !get_user_option('primary_blog', $user_id)) {
        update_user_option($user_id, 'primary_blog', $to_site_id, true);
    }

    bypass_server_limit();

    // Copy Site - File
    if ($copy_file == 'yes') {
        // do_action('mucd_before_copy_files', $from_site_id, $to_site_id);
        $result = copy_files($from_site_id, $to_site_id);
        // do_action('mucd_after_copy_files', $from_site_id, $to_site_id);
    }

    // Copy Site - Data
    // do_action('mucd_before_copy_data', $from_site_id, $to_site_id);
    $result = copy_data($from_site_id, $to_site_id);
    // do_action('mucd_after_copy_data', $from_site_id, $to_site_id);

    // Copy Site - Users
    // if ($keep_users == 'yes') {
    //     do_action('mucd_before_copy_users', $from_site_id, $to_site_id);
    //     $result = copy_users($from_site_id, $to_site_id);
    //     do_action('mucd_after_copy_users', $from_site_id, $to_site_id);
    // }

    update_blog_option($to_site_id, 'mucd_duplicable', "no");

    $form_message['msg'] = MUCD_NETWORK_PAGE_DUPLICATE_NOTICE_CREATED;
    $form_message['site_id'] = $to_site_id;

    write_log('End site duplication : new site ID = ' . $to_site_id);

    wp_cache_flush();

    return $form_message;
}

/**
 * Creates an admin user if no user exists with this email
 * @since 0.2.0
 * @param  string $email the email
 * @param  string $domain the domain
 * @return int id of the user
 */
public static function create_admin($email, $domain)
{
    // Create New site Admin if not exists
    $password = 'N/A';
    $user_id = email_exists($email);
    if (!$user_id) { // Create a new user with a random password
        var_dump($domain);
        $password = wp_generate_password(12, false);
        $user_id = wpmu_create_user($domain, $password, $email);
        var_dump($user_id);
        if (false == $user_id) {
            return new WP_Error('file_copy', MUCD_NETWORK_PAGE_DUPLICATE_ADMIN_ERROR_CREATE_USER);
        } else {
            add_user_meta($user_id, 'first_name', "Marko");
    add_user_meta($user_id, 'last_name', "Markovic");
    wp_new_user_notification($user_id, $password);
        }
    }

    return $user_id;
}

/**
 * Copy users and roles from one site to another
 * @since 0.2.0
 * @param  int $from_site_id duplicated site id
 * @param  int $to_site_id   new site id
 */
public static function copy_users($from_site_id, $to_site_id)
{

    global $wpdb;

    // Bugfix Pierre Dargham : relocating this declaration outside of the loop
    // PHP < 5.3
    function user_array_map($a)
    {
        return $a[0];
    }

    if (is_main_site($from_site_id)) {
        $is_from_main_site = true;
        $args = array('fields' => 'ids');
        $all_sites_ids = get_sites($args);
        if (!empty($all_sites_ids)) {
            $all_sites_ids = array_map('user_array_map', $all_sites_ids);
        }
    } else {
        $is_from_main_site = false;
    }

    // Source Site information
    $from_site_prefix = $wpdb->get_blog_prefix($from_site_id);                    // prefix 
    $from_site_prefix_length = strlen($from_site_prefix);                           // prefix length

    // Destination Site information
    $to_site_prefix = $wpdb->get_blog_prefix($to_site_id);                        // prefix
    $to_site_prefix_length = strlen($to_site_prefix);

    $users = get_users('blog_id=' . $from_site_id);

    $admin_email = get_blog_option($to_site_id, 'admin_email', 'false');

    switch_to_blog($to_site_id);

    foreach ($users as $user) {
        if ($user->user_email != $admin_email) {

            add_user_to_blog($to_site_id, $user->ID, 'subscriber');

            $all_meta = array_map('user_array_map', get_user_meta($user->ID));

            foreach ($all_meta as $metakey => $metavalue) {
                $prefix = substr($metakey, 0, $from_site_prefix_length);
                if ($prefix == $from_site_prefix) {
                    $raw_meta_name = substr($metakey, $from_site_prefix_length);
                    if ($is_from_main_site) {
                        $parts = explode('_', $raw_meta_name, 2);
                        if (count($parts) > 1 && in_array($parts[0], $all_sites_ids)) {
                            continue;
                        }
                    }
                    update_user_meta($user->ID, $to_site_prefix . $raw_meta_name, maybe_unserialize($metavalue));
                }
            }
        }
    }

    restore_current_blog();
}

/**
 * Init log object
 * @since 0.2.0
 * @param  array $data data from FORM
 */
public static function init_log($data)
{
    // INIT LOG AND SAVE OPTION
    if (isset($data['log']) && $data['log'] == 'yes') {
        if (isset($data['log-path']) && !empty($data['log-path'])) {
            $log_name = @date('Y_m_d_His') . '-' . $data['domain'] . '.log';
            if (substr($data['log-path'], -1) != "/") {
                $data['log-path'] = $data['log-path'] . '/';
            }
            // $log = new MUCD_Log(true, $data['log-path'], $log_name);
        }
    } else {
        // $log = new MUCD_Log(false);
    }
}

/**
 * Check if log is active
 * @since 0.2.0
 * @return boolean
 */
public static function log()
{
    return (self::$log !== false && self::$log->can_write() && self::$log->mod() !== false);
}

/**
 * Check if log has error
 * @since 0.2.0
 * @return boolean
 */
public static function log_error()
{
    return (self::$log !== false && !(self::$log->can_write()) && self::$log->mod() !== false);
}

/**
 * Writes a message in log file
 * @since 0.2.0
 * @param  string $msg the message
 */
public static function write_log($msg)
{
    if (self::log() !== false) {
        self::$log->write_log($msg);
    }
}

/**
 * Close the log file
 * @since 0.2.0
 */
public static function close_log()
{
    if (self::log() !== false) {
        self::$log->close_log();
    }
}

/**
 * Get the url of the created log file
 * @since 0.2.0
 * @return  string the url of false if no log file was created
 */
public static function log_url()
{
    if (self::log() !== false) {
        return self::$log->file_url();
    }
    return false;
}

/**
 * Get log directory
 * @since 0.2.0
 * @return string the path
 */
public static function log_dir()
{
    return self::$log->dir_path();
}

/**
 * Bypass limit server if possible
 * @since 0.2.0
 */
public static function bypass_server_limit()
{
    @ini_set('memory_limit', '5024M');
    @ini_set('max_execution_time', '0');
}
 /**
         * Copy files from one site to another
         * @since 0.2.0
         * @param  int $from_site_id duplicated site id
         * @param  int $to_site_id   new site id
         */
        public static function copy_files( $from_site_id, $to_site_id ) {
            // Switch to Source site and get uploads info
            switch_to_blog($from_site_id);
            $wp_upload_info = wp_upload_dir();
            $from_dir['path'] = str_replace(' ', "\\ ", trailingslashit($wp_upload_info['basedir']));
            $from_site_id==MUCD_PRIMARY_SITE_ID ? $from_dir['exclude'] = get_primary_dir_exclude() :  $from_dir['exclude'] = array();

            // Switch to Destination site and get uploads info
            switch_to_blog($to_site_id);
            $wp_upload_info = wp_upload_dir();
            $to_dir = str_replace(' ', "\\ ", trailingslashit($wp_upload_info['basedir']));

            restore_current_blog();

            $dirs = array();
            $dirs[] = array(
                'from_dir_path' => $from_dir['path'],
                'to_dir_path'   => $to_dir,
                'exclude_dirs'  => $from_dir['exclude'],
            );

            $dirs = apply_filters('mucd_copy_dirs', $dirs, $from_site_id, $to_site_id);

            foreach($dirs as $dir) {
                if(isset($dir['to_dir_path']) && !init_dir($dir['to_dir_path'])) {
                    mkdir_error($dir['to_dir_path']);
                }
                // write_log('Copy files from ' . $dir['from_dir_path'] . ' to ' . $dir['to_dir_path']);
                recurse_copy($dir['from_dir_path'], $dir['to_dir_path'], $dir['exclude_dirs']);
            }

            return true;
        }

        /**
         * Copy files from one directory to another
         * @since 0.2.0
         * @param  string $src source directory path
         * @param  string $dst destination directory path
         * @param  array  $exclude_dirs directories to ignore
         */
        public static function recurse_copy($src, $dst, $exclude_dirs=array()) {
            $src = rtrim( $src, '/' );
            $dst = rtrim( $dst, '/' );
            $dir = opendir($src);
            @mkdir($dst);
            while(false !== ( $file = readdir($dir)) ) {
                if (( $file != '.' ) && ( $file != '..' )) {
                    if ( is_dir($src . '/' . $file) ) {
                        if(!in_array($file, $exclude_dirs)) {
                            recurse_copy($src . '/' . $file,$dst . '/' . $file);
                        }
                    }
                    else {
                        copy($src . '/' . $file,$dst . '/' . $file);
                    }
                }
            }
            closedir($dir);
        }

        /**
         * Set a directory writable, creates it if not exists, or return false
         * @since 0.2.0
         * @param  string $path the path
         * @return boolean True on success, False on failure
         */
        public static function init_dir($path) {
            $e = error_reporting(0);

            if(!file_exists($path)) {
                return mkdir($path, 0777);
            }
            else if(is_dir($path)) {
                if(!is_writable($path)) {
                    return chmod($path, 0777);
                }
                return true;
            }

            error_reporting($e);
            return false;
        }

        /**
         * Removes a directory and all its content
         * @since 0.2.0
         * @param  string $dir the path
         */
        public static function rrmdir($dir) {
           if (is_dir($dir)) { 
             $objects = scandir($dir); 
             foreach ($objects as $object) { 
               if ($object != "." && $object != "..") { 
                 if (filetype($dir."/".$object) == "dir") self::rrmdir($dir."/".$object); else unlink($dir."/".$object); 
               } 
             } 
             reset($objects);
             rmdir($dir); 
           } 
        }

        /**
         * Stop process on Creating dir Error, print and log error, removes the new blog
         * @since 0.2.0
         * @param  string  $dir_path the path
         */
        public static function mkdir_error($dir_path) {
            $error_1 = 'ERROR DURING FILE COPY : CANNOT CREATE ' . $dir_path;
            //write_log($error_1 );
            $error_2 = sprintf( MUCD_NETWORK_PAGE_DUPLICATE_COPY_FILE_ERROR , get_primary_upload_dir() );
            //write_log($error_2 );
            //write_log('Duplication interrupted on FILE COPY ERROR');
            echo '<br />Duplication failed :<br /><br />' . $error_1 . '<br /><br />' . $error_2 . '<br /><br />';
            // if( $log_url = log_url() ) {
            //     echo '<a href="' . $log_url . '">' . MUCD_NETWORK_PAGE_DUPLICATE_VIEW_LOG . '</a>';
            // }
            // MUCD_Functions::remove_blog(self::$to_site_id);
            wp_die();
        }

        public static function copy_data( $from_site_id, $to_site_id ) {
            self::$to_site_id = $to_site_id;

            // Copy
            $saved_options = self::db_copy_tables( $from_site_id, $to_site_id );
            // Update
            self::db_update_data( $from_site_id, $to_site_id, $saved_options );
        }

        /**
         * Copy tables from a site to another
         * @since 0.2.0
         * @param  int $from_site_id duplicated site id
         * @param  int $to_site_id   new site id
         */
        public static function db_copy_tables( $from_site_id, $to_site_id ) {
            global $wpdb ;
            
            // Source Site information
            $from_site_prefix = $wpdb->get_blog_prefix( $from_site_id );                    // prefix 
            $from_site_prefix_length = strlen($from_site_prefix);                           // prefix length

            // Destination Site information
            $to_site_prefix = $wpdb->get_blog_prefix( $to_site_id );                        // prefix
            $to_site_prefix_length = strlen($to_site_prefix);                               // prefix length

            // Options that should be preserved in the new blog.
            $saved_options = get_saved_option();
            foreach($saved_options as $option_name => $option_value) {
                $saved_options[$option_name] = get_blog_option( $to_site_id, $option_name );
            }

            // Bugfix : escape '_' , '%' and '/' character for mysql 'like' queries
            $from_site_prefix_like = $wpdb->esc_like($from_site_prefix);

            // SCHEMA - TO FIX for HyperDB
            $schema = DB_NAME;

            // Get sources Tables
            if($from_site_id == MUCD_PRIMARY_SITE_ID) {
                $from_site_table = self::get_primary_tables($from_site_prefix);
            }
            else {
                $sql_query = $wpdb->prepare('SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = \'%s\' AND TABLE_NAME LIKE \'%s\'', $schema, $from_site_prefix_like . '%');
                $from_site_table =  self::do_sql_query($sql_query, 'col'); 
            }

            foreach ($from_site_table as $table) {

                $table_name = $to_site_prefix . substr( $table, $from_site_prefix_length );

                // Drop table if exists
                self::do_sql_query('DROP TABLE IF EXISTS `' . $table_name . '`');

                // Create new table from source table
                self::do_sql_query('CREATE TABLE IF NOT EXISTS `' . $table_name . '` LIKE `' . $schema . '`.`' . $table . '`');

                // Populate database with data from source table
                self::do_sql_query('INSERT `' . $table_name . '` SELECT * FROM `' . $schema . '`.`' . $table . '`');

            }

            // apply key options from new blog.
            self::db_restore_data( $to_site_id,  $saved_options );

            return $saved_options;
       }

        /**
         * Get tables to copy if duplicated site is primary site
         * @since 0.2.0
         * @param  array of string $from_site_tables all tables of duplicated site
         * @param  string $from_site_prefix db prefix of duplicated site
         * @return array of strings : the tables
         */
       public static function get_primary_tables($from_site_prefix) {

            $default_tables =  get_primary_tables_to_copy();

            foreach($default_tables as $k => $default_table) {
                $default_tables[$k] = $from_site_prefix . $default_table;
            }

            return $default_tables;
       }


        /**
         * Updated tables from a site to another
         * @since 0.2.0
         * @param  int $from_site_id duplicated site id
         * @param  int $to_site_id   new site id
         */
        public static function db_update_data( $from_site_id, $to_site_id, $saved_options ) {
            global $wpdb ;  

            $to_blog_prefix = $wpdb->get_blog_prefix( $to_site_id );

            // Looking for uploads dirs
            switch_to_blog($from_site_id);
            $dir = wp_upload_dir();
            $from_upload_url = str_replace(network_site_url(), get_bloginfo('url').'/',$dir['baseurl']);
            $from_blog_url = get_blog_option( $from_site_id, 'siteurl' );
            
            switch_to_blog($to_site_id);
            $dir = wp_upload_dir();
            $to_upload_url = str_replace(network_site_url(), get_bloginfo('url').'/', $dir['baseurl']);
            $to_blog_url = get_blog_option( $to_site_id, 'siteurl' );

            restore_current_blog();

            $tables = array();

            // Bugfix : escape '_' , '%' and '/' character for mysql 'like' queries
            $to_blog_prefix_like = $wpdb->esc_like($to_blog_prefix);

            $results = self::do_sql_query('SHOW TABLES LIKE \'' . $to_blog_prefix_like . '%\'', 'col', FALSE);

            foreach( $results as $k => $v ) {
                $tables[str_replace($to_blog_prefix, '', $v)] = array();
            }

            foreach( $tables as $table => $col) {
                $results = self::do_sql_query('SHOW COLUMNS FROM `' . $to_blog_prefix . $table . '`', 'col', FALSE);

                $columns = array();

                foreach( $results as $k => $v ) {
                    $columns[] = $v;
                }

                $tables[$table] = $columns;    
            }

            $default_tables = get_fields_to_update();

            foreach( $default_tables as $table => $field) {
                $tables[$table] = $field;
            }

            $from_site_prefix = $wpdb->get_blog_prefix( $from_site_id );
            $to_site_prefix = $wpdb->get_blog_prefix( $to_site_id );

            $string_to_replace = array (
                $from_upload_url => $to_upload_url,
                $from_blog_url => $to_blog_url,
                $from_site_prefix => $to_site_prefix
            );

            $string_to_replace = apply_filters('mucd_string_to_replace', $string_to_replace, $from_site_id, $to_site_id);

            foreach( $tables as $table => $field) {
                foreach( $string_to_replace as $from_string => $to_string) {
                    self::update($to_blog_prefix . $table, $field, $from_string, $to_string);
                }
            }

            self::db_restore_data( $to_site_id,  $saved_options );
        }

        /**
        * Restore options that should be preserved in the new blog
        * @since 0.2.0
        * @param  int $from_site_id duplicated site id
        * @param  int $to_site_id   new site id
        */
        public static function db_restore_data( $to_site_id, $saved_options ) {

           switch_to_blog( $to_site_id );

           foreach ( $saved_options as $option_name => $option_value ) {
               update_option( $option_name, $option_value );
           }

           restore_current_blog();
        }

        /**
         * Updates a table
         * @since 0.2.0
         * @param  string $table to update
         * @param  array of string $fields to update
         * @param  string $from_string original string to replace
         * @param  string $to_string new string
         */
        public static function update($table, $fields, $from_string, $to_string) {
            if(is_array($fields) || !empty($fields)) {
                global $wpdb;

                foreach($fields as $field) {

                    // Bugfix : escape '_' , '%' and '/' character for mysql 'like' queries
                    $from_string_like = $wpdb->esc_like($from_string);

                    $sql_query = $wpdb->prepare('SELECT `' .$field. '` FROM `'.$table.'` WHERE `' .$field. '` LIKE "%s" ', '%' . $from_string_like . '%');  
                    $results = self::do_sql_query($sql_query, 'results', FALSE);

                    if($results) {
                        $update = 'UPDATE `'.$table.'` SET `'.$field.'` = "%s" WHERE `'.$field.'` = "%s"';

                         foreach($results as $result => $row) {
                            $old_value = $row[$field];
                            $new_value = self::try_replace( $row, $field, $from_string, $to_string );
                            $sql_query = $wpdb->prepare($update, $new_value, $old_value);
                            $results = self::do_sql_query($sql_query);
                        }
                    }
                }
            }
        }
      
        /**
         * Replace $from_string with $to_string in $val
         * Warning : if $to_string already in $val, no replacement is made
         * @since 0.2.0
         * @param  string $val
         * @param  string $from_string
         * @param  string $to_string
         * @return string the new string
         */
        public static function replace($val, $from_string, $to_string) {
            $new = $val;
            if(is_string($val)) {
                $pos = strpos($val, $to_string);
                if($pos === false) {
                    $new = str_replace($from_string, $to_string, $val);
                }
            }
            return $new;
        }

        /**
         * Replace recursively $from_string with $to_string in $val
         * @since 0.2.0
         * @param  mixte (string|array) $val
         * @param  string $from_string
         * @param  string $to_string
         * @return string the new string
         */
        public static function replace_recursive($val, $from_string, $to_string) {
            $unset = array();
            if(is_array($val)) {
                foreach($val as $k => $v) {
                    $val[$k] = self::try_replace( $val, $k, $from_string, $to_string );
                }
            }
            else
                $val = self::replace($val, $from_string, $to_string);

            foreach($unset as $k)
                unset($val[$k]);

            return $val;
        }

        /**
         * Try to replace $from_string with $to_string in a row
         * @since 0.2.0
         * @param  array $row the row
         * @param  array $field the field
         * @param  string $from_string
         * @param  string $to_string
         * @return the new data
         */
        public static function try_replace( $row, $field, $from_string, $to_string) {
            if(is_serialized($row[$field])) {
                $double_serialize = FALSE;
                $row[$field] = @unserialize($row[$field]);

                // FOR SERIALISED OPTIONS, like in wp_carousel plugin
                if(is_serialized($row[$field])) {
                    $row[$field] = @unserialize($row[$field]);
                    $double_serialize = TRUE;
                }

                if(is_array($row[$field])) {
                    $row[$field] = self::replace_recursive($row[$field], $from_string, $to_string);
                }
                else if(is_object($row[$field]) || $row[$field] instanceof __PHP_Incomplete_Class) { // Étrange fonctionnement avec Google Sitemap...
                    $array_object = (array) $row[$field];
                    $array_object = self::replace_recursive($array_object, $from_string, $to_string);
                    foreach($array_object as $key => $field) {
                        $row[$field]->$key = $field;
                    }
                }
                else {
                        $row[$field] = self::replace($row[$field], $from_string, $to_string);
                }

                $row[$field] = serialize($row[$field]);

                // Pour des options comme wp_carousel...
                if($double_serialize) {
                    $row[$field] = serialize($row[$field]);
                }
            }
            else {
                $row[$field] = self::replace($row[$field], $from_string, $to_string);
            }
            return $row[$field];
        }

        /**
         * Runs a WPDB query
         * @since 0.2.0
         * @param  string  $sql_query the query
         * @param  string  $type type of result
         * @param  boolean $log log the query, or not
         * @return $results of the query
         */
        public static function do_sql_query($sql_query, $type = '', $log = TRUE) {
            global $wpdb;
            $wpdb->hide_errors();

            switch ($type) {
                case 'col':
                    $results = $wpdb->get_col($sql_query);
                    break;
                case 'row':
                    $results = $wpdb->get_row($sql_query);
                    break;
                case 'var':
                    $results = $wpdb->get_var($sql_query);
                    break;
                case 'results':
                    $results = $wpdb->get_results($sql_query, ARRAY_A);
                    break;
                default:
                    $results = $wpdb->query($sql_query);
                    break;
            }

            if($log) {
                write_log('SQL :' .$sql_query);
                write_log('Result :' . var_export($results, true));
            }

            if ($wpdb->last_error != "") {
                self::sql_error($sql_query, $wpdb->last_error);
           }

            return $results;
        }

        /**
         * Stop process on SQL Error, print and log error, removes the new blog
         * @since 0.2.0
         * @param  string  $sql_query the query
         * @param  string  $sql_error the error
         */
        public static function sql_error($sql_query, $sql_error) {
            $error_1 = 'ERROR SQL ON : ' . $sql_query;
            // write_log($error_1 );
            $error_2 = 'WPDB ERROR : ' . $sql_error;
            // write_log($error_2 );
            // write_log('Duplication interrupted on SQL ERROR');
            echo '<br />Duplication failed :<br /><br />' . $error_1 . '<br /><br />' . $error_2 . '<br /><br />';
            if( $log_url = log_url() ) {
                echo '<a href="' . $log_url . '">' . MUCD_NETWORK_PAGE_DUPLICATE_VIEW_LOG . '</a>';
            }
            remove_blog(self::$to_site_id);
            wp_die();
        }

        public static function valid_windows_dir_path($path) {
    	    if(strpos($path, ":") == 1 && preg_match('/[a-zA-Z]/', $path[0])) // check if it's something like C:\
    	            {
    	                $tmp = substr($path,2);
    	                $bool = preg_match('/^[^*?"<>|:]*$/',$tmp);
    	                return ($bool == 1); // so that it will return only true and false
    	            }
    	            return false;    
    	}

        /**
         * Check if a path is valid UNIX path
         * @since 0.2.0
         * @param  string $path the path
         * @return boolean true | false
         */
    	public static function valid_unix_dir_path($path) {
    	    $reg = "/^(\/([a-zA-Z0-9+\$_.-])+)*\/?$/";
    	    $bool = preg_match($reg,$path);
    	    return ($bool == 1);
    	}

        /**
         * Check if a path is valid MS-windows or UNIX path
         * @since 0.2.0
         * @param  string $path the path
         * @return boolean true | false
         */
    	public static function valid_path($path) {
    		return ( valid_unix_dir_path($path) || valid_windows_dir_path($path) );
    	}

        /**
         * Removes completely a blog from the network
         * @since 0.2.0
         * @param  int $blog_id the blog id
         */
        public static function remove_blog($blog_id) {
            switch_to_blog($blog_id);
            $wp_upload_info = wp_upload_dir();
            $dir = str_replace(' ', "\\ ", trailingslashit($wp_upload_info['basedir']));
            restore_current_blog();   

            wpmu_delete_blog($blog_id, true);

            // wpmu_delete_blog leaves an empty site upload directory, that we want to remove :
            rrmdir($dir);
        }
        
        /**
         * Check if site is duplicable
         * @since 0.2.0
         * @param  int $blog_id the blog id
         * @return boolean true | false
         */
        public static function is_duplicable($blog_id) {
            if( get_site_option( 'mucd_duplicables', 'all', 'selected' ) == 'all') {
                return true;
            }

            if( get_blog_option( $blog_id, 'mucd_duplicable' , 'no' ) == 'yes') {
                return true;
            }
            return false;
        }

        /**
         * Get all duplicable sites
         * @since 0.2.0
         * @return array of blog data
         */
        public static function get_site_list() {
            $site_list = array();
            $network_blogs = get_sites(apply_filters( 'mucd_get_site_list_args', array()));
            foreach( $network_blogs as $blog ){
                if (is_duplicable($blog['blog_id']) && MUCD_SITE_DUPLICATION_EXCLUDE != $blog['blog_id']) {
                    $site_list[] = $blog;
                }

            }

            return $site_list;
        }

        /**
         * Check if a value is in an array for a specific key
         * @since 0.2.0
         * @param  mixte $value the value
         * @param  array $array the array
         * @param  string $key  the key
         * @return boolean true | false
         */
        public static function value_in_array($value, $array, $key) {
            foreach($array as $row) {
                if(isset($row[$key]) && $value == $row[$key]) {
                    return true;
                }
            }
            return false;
        }

        /**
         * Get upload directory of the entire network
         * @since 0.2.0
         * @return string path of the upload directory
         */
        public static function get_primary_upload_dir() {
            $current_blog = get_current_blog_id();
            switch_to_blog(MUCD_PRIMARY_SITE_ID);
            $wp_upload_info = wp_upload_dir();
            switch_to_blog($current_blog);

            return $wp_upload_info['basedir'];
        }

        /**
         * Check if site exists
         * @since 1.3.0
         * @param  int $blog_id the blog id
         * @return boolean true | false
         */
        public static function site_exists($blog_id) {
            return (get_blog_details($blog_id) !== false);
        }

        /**
         * Set locale to en_US
         * @since 1.3.1
         */
        public static function set_locale_to_en_US() {

            // Bugfix Pierre Dargham : relocating this declaration outside of the call to add_filter
            // PHP < 5.3 does not accept anonymous functions
            function mucd_locale_en_us( $locale ) { return 'en_US'; }

            add_filter( 'locale', 'mucd_locale_en_us' );
        }

        /**
         * Get network data for a given id.
         *
         * @author wp-cli
         * @see https://github.com/wp-cli/wp-cli/blob/master/php/commands/site.php
         *
         * @param int     $network_id
         * @return bool|array False if no network found with given id, array otherwise
         */
        public static function get_network( $network_id ) {
            global $wpdb;

            // Load network data
            $networks = $wpdb->get_results( $wpdb->prepare(
                "SELECT * FROM $wpdb->site WHERE id = %d", $network_id ) );

            if ( !empty( $networks ) ) {
                // Only care about domain and path which are set here
                return $networks[0];
            }

            return false;
        }

        public static function get_sites( $args = array() ) {
            if(version_compare(get_bloginfo('version'), '4.6', '>=')) {
                $defaults = array('number' => MUCD_MAX_NUMBER_OF_SITE);
                $args = wp_parse_args( $args, $defaults );
                $args = apply_filters( 'mucd_get_sites_args', $args );
                $sites = get_sites($args);
                foreach($sites as $key => $site) {
                    $sites[$key] = (array) $site;
                }
                return $sites;
            } else {
                $defaults = array('limit' => MUCD_MAX_NUMBER_OF_SITE);
                $args = apply_filters( 'mucd_get_sites_args', $args );
                $args = wp_parse_args( $args, $defaults );
                return wp_get_sites( $args );
            }
        }

        /**
         * Deactivate the plugin if we are not on a multisite installation
         * @since 0.2.0
         */
        public static function check_if_multisite() {
            if (!function_exists('is_multisite') || !is_multisite()) {
                deactivate_plugins( plugin_basename( __FILE__ ) );
                wp_die('multisite-clone-duplicator works only for multisite installation');
            }
        }

        /**
         * Deactivate the plugin if we are not on the network admin
         * @since 1.4.0
         */
        public static function check_if_network_admin() {
            if (!is_network_admin() ) {
                deactivate_plugins( plugin_basename( __FILE__ ) );
                wp_die('multisite-clone-duplicator works only as multisite network-wide plugin');
            }
        }

        /**
         * Init 'mucd_duplicable' options
         * @param string $blogs_value the value for blogs options
         * @param string $network_value the value for site option
         * @since 0.2.0
         */
        public static function init_duplicable_option($blogs_value = "no", $network_value = "all") {
            $network_blogs = get_sites();
            foreach( $network_blogs as $blog ){
                $blog_id = $blog['blog_id'];
                add_blog_option( $blog_id, 'mucd_duplicable', $blogs_value);
            }
            add_site_option( 'mucd_duplicables', $network_value );
        }

        /**
         * Delete 'mucd_duplicable' option for all sites
         * @since 0.2.0
         */
        public static function delete_duplicable_option() {
            $network_blogs = get_sites();
            foreach( $network_blogs as $blog ){
                $blog_id = $blog['blog_id'];
                delete_blog_option( $blog_id, 'mucd_duplicable');
            }
            delete_site_option( 'mucd_duplicables');
        }

        /**
         * Set 'mucd_duplicable' option to "yes" for the list of blogs, other to "no"
         * @since 0.2.0
         * @param array $blogs list of blogs we want the option set to "yes"
         */
        public static function set_duplicable_option($blogs) {
            $network_blogs = get_sites();
            foreach( $network_blogs as $blog ){
                if(in_array($blog['blog_id'], $blogs)) {
                    update_blog_option( $blog['blog_id'], 'mucd_duplicable', "yes");
                }
                else {
                    update_blog_option($blog['blog_id'], 'mucd_duplicable', "no");
                }
            }
        }

        /**
         * Add plugin default options
         * @since 1.3.0
         */
        public static function init_options() {
            add_site_option('mucd_copy_files', 'yes');
            add_site_option('mucd_keep_users', 'yes');
            add_site_option('mucd_log', 'no');
            $upload_dir = wp_upload_dir();
            add_site_option('mucd_log_dir', $upload_dir['basedir'] . '/multisite-clone-duplicator-logs/');
            add_site_option('mucd_disable_enhanced_site_select', 'no');
            init_duplicable_option();
        }

        /**
         * Removes plugin options
         * @since 1.3.0
         */
        public static function delete_options() {
            delete_site_option('mucd_copy_files');
            delete_site_option('mucd_keep_users');
            delete_site_option('mucd_log');
            delete_site_option('mucd_log_dir');
            delete_site_option('mucd_disable_enhanced_site_select');
            delete_duplicable_option();
        }
      
        /**
         * Get log directory option
         * @since 0.2.0
         * @return string the path
         */
        public static function get_option_log_directory() {
            $upload_dir = wp_upload_dir();   
            return get_site_option('mucd_log_dir', $upload_dir['basedir'] . '/multisite-clone-duplicator-logs/');
        }

        /**
         * Get directories to exclude from file copy when duplicated site is primary site
         * @since 0.2.0
         * @return  array of string
         */
        public static function get_primary_dir_exclude() {
            return array(
                'sites',
            );
        }

        /**
         * Get default options that should be preserved in the new blog.
         * @since 0.2.0
         * @return  array of string
         */
        public static function get_default_saved_option() {
            return array(
                'siteurl'=>'',
                'home'=>'',
                'upload_path'=>'',
                'fileupload_url'=>'',
                'upload_url_path'=>'',
                'admin_email'=>'',
                'blogname'=>''
            );
        }

        /**
         * Get filtered options that should be preserved in the new blog.
         * @since 0.2.0
         * @return  array of string (filtered)
         */
        public static function get_saved_option() {
            return apply_filters('mucd_copy_blog_data_saved_options', get_default_saved_option());
        }   
        
        /**
         * Get default fields to scan for an update after data copy
         * @since 0.2.0
         * @return array '%table_name' => array('%field_name_1','%field_name_2','%field_name_3', ...)
         */
        public static function get_default_fields_to_update() {
            return array (
                'commentmeta' => array(),
                'comments' => array(),
                'links' => array('link_url', 'link_image'),
                'options' => array('option_name', 'option_value'),
                'postmeta' => array('meta_value'),
                'posts' => array('post_content', 'guid'),
                'terms' => array(),
                'term_relationships' => array(),
                'term_taxonomy' => array(),
            );
        }

        /**
         * Get filtered fields to scan for an update after data copy
         * @since 0.2.0
         * @return  array of string (filtered)
         */
        public static function get_fields_to_update() {
            return apply_filters('mucd_default_fields_to_update', get_default_fields_to_update());
        }
      
        /**
         * Get default tables to duplicate when duplicated site is primary site
         * @since 0.2.0
         * @return  array of string
         */
        public static function get_default_primary_tables_to_copy() {
            return array (
                'commentmeta',
                'comments',
                'links',
                'options',
                'postmeta',
                'posts',
                'terms',
                'term_relationships',
                'term_taxonomy',
                'termmeta',
            );
        }

        /**
         * Get filtered tables to duplicate when duplicated site is primary site
         * @since 0.2.0
         * @return  array of string (filtered)
         */
        public static function get_primary_tables_to_copy() {
            return apply_filters('mucd_default_primary_tables_to_copy', get_default_primary_tables_to_copy());
        }
    

?>
