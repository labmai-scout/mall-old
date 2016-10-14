/*
 * Lightweight RTE - jQuery Plugin
 * Basic Toolbars
 * Copyright (c) 2009 Andrey Gayvoronsky - http://www.gayvoronsky.com
 */
var rte_tag		= '-rte-tmp-tag-';

var	rte_toolbar = {
	block			: {command: 'formatblock', select: '<select>\
	<option value="">--</option>\
	<option value="<p>">PARAGRAPH</option>\
	<option value="<h1>">H1</option>\
	<option value="<h2>">H2</option>\
	<option value="<h3>">H3</option>\
	<option value="<h4>">H4</option>\
	<option value="<h5>">H5</option>\
	<option value="<h6>">H6</option>\
</select>\
	', arg_cmp: 
		function(node, arg) {
			arg = arg.replace(/<([^>]*)>/, '$1');
			return (arg.toLowerCase() == node.nodeName.toLowerCase());
		}
	, tags: ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6']},
	s1				: {separator : true },
	bold			: {command: 'bold', tags:['b', 'strong']},
	italic			: {command: 'italic', tags:['i', 'em']},
	strikeThrough	: {command: 'strikethrough', tags: ['s', 'strike'] },
	underline		: {command: 'underline', tags: ['u']},
	s2				: {separator: true },
	justifyLeft   	: {command: 'justifyleft'},
	justifyCenter	: {command: 'justifycenter'},
	justifyRight	: {command: 'justifyright'},
	justifyFull		: {command: 'justifyfull'},
	s3				: {separator : true},
	outdent			: {command: 'outdent'},
	indent			: {command: 'indent'},
	s4				: {separator : true},
	subscript		: {command: 'subscript', tags: ['sub']},
	superscript		: {command: 'superscript', tags: ['sup']},
	s5				: {separator : true },
	orderedList		: {command: 'insertorderedlist', tags: ['ol'] },
	unorderedList	: {command: 'insertunorderedlist', tags: ['ul'] },
	s6				: {separator : true },
	link			: {exec: 
		function() {
			var self = this;
			//var panel = self.create_panel("Create link", 385);
			Dialog.show({
				data: '\
<div class="padding_1">\
<table class="form"><tr><td class="label">URL</td><td><input type="text" id="url" class="text" size="30" value=""/></td></tr>\
<tr><td class="label">Title</td><td><input type="text" id="title" class="text" size="30" value=""> <label>Target</label> <select id="target"><option value="">default</option><option value="_blank">new</option></select></td></tr>\
<tr><td></td><td><input id="ok" type="submit" class="button button_edit" value="Insert" /></td></tr></table>',
				success: function(){
					var panel=$('div.dialog');
					$('#view', panel).click(function() {
							(url.val().length >0 ) ? window.open(url.val()) : alert("Enter URL to view");
							return false;
						}
					);
					
					$('#ok', panel).click(
						function() {
							var url = $('#url', panel).val();
							var target = $('#target', panel).val();
							var title = $('#title', panel).val();
		
							if(self.getSelectedText().length <= 0) {
								alert('Select the text you wish to link!');
								return false;
							}
		
							Dialog.close(); 
		
							if(url.length <= 0)
								return false;
		
							self.execCommand('unlink');
		
							// we wanna well-formed linkage (<p>,<h1> and other block types can't be inside of link due to WC3)
							self.execCommand('createLink', rte_tag);
							
							$link = $(self.iframe).contents().find('a[href*="' + rte_tag + '"]');

							if(target.length > 0) $link.attr('target', target);
							if(title.length > 0) $link.attr('title', title);
							$link.attr('href', url);

							var tmp = $('<span></span>').append($link);
							self.selectionReplaceWith(tmp.html());
							return false;
						}
					)
				}
			});

		}, tags: ['a'] },
	unlink			: {command: 'unlink'},
	s8				: {separator : true },
	removeFormat	: {exec: 
		function() {
			this.execCommand('removeFormat');
			this.execCommand('unlink');
		}}/*,
	clear			: {exec: function() { if(confirm('Clear Document?')) this.set_content(''); }}*/
};

function cleanup_word(s, bIgnoreFont, bRemoveStyles, bCleanWordKeepsStructure) {
	s = s.replace(/<o:p>\s*<\/o:p>/g, '') ;
	s = s.replace(/<o:p>[\s\S]*?<\/o:p>/g, '&nbsp;') ;

	// Remove mso-xxx styles.
	s = s.replace( /\s*mso-[^:]+:[^;"]+;?/gi, '' ) ;

	// Remove margin styles.
	s = s.replace( /\s*MARGIN: 0cm 0cm 0pt\s*;/gi, '' ) ;
	s = s.replace( /\s*MARGIN: 0cm 0cm 0pt\s*"/gi, "\"" ) ;

	s = s.replace( /\s*TEXT-INDENT: 0cm\s*;/gi, '' ) ;
	s = s.replace( /\s*TEXT-INDENT: 0cm\s*"/gi, "\"" ) ;

	s = s.replace( /\s*TEXT-ALIGN: [^\s;]+;?"/gi, "\"" ) ;

	s = s.replace( /\s*PAGE-BREAK-BEFORE: [^\s;]+;?"/gi, "\"" ) ;

	s = s.replace( /\s*FONT-VARIANT: [^\s;]+;?"/gi, "\"" ) ;

	s = s.replace( /\s*tab-stops:[^;"]*;?/gi, '' ) ;
	s = s.replace( /\s*tab-stops:[^"]*/gi, '' ) ;

	// Remove FONT face attributes.
	if (bIgnoreFont) {
		s = s.replace( /\s*face="[^"]*"/gi, '' ) ;
		s = s.replace( /\s*face=[^ >]*/gi, '' ) ;

		s = s.replace( /\s*FONT-FAMILY:[^;"]*;?/gi, '' ) ;
	}

	// Remove Class attributes
	s = s.replace(/<(\w[^>]*) class=([^ |>]*)([^>]*)/gi, "<$1$3") ;

	// Remove styles.
	if (bRemoveStyles)
		s = s.replace( /<(\w[^>]*) style="([^\"]*)"([^>]*)/gi, "<$1$3" ) ;

	// Remove style, meta and link tags
	s = s.replace( /<STYLE[^>]*>[\s\S]*?<\/STYLE[^>]*>/gi, '' ) ;
	s = s.replace( /<(?:META|LINK)[^>]*>\s*/gi, '' ) ;

	// Remove empty styles.
	s =  s.replace( /\s*style="\s*"/gi, '' ) ;

	s = s.replace( /<SPAN\s*[^>]*>\s*&nbsp;\s*<\/SPAN>/gi, '&nbsp;' ) ;

	s = s.replace( /<SPAN\s*[^>]*><\/SPAN>/gi, '' ) ;

	// Remove Lang attributes
	s = s.replace(/<(\w[^>]*) lang=([^ |>]*)([^>]*)/gi, "<$1$3") ;

	s = s.replace( /<SPAN\s*>([\s\S]*?)<\/SPAN>/gi, '$1' ) ;

	s = s.replace( /<FONT\s*>([\s\S]*?)<\/FONT>/gi, '$1' ) ;

	// Remove XML elements and declarations
	s = s.replace(/<\\?\?xml[^>]*>/gi, '' ) ;

	// Remove w: tags with contents.
	s = s.replace( /<w:[^>]*>[\s\S]*?<\/w:[^>]*>/gi, '' ) ;

	// Remove Tags with XML namespace declarations: <o:p><\/o:p>
	s = s.replace(/<\/?\w+:[^>]*>/gi, '' ) ;

	// Remove comments [SF BUG-1481861].
	s = s.replace(/<\!--[\s\S]*?-->/g, '' ) ;

	s = s.replace( /<(U|I|STRIKE)>&nbsp;<\/\1>/g, '&nbsp;' ) ;

	s = s.replace( /<H\d>\s*<\/H\d>/gi, '' ) ;

	// Remove "display:none" tags.
	s = s.replace( /<(\w+)[^>]*\sstyle="[^"]*DISPLAY\s?:\s?none[\s\S]*?<\/\1>/ig, '' ) ;

	// Remove language tags
	s = s.replace( /<(\w[^>]*) language=([^ |>]*)([^>]*)/gi, "<$1$3") ;

	// Remove onmouseover and onmouseout events (from MS Word comments effect)
	s = s.replace( /<(\w[^>]*) onmouseover="([^\"]*)"([^>]*)/gi, "<$1$3") ;
	s = s.replace( /<(\w[^>]*) onmouseout="([^\"]*)"([^>]*)/gi, "<$1$3") ;

	if (bCleanWordKeepsStructure) {
		// The original <Hn> tag send from Word is something like this: <Hn style="margin-top:0px;margin-bottom:0px">
		s = s.replace( /<H(\d)([^>]*)>/gi, '<h$1>' ) ;

		// Word likes to insert extra <font> tags, when using MSIE. (Wierd).
		s = s.replace( /<(H\d)><FONT[^>]*>([\s\S]*?)<\/FONT><\/\1>/gi, '<$1>$2<\/$1>' );
		s = s.replace( /<(H\d)><EM>([\s\S]*?)<\/EM><\/\1>/gi, '<$1>$2<\/$1>' );
	} else {
		s = s.replace( /<H1([^>]*)>/gi, '<div$1><b><font size="6">' ) ;
		s = s.replace( /<H2([^>]*)>/gi, '<div$1><b><font size="5">' ) ;
		s = s.replace( /<H3([^>]*)>/gi, '<div$1><b><font size="4">' ) ;
		s = s.replace( /<H4([^>]*)>/gi, '<div$1><b><font size="3">' ) ;
		s = s.replace( /<H5([^>]*)>/gi, '<div$1><b><font size="2">' ) ;
		s = s.replace( /<H6([^>]*)>/gi, '<div$1><b><font size="1">' ) ;

		s = s.replace( /<\/H\d>/gi, '<\/font><\/b><\/div>' ) ;

		// Transform <P> to <DIV>
		var re = new RegExp( '(<P)([^>]*>[\\s\\S]*?)(<\/P>)', 'gi' ) ;	// Different because of a IE 5.0 error
		s = s.replace( re, '<div$2<\/div>' ) ;

		// Remove empty tags (three times, just to be sure).
		// This also removes any empty anchor
		s = s.replace( /<([^\s>]+)(\s[^>]*)?>\s*<\/\1>/g, '' ) ;
		s = s.replace( /<([^\s>]+)(\s[^>]*)?>\s*<\/\1>/g, '' ) ;
		s = s.replace( /<([^\s>]+)(\s[^>]*)?>\s*<\/\1>/g, '' ) ;
	}

	return s;
};


jQuery(function($){
	$('textarea.rich-textarea').livequery(function(){
		var $el = $(this);
		$el.rte({
			controls: rte_toolbar,
			base_url: $('base').attr('href'),
			css: $el.attr('css') || 'index.php/css?f=text'
		});
	});
});
