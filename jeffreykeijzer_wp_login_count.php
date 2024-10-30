<?php
/**
 * Plugin Name: Jeffrey Keijzer WP Login Count
 * Plugin URI: http://jeffreykeijzer.com/jeffreys-login-count-wordpress
 * Description: This plugin Counts the amount of logins per user and shows the last login date.
 * Version: 1.0
 * Author: Jeffrey Keijzer
 * Author URI: http://jeffreykeijzer.com
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 *  Copyright 2014 Jeffrey Keijzer  (email : plugin@jeffreykeijzer.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 */

session_start();

//--> Logout
add_action( 'wp_logout', 'jeffreykeijzer_login_post_logout');
function jeffreykeijzer_login_post_logout(){
	$_SESSION['jeffreykeijzer_logger_count'] = NULL;
}

//--> Trigger on Swich
add_action( 'set_current_user', 'jeffreykeijzer_login_pre_log');
function jeffreykeijzer_login_pre_log(){
	global $current_user;
	if(empty($_SESSION['jeffreykeijzer_logger_count']) && $current_user->ID > 0 && is_user_logged_in()){
		jeffreykeijzer_login_log();
	}
}

//--> Exec Log
function jeffreykeijzer_login_log(){
	//--> Vars
	$_SESSION['jeffreykeijzer_logger_count'] = 1;
	global $wpdb;
	global $current_user;
	get_currentuserinfo();
	
	//--> Install Table if needed
	$table_name = $wpdb->prefix."jk_login_log";
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		$sql = 'CREATE TABLE '.$wpdb->prefix.'jk_login_log(
		user_id INTEGER NOT NULL,
		count INTEGER NOT NULL,
		date DATE,
		PRIMARY KEY (user_id))';
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}
	//--> DB Stuff	
	$logInfo = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix."jk_login_log WHERE user_id='".$current_user->ID."'" );
	if($current_user->ID > 0){
		if(empty($logInfo)){
			$wpdb->insert($wpdb->prefix.'jk_login_log', array('user_id' => $current_user->ID, 'count' => 1, 'date' => current_time('mysql', 1)),	array('%d', '%d', '%s'));
		}else{
			$wpdb->update($wpdb->prefix.'jk_login_log', array('user_id' => $current_user->ID, 'count' => ($logInfo[0]->count + 1), 'date' => current_time('mysql', 1)), array( 'user_id' => $current_user->ID ), array('%d', '%d', '%s'), array( '%d' ));
		}
	}
}

//--> Display Columns
add_filter('manage_users_columns', 'jeffreykeijzer_add_user_id_column');
function jeffreykeijzer_add_user_id_column($columns) {
	$columns['user_login_count']	= 'Login Count';
	$columns['user_login_date']	= 'Login Date';
	return $columns;
}

//--> Update Numbers
add_action('manage_users_custom_column',  'jeffreykeijzer_show_user_id_column_content', 10, 3);
function jeffreykeijzer_show_user_id_column_content($value, $column_name, $user_id) {
	global $wpdb;
	$user = get_userdata( $user_id );
	
	$logInfo = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix."jk_login_log WHERE user_id='".$user->ID."'" );
	if(empty($logInfo)){ $counter = 0; }else{ $counter = $logInfo[0]->count; }
	if(empty($logInfo)){ $dater = 'No Login'; }else{ $dater = $logInfo[0]->date; }
	
	if ( 'user_login_count' == $column_name ) return $counter;
	if ( 'user_login_date' == $column_name ) return $dater;
    return $value;
}