<?php 

  //require ( 'preparing_duplicate.php' );
// require_once("../../../../wp-load.php");
require_once(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/wp-load.php');
require_once('multisite-clone-duplicator.php');
$log = null; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Check if the request method is POST

  // Retrieve form data
  //$name = $_POST['names'];
  //$email = $_POST['emails'];

 $data = $_POST['data'];
  //echo "HERE". var_dump($data); 
  
MUCD_Duplicate::duplicate_site($data);

//$user_id = MUCD_Duplicate::duplicate_site($data);
  // Return a successful response
$response = array(
  'status' => 'success',
  'message' => 'AJAX request successful.',
  'data' => $data // Optional: Include additional data in the response
);

// Set the response content type to JSON
header('Content-Type: application/json');

// Output the response as JSON
echo json_encode($response);

// Terminate the PHP script
exit(); 

  // Process the data
  // ...

  // Return a response if needed
 // echo 'Form submitted successfully.';
}	


// function duplicate_site($data)
// {
//     $wpdb = $GLOBALS['wpdb'];

//     //global $wpdb;

//     $form_message = array();

//     //$wpdb->hide_errors();

//     // self::init_log($data);

//     $email = $data['email'];
//     $domain = $data['domain'];
//     $newdomain = $data['newdomain'];
//     $path = $data['path'];
//     $title = $data['title'];
//     $from_site_id = $data['from_site_id'];
//     $keep_users = $data['keep_users'];
//     $copy_file = $data['copy_files'];
//     $public = $data['public'];
//     $network_id = $data['network_id'];

//     // write_log('Start site duplication : from site ' . $from_site_id);
//     // write_log('Admin email : ' . $email);
//     // write_log('Domain : ' . $newdomain);
//     // write_log('Path : ' . $path);
//     // write_log('Site title : ' . $title);
//      $user_id = create_admin($email, $domain);
//      echo "after  user id ";

//    // var_dump($user_id);
//    // printf("switch");

//    if (is_wp_error($user_id)) {
//        $form_message['error'] = $user_id->get_error_message();
//         return $form_message;
//     }
//         echo "test 2314";


//     // Create new site

//     switch_to_blog(1);
//     $to_site_id = wpmu_create_blog($newdomain, $path, $title, $user_id, array('public' => $public), $network_id);
//     //$wpdb->show_errors();

//     if (is_wp_error($to_site_id)) {
//         $form_message['error'] = $to_site_id->get_error_message();
//         return $form_message;
//     }

//     // User rights adjustments
//     if (!is_super_admin($user_id) && !get_user_option('primary_blog', $user_id)) {
//         update_user_option($user_id, 'primary_blog', $to_site_id, true);
//     }

//     bypass_server_limit();

//     // Copy Site - File
//     if ($copy_file == 'yes') {
//         // do_action('mucd_before_copy_files', $from_site_id, $to_site_id);
//         $result = copy_files($from_site_id, $to_site_id);
//         // do_action('mucd_after_copy_files', $from_site_id, $to_site_id);
//     }

//     // Copy Site - Data
//     // do_action('mucd_before_copy_data', $from_site_id, $to_site_id);
//     $result = copy_data($from_site_id, $to_site_id);
//     // do_action('mucd_after_copy_data', $from_site_id, $to_site_id);

//     // Copy Site - Users
//     // if ($keep_users == 'yes') {
//     //     do_action('mucd_before_copy_users', $from_site_id, $to_site_id);
//     //     $result = copy_users($from_site_id, $to_site_id);
//     //     do_action('mucd_after_copy_users', $from_site_id, $to_site_id);
//     // }

//     update_blog_option($to_site_id, 'mucd_duplicable', "no");

//     $form_message['msg'] = MUCD_NETWORK_PAGE_DUPLICATE_NOTICE_CREATED;
//     $form_message['site_id'] = $to_site_id;

//     // write_log('End site duplication : new site ID = ' . $to_site_id);

//     wp_cache_flush();

//     return $form_message;
// }

// function create_admin($email, $domain)
// {
//     // Create New site Admin if not exists
//     $password = 'N/A';
//     $user_id = email_exists($email);
//     echo "after email exists"; 

//     if (!$user_id) { // Create a new user with a random password
//         var_dump($domain);
//         $password = wp_generate_password(12, false);
//         $user_id = wpmu_create_user($domain, $password, $email);
//         var_dump($user_id);
//         if (false == $user_id) {
//             return new WP_Error('file_copy', MUCD_NETWORK_PAGE_DUPLICATE_ADMIN_ERROR_CREATE_USER);
//         } else {
//             add_user_meta($user_id, 'first_name', "Marko");
//             add_user_meta($user_id, 'last_name', "Markovic");
//             wp_new_user_notification($user_id, $password);
//         }
//     }

//     return $user_id;
// }
// function copy_users($from_site_id, $to_site_id)
// {

//     global $wpdb;

//     // Bugfix Pierre Dargham : relocating this declaration outside of the loop
//     // PHP < 5.3
//     function user_array_map($a)
//     {
//         return $a[0];
//     }

//     if (is_main_site($from_site_id)) {
//         $is_from_main_site = true;
//         $args = array('fields' => 'ids');
//         $all_sites_ids = get_sites($args);
//         if (!empty($all_sites_ids)) {
//             $all_sites_ids = array_map('user_array_map', $all_sites_ids);
//         }
//     } else {
//         $is_from_main_site = false;
//     }

//     // Source Site information
//     $from_site_prefix = $wpdb->get_blog_prefix($from_site_id);                    // prefix 
//     $from_site_prefix_length = strlen($from_site_prefix);                           // prefix length

//     // Destination Site information
//     $to_site_prefix = $wpdb->get_blog_prefix($to_site_id);                        // prefix
//     $to_site_prefix_length = strlen($to_site_prefix);

//     $users = get_users('blog_id=' . $from_site_id);

//     $admin_email = get_blog_option($to_site_id, 'admin_email', 'false');

//     switch_to_blog($to_site_id);

//     foreach ($users as $user) {
//         if ($user->user_email != $admin_email) {

//             add_user_to_blog($to_site_id, $user->ID, 'subscriber');

//             $all_meta = array_map('user_array_map', get_user_meta($user->ID));

//             foreach ($all_meta as $metakey => $metavalue) {
//                 $prefix = substr($metakey, 0, $from_site_prefix_length);
//                 if ($prefix == $from_site_prefix) {
//                     $raw_meta_name = substr($metakey, $from_site_prefix_length);
//                     if ($is_from_main_site) {
//                         $parts = explode('_', $raw_meta_name, 2);
//                         if (count($parts) > 1 && in_array($parts[0], $all_sites_ids)) {
//                             continue;
//                         }
//                     }
//                     update_user_meta($user->ID, $to_site_prefix . $raw_meta_name, maybe_unserialize($metavalue));
//                 }
//             }
//         }
//     }

//     restore_current_blog();
// }
	
// function init_log($data)
// {
//     // INIT LOG AND SAVE OPTION
//     if (isset($data['log']) && $data['log'] == 'yes') {
//         if (isset($data['log-path']) && !empty($data['log-path'])) {
//             $log_name = @date('Y_m_d_His') . '-' . $data['domain'] . '.log';
//             if (substr($data['log-path'], -1) != "/") {
//                 $data['log-path'] = $data['log-path'] . '/';
//             }
//             // $log = new MUCD_Log(true, $data['log-path'], $log_name);
//         }
//     } else {
//         // $log = new MUCD_Log(false);
//     }
// }

// function bypass_server_limit()
// {
//     @ini_set('memory_limit', '5024M');
//     @ini_set('max_execution_time', '0');
// }

// function copy_files($from_site_id, $to_site_id)
// {
//     // Switch to Source site and get uploads info
//     switch_to_blog($from_site_id);
//     $wp_upload_info = wp_upload_dir();
//     $from_dir['path'] = str_replace(' ', "\\ ", trailingslashit($wp_upload_info['basedir']));
//     $from_site_id == MUCD_PRIMARY_SITE_ID ? $from_dir['exclude'] = get_primary_dir_exclude() :  $from_dir['exclude'] = array();

//     // Switch to Destination site and get uploads info
//     switch_to_blog($to_site_id);
//     $wp_upload_info = wp_upload_dir();
//     $to_dir = str_replace(' ', "\\ ", trailingslashit($wp_upload_info['basedir']));

//     restore_current_blog();

//     $dirs = array();
//     $dirs[] = array(
//         'from_dir_path' => $from_dir['path'],
//         'to_dir_path'   => $to_dir,
//         'exclude_dirs'  => $from_dir['exclude'],
//     );

//     $dirs = apply_filters('mucd_copy_dirs', $dirs, $from_site_id, $to_site_id);

//     foreach ($dirs as $dir) {
//         if (isset($dir['to_dir_path']) && !init_dir($dir['to_dir_path'])) {
//             mkdir_error($dir['to_dir_path']);
//         }
//         // write_log('Copy files from ' . $dir['from_dir_path'] . ' to ' . $dir['to_dir_path']);
//         recurse_copy($dir['from_dir_path'], $dir['to_dir_path'], $dir['exclude_dirs']);
//     }

//     return true;
// }

// function recurse_copy($src, $dst, $exclude_dirs = array())
// {
//     $src = rtrim($src, '/');
//     $dst = rtrim($dst, '/');
//     $dir = opendir($src);
//     @mkdir($dst);
//     while (false !== ($file = readdir($dir))) {
//         if (($file != '.') && ($file != '..')) {
//             if (is_dir($src . '/' . $file)) {
//                 if (!in_array($file, $exclude_dirs)) {
//                     recurse_copy($src . '/' . $file, $dst . '/' . $file);
//                 }
//             } else {
//                 copy($src . '/' . $file, $dst . '/' . $file);
//             }
//         }
//     }
//     closedir($dir);
// }

// function init_dir($path)
// {
//     $e = error_reporting(0);

//     if (!file_exists($path)) {
//         return mkdir($path, 0777);
//     } else if (is_dir($path)) {
//         if (!is_writable($path)) {
//             return chmod($path, 0777);
//         }
//         return true;
//     }

//     error_reporting($e);
//     return false;
// }


// function rrmdir($dir)
// {
//     if (is_dir($dir)) {
//         $objects = scandir($dir);
//         foreach ($objects as $object) {
//             if ($object != "." && $object != "..") {
//                 if (filetype($dir . "/" . $object) == "dir") rrmdir($dir . "/" . $object);
//                 else unlink($dir . "/" . $object);
//             }
//         }
//         reset($objects);
//         rmdir($dir);
//     }
// }
// function mkdir_error($dir_path)
// {
//     $error_1 = 'ERROR DURING FILE COPY : CANNOT CREATE ' . $dir_path;
//     //write_log($error_1 );
//     // $error_2 = sprintf(MUCD_NETWORK_PAGE_DUPLICATE_COPY_FILE_ERROR, get_primary_upload_dir());
//     $error_2 = "";
//     //write_log($error_2 );
//     //write_log('Duplication interrupted on FILE COPY ERROR');
//     echo '<br />Duplication failed :<br /><br />' . $error_1 . '<br /><br />' . $error_2 . '<br /><br />';
//     // if( $log_url = log_url() ) {
//     //     echo '<a href="' . $log_url . '">' . MUCD_NETWORK_PAGE_DUPLICATE_VIEW_LOG . '</a>';
//     // }
//     // MUCD_Functions::remove_blog(self::$to_site_id);
//     wp_die();
// }

// function copy_data($from_site_id, $to_site_id)
// {
//     $to_site_id = $to_site_id;

//     // Copy
//     $saved_options = db_copy_tables($from_site_id, $to_site_id);
//     // Update
//     db_update_data($from_site_id, $to_site_id, $saved_options);
// }

// function db_copy_tables($from_site_id, $to_site_id)
// {
//     global $wpdb;

//     // Source Site information
//     $from_site_prefix = $wpdb->get_blog_prefix($from_site_id);                    // prefix 
//     $from_site_prefix_length = strlen($from_site_prefix);                           // prefix length

//     // Destination Site information
//     $to_site_prefix = $wpdb->get_blog_prefix($to_site_id);                        // prefix
//     $to_site_prefix_length = strlen($to_site_prefix);                               // prefix length

//     // Options that should be preserved in the new blog.
//     $saved_options = get_saved_option();
//     foreach ($saved_options as $option_name => $option_value) {
//         $saved_options[$option_name] = get_blog_option($to_site_id, $option_name);
//     }

//     // Bugfix : escape '_' , '%' and '/' character for mysql 'like' queries
//     $from_site_prefix_like = $wpdb->esc_like($from_site_prefix);

//     // SCHEMA - TO FIX for HyperDB
//     $schema = DB_NAME;

//     // Get sources Tables
//     if ($from_site_id == MUCD_PRIMARY_SITE_ID) {
//         $from_site_table = get_primary_tables($from_site_prefix);
//     } else {
//         $sql_query = $wpdb->prepare('SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = \'%s\' AND TABLE_NAME LIKE \'%s\'', $schema, $from_site_prefix_like . '%');
//         $from_site_table =  do_sql_query($sql_query, 'col');
//     }

//     foreach ($from_site_table as $table) {

//         $table_name = $to_site_prefix . substr($table, $from_site_prefix_length);

//         // Drop table if exists
//         do_sql_query('DROP TABLE IF EXISTS `' . $table_name . '`');

//         // Create new table from source table
//         do_sql_query('CREATE TABLE IF NOT EXISTS `' . $table_name . '` LIKE `' . $schema . '`.`' . $table . '`');

//         // Populate database with data from source table
//         do_sql_query('INSERT `' . $table_name . '` SELECT * FROM `' . $schema . '`.`' . $table . '`');
//     }

//     // apply key options from new blog.
//     db_restore_data($to_site_id,  $saved_options);

//     return $saved_options;
// }

// function get_primary_tables($from_site_prefix)
// {

//     $default_tables =  get_primary_tables_to_copy();

//     foreach ($default_tables as $k => $default_table) {
//         $default_tables[$k] = $from_site_prefix . $default_table;
//     }

//     return $default_tables;
// }
// function db_update_data($from_site_id, $to_site_id, $saved_options)
// {
//     global $wpdb;

//     $to_blog_prefix = $wpdb->get_blog_prefix($to_site_id);

//     // Looking for uploads dirs
//     switch_to_blog($from_site_id);
//     $dir = wp_upload_dir();
//     $from_upload_url = str_replace(network_site_url(), get_bloginfo('url') . '/', $dir['baseurl']);
//     $from_blog_url = get_blog_option($from_site_id, 'siteurl');

//     switch_to_blog($to_site_id);
//     $dir = wp_upload_dir();
//     $to_upload_url = str_replace(network_site_url(), get_bloginfo('url') . '/', $dir['baseurl']);
//     $to_blog_url = get_blog_option($to_site_id, 'siteurl');

//     restore_current_blog();

//     $tables = array();

//     // Bugfix : escape '_' , '%' and '/' character for mysql 'like' queries
//     $to_blog_prefix_like = $wpdb->esc_like($to_blog_prefix);

//     $results = do_sql_query('SHOW TABLES LIKE \'' . $to_blog_prefix_like . '%\'', 'col', FALSE);

//     foreach ($results as $k => $v) {
//         $tables[str_replace($to_blog_prefix, '', $v)] = array();
//     }

//     foreach ($tables as $table => $col) {
//         $results = do_sql_query('SHOW COLUMNS FROM `' . $to_blog_prefix . $table . '`', 'col', FALSE);

//         $columns = array();

//         foreach ($results as $k => $v) {
//             $columns[] = $v;
//         }

//         $tables[$table] = $columns;
//     }

//     $default_tables = get_fields_to_update();

//     foreach ($default_tables as $table => $field) {
//         $tables[$table] = $field;
//     }

//     $from_site_prefix = $wpdb->get_blog_prefix($from_site_id);
//     $to_site_prefix = $wpdb->get_blog_prefix($to_site_id);

//     $string_to_replace = array(
//         $from_upload_url => $to_upload_url,
//         $from_blog_url => $to_blog_url,
//         $from_site_prefix => $to_site_prefix
//     );

//     $string_to_replace = apply_filters('mucd_string_to_replace', $string_to_replace, $from_site_id, $to_site_id);

//     foreach ($tables as $table => $field) {
//         foreach ($string_to_replace as $from_string => $to_string) {
//             update($to_blog_prefix . $table, $field, $from_string, $to_string);
//         }
//     }

//     db_restore_data($to_site_id,  $saved_options);
// }

// function db_restore_data($to_site_id, $saved_options)
// {

//     switch_to_blog($to_site_id);

//     foreach ($saved_options as $option_name => $option_value) {
//         update_option($option_name, $option_value);
//     }

//     restore_current_blog();
// }

// function update($table, $fields, $from_string, $to_string)
// {
//     if (is_array($fields) || !empty($fields)) {
//         global $wpdb;

//         foreach ($fields as $field) {

//             // Bugfix : escape '_' , '%' and '/' character for mysql 'like' queries
//             $from_string_like = $wpdb->esc_like($from_string);

//             $sql_query = $wpdb->prepare('SELECT `' . $field . '` FROM `' . $table . '` WHERE `' . $field . '` LIKE "%s" ', '%' . $from_string_like . '%');
//             $results = do_sql_query($sql_query, 'results', FALSE);

//             if ($results) {
//                 $update = 'UPDATE `' . $table . '` SET `' . $field . '` = "%s" WHERE `' . $field . '` = "%s"';

//                 foreach ($results as $result => $row) {
//                     $old_value = $row[$field];
//                     $new_value = try_replace($row, $field, $from_string, $to_string);
//                     $sql_query = $wpdb->prepare($update, $new_value, $old_value);
//                     $results = do_sql_query($sql_query);
//                 }
//             }
//         }
//     }
// }

// function replace($val, $from_string, $to_string)
// {
//     $new = $val;
//     if (is_string($val)) {
//         $pos = strpos($val, $to_string);
//         if ($pos === false) {
//             $new = str_replace($from_string, $to_string, $val);
//         }
//     }
//     return $new;
// }


// function replace_recursive($val, $from_string, $to_string)
// {
//     $unset = array();
//     if (is_array($val)) {
//         foreach ($val as $k => $v) {
//             $val[$k] = try_replace($val, $k, $from_string, $to_string);
//         }
//     } else
//         $val = replace($val, $from_string, $to_string);

//     foreach ($unset as $k)
//         unset($val[$k]);

//     return $val;
// }

// function try_replace($row, $field, $from_string, $to_string)
// {
//     if (is_serialized($row[$field])) {
//         $double_serialize = FALSE;
//         $row[$field] = @unserialize($row[$field]);

//         // FOR SERIALISED OPTIONS, like in wp_carousel plugin
//         if (is_serialized($row[$field])) {
//             $row[$field] = @unserialize($row[$field]);
//             $double_serialize = TRUE;
//         }

//         if (is_array($row[$field])) {
//             $row[$field] = replace_recursive($row[$field], $from_string, $to_string);
//         } else if (is_object($row[$field]) || $row[$field] instanceof __PHP_Incomplete_Class) { // Étrange fonctionnement avec Google Sitemap...
//             $array_object = (array) $row[$field];
//             $array_object = replace_recursive($array_object, $from_string, $to_string);
//             foreach ($array_object as $key => $field) {
//                 $row[$field]->$key = $field;
//             }
//         } else {
//             $row[$field] = replace($row[$field], $from_string, $to_string);
//         }

//         $row[$field] = serialize($row[$field]);

//         // Pour des options comme wp_carousel...
//         if ($double_serialize) {
//             $row[$field] = serialize($row[$field]);
//         }
//     } else {
//         $row[$field] = replace($row[$field], $from_string, $to_string);
//     }
//     return $row[$field];
// }

// function do_sql_query($sql_query, $type = '', $log = TRUE)
// {
//     global $wpdb;
//     $wpdb->hide_errors();

//     switch ($type) {
//         case 'col':
//             $results = $wpdb->get_col($sql_query);
//             break;
//         case 'row':
//             $results = $wpdb->get_row($sql_query);
//             break;
//         case 'var':
//             $results = $wpdb->get_var($sql_query);
//             break;
//         case 'results':
//             $results = $wpdb->get_results($sql_query, ARRAY_A);
//             break;
//         default:
//             $results = $wpdb->query($sql_query);
//             break;
//     }

//     if ($log) {
//         write_log('SQL :' . $sql_query);
//         write_log('Result :' . var_export($results, true));
//     }

//     if ($wpdb->last_error != "") {
//         sql_error($sql_query, $wpdb->last_error);
//     }

//     return $results;
// }

// function sql_error($sql_query, $sql_error)
// {
//     $error_1 = 'ERROR SQL ON : ' . $sql_query;
//     // write_log($error_1 );
//     $error_2 = 'WPDB ERROR : ' . $sql_error;
//     // write_log($error_2 );
//     // write_log('Duplication interrupted on SQL ERROR');
//     echo '<br />Duplication failed :<br /><br />' . $error_1 . '<br /><br />' . $error_2 . '<br /><br />';
//     if ($log_url = log_url()) {
//         echo '<a href="' . $log_url . '">' . MUCD_NETWORK_PAGE_DUPLICATE_VIEW_LOG . '</a>';
//     }
//     remove_blog($to_site_id);
//     wp_die();
// }



// function valid_windows_dir_path($path)
// {
//     if (strpos($path, ":") == 1 && preg_match('/[a-zA-Z]/', $path[0])) // check if it's something like C:\
//     {
//         $tmp = substr($path, 2);
//         $bool = preg_match('/^[^*?"<>|:]*$/', $tmp);
//         return ($bool == 1); // so that it will return only true and false
//     }
//     return false;
// }


// function valid_unix_dir_path($path)
// {
//     $reg = "/^(\/([a-zA-Z0-9+\$_.-])+)*\/?$/";
//     $bool = preg_match($reg, $path);
//     return ($bool == 1);
// }

// function valid_path($path)
// {
//     return (valid_unix_dir_path($path) || valid_windows_dir_path($path));
// }

// function remove_blog($blog_id)
// {
//     switch_to_blog($blog_id);
//     $wp_upload_info = wp_upload_dir();
//     $dir = str_replace(' ', "\\ ", trailingslashit($wp_upload_info['basedir']));
//     restore_current_blog();

//     wpmu_delete_blog($blog_id, true);

//     // wpmu_delete_blog leaves an empty site upload directory, that we want to remove :
//     rrmdir($dir);
// }

// function is_duplicable($blog_id)
// {
//     if (get_site_option('mucd_duplicables', 'all', 'selected') == 'all') {
//         return true;
//     }

//     if (get_blog_option($blog_id, 'mucd_duplicable', 'no') == 'yes') {
//         return true;
//     }
//     return false;
// }

// function get_site_list()
// {
//     $site_list = array();
//     $network_blogs = get_sites(apply_filters('mucd_get_site_list_args', array()));
//     foreach ($network_blogs as $blog) {
//         if (is_duplicable($blog['blog_id']) && MUCD_SITE_DUPLICATION_EXCLUDE != $blog['blog_id']) {
//             $site_list[] = $blog;
//         }
//     }

//     return $site_list;
// }


// function value_in_array($value, $array, $key)
// {
//     foreach ($array as $row) {
//         if (isset($row[$key]) && $value == $row[$key]) {
//             return true;
//         }
//     }
//     return false;
// }


// function get_primary_upload_dir()
// {
//     $current_blog = get_current_blog_id();
//     switch_to_blog(MUCD_PRIMARY_SITE_ID);
//     $wp_upload_info = wp_upload_dir();
//     switch_to_blog($current_blog);

//     return $wp_upload_info['basedir'];
// }

// function site_exists($blog_id)
// {
//     return (get_blog_details($blog_id) !== false);
// }

// function set_locale_to_en_US()
// {

//     // Bugfix Pierre Dargham : relocating this declaration outside of the call to add_filter
//     // PHP < 5.3 does not accept anonymous functions
//     function mucd_locale_en_us($locale)
//     {
//         return 'en_US';
//     }

//     add_filter('locale', 'mucd_locale_en_us');
// }

// function get_network($network_id)
// {
//     global $wpdb;

//     // Load network data
//     $networks = $wpdb->get_results($wpdb->prepare(
//         "SELECT * FROM $wpdb->site WHERE id = %d",
//         $network_id
//     ));

//     if (!empty($networks)) {
//         // Only care about domain and path which are set here
//         return $networks[0];
//     }

//     return false;
// }

// function get_sites($args = array())
// {
//     if (version_compare(get_bloginfo('version'), '4.6', '>=')) {
//         $defaults = array('number' => MUCD_MAX_NUMBER_OF_SITE);
//         $args = wp_parse_args($args, $defaults);
//         $args = apply_filters('mucd_get_sites_args', $args);
//         $sites = get_sites($args);
//         foreach ($sites as $key => $site) {
//             $sites[$key] = (array) $site;
//         }
//         return $sites;
//     } else {
//         $defaults = array('limit' => MUCD_MAX_NUMBER_OF_SITE);
//         $args = apply_filters('mucd_get_sites_args', $args);
//         $args = wp_parse_args($args, $defaults);
//         return wp_get_sites($args);
//     }
// }


// function check_if_multisite()
// {
//     if (!function_exists('is_multisite') || !is_multisite()) {
//         deactivate_plugins(plugin_basename(__FILE__));
//         wp_die('multisite-clone-duplicator works only for multisite installation');
//     }
// }

// function check_if_network_admin()
// {
//     if (!is_network_admin()) {
//         deactivate_plugins(plugin_basename(__FILE__));
//         wp_die('multisite-clone-duplicator works only as multisite network-wide plugin');
//     }
// }


// function init_duplicable_option($blogs_value = "no", $network_value = "all")
// {
//     $network_blogs = get_sites();
//     foreach ($network_blogs as $blog) {
//         $blog_id = $blog['blog_id'];
//         add_blog_option($blog_id, 'mucd_duplicable', $blogs_value);
//     }
//     add_site_option('mucd_duplicables', $network_value);
// }


// function delete_duplicable_option()
// {
//     $network_blogs = get_sites();
//     foreach ($network_blogs as $blog) {
//         $blog_id = $blog['blog_id'];
//         delete_blog_option($blog_id, 'mucd_duplicable');
//     }
//     delete_site_option('mucd_duplicables');
// }

// function set_duplicable_option($blogs)
// {
//     $network_blogs = get_sites();
//     foreach ($network_blogs as $blog) {
//         if (in_array($blog['blog_id'], $blogs)) {
//             update_blog_option($blog['blog_id'], 'mucd_duplicable', "yes");
//         } else {
//             update_blog_option($blog['blog_id'], 'mucd_duplicable', "no");
//         }
//     }
// }


// function init_options()
// {
//     add_site_option('mucd_copy_files', 'yes');
//     add_site_option('mucd_keep_users', 'yes');
//     add_site_option('mucd_log', 'no');
//     $upload_dir = wp_upload_dir();
//     add_site_option('mucd_log_dir', $upload_dir['basedir'] . '/multisite-clone-duplicator-logs/');
//     add_site_option('mucd_disable_enhanced_site_select', 'no');
//     init_duplicable_option();
// }

// function delete_options()
// {
//     delete_site_option('mucd_copy_files');
//     delete_site_option('mucd_keep_users');
//     delete_site_option('mucd_log');
//     delete_site_option('mucd_log_dir');
//     delete_site_option('mucd_disable_enhanced_site_select');
//     delete_duplicable_option();
// }

// function get_option_log_directory()
// {
//     $upload_dir = wp_upload_dir();
//     return get_site_option('mucd_log_dir', $upload_dir['basedir'] . '/multisite-clone-duplicator-logs/');
// }

// function get_primary_dir_exclude()
// {
//     return array(
//         'sites',
//     );
// }


// function get_default_saved_option()
// {
//     return array(
//         'siteurl' => '',
//         'home' => '',
//         'upload_path' => '',
//         'fileupload_url' => '',
//         'upload_url_path' => '',
//         'admin_email' => '',
//         'blogname' => ''
//     );
// }

// function get_saved_option()
// {
//     return apply_filters('mucd_copy_blog_data_saved_options', get_default_saved_option());
// }


// function get_default_fields_to_update()
// {
//     return array(
//         'commentmeta' => array(),
//         'comments' => array(),
//         'links' => array('link_url', 'link_image'),
//         'options' => array('option_name', 'option_value'),
//         'postmeta' => array('meta_value'),
//         'posts' => array('post_content', 'guid'),
//         'terms' => array(),
//         'term_relationships' => array(),
//         'term_taxonomy' => array(),
//     );
// }


// function get_fields_to_update()
// {
//     return apply_filters('mucd_default_fields_to_update', get_default_fields_to_update());
// }


// function get_default_primary_tables_to_copy()
// {
//     return array(
//         'commentmeta',
//         'comments',
//         'links',
//         'options',
//         'postmeta',
//         'posts',
//         'terms',
//         'term_relationships',
//         'term_taxonomy',
//         'termmeta',
//     );
// }

// function get_primary_tables_to_copy()
// {
//     return apply_filters('mucd_default_primary_tables_to_copy', get_default_primary_tables_to_copy());
// }






?>
<?php 

  //require ( 'preparing_duplicate.php' );
// require_once("../../../../wp-load.php");
require_once(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/wp-load.php');

$log = null; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Check if the request method is POST

  // Retrieve form data
  //$name = $_POST['names'];
  //$email = $_POST['emails'];
 $data = $_POST['data'];
  //echo "HERE". var_dump($data); 
 duplicate_site($data); 
//$user_id = MUCD_Duplicate::duplicate_site($data);
  // Return a successful response
$response = array(
  'status' => 'success',
  'message' => 'AJAX request successful.',
  'data' => $data // Optional: Include additional data in the response
);

// Set the response content type to JSON
header('Content-Type: application/json');

// Output the response as JSON
echo json_encode($response);

// Terminate the PHP script
exit(); 

  // Process the data
  // ...

  // Return a response if needed
 // echo 'Form submitted successfully.';
}	


// function duplicate_site($data)
// {
//     $wpdb = $GLOBALS['wpdb'];

//     //global $wpdb;

//     $form_message = array();

//     //$wpdb->hide_errors();

//     // self::init_log($data);

//     $email = $data['email'];
//     $domain = $data['domain'];
//     $newdomain = $data['newdomain'];
//     $path = $data['path'];
//     $title = $data['title'];
//     $from_site_id = $data['from_site_id'];
//     $keep_users = $data['keep_users'];
//     $copy_file = $data['copy_files'];
//     $public = $data['public'];
//     $network_id = $data['network_id'];

//     // write_log('Start site duplication : from site ' . $from_site_id);
//     // write_log('Admin email : ' . $email);
//     // write_log('Domain : ' . $newdomain);
//     // write_log('Path : ' . $path);
//     // write_log('Site title : ' . $title);
//      $user_id = create_admin($email, $domain);
//      echo "after  user id ";

//    // var_dump($user_id);
//    // printf("switch");

//    if (is_wp_error($user_id)) {
//        $form_message['error'] = $user_id->get_error_message();
//         return $form_message;
//     }
//         echo "test 2314";


//     // Create new site

//     switch_to_blog(1);
//     $to_site_id = wpmu_create_blog($newdomain, $path, $title, $user_id, array('public' => $public), $network_id);
//     //$wpdb->show_errors();

//     if (is_wp_error($to_site_id)) {
//         $form_message['error'] = $to_site_id->get_error_message();
//         return $form_message;
//     }

//     // User rights adjustments
//     if (!is_super_admin($user_id) && !get_user_option('primary_blog', $user_id)) {
//         update_user_option($user_id, 'primary_blog', $to_site_id, true);
//     }

//     bypass_server_limit();

//     // Copy Site - File
//     if ($copy_file == 'yes') {
//         // do_action('mucd_before_copy_files', $from_site_id, $to_site_id);
//         $result = copy_files($from_site_id, $to_site_id);
//         // do_action('mucd_after_copy_files', $from_site_id, $to_site_id);
//     }

//     // Copy Site - Data
//     // do_action('mucd_before_copy_data', $from_site_id, $to_site_id);
//     $result = copy_data($from_site_id, $to_site_id);
//     // do_action('mucd_after_copy_data', $from_site_id, $to_site_id);

//     // Copy Site - Users
//     // if ($keep_users == 'yes') {
//     //     do_action('mucd_before_copy_users', $from_site_id, $to_site_id);
//     //     $result = copy_users($from_site_id, $to_site_id);
//     //     do_action('mucd_after_copy_users', $from_site_id, $to_site_id);
//     // }

//     update_blog_option($to_site_id, 'mucd_duplicable', "no");

//     $form_message['msg'] = MUCD_NETWORK_PAGE_DUPLICATE_NOTICE_CREATED;
//     $form_message['site_id'] = $to_site_id;

//     // write_log('End site duplication : new site ID = ' . $to_site_id);

//     wp_cache_flush();

//     return $form_message;
// }

// function create_admin($email, $domain)
// {
//     // Create New site Admin if not exists
//     $password = 'N/A';
//     $user_id = email_exists($email);
//     echo "after email exists"; 

//     if (!$user_id) { // Create a new user with a random password
//         var_dump($domain);
//         $password = wp_generate_password(12, false);
//         $user_id = wpmu_create_user($domain, $password, $email);
//         var_dump($user_id);
//         if (false == $user_id) {
//             return new WP_Error('file_copy', MUCD_NETWORK_PAGE_DUPLICATE_ADMIN_ERROR_CREATE_USER);
//         } else {
//             add_user_meta($user_id, 'first_name', "Marko");
//             add_user_meta($user_id, 'last_name', "Markovic");
//             wp_new_user_notification($user_id, $password);
//         }
//     }

//     return $user_id;
// }
// function copy_users($from_site_id, $to_site_id)
// {

//     global $wpdb;

//     // Bugfix Pierre Dargham : relocating this declaration outside of the loop
//     // PHP < 5.3
//     function user_array_map($a)
//     {
//         return $a[0];
//     }

//     if (is_main_site($from_site_id)) {
//         $is_from_main_site = true;
//         $args = array('fields' => 'ids');
//         $all_sites_ids = get_sites($args);
//         if (!empty($all_sites_ids)) {
//             $all_sites_ids = array_map('user_array_map', $all_sites_ids);
//         }
//     } else {
//         $is_from_main_site = false;
//     }

//     // Source Site information
//     $from_site_prefix = $wpdb->get_blog_prefix($from_site_id);                    // prefix 
//     $from_site_prefix_length = strlen($from_site_prefix);                           // prefix length

//     // Destination Site information
//     $to_site_prefix = $wpdb->get_blog_prefix($to_site_id);                        // prefix
//     $to_site_prefix_length = strlen($to_site_prefix);

//     $users = get_users('blog_id=' . $from_site_id);

//     $admin_email = get_blog_option($to_site_id, 'admin_email', 'false');

//     switch_to_blog($to_site_id);

//     foreach ($users as $user) {
//         if ($user->user_email != $admin_email) {

//             add_user_to_blog($to_site_id, $user->ID, 'subscriber');

//             $all_meta = array_map('user_array_map', get_user_meta($user->ID));

//             foreach ($all_meta as $metakey => $metavalue) {
//                 $prefix = substr($metakey, 0, $from_site_prefix_length);
//                 if ($prefix == $from_site_prefix) {
//                     $raw_meta_name = substr($metakey, $from_site_prefix_length);
//                     if ($is_from_main_site) {
//                         $parts = explode('_', $raw_meta_name, 2);
//                         if (count($parts) > 1 && in_array($parts[0], $all_sites_ids)) {
//                             continue;
//                         }
//                     }
//                     update_user_meta($user->ID, $to_site_prefix . $raw_meta_name, maybe_unserialize($metavalue));
//                 }
//             }
//         }
//     }

//     restore_current_blog();
// }
	
// function init_log($data)
// {
//     // INIT LOG AND SAVE OPTION
//     if (isset($data['log']) && $data['log'] == 'yes') {
//         if (isset($data['log-path']) && !empty($data['log-path'])) {
//             $log_name = @date('Y_m_d_His') . '-' . $data['domain'] . '.log';
//             if (substr($data['log-path'], -1) != "/") {
//                 $data['log-path'] = $data['log-path'] . '/';
//             }
//             // $log = new MUCD_Log(true, $data['log-path'], $log_name);
//         }
//     } else {
//         // $log = new MUCD_Log(false);
//     }
// }

// function bypass_server_limit()
// {
//     @ini_set('memory_limit', '5024M');
//     @ini_set('max_execution_time', '0');
// }

// function copy_files($from_site_id, $to_site_id)
// {
//     // Switch to Source site and get uploads info
//     switch_to_blog($from_site_id);
//     $wp_upload_info = wp_upload_dir();
//     $from_dir['path'] = str_replace(' ', "\\ ", trailingslashit($wp_upload_info['basedir']));
//     $from_site_id == MUCD_PRIMARY_SITE_ID ? $from_dir['exclude'] = get_primary_dir_exclude() :  $from_dir['exclude'] = array();

//     // Switch to Destination site and get uploads info
//     switch_to_blog($to_site_id);
//     $wp_upload_info = wp_upload_dir();
//     $to_dir = str_replace(' ', "\\ ", trailingslashit($wp_upload_info['basedir']));

//     restore_current_blog();

//     $dirs = array();
//     $dirs[] = array(
//         'from_dir_path' => $from_dir['path'],
//         'to_dir_path'   => $to_dir,
//         'exclude_dirs'  => $from_dir['exclude'],
//     );

//     $dirs = apply_filters('mucd_copy_dirs', $dirs, $from_site_id, $to_site_id);

//     foreach ($dirs as $dir) {
//         if (isset($dir['to_dir_path']) && !init_dir($dir['to_dir_path'])) {
//             mkdir_error($dir['to_dir_path']);
//         }
//         // write_log('Copy files from ' . $dir['from_dir_path'] . ' to ' . $dir['to_dir_path']);
//         recurse_copy($dir['from_dir_path'], $dir['to_dir_path'], $dir['exclude_dirs']);
//     }

//     return true;
// }

// function recurse_copy($src, $dst, $exclude_dirs = array())
// {
//     $src = rtrim($src, '/');
//     $dst = rtrim($dst, '/');
//     $dir = opendir($src);
//     @mkdir($dst);
//     while (false !== ($file = readdir($dir))) {
//         if (($file != '.') && ($file != '..')) {
//             if (is_dir($src . '/' . $file)) {
//                 if (!in_array($file, $exclude_dirs)) {
//                     recurse_copy($src . '/' . $file, $dst . '/' . $file);
//                 }
//             } else {
//                 copy($src . '/' . $file, $dst . '/' . $file);
//             }
//         }
//     }
//     closedir($dir);
// }

// function init_dir($path)
// {
//     $e = error_reporting(0);

//     if (!file_exists($path)) {
//         return mkdir($path, 0777);
//     } else if (is_dir($path)) {
//         if (!is_writable($path)) {
//             return chmod($path, 0777);
//         }
//         return true;
//     }

//     error_reporting($e);
//     return false;
// }


// function rrmdir($dir)
// {
//     if (is_dir($dir)) {
//         $objects = scandir($dir);
//         foreach ($objects as $object) {
//             if ($object != "." && $object != "..") {
//                 if (filetype($dir . "/" . $object) == "dir") rrmdir($dir . "/" . $object);
//                 else unlink($dir . "/" . $object);
//             }
//         }
//         reset($objects);
//         rmdir($dir);
//     }
// }
// function mkdir_error($dir_path)
// {
//     $error_1 = 'ERROR DURING FILE COPY : CANNOT CREATE ' . $dir_path;
//     //write_log($error_1 );
//     // $error_2 = sprintf(MUCD_NETWORK_PAGE_DUPLICATE_COPY_FILE_ERROR, get_primary_upload_dir());
//     $error_2 = "";
//     //write_log($error_2 );
//     //write_log('Duplication interrupted on FILE COPY ERROR');
//     echo '<br />Duplication failed :<br /><br />' . $error_1 . '<br /><br />' . $error_2 . '<br /><br />';
//     // if( $log_url = log_url() ) {
//     //     echo '<a href="' . $log_url . '">' . MUCD_NETWORK_PAGE_DUPLICATE_VIEW_LOG . '</a>';
//     // }
//     // MUCD_Functions::remove_blog(self::$to_site_id);
//     wp_die();
// }

// function copy_data($from_site_id, $to_site_id)
// {
//     $to_site_id = $to_site_id;

//     // Copy
//     $saved_options = db_copy_tables($from_site_id, $to_site_id);
//     // Update
//     db_update_data($from_site_id, $to_site_id, $saved_options);
// }

// function db_copy_tables($from_site_id, $to_site_id)
// {
//     global $wpdb;

//     // Source Site information
//     $from_site_prefix = $wpdb->get_blog_prefix($from_site_id);                    // prefix 
//     $from_site_prefix_length = strlen($from_site_prefix);                           // prefix length

//     // Destination Site information
//     $to_site_prefix = $wpdb->get_blog_prefix($to_site_id);                        // prefix
//     $to_site_prefix_length = strlen($to_site_prefix);                               // prefix length

//     // Options that should be preserved in the new blog.
//     $saved_options = get_saved_option();
//     foreach ($saved_options as $option_name => $option_value) {
//         $saved_options[$option_name] = get_blog_option($to_site_id, $option_name);
//     }

//     // Bugfix : escape '_' , '%' and '/' character for mysql 'like' queries
//     $from_site_prefix_like = $wpdb->esc_like($from_site_prefix);

//     // SCHEMA - TO FIX for HyperDB
//     $schema = DB_NAME;

//     // Get sources Tables
//     if ($from_site_id == MUCD_PRIMARY_SITE_ID) {
//         $from_site_table = get_primary_tables($from_site_prefix);
//     } else {
//         $sql_query = $wpdb->prepare('SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = \'%s\' AND TABLE_NAME LIKE \'%s\'', $schema, $from_site_prefix_like . '%');
//         $from_site_table =  do_sql_query($sql_query, 'col');
//     }

//     foreach ($from_site_table as $table) {

//         $table_name = $to_site_prefix . substr($table, $from_site_prefix_length);

//         // Drop table if exists
//         do_sql_query('DROP TABLE IF EXISTS `' . $table_name . '`');

//         // Create new table from source table
//         do_sql_query('CREATE TABLE IF NOT EXISTS `' . $table_name . '` LIKE `' . $schema . '`.`' . $table . '`');

//         // Populate database with data from source table
//         do_sql_query('INSERT `' . $table_name . '` SELECT * FROM `' . $schema . '`.`' . $table . '`');
//     }

//     // apply key options from new blog.
//     db_restore_data($to_site_id,  $saved_options);

//     return $saved_options;
// }

// function get_primary_tables($from_site_prefix)
// {

//     $default_tables =  get_primary_tables_to_copy();

//     foreach ($default_tables as $k => $default_table) {
//         $default_tables[$k] = $from_site_prefix . $default_table;
//     }

//     return $default_tables;
// }
// function db_update_data($from_site_id, $to_site_id, $saved_options)
// {
//     global $wpdb;

//     $to_blog_prefix = $wpdb->get_blog_prefix($to_site_id);

//     // Looking for uploads dirs
//     switch_to_blog($from_site_id);
//     $dir = wp_upload_dir();
//     $from_upload_url = str_replace(network_site_url(), get_bloginfo('url') . '/', $dir['baseurl']);
//     $from_blog_url = get_blog_option($from_site_id, 'siteurl');

//     switch_to_blog($to_site_id);
//     $dir = wp_upload_dir();
//     $to_upload_url = str_replace(network_site_url(), get_bloginfo('url') . '/', $dir['baseurl']);
//     $to_blog_url = get_blog_option($to_site_id, 'siteurl');

//     restore_current_blog();

//     $tables = array();

//     // Bugfix : escape '_' , '%' and '/' character for mysql 'like' queries
//     $to_blog_prefix_like = $wpdb->esc_like($to_blog_prefix);

//     $results = do_sql_query('SHOW TABLES LIKE \'' . $to_blog_prefix_like . '%\'', 'col', FALSE);

//     foreach ($results as $k => $v) {
//         $tables[str_replace($to_blog_prefix, '', $v)] = array();
//     }

//     foreach ($tables as $table => $col) {
//         $results = do_sql_query('SHOW COLUMNS FROM `' . $to_blog_prefix . $table . '`', 'col', FALSE);

//         $columns = array();

//         foreach ($results as $k => $v) {
//             $columns[] = $v;
//         }

//         $tables[$table] = $columns;
//     }

//     $default_tables = get_fields_to_update();

//     foreach ($default_tables as $table => $field) {
//         $tables[$table] = $field;
//     }

//     $from_site_prefix = $wpdb->get_blog_prefix($from_site_id);
//     $to_site_prefix = $wpdb->get_blog_prefix($to_site_id);

//     $string_to_replace = array(
//         $from_upload_url => $to_upload_url,
//         $from_blog_url => $to_blog_url,
//         $from_site_prefix => $to_site_prefix
//     );

//     $string_to_replace = apply_filters('mucd_string_to_replace', $string_to_replace, $from_site_id, $to_site_id);

//     foreach ($tables as $table => $field) {
//         foreach ($string_to_replace as $from_string => $to_string) {
//             update($to_blog_prefix . $table, $field, $from_string, $to_string);
//         }
//     }

//     db_restore_data($to_site_id,  $saved_options);
// }

// function db_restore_data($to_site_id, $saved_options)
// {

//     switch_to_blog($to_site_id);

//     foreach ($saved_options as $option_name => $option_value) {
//         update_option($option_name, $option_value);
//     }

//     restore_current_blog();
// }

// function update($table, $fields, $from_string, $to_string)
// {
//     if (is_array($fields) || !empty($fields)) {
//         global $wpdb;

//         foreach ($fields as $field) {

//             // Bugfix : escape '_' , '%' and '/' character for mysql 'like' queries
//             $from_string_like = $wpdb->esc_like($from_string);

//             $sql_query = $wpdb->prepare('SELECT `' . $field . '` FROM `' . $table . '` WHERE `' . $field . '` LIKE "%s" ', '%' . $from_string_like . '%');
//             $results = do_sql_query($sql_query, 'results', FALSE);

//             if ($results) {
//                 $update = 'UPDATE `' . $table . '` SET `' . $field . '` = "%s" WHERE `' . $field . '` = "%s"';

//                 foreach ($results as $result => $row) {
//                     $old_value = $row[$field];
//                     $new_value = try_replace($row, $field, $from_string, $to_string);
//                     $sql_query = $wpdb->prepare($update, $new_value, $old_value);
//                     $results = do_sql_query($sql_query);
//                 }
//             }
//         }
//     }
// }

// function replace($val, $from_string, $to_string)
// {
//     $new = $val;
//     if (is_string($val)) {
//         $pos = strpos($val, $to_string);
//         if ($pos === false) {
//             $new = str_replace($from_string, $to_string, $val);
//         }
//     }
//     return $new;
// }


// function replace_recursive($val, $from_string, $to_string)
// {
//     $unset = array();
//     if (is_array($val)) {
//         foreach ($val as $k => $v) {
//             $val[$k] = try_replace($val, $k, $from_string, $to_string);
//         }
//     } else
//         $val = replace($val, $from_string, $to_string);

//     foreach ($unset as $k)
//         unset($val[$k]);

//     return $val;
// }

// function try_replace($row, $field, $from_string, $to_string)
// {
//     if (is_serialized($row[$field])) {
//         $double_serialize = FALSE;
//         $row[$field] = @unserialize($row[$field]);

//         // FOR SERIALISED OPTIONS, like in wp_carousel plugin
//         if (is_serialized($row[$field])) {
//             $row[$field] = @unserialize($row[$field]);
//             $double_serialize = TRUE;
//         }

//         if (is_array($row[$field])) {
//             $row[$field] = replace_recursive($row[$field], $from_string, $to_string);
//         } else if (is_object($row[$field]) || $row[$field] instanceof __PHP_Incomplete_Class) { // Étrange fonctionnement avec Google Sitemap...
//             $array_object = (array) $row[$field];
//             $array_object = replace_recursive($array_object, $from_string, $to_string);
//             foreach ($array_object as $key => $field) {
//                 $row[$field]->$key = $field;
//             }
//         } else {
//             $row[$field] = replace($row[$field], $from_string, $to_string);
//         }

//         $row[$field] = serialize($row[$field]);

//         // Pour des options comme wp_carousel...
//         if ($double_serialize) {
//             $row[$field] = serialize($row[$field]);
//         }
//     } else {
//         $row[$field] = replace($row[$field], $from_string, $to_string);
//     }
//     return $row[$field];
// }

// function do_sql_query($sql_query, $type = '', $log = TRUE)
// {
//     global $wpdb;
//     $wpdb->hide_errors();

//     switch ($type) {
//         case 'col':
//             $results = $wpdb->get_col($sql_query);
//             break;
//         case 'row':
//             $results = $wpdb->get_row($sql_query);
//             break;
//         case 'var':
//             $results = $wpdb->get_var($sql_query);
//             break;
//         case 'results':
//             $results = $wpdb->get_results($sql_query, ARRAY_A);
//             break;
//         default:
//             $results = $wpdb->query($sql_query);
//             break;
//     }

//     if ($log) {
//         write_log('SQL :' . $sql_query);
//         write_log('Result :' . var_export($results, true));
//     }

//     if ($wpdb->last_error != "") {
//         sql_error($sql_query, $wpdb->last_error);
//     }

//     return $results;
// }

// function sql_error($sql_query, $sql_error)
// {
//     $error_1 = 'ERROR SQL ON : ' . $sql_query;
//     // write_log($error_1 );
//     $error_2 = 'WPDB ERROR : ' . $sql_error;
//     // write_log($error_2 );
//     // write_log('Duplication interrupted on SQL ERROR');
//     echo '<br />Duplication failed :<br /><br />' . $error_1 . '<br /><br />' . $error_2 . '<br /><br />';
//     if ($log_url = log_url()) {
//         echo '<a href="' . $log_url . '">' . MUCD_NETWORK_PAGE_DUPLICATE_VIEW_LOG . '</a>';
//     }
//     remove_blog($to_site_id);
//     wp_die();
// }



// function valid_windows_dir_path($path)
// {
//     if (strpos($path, ":") == 1 && preg_match('/[a-zA-Z]/', $path[0])) // check if it's something like C:\
//     {
//         $tmp = substr($path, 2);
//         $bool = preg_match('/^[^*?"<>|:]*$/', $tmp);
//         return ($bool == 1); // so that it will return only true and false
//     }
//     return false;
// }


// function valid_unix_dir_path($path)
// {
//     $reg = "/^(\/([a-zA-Z0-9+\$_.-])+)*\/?$/";
//     $bool = preg_match($reg, $path);
//     return ($bool == 1);
// }

// function valid_path($path)
// {
//     return (valid_unix_dir_path($path) || valid_windows_dir_path($path));
// }

// function remove_blog($blog_id)
// {
//     switch_to_blog($blog_id);
//     $wp_upload_info = wp_upload_dir();
//     $dir = str_replace(' ', "\\ ", trailingslashit($wp_upload_info['basedir']));
//     restore_current_blog();

//     wpmu_delete_blog($blog_id, true);

//     // wpmu_delete_blog leaves an empty site upload directory, that we want to remove :
//     rrmdir($dir);
// }

// function is_duplicable($blog_id)
// {
//     if (get_site_option('mucd_duplicables', 'all', 'selected') == 'all') {
//         return true;
//     }

//     if (get_blog_option($blog_id, 'mucd_duplicable', 'no') == 'yes') {
//         return true;
//     }
//     return false;
// }

// function get_site_list()
// {
//     $site_list = array();
//     $network_blogs = get_sites(apply_filters('mucd_get_site_list_args', array()));
//     foreach ($network_blogs as $blog) {
//         if (is_duplicable($blog['blog_id']) && MUCD_SITE_DUPLICATION_EXCLUDE != $blog['blog_id']) {
//             $site_list[] = $blog;
//         }
//     }

//     return $site_list;
// }


// function value_in_array($value, $array, $key)
// {
//     foreach ($array as $row) {
//         if (isset($row[$key]) && $value == $row[$key]) {
//             return true;
//         }
//     }
//     return false;
// }


// function get_primary_upload_dir()
// {
//     $current_blog = get_current_blog_id();
//     switch_to_blog(MUCD_PRIMARY_SITE_ID);
//     $wp_upload_info = wp_upload_dir();
//     switch_to_blog($current_blog);

//     return $wp_upload_info['basedir'];
// }

// function site_exists($blog_id)
// {
//     return (get_blog_details($blog_id) !== false);
// }

// function set_locale_to_en_US()
// {

//     // Bugfix Pierre Dargham : relocating this declaration outside of the call to add_filter
//     // PHP < 5.3 does not accept anonymous functions
//     function mucd_locale_en_us($locale)
//     {
//         return 'en_US';
//     }

//     add_filter('locale', 'mucd_locale_en_us');
// }

// function get_network($network_id)
// {
//     global $wpdb;

//     // Load network data
//     $networks = $wpdb->get_results($wpdb->prepare(
//         "SELECT * FROM $wpdb->site WHERE id = %d",
//         $network_id
//     ));

//     if (!empty($networks)) {
//         // Only care about domain and path which are set here
//         return $networks[0];
//     }

//     return false;
// }

// function get_sites($args = array())
// {
//     if (version_compare(get_bloginfo('version'), '4.6', '>=')) {
//         $defaults = array('number' => MUCD_MAX_NUMBER_OF_SITE);
//         $args = wp_parse_args($args, $defaults);
//         $args = apply_filters('mucd_get_sites_args', $args);
//         $sites = get_sites($args);
//         foreach ($sites as $key => $site) {
//             $sites[$key] = (array) $site;
//         }
//         return $sites;
//     } else {
//         $defaults = array('limit' => MUCD_MAX_NUMBER_OF_SITE);
//         $args = apply_filters('mucd_get_sites_args', $args);
//         $args = wp_parse_args($args, $defaults);
//         return wp_get_sites($args);
//     }
// }


// function check_if_multisite()
// {
//     if (!function_exists('is_multisite') || !is_multisite()) {
//         deactivate_plugins(plugin_basename(__FILE__));
//         wp_die('multisite-clone-duplicator works only for multisite installation');
//     }
// }

// function check_if_network_admin()
// {
//     if (!is_network_admin()) {
//         deactivate_plugins(plugin_basename(__FILE__));
//         wp_die('multisite-clone-duplicator works only as multisite network-wide plugin');
//     }
// }


// function init_duplicable_option($blogs_value = "no", $network_value = "all")
// {
//     $network_blogs = get_sites();
//     foreach ($network_blogs as $blog) {
//         $blog_id = $blog['blog_id'];
//         add_blog_option($blog_id, 'mucd_duplicable', $blogs_value);
//     }
//     add_site_option('mucd_duplicables', $network_value);
// }


// function delete_duplicable_option()
// {
//     $network_blogs = get_sites();
//     foreach ($network_blogs as $blog) {
//         $blog_id = $blog['blog_id'];
//         delete_blog_option($blog_id, 'mucd_duplicable');
//     }
//     delete_site_option('mucd_duplicables');
// }

// function set_duplicable_option($blogs)
// {
//     $network_blogs = get_sites();
//     foreach ($network_blogs as $blog) {
//         if (in_array($blog['blog_id'], $blogs)) {
//             update_blog_option($blog['blog_id'], 'mucd_duplicable', "yes");
//         } else {
//             update_blog_option($blog['blog_id'], 'mucd_duplicable', "no");
//         }
//     }
// }


// function init_options()
// {
//     add_site_option('mucd_copy_files', 'yes');
//     add_site_option('mucd_keep_users', 'yes');
//     add_site_option('mucd_log', 'no');
//     $upload_dir = wp_upload_dir();
//     add_site_option('mucd_log_dir', $upload_dir['basedir'] . '/multisite-clone-duplicator-logs/');
//     add_site_option('mucd_disable_enhanced_site_select', 'no');
//     init_duplicable_option();
// }

// function delete_options()
// {
//     delete_site_option('mucd_copy_files');
//     delete_site_option('mucd_keep_users');
//     delete_site_option('mucd_log');
//     delete_site_option('mucd_log_dir');
//     delete_site_option('mucd_disable_enhanced_site_select');
//     delete_duplicable_option();
// }

// function get_option_log_directory()
// {
//     $upload_dir = wp_upload_dir();
//     return get_site_option('mucd_log_dir', $upload_dir['basedir'] . '/multisite-clone-duplicator-logs/');
// }

// function get_primary_dir_exclude()
// {
//     return array(
//         'sites',
//     );
// }


// function get_default_saved_option()
// {
//     return array(
//         'siteurl' => '',
//         'home' => '',
//         'upload_path' => '',
//         'fileupload_url' => '',
//         'upload_url_path' => '',
//         'admin_email' => '',
//         'blogname' => ''
//     );
// }

// function get_saved_option()
// {
//     return apply_filters('mucd_copy_blog_data_saved_options', get_default_saved_option());
// }


// function get_default_fields_to_update()
// {
//     return array(
//         'commentmeta' => array(),
//         'comments' => array(),
//         'links' => array('link_url', 'link_image'),
//         'options' => array('option_name', 'option_value'),
//         'postmeta' => array('meta_value'),
//         'posts' => array('post_content', 'guid'),
//         'terms' => array(),
//         'term_relationships' => array(),
//         'term_taxonomy' => array(),
//     );
// }


// function get_fields_to_update()
// {
//     return apply_filters('mucd_default_fields_to_update', get_default_fields_to_update());
// }


// function get_default_primary_tables_to_copy()
// {
//     return array(
//         'commentmeta',
//         'comments',
//         'links',
//         'options',
//         'postmeta',
//         'posts',
//         'terms',
//         'term_relationships',
//         'term_taxonomy',
//         'termmeta',
//     );
// }

// function get_primary_tables_to_copy()
// {
//     return apply_filters('mucd_default_primary_tables_to_copy', get_default_primary_tables_to_copy());
// }






?>
