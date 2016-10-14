// menu_id:  menu的id
// ajax_url: AJAX路径
jQuery(function($){

	var $menu = $('#' + menu_id);
	
	/*
	$menu.find('.category').bind('click', function(){
		var $category = $(this);
		var $button = $category.find('.toggle_button');
		$button.toggleClass("toggle_button_collapsed");
		var hidden = $button.hasClass("toggle_button_collapsed") ? 1 : 0;
		
		Q.trigger({
			object:'set_session',
			event:'click',
			data:{c_name:$button.next().text(), hidden: hidden},
			url: ajax_url,
			success: function() {
				if (hidden) {
					$category.next('.items').slideUp(200);
				}
				else {
					$category.next('.items').slideDown(200);
				}
			}
		});		

		return false;
	});
	*/

	$menu.find('a.toggle_button_hidden').click(function(){
		var $button = $(this);
		$button.toggleClass("toggle_button_show_hidden");
		var show_hidden = $button.hasClass("toggle_button_show_hidden") ? 0 : 1;

		Q.trigger({
			object:'sbmenu_show_hidden',
			event:'click',
			data: {show_hidden: show_hidden},
			url: ajax_url,
			success: function() {
				if (show_hidden) {
					$menu.find('span.toggle_button').removeClass("toggle_button_collapsed");
					$menu.find('.item_hidden')
					.filter('.icon_item')
						.each(function(){
							if (Q.browser.msie && Q.browser.version < 8) {
								$(this).css('display', 'inline');
							}
							else {
								$(this).css('display', 'inline-block');
							}
						})
					.end()
					.filter('.list_item')
						.show()
					.end()
					.animate({opacity:1}, 100);

					// $menu.find('.items').slideDown(200);
				}
				else {
					$menu.find('.item_hidden').animate({opacity:0}, 100, function(){
						$(this).css('display', 'none');
					});
				}		
			}
		});	
		
		return false;	
	});	
	
	$menu.find('.items  div.item').bind('click', function(e) {
		var href = $(this).find('a:eq(0)').attr('href');
		var type = '_self';
		if (e.ctrlKey) {
			type = '_blank';
		}
		window.open(href, type);
		e.preventDefault();
		return false;
	});
	
});
