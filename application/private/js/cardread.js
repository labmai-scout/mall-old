(function($) {

	$.fn.cardread = function(option) {

		var opt = {
			'width':200,
			'height':58
		};

		opt = $.extend(opt, option||{});
		var $obj = $(this);

		// 初始化，参数设置
		var $container = $('<div class="cardread_container center"><input type="password" class="text" /><p class="message"></p></div>');
		$container
			.appendTo($('body'))
			.css({
				'width' : opt.width + 'px',
				'height': opt.height + 'px'
			});
		var $button = $('<span class="cardread_button"></span>');
		$obj.after($button);

		var $input   = $container.find('input');
		var $message = $container.find('.message');

		//功能函数
		$container.intID = 0;
		$container.close = function () {
			this.hide();
			clearInterval(this.intID);
		}

		$container.open = function() {
			$('.cardread_container').hide();
			this.locate();
			this.find('input').val('');
			$message
				.removeClass('error')
				.addClass('description')
				.text(opt.normalMsg);
			$container.show();
			$input.focus();

			this.intID = setInterval(function() {
				if($obj.parents('body').length == 0 ){
					$container.close();
				}
			},2000);
		}
		
		// 在dialog中需要重新调整位置
		$container.locate = function() {
				this.css({
					'top'   : ($obj.offset().top + $obj.height()/2 - opt.height/2 ) + 'px',
					'left'  : ($obj.offset().left + $obj.width() - opt.width/2) + 'px'
				});
		}

		var getUser = function(ID) {
			$.ajax({
				url: opt.ajax,
				data: {'card_no':ID},
				global: false,
				success: function(data, status) {
					var user = data.user;
					if( user.alt ){
						$obj.focus();
						$obj.trigger('autoactivate.autocomplete', data.user);
						$container.close();
					} else {
						$message
							.text(opt.errorMsg)
							.removeClass('description')
							.addClass('error');
						$input.val('');
					}
				}
			});	

		}

		// 绑定事件
		$button.bind('click.cardread',function(){
			$container.open();
			return false;
		});
		$container.bind('click.cardread',function(){
			return false;
		});	
		$input.bind('keydown.cardread', function(e) {
			if( e.keyCode == 13 ) {
				var ID = $input.val();
				getUser(ID);
				return false;
			}
		});
		$(document)
			.bind('click.cardread',function() {
				$container.close();
			})
			.bind('keydown.cardread',function(e) {
				if( e.keyCode == 27 ) {
					$container.close();
				}
			});

	}

	$(':visible > input[q-cardread]').livequery(
		function() {

			$input = $(this);
			$input.cardread({
				'ajax'      : $input.classAttr('cardread'),
				'normalMsg' : $input.classAttr('cardreadNormalMsg'),
				'errorMsg'  : $input.classAttr('cardreadErrorMsg')
			});
		}
	);

})(jQuery);
