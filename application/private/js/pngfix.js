jQuery(function($) {
	
		var setting = {blank: 'user/images/blank.gif'};
		
		var DXFilter='progid:DXImageTransform.Microsoft.AlphaImageLoader';
		
		var base = $('base:first').attr('href');
		
		$("img[src*=.png], img[src*=\/text\/]").each(function() {
			
			var cs=this.style;
			cs.width=this.width;
			cs.height=this.height;
			var uri = this.src;
			if(uri.match(/^http:\/\//)){
				var src=encodeURI(uri);
			}else{
				var src=encodeURI([base,uri].join(''));
			}
			cs.filter=[DXFilter,"(src='", src ,"', sizingMethod='scale')"].join('');
			this.src = setting.blank;
		});				
	
});
