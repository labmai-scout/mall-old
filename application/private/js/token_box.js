(function($) {

	var tokenParsers = {
		email: {
			parse: function(str) {
				var pattern=/^\s*([^<>\s]+)\s*<(\S+)>\s*$/;
				var email_pattern = /[a-z0-9!#$%&'*+\/=?\^_`{|}~\-]+(?:\.[a-z0-9!#$%&'*+\/=?\^_`{|}~\-]+)*@(?:[a-z0-9](?:[a-z0-9\-]*[a-z0-9])?\.)+(?:[A-Z]{2}|com|org|net|gov|mil|biz|info|mobi|name|aero|jobs|museum)\b\s*$/i;
				var parts;

				parts = str.match(pattern);
				if(!parts) {
					//检查是不是只是一个email地址
					parts = str.match(email_pattern);
					if (!parts) {
						return null;
					}
					
					return {key: parts[0], value: parts[0]};
				}
				
				var key = parts[2];
				var value = parts[1];
				
				//检查email是否合法
				parts = key.match(email_pattern);
				if(!parts) {
					return null;
				}
				
				return {key: key, value: value};
			}
		}
	};

	$(':visible > input.token').livequery(function(){
		
		// <input class="token token_autocomplete:http://asdas token_max:10 token_verify token_parser:email" />
							
		var $input=$(this);					
		
		var autocomplete = $input.classAttr('token_autocomplete') || '';
		var max = $input.classAttr('token_max') || 0;
		var parser = tokenParsers[$input.classAttr('token_parser')] || null;
		var verify = $input.hasClass('token_verify');
		var readonly = $input.classAttr('token_readonly') ? true : false;
		var box = $input.hasClass('autocomplete_box');
	
		var tokens={}, tokensCount=0;

		var cls = $input.attr('class') || '';		
		var new_cls = cls.replace(/\btoken\S*|\btext\b/g, '');
		var token_tip = $input.classAttr('token_tip');
		
		var $tokenBox = $('<div class="clearfix token_box ' + new_cls + '" />');
		var $tokenInput = $('<input />');

		//hongjie.zhu >>>> select_token
		//jia.huang select_token => readonly		
		if(readonly){
			$tokenInput.css({
				border: 'none',
				visibility: 'hidden'
			}).attr('readonly', 'true');
		}

		function tokenElement(k, v){
			var em = false;
			
			if (arguments.length < 2) {
				if (parser) {
					var pair = parser.parse(k);
					if (!pair) {
						return null;
					}
					k = pair.key;
					v = pair.value;
				} else {
					v = k;
				}
			}

			if (tokens[k]) {
				return null;
			}
			
			tokens[k] = v;

			if (typeof(v) == 'object') {
				em = v.em;
				v = v.text;
			}

			var $token=$('<div class="token'+(em?' token_em':'')+'"><strong/><span class="remove_button">&#160;</span></div>');

			$token.find('strong').text(v);
		
			$input.val($.toJSON(tokens));

			$token
			.bind('click', function(){
				$tokenInput.insertAfter($token).focus().trigger('_blur.token');
				$token.addClass('token_selected').siblings('.token').removeClass('token_selected');
				return false;
			})
			.bind('unload.token', function(){
				delete tokens[k]; tokensCount --;
				$token.remove();
				$input.val($.toJSON(tokens));
				if (tokensCount === 0) { $tokenInput.addClass('visible'); }
			})
			.find('span.remove_button').bind('click', function(){
				$token.trigger('unload.token');
				return false;
			});

			return $token;
		}
		
		try {
		
			var i, t, tmp = $.secureEvalJSON(this.value);
			
			if (tmp == null) throw(0);
		
			if (tmp.length) { // 数组
				for (i in tmp) {
					if (tmp.hasOwnProperty(i)) {
						t = tokenElement(tmp[i]);
						if (t) {
							$tokenBox.append(t);
							tokensCount++;
						}
					}
				}
			}
			else {	// 键值对
				for (i in tmp) {
					if (tmp.hasOwnProperty(i)) {
						t = tokenElement(i, tmp[i]);
						if (t) {
							$tokenBox.append(t);
							tokensCount++;
						}
					}
				}
			}
		} 
		catch (e) {
		}
		
		if (tokensCount<1) {
			$tokenInput.addClass('visible');
		}
		
		if (autocomplete && $.fn.autocomplete) {
			var opt={ajax:autocomplete, base:$tokenBox};
			if (readonly || box) {
				opt.wrapper = '<ul class="token_box autocomplete" />';
			}
			$tokenInput.autocomplete(opt);
		}
		
		//根据input调整宽度
		(function(){
			var fakeInput = $input.clone();
			fakeInput.show().css({visiblity:'hidden'}).appendTo('body');
			$tokenBox.width(fakeInput.innerWidth() - 2);
			fakeInput.remove();
		})();

		$tokenBox.click(function(){
			if (readonly) {			
				$tokenInput.trigger('autocomplete');
			}
			else {
				$tokenInput.appendTo($tokenBox).focus();
			}
			return false;
		}).append($tokenInput);
	
		$input.after($tokenBox);
		
		$tokenInput
		.data('token.focus', false);
				
		$tokenInput
		.bind('focus.autocomplete', function() {
			if (autocomplete) {
				setTimeout(function(){
					$tokenInput.trigger('autocomplete.autocomplete');
				}, 50);
			}
		})
		.bind('focus.token', function(){
			$tokenInput.siblings('.token').removeClass('token_selected');
			if( $tokenInput.next().length < 1){
				//input at the end
				var $prev=$tokenInput.prev();
				var offset;
				if($prev.length>0){
					offset = $prev.offset().left-$tokenBox.offset().left + $prev.width();
				}else{
					offset = 0;
				}
				$tokenInput.width($tokenBox.width() - offset - 10);
			}else{
				$tokenInput.width(40);
			}
			
			$tokenInput.data('token.focus', true);
			$tokenInput.addClass('visible');
			
			if (token_tip && $tokenInput.data('token.focus')) {
				var tooltip = $tokenInput.data('tooltip');
				if (!tooltip)  {
					tooltip = new Q.Tooltip({
						content: token_tip,
						position: 'left',
						offsetY: parseInt(0, 10)
					});
				}
				var offset = $tokenInput.position();
				var x = offset.left;
				var y = offset.top - 8;
				tooltip.remove();
				tooltip.show(x, y, $tokenBox);
				$tokenInput.data('tooltip', tooltip);
			}

		})
		.bind('_blur.token', function(){
			$tokenInput.data('token.focus', false);
			$tokenInput.val('').removeClass('visible');
		})
		.blur(function(){
			$tokenInput.data('token.focus', false);
			$tokenInput.val('').appendTo($tokenBox);
			if(tokensCount>0) { $tokenInput.width(0).removeClass('visible'); }
			if (token_tip && !$tokenInput.data('token.focus')) {
				var tooltip = $tokenInput.data('tooltip');
				tooltip.remove();
			}
		})
		.keydown(function(e){
			var focus=$tokenInput.data('token.focus');
			var code=e.which||e.keyCode;
			var $prev, $next;
			
			switch(code){
			case 8: //delete
				if(!focus || this.value==''){
					$prev=$tokenInput.prev('.token');
					$next=$tokenInput.next('.token');
					if($prev.length>0){
						if($prev.hasClass('token_selected')){
							$prev.trigger('unload.token');
							$tokenInput.trigger('focus.token');
						}else if($next.length>0 && $next.hasClass('token_selected')){
							$next.trigger('unload.token');
							$tokenInput.trigger('focus.token');
						}else{
							$prev.addClass('token_selected');
							$tokenInput.trigger('_blur.token');
						}
					}
					return false;
				}
				break;
			case 37: //left
				if ($tokenInput.caret().begin === 0) {
					$prev=$tokenInput.prev('.token');
					$next=$tokenInput.next('.token');
					if($prev.length>0){
						if($prev.hasClass('token_selected')){
							$prev.removeClass('token_selected');
							$tokenInput.after($prev).val('').trigger('focus.token');
						}else if($next.length>0 && $next.hasClass('token_selected')){
							$next.removeClass('token_selected');
							$tokenInput.trigger('focus.token');
						}else{
							$prev.addClass('token_selected');
							$tokenInput.trigger('_blur.token');
						}
					}else if(!focus){
						$tokenInput.trigger('focus.token');								
					}
					return false;
				}
				break;
			case 39: //right
				if ($tokenInput.caret().begin == this.value.length) {
					$prev=$tokenInput.prev('.token');
					$next=$tokenInput.next('.token');
					if($next.length>0){
						if($next.hasClass('token_selected')){
							$next.removeClass('token_selected');
							$tokenInput.before($next).trigger('focus.token');
						}else if($prev.length>0 && $prev.hasClass('token_selected')){
							$prev.removeClass('token_selected');
							$tokenInput.trigger('focus.token');
						}else{
							$next.addClass('token_selected');
							$tokenInput.trigger('_blur.token');
						}
					}else if(!focus){
						$tokenInput.trigger('focus.token');								
					}							
					return false;
				}
				break;
			}
			return true;
		})
		.bind('keypress.token_box', function(e){
			if(max>0 && tokensCount>=max) { return false; }
			if(!$tokenInput.data('token.focus')) { return false; }
			
			var code = e.which || e.keyCode;

			if((code==13 || code==3)){	//enter
				e.preventDefault();
				this.value = $.trim(this.value); // BUG #722::token_box 允许为空值(xiaopei.li@2011.06.24)
				if(this.value!='') {
					if(verify && autocomplete){
						$tokenInput.trigger("autoactivate.autocomplete"); 
					}
					else {
						var t=tokenElement(this.value);
						if (t) { 
							$tokenInput.before(t);
							tokensCount++; 
						}
						$tokenInput.val('').trigger('focus.token');
					}
					return false;
				}
			}
			
		})
		.bind('autoactivate.autocomplete', function(e, item){
			if(typeof(item)!='object'){
				item=$tokenInput.data('autocomplete.selected');
			}
			if(typeof(item)=='object' && item.text){
				var t;
				if (item.alt) { t = tokenElement(item.alt, item.text || item); }
				else { t = tokenElement(item.text || item); }
				
				if (t) { 
					$tokenInput.before(t); 
					tokensCount++;
				}
			}
			$tokenInput.val('');
			if(!readonly) {
				$tokenInput.trigger('focus.token');
			}
		})
		.click(function(){return false;});
		
		$input.bind('autoactivate.autocomplete', function(e, item) {
			$tokenInput.trigger('autoactivate.autocomplete', item);
		});
	
	}, function(){
		$(this)
			.unbind('autoactivate.autocomplete')
			.next('div.token_box').remove();
	});

})(jQuery);

