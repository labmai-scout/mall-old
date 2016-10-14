jQuery(function($) {
	//还可以输入多少字
	$(':visible > textarea.comment').livequery(function(){
		var max_length =  Q.comment_max_length || 450;

		String.prototype.cutstr = function(len) {
			var str_length = 0;  
			var str_len = 0;  
			str_cut = new String();  
			str_len = this.length;  
			for(var i = 0; i<str_len; i++) {  
				a = this.charAt(i);  
				str_length++; 
				if (escape(a).length > 4) str_length++;
			
				str_cut = str_cut.concat(a); 
				 
				if (str_length >= len) return str_cut;
			} 
			if (str_length<len) return this;
		}
		
		var $textarea = $(this);
		var $span = $textarea.prev('small').find('span');
		$textarea.bind('keyup', function(e){
			var current_length = $textarea.val().replace(/[^\x00-\xff]/g,"**").length;
			var spare = max_length - current_length;
			if (current_length > max_length) {
				$textarea.val($textarea.val().cutstr(max_length));
			}
			$span.html(Math.max(spare, 0));
		});
	});


	$(':visible > textarea.at,input.at').livequery(function(){
		 var $textarea = $(this);
		 var $el = $textarea.data('autocomplete');

		function _at_name(e){
		 	var keycode = e.which; 
			//得到当前光标所在的位置
			var CursorPosition = text_operate.getCursorPosition($textarea.get(0));

			//截取光标之前的所有文字
			var all_str = $textarea.val().substr(0, CursorPosition);
			var $el = $textarea.data('autocomplete');

			//输入回车或者光标前没有内容,则删除选择框
			if (keycode == 32 || keycode == 27 || !all_str){
				if ($el) {
					$el.remove();
					$textarea.data('autocomplete', null);
				}
			}
			else if (all_str && keycode != 40 && keycode != 38 && keycode != 13) {
		
				if (all_str.indexOf('@') == -1) return;
				var at_array = all_str.split('@');
				var current_str = at_array[at_array.length-1];

				var at_str = current_str.indexOf('}') == -1 ? current_str : null;

				if (at_str != null) {
					//得到＠后的内容
					var str_length = at_str.length;
					at_str = at_str.replace('{', '').replace('}', '');

					Q.trigger({
						url:$textarea.classAttr('at_autocomplete'),
						object:'at_users',
						event:'get',
						global:false,
						data: {at_str:at_str},
						success :function (data) {
							var $el = $textarea.data('autocomplete');
							if ($el) {
								$el.remove();
								$textarea.data('autocomplete', null);
							}

							var _zIndex = function($el) {
								var z = $el.css('z-index');
								$el.parents().each(function(){
									z = Math.max(z, $(this).css('z-index'));
								});
								return z;
							};


							var p = text_operate.getInputPositon($textarea.get(0));
							var left = p.left - 15;
							var top = p.top + 15;

							var $el = $(data['at_users']);
							$textarea.data('autocomplete', $el);
							$el.css({position: "absolute",left:left+'px',top:top+'px', zIndex: _zIndex($textarea)}).appendTo('body');
							$el.find('li:first:not(.special)').addClass('active');

							$(document).one('click', function(){
								$el.remove();
								$textarea.data('autocomplete', null);
							});

							//点击选择姓名
							$el.find('li:not(.special)').click(function(){
							 	var user_name = $(this).find('.name').html();
							 	var user_id = $(this).find('.id');
							 	
								//删除＠到光标的内容
								if (str_length) text_operate.del($textarea.get(0),str_length);

								var name_str = '{' + user_name + '}';

								if (user_id.length) {
									name_str += user_id.html();
								}

								//插入＠用户姓名，以空格结束
							 	text_operate.insertText($textarea.get(0), name_str);
							 	
							 	$el.remove();
							 	$textarea.data('autocomplete', null).focus();


							});

							$el.find('li:not(.special)').mouseenter(function(){
								$(this).addClass('active').siblings().removeClass('active');
							});
							
						}
						

					});
					
				}
				else{
					var $el = $textarea.data('autocomplete');
					if ($el) {
						$el.remove();
						$textarea.data('autocomplete', null);
					}
				}
				
			}
			
		};

		$textarea
			.click(_at_name)
			.keyup(_at_name);

		
		//上下选择＠用户并回车输入
		$textarea.keydown(function(e){
			var $el = $textarea.data('autocomplete');
			if ($el){
				//$textarea.focus();
			 	var keycode = e.which; 
				//得到当前光标所在的位置
				var CursorPosition = text_operate.getCursorPosition($textarea.get(0));

				//截取光标之前的所有文字
				var all_str = $textarea.val().substr(0, CursorPosition);

				var at_array = all_str.split('@');
				var current_str = at_array[at_array.length-1];

				var at_str = current_str.indexOf('}') == -1 ? current_str : null;

				var str_length = at_str.length;
				at_str = at_str.replace('{', '').replace('}', '');
				
				switch(e.which) {
				//down
				case 40:
					if($el){
						$active_li = $el.find('li.active');
						if (!$active_li.length || !$active_li.next('li:not(.special)').length) {
							$el.find('li:first:not(.special)').addClass('active').siblings().removeClass('active');
						}
						else{
							$active_li.next('li:not(.special)').addClass('active').siblings().removeClass('active');
						}
       					return false;  
					}
					break;
				//up
				case 38: 
					if($el){
						$active_li = $el.find('li.active');
						if (!$active_li.length || !$active_li.prev().length) {
							$el.find('li:not(.special):last').addClass('active').siblings().removeClass('active');
						}
						else {
							$active_li.prev('li:not(.special)').addClass('active').siblings().removeClass('active');
						}		 
						return false;  
					}
					break;
				//回车输入
				case 13:
					if($el){
						$active_li = $el.find('li.active');
						if ($active_li.length) {
							var user_name = $active_li.find('.name').html();
							var user_id = $active_li.find('.id');

							//删除＠到光标的内容
							if (str_length) text_operate.del($textarea.get(0),str_length);

							var name_str = '{' + user_name + '}';

							if (user_id.length) {
								name_str += user_id.html();
							}

							//插入＠用户姓名，以空格结束
						 	text_operate.insertText($textarea.get(0),name_str);
						 	
						 	$el.remove();
						 	$textarea.data('autocomplete', null).focus();
						}
						return false;
					}
				 	break;
				 default:
					return true;
				}
			}

		}) 
	
	});

});







//处理textarea和input
var text_operate = {  
    /**  
    * 获取输入光标在页面中的坐标  
    * @param        {HTMLElement}   输入框元素          
    * @return       {Object}        返回left和top,bottom  
    */  
    getInputPositon: function (elem) {  
    	//ie没有支持？？？
        // if (document.selection) {   //IE Support  
        //     elem.focus();  
        //     var Sel = document.selection.createRange();  
        //     return {  
        //         left: Sel.boundingLeft,  
        //         top: Sel.boundingTop,  
        //         bottom: Sel.boundingTop + Sel.boundingHeight  
        //     };  
        // } else {  
            var that = this;  
            var cloneDiv = '{$clone_div}', cloneLeft = '{$cloneLeft}', cloneFocus = '{$cloneFocus}', cloneRight = '{$cloneRight}';  
            var none = '<span style="white-space:pre-wrap;"> </span>';  
            var div = elem[cloneDiv] || document.createElement('div'), focus = elem[cloneFocus] || document.createElement('span');  
            var text = elem[cloneLeft] || document.createElement('span');  
            var offset = that._offset(elem), index = this._getFocus(elem), focusOffset = { left: 0, top: 0 };  
  
            if (!elem[cloneDiv]) {  
                elem[cloneDiv] = div, elem[cloneFocus] = focus;  
                elem[cloneLeft] = text;  
                div.appendChild(text);  
                div.appendChild(focus);  
                document.body.appendChild(div);  
                focus.innerHTML = '|';  
                focus.style.cssText = 'display:inline-block;width:0px;overflow:hidden;z-index:-100;word-wrap:break-word;word-break:break-all;';  
                div.className = this._cloneStyle(elem);  
                div.style.cssText = 'visibility:hidden;display:inline-block;position:absolute;z-index:-100;word-wrap:break-word;word-break:break-all;overflow:hidden;';  
            };  
            div.style.left = this._offset(elem).left + "px";  
            div.style.top = this._offset(elem).top + "px";  
            var strTmp = elem.value.substring(0, index).replace(/</g, '<').replace(/>/g, '>').replace(/\n/g, '<br/>').replace(/\s/g, none);  
            text.innerHTML = strTmp;  
  
            focus.style.display = 'inline-block';  
            try { focusOffset = this._offset(focus); } catch (e) { };  
            focus.style.display = 'none';  
            return {  
                left: focusOffset.left,  
                top: focusOffset.top,  
                bottom: focusOffset.bottom  
            };  
       // }  
    },  
  
    // 克隆元素样式并返回类  
    _cloneStyle: function (elem, cache) {  
        if (!cache && elem['${cloneName}']) return elem['${cloneName}'];  
        var className, name, rstyle = /^(number|string)$/;  
        var rname = /^(content|outline|outlineWidth)$/; //Opera: content; IE8:outline && outlineWidth  
        var cssText = [], sStyle = elem.style;  
  
        for (name in sStyle) {  
            if (!rname.test(name)) {  
                val = this._getStyle(elem, name);  
                if (val !== '' && rstyle.test(typeof val)) { // Firefox 4  
                    name = name.replace(/([A-Z])/g, "-$1").toLowerCase();  
                    cssText.push(name);  
                    cssText.push(':');  
                    cssText.push(val);  
                    cssText.push(';');  
                };  
            };  
        };  
        cssText = cssText.join('');  
        elem['${cloneName}'] = className = 'clone' + (new Date).getTime();  
        this._addHeadStyle('.' + className + '{' + cssText + '}');  
        return className;  
    },  
  
    // 向页头插入样式  
    _addHeadStyle: function (content) {  
        var style = this._style[document];  
        if (!style) {  
            style = this._style[document] = document.createElement('style');  
            document.getElementsByTagName('head')[0].appendChild(style);  
        };  
        style.styleSheet && (style.styleSheet.cssText += content) || style.appendChild(document.createTextNode(content));  
    },  
    _style: {},  
  
    // 获取最终样式  
    _getStyle: 'getComputedStyle' in window ? function (elem, name) {  
        return getComputedStyle(elem, null)[name];  
    } : function (elem, name) {  
        return elem.currentStyle[name];  
    },  
  
    
    // 获取光标在文本框的位置  
    _getFocus: function (elem) {  
        var index = 0;  
        if (document.selection) {// IE Support  
            elem.focus();  
            var Sel = document.selection.createRange();  
            if (elem.nodeName === 'TEXTAREA') {//textarea  
                var Sel2 = Sel.duplicate();  
                Sel2.moveToElementText(elem);  
                var index = -1;  
                while (Sel2.inRange(Sel)) {  
                    Sel2.moveStart('character');  
                    index++;  
                };  
            }  
            else if (elem.nodeName === 'INPUT') {// input  
                Sel.moveStart('character', -elem.value.length);  
                index = Sel.text.length;  
            }  
        }  
        else if (elem.selectionStart || elem.selectionStart == '0') { // Firefox support  
            index = elem.selectionStart;  
        }  
        return (index);  
    },  
  
    // 获取元素在页面中位置  
    _offset: function (elem) {  
        var box = elem.getBoundingClientRect(), doc = elem.ownerDocument, body = doc.body, docElem = doc.documentElement;  
        var clientTop = docElem.clientTop || body.clientTop || 0, clientLeft = docElem.clientLeft || body.clientLeft || 0;  
        var top = box.top + (self.pageYOffset || docElem.scrollTop) - clientTop, left = box.left + (self.pageXOffset || docElem.scrollLeft) - clientLeft;  
        return {  
            left: left,  
            top: top,  
            right: left + box.width,  
            bottom: top + box.height  
        };  
    }, 

    del:function(t, n){
		var p = this.getCursorPosition(t);
		var s = t.scrollTop;
		var val = t.value;
		t.value = n > 0 ? val.slice(0, p - n) + val.slice(p):
		val.slice(0, p) + val.slice(p - n);
		this.setCursorPosition(t ,p - (n < 0 ? 0 : n));
		firefox=navigator.userAgent.toLowerCase().match(/firefox\/([\d\.]+)/) && setTimeout(function(){
			if(t.scrollTop != s) t.scrollTop=s;
		},10)
	},

	setCursorPosition:function(t, p){
		this.sel(t,p,p);
	},


	sel:function(t, s, z){
		if(document.selection){
			var range = t.createTextRange();
			range.moveEnd('character', -t.value.length);
			range.moveEnd('character', z);
			range.moveStart('character', Number(s));
			range.select();
		}else{
			t.setSelectionRange(Number(s),z);
			t.focus();
		}

	},

	getCursorPosition:function  (ctrl) {//获取光标位置函数
		var CaretPos = 0;	// IE Support
		if (document.selection) {
			ctrl.focus ();
			var Sel = document.selection.createRange();
			Sel.moveStart ('character', -ctrl.value.length);
			CaretPos = Sel.text.length;
		}
		// Firefox support
		else if (ctrl.selectionStart || ctrl.selectionStart == '0'){
			CaretPos = ctrl.selectionStart;
		}
		return CaretPos;
	},

	insertText:function (obj,str) {//光标后面插入文字
	//ie貌似有问题，先注释了
    // if (document.selection) {
    //     var sel = document.selection.createRange();
    //     sel.text = str;
    //} else 
	    if (typeof obj.selectionStart === 'number' && typeof obj.selectionEnd === 'number') {
	        var startPos = obj.selectionStart,
	            endPos = obj.selectionEnd,
	            cursorPos = startPos,
	            tmpStr = obj.value;
	        obj.value = tmpStr.substring(0, startPos) + str + tmpStr.substring(endPos, tmpStr.length);
	        cursorPos += str.length;
	        obj.selectionStart = obj.selectionEnd = cursorPos;
	    } else {
	        obj.value += str;
	    }
	}
};
