jQuery(function($){

	// 单文件上传
	//点击按钮，弹出一个dialog
	var $upload_one_file = $('#' + upload_one_file);

	//多文件上传
	var $flash_plugin_message = $('#' + flash_plugin_message_id);
	var $upload_multiple_file = $('#' + upload_multiple_file_id);
	var $cancel_button = $('#' + cancel_button_id);
			
	$upload_one_file.click(function() {
		Dialog.show(upload_form);
	});

	Q.js_ready('nfs:swfupload nfs:swfupload.queue nfs:swfupload.file', function() {

		var opt={};
		
		var button_css = 'font-weight:normal; font-size:12px; font-family: "Lucida Grande", Helvetica, Arial, sans-serif; margin:0; padding:0;';
		
		var $dummy = $('<div style="position:absolute; white-space:nowrap; visibility: hidden; ' + button_css + '">' + button_text + '</div>').appendTo('body');
	
		var dummy_width = 
	
		$.extend(opt, Q.SWFUpload, {
			flash_url:"!nfs/swfupload.swf?v2",
			upload_url: upload_url,
			post_params: upload_post_params,
			file_size_limit: file_size_limit || "20 MB",
			file_types:"*.*",
			file_types_description:"All Files",
			file_upload_limit:0,
			file_queue_limit:0,
			custom_settings: {
				fileContainer: file_container_id,
				fileTemplate: file_template_id,
				cancelButton: cancel_button_id,
				fileUploaded: function(file, data){
					$('#' + file.id).replaceWith(data);
				}
			},
		
			button_image_url: button_image_url,
		
			button_width: $dummy.width() + 20 + (Q.browser.safari ? 5 : 4),
			//button_height: $dummy.height() + (Q.browser.safari ? 2 : 3),
			button_height: 21,
			button_text: '<span class="button_text">' + button_text + '</span>',
			button_placeholder_id: button_placeholder_id,
			button_text_style: '.button_text { ' + button_css + '}',
			button_text_left_padding: 17,
			button_text_top_padding: (Q.browser.msie ? 0 : 1),
			button_window_mode : SWFUpload.WINDOW_MODE.TRANSPARENT,
		
			debug:false
		
		});

		$dummy.remove();
		
		var su = new SWFUpload(opt);
		/*
		NO.BUG#144（guoping.zhang@2010.11.13)
		判断客户端是否存在flash:
			如果不存在，则隐藏flash上传（批量上传）按钮；
			如果存在，则隐藏下载flash插件；
		*/
		if (su.support.loading) {
			$flash_plugin_message.hide();
			$upload_multiple_file.show();
		}
		else {
			$upload_multiple_file.hide();
			$flash_plugin_message.show();
		}
		
		$cancel_button.click(function(){
			su.cancelQueue();
			return false;
		});
		
	});
	
});
