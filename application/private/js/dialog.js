(function($) {

	window['Dialog'] = {
		autoWidthInterval: 0,
		render: function() {
            if (!Dialog.view) {
                Dialog.view = $('<div class="dialog"><div class="dialog_block" /><div class="dialog_wrapper"><div class="dialog_border"><div class="dialog_close_border clearfix"><div class="dialog_title"/><a class="dialog_close">&#160;</a></div><div class="dialog_content"/></div></div></div>');
            }
		},
		show: function(opt) {

			var $body = $('body > table');
            Dialog.render();
			
			var view = Dialog.view;
			var background = view.find('.dialog_block');
			var wrapper = view.find('.dialog_wrapper');
			var border = view.find('.dialog_border');
			var content = view.find('.dialog_content');
			var close_button = view.find('.dialog_close');
			var close_border = view.find('.dialog_close_border');
			var dialog_title = view.find('.dialog_title');
		
			view.appendTo('body');

			if (typeof opt != 'object') {
				var data = opt;
				opt = {
					data: data
				};
			}
	
            opt = $.extend(opt || {}, {
                keyboard: true
            });

			$(document).bind('keydown.dialog', function(e, close) {
                //如果esc后 keyboard为false，则可进行close操作
				if (close || (e.keyCode == 27 && opt.keyboard)) {
					if ($.isFunction(opt.cancel)) {
						opt.cancel();
					}
					Dialog.close();
					return false;
				}
			});
			
			close_button.bind('click',function(){
				$(document).trigger('keydown.dialog', [1]);
				return false;
			});

			view.show();
			
			wrapper.css({
				'margin-top': $(document).scrollTop() + 60
			});
			
			background.css({
				zIndex: 600,
				left: 0,
				top: 0,
				height: $body.height(),
				width: $body.width()
			})
			.show();

			if (opt.src) {
				content.addClass("noload").attr("src", opt.src);
			}
			
			if (opt.title) {
				dialog_title.html(opt.title);
			}
			else {
				dialog_title.html("&#160;");
			}
			
			if (opt.drag) {
				border.css({left: 'auto', top: 'auto'});
				border.data('drag.data', null);
				close_border.bind('mousedown touchstart', function(e) {
					e = Q.event(e);
					var isTouch = e.isTouch;
					var $warp_body = $('body');
					
					var oldCursor = $warp_body.css('cursor');
					$warp_body.css({cursor: 'pointer'});
					
					border.css({position: 'absolute', display: 'block', width: border.width()});
					
					var left = border.position().left;
					var top = border.position().top;
					var x = left - e.pageX;
					var y = top - e.pageY;
					
					var $drag = border.data('drag.data') || {
						min_left: 0 - border.offset().left,
						max_left: $body.outerWidth() - border.offset().left - border.outerWidth(),
						min_top: 0 - border.offset().top,
						max_top: $body.outerHeight() - border.offset().top - border.outerHeight(),
						width: border.width()
					};
					
					
					var _dragmove = function(e) {
						e = Q.event(e);
						
						border.css({
							left: Math.min(Math.max(e.pageX + x, $drag.min_left), $drag.max_left),
							top: Math.min(Math.max(e.pageY + y, $drag.min_top), $drag.max_top)
						});
					};
					
					var _dragend = function(e) {
						e = Q.event(e);
						border.data('drag.data', $drag);
						
						if (isTouch) {
							close_border
							.unbind('touchmove', _dragmove);
						}
						else {
							$(document)
							.unbind('mousemove', _dragmove);
						}
						
						$warp_body.css({cursor: oldCursor});
					}
					
					if (isTouch) {
						close_border
						.bind('touchmove', _dragmove)
						.one('touchend', _dragend);
					}
					else {
						$(document)
						.bind('mousemove', _dragmove)
						.one('mouseup', _dragend);
					}
				});
			}
			
			function _show(opt) {		
				if (opt.width) border.css({'min-width':opt.width});

				Dialog.autoWidthInterval = window.setTimeout(function() {
					var width = border.outerWidth(true);
                    if (Q.browser.msie && Q.browser.version < 9) width -= 2;
					if (width != wrapper.innerWidth()) {
						
						//wrapper.css({'width':width, 'min-width':opt.width});
						wrapper.css({'width':width});
						close_border.css({'width':(width - 10) });
					}

					if (Q.browser.msie && Q.browser.version<8) {
						wrapper.css({left: $(document).scrollLeft() + ($(window).width() - width)/2 });
					}

					var height = $body.height();
					var width = $body.width();
					if (height != background.height()) {
						background.css({'height':height});
					}
					if (width != background.width()) {
						background.css({'width':width});
					}

				}, 100);

				content.html(opt.data);
				border.addClass('dialog_shadow');
				// 默认选中对话框中第一个元素
				content.find(':input:visible:first').trigger('focus');

                //dialog增加succes，可进行dialog相关事件绑定
                if ($.isFunction(opt.success)) {
                    opt.success();
                }
			}

			if (opt.url) {
				$.post(opt.url, opt.post||{}, function(data) {
					opt.data = data; _show(opt);
					if($.isFunction(opt.success)){
						opt.success.apply(this);
					}
				
				} );
			}
			else {
				_show(opt);
			}

		},
		close: function() {
            //初始化jquery return 对象
            var ret_obj = {ret: true, cfm: false};
            $(Dialog).trigger('before_close', [ret_obj]);
            //如果存在ret_obj.confirm，则进行confirm，否则直接关闭
            if (!ret_obj.cfm || confirm(ret_obj.cfm)) {
                $(document).unbind('keydown.dialog');
                Dialog.view.find('.dialog_close_border').unbind('mousedown touchstart');
                if (Dialog.view) {
                    Dialog.view.find('.dialog_content').empty();
                    Dialog.view.hide();

                    //将dialog宽度设置成最小
                    Dialog.view.find('.dialog_close_border').width(1);
                    Dialog.view.find('.dialog_wrapper').width(1);
                }
                clearInterval(Dialog.autoWidthInterval);

                $(document).trigger('dialog_close');
            }
            $(document).trigger('clean.float.view');
        }
	};

	Q.ajaxProcess.dialog = function(data, status, url){
			if(data == "#close") {
				Dialog.close();
			}
			else if (data) {
				if (url) {
					if (typeof data != "object") {
						data = {data:data};
					}
					data.src = url;	
				}
				Dialog.show(data);
			}
		};

})(jQuery);
