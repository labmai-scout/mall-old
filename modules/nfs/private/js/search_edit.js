jQuery(function($){
	var $form = $('#' + form_id);
	var $select_all = $('#' + select_all_id);
	var $download = $('#' + download_id);

	if ($download.length) {	
		$dropdown = $download.siblings('div.nfs_dropdown');

        $download.bind('click', function() {

        	offset = $download.position();
	        dropdown_left = offset.left;
	        dropdown_top = offset.top + 23;

	        $dropdown.css({
	            left: dropdown_left,
	            top: dropdown_top
	        });

            if ($dropdown.hasClass('hidden')) {
                $dropdown.removeClass('hidden');
            }
            else {
                $dropdown.addClass('hidden');
            }

            return false;
        });

        $dropdown.find('a').bind('click', function() {
            var $selects = $form.find('input[name="select\[\]"]');

            var selected = 0;
            $.each($selects, function(k, v) {
                if ($selects.eq(k).is(':checked')) {
                    selected += 1;
                }
            });

            if (selected > 0) {
                $input = $('<input class="hidden" name="download_type" />');
                $input.val($(this).attr('name'));
                $input.appendTo($form);
                $form.submit();
            }

            return false;
        });

        $('body').bind('click', function() {
            if (!$dropdown.hasClass('hidden')) {
                $dropdown.addClass('hidden');
            }
        });
	}
	//全选按钮
	$select_all
	.bind('click', function(){
		if(Q.browser.msie){
			$(this).change();
		}
	})
	.bind('change', function(){
		// console.log('here');
		if ($select_all.is(':checked')) {
			$form.find('input[name="select\[\]"]').attr('checked', 'checked');
			$select_all.attr('checked', 'checked');
		}
		else {
			$form.find('input[name="select\[\]"]').removeAttr('checked');
			$select_all.removeAttr('checked');
		}
	});
});