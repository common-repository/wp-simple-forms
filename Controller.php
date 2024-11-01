<?php
class WP_Controller{
	function __construct(){
		if(!function_exists('get_option')) {
			require_once('../../../wp-blog-header.php');
		}
	}
}
class SimpleForm{
	public $questions = array();
	public $radio_count;
	public $question_count = 0;
	function __construct($attached_questions){
		$this->questions = $attached_questions;
	}
	
	function open($action){
		return "<form action = '$action' method='post'>\n";
	}
	function contents(){
		$html = '';
		foreach($this->questions as $q){
			$meta = array(
				'id' => $q['id'],
				'type' => $q['type'],
				'title' => $q['title'],
				'help_text' => $q['help_text'],
				'is_required' => $q['is_required'],
				'q_index' => $q['q_index'],
			);
			if(isset($q['options'])){
				$meta['options'] = $q['options'];
			}
			$question = new Question($meta);
			//$radio_count = $question->radio_count;
			$this->question_count++;
			$html .= $question->html();
		}
		
		//$html .= "<input type='hidden' name='question_count' value='$this->question_count' />\n";
		//$html .= "<input type='hidden' name='radio_count' value='$this->radio_count' />\n";
		return $html;
	}
	function submit($value = null){
		$value = $value ? $value : 'Submit';
		$html = "<input type='submit' name='submit' class='wpsf-submit' value='$value' />\n";
		return $html;
	}
	function close(){
		return "</form>\n";
	}
}
class Question{
	public $id;
	public $type;
	public $title;
	public $help_text;
	public $options = array();
	public $is_required;
	public $q_index;
	public $question_count = 0;
	public $radio_count = 0;
	
	function __construct($meta){
		foreach ( $meta as $key => $value ) {
			//$this->$key = $value; 
			$this->set_property($key, $value);
		}
		//set question count - required for correctly naming form inputs
	}
	function set_value($field, $default){
		if(! isset($_POST[$field])){
			return $default;
		}
		return $_POST[$field];
	}
	function set_check($field, $value){
		//loop thru input array.  if the value of the input matches the value of the field, check it
		if(! isset($_POST[$field])){
			return '';
		}
		//if checkbox
		if(is_array($_POST[$field])){
			foreach($_POST[$field] as $input){
				if($input === $value){
					return 'checked="checked"';
				}
			}
		}else{
			if($_POST[$field] === $value){
				return 'checked="checked"';
			}
		}
	}
	function set_select($field, $value){
		//loop thru input array.  if the value of the input matches the value of the field, check it
		if(! isset($_POST[$field])){
			return '';
		}
		if($_POST[$field] === $value){
			return 'selected="selected"';
		}
	}
	function check_required(){
		if($this->is_required == 'true'){
			return '<span class="cd-required-asterisk">*</span>';
		}
	}
	function set_property($prop, $val){
		$this->$prop = $val;
	}
	function get_property($prop){
		return $this->$prop;
	}
	function html(){
		$html;
		$required = ''; //set to 'required' if required
		if(!isset($this->type)){return 'error: Question type not defined';}
		//begin form box
		if($this->is_required == 'true'){
			$required = '_required';
		}
		$html = '<div class="cd-form-entry">';
		/**
		 * for checkboxes and radioboxes the server
		 * we set a default value of "cd-0".  This forces the form to submit all questions.
		 * The default behavior is to ignore radio and checkboxes that are empty, which
		 * does not allow us to look for required questions.  
		 * 
		 * All questions use a name of "question_$q_index".
		 * This forces all questions to have diff names
		 * 
		 * Checkboxes are set to "question_$q_index[]" so the server looks
		 * for an array.  If the $_POST input is an array, then it will take all
		 * of the answers
		 * 
		 * Required questions add "_required" to the end of the name.  The 
		 * server will parse the name and look for required.  If "required" is
		 * found and the value is "cd-0", the user has not filled out a required question
		 */
		switch ($this->type) {
			case 'text':
				$name = "question_$this->q_index$required";
				$html .= "<label class='q-title'>$this->title" . $this->check_required() . "</label>
				<label class='q-help'>$this->help_text</label>
				<input type='text' name='$name' value='" . $this->set_value($name, '') . "'/>\n
				<input type='hidden' name='titles[]' value='$this->title'/>\n";
				break;
			
			case 'dropdown':
				$name = "question_$this->q_index$required";
				$html .=  "<label class='q-title'>$this->title" . $this->check_required() . "</label><label class='q-help'>$this->help_text</label>\n";
				$html .=  "<select class='dropdown-options' name='$name'>\n";
				foreach($this->options as $option){
					$html .=  "<option value='$option'" . $this->set_select($name, $option) . ">$option</option>\n";
				}
				$html .=  "</select>\n";
				$html .= "<input type='hidden' name='titles[]' value='$this->title'/>\n";
				break;
				
			case 'check-boxes':
				$name = "question" . $this->q_index . $required;
				$array_name = $name . '[]';
				$html .= "<input type='hidden' name='$name' value='cd-0' />";
				$html .= "<label class='q-title'>$this->title" . $this->check_required() . "</label><label class='q-help'>$this->help_text</label>\n";
				$html .= "<ul>\n";
				foreach($this->options as $option){
					$html .= "<li><label><input value='$option' type='checkbox' name='$array_name'" . $this->set_check($name, $option) . "/> $option<label></li>\n";
				}
				$html .= "</ul>\n";
				$html .= "<input type='hidden' name='titles[]' value='$this->title'/>\n";
				break;
				
			case 'mult-choice':
				$name = "question" . $this->q_index . $required;
				$html .= "<input type='hidden' name='$name' value='cd-0' />";
				$html .= "<label class='q-title'>$this->title" . $this->check_required() . "</label><label class='q-help'>$this->help_text</label>\n";
				$html .= "</ul>\n";
				foreach($this->options as $option){
					$html .= "<li><label><input name='$name' " . $this->set_check($name, $option) . "value='$option' type='radio'> $option</label></li>\n";
				}
				$html .= "</ul>\n";
				$html .= "<input type='hidden' name='titles[]' value='$this->title'/>\n";
				//$this->radio_count++;
				break;

			case 'textarea':
				$name = "question" . $this->q_index . $required;
				$html .= "<label class='q-title'>$this->title" . $this->check_required() . "</label><label class='q-help'>$this->help_text</label>\n";
				$html .= "<textarea name='$name'>" . $this->set_value($name, '') . "</textarea>\n";
				$html .= "<input type='hidden' name='titles[]' value='$this->title'/>\n";
				
				break;
		}
		$html .= '</div>';
		return $html;
	}
}
class SimpleForm_controller extends WP_Controller{
	function __construct(){
		global $questions;
	}
	function new_form($questions){
		$form = new SimpleForm($questions);
		return $form;
	}
	function validate_form_input(){
		foreach($_POST as $name => $value){
			//if field is required, verify value is not cd-0
			if(strpos($name, 'required') !== false){
				if($value === 'cd-0' || trim( $value ) == '' ){
					return false;
				}
			}
		}
		return true;
	}
	function create_table(){
		$html = "<table cellspacing='0' cellpadding='10'>";
		$html .="<tr>";
		foreach($_POST['titles'] as $title){
			$html .= "<th align='left'>$title</th>";
		}
		$html .= "</tr><tr style='border-bottom:1px solid #ccc;'>";
		unset($_POST['titles']);
		unset($_POST['submit']);
				foreach($_POST as $name => $value){
			
			//if field is required, verify value is not cd-0
			if(is_array($value)){
				$html .= '<td align="left">'.implode(',',$value).'</td>';
			}else{
				$html .= "<td align='left'>$value</td>";
			}
		}
		$html .= "</tr>";
		$html .= "</table>";
		return $html;
	}
	/*function new_question($meta){
		$q = new Question($meta);
		return $q;
	}*/
	function send_entries($entries){
		$headers = 'From: ' . get_bloginfo('name') . ' <noreply@' . get_bloginfo('name') . '>' . "\n";
		$headers .= 'MIME-Version: 1.0' . "\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n"; 		
		$email_address = get_option('simpleform-email');
		send_email( $email_address, 'Form Submitted', $entries, $headers); 
		
	}
	function get_all($template_name){
		global $wpdb;
		//get form questions from server
		$table = $wpdb->prefix . "wpsf_template";
		$templates = $wpdb->get_results( "SELECT * FROM $table WHERE name = '$template_name'" );
		$template_index = 0;
		$question_index = 0;
		//$final_questions;
		$final_elements;
		foreach($templates as $template){
			//add template data
			$template_data = array(
				'template_id' => $template->id,
				'template_name' => $template->name
			);
			//$final_elements[$template_index] = $template_data;
			//get question data for this template
			$table = $wpdb->prefix . "wpsf_question";
			$questions = $wpdb->get_results( "SELECT * FROM $table WHERE template_id = $template->id ORDER BY q_index" );
			foreach($questions as $q){
				$question_meta = array(
					'id' => $q->id,
					'title' => $q->title,
					'help_text' => $q->help_text,
					'type' => $q->type,
					'is_required' => $q->is_required,
					'q_index' => $q->q_index
				);
				//add question data
				$final_questions[$question_index] = $question_meta;
				
				//$final_elements[$template_index]['questions'][$question_index] = $question_meta;
				//GET OPTIONS
				$table = $wpdb->prefix . "wpsf_question_option";
				$options = $wpdb->get_results( "SELECT * FROM $table WHERE question_id = $q->id ORDER BY id" );
				foreach($options as $o){
					$final_questions[$question_index]['options'][] = $o->value;
					//$final_elements[$template_index]['questions'][$question_index]['options'][] = $o->value;
				}
				$question_index++;
			}
			$template_index++;
		}
		return $final_questions;
	}
}
