jQuery(function($){

	$(':input[class*="hint:"], :input[q-hint]').livequery(function(){

		var $el=$(this);
		var hint = $el.classAttr("hint") || $el.attr('hint') || '';

		$el.focus(function() {
			if($el.val()==hint){
				$el.val('');
				$el.removeClass('hint');
			}
		}).blur(function() {
			if($el.val()==hint||$el.val()==''){
				$el.val(hint);
				$el.addClass('hint');
			}
		}).blur();
		
		$(':submit, :image', this.form).click(function() {
			if($el.hasClass('hint') && $el.val()==hint) $el.val('').removeClass('hint');
		});

		$(this.form).submit(function() {
			if($el.hasClass('hint') && $el.val()==hint) $el.val('').removeClass('hint');
		});
	});
	
});
