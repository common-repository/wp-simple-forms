<?php
/*
Plugin Name: WP Simple Forms
Plugin URI: http://crossdistinction.com/wp-simple-forms
Description: Adding forms to a webpage has never been easier.  Quickly create dropdowns, checkboxes, multiple choice, and text questions for any page on your site.
Version: 0.1.4
Author: Pat Brown
Author URI: http://crossdistinction.com
License: GPL2
*/
	if(!function_exists('get_option')) {
		require_once('../../../wp-blog-header.php');
	}

	include('functions.php');
	include('Controller.php');

	include('shortcode.php');
	
	//install db
	global $wpsf_db_version;
	$wpsf_db_version = "0.40";
	
	function wpsf_install() {
		//echo 'read';
		global $wpdb;
		global $wpsf_db_version;

		$installed_ver = get_option( "wpsf_db_version" );
 
		add_option("wpsf_db_version", $wpsf_db_version);
			
		if( $installed_ver != $wpsf_db_version ) {
			$table_name = $wpdb->prefix . "wpsf_question";
			$sql = "CREATE TABLE $table_name (
			  id int(11) NOT NULL AUTO_INCREMENT,
			  template_id int(11) NOT NULL,
			  title varchar(300) NOT NULL,
			  help_text varchar(100) NOT NULL,
			  type varchar(20) NOT NULL,
			  is_required varchar(10) NOT NULL DEFAULT 'false',
			  q_index int(11) NOT NULL,
			  PRIMARY KEY  (id)
			);";
			$table_name = $wpdb->prefix . "wpsf_question_option";
			$sql .= "CREATE TABLE $table_name (
			  id int(11) NOT NULL AUTO_INCREMENT,
			  question_id int(11) NOT NULL,
			  value varchar(50) NOT NULL,
			  PRIMARY KEY  (id)
			);";
			$table_name = $wpdb->prefix . "wpsf_template";
			$sql .="CREATE TABLE $table_name (
			  id int(11) NOT NULL AUTO_INCREMENT,
			  name varchar(50) NOT NULL,
			  PRIMARY KEY  (id)
			);";
			$table_name = $wpdb->prefix . "wpsf_attendee_custom_question";
			$sql .="CREATE TABLE $table_name (
			  id int(11) NOT NULL AUTO_INCREMENT,
			  name varchar(50) NOT NULL,
			  value varchar(300) NOT NULL,
			  charge_id varchar(50) NOT NULL,
			  PRIMARY KEY  (id)
			);";
			
			if(!function_exists('dbDelta')) {
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			}
			
			dbDelta($sql);
	 
			update_option("wpsf_db_version", $wpsf_db_version);
		}

	}
	
	register_activation_hook(__FILE__,'wpsf_install');