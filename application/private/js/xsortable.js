(function($){

	$.fn.xsortable = function(o){
		
		var opt={
			itemName: 'item', 
			parentName: 'parent', 
			cursor: 'move', 
			indentPlaceHolder:'.indent_place_holder', 
			alterTree: true,
			sectionEmptyText: '(none)'
			};

		$.extend(opt, o);
		
		var $container=$(this);
		var selItemID=['[name=',opt.itemName,']'].join('');
		
		$(this).each(function() {
			var $el = $(this);
			var cls = $el.attr('class') || "";
			var parts = cls.match(/(?:^|\s)section:([a-z0-9-_]+)(?:$|\s)/i);
			var sectionID = parts ? parts[1] : null;

			var $children=$el.children().addClass('xsortable_item');
			
			//添加空文本
			var $empty=$('<div class="xsortable_section_placeholder hidden">' + opt.sectionEmptyText + '</div>').prependTo(this);
			$empty.data('xsortable_section', sectionID);
			
			if($children.length==0){
				$empty.show();
			} else {
				$children.each(function(){
					
					var $item = $(this);
					var $handle = opt.handle ? $item.find(opt.handle) : $item;
					var dragging=null;
					
					var parts, itemID=0, parentID=0;
				
					var cls = $item.attr('class') || '';
					parts = cls.match(/(?:^|\s)item:([0-9-]+)(?:$|\s)/);
					if(parts)itemID=parts[1];
					
					parts = cls.match(/(?:^|\s)parent:([0-9-]+)(?:$|\s)/);
					if(parts)parentID=parts[1];
					
					$item
					.append(['<input type="hidden" name="parent[',itemID,']" value="', parentID,'" />'].join(''));
					
					$item.data('xsortable_item', itemID);
					$item.data('xsortable_parent', parentID);
					if(sectionID){
						$item.append(['<input type="hidden" name="section[',itemID,']" value="', sectionID,'" />'].join(''));
						$item.data('xsortable_section', sectionID);
					}
										
					if(opt.alterTree){
						$item.find(opt.indentPlaceHolder).eq(0).css('white-space', 'nowrap');
						if(parentID>0){
							setParent($item, parentID, sectionID, true);
						}
					}
					
					function setParent($collection, parentID, sectionID, move){
						$collection.each(function(){
							var $el=$(this);
							var itemID=$el.data('xsortable_item');
							var oldParentID=$el.data('xsortable_parent')||0;
							
							parentID=parentID||0;

							var $np=$container.children(['.xsortable_item.item\\:',parentID].join(''));
							
							if (oldParentID != parentID) {
								//parent更换
								$el
								.removeClass('parent\:' + oldParentID)
								.addClass('parent\:' + parentID)
								.data('xsortable_parent', parentID);
								
								if(dragging){
									$el.addClass('xsortable_modified');
								}
								
								$el.find(['[name=parent\[',itemID,'\]]'] .join('')).val(parentID);
							}

							if(sectionID) {
								//section更换
								$el.data('xsortable_section', sectionID);
								$el.find(['[name=section\[',itemID,'\]]'].join('')) .val(sectionID);
							}
							
							if(parentID>0 && move == true){
								$el.insertAfter($np);
							}
							
							var dx = (parentID > 0) ?
										$np.find(opt.indentPlaceHolder).children('div.indent_unit').length + 1
										: 0;
							
							$el.find(opt.indentPlaceHolder).html(dx>0? Array(dx+1).join('<div class="indent_unit"/>'):'');
							setParent($($container.children(['.xsortable_item.parent\\:', itemID].join('')).get().reverse()), itemID, sectionID, true);
						});
					}
		
					$handle
					.bind('mousedown.xsortable', function(e){
						dragging={x:e.pageX, y:e.pageY};
						e.preventDefault();
						
						$handle[0]._storedCursor = $('body').css("cursor"); //Reset cursor
						$('body').css("cursor", opt.cursor);
					
						$item.addClass('xsortable_moving');
					
						function associate(){
							if($item.data('xsortable_section')!=$(this).data('xsortable_section')){
								var os=$(['.section\\:',$item.data('xsortable_section')].join(''));
								var ns=$(['.section\\:',$(this).data('xsortable_section')].join(''));
								setParent($item, $(this).data('xsortable_parent'), $(this).data('xsortable_section'));
								if(os.children('.xsortable_item').length==0){
									os.children('.xsortable_section_placeholder').show();
								}
								ns.children('.xsortable_section_placeholder').hide();
							} else {
								setParent($item, $(this).data('xsortable_parent'));
							}
						}
						
						function getPrev($i){
							//filter with section placeholder
							var $items=$container.children('.xsortable_item, .xsortable_section_placeholder:visible');
							return $items.eq($items.index($i) - 1);
						}
						
						function getNext($i){
							
							var $items=$container.children('.xsortable_item, .xsortable_section_placeholder:visible');

							var si=$items.index($i);
							
							function count_children($i){
								var $is=$items.filter(['.parent\\:',$i.data('xsortable_item')].join(''));
								var count=$is.length;
								$is.each(function(){
									count+=count_children($(this));
								});
								return count;
							}
							
							return $items.eq(si + count_children($i)+1);
						}
						
						$('body')
						.one('mouseup.xsortable', function(){
							dragging=null;
							if($handle[0]._storedCursor)$('body').css('cursor', $handle[0]._storedCursor);
							$handle.unbind('mousemove.xsortable');
							$('body').unbind('mousemove.xsortable');
							$item.removeClass('xsortable_moving');
						})
						.bind('mousemove.xsortable', function(e){
							if(dragging){
								e.preventDefault();
								var dy = (e.pageY - dragging.y)/20;
								if(dy > 0) dy = Math.floor(dy);
								else dy = Math.ceil(dy);
								
								if (dy != 0) {
									$item.addClass('xsortable_modified');
									if (dy>0) {
										var $next=getNext($item);
										if($item.data('xsortable_section') != $next.data('xsortable_section')){
											$item.insertBefore($next);
										}else{
											$item.insertAfter($next);
										}
										associate.apply($next);
									} else {
										var $prev=getPrev($item);
										if($item.data('xsortable_section') != $prev.data('xsortable_section')){
											$item.insertAfter($prev);
									 	}else{
											$item.insertBefore($prev);
										}
										associate.apply($prev);
									}
									dragging.y=e.pageY;
								}

								if(opt.alterTree){
									var dx = (e.pageX - dragging.x)/40;
									if(dx > 0) dx = Math.floor(dx);
									else dx = Math.ceil(dx);
									if(dx > 0) {
										var $prev = $item.prevAll(['.xsortable_item.parent\\:',$item.data('xsortable_parent'),':first'].join(''));
										if($prev.length){
											setParent($item, $prev.data('xsortable_item'));
										}
										dragging.x=e.pageX;
									} else if (dx < 0) {
										var parentID=$item.data('xsortable_parent');
										if(parentID>0){
											setParent($item, $item.prevAll(['.xsortable_item.item\\:',parentID].join(''),':last').data('xsortable_parent'));
											setParent($item.nextAll(['.xsortable_item.parent\\:', parentID].join('')), $item.data('xsortable_item'));
										}						
										dragging.x=e.pageX;
									}
								}
							}
						});				

						return false;
		
					});
					
				
				});
			}
		});
	
	}

})(jQuery);
