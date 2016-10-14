/*JSFORMATTED*/
jQuery(function($){

	function log() {
		if (window.console) {
			window.console.log.apply(this, arguments);
		}
	}

	var $form = $('<div class="form no_translation"><form q-widget="i18n_ipe" q-object="form" q-event="submit"><input name="domain" class="text readonly" readonly="readonly"/><div class="hr_1">&#160;</div><textarea cols="80" rows="1" class="autogrow" name="orig" /><div class="hr_1">&#160;</div><textarea cols="80" rows="1" class="autogrow" name="trans"/><div class="hr_1">&#160</div><div><input type="submit" class="button button_edit middle" name="submit" value="编辑" /> <a class="button button_cancel middle">关闭</a></div></form></div>');

	$form.css({
		position: 'fixed',
		display: 'block',
		border: '1px solid #999',
		padding: '10px',
		'box-shadow':'0 2px 6px rgba(0,0,0,0.5)',
		'z-index':20000,
	});

	$form.find('.button_cancel')
	.click(function(){
		$form.detach();
	});

	function addHelper($el, parts) {

		if (!parts) return;

		var $helper = $('<span class="no_translation">T</span>');
		$helper
		.css({
			'cursor': 'default',
			'background-color': '#ff0',
			'color': '#000',
			'font-weight':'bold',
			'font-size':'10px',
			'padding':'0 4px',
		});

		if ($el.is('a, :input')) {
			$el.after($helper);
		}
		else {
			var $el2 = $el.parents('a:eq(0), :input:eq(0)');
			if ($el2.length) {
				$el2.after($helper);
			}
			else {
				$el.append($helper);
			}
		}

		$helper.click(function(e){
				
			$form.appendTo('body');
			$form.css({
				left: Math.floor(($(window).width() - $form.width()) / 2), 
				top: Math.floor(($(window).height() - $form.height())/2)
			});
			$form.find('[name=domain]').val(parts[1]);
			$form.find('[name=orig]').val(parts[2].replace(/@\//g, '%'));
			$form.find('[name=trans]').val(parts[3].replace(/@\//g, '%'));

			e.preventDefault();
			return false;
		});

		var $helpers = $el.data('i18n_helpers')||[];
		$helpers.push($helper);
		$el.data('i18n_helpers', $helpers);

	}

	function removeHelpers($el) {
		var $helpers = $el.data('i18n_helpers')||[];
		for(var i in $helpers) {
			$helpers[i].remove();
		}
		$el.data('i18n_helpers', null);
	}

	$(":visible :not(script, .no_translation, .no_translation *)").livequery(function(){
		var $el = $(this);
		var txtNodes;
		
		if ($el.is(':input')) {
			txtNodes = [];
		}
		else {
			txtNodes= $.grep(this.childNodes, function(n){ return n.nodeType == 3 && $.trim(n.nodeValue) != ""; });
		}

		var i;
		for (i=0; i< this.attributes.length; i++) {
			if (this.attributes[i].nodeValue) {
				txtNodes.push(this.attributes[i]);
			}
		}
		for (i=0; i<txtNodes.length; i++) {
			var node = txtNodes[i];
			var s = node.nodeValue;
			if (s == undefined) continue;
			s = s.replace(
				/\{\[(\w+):(.+?)\/\/(.+?)\]\}/g, function(){
					addHelper($el, arguments);
					return '';
				});
			s = s.replace(
				/\%7B\%5B(\w+)\%3A(.+?)\%2F\%2F(.+?)\%5D\%7D/g, function(){
					/*var args = arguments;
					for (var j=0; j<args.length; j++){
						args[j] = decodeURIComponent(args[j]);
					}
					addHelper($el, args); */
					return '';
				});
			node.nodeValue = s;
		}
	}, function(){
		var $el = $(this);
		removeHelpers($el);
	});

	$(":visible :input:not(.no_translation *)").livequery(function(){
		var $el = $(this);
		var s = $el.val();
		s = s.replace(
			/\{\[(\w+):(.+?)\/\/(.+?)\]\}/g, function(){
				addHelper($el, arguments);
				return '';
			});
		s = s.replace(
			/\%7B\%5B(\w+)\%3A(.+?)\%2F\%2F(.+?)\%5D\%7D/g, function(){
				addHelper($el, arguments);
				return '';
			});
		$el.val(s);
	}, function(){
		var $el = $(this);
		removeHelpers($el);
	});

});
