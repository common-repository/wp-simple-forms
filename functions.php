<?php

//show settings link after plugin activation
function wpsf_action_links($links, $file) {
    static $my_plugin;
    if (!$my_plugin) {
        //$my_plugin = plugin_basename(__FILE__);
        $my_plugin = "wp_paid_events/wp_paid_events.php";
    }
    if ($file == $my_plugin) {
        $settings_link = '<a href="options-general.php?page=wp-paid-events-settings">Settings</a>';
        array_unshift($links, $settings_link);
    }
    return $links;
}

add_filter('plugin_action_links', 'wpsf_action_links', 10, 2);

function wpsf_admin_menu() {  
    $page = add_menu_page("WP Simple Forms", "WP Simple Forms", 'manage_options', "wpsf-settings", "wpsf_settings");  
	//Using registered $page handle to hook stylesheet loading
	add_action('admin_print_styles-'. $page, 'wpsf_admin_styles');
}  
function wpsf_settings(){
	include('wpsf_settings.php');
}
add_action('admin_menu', 'wpsf_admin_menu');  
add_action('admin_init', 'wpsf_admin_init');

function wpsf_admin_init(){
	// Register our stylesheets
	wp_register_style('wpsfNotify', 'http://code.crossdistinction.com/css/notify.css');
	wp_register_style('wpsfjQueryUI', plugins_url('plugins/jquery-ui/css/ui-lightness/jquery-ui-1.8.21.custom.css', __FILE__));
	wp_register_style('wpsf-admin-style', plugins_url('admin-style.css', __FILE__));

	//Register our scripts
	wp_enqueue_script( 
	     "jquery"
	    ,''
	);
	wp_enqueue_script( 
	     "jquery-ui-core"
	    ,''
	    ,array('jquery')
	);
	wp_enqueue_script( 
	     "jquery-ui-widget"
	    ,''
	    ,array('jquery')
	);
	wp_enqueue_script( 
	     "jquery-ui-mouse"
	    ,''
	    ,array('jquery')
	);
	wp_enqueue_script( 
	     "jquery-ui-sortable"
	    ,''
	    ,array('jquery')
	);
	wp_enqueue_script( 
	     "cd-deletable"
	    ,plugins_url('/wp-simple-forms/plugins/deletable.js')
	    ,array('jquery')
		,''
		, true
	);
	wp_enqueue_script( 
	     "cd-notify"
	    ,plugins_url('/wp-simple-forms/js/notify.js')
	    ,array('jquery')
	);
	wp_enqueue_script( 
	     "cd-form-elements"
	    ,plugins_url('/wp-simple-forms/js/form.elements.js')
	    ,array('jquery')
	);
}
function wpsf_admin_styles(){
	//Called only on plugin admin page
	wp_enqueue_style('wpsfNotify');
	wp_enqueue_style('wpsfjQueryUI');
	wp_enqueue_style('wpsf-admin-style');
}

include_once('ajax.php');

