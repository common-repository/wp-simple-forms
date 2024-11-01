/*jslint nomen: true, plusplus: true*/
/*global ajaxurl, notify, jQuery */
var FormElements = {};
(function ($) {
	"use strict";
	FormElements.templates = {
		_activeTemplate : '',
		setActive : function (templateObj) {
			this._activeTemplate = templateObj;
			//console.log(this._activeTemplate);
		},
		getActive : function () {
			return this._activeTemplate;
		},
		_allTemplates : [],
		attach :  function (templateObj) {
			this._allTemplates[this._allTemplates.length] = templateObj;
			this._activeTemplate = templateObj;
			templateObj.html.prependTo('#my-form-elements');
		}
	};
	//stores all templates created
	FormElements.elements = {
		_Template: function (serverId, name) {
			var el = this;
			this.attachedQuestions = [];//stores all questions attached to template
			(function () {$('.form-elements-selected-wrapper').removeClass('form-elements-selected-wrapper'); }());
			this.id = $('.template-wrapper').length + 1;
			if (serverId !== undefined) {
				this.serverId = serverId;
			} else {
				this.serverId = '';
			}
			if (name !== undefined) {
				this.templateName = name;
			} else {
				this.templateName = 'Template ' + this.id;
			}
			this.html = $('<ul class="template-wrapper form-elements-selected-wrapper" id="' + this.serverId + '"></ul>').sortable({
				placeholder: "ui-state-highlight",
				start: function (event, ui) {
					el.moveElement.setStart(ui.item.index());
				},
				stop: function (event, ui) {
					//console.log()
					//update database indexes 
					el.moveElement.setStop(ui.item.index());
					el.moveElement.rearrange();
				}
			}).click(function () {
				$('.form-elements-selected-wrapper').removeClass('form-elements-selected-wrapper');
				//remove active status from obj and apply to new template
				FormElements.templates.setActive(el);
				$(this).addClass('form-elements-selected-wrapper');
			}).deletable({
				message: 'Are you sure you want to delete this template?  All of your questions will be deleted as well.',
				onDelete: function () {
					el.deleteTemplate();
				}
			});
			el._template = $('<table><tr><th><strong>Template Name:</strong></th><th><form><input type="text" name="custom_question_template_names[]" value="' + el.templateName + '"/></form></th></tr></table>');
			el._template.find('input').change(function () {
					//el._template.css('border', inputCounterT++ +'px solid red');
				el.save();
			});
			el._template.find('form').submit(function () {
				return false;
			});
			this._template.appendTo(el.html);
			this.save = function () { //save template
				var data = {
					action: 'save-question-template',
					nonce: FormElements.nonce,
					id: el.html.closest('.template-wrapper').attr('id'),
					template_name: el.html.closest('.template-wrapper').find('input').first().val()
				};
				$.post(ajaxurl, data, function (response) {
					//console.log(response.insert_id);
					if (response.insert_id !== undefined) {
						//set wrapper id to new insert id
						el.html.closest('.template-wrapper').attr('id', response.insert_id);
						el.serverId = response.insert_id;
						//console.log(el.serverId);
					}
					if (response.update !== undefined) {
						notify('Template updated', el.html, true, 'absRight');
					}
				});
			};
			this.deleteTemplate = function () {
				var data = {
					action: 'delete-template',
					nonce: FormElements.nonce,
					id: el.serverId
				};
				//console.log(data);
				$.post(ajaxurl, data, function (response) {
					//alert('Got this from the server: ' + response);
					//change server id to retrieved id if question was new
					if (response.deleted !== undefined) {
						notify('Template deleted');
					}
				});
				return false;
			};
			this.moveElement = {
				_start: '',
				_stop: '',
				setStart: function (startIndex) {
					this._start = startIndex;
					//console.log('start' + this._start);
				},
				setStop: function (newIndex) {
					this._stop = newIndex;
					//console.log(newIndex);
				},
				rearrange: function () {
					if (this._start === this._stop) {
						return;
					}
					//console.log(this._start);
					//when questions are sorted, this function should overwrite the index of every question in db
					//send indexes to server
					var data = {
						startIndex: this._start,
						stopIndex: this._stop,
						templateId: el.serverId,
						action: 'rearrange-elements',
						nonce: FormElements.nonce
					};
					$.post(ajaxurl, data, function (response) {
						//alert('Got this from the server: ' + response);
						//change server id to retrieved id if question was new
						if (response.insert_id !== undefined) {
							el.serverId = response.insert_id;
						}
						if (response.update !== undefined) {
							notify('Order saved', el.html, true, 'absRight');
						}
					});
				}
			};
			return this;
		},
		_SelectTypeBox: function (selected) {
			//console.log('selected:' + selected);
			this._html = $('<select name="question_types[]"><option value="text">Text</option><option value="dropdown">Dropdown</option><option value="check-boxes">Check Boxes</option><option value="mult-choice">Multiple Choice</option><option value="textarea">Text Box</option></select>');
			this._html.find('option[value="' + selected + '"]').attr('selected', 'selected');
			//console.log(test.length);
			//console.log($(html));
			return this._html;
		},
		_optionField: function (status) {
			this.status = 'active';
			var html = $('<input type="text" value="Option"/>');
		},
		_QuestionBox: function (questionType, meta) {
			var el = this;
			if (meta === undefined) {
				this.m = {
					serverId: '',
					type: questionType,
					title: '',
					helpText: '',
					options: [],
					is_required: false
				};
			} else {
				this.m = meta;
			}
			
			el._previewBox = $('<div class="question-preview"></div>');
			this.preview = function () {
				//wordpress doesn't allow html insertion to db - conver /" to " to allow html in title'
				el.m.title = el.m.title.replace(/\\/g, '');
				el.m.helpText = el.m.helpText.replace(/\\/g, '');
				el._previewBox.html('');
				el._previewBox.append('<p><strong>' + el.m.title + '</strong></p><p>' + el.m.helpText + '</p>');
				if (el.m.is_required === 'true') {
					el._previewBox.append('<p>* Required</p>');
				}
				if (el.m.type === 'text') {
					el._previewBox.append('<p><input type="text" readonly="readonly" /></p>');
					//console.log(el.html.find('.question-wrapper').length);
				}//end text
				//fill el.m.options if not set
				if (el.m.options === '' || el.m.options === undefined) {
					el.m.options = ['empty'];
				}
				if (el.m.type === 'dropdown') {
					//reset preview box
					el._previewBox.append('<select class="dropdown-options"></select>');
					$.each(el.m.options, function (index, value) {
						el._previewBox.find('select.dropdown-options').append('<option value="' + value + '">' + value + '</option>');
					});
				}
				if (el.m.type === 'check-boxes') {
					//reset preview box
					$.each(el.m.options, function (index, value) {
						el._previewBox.append('<label><input type="checkbox" value="' + value + '" /> ' + value + '</label><br />');
					});
				}
				if (el.m.type === 'mult-choice') {
					//reset preview box
					//radio name must be unique - find all current radio inputs and use length to create new name
					var length = $('input[type="radio"]').length;
					$.each(el.m.options, function (index, value) {
						el._previewBox.append('<label><input type="radio" name="radio' + length + '" value="' + value + '" /> ' + value + '</label><br />');
					});
				}
				if (el.m.type === 'textarea') {
					el._previewBox.append('<textarea></textarea>');

				}
				(function () {
					//append previewbox on first run, after that, show
					el.html.append(el._previewBox);
					return el._previewBox.show();
				}());
				el._editBox.hide();
				//save question
				//el.save();
				return false;
			};
			this.edit = function () {
				if (el._previewBox !== '') {
					el._previewBox.hide();
				}
				(function () {
					//append edit on first run, after that, show
					el.html.append(el._editBox);
					return el._editBox.show();
				}());
			};
			this.save = function () {
				var data = {
					action: 'save-custom-question',
					nonce: FormElements.nonce,
					template_id: el.html.closest('.template-wrapper').attr('id'),
					template_name: el.html.closest('.template-wrapper').find('input').first().val(),
					id: el.m.serverId,
					question_meta: {
						type: el.m.type,
						title: el.m.title,
						help_text: el.m.helpText,
						is_required: el.m.is_required,
						q_index: el.html.index()
					},
					options: el.m.options
				};
				//console.log(data);
				$.post(ajaxurl, data, function (response) {
					//alert('Got this from the server: ' + response);
					//change server id to retrieved id if question was new
					if (response.insert_id !== undefined) {
						el.m.serverId = response.insert_id;
					}
					if (response.update !== undefined) {
						notify('Question saved', el._previewBox, true, 'absRight');
					}
				});
				return false;
			};
			this.deleteMe = function () {
				var data = {
					action: 'delete-custom-question',
					nonce: FormElements.nonce,
					id: el.m.serverId
				};
				//console.log(data);
				$.post(ajaxurl, data, function (response) {
					//alert('Got this from the server: ' + response);
					//change server id to retrieved id if question was new
					if (response.deleted !== undefined) {
						notify('Question deleted');
					}
				});
				return false;
			};
			this._editBox = $('<form class="question-edit-box"><table>'
				+ '<tr><td>Question Title:</td><td><input type="text" class="question-title" name="custom_question_titles[]" size="100" value="' + el.m.title + '" /></td></tr>'
				+ '<tr><td>Help Text:</td><td><input type="text" class="question-help-text" name="custom_question_help_text[]" size="100" value="' + el.m.helpText + '" /></td></tr>'
				+ '<tr><td>Question Type</td><td class="question_type">'
				+ '</td></tr></table><table class="custom-fields-row">'
				+ '</table><input type="hidden" name="template_name" value="" /><input type="submit" value="Done" /></form>')
				.submit(el.preview).submit(el.save);
			if (el.m.is_required === 'true') {
				this.requiredField = $('<label><input type="checkbox" checked="checked" autocomplete="false"/>Make this a required field</label>');
			} else {
				this.requiredField = $('<label><input type="checkbox" autocomplete="false"/>Make this a required field</label>');
			}
			this.requiredField.change(function () {
				//toggle el.is_required;
				if ($(this).find('input:checked').length > 0) {
					el.m.is_required = 'true';
				} else {
					el.m.is_required = 'false';
				}
			});
			this.requiredField.appendTo(this._editBox);
			this.selectTypeBox = new FormElements.elements._SelectTypeBox(questionType);
			//set html of element
			this.html = $('<li class="question-wrapper"></li>').deletable({
				onDelete: function () {
					el.deleteMe();
				}
			});
			//show editing window
			el.edit();
			//set html functionality
			this.html.dblclick(function () {
				//alert('hi');
				el.edit();
			});
			this.html.find('.question-title').change(function () {
				//console.log( $(this).val());
				el.m.title = $(this).val();
				//console.log(el.title);
			});
			this.html.find('.question-help-text').change(function () {
				el.m.helpText = $(this).val();
				//console.log(el.title);
			});
			this.html.find('input[type="checkbox"]');
			this.html.find('.question_type').html(this.selectTypeBox);
			//this.customFields = new Array();
			this.customFields = this.html.find('.custom-fields-row');
			this.addToCustomFields = function (obj) {
				//store custom fields in array for use later
				var row = $('<tr></tr>').appendTo(el.customFields);
				obj.appendTo(row);
				obj.find('input').change(function () {
					//console.log($(this).index());
					//console.log('rent:' + $(this).closest('tr').index() )
					el.m.options[$(this).closest('tr').index()] = $(this).val();
				});
				//console.log(el.customFields.html());				
			};
			this.selectTypeBox.change(function () {
				//remove custom fields
				//console.log($(this).val());
				if ($(this).val() === 'text' || $(this).val() === 'textarea') {
					el.customFields.hide();
				} else {
					el.customFields.show();
				}
				el.m.type = $(this).val();
			});
			this.addCustomFields = function () {
				var el = this, duplicate;
				if (el.m.options !== undefined && el.m.options.length > 0) { //populate options
					$.each(el.m.options, function (index, value) {
						el.activeField = $('<td></td><td><input type="text" name="dropDownOptions[]" value="' + value + '"/></td>');
						el.addToCustomFields(el.activeField);
					});
				} else { //add empty field
					el.activeField = $('<td></td><td><input type="text" name="dropDownOptions[]" value=""/></td>');
					el.addToCustomFields(el.activeField);
				}
				duplicate = function () {
					var myClone = el.activeField.clone();
					myClone.find('input').attr({
						value: '',
						readonly: "readonly",
						placeholder: "Click to add option"
					});
					myClone.find('input').focus(duplicate);
					el.addToCustomFields(myClone);
					$(this).removeAttr('placeholder').removeAttr('readonly').unbind('focus');
				};
				//duplicate self
				duplicate();
			};
			//add custom fields if el.m.options are set
			this.addCustomFields(el.m.options);
			return this;
		}
	};
	FormElements.functions = {
		saveQuestionOrder: function(){
			var newIndex;
			//add question to active template
			var activeTemp = FormElements.templates.getActive();
			for(var i = 0; i < activeTemp.attachedQuestions.length; i++){
				//get html index of element
				newIndex = activeTemp.attachedQuestions[i].html.index();
				//reset q_index of question meta with newIndex
				activeTemp.attachedQuestions[i].m.q_index = newIndex;
				//save question
				activeTemp.attachedQuestions[i].save();
			}
			
			//console.log(activeTemp);
		},
		createTemplate: function (serverId, name, noSave) {
			var el = this;
			el.template = new FormElements.elements._Template(serverId, name);
			//attach template to global templates
			FormElements.templates.attach(el.template);
			//save template unless otherwise told not to do so
			if (noSave === undefined) {
				this.template.save();
			}
		},
		addQuestion : function (type, meta) {
			if ($('.template-wrapper').length === 0) {
				//create template
				this.createTemplate();
			}
			//console.log(meta);
			this.question = new FormElements.elements._QuestionBox(type, meta);
			//on add set template id of element to serverId of template
			if (meta !== undefined) {
				this.question.preview();
			}
			//add question to active template
			var activeTemp = FormElements.templates.getActive();
			//console.log('this ' +this.question);
			FormElements.functions.attachQuestion(activeTemp, this.question);
			if (type === 'text' || type === 'textarea') {
				this.question.customFields.hide();
			}
		},
		attachQuestion: function (template, questionObj) {
			//console.log(template)
			template.attachedQuestions[template.attachedQuestions.length] = questionObj;
			questionObj.html.appendTo('.form-elements-selected-wrapper');
		},
		getSavedElements: function (callback) {
			var el = this, data;
			//var templates = this.getTemplates();
			data = {
				action: 'get-saved-elements',
				nonce: FormElements.nonce
			};
			el.elements = $.post(ajaxurl, data, function (response) {
				//console.log(response.insert_id);
				if (response.elements !== undefined) {
					//console.log(response.elements);
					if (callback !== undefined) {
						callback(response.elements);
					} else {
						return response.elements;
					}
				}
			});
			//console.log(el.elements);
			return el.elements;
		},
		showElements: function (elements) {
			var i, j, type, meta;
			if (elements === undefined) {
				return false;
			}
			//console.log(elements);

			for (i = 0; i < elements.length; i++) {
				//create template
				FormElements.functions.createTemplate(elements[i].template_id, elements[i].template_name, 'true');
				//console.log(elements[i].questions);
				if (elements[i].questions !== undefined) {
					//attach each question
					for (j = 0; j < elements[i].questions.length; j++) {
						type = elements[i].questions[j].type;
						meta = elements[i].questions[j];
						//console.log(meta);
						FormElements.functions.addQuestion(type, meta);
					}
				}
				//console.log(elements[i]['template_id']);
			}
		}
	};
	FormElements.publicElements = {
		publicQuestions: [],
		_QuestionBox: function (questionType, meta) {
			var el = this, text, textarea, select, elementLength, optionCount, table, setAnswer;
			this.m = meta;
			//wordpress doesn't allow html insertion to db - conver /" to " to allow html in title'
			el.m.title = el.m.title.replace(/\\/g, '');
			el.m.helpText = el.m.helpText.replace(/\\/g, '');
			//create wrapper for questions
			el._wrapper = $('<div class="question-wrapper cd-form-entry"></div>');
			el._wrapper.append('<label class="question-title">' + el.m.title + '</label>');
			//remove spaces in name
			this.m.name = el.m.title.replace(/\s+/g, '');
			if (el.m.is_required === 'true') {
				el._wrapper.find('.question-title').append('<span>*</span>');
			}
			el._wrapper.append('<div class="cd-help-text">' + el.m.helpText + '</div>');
			if (el.m.type === 'text') {
				text = $('<input type="text" id="' + el.m.name.substr(0, 30) + '" name="' + el.m.name.substr(0, 30) + '" />');
				if (el.m.is_required === 'true') {
					text.addClass('required');
				}
				text.change(function () {
					el.answer = $(this).val();
				});
				el._wrapper.append(text);
				//set answer on load
				el.answer = text.val();
			}//end text
			if (el.m.type === 'textarea') {
				textarea = $('<textarea id="' + el.m.name.substr(0, 30) + '" name="' + el.m.name.substr(0, 30) + '"></textarea>');
				if (el.m.is_required === 'true') {
					textarea.addClass('required');
				}
				textarea.change(function () {
					el.answer = $(this).val();
					console.log($(this).val());
				console.log(el.answer);
				});
				el._wrapper.append(textarea);
				//set answer on load
				el.answer = textarea.val();
				console.log(el.answer);
			}//end text
			//fill el.m.options if not set
			if (el.m.options === '' || el.m.options === undefined) {
				el.m.options = ['empty'];
			}
			if (el.m.type === 'dropdown') {
				select = $('<select class="dropdown-options" name="' + el.m.name.substr(0, 30) + '" id="' + el.m.name.substr(0, 30) + '"></select>');
				if (el.m.is_required === 'true') {
					select.addClass('required');
				}
				select.change(function () {
					el.answer = $(this).val();
				});
				el._wrapper.append(select);
				$.each(el.m.options, function (index, value) {
					var option = '<option value="' + value + '">' + value + '</option>';
					el._wrapper.find('select.dropdown-options').append(option);
				});
				//when question is first shown, set answer
				el.answer = select.val();
			}
			if (el.m.type === 'check-boxes') {
				el._wrapper.addClass('cd-checkbox');
				//radio name must be unique - find all current radio inputs and use length to create new name
				elementLength = $('input[type="checkbox"]').length;
				optionCount = 0;
				//create table to keep things neat
				table = $('<table></table>');
				$.each(el.m.options, function (index, value) {
					var id, name, tData, checkBox, label, tRow;
					optionCount++;
					//id must be unique
					id = el.m.name.substr(0, 30) + optionCount;
					//name must be the same for every input, but different for every radiobox question
					//name = el.m.name.substr(0,30).replace(',','') + length;
					name = 'checkbox' + elementLength;
					//create radio
					tData = $('<td></td>');
					checkBox = $('<input type="checkbox" name="' + name + '" id="' + id + '" value="' + value + '" />');
					//add required if applicable
					if (el.m.is_required === 'true') {
						checkBox.addClass('required');
					}
					//add radio to td
					tData.append(checkBox);
					//create label
					label = $('<td><label for="' + name + '"> ' + value + '</label></td>');
					//add label to row
					tRow = $('<tr></tr>');
					tRow.append(label);
					//add radio(tData) after label
					label.after(tData);
					//add row to table
					$(table).append(tRow);
				});
				//add table to wrapper
				el._wrapper.append(table);
				/*
				$.each(el.m.options, function(index,value){
					el._wrapper.append('<label><input type="checkbox" name="'+ el.m.name.substr(0,30) +'" id="'+ el.m.name.substr(0,30) +'" value="' + value + '" /> ' + value + '</label><br />');
				});
				*/
				//check each checkbox to see if it is checked and to set on change
				setAnswer = function () {
					//reset answer
					el.answer = [];
					if (el._wrapper.find('input:checked').length > 0) {
						el._wrapper.find('input:checked').each(function () {
							el.answer[el.answer.length] = $(this).val();
						});
						//console.log(el.answer);
					}
				};
				setAnswer();
				el._wrapper.find('input').change(setAnswer);
			}
			if (el.m.type === 'mult-choice') {
				el._wrapper.addClass('cd-radio');
				//radio name must be unique - find all current radio inputs and use length to create new name
				elementLength = $('input[type="radio"]').length;
				optionCount = 0;
				//create table to keep things neat
				table = $('<table></table>');
				$.each(el.m.options, function (index, value) {
					var id, name, tData, radioBox, label, tRow;
					optionCount++;
					//id must be unique
					id = el.m.name.substr(0, 30) + optionCount;
					//name must be the same for every input, but different for every radiobox question
					//name = el.m.name.substr(0,30).replace(',','') + length;
					name = 'radio' + elementLength;
					//create radio
					tData = $('<td></td>');
					radioBox = $('<input type="radio" id="' + id + '" name="' + name + '" value="' + value + '" />');
					//add required if applicable
					if (el.m.is_required === 'true') {
						radioBox.addClass('required');
					}
					//add radio to td
					tData.append(radioBox);
					//create label
					label = $('<td><label for="' + name + '"> ' + value + '</label></td>');
					//add label to row
					tRow = $('<tr></tr>');
					tRow.append(label);
					//add radio(tData) after label
					label.after(tData);
					//add row to table
					$(table).append(tRow);
				});
				//add table to wrapper
				el._wrapper.append(table);
				//check each radio to see if it is checked and to set on change
				setAnswer = function () {
					if (el._wrapper.find('input:checked').length > 0) {
						el.answer = $(this).val();
					} else {
						el.answer = '';
					}
				};
				setAnswer();
				el._wrapper.find('input').change(setAnswer);
			}
			el._wrapper.find('input').blur(function () {
				//jQuery(this).valid();
			});
			el.html = el._wrapper;
		}
	};
	FormElements.publicFunctions = {
		createGeneralWrapper: function () {
			var wrapper = $('<div id="custom-question-wrapper"><h2>Checkout Information</h2></div>');
			//wrapper.append('');
			$('#buy-form').prepend(wrapper);
		},
		showElements: function (questions) {
			var i, type, meta;
			FormElements.publicFunctions.createGeneralWrapper();

			if (questions === undefined) {
				return false;
			}
			//add each question to wrapper
			for (i = 0; i < questions.length; i++) {
				//attach each question
				type = questions[i].type;
				meta = questions[i];
				//console.log(meta);
				FormElements.publicFunctions.addQuestion(type, meta);
			}
		},
		addQuestion : function (type, meta) {
			//console.log(meta);
			this.question = new FormElements.publicElements._QuestionBox(type, meta);
			//add question to custom-question-wrapper
			//console.log(this.question);
			FormElements.publicElements.publicQuestions[FormElements.publicElements.publicQuestions.length] = this.question;
			$('#custom-question-wrapper').append(this.question.html);
		},
		getAnswers: function () {
			var answers = [], answerCount = 0;
			$.each(FormElements.publicElements.publicQuestions, function (index, element) {
				//console.log(element);
				var name = element.m.name, value = element.answer;
				//console.log('name:' + name + ';' + value + ' - ');
				//answers[answerCount] = ['name', 'values'];
				answers[answerCount] = [name, value];
				//answers[answerCount]['name'] = name; 
				//answers[answerCount]['values'] = value;
				answerCount++;
			});
			//console.log(answers);
			return (answers);
		}
	};
}(jQuery));