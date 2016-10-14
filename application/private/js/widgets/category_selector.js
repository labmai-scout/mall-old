(function($) {

	var $category_menus = {};
	var $current_root;
	var removeTimeout = null;
	var ajaxTimeout = null;


	$.fn.categorySelector = function(opt) {
		var $root = $(this);
	
		opt = opt || {};
		opt.menu = opt.menu || '<div class="category_selector_menu"><table class="content" width="1"></table></div>';
		opt.item = opt.item || '<tr class="category_item"><td width="100%"><div class="text"/></td><td><div class="number"/></td><td ><div class="flag"/></td></tr>';

		var _zIndex = function($el) {
			var z = $el.css('z-index');
			if (isNaN(z)) z = 0;
			$el.parents().each(function(){
				var myZ = parseInt($(this).css('z-index'));
				if (isNaN(myZ)) myZ = 0;
				z = Math.max(z, myZ);
			});
			return z;
		};

		var _remove_all_menus = function(){
			for (var i in $category_menus) {
				$category_menus[i].remove();
			}
		};

		var _render = function() {
			var $form = $root.parents('form');
			var max_width = Math.min(280, $form.innerWidth());
			if ($root.outerWidth() <= max_width) return;
			
			var $links = $root.find('.category_selector_link:not(.category_selector_first, .category_selector_last, .category_selector_more)');
			var $placeholder = $('<div class="category_selector_link category_selector_placeholder"><a href="#">...</a></div>');
			var w = $root.outerWidth() + $placeholder.outerWidth();
			var $ghost;

			$links.each(function(i) {
				var $l = $(this);
				if (w - $l.outerWidth() <= max_width) {
					$placeholder.find('a').attr('title', $l.find('a').text()).attr('q-category-id', $l.find('a').attr('q-category-id'));
					$l.after($placeholder);
					$l.remove();
					return false;
				}
				w -= $l.outerWidth();
				$l.remove();
			});

		};

		var _select_category = function(category_id) {
			if (opt.ajax) {
				Q.trigger({
					widget: 'category_selector',
					object: 'category',
					event: 'click',
					global: false,
					data: {
						uniqid: opt.uniqid,
						root_id: opt.root_id,
						category_id: category_id,
						name: opt.name
					},
					url: opt.url,
					complete: function() {
						setTimeout(function(){
							$root.find('[name='+name+']').change();
							_render();
						}, 20);
					}
				});

			}
			else {
				// 设置隐藏提交元素 并自动提交root所在表单
				var $hidden = $('<input type="hidden" />');
				$hidden.attr('name', opt.name);
				$hidden.val(category_id);
				$root.parents('form').append($hidden).submit();
			}
		};

		$(document).bind('click', _remove_all_menus);

		var _menu_offset = -8;
		if (Q.browser.msie && Q.browser.version < 8) {
			_menu_offset = -16;
		}

		var _show_next_for_category = function(category_id, parent_category_id) {

			var $parent_item;
			var $parent_menu;

			if (parent_category_id != undefined) {
				$parent_menu = $category_menus[parent_category_id];
				if ($parent_menu) {
					var _remove = function($m){
						$m.find('.category_item').each(function(){
							var $item = $(this);
							var id = $item.data('category_id');
							if (id) {
								var $el = $category_menus[id];
								if ($el) {
									_remove($el);
									$el.remove();
									delete $category_menus[id];
								}
							}
						})
					};
					_remove($parent_menu);
					$parent_menu.find('.category_item').each(function(){
						var $item = $(this);
						var id = $item.data('category_id');
						if (id == category_id) {
							$parent_item = $item;
							return false;
						}
					});
				}
			}
			else {
				var $el = $category_menus[category_id];
				if ($el) {
					$el.remove();
					delete $category_menus[category_id];
				}
			}

			var $menu = $(opt.menu);
			var $menu_content = $menu.find('.content');
			$menu.append('<div class="loading">&#160;</div>');
			$menu.appendTo('body');

			if ($parent_menu) {
				var ioffset = $parent_item.offset();
				$menu.css({zIndex: $parent_menu.css('z-index') + 1, left: ioffset.left + $parent_item.width() + _menu_offset, top: ioffset.top });
			}
			else {
				$parent_item = $root.find('.category_selector_more');
				var ioffset = $parent_item.offset();
				$menu.css({zIndex: _zIndex($root) + 1, left: ioffset.left + $parent_item.width(), top: ioffset.top});
			}

			Q.trigger({
				widget:'category_selector',
				object:'category',
				event:'mouseover',
				global: false,
				data: {
					category_id: category_id,
					uniqid: opt.uniqid,
					root_id: opt.root_id
				},
				url: opt.url,
				complete: function () {
					if ($category_menus[category_id]!=$menu) { $menu.remove(); }
				},
				success: function (data, status) {
					if (data.hasOwnProperty('items')) {
						var items = data.items || {};
						var count = 0;
						$menu.find('.loading').remove();
						for (var id in items) {
							count ++;
							var t = items[id];
							var $t = $(opt.item);
							$t.find('.text').html(t.html);
							$t.data('category_id', id);
							if (t.ccount > 0) {
								$t.find('.number').html(t.ccount);
								$t.find('.flag').addClass('flag_more');
							}
							$menu_content.append($t);
							$t.data('children_count', t.ccount);
							$t.mouseenter(function(){
								var $t = $(this);
								$t.addClass('category_item_active');
								if (ajaxTimeout) {
									clearTimeout(ajaxTimeout);
									ajaxTimeout = null;
								}
								if ($t.data('children_count') > 0) {
									ajaxTimeout = setTimeout(function(){
										_show_next_for_category($t.data('category_id'), category_id);
									}, 50);
								}
							})
							.mouseleave(function(){
								$(this).removeClass('category_item_active');
							})
							.click(function(){
								var $t = $(this);
								//把当前$menu加入队列 才能选择
								$category_menus[category_id] = $menu;
								_select_category($t.data('category_id'));
							});
						}

						if (count > 0) {
							$category_menus[category_id] = $menu;
							
							$menu
							.mouseenter(function(){
								if (removeTimeout) {
									clearTimeout(removeTimeout);
									removeTimeout = null;
								}
								$root.data('removeTimeout', null);
							})
							.mouseleave(function(){
								if (removeTimeout) {
									clearTimeout(removeTimeout);
								}
								removeTimeout = setTimeout(_remove_all_menus, 1000);
							});
						}

						delete data.items;
					}
				}
			});
			
		};

		$('.category_selector_more', $root)
		.live('click', function(){
			if (removeTimeout) {
				clearTimeout(removeTimeout);
				removeTimeout = null;
			}

			if ($current_root != $root) {
				_remove_all_menus();
				$current_root = $root;
			}

			var category_id = $root.find('[name=' + opt.name + ']').val();
			_show_next_for_category(category_id);
		})
		.live('mouseleave', function(){
			if (removeTimeout) {
				clearTimeout(removeTimeout);
			}
			removeTimeout = setTimeout(_remove_all_menus, 1000);
		});

		$('.category_selector_link:not(.category_selector_more) a', $root)
		.live('click', function(e){
			var category_id = $(this).attr('q-category-id') || 0;
			_select_category(category_id);
			e.preventDefault();
			return false;
		});

		_render();

	};

})(jQuery);
