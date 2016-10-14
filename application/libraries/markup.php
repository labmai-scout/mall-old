<?php

class Markup {
    
    private $_map;

    private $_source;
    private $_output;
    
    private $_uniqid = 0;

    function __construct($source, $show_html=TRUE, $filters=NULL) {
        
        if (!is_array($filters)) {
            $raw_filters = array_map(function($raw_filter) {
                return isset($raw_filter['callback']) ? $raw_filter : ['callback'=>$raw_filter, 'weight'=>0];
            }, (array) Config::get('markup.filters'));
            
            usort($raw_filters, function($a, $b) {
                return $a['weight'] - $b['weight'];
            });
            
            $filters = array_map(function($raw_filter) { return $raw_filter['callback']; }, $raw_filters);
        }
        
        
        $this->_map = new ArrayObject;
        $this->_source = $source;
        foreach ($filters as $filter) {
            $this->_source = call_user_func($filter, $this->_source, $show_html, $this);
        }
    
    }
    
    function map_string($string) {
        $key = '__MARKUP'.($this->_uniqid++).'__';
        $this->_map[$key] = $string;
        return $key;
    }
    
    function __toString() {
        if (!$this->_output) {
            $map = array_reverse((array)$this->_map);
            $this->_output = strtr($this->_source, (array)$map);
        }
        return $this->_output;
    }
    
	// 替换URL路径
    static function decode_URL($source, $show_html, $markup) {
        
        if ($show_html) {
            
            $source = preg_replace_callback('`<a\s+.*href.+<\/a>`si', function ($matches) use ($markup) {
                return $markup->map_string($matches[0]);
            }, $source);

            $source = preg_replace_callback('`((?:https?|ftp)://\S+[[:alnum:]]/?)`si', function ($matches) use ($markup) {
                $replaced = '<a href="'.H($matches[1]).'" class="blue prevent_default" target="_blank">'.H($matches[1]).'</a>';
                return $markup->map_string($replaced);
            }, $source);
        
            $source = preg_replace_callback('`((?<!//)(www\.\S+[[:alnum:]]/?))`si', function ($matches) use ($markup) {
                $replaced = '<a href="'.H($matches[1]).'" class="blue prevent_default" target="_blank">'.H($matches[1]).'</a>';
                return $markup->map_string($replaced);
            }, $source);
        
            $source = preg_replace_callback('`\r\n|\n`si', function ($matches) use ($markup) {
                $replaced = '<br/>';
                return $markup->map_string($replaced);
            }, $source);
        }
        
        return $source;
    }
    
	static function encode_Q($object){
		return "[[Q:$object]]";
	}

	// 替换Q对象
    static function decode_Q($source, $show_html, $markup) {
        
        if ($show_html) {
            $source 
                = preg_replace_callback(
                    '/\[\[Q:(\w+?)#(\d*)\]\]/', 
                    function ($matches) use ($markup) {
                		$oname = $matches[1];
                		$id = $matches[2];
            			$object = O($oname,$id);
        				if ($object->name) {
        					$ret = URI::anchor($object->url(), H($object->name),'class="markup_link"');
        				}
        				else {
        					/*
        					  xiaopei.li@2011.03.01
        					  改进markup，若object无name属性，
        					  则可实现hook(oname.markup.name)，
        					  以生成效果更好的<a>
        					  TODO trigger or call a object's method?
        					*/
        					$event = $oname.'.markup.name';
        					$name = Event::trigger($event, $object);
        					$ret = URI::anchor($object->url(), $name, 'class="markup_link"');
        				}                		
                        return $markup->map_string($ret);
                    }, 
                    $source);
        }
        else {
            $source 
                = preg_replace_callback(
                    '/\[\[Q:(\w+?)#(\d*)\]\]/', 
                    function ($matches) use ($markup) {
                		$oname = $matches[1];
                		$id = $matches[2];
            			$object = O($oname,$id);
        				if ($object->name) {
        					$ret = $object->name;
        				}
        				else {
        					$event = $oname.'.markup.name';
        					$ret = Event::trigger($event, $object);
        				}
                        return $markup->map_string($ret);
                    }, 
                    $source);
        }
  
        return $source;
    }
 
    static function decode_markdown_link($source, $show_html, $markup) {

		if (!$show_html) return $source;

		return preg_replace_callback('/\[\s*([\w\W]+?)\s*\]\s*\(\s*([\w\W]+?)\s*\)/', function ($matches) use ($markup) {
			$string = URI::anchor($matches[2], $matches[1], 'class="blue"');
			return $markup->map_string($string);
		}, $source); 

    }

 
   
}

