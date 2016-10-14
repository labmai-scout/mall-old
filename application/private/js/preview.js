/*
NO.TASK#313(guoping.zhang@2011.01.12)
列表信息预览功能
*/
jQuery(function($){

	var $preview_container = $('.preview_container');
	if($preview_container.size() == 0 ){

		$preview_container = $('<div class="preview_container" ><div class="preview_arrow">&#160;</div><div class="preview_content"/></div>');
		$preview_container.appendTo( $('body') ).css({top: -10000, left:0});
	}
	var $preview_arrow = $preview_container.find('.preview_arrow');

	var $document = $(document);
	var document_height;
	var document_width;
	var old_preview_container_height;
	var old_preview_arrow_top;
	var click_on_preview;
	var mouse_event;

	//显示preview
	function show_preview() {
		click_on_preview = false;
		document_height = $document.height();
		document_width  = $document.width();

		var el = $preview_container.data('preview_element');
		if (!el) return;
		var $el = $(el);

		var preview_container_top  = $el.offset().top + ($el.height() - $preview_container.height() )/ 2;
		var preview_container_left = $el.offset().left + $el.width();
		var preview_arrow_top      = ( $preview_container.height() - $preview_arrow.height() )/2; 

		$preview_container
		.css({
			left: preview_container_left,
			top:  preview_container_top>0?preview_container_top:0
		});
		$preview_arrow.css({
			top:  preview_arrow_top
		});

		$preview_container.find('.preview_content').addClass('preview_loading').empty();
		$preview_container.show();

		old_preview_container_height = $preview_container.height();
		old_preview_arrow_top = parseInt($preview_arrow.css('top').replace('px',''));
	}

	function adjust_preview() {
		var el = $preview_container.data('preview_element');
		if (!el) return;
		var $el = $(el);
		if( $document.width() > document_width ) {
			if ( $preview_container.width() <= ($el.offset().left - $preview_container.width() - $preview_arrow.width()) ){
				var preview_container_left   =  $el.offset().left - $preview_container.width() - $preview_arrow.width();
				$preview_container.css({'left':preview_container_left});
				left_arrow = '<div class="preview_arrow preview_arrow_left">&#160;</div>';
				$preview_arrow.hide();
				$preview_arrow = $(left_arrow);
				$preview_container.append($preview_arrow);
				$preview_container.find('.preview_content').addClass('preview_content_left');
				$preview_arrow.css({'top':old_preview_arrow_top});
			} else if( (document_width - $preview_container.width() )  > mouse_event.pageX ){
				var preview_container_left = document_width - $preview_container.width();
				$preview_container.css({'left':preview_container_left});
			} else {
				$preview_container.css({'left':(mouse_event.pageX + 15) } );
			}
		}

		if( $document.height() > document_height ) {
			var change = $preview_container.height() - old_preview_container_height;
			$preview_container.css({ 'top':($preview_container.offset().top-change) });
			$preview_arrow.css({'top':(old_preview_arrow_top + change)});
		}
	}


	var closeTimeout = null;
	var showTimeout = null;
	var timeout = 200;

	function unbind_preview_event() {
		$document.unbind('click.preview');
		$preview_container
			.unbind('mouseenter.preview')
			.unbind('mouseleave.preview')
			.unbind('click.preview');
	}
	//关闭previw,将各项css恢复初始值.
	function close_preview() {
		unbind_preview_event();
		$preview_container.hide().find('.preview_content').empty();
		$preview_container.data('preview_element', null);

		$preview_container.find('.preview_content').removeClass('preview_content_left');
		$preview_arrow = $preview_container.find('.preview_arrow').show();
		$preview_container.find('.preview_arrow_left').remove();
	}
	
	function reset_close_timeout() {
		if (closeTimeout) {
			clearTimeout(closeTimeout); 
			closeTimeout = null;
		}
	}

	function reset_show_timeout() {
		if (showTimeout) {
			clearTimeout(showTimeout);
			showTimeout = null;
		}
	}	

	function timeout_close_preview() {
		closeTimeout = setTimeout(function() {
			close_preview();	
		}, 1000);
	}

	
	//绑定mouseenter/mouseleave事件
	$('[q-preview]')
	.die('mouseenter.preview')
	.die('mouseleave.preview')
	.live('mouseenter.preview', function(e){
		close_preview();
		reset_close_timeout();
		reset_show_timeout();

		mouse_event = e;

		var $el = $(this);
		var curr_el = $preview_container.data('preview_element');
		var el = $el[0];
		if (el == curr_el) {
			return;
		}
		$preview_container.data('preview_element', el);

		$document
		.bind('click.preview', function(){
			if (!click_on_preview) {
				reset_show_timeout();
				reset_close_timeout();
				close_preview();
				$(this).unbind('click.preview');
			}
			click_on_preview = false;
		});

		$preview_container
		.bind('mouseenter.preview', function(e) {
			reset_close_timeout();
		})
		.bind('mouseleave.preview', function(e) {
			reset_close_timeout();
			timeout_close_preview();
		})
		.bind('click.preview', function(e) {
			click_on_preview = true;
		});

		show_preview();
		adjust_preview();

		showTimeout = setTimeout(function() {
			//获取传递过来的q-static参数
			var str = $el.attr('q-static');
			var data = Q.toQueryParams(str) || {};
			Q.trigger({
				object: 'preview',
				event: 'click',
				data: data,
				url: $el.attr('q-preview'),
				global: false,
				success: function(data, status) {
					if (data.preview) {
						$preview_container.find('.preview_content').removeClass('preview_loading').html(data.preview);
						adjust_preview();
						delete data.preview;
					}
				}
			});	
		}, timeout);

	})
	.live('mouseleave.preview', function(e){
		reset_close_timeout();
		timeout_close_preview();
	});
	
	
});
