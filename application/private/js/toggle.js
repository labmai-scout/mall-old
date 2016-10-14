(function($){

	$('.monitor:enabled').livequery(function(){
		var $monitor = $(this);
		
		$monitor.click(function(){
			$monitor.trigger('change.toggle');
		});

		var monitor_val = function(){
			var val = null;
			if ($monitor.is(':radio') && !$monitor.is(':checked')) {
			}
			else {
				if ($monitor.is(':checkbox')) val = $monitor.is(':checked') ? 'on':'off';
				else val = $monitor.val();
			}
			return val;
		};

		var monitor_toggle = function(el, val) {
			var $el = $(el);
			var cls = $el.attr('class') || '';
			if (cls.indexOf('show_on') >= 0) {
				if (cls.indexOf('show_on:'+val) >=0) {
					$el.show();
				}
				else {
					$el.hide();
				}
			}
			else if (cls.indexOf('hide_on') >= 0) {
				if (cls.indexOf('hide_on:'+val) >=0) {
					$el.hide();
				}
				else {
					$el.show();
				}
			}
		};			

		var monitor_toggle_status = function(el, val) {
			var $el = $(el);
			var cls = $el.attr('class') || '';
			var $inputs;
			if (cls.indexOf('enable_on') >= 0) {
				if (cls.indexOf('enable_on:'+val) >=0) {
					$el.filter(':input').add($el.find(':input'))
					.each(function() {
						var $el = $(this);
						$el.removeAttr('disabled');
						if ($el.is(':visible')) {
							$el.hide().show();
						}
					});
				}
				else {
					$el.filter(':input').add($el.find(':input'))
					.each(function() {
						var $el = $(this);
						$el.attr('disabled', 'disabled');
						if ($el.is(':visible')) {
							$el.hide().show();
						}
					});
				}
			}
			else if (cls.indexOf('disable_on') >= 0) {
				if (cls.indexOf('disable_on:'+val) >=0) {
					$el.filter(':input').add($el.find(':input'))
					.each(function() {
						var $el = $(this);
						$el.attr('disabled', 'disabled');
						if ($el.is(':visible')) {
							$el.hide().show();
						}
					});
				}
				else {
					$el.filter(':input').add($el.find(':input'))
					.each(function() {
						var $el = $(this);
						$el.removeAttr('disabled');
						if ($el.is(':visible')) {
							$el.hide().show();
						}
					});
				}
			}

			if (cls.indexOf('writable_on') >= 0) {
				if (cls.indexOf('writable_on:'+val) >=0) {
					$el.filter(':input').removeAttr('readonly').removeClass('readonly');
					$el.find(':input').removeAttr('readonly').removeClass('readonly');
				}
				else {
					$el.filter(':input').attr('readonly', 'readonly').addClass('readonly');
					$el.find(':input').attr('readonly', 'readonly').addClass('readonly');
				}
			}
			else if (cls.indexOf('readonly_on') >= 0) {
				if (cls.indexOf('readonly_on:'+val) >=0) {
					$el.filter(':input').attr('readonly', 'readonly').addClass('readonly');
					$el.find(':input').attr('readonly', 'readonly').addClass('readonly');
				}
				else {
					$el.filter(':input').removeAttr('readonly').removeClass('readonly');
					$el.find(':input').removeAttr('readonly').removeClass('readonly');
				}
			}
		};

		$monitor
		.bind('change.toggle', function(){

			var val = monitor_val();
			if (!val) return;

			var $root = $monitor.parents('form');
			if (!$root.length) $root = $(document);
			var name = Q.escape($monitor.attr('name'));

			$root.find('.toggle\\:' + name)
				.each(function(){
					monitor_toggle(this, val);
				});
			
			$root.find('.toggle_status\\:' + name)
				.each(function(){
					monitor_toggle_status(this, val);
				});

		});
	

		(function(){
			var name = Q.escape($monitor.attr('name'));
			var $root = $monitor.parents('form');
			if (!$root.length) $root = $(document);

			$('.toggle\\:' + name, $root)
			.livequery(function() {
				var val = monitor_val();
				if (!val) return;
				monitor_toggle(this, val);
			});

			$('.toggle_status\\:' + name, $root)
			.livequery(function() {
				var val = monitor_val();
				if (!val) return;
				monitor_toggle_status(this, val);
			});
		})();
		
	});
	// class="toggle_status:xxx enable_on:xx"
})(jQuery);
