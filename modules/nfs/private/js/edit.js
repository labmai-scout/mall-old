jQuery(function($){
	
	var $form = $('#' + form_id);
	var $select_all = $('#' + select_all_id);
	var $delete_all = $('#' + delete_all_id);
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
            else {
                alert(download_all_alert);
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
		if ($select_all.is(':checked')) {
			$form.find('input[name="select\[\]"]').attr('checked', 'checked');
			$select_all.attr('checked', 'checked');
		}
		else {
			$form.find('input[name="select\[\]"]').removeAttr('checked');
			$select_all.removeAttr('checked');
		}
	});

	//批量删除
	$delete_all.bind('click',function(){
		var $selects = $form.find('input[name="select\[\]"]');
		var delete_path = new Array();
		var num = 0;
		$.each($selects, function(k,v){
			if ($selects.eq(k).is(':checked')) {
				delete_path[num] = $(v).val();
				num ++;
			}
		});
		if (delete_path.length > 0) {
			Q.trigger({
				url : submit_url,
				object : 'delete_all',
				event : 'click',
				data : {
					delete_path : delete_path,
					path : path,
					form_token : form_token
				}
		
			});
		}
        else {
            alert(delete_all_alert);
        }
		return false;
	});
});
