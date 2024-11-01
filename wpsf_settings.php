<?php
	if(count($_POST) > 0){
		$sf_email = $_POST['sf-email'];  
		$sf_confirm = $_POST['sf-confirm'];  
        update_option('simpleform-email', trim($sf_email));  
        update_option('simpleform-confirmation-message', trim($sf_confirm));  
		
		echo "<div class='updated'><p><strong>Options saved</strong></p></div>";
	}else {  
        //Normal page display  
        $sf_email = get_option('simpleform-email');  
        $sf_confirm = get_option('simpleform-confirmation-message');  
		$sf_confirm = '' ? 'Thank you.  Your form has been submitted.' : $sf_confirm;
    }  
?>

<div class="wrap">
		<h2>Email Settings</h2>
		<form method="post" action="">
			<table>
				<tr>
				<?php 
					echo "<td><label>Form Recipient Email:</label></td><td><input type='text' name='sf-email' value='$sf_email' size='40'/></td>";
				?>
				</tr>
				<tr>
				<?php
					echo "<td><label>Form Confirmation Message:</label></td><td><textarea name='sf-confirm'>$sf_confirm</textarea></td>";
				?>
				</tr>
			</table>
		
		<p class="submit">  
        <input type="submit" name="Submit" value="<?php _e('Update Email Settings', 'wpe_trdom' ) ?>" />  
        </p>  
        </form>
		<hr />
		<h2>Custom Question Templates</h2>
		<p>Here you can create question templates.  Add forms to pages using the [simpleform name="formname"] shortcode (where formname is the name of the form).  </p>
		<div class="user-controls">
			<a href="#" id="new-template">New Template</a>
			<select id="add-item">
				<option value="add-item">Add Item</option>
				<option value="text">Text</option>
				<option value="dropdown">Dropdown</option>
				<option value="check-boxes">Check Boxes</option>
				<option value="mult-choice">Multiple Choice</option>
				<option value="textarea">Message Box</option>
			</select>
			<a href="#" id="save-question-order">Save Question Order</a>
		</div>
		<div id="my-form-elements">
		<!--
		<div class="template-wrapper">
			<table>
					<tr>
						<th><strong>Template Name:</strong></th><th><input type="text" name="custom_question_template_names[]" value="Template 1"/></th>
					</tr>
					
			</table>
			<!--
			<div class="question-wrapper">
				<table>
					<tr>
						<td>Question Title:</td><td><input type="text" name="custom_question_titles[]" /></td>
					</tr>
					<tr>
						<td>Help Text:</td><td><input type="text" name="custom_question_titles[]" /></td>
					</tr>
					<tr>
						<td>Question Type</td>
						<td>
							<select name="question_types[]">
								<option value="add-item">Add Item</option>
								<option value="text">Text</option>
								<option value="dropdown">Dropdown</option>
								<option value="check-boxes">Check Boxes</option>
								<option value="mult-choice">Multiple Choice</option>
							</select>
						</td>
					</tr>
				</table>
			</div>
			
		</div>-->
		</div>
      	<div class="clear"></div>
        
    </form>  
</div>  


<style>
	
</style>

<?php
	
?>
<script>
	(function($){
		$(document).ready(function(){
			FormElements.nonce = '<?php echo wp_create_nonce('form-elements-nonce'); ?>';
			//inputCounterT = 0;
			//populate form elements from server
			//console.log();
			var savedElements = FormElements.functions.getSavedElements(FormElements.functions.showElements);
			$('#new-template').click(function(){
				FormElements.functions.createTemplate();
				return false;
			});
			$('#add-item').change(function(){
				var val = $(this).val();
				if(val === 'add-item'){return false;}
				FormElements.functions.addQuestion(val);
				//reset value to add item
				$(this).val('add-item');
			});
			
			$('#save-question-order').click(function(){
				FormElements.functions.saveQuestionOrder();
			});
			
			var controls = $('.user-controls');
			var controlsOrigCSS = {
				position: controls.css('position'),
				padding: controls.css('padding'),
				'box-shadow': controls.css('box-shadow')
			}
			var controlsPos = controls.position();
			$(window).scroll(function(){
				//if controls are above fold, make them fixed
				if (controlsPos.top < $(window).scrollTop()) {
					controls.css({
						position:'fixed',
						top: '30px',
						background: '#fff',
						'z-index': '100',
						padding:'1em',
						'box-shadow': '0 2px 5px #333'
					});
				} else {
					controls.css(controlsOrigCSS);
				}
			});
			$('body').click(function(){
				
				console.log(controls.position());
				console.log($(window).scrollTop());
			});
		});//end document ready
		
		
		
		//FormElements.init();
	})(jQuery);
</script>
