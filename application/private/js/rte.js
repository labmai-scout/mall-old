(function($){
	/*
	 * Lightweight RTE - jQuery Plugin
	 * Copyright (c) 2009 Andrey Gayvoronsky - http://www.gayvoronsky.com
	 * Modified by Jia Huang - http://www.geneegroup.com
	 */
	
	var lwRTE = function(textarea, options) {
		
		var $textarea = $(textarea);
		
		this.css_url	= options.css;
		this.css_class	= options.frame_class || '';
		this.base_url	= options.base_url || $('base').attr('href');
		this.width		= options.width; // || $textarea.outerWidth();
		this.height		= options.height || $textarea.outerHeight();
	
		this.iframe		= null;
		this.iframe_doc	= null;
		this.textarea	= null;
		this.event		= null;
		this.range		= null;
		this.controls	= {};
	
		$.extend(this.controls, options.controls);
	
		if (document.designMode || document.contentEditable) {
			var $zone = $textarea.wrap('<div class="rte_zone" />');
			if (this.width) $zone.width(this.width);
			this.textarea	= textarea;
			this.enableDesignMode();
		}
	};
	
	lwRTE.prototype.execCommand = function(command, args) {
		this.iframe.contentWindow.focus();
		try {
			this.iframe_doc.execCommand(command, false, args);
		} catch(e) {
			console.log(e);
		}
		this.iframe.contentWindow.focus();
	};
	
	lwRTE.prototype.getToolbar = function() {
		var $textarea = $(this.textarea);
		return ($textarea.prev().length && $textarea.prev().hasClass('rte_toolbar')) ? $textarea.prev().get(0) : null;
	};
	
	lwRTE.prototype.activateToolbar = function(tb) {
		var old_tb = this.getToolbar();
	
		if(old_tb)
			old_tb.remove();
	
		$(this.textarea).before($(tb).clone(true));
	};
	
	lwRTE.prototype.autoGrow = function() {
		if($.browser.safari){
			this.iframe.style.height = 0;
		}
		this.iframe.style.height = Math.max(this.height, (parseInt(this.iframe_doc.body.style.marginTop) + parseInt(this.iframe_doc.body.style.marginBottom) + this.iframe_doc.body.offsetHeight + this.iframe_doc.body.offsetTop)) + 'px';
	};
	
	lwRTE.prototype.disableDesignMode = function() {
		var self = this;
		var $textarea = $(self.textarea);

		if (self.iframe) {
			$(self.iframe).remove();
			self.iframe = null;
			self.iframe_doc = null;
		}

		var tb = self.getToolbar();
		if (tb) {
			$(tb).remove();
		}
		
		return self;
	}

	lwRTE.prototype.enableDesignMode = function() {
		var self = this;
		var $textarea = $(self.textarea);
		
		if (!self.iframe) {
	
			// need to be created this way
			self.iframe	= document.createElement("iframe");
			self.iframe.frameBorder = 0;
			self.iframe.frameMargin = 0;
			self.iframe.framePadding = 0;
			self.iframe.width = '100%';
			self.iframe.height = self.height || '100%';
	
			var $iframe = $(self.iframe);
			
			if ($textarea.attr('class'))
				self.iframe.className = $textarea.attr('class');
		
			
			$iframe.data('rte', this);
		
			var content	= $textarea.val().replace(/\n/ig, '<br>');
		
			$textarea.hide().after(self.iframe);
			
			//self.textarea=null;
		
			var css = (self.css_url) ? '<link type="text/css" rel="stylesheet" href="' + self.css_url + '" />' : '';
			var base = (self.base_url) ? '<base href="' + self.base_url + '" />' : '';
			var style = (self.css_class) ? 'class="' + self.css_class + '"' : '';
		
			// Mozilla need this to display caret
			if($.trim(content) == '') content = '<br>';
		
			var doc = ['<html><head>', base, css, '</head><body ', style, 'style="padding:0;margin:2px;">', content, '</body></html>'].join('');
		
			self.iframe_doc	= self.iframe.contentWindow.document;
			var $iframe_doc = $(self.iframe_doc);
		
			try {
				self.iframe_doc.designMode = 'on';
			} catch ( e ) {
				// Will fail on Gecko if the editor is placed in an hidden container element
				// The design mode will be set ones the editor is focused
				$(self.iframe_doc).focus(function() { self.iframe_doc.designMode(); } );
			}
		
			self.iframe_doc.open();
			self.iframe_doc.write(doc);
			self.iframe_doc.close();
		
			var $body = $(self.iframe_doc.body);
			
			var timeout=null;
			$body
			.bind('DOMSubtreeModified.autoGrow', function(){
				self.autoGrow();
			})
			.bind('DOMSubtreeModified.syncContent', function(){
				if (timeout) {
					window.clearTimeout(timeout);
				}
				
				timeout = window.setTimeout(function() {
					if($iframe.is(':visible')) self.syncContent(); 
				}, 100);
			});

            //针对IE6、7下DomSubtreeModified不可用进行修正
            if (Q.browser.msie && (Q.browser.version <= 7)) {

                $body.bind('blur', function(e) {
                    syncContent();
                    e.preventDefault();
                    return false;
                });

                $textarea.parents('form:eq(0)').submit(function (e) {
                    syncContent();
                });

                var syncContent = function() {
                    $textarea.val($body.html()).change();
                }
            }

			$iframe_doc
			.mouseup(function(event) { 
				if(self.iframe_doc.selection)
					self.range = self.iframe_doc.selection.createRange();  //store to restore later(IE fix)
		
				self.setSelectedControls( (event.target) ? event.target : event.srcElement, self.controls); 
			})
			.keyup(function(event) { 
				self.setSelectedControls( self.getSelectedElement(), self.controls); 
			});
		
			// Mozilla CSS styling off
			if(!$.browser.msie)
				self.execCommand('styleWithCSS', false);
				
			if(!self.toolbar)
				self.toolbar = self.createToolbar(self.controls);
	
			self.activateToolbar(self.toolbar);
		
		}

		$iframe.show();
	
		window.setTimeout(function() {
			self.autoGrow();
		}, 20);
	
		return self;
	};
		
	lwRTE.prototype.content = function() {
		return $(this.iframe_doc.body).html();
	};
		
	lwRTE.prototype.syncContent = function() {
		var self = this;
		var content= this.content();
	
		var $textarea = $(self.textarea);
		
		//HTML to XHTML
		content=content
			.replace( 
		/(<\s*(?:img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param)(.*?)(?!\/))>/ig, '$1/>')
			.replace(/<br(\s*)\/>/ig, '\n')
			.replace(/<div>(.*?)<\/div>/ig, '$1\n');
			
		if ($textarea.val() != content) {
			$textarea.html(content).change();
		}
	};
		
	lwRTE.prototype.toolbarClick = function(obj, control) {
		var fn = control.exec;
	
		$('.rte_panel', this.getToolbar()).remove();
	
		if(fn)
			fn.apply(this);
		else if(this.iframe && control.command) {
			var args = control.args;
	
			if(obj.tagName.toUpperCase() == 'SELECT') {
				args = obj.options[obj.selectedIndex].value;
	
				if(args.length <= 0)
					return;
			}
	
			this.execCommand(control.command, args);
		}
	};
		
	lwRTE.prototype.createToolbar = function(controls) {
		var self = this;
		var tb = $('<div class="rte_toolbar" ><ul /></div>');
		var obj, li;
		
		for (var key in controls){
			if(controls[key].separator) {
				li = $('<li class="separator" />');
			} else {
				if(controls[key].select) {
					obj = $(controls[key].select)
						.change( function(e) {
							self.event = e;
							self.toolbarClick(this, controls[this.className]); 
							return false;
						});
				} else {
					obj = $('<a href="#" />')
						.attr('title', (controls[key].hint) ? controls[key].hint : key)
						.attr('rel', key)
						.click( function(e) {
							self.event = e;
							self.toolbarClick(this, controls[this.rel]); 
							return false;
						});
				}
	
				li = $("<li/>").append(obj.addClass(key));
			}
	
			$("ul",tb).append(li);
		}
	
		return tb.get(0);
	};
	
	lwRTE.prototype.getContent = function() {
		return (this.iframe) ? $('body', this.iframe_doc).html() : $(this.textarea).val();
	};
	
	lwRTE.prototype.setContent = function(content) {
		(this.iframe) ? $('body', this.iframe_doc).html(content) : $(this.textarea).val(content);
	};
	
	lwRTE.prototype.setSelectedControls = function(node, controls) {
		var toolbar = this.getToolbar();
	
		if(!toolbar)
			return false;
			
		var key, i_node, obj, control, tag, i, value;
	
		for (key in controls) {
			control = controls[key];
			obj = $('.' + key, toolbar);
	
			obj.removeClass('active');
	
			if(!control.tags)
				continue;
	
			i_node = node;
			do {
				if(i_node.nodeType != 1)
					continue;
	
				tag	= i_node.nodeName.toLowerCase();
				if($.inArray(tag, control.tags) < 0 )
					continue;
	
				if(control.select) {
					obj = obj.get(0);
					if(obj.tagName.toUpperCase() == 'SELECT') {
						obj.selectedIndex = 0;
	
						for(i = 0; i < obj.options.length; i++) {
							value = obj.options[i].value;
							if(value && ((control.arg_cmp && control.arg_cmp(i_node, value)) || tag == value)) {
								obj.selectedIndex = i;
								break;
							}
						}
					}
				} else
						obj.addClass('active');
			}  while(i_node = i_node.parentNode);
		}
			
		return true;
	};
	
	lwRTE.prototype.getSelectedElement = function () {
		var node, selection, range;
		var iframe_win	= this.iframe.contentWindow;
		
		if (iframe_win.getSelection) {
			try {
				selection = iframe_win.getSelection();
				range = selection.getRangeAt(0);
				node = range.commonAncestorContainer;
			} catch(e){
				return false;
			}
		} else {
			try {
				selection = iframe_win.document.selection;
				range = selection.createRange();
				node = range.parentElement();
			} catch (e) {
				return false;
			}
		}
	
		return node;
	};
	
	lwRTE.prototype.getSelectionRange = function() {
		var rng	= null;
		var iframe_window = this.iframe.contentWindow;
		this.iframe.focus();
		
		if(iframe_window.getSelection) {
			var selectionObject = iframe_window.getSelection();
			rng = selectionObject.getRangeAt(0);
			if($.browser.opera) { //v9.63 tested only
				var s = rng.startContainer;
				if(s.nodeType === Node.TEXT_NODE)
					rng.setStartBefore(s.parentNode);
			}
		} else {
			this.range.select(); //Restore selection, if IE lost focus.
			rng = this.iframe_doc.selection.createRange();
		}
	
		return rng;
	};
	
	lwRTE.prototype.getSelectedText = function() {
		var iframe_win = this.iframe.contentWindow;
	
		if(iframe_win.getSelection)	
			return iframe_win.getSelection().toString();
	
		this.range.select(); //Restore selection, if IE lost focus.
		return iframe_win.document.selection.createRange().text;
	};
	
	lwRTE.prototype.getSelectedHTML = function() {
		var html = null;
		var iframe_window = this.iframe.contentWindow;
		var rng	= this.getSelectionRange();
	
		if(rng) {
			if(iframe_window.getSelection) {
				var e = document.createElement('div');
				e.appendChild(rng.cloneContents());
				html = e.innerHTML;		
			} else {
				html = rng.htmlText;
			}
		}
	
		return html;
	};
		
	lwRTE.prototype.selectionReplaceWith = function(html) {
		if($(this.iframe).is(':visible')){
			var rng	= this.getSelectionRange();
			var iframe_window = this.iframe.contentWindow;
		
			if(!rng)
				return;
		
			this.execCommand('removeFormat'); // we must remove formating or we will get empty format tags!
			if(iframe_window.getSelection) {
				if(rng.collapsed){
					if(rng.startContainer.nodeValue=='BODY'){
						$('body', this.iframe_doc).html(html);
					}else{
						rng.insertNode(rng.createContextualFragment(html));
					}
				}else{
					rng.deleteContents();
					rng.insertNode(rng.createContextualFragment(html));
				}
			} else {
				this.execCommand('delete');
				rng.pasteHTML(html);
			}
		}else{
			if(document.selection){
				document.selection.createRange().text=html;
			}else{
				this.textarea.value=[this.textarea.value.substr(0,this.textarea.selectionStart), html, this.textarea.value.substr(this.textarea.selectionStart)].join('');
			}
		}
	};
	
	
	$.fn.rte = function(options) {
		var rte = new lwRTE(this, options);
		$(this).data('rte', rte);
		return rte;
	};
	


})(jQuery);

