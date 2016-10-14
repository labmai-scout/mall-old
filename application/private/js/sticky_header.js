(function($) {

	if (Q.browser.msie && Q.browser.version < 7) {
		return;
	}
	
	var $window = $(window);
	
	$('table.sticky').livequery(function() {
		
		var $table = $(this);
		var $header = $('thead', this).clone(true).insertBefore(this.parentNode).wrap('<table class="sticky_header"/>').parent().css({position:'fixed', top:'0px', zIndex:500});
		
		$header.find('.check_hover').trigger('mouseleave');
		
		var vPosition, vLength;
		var lastScrollHeight;
		
		//更新浮动表头位置
		function tracker() {

			var scrollHeight = document.documentElement.scrollHeight || document.body.scrollHeight;

			if (lastScrollHeight != scrollHeight) {
				lastScrollHeight = scrollHeight;
				
				vPosition = $table.offset().top;
				vLength = $table.height() - 40;
	
				// 修改header宽度
				var $tableHeaders = $('th', $table);
				$('th', $header).each(function(index) {
					$(this).width($tableHeaders.eq(index).width());
				});
				
				$header.width($table.width());
			}
			
			var hPosition = $table.offset().left;
	
			// Track horizontal positioning relative to the viewport and set visibility.
			var hScroll = document.documentElement.scrollLeft || document.body.scrollLeft;
			
			var vOffset = (document.documentElement.scrollTop || document.body.scrollTop) - vPosition;
			var visState = (vOffset > 0 && vOffset < vLength) ? 'visible' : 'hidden';

			$header.css({left: -hScroll + hPosition +'px', visibility: visState});
	
		}
	
		tracker();

		//响应滚屏
		$window.scroll(tracker);
	
		//响应窗口大小变更
		var sizing = false;
		$window.resize(function() {
			if (sizing) { return; }
			sizing = true;
			window.setTimeout(function() {
				lastScrollHeight = 0;
				tracker();
				sizing = false;
			}, 100);
		});
	});

	
})(jQuery);
