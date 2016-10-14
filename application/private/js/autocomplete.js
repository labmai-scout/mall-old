(function($) {

	$.ui = $.ui || {};
	$.ui.autocomplete = $.ui.autocomplete || {};
	$.ui.autocomplete.ext = $.ui.autocomplete.ext || {};

	$.ui.autocomplete.ext.ajax = function(opt) {
		var ajax = opt.ajax;
		var ajaxObject;
		return {
			getList: function(input) {
				input.addClass('autocompleting');
				var $base = opt.base ? $(opt.base) : input;

				var _data = Q.toQueryParams($base.classAttr('static'))||{};
				var selectors=Q.toQueryParams($base.classAttr('dynamic'))||{};
				for(k in selectors){
					_data[k]=$(selectors[k]).val();
				}

				_data.s = input.filter(':not(.hint)').val();
				if (ajaxObject) ajaxObject.abort();
				ajaxObject = $.ajax({
					url: ajax, 
					data: _data, 
					complete: function() {
						input.removeClass('autocompleting');
						ajaxObject = null;
					},
					success: function(json) {
						input.trigger("updateList", [json]);
					},
					dataType: 'json',
					global: false
				});
			}
		};
	};

	$.ui = $.ui || {}; $.ui.autocomplete = $.ui.autocomplete || {}; var active;

	$.fn.autocompleteMode = function(container, input, size, opt) {
		var original = input.val(); var selected = -1; var self = this;
		$.data(document.body, "autocompleteMode", true);

		$(document).one("cancel.autocomplete", function() {
			input.trigger("cancel.autocomplete"); $(document).trigger("off.autocomplete"); input.val(original);
		});

		$(document).one("autoactivate.autocomplete", function() {
			if(!active || active.length<1)return false;
			input.trigger("autoactivate.autocomplete", [$.data(active[0], "originalObject")]);
			$(document).trigger("off.autocomplete");
		});

		$(document).one("off.autocomplete", function(e, reset) {
			container.remove();
			$.data(document.body, "autocompleteMode", false);
			input.unbind("keydown.autocomplete");
			$(document).add(window).unbind("click.autocomplete").unbind("cancel.autocomplete").unbind("autoactivate.autocomplete");
		});

		// If a click bubbles all the way up to the window, close the autocomplete
		$('body').bind("click.autocomplete", function() { $(document).trigger("cancel.autocomplete"); });

		select = function() {
			//active = $("> *", container).removeClass("active").slice(selected, selected + 1).addClass("active");
			active = $("li:not(.special)", container).removeClass("active").slice(selected, selected + 1).addClass("active");

			if(active[0]){
				input.trigger("itemSelected.autocomplete", [$.data(active[0], "originalObject")]);
				//input.val(opt.insertText($.data(active[0], "originalObject")));
				input.data('autocomplete.selected', opt.insertText($.data(active[0], "originalObject")));
			}
		};

		var li = $("li:not(.special)", container);
		li.mouseover(function(e) {
			if(e.target == container[0]) return;
			selected = li.index($(e.target).is('li') ? $(e.target)[0] : $(e.target).parents('li')[0]); select();
		}).bind("click.autocomplete", function(e) {
			$(document).trigger("autoactivate.autocomplete"); $.data(document.body, "suppressKey", false);
		});

		input
		.bind("keydown.autocomplete", function(e) {
			if(e.which == 27) {
				$(document).trigger("cancel.autocomplete");
			}
			else if(e.which == 13 && active && active.length > 0) {
				$(document).trigger("autoactivate.autocomplete");
			}
			else {
				switch(e.which) {
				case 40:
				case 9:
				case 39:
					selected = selected >= size - 1 ? 0 : selected + 1; break;
				case 38:
				case 37:
					selected = selected <= 0 ? size - 1 : selected - 1; break;
				default:
					return true;
				}
				select();
			}
			$.data(document.body, "suppressKey", true);
			return false;
		});

		select();
	};

	$.fn.autocomplete = function(opt) {
		opt = $.extend({}, {
			timeout: 200,
			getList: function(input) { input.trigger("updateList", [opt.list]); },
			template: function(item){ return "<li><div class=\"autocomplete_item\">" + (item.html || item) + "</div></li>"; },
			wrapper: "<ul class='autocomplete'/>",
			insertText: function(item) { return item.text; }
			}, opt);

		if($.ui.autocomplete.ext) {
			for(var ext in $.ui.autocomplete.ext) {
				if(opt[ext]) {
					opt = $.extend(opt, $.ui.autocomplete.ext[ext](opt));
					delete opt[ext];
				}
			}
		}

		var $input = $(this);

		/*
		 * commented by Jia Huang
		 * 不启用输入法是会造成重复两次keypress事件, 暂时comment掉
		var ime_fix;
		var ime_fix_timeout = 500;

		$input
		.bind('focus.autocomplete', function(){
			var old_val = $input.val();
			var just_focus = true;
			ime_fix = window.setInterval(function(){
				var new_val = $input.val();
				if (just_focus || old_val != new_val) {
					just_focus = false;
					old_val = new_val;
					var e = $.Event("keypress.autocomplete");
					e.charCode = 64; // 模拟一个允许的键值输入
					$input.trigger(e);
					return false;
				}
			}, ime_fix_timeout);
		})
		.bind('blur.autocomplete', function(){
			clearInterval(ime_fix);
		});
		*/

		var $alt;
		if (opt.alt) $alt = $(opt.alt);

		$input
		.bind('change.autocomplete', function(e) {
			if ($alt && $alt.data('autocomplete.text') != $input.val()) {
				$alt.val($input.val());
			}
		})
		.bind('autoactivate.autocomplete', function(e, item) {
			if(typeof item != 'undefined'){
				var text = item.text || item;
				$input.val(text);
				if ($alt) {
					$alt.val(item.alt || item).change(); // hidden改变时，触发change事件 (xiaopei.li@2011.05.31)
					$alt.data('autocomplete.text', text);
				}
			}
		})
		/*TODO keypress 暂时用 keyup来取代, 但是目前IE和chrome中仍然在中文输入下触发事件太过于频繁，望有好的解决方案来进行处理，比如说chrome和firefox下用oninput事件，IE下使用onpropertychange事件来进行处理的方式进行解决, 或者查询jquery中是否有相应的事件处理机制，已经融合其中了。列入2.2.1中进行解决。*/
		.bind('keyup.autocomplete', function(e) {
			var eTarget = $ (e.target || this);
			var typingTimeout = $.data(this, "typingTimeout");
			var current_val = eTarget.val();
			if (current_val == eTarget.data('current_val')) {
				e.preventDefault();
				return	false;
			}
			eTarget.data('current_val', current_val);
			
			if(typingTimeout) window.clearInterval(typingTimeout);

			if($.data(document.body, "suppressKey"))
				return $.data(document.body, "suppressKey", false);
			/*
			else if($.data(document.body, "autocompleteMode") && e.charCode < 32 && e.keyCode != 8 && e.keyCode != 46)
				return false;
			*/
			else {
				$.data(this, "typingTimeout", window.setTimeout(function() {
					eTarget.trigger("autocomplete");
				}, opt.timeout));
			}
		})
		.bind("autocomplete.autocomplete", function() {
			var self = $(this);

			self.one("updateList", function(e, list) {
				
				$(document).trigger("off.autocomplete");

				if (!list.length){
					return false;
				}

				var special = $(list).last().attr('special');

				list = $(list)
				.map(function() {
					var node = $(opt.template(this))[0];
					$.data(node, "originalObject", this);
					return node;
				});

				if (special) {
					list.last().attr('class', 'special');
					size = list.length - 1;
				}
				else {
					size = list.length;
				}
				var container = list.wrapAll(opt.wrapper).parent();

				if(opt.base){
					obj=$(opt.base);
				}else{
					obj=self;
				}
				var width=obj.outerWidth();
				var height=obj.outerHeight();
				opt.container = container;
				container.insertAfter(obj);
				/*
					NO. BUG#137 (Cheng.Liu@2010.11.10)
					没有指定top， IE6中，添加到obj层之后，不指定top，
					会定位到父级浮动框的top处
				*/
				var top = obj.position().top + height;
				var p = obj.parent(), ppos;
				while(p.length && !p.is('body')) {
					top += p.scrollTop();
					ppos = p.css('position');
					if (ppos=='relative' || ppos=='absolute' || ppos=='fixed') break;
					p = p.parent();
				}
				container.css({left:obj.position().left, top:top , width: width});

                var max_z_index = 0;
                //获取container的parents中z_index最大值
                container.parents().each(function() {
                    max_z_index = Math.max($(this).css('z-index'), max_z_index);
                });

                //设置container、p的z_index
                container.css('z-index', max_z_index);

				/*
					TODO cheng.liu@geneegroup.com
					2011/10/25 container和obj所属的定位父元素如果是body的情况下，在定位container的left时， 实际值总是会比指定值相差8个px，
					推测是某个input的双倍边距所产生的，但是在所有位置都没有发现该类边距。
					故换位操作，将父级元素强制换成table，可以产生该类双倍边距的问题。待之后找出原因mark
				*/
				if (Q.browser.msie && Q.browser.version < 9) {
					var $tmp_table = obj.parents('table,form').eq(0);
					$tmp_table.css('position', 'relative');
					top = top - ($tmp_table.offset().top - p.offset().top);
					container.css({left: obj.position().left, top:top});
                    //IE6中，由于z-index的bug问题，需要同时设定p的z-index
                    p.css('z-index', max_z_index);
                }
				
				$(document).autocompleteMode(container, self, size, opt);
			});

			opt.getList(self);
		});

	};


	$(':visible > input:text[class*="autocomplete\\:"], :visible > input:text[q-autocomplete]').livequery(function(){
		var $input = $(this);
		$input
		.autocomplete({
			ajax: $input.classAttr("autocomplete"),
			alt: $input.classAttr("autocomplete_alt")
		});

		$input.bind('focus.autocomplete', function() {
			setTimeout(function(){
				$input.trigger('autocomplete.autocomplete');
			}, 50);
		});

	}, function(){
		var $input = $(this);
		$input.trigger('blur.autocomplete');
		$input.unbind('focus.autocomplete autocomplete.autocomplete change.autocomplete autoactivate.autocomplete focus.autocomplete blur.autocomplete keypress.autocomplete');
	});

})(jQuery);
