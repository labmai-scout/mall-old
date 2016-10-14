(function($) {

	Q.role_sortable = function (role_container_id, role_url) {
		
		$document = $(document);
		$body = $('body');

		var $root_container = $('#' + role_container_id);
		var $hider = $('<div class="role_hide_item hidden">&#160;</div>');
		var delta = 6;

		$root_container
		.find('.role_drag_handle')
		.live('mousedown touchstart', function(e) {
			e = Q.event(e);
			var isTouch = e.isTouch;
			
			var $handle = $(this);
			var $helper = $handle.parents('.role_item:first');
			
			$hider
			.insertAfter($helper)
			.addClass('role_item')
			.css({
				height: $helper.height()
			})
			.show();
			
			var left = $helper.offset().left;
			
			$helper
			.addClass('role_drag_helper')
			.appendTo($body)
			.css({
				position: 'absolute',
				left: left + 10,
				top: e.pageY - 15
			});
			
			
			var oldCursor = $body.css('cursor');
			$body.css({cursor:'move'});

			var hasTarget;
			var sortable = false;
			var $items = $root_container.find('.role_item');
			var items_count = $items.length;
			
			var index; 
			var prev_index;
			var hider_index;
			var old_hider_index = $items.index($hider);
			var need_move = false;
			
			var _dragmove = function(e) {
				e = Q.event(e);
				
				$items = $root_container.find('.role_item');
				
				$helper.css({
					top: e.pageY - 15
				});
				
				
				index = get_hover_index(0, items_count-1, e.pageY, $items);
				hider_index = $items.index($hider);
				if (index != hider_index) _drag_over(index);
				
				function _drag_over(index) {
					//根据获取到的item索引定义各个变量的值
					var $item = $items.eq(index);
					var offset = $item.offset();
					var top = offset.top;
					var bottom = offset.top + $item.height();
					var dvalue = e.pageY - top;
					if (dvalue < (bottom-top)/2) {
						if ((index - hider_index) != 1 || index == 0) {
							$hider.insertBefore($item);
							var $prev = $hider.prev('.role_item');
							prev_index = $prev.length ? $prev.classAttr('item_weight') : -1 ;
							need_move = true;
						}
						
					}
					else {
						if ((hider_index - index) != 1 || index == items_count - 1) {
							$hider.insertAfter($item);
							prev_index = $item.classAttr('item_weight');
							need_move = true;
						}
							
					}
				}
				e.preventDefault();
				return false;
			};
			
			var _dragend = function(e) {
				if (isTouch) {
					$handle
					.unbind('touchmove', _dragmove);
				}
				else {
					$document
					.unbind('mousemove', _dragmove);
				}
	
				e = Q.event(e);
				$body.css({cursor:oldCursor});
				$helper
				.removeClass('role_drag_helper')
				.css({
					position: 'relative',
					left: 0,
					top: 0
				})
				.insertAfter($hider);
				
				$hider.remove();
				
				if (need_move && old_hider_index != hider_index) {
					Q.trigger({
						object: 'role',
						event: 'change_weight',
						url: role_url,
						data: {
							role_id: $helper.find('.role_title:eq(0)').classAttr('item'),
							prev_index: prev_index,
							uniqid: role_container_id
						},
						success: function(data) {
							
						}
					});
				}
				
				e.preventDefault();
				return false;
			};
			
			//二分函数，获取到鼠标所停留的item索引
			var get_hover_index = function(start, end, y, $items){
				
				if(y < $items.eq(0).offset().top) { return 0; }
				if(y > ($items.eq(items_count-1).offset().top + $items.eq(items_count-1).height())) { return items_count-1; }
				
				var middle = parseInt((end-start)/2, 10) + start;
				
				if(middle == start) { return start; }
				if(middle == end) { return end; }
				
				var start_y = $items.eq(start).offset().top;
				var end_y = $items.eq(end).offset().top;
				var middle_y = $items.eq(middle).offset().top;
				
				if(y >= start_y && y <= middle_y) {
					return get_hover_index(start,middle,y,$items);
				}
				if(y >= middle_y && y <= end_y) {
					return get_hover_index(middle,end,y,$items);
				}
				if(y > end_y && y <= (end_y+$items.eq(end).height())){
					return end;
				}
			};
	
			if (isTouch) {
				$handle
				.bind('touchmove', _dragmove)
				.one('touchend', _dragend);
			}
			else {
				$document
				.bind('mousemove', _dragmove)
				.one('mouseup', _dragend);
			}
						
			e.preventDefault();
			return false;
		});
		
		
	};
		
})(jQuery);
