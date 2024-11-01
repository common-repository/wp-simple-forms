//Dependencies: UI Core, UI Widget
(function( $ ){
	$.widget("ui.deletable", {  
		options: {  
		  location: "bottom",  
		  color: "#000",  
		  hoverColor: '#fff',
		  backgroundColor: "#fff",
		  hoverBackgroundColor: '#ccc',
		  fontSize: '1em',
		  fadeOut: 'fast',
		  hover: true,
		  height: '1.5em',
		  width: '1.5em',
		  message : 'Are you sure you want to delete this?'
		}, 
	
		_create: function() {  
	    
	    	var self = this,  
	        o = self.options,  
	        el = self.element,  
	        //create handle and append to element
	    	handle=$('<div class="remove-handle">X</div>').css({
	    		position: 'absolute',
	    		right: '0',
	    		top: '0',
	    		color: o.color,
	    		'font-weight': 'bold',
	    		border: '1px outset #666',
	    		width: o.width,
	    		height: o.height,
	    		'border-radius': '.2em',
	    		background: o.backgroundColor,
	    		margin: '1px',
	    		'text-align': 'center',
	    		'font-size' : o.fontSize,
	    		'z-index' : '101',
	    		cursor : 'pointer'
	    	}).hover(function(){
	    		$(this).css({
	    			background: o.hoverBackgroundColor,
	    			color: o.hoverColor
				});
	    	},function(){
	    		$(this).css({
	    			background: o.backgroundColor,
	    			color: o.color
	    		});
	    	}).appendTo(el).click(function(){
	    		var c = confirm(o.message);
	    		if(c === true){
	    			$(this).closest('.deletable').fadeOut(o.fadeOut,function(){
				    	self._trigger("onDelete", null, el);
						$(this).remove();
					})	
	    		}
				    
	    	}).hide();
	    	//make element relative
	    	el.css('position','relative').addClass('deletable');
	    	//apply hover
	    	if(o.hover){
	    		el.hover(function(){
	    			handle.fadeIn('fast');
	    		},function(){
	    			handle.fadeOut('fast');
	    		})
	    	}
	    },
		hide: function() {
			this.element.find('.remove-handle').hide();
		},
	    destroy: function() { //has no been tested 
			this.element
			.removeClass("deletable")
			.find('.remove-handle')
			.css('border','2px solid red')//.remove()

    	}
	});   
})( jQuery );