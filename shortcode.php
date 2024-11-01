<?php
function wpsf_showform($atts) {
	global $wpdb;
	$entries_table;
	include_once('helpers/email_helper.php');
	$q_control = new SimpleForm_controller();
    extract(shortcode_atts(array(  
        "name" => null,
        "id" =>null,
    ), $atts));  
	//set styles and scripts
	
	if( is_null($name && $id) ){
		return;
	}
	//check if form was already submitted
	if(count($_POST) > 0){
		if($q_control->validate_form_input()){
			//form has validated
			echo get_option('simpleform-confirmation-message');
			$entries_table = $q_control->create_table();
			$q_control->send_entries($entries_table);
			return;
		}else{
			echo '<p class="cd-error">Error: Please make sure all required fields are filled in.</p>';
		}
	}
	$questions = $q_control->get_all($name);
	if(isset($questions)){
		$form = $q_control->new_form($questions);
		echo $form->open('');
		echo $form->contents();
		echo $form->submit();
		echo $form->close();
	}
	
	//enqueue style
	wp_enqueue_style( 
     'simpleForm-style'
    , plugins_url('/wp-simple-forms/style.css')
);
	
}	//return $all_events;  
add_shortcode("simpleform", "wpsf_showform");
