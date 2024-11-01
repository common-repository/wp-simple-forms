<?php


add_action('wp_ajax_saveAnswers', 'save_anwsers');
add_action('wp_ajax_nopriv_saveAnswers', 'save_anwsers');
function save_anwsers(){
	global $wpdb;
	//header( "Content-Type: application/json");
	//echo json_encode('started');
    $firstName = $_POST["firstName"];
    $lastName = $_POST["lastName"];
    $email = $_POST["email"];
    $event_ids = $_POST["event_ids"];
	$event_qs = $_POST['event_quantities'];
	$charge_id = $_POST['charge_id'];
	$event_prices = $_POST['paid'];
	$custom_question_answers = $_POST['questions'];

	//print_r($custom_question_answers);
	//insert attendee
	$table_name = $wpdb->prefix . "wpsf_attendees";
	//echo 'about to insert';
    $rows_affected = $wpdb->insert( $table_name, array( 'first_name' => $firstName, 'last_name' => $lastName, 'email' => $email ) );
   	//echo 'rows affect';
   //	print_r($rows_affected);
    //register all events attendee is purchasing
    $attendee_id = $wpdb->insert_id;
	$table_name = $wpdb->prefix . "wpsf_events_assoc";
	
	//event ids and quantites should match up
	for($i=0;$i < count($event_ids); $i++) {
   		$rows_affected = $wpdb->insert( $table_name, array( 'attendee_id' => $attendee_id, 'custom_post_id' => $event_ids[$i], 'tickets_purchased' => $event_qs[$i], 'total_paid' => $event_prices[$i], 'charge_id' => $charge_id) );
	}

	//insert custom questions
	/*[0]
	 * ->question name
	 * ->qustion value
	 * 
	 * if mult values
	 * [0]
	 *  ->question name
	 *    [0]
	 * 		 ->value 1
	 * 		 ->value 2
	 */
	if(isset($custom_question_answers)):
		foreach($custom_question_answers as $question){
			//print_r($question);
			$question_name = $question[0];
			$question_value = $question[1];
			$table_name = $wpdb->prefix . "wpsf_attendee_custom_question";
			//determine if $question_value is array in order to use implode
			if(is_array($question_value)){
	   			$wpdb->insert( $table_name, array('name' => $question_name, 'charge_id' => $charge_id, 'value' => implode(',', $question_value) ) );
			}else{
	   			$wpdb->insert( $table_name, array('name' => $question_name, 'charge_id' => $charge_id, 'value' => $question_value ) );
			}
	   		//$wpdb->insert( $table_name, array('name' => "frank!", 'charge_id' => "stupid" ) );
	  		//$insert_id = $wpdb->insert_id;
			
		}
	endif;
	
	$array = array(
		'result' => 0, 
	);
	echo json_encode($array);
		
		
	exit;
}

add_action('wp_ajax_rearrange-elements', 'rearrange_elements');
function rearrange_elements(){
	$nonce = $_POST['nonce'];
	if(!wp_verify_nonce($nonce, 'form-elements-nonce')){
		die('Security Check');
	}
	global $wpdb;
	
	$start_index = $_POST['startIndex'];
	$stop_index = $_POST['stopIndex'];
	$template_id = $_POST['templateId'];
	//rearrange elements (shift up or down) to make room for alteredElement
	//after shift, set alteredElement index to endIndex using alteredElement id
	//alteredEl id = get id of question where index = startIndex 
	$table = $wpdb->prefix . "wpsf_question";
	$altered_el = $wpdb->get_row( "SELECT *  FROM $table WHERE template_id = $template_id AND q_index = $start_index");
	
	//echo('h' . $altered_el);
	//print_r($altered_el);
	
	if($stop_index > $start_index){ //element moved down ie 1 to 6
		//update questions between start and stop index (shift everything up)
		for($i=$start_index+1; $i <= $stop_index; $i++){
			//update questions set index = $i-1 where index = $i
			$wpdb->update( $table, array('q_index'=>$i-1), array('q_index' => $i, 'template_id' => $template_id));
		}
	}else{
		//update questions before new Index (shift everything down)
		for($i=$start_index-1; $i >= $stop_index; $i--){
			//update questions set index = $i+1 where index = $i
			$wpdb->update( $table, array('q_index'=>$i+1), array('q_index' => $i, 'template_id' => $template_id));
		}
	}
	//update question set index to stopIndex where id = alteredEl
	$wpdb->update( $table, array('q_index'=>$stop_index), array('id' => $altered_el->id, 'template_id' => $template_id));
	
	header( "Content-Type: application/json");
	$response['update'] = true;
	echo json_encode($response);
	exit;
}

add_action('wp_ajax_get-saved-elements', 'get_saved_elements');
add_action('wp_ajax_nopriv_get-saved-elements', 'get_saved_elements');
function get_saved_elements(){
	$nonce=$_POST['nonce'];
	if (! wp_verify_nonce($nonce, 'form-elements-nonce') ) die('Security check'); 
	header( "Content-Type: application/json");
	
	global $wpdb;
	$final_elements;
	
	/* returns
	template name
	template id 
	template questions
	 * [0]
			question id 
			question name 
		    etc.
	 * 		options
	 * 			[0]
	 * 			[1]
	 * [1]
	 * 		question id
	 * 		question name
	 * 		etc.
	 * 		options
	 * 			[0]
	 * 			[1]
	*/
	$table = $wpdb->prefix . "wpsf_template";
	$templates = $wpdb->get_results( "SELECT * FROM $table ORDER BY id" );
	$template_index = 0;
	foreach($templates as $template){
		//add template data
		$template_data = array(
			'template_id' => $template->id,
			'template_name' => $template->name
		);
		$final_elements[$template_index] = $template_data;
		//get question data for this template
		$table = $wpdb->prefix . "wpsf_question";
		$questions = $wpdb->get_results( "SELECT * FROM $table WHERE template_id = $template->id ORDER BY q_index" );
		$question_index = 0;
		
		foreach($questions as $q){
			$question_meta = array(
				'serverId' => $q->id,
				'title' => $q->title,
				'helpText' => $q->help_text,
				'type' => $q->type,
				'is_required' => $q->is_required,
				'q_index' => $q->q_index
			);
			//add question data
			$final_elements[$template_index]['questions'][$question_index] = $question_meta;
			//GET OPTIONS
			$table = $wpdb->prefix . "wpsf_question_option";
			$options = $wpdb->get_results( "SELECT * FROM $table WHERE question_id = $q->id ORDER BY id" );
			foreach($options as $o){
				$final_elements[$template_index]['questions'][$question_index]['options'][] = $o->value;
			}
			$question_index++;
		}
		$template_index++;
	}
	if(isset($final_elements)){
		$response = array('elements' => $final_elements);
		echo json_encode($response);
		//print_r($response['elements']);
	}
	
	
	exit;
}

add_action('wp_ajax_save-question-template', 'save_question_template');
function save_question_template(){
	$nonce=$_POST['nonce'];
	if (! wp_verify_nonce($nonce, 'form-elements-nonce') ) die('Security check'); 
	header( "Content-Type: application/json");
	
	global $wpdb;
	if(isset($_POST['id'])){
		$template_id = $_POST['id'];
	}
	$template_name = $_POST['template_name'];

	$table = $wpdb->prefix . "wpsf_template";

	if(!isset($template_id)){ //template is new
		$wpdb->insert( $table, array('name' => $template_name)); 
		$response = array('insert_id' => $wpdb->insert_id);
		echo json_encode($response);
		//overwrite template id
	}else{ //update template
		$wpdb->update( $table, array('name'=>$template_name), array('id' => $template_id));
		$response = array('update' => true);
		echo json_encode($response);
	}
	exit;
}

add_action('wp_ajax_save-custom-question', 'save_custom_question');
function save_custom_question(){
	//die();
	$nonce=$_POST['nonce'];
	if (! wp_verify_nonce($nonce, 'form-elements-nonce') ) die('Security check'); 
	header( "Content-Type: application/json");

	global $wpdb;
	if(isset($_POST['template_id'])){
		$template_id = $_POST['template_id'];
	}
	$template_name = $_POST['template_name'];
	$question_id = $_POST['id']; //will be '' if not set
	$question_meta = $_POST['question_meta'];
	if(isset($_POST['options'])){
		$options = $_POST['options'];
	}
	
	
	/*
	echo 'tid:' . $template_id;
	echo 'tname:'.$template_name;
	echo 'id:'.$question_id;
	print_r($question_meta);
	print_r($options);
	*/
	
	//add template if new
	if(!isset($template_id)){
		echo 'inserting template';
		$table = $wpdb->prefix . "wpsf_template";
		$wpdb->insert( $table, array('name' => $template_name)); 
		//overwrite template id
		$template_id = $wpdb->insert_id;
	}
	if($question_id === ''){ //question is new
		$table = $wpdb->prefix . "wpsf_question";
	
		//add template id to question_meta
		$question_meta['template_id'] = $template_id;
		$wpdb->insert( $table, $question_meta); 
		
		$response['insert_id'] = $wpdb->insert_id;
		
		//overwrite question id
		$question_id = $wpdb->insert_id;
		
	}else{ //update question
		$table = $wpdb->prefix . "wpsf_question";
		$wpdb->update( $table, $question_meta, array('id' => $question_id));
	}
	//check for options if not a textbox
	if($question_meta['type'] !== 'text' && isset($options)){
		
		$table = $wpdb->prefix . "wpsf_question_option";
		//delete all previous options for this question
		$wpdb->query( 
			$wpdb->prepare( 
				"
		         DELETE FROM $table
				 WHERE question_id = $question_id
				" 
		        )
		);
		foreach($options as $option){
			//insert each option
			$wpdb->insert( $table, array('question_id'=>$question_id, 'value' => $option)); 
		}
	}
	
	$response['update'] = true;
	echo json_encode($response);
	exit;
	
}

add_action('wp_ajax_delete-custom-question', 'delete_custom_question');
function delete_custom_question(){
	//die();
	$nonce=$_POST['nonce'];
	if (! wp_verify_nonce($nonce, 'form-elements-nonce') ) die('Security check'); 
	header( "Content-Type: application/json");

	global $wpdb;
	$question_id = $_POST['id'];
	//delete all previous options for this qustion
	$table = $wpdb->prefix . "wpsf_question_option";
		$wpdb->query( 
			$wpdb->prepare( 
				"
		         DELETE FROM $table
				 WHERE question_id = $question_id
				" 
		        )
		);
	
	//delete this question
	$table = $wpdb->prefix . "wpsf_question";
		$wpdb->query( 
			$wpdb->prepare( 
				"
		         DELETE FROM $table
				 WHERE id = $question_id
				" 
		        )
		);
	$response['deleted'] = true;
	echo json_encode($response);
	exit;
}


add_action('wp_ajax_delete-template', 'delete_template');
function delete_template(){
	//die();
	$nonce=$_POST['nonce'];
	if (! wp_verify_nonce($nonce, 'form-elements-nonce') ) die('Security check'); 
	header( "Content-Type: application/json");

	global $wpdb;
	$template_id = $_POST['id'];
	
	//get question id's that relate to template
	$table = $wpdb->prefix . "wpsf_question";
	$question_ids = $wpdb->get_col("SELECT id FROM $table WHERE template_id = $template_id");
	//print_r($question_ids);
	//exit;
	
	//delete all options for this qustion
	$table = $wpdb->prefix . "wpsf_question_option";
		$wpdb->query( 
			$wpdb->prepare( 
				"
		         DELETE FROM $table
				 WHERE question_id in (" . implode(",",$question_ids) . ")
				" 
		        )
		);
		
	
	//delete all questions for template
	$table = $wpdb->prefix . "wpsf_question";
	$wpdb->query( 
		$wpdb->prepare( 
			"
	         DELETE FROM $table
			 WHERE template_id = $template_id
			" 
	        )
	);
	//delete template
	$table = $wpdb->prefix . "wpsf_template";
		$wpdb->query( 
			$wpdb->prepare( 
				"
		         DELETE FROM $table
				 WHERE id = $template_id
				" 
		        )
		);
	$response['deleted'] = true;
	echo json_encode($response);
	exit;
}
