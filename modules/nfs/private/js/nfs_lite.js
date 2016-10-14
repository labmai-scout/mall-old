jQuery(function($) {
	$('.file').livequery(function(e) {
	
		var $rename_link = $(this).closest('tr').find('a.rename').bind('click', function(e) {
			// 阻止链接
			e.preventDefault();

			// 隐藏更名、删除链接
			var $delete_link = $rename_link.siblings('a.delete');
			$rename_link.hide();
			$delete_link.hide();

			// 显示更名输入框
			var $file_name = $rename_link.closest('tr').find('span.file');
			var file_name = $.trim($file_name.find('a').text());
			var $rename_input = $('<input class="text middle" value="' + file_name + '">');
			var $rename_submit = $('<a class="button button_save middle submit">' + submit_text + '</a>');
			var $cancel_button = $('<a class="button button_cancel middle">' + cancel_text + '</a>');


			// 定义提交更名的函数
			var submitting = false;
			var rename = function(old_name, new_name) {
				Q.trigger({
					object: 'rename_file',
					event: 'submit',
					url: url,
					data: {
						'old_name': old_name,
						'name': new_name
					},
					success: function(data) {
						submitting = false;
					}
				});
			};
			var submit_now = function() {
				if (submitting) {
					return;
				}
				submitting = true;
				rename(file_name, $rename_input.val());
			};

			// 绑定
			var $file_name_backup = $file_name.clone();
			$file_name.replaceWith($rename_input);
			$rename_input.after($rename_submit);
			$rename_submit.after($cancel_button);
			$rename_submit.after('&#160;');
			$rename_input.after('&#160;');
			$rename_input.focus().bind('keypress', function(e) {
				var code = (e.keyCode ? e.keyCode : e.which);
				if (code === 13) {		// Enter keycode
					e.preventDefault(); // 阻止表单提交
					submit_now();
				}
				if (code === 27) {	// ESC
					e.preventDefault();
					$rename_input.replaceWith($file_name_backup);
					$rename_submit.remove();
					$rename_link.show();
					$delete_link.show();
				}
			});
			$rename_input.bind('keydown.dialog', function(e) {
				// 阻止dialog中按ESC关闭
				// TODO bind 中的 dialog 无用
				var code = (e.keyCode ? e.keyCode : e.which);
				if (code === 27) {	// ESC
					e.preventDefault();
					return false;
				}
			});
			$cancel_button.bind('click', function(e) {
				$delete_link.show();
				$rename_link.show();
				$rename_input.parent('td:first').html($file_name_backup);
			});
			$rename_submit.bind('click',  function(e) {
				submit_now();
			});

		});

	});
	//绑定删除文件后进行‘广播‘     
	(function(){
		var $tbody = $('#' + table_id + ' tbody');
		var $div = $tbody.parents('div:eq(1)');
		
		var _timeout = null;
		
		if( $div.data('event')==undefined || !$div.data('event') ) {//判断是否绑定了事件
			$div.bind("DOMSubtreeModified", function() {
				$event = $(this).data('event', 'true');
			    if ( _timeout ) return;
			    _timeout = setTimeout (function(){
			    	Q.broadcast($div, 'nfs[' + path_type + '_' + object_name +'].file_number_changed', {id:object_id} );
			    	_timeout = null;
			    }, 1000);
			});
		
		}
	})();
	

});
