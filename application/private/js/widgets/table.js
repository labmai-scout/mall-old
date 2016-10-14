(function($){

	Q.tableWidget = {};

	Q.tableWidget.showSearch = function(button, e){
		var $button = $(button);
		var $filter = $button.parents('.filter').eq(0);
		var $panel = $filter.find('.filter_panel');
		
		var $document = $(document);
		
		if ($filter.hasClass('active')) {
			return false;
		}
			
		$filter.addClass('active');
		$panel.css('visibility', 'visible');
		
		$filter
		.unbind('click.tableWidget.hidePanel')
		.bind('click.tableWidget.hidePanel', function(){
			$filter.data('tableWidget.hidePanel.clicked', true);
		});
		
		$document
		.unbind('click.tableWidget.hidePanel')
		.bind('click.tableWidget.hidePanel', function(){
			if ($filter.data('tableWidget.hidePanel.clicked')) {
				$filter.data('tableWidget.hidePanel.clicked', false);
				return;
			}
			
			$filter.removeClass('active');

			$panel.css('visibility', 'hidden');
			
			$filter.unbind('click.tableWidget.hidePanel');
			
			$document.unbind('click.tableWidget.hidePanel');
		});
		
		$button.one('click', function(){
			$filter.data('tableWidget.hidePanel.clicked', false);
			$filter.unbind('click.tableWidget.hidePanel');
			$panel.css('visibility', 'hidden');
			$filter.removeClass('active');
			$document.unbind('click.tableWidget.hidePanel');
 
		});
		
		e = $.Event(e);
		e.stopPropagation();
		
		return false;
	};

})(jQuery);
