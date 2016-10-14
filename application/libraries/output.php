<?php

class Output extends _Output {
	
	static function safe_html($html, $tags=NULL) {
		/*
		if ($tags == NULL) 
			$tags = '<a><abbr><acronym><address><applet><area><b><base><basefont><bdo><big><blockquote><body><br><button><caption><center>
<cite><col><colgroup><dd><del><dir><div><dfn><dl><dt><fieldset><font><form><frame><frameset><h1><h2><h3><h4><h5><h6><hr>
<html><i><iframe><img><input><ins><isindex><kbd><label><legend><li><map><menu><noframes><object><ol><optgroup><option><p>
<param><pre><q><s><samp><select><small><span><strike><strong><sub><sup><table><tbody><td><textarea><tfoot><th><thead><title>
<tr><tt><u><ul><var><xmp>';	
		return strip_tags($html, $tags);
		*/
		$del = array(
		   "/<script.*>(.*)<\/script>/siU",
		   '/on(click|dblclick|mousedown|mouseup|mouseover|mousemove|mouseout|keypress|keydown|keyup)="[^"]*"/i',
		   '/on(abort|beforeunload|error|load|move|resize|scroll|stop|unload)="[^"]*"/i',
		   '/on(blur|change|focus|reset|submit)="[^"]*"/i',
		   '/on(bounce|finish|start)="[^"]*"/i',
		   '/on(beforecopy|beforecut|beforeeditfocus|beforepaste|beforeupdate|contextmenu|cut)="[^"]*"/i',
		   '/on(drag|dragdrop|dragend|dragenter|dragleave|dragover|dragstart|drop|losecapture|paste|select|selectstart)="[^"]*"/i',
		   '/on(afterupdate|cellchange|dataavailable|datasetchanged|datasetcomplete|errorupdate|rowenter|rowexit|rowsdelete|rowsinserted)="[^"]*"/i',
		   	'/on(afterprint|beforeprint|filterchange|help|propertychange|readystatechange)="[^"]*"/i',
            '/javascript\:.*(\;|")/',
		);
		$replace = array('$1','','','','','','','','','');
		$html = strip_tags(preg_replace($del, $replace, $html));
		return $html;
	}

	static function & T($format, $args=NULL, $options=NULL) {
		if (Config::get('debug.i18n_ipe')) {
			if ($args) foreach($args as &$v) {
				$v = preg_replace('/\{\[.+?\]\}/', '', $v);
			}
			$format = preg_replace('/\{\[.+?\]\}/', '', $format);
		}
		return parent::T($format, $args, $options);
	}

	static function HTML_brief($html, $length=NULL) {
		$html = strip_tags($html);
		if ($length > 0 && mb_strlen($html) > $length) {
			$html = mb_substr($html, 0, $length) . '...';
		}

		return $html;
	}

	static function H($str, $convert_return = FALSE) {

		$str = parent::H($str);

		if ($convert_return) {
			$in = array(
				'`((?:https?|ftp)://\S+[[:alnum:]]/?)`si',
				'`((?<!//)(www\.[\w.-/]+[[:alnum:]]/?))`si',
				'`\r\n|\n`si',
			);  

			$out = array(
				'<a href="$1" class="blue prevent_default" target="_blank">$1</a> ',
				'<a href="http://$1" class="blue prevent_default" target="_blank">$1</a>',
				'<br/>',
			);  

			$str = preg_replace($in, $out, $str);
		}

		return $str;
	}

}
