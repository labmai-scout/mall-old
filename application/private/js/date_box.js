Number.prototype.fillzero = function(n){
	var arr=new Array((l=this.toString().length)<n?n-l:0);
	arr.push(this);
	return arr.join('0');
};

jQuery(function($) {
	
	/*
	通过q-date-format属性来设置format
	q-date_format="$year年$month月$day日$hour小时$min分钟$sec秒$mer"
	q-date_format="$year年$month月$day日"

	不是24小时制的时候默认为AM,PM显示，可以传入q-date_meridiem="上午,下午"进行设置
	*/

	var format = "$year/$month/$day $hour:$min:$sec $mer";
	var fields = ['year', 'month', 'day', 'hour', 'min', 'sec', 'mer'];


	var $curr_input;
	var hour_type = Q['24hour'];
	var MON=0, DAY=1, YEAR=2, HOUR=3, MIN=4, MER=5;
	function _foo(e) {e.preventDefault(); return false;}

	function _zIndex($el) {
		var z = parseInt($el.css('z-index')) || 0;
		$el.parents().each(function(){
			var nz = parseInt($(this).css('z-index')) || 0;
			z = Math.max(z, nz);
		});
		return z;
	};

	$(':visible > input.date').livequery(function(){

		var supportTouch = Q.supportTouch();
		var MOUSEDOWN_EVENT = supportTouch ? 'touchstart' : 'mousedown';
		var	MOUSEMOVE_EVENT = supportTouch ? 'touchmove' : 'mousemove';
		var MOUSEUP_EVENT = supportTouch ? 'touchend' : 'mouseup';

		var $input = $(this);
		var $hidden=$('<input type="hidden" />');

		var date_format = $input.classAttr('date_format') || format;
		//24小时制没有mer 12小时的有默认的am，pm
		if (hour_type) {
			date_format = date_format.replace('$mer', '');	
		}else {
			var format_meridiem = ['AM','PM'];
			
			var date_meridiem = $input.classAttr('date_meridiem');
			if (date_meridiem) {
				format_meridiem = date_meridiem.split(',');
			}
		}

		for (var i in fields) {
			if (date_format.indexOf(fields[i]) == -1) break;
			var format_type = fields[i];
		}

		$hidden.attr('name', $input.attr('name'));
		$input.removeAttr('name').after($hidden);
		$input.data('date_box.hidden', $hidden[0]);
		$input.data('date_box.date_format', date_format);
		$input.data('date_box.format_meridiem', $input.data('date_box.format_meridiem'));
		$input.data('date_box.format_type', format_type);


		function _mousewheel(e, delta) {

			var $el = $(this);
			var unit = $el.attr('q-date-unit');
			var time = $input.data('date_box.time');

			var d = Math.ceil(Math.abs(delta)/2); // 此处可统一各浏览器间 不同的鼠标滚轮一格的值 到1
			if (d==0) {
				return false;
			}

			delta = delta > 0 ? d : -d;

			var uflag = false;
			if (unit == 'mer') {
				if(delta > 0){	//a A
					var h=time.getHours();
					if(h>11)time.setHours(h-12);
					else if(h==0)time.setHours(12);
				} 
				else {	//p P
					var h=time.getHours();
					if(h<12)time.setHours(h+12);
					else if(h==12)time.setHours(0);
				}
				uflag=true;
			} 
			else { //0-9
				switch(unit) {
					case 'month':
						var m=time.getMonth();	//0-11
						m += delta;
						if (m > 11) {
							m = 0;
							time.setFullYear(time.getFullYear()+1);
						}
						else if (m < 0) {
							m = 11;
							time.setFullYear(time.getFullYear()-1);
						}
						time.setMonth(m);
						break;
					case 'day':
						{
							var t = time.getTime();
							t += delta * 86400000;
							time.setTime(t);
						}
						break;
					case 'year':
						var y = time.getFullYear();	
						y += delta;
						time.setFullYear(y);
						break;
					case 'hour':
						{
							var t = time.getTime();
							t += delta * 3600000;
							time.setTime(t);
						}
						break;
					case 'min':
						{
							var t = time.getTime();
							t += delta * 60000;
							time.setTime(t);
						}
						break;
					case 'sec':
						{
							var t = time.getTime();
							t += delta * 1000;
							time.setTime(t);
						}
						break;
				}
			}
			_setTime(time);
			return false;
		}

		var curr_unit;
		function _picker_mousewheel(e, delta) {
			if (curr_unit) {
				_mousewheel.apply(curr_unit, [e, delta]);
			}
			return false;
		}

		function _mouseenter() {
			curr_unit = this;
			$(curr_unit).focus();
		}

		function _removePicker() {
			if ($curr_input) {
				$($curr_input.data('date_box.picker')).remove();
				$curr_input.data('date_box.picker', null);
				$curr_input = null;
			}
		}

		function _keydown(e){
			var $el = $(this);
			var unit = $el.attr('q-date-unit');
			switch(e.which){
				case 9:
				case 13:
					(function() {
						_removePicker();
						var $inputs = $input.parents('form').find(':input:visible');
						var i = $inputs.index($input);
						if (i == $inputs.length - 1) { i = 0 }
						else i++;
						$inputs.eq(i).focus();
					})();
					return false;
				case 8: //使DELETE失效
					return false;
				case 37: //left
					$el.parents('.unit:first').prev().find('a').focus();
					break;
				case 39: //right
					$el.parents('.unit:first').next().find('a').focus();
					break;
				case 38: //up
				case 40: //down
					var time = $input.data('date_box.time');
					var delta = e.which == 38 ? 1 : -1;

					switch(unit){
						case 'mer':
							var h = time.getHours();
							if(h>11) time.setHours(h-12);
							else time.setHours(h+12);
							break;
						case 'month':
							var m=time.getMonth()+1;	//0-11 +1 = 1-12
							m+=delta;
							if(m<1) {
                                m=12;
                                time.setFullYear(time.getFullYear()-1);
                            }
							else if(m>12) {
                                m=1;
                                time.setFullYear(time.getFullYear()+1);
                            }
							time.setMonth(m-1);
							break;
						case 'day':
							time.setDate(time.getDate() + delta);
							break;
						case 'year':
							time.setFullYear(time.getFullYear() + delta);
							break;
						case 'hour':
							time.setHours(time.getHours() + delta);
							break;
						case 'min':
							time.setMinutes(time.getMinutes() + delta);
							break;
						case 'sec':
							time.setSeconds(time.getSeconds() + delta);
							break;
					}
					_setTime(time);
					break;
				default:
					if (e.which>=48 && e.which<=112) return true;
			}

			return false;
		}

		function _keypress(e) {
			var $el = $(this);
			var unit = $el.attr('q-date-unit');
			var ekey = e.which;
			switch (ekey) {
				case 8: //delete
				case 37: //left
				case 38: //up
				case 39: //right
				case 40: //down
					return false;
				case 9:
				case 13:
					return true;
				default:
					var time = $input.data('date_box.time');
					var uflag = false;
					if (unit == 'mer') {
						if(ekey==97||ekey==65){	//a A
							var h=time.getHours();
							if(h>11)time.setHours(h-12);
							else if(h==0)time.setHours(12);
						} 
						else if(ekey==112||ekey==80){	//p P
							var h=time.getHours();
							if(h<12)time.setHours(h+12);
							else if(h==12)time.setHours(0);
						}
						uflag=true;
					} 
					else if(ekey>=48 && ekey<=57) {//0-9
						switch(unit) {
							case 'month':
								time.getTime()
									var m=time.getMonth()+1;	//0-11 +1 = 1-12
								m=(m % 10) *10 + ekey - 48;
								if(m>=1 && m<=12){
									uflag=true;
								}else{
									m=ekey-48;
									if(m>=1 && m<=12)
										uflag=true;
								}
								if(uflag){
									time.setMonth(m-1);
								}
								break;
							case 'day':
								var d=time.getDate();	//1-31
								d=(d % 10) *10 + ekey - 48;
								var tt=new Date(time.getTime());
								tt.setDate(d);
								if(tt.getDate()==d){
									uflag=true;
								}else{
									d=ekey-48;
									tt.setDate(d);
									if(tt.getDate()==d)uflag=true;
								}
								if(uflag){
									time.setDate(d);
								}
								break;
							case 'year':
								var y=time.getFullYear();	
								y=(y % 1000) *10 + ekey - 48;
								time.setFullYear(y);
								uflag=true;
								break;
							case 'hour':
								{
									var fh = time.getHours();	//0-23
									if ($el.data('date_box.format_meridiem')) {
										var h = fh % 12;
										h = (h % 10) * 10 + ekey - 48;
										if (h>=1 && h<=12){
											uflag=true;
										}
										else {
											h=ekey-48;
											uflag=true;
										}

										h %= 12;
										if (fh > 11) {
											h += 12;
										}

										time.setHours(h % 24);
									}
									else {
										fh = (fh % 10) * 10 + ekey - 48;
										if (fh >= 0 && fh <= 23) {
											uflag = true;
										}
										else {
											fh = ekey - 48;
											uflag = true;
										}

										time.setHours(fh % 24); 
									}

								}
								break;
							case 'min':
								{
									var m=time.getMinutes(); //0-60
									m=(m % 10) * 10 + ekey - 48;
									if(m>=0 && m<=60){
										uflag=true;
									}else{
										m=ekey - 48;
										uflag=true;
									}
									time.setMinutes(m);
								}
								break;
							case 'sec':
								{
									var m=time.getSeconds(); //0-60
									m=(m % 10) * 10 + ekey - 48;
									if(m>=0 && m<=60){
										uflag=true;
									}else{
										m=ekey - 48;
										uflag=true;
									}
									time.setSeconds(m);
								}
								break;
						}
					}
					if(uflag){
						_setTime(time);
					}
			}

			e.preventDefault();
			return false;
		};

		function _mousedown(e) {
			e = Q.event(e);
			var isTouch = e.isTouch;

			var target = this;
			var $target = $(this);
			var $document = isTouch ? $target : $(document);

			$target.focus();

			var y = e.pageY;

			var _dragmove = function(e) {
				e = Q.event(e);
				var delta = y - e.pageY;
				y = e.pageY;

				// if (Q.browser.msie) delta = Math.round(delta/10);
				// delta 是单位时间内拖动的距离，无法累积
				// 上面的方法使得必须拖得猛(很快拖出较长距离)才能走，所以就使控件很迟钝
				// 注释掉使IE下与FF/Chrome/Safari体验相同，虽然力度大时走得较快，但较迟钝更符合期望
				// (xiaopei.li@2011.08.05)

				_mousewheel.apply(target, [e, delta]);

				e.preventDefault();
				return false;	
			};

			var _dragend = function(e) {
				$document.unbind('mousemove', _dragmove);
				e.preventDefault();			
				return false;	
			};

			$document
				.bind(MOUSEMOVE_EVENT, _dragmove)
				.one(MOUSEUP_EVENT, _dragend);

			e.preventDefault();
			return false;	
		}

		function _showPicker() {
			var $input = $(this);
			if ($input.data('date_box.picker')) return;

			var $picker = $('<div class="datetime_picker"></div>');
			$input.data('date_box.picker', $picker[0]);

			if ($curr_input) {
				_removePicker();
			}

			$curr_input = $input;

			var html = $input.data('date_box.date_format');
			for (var i in fields) {
				//如果是24小时制则不显示 mer
				if (fields[i] == 'mer' && hour_type) continue;
				html = html.replace('$'+fields[i], '<div class="unit '+fields[i]+'"><a href="#" q-date-unit="'+fields[i]+'" /></div>');	
			}

			var _removeTimeout = null;

			$picker.html(html);
			$picker
				.click(_foo)
				.mousewheel(_picker_mousewheel)
				/*
				   .mouseenter(function(){
				   if (_removeTimeout) {
				   clearTimeout(_removeTimeout); _removeTimeout = null; 
				   }
				   })
				   .mouseleave(function(){
				   if (_removeTimeout) clearTimeout(_removeTimeout);
				   _removeTimeout = setTimeout(function(){
				   _removePicker();
				   }, 500);
				   })*/
				.find('a[q-date-unit]')
				.mousewheel(_mousewheel)
				.keydown(_keydown)
				.keypress(_keypress)
				.mouseenter(_mouseenter)
				.bind(MOUSEDOWN_EVENT, _mousedown)
				.focus(function(){$(this).addClass('focus');})
				.blur(function(){$(this).removeClass('focus');})
				//.mouseleave(_mouseleave)
				.click(_foo);

			$picker
				.appendTo('body')
				.css({zIndex: _zIndex($input), left: $input.offset().left - 5, top: $input.offset().top - 5})
				.show()
				.find('a[q-date-unit]:first')
				.focus();

			$(document).unbind('mousedown', _removePicker).one('mousedown', _removePicker);

			_setTime($input.data('date_box.time'));
			return false;
		};

		// $input不做编辑 只能点击
		$input
			.bind('focus', _showPicker)
			//.bind('mouseenter', _showPicker)
			.bind('keydown', _foo);

		function _setTime(time) {

			$hidden.val(Math.floor(time.getTime()/1000));

			var $picker = $($input.data('date_box.picker'));

			var $format_type = $input.data('date_box.format_type');
			var $format_meridiem = $input.data('date_box.format_meridiem');
			switch($format_type){
				case 'year':
					time.setMonth(0);
				case 'month':
					time.setDate(1);
				case 'day':
					time.setHours(0, 0);
			}

			var month = time.getMonth()+1;
			var day=time.getDate();
			var year=time.getFullYear();
			var hour=time.getHours();
			var minute=time.getMinutes();
			var second=time.getSeconds();
			var mer = 0;
	
			if ( undefined != $format_meridiem) {
				mer = hour > 11 ? 1 : 0;
				hour = hour>12 ? hour - 12 : (hour === 0 ? 12 : hour);
			}

			var data = {
				'year': year.fillzero(4),
				'month': month.fillzero(2),
				'day': day.fillzero(2),
				'hour': hour.fillzero(2),
				'min': minute.fillzero(2),
				'sec' : second.fillzero(2),
				'mer': $format_meridiem !== undefined ? $format_meridiem[mer] : ''
			}

			// %year/%month/%day %hour:%min %mer
			var str = $input.data('date_box.date_format');
			for (var i in fields) {
				str = str.replace('$'+fields[i], data[fields[i]]);
				$picker.find('.'+fields[i] + ' a').html(data[fields[i]]);
			}

			$input.data('date_box.time', time).val(str);
		}

		var time = new Date();
		var value = parseInt($input.val());
		if(value) time.setTime(value*1000);
		_setTime(time);

		// 调整input的宽度
		var str = $input.val();
		var $dummy=$('<div class="text_like" style="position:absolute; left:-10000px; top:-10000px">' + str + '</div>');
		$dummy.appendTo('body');
		$input.width($dummy.innerWidth() - 2).val(str);
		$dummy.remove();


	}, function(){
		var $input = $(this);
		var $hidden = $($input.data('date_box.hidden'));
		/*
			在IE9浏览模式+IE7文档模式的情况下, livequery的运行方式比较诡异，fn2 会直接在fn 之后运行，不会在该元素被关闭或者隐藏时候触发，跟其他任何浏览器的运转触发机制都存在差异。
			此处暂时没有想到很好的解决方式，强硬判断元素当前状态来进行处理。
		*/
		if ($hidden.length && !$input.is(':visible')){
			$input
				.attr('name', $hidden.attr('name'))
				.val($hidden.val());
			$hidden.remove();
			$input.unbind();
		}
	});

});
