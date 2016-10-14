(function($){
	$.fn.popFade = function(dx, dy, text) {
		// find the center of the element
		var $el = $(this);
		var o = $el.offset();
		var w = $el.width();
		var h = $el.height();

		var $div = $('<div class="pop_fade" style="position:absolute" />');
		$div.html(text).appendTo('body');

		var dw = $div.width(), dh = $div.height();

		var x = o.left + (w - dw) / 2 + dx;
		var y = o.top + (h - dh) / 2 + dy;
	
		$div.css({
			left: x, 
			top: y,
			opacity: 0 
		})
		.animate({
			left: x - dw / 2,
			top: y - dh, 
			width: dw * 2,
			height: dw * 2,
			fontSize: '200%'
		}, { queue: false, duration: 500})
		.animate({
			opacity: 1
		}, 250)
		.animate({
			opacity: 0 
		}, 250, function() {
			$div.remove();
		})
		;
			
	};
})(jQuery);
