(function($){
	
	Q.Tooltip = function(opt) {
		if (typeof(opt) !== 'object') {
			opt = {content: opt};
		}
		this.opt = opt;
	};
	
	var _checkSizeInterval;
	
	Q.Tooltip.prototype.show = function(x, y, $prev) {	
	
		if (this.$tip) {
			return;
		}
		
		var $tip = this.$tip;
	
		$tip = $('<div class="tooltip"><div class="tooltip_content" /></div>');
		if (this.opt.extraClass) {
			$tip.addClass(this.opt.extraClass);
		}
		$tip.children('.tooltip_content').html(this.opt.content);
		if (typeof($prev)!=='undefined') {
			$prev.after($tip);
		}
		else {
			$tip.appendTo('body');
		}
		$tip.css({display: 'none'});
		$tip.bind('mouseover', function(){
			$tip.remove()
		});

		var w = $tip.outerWidth(true);
		var h = $tip.outerHeight(true);

		this.x = x - Math.round(w/2) + 1;
		this.x = (this.x > 0) ? this.x : 0;
		this.y = y - h;

		$tip
		.css({
			left: this.x, 
			top: this.y + 5,
			opacity: 0, 
			display: 'block'
		})
		.animate({
			top: this.y,
			opacity: 1
		}, 50);
		
		_checkSizeInterval = setInterval(function(){
			var nw = $tip.outerWidth(true);
			var nh = $tip.outerHeight(true);
			
			if (nw != w || nh != h) {
				w = nw;
				h = nh;

				this.x = x - Math.round(w/2) + 1;
				this.y = y - h;
		
				$tip
				.css({
					left: this.x
				})
				.clearQueue()
				.animate({
					top: this.y
				}, 50);
	
			}

		}, 50);
		
		
		this.$tip = $tip;
		
	};
	
	Q.Tooltip.prototype.remove = function() {
		if (_checkSizeInterval) {
			clearInterval(_checkSizeInterval);
			_checkSizeInterval = null;
		}
		
		if (this.$tip) {

			/*
			this.$tip
			.animate({
				top: this.y - 10,
				opacity: 0
			}, 50, function(){
				$(this).remove();
			});
			*/
			
			this.$tip.remove();

			this.$tip = null;
		}
	};
	
	function _mouseenter(e) {
		var el = this;
		var $el = $(this);
		
		var tooltip = $el.data('tooltip');
		
		if (!tooltip) {
			tooltip = new Q.Tooltip({
				content: $el.classAttr('tooltip'),
				extraClass: $el.classAttr('tooltip_class'),
				position: $el.classAttr('tooltip_position'),
				offsetY: parseInt($el.classAttr('tooltip_offsetY')||0, 10)
				/*
					NO. BUG#151 (Cheng.Liu@2010.11.10)
					将offsetY的值在为Nan时修改为默认为0
					避免无法定位top坐标
				*/
			});
			$el.data('tooltip', tooltip);
		}
		
		if (tooltip.removeTimeout) {
			window.clearTimeout(tooltip.removeTimeout);
			tooltip.removeTimeout = null;
		}

		if (tooltip.showTimeout) {
			window.clearTimeout(tooltip.showTimeout);
			tooltip.showTimeout = null;
		}

		tooltip.showTimeout = window.setTimeout(function(){

			if ($el.data('tooltip_suppress')) {
				return;
			}
		
			var offset = $el.offset();
			
			var deltaX;
			switch (tooltip.opt.position) {
			case 'left':
				deltaX = Math.min(15, Math.round($el.outerWidth() / 4));
				break;
			case 'right':
				deltaX = Math.max($el.outerWidth() - 15, Math.round($el.outerWidth() * 3 / 4));
				break;
			default:
				deltaX = Math.round($el.outerWidth() / 2) - 1;
			}

			tooltip.show(offset.left + deltaX, offset.top + tooltip.opt.offsetY - 3);
			tooltip.$tip
			.mouseenter(function(){
				if (tooltip.showTimeout) { return; }
				_mouseenter.apply(el);
				return false;
			})
			.mouseleave(function(){
				if (tooltip.removeTimeout) { return; }
				_mouseleave.apply(el);
			});
			
		}, 50);

	}
	
	function _mouseleave(e) {
		var $el = $(this);
		var tooltip = $el.data('tooltip');
		if (!tooltip) { return; }
		
		if (tooltip.showTimeout) {
			window.clearTimeout(tooltip.showTimeout);
			tooltip.showTimeout = null;
		}
		tooltip.removeTimeout = window.setTimeout(function(){
			tooltip.remove();
		}, 50);
	}
	
	$('[class*="tooltip\:"], [q-tooltip]')
	.live('mouseenter', _mouseenter)
	.live('mouseleave', _mouseleave);

})(jQuery);
