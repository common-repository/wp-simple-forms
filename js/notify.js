var notify = function(messageToUser, el , addToEl, howToPlace){

			//add message
			this.box;
			
			this.is_active = false;
			this._timeout;
			this._fadeOut;
			var box_id;
			
			notify._fadeOut = function(){
				notify.box.fadeOut('slow', function(){
					//after fadeout, reset to inactive
					notify.is_active = false;
				})
			};
			
			this.closeBox = function(){
				
				if(notify._timeout){clearTimeout(notify._timeout);}
				
				notify._timeout = setTimeout('notify._fadeOut()',3000);
				
			}
			
				
			if(typeof addToEl === 'undefined' || addToEl === false){
				//make sure container exists, if it doesn't, add it
				if(jQuery('.cd-info').length <= 0){
					jQuery('body').append('<div class="cd-info cd-info-fixed"></div>');
				}
			}else{
				//make sure el doesn't already have cd-info
				if(el.find('.cd-info').length === 0){
					note = jQuery('<span class="cd-info"></span>');
					//check how to place notification
					if(howToPlace === 'append'){
						el.append('<span class="cd-info"></span>');
					}
					else if(howToPlace === 'absRight'){
						el.css('position', 'relative');
						note.css({
							position: 'absolute',
							top:'0',
							right:'0',
							width:'15%'
						}).appendTo(el);
					}
					else{
						el.after('<span class="cd-info"></span>');
					}
				}
			}
			
			
			if(typeof(el) === "undefined"){
				//if no element is specified, send info to first container
				notify.box = jQuery('.cd-info').first();
				//console.log('undefined');
			}else{
				//jQuery('.cd-info').text('hi');
				//traverse the local area to find .cd-info
				if(el.siblings('.cd-info').length > 0){
					el.hide();
					//notify.box = jQuery(el).siblings('.cd-info');
					notify.box = jQuery('.cd-info:eq(0)');
				}else if(el.find('.cd-info').length > 0){
					notify.box = el.find('.cd-info');
				}else if(el.closest('.cd-info').length > 0){
					notify.box = el.closest('.cd-info');
				}else{
					notify.box = jQuery('.cd-info');
				}
			}
			//set box html
			//fade in box
			notify.box.html(messageToUser)
			.fadeIn('slow');				
			this.closeBox()
			
			
				
			
				
			
			
		}	