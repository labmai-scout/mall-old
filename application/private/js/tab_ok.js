(function($){

	//使textarea能够使用tab键
	$('textarea.tab_ok').live('keydown', function(e){
		var key = e.keyCode || e.which;
		if(key == 9) {	//tab key
			e.preventDefault();
			if(e.shiftKey) {
				//shift + tab 反缩进
				if(document.selection){
					document.selection.createRange().text= document.selection.createRange().text.replace(/^\t|^ {1,4}/gm, '');
				}
				else {
					(function(){
						var start = this.selectionStart;
						var end = this.selectionEnd;
						var sel = start<end ? this.value.substr(start, end-start):'';
						
						var parts = sel.match(/^\t/gm);
						var iclen = parts? parts.length : 0;
						
						parts = sel.match(/^ {1,4}/gm);
						if (parts) {
							for(var i in parts) {
								if (parts.hasOwnProperty(i)) {
									iclen += parts[i].length;
								}
							}
						}
						
						if(iclen > 0) {
							this.value=[this.value.substr(0, start), sel.replace(/^\t|^ {1,4}/gm, ''), this.value.substr(end)].join('');
							if(start != end) {
								this.selectionStart = start;
								this.selectionEnd = end - iclen;
							}
						}
					})();
				}
			} else {
				//插入tab
				if(document.selection){
					document.selection.createRange().text= '    ' + document.selection.createRange().text.replace(/(\r\n|\n\r|\n)(.+)/gm, '$1    $2');
				}
				else {
					(function(){
						var start = this.selectionStart;
						var end = this.selectionEnd;
						var text = start<end ? this.value.substr(start, end-start):'';
						var parts = text.match(/(\r\n|\n\r|\n)(.+)/gm);
						var tab_num = 1 + ( parts? parts.length : 0 );
						this.value=[this.value.substr(0, start), '    ', text.replace(/(\r\n|\n\r|\n)(.+)/gm, "$1    $2"), this.value.substr(end)].join('');
						if(start == end) {
							this.selectionStart = this.selectionEnd = start + 4;
						} else {
							this.selectionStart = start;
							this.selectionEnd = end + tab_num*4;
						}
					})();
				}
			}
		}
	});
	
})(jQuery);