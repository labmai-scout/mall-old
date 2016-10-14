(function($){

	var _scrollbarWidth;
	Q['scrollbarWidth'] = function(){
		if (_scrollbarWidth === undefined) {
			var $t = $('<textarea cols="1" rows="1"/>');
			$t.css({visibility: 'hidden', position: 'absolute', left:-10000, top:-10000});
			$t.appendTo('body');
			$t[0].wrap = 'off';
			var w = $t[0].offsetHeight;
			$t[0].wrap = 'soft';
			w -= $t[0].offsetHeight;
			$t.remove();
			_scrollbarWidth = w || 0;
		}
		return _scrollbarWidth;
	};


	$('[confirm]').livequery(function(){
		var $el = $(this);
		$el.bind('click', function(){
			return confirm($(this).attr('confirm'));
		});
	});
	
	//如果是Quirk Mode  && version <= 6
	if(Q.browser.msie && Q.browser.version<7) {
		// IE6 BackgroundImageCache BUG
		try {
			document.execCommand('BackgroundImageCache', false, true);
		}
		catch(e) {}
		
		// IE6 不支持非A元素:hover
		$('.check_hover:not(a)')
		.live('mouseenter', function(e){
			var $el = $(this);
			$el
				.addClass('active').find('.show_on_hover')
				.each(function(){
					var $e = $(this);
					var c = $e.parents('.check_hover');
					if (c[0]==$el[0]) {
						$e.css({
							'visibility':'visible'
						}).show();
					}
				});
			
			$el.one('mouseleave', function(){
				$(this).removeClass('active').find('.show_on_hover').css('visibility', 'hidden');
			});
		});
		
	}
	
	//修复iPad offset获取错误
	if (/webkit.*mobile/i.test(navigator.userAgent)
		&& "getBoundingClientRect" in document.documentElement) {
		$.fn.offsetOld = $.fn.offset;
		$.fn.offset = function () {
			var result = this.offsetOld();
			if (this[0].tagName !== 'BODY') {
				result.top -= window.scrollY;
				result.left -= window.scrollX;
			}
			
			return result;
		};
	}

	$('[q-fullscreen]').live('click', function(){
		var $button = $(this);
		var $view = $('#' + $button.attr('q-fullscreen'));
		if ($view.hasClass('fullscreen')) {
			$view.removeClass('fullscreen');
			setTimeout(function(){
				$view.appendTo($view.data('fullscreen_origin'));
				$('body > table').show();
			}, 0);
			Q.is_fullScreen = false;
		}
		else {
			var w = $view.css('width');
			$view.data('fullscreen_origin', $view.parent()[0]);
			$('body > table').hide();
			$view.addClass('fullscreen').prependTo('body');
			Q.is_fullScreen = true;	
		}
		$(window).resize();
		return false;
	});

	$('img.loading').livequery(function(){
		var $el = $(this);
		
		if (Q.browser.msie && Q.browser.version<8) {
			$el.removeClass('loading');
			return;
		}

		var _on_load = function() {
			$el.removeClass('loading');
			if ($el[0] != this) $el[0].src = this.src;
		};

		if (this.src !== '') {
			if (this.complete || this.readyState === 4) {
				_on_load.apply(this);
			}
			else if (this.readyState === 'uninitialized' && this.src.indexOf('data:') === 0) {
				$el.trigger('error');
			}
			else {
				var image = new Image();
				image.src = this.src;
				this.src = 'images/blank.gif';
				$(image).load(_on_load);
			}
		}
		else {
			_on_load();
		}

	});

	Q['supportFix'] = function(){
		var container = document.body;
		if (document.createElement &&
			container && container.appendChild && container.removeChild) {
			
			var el = document.createElement("div");
			if (!el.getBoundingClientRect) {
				return false;
			}

			el.innerHTML = "x";
			el.style.cssText = "position:fixed;top:100px;";
			container.appendChild(el);

			var originalHeight = container.style.height, originalScrollTop = container.scrollTop;
			container.style.height = "3000px";
			container.scrollTop = 500;

			var elementTop = el.getBoundingClientRect().top;
			container.style.height = originalHeight;

			var isSupported = elementTop === 100;
			container.removeChild(el);
			container.scrollTop = originalScrollTop;

			return isSupported;
		}
		return false;
	};
	
	$(window).resize(function(){
		$document = $(document);
		var table_height = $('body>table').height();
		var document_height = $document.height();
		var change = document_height - table_height;
		if( change > 0 ) {
			var $center = $('#center');
			$center.height( $center.height() + change + 150 );
		}
	});

})(jQuery);

jQuery(function($){

	var $window = $(window);
	var $document = $(document);

	$('iframe.autosize')
	.each(function(){
		var iframe = this;
		var $iframe = $(iframe);

		function setWidth(){
			$iframe.width($iframe.parent().innerWidth());
		}
		
		setWidth();
		
		function setHeight() {
			$iframe.height(
				iframe.contentWindow.document.body.offsetHeight || iframe.contentWindow.document.body.offsetHeight
			);
			setWidth();
		}
		
		$window.resize(setWidth);
		
		$iframe.load(function(){
			setHeight();
		});

		if (safari) {
			var src = iframe.src;
			iframe.src = '';
			iframe.src = src;
		}
		
	});
	
	$('.select_on_focus').livequery('focus', function(){
		$(this).select();
	});

	//autosize for IE
	if (Q.browser.msie && Q.browser.version<=8) {
		$(window)
		.resize(function(){
			var sbh = $('#sidebar .sidebar_wrapper').height();
			var bh = $(window).height() - 60 - Q.scrollbarWidth();
			if (sbh < bh) {
				$('#sidebar').attr('height', bh + 4);	
			}
		}).resize();
	}

	// 重定义button 为了保证button的CSS一致性
	if (Q.browser.msie && Q.browser.version<9) {
		$(':visible input.button, :visible button')
		.livequery(function(){
			var $button = $(this);
			var $fake_button = $('<a />');
			$fake_button.attr('class', $button.attr('class')).addClass('button');
			//$fake_button.addClass('prevent_default');
			$fake_button.html($button.val() || $button.html());
			$button.after($fake_button).css({
				opacity: 0,
				position: 'absolute',
				left: -32767,
				top: -32767,
				zIndex: -100
			});
		
			$button.data('fake_button', $fake_button);

			$fake_button
			.unbind('click.fake_button')	//仅允许绑定一次
			.bind('click.fake_button', function(){
				$button.click();
				return false;
			});

		}, function(){
			var $button = $(this);
			var $fake_button = $button.data('fake_button');
			if ($fake_button && $fake_button.length) {
				$fake_button.remove();
			}
		});
	}

	//png fix
	if (Q.browser.msie && Q.browser.version<8) {
		//fix images with png-source
		var imgPattern = "img.pngfix:visible";
		
		$(document).find(imgPattern).livequery(function() {
			
			var $img = $(this);

			$img.attr('width',$img.width());
			$img.attr('height',$img.height());

			var src = this.src;
			if (src !== 'images/blank.gif') {
				this.src='images/blank.gif';
				this.srcBackup = src;
				this.style.filter='progid:DXImageTransform.Microsoft.AlphaImageLoader(src=\'' + src + '\', sizingMethod=\'crop\');';
			}

		}, function() {
			this.src = this.srcBackup;
			this.style.filter='none';
		});

	}

	$('.unselectable').livequery(function(){
		$(this).set_unselectable();
	});


	var $loadingBox = $('<div class="loading_box">&#160;</div>');
	$loadingBox.hide().appendTo('body');
	$loadingBox
	.ajaxStart(function() {
		var $box = Q.$loadingBox || $loadingBox;
		$loadingBox.data('box', $box);
		var $window = $(window);
		var w = $window.width();
		var h = $window.height();
		if (Q.supportFix()) {
			$box
			.css({
				position: "fixed",
				left: (w - $box.width()) / 2, 
				top: (h - $box.height()) / 2
			});
		}
		else {
			var $doc = $(document);
			var st = $doc.scrollTop();
			var sl = $doc.scrollLeft();
			$box
			.css({
				position: "absolute",
				left: sl + (w - $box.width()) / 2,
				top: st + (h - $box.height()) / 2
			});
		}
		$box.show();
	})
	.ajaxComplete(function() {
		var $box = $loadingBox.data('box');
		if ($box && $box.length) $box.hide();
	});

	//tab num_notif的IE兼容
	if (Q.browser.msie && Q.browser.version < 9) {
		$('.num_notif').livequery(function(){
			var $el = $(this);
			var of = $el.offset();
			$el.appendTo('body');
			var _zIndex = function($el) {
				var z = $el.css('z-index');
				$el.parents().each(function(){
					z = Math.max(z, $(this).css('z-index'));
				});
				return z;
			};
			$el.css({position:'absolute', left: of.left, right:'auto', top: of.top, zIndex: _zIndex($el) + 1});
		});
	}

	$('input:text[size], input:password[size]').livequery(function(){
		var $el = $(this);
		var w = parseInt(this.style.width);
	    if (!w) {
			w = parseInt($el.attr('size'));
			$el.css('width', w * 7);
		}	
	});

	$('textarea[cols]').livequery(function(){
		var $el = $(this);
		var w = parseInt(this.style.width);
	    if (!w) {
			w = parseInt($el.attr('cols'));
			$el.css('width', w * 7);
		}	
	});

	//autosize for IE
	if (Q.browser.msie && Q.browser.version<=8) {
		$('table.sticky>tbody>tr.row:even>td').livequery(function(){
			$(this).addClass('row_odd');
		});
		$('table.sticky>tbody>tr.row:odd>td').livequery(function(){
			$(this).addClass('row_even');
		});
	}


});
