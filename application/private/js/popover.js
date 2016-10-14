/*
NO.TASK#313(guoping.zhang@2011.01.12)
列表信息预览功能
*/
jQuery(function($){

	var $popover_container = $('.popover_container');
	var $document = $(document);
	var document_height;
	var document_width;
	var old_popover_container_height;
	var old_popover_arrow_top;
	var click_on_popover;
	var mouse_event;

	//显示popover
	function show_popover() {
		click_on_popover = false;
		document_height = $document.height();
		document_width  = $document.width();

		var el = $popover_container.data('popover_element');
		if (!el) return;
		var $el = $(el);

		var popover_container_top  = $el.offset().top + ($el.outerHeight() - $popover_container.outerHeight() )/ 2;
		var popover_container_left = $el.offset().left + $el.outerWidth() - 10;
		var popover_arrow_top      = ( $popover_container.outerHeight() - $popover_arrow.outerHeight() )/2; 

		$popover_container
		.css({
			left: popover_container_left,
			top:  popover_container_top>0?popover_container_top:0
		});
		$popover_arrow.css({
			top:  popover_arrow_top
		});

		$popover_container.find('.popover_content').addClass('popover_loading').empty();
		$popover_container.show();

		old_popover_container_height = $popover_container.height();
		old_popover_arrow_top = parseInt($popover_arrow.css('top').replace('px',''));
	}

	function adjust_popover() {
		var el = $popover_container.data('popover_element');
		if (!el) return;
		var $el = $(el);
		if( $document.width() > document_width ) {
			if ( $popover_container.width() <= ($el.offset().left - $popover_container.width() - $popover_arrow.width()) ){
				var popover_container_left   =  $el.offset().left - $popover_container.width() - $popover_arrow.width();
				$popover_container.css({'left':popover_container_left});
				left_arrow = '<div class="popover_arrow popover_arrow_left">&#160;</div>';
				$popover_arrow.hide();
				$popover_arrow = $(left_arrow);
				$popover_container.append($popover_arrow);
				$popover_container.find('.popover_content').addClass('popover_content_left');
				$popover_arrow.css({'top':old_popover_arrow_top});
			} else if( (document_width - $popover_container.width() )  > mouse_event.pageX ){
				var popover_container_left = document_width - $popover_container.width();
				$popover_container.css({'left':popover_container_left});
			} else {
				$popover_container.css({'left':(mouse_event.pageX + 15) } );
			}
		}

		if( $document.height() > document_height ) {
			var change = $popover_container.height() - old_popover_container_height;
			$popover_container.css({ 'top':($popover_container.offset().top-change) });
			$popover_arrow.css({'top':(old_popover_arrow_top + change)});
		}
	}


	var showTimeout = null;
	var timeout = 200;

	function unbind_popover_event() {
		$document.unbind('click.popover');
		$popover_container
			.unbind('click.popover');
	}
	//关闭previw,将各项css恢复初始值.
	function close_popover() {
		unbind_popover_event();
		$popover_container.hide().find('.popover_content').empty();
		$popover_container.data('popover_element', null);

		$popover_container.find('.popover_content').removeClass('popover_content_left');
		$popover_arrow = $popover_container.find('.popover_arrow').show();
		$popover_container.find('.popover_arrow_left').remove();
	}
	
	//点击事件弹出的pieview
	$('[q-popover]').live('click', function(e){
		if($popover_container.size() == 0 ){

			$popover_container = $('<div class="popover_container" ><div class="popover_arrow">&#160;</div><a class="popover_close">&#160;</a><div class="popover_content"></div></div>');
			$popover_container.appendTo( $('body') ).css({top: -10000, left:0});
		}
		var $popover_arrow = $popover_container.find('.popover_arrow');
		close_popover();

		mouse_event = e;

		var $el = $(this);
		var curr_el = $popover_container.data('popover_element');
		var el = $el[0];
		if (el == curr_el) {
			return;
		}
		$popover_container.data('popover_element', el);

		$popover_container
		.bind('click.popover', function(e) {
			click_on_popover = true;
		});

		show_popover();
		adjust_popover();

		showTimeout = setTimeout(function() {
			//获取传递过来的q-static参数
			var str = $el.attr('q-static');
			var data = Q.toQueryParams(str) || {};
			Q.trigger({
				object: 'popover',
				event: 'click',
				data: data,
				url: $el.attr('q-popover'),
				global: false,
				success: function(data, status) {

					if (data.popover) {
						$popover_container.find('.popover_content').removeClass('popover_loading').html(data.popover);
						adjust_popover();
						delete data.popover;
					}
				}
			});	
		}, timeout);
		return false;
	});

	$popover_container.find('.popover_close')
		.live('click', function(){
			close_popover();
		})
	
});
