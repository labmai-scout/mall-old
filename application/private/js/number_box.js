(function($) {

	$('input.number').livequery(function(){
		
		var $input = $(this);
		
		$input
		.change(function(){
			var val = parseFloat(this.value);
			$input.val(val ? val:'0');
		})
		.change();
	
	});
	
	
	$(':visible > input.currency').livequery(function(){
	
		var $input = $(this);
		var prefix = $input.attr('sign') || '';
		var $hidden = $('<input type="hidden" />');

		$hidden.attr('name', $input.attr('name'));
		$input.removeAttr('name').after($hidden);
		
		$hidden.bind('change.currency', function(){
			var val = parseFloat(this.value);
			$input.val(prefix + (val ? val.toFixed(2):'0.00'));
			$input.attr('defaultValue', $input.val());
		});
	
		$input
		.bind('focus.currency', function () {
            //对于readonly的input，不予focus
            if ($(this).hasClass('readonly'))  return;
			var val = parseFloat( $input.val().replace(prefix, ''));
			$input.val(val);
			setTimeout(function(){
				$input.select();
			},0);
			$hidden.data('old_value', $hidden.val());
			return false;
		})
		.bind('change.currency', function (){
			var val = parseFloat(this.value.replace(prefix, '')) || 0;
			$hidden.val(val).change();
		})
		.bind('blur.currency', function() {
			$input.change();
			$hidden.trigger('blur');
		})
		.change();
		
		$hidden.data('old_value', $hidden.val());
		
	}, function(){
		var $input = $(this);
		var $hidden = $(this).next('input[type=hidden]');
		$input.attr('name', $hidden.attr('name'));
		$hidden.remove();
		$input.unbind('focus.currency change.currency blur.currency');
	});


})(jQuery);
