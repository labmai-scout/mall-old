/*
 * index: 递增索引
 * root_id: 根容器随机ID
 * delete_message: 删除的确认消息
 */
jQuery(function($){
    var $root = $('#' + root_id);
    var token_regex = new RegExp(Q.escape(encodeURIComponent(index_token)) + '|' + Q.escape(index_token), 'g');
    
    $('.flexform_button_add', $root).live('click', function(){
		
		var $button = $(this);
		if ($button.parents('.flexform:first')[0] != $root[0]) {
			return;
		}
		
		// 保证index递增    	
    	index ++;

		var tpl = $root.find('script.flexform_template')[0].text;
	 	var $new = Q.clone(tpl, index, [
    		{pattern: token_regex, value: index}
    	]);

    	$new.find('input').removeClass('is_template');

   		$root.children('.flexform_container').append($new);
    });
    
    $('.flexform_button_delete', $root).live('click', function(){
		var $button = $(this);
		if ($button.parents('.flexform:first')[0] != $root[0]) {
			return;
		}

		if ( $button.is('.flexform_no_delete_confirm') || confirm(delete_message)){
			$button.parents('div.flexform_item:first').remove();
		}
    });
    
});
