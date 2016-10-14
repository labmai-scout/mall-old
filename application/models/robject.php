<?php

class RObject_Model extends ORM_Model
{
	protected $object_page = array(
		'view'=>'show/%object.%id'
		);
    private $vars = array();

    public function url($arguments=NULL, $query=NULL, $fragment=NULL, $op='view'){
		if (is_array($arguments)) $arguments = implode('.', $arguments);

		$url = $this->object_page[$op];

		$this->vars['object'] = $this->name();
		$this->vars['arguments'] = $arguments;

		$url = preg_replace_callback('/\[([^\[\]]+)\]/',
			array($this, '_url_ignore'), $url);
		
		if (preg_match_all('/%([a-z]+)/i', $url, $parts)) {
			foreach($parts[1] as $name) {
				$val = $this->vars[$name] ?: $this->get($name);
				if(NULL === $val) $val = $this->$name;
				$url = preg_replace('/%'.preg_quote($name).'/', $val, $url);
			}
		}

		return URI::url($url, $query, $fragment);
    }

    public function fetch_data($criteria=null, $no_fetch=false)
    {
        if (is_scalar($criteria)) {
            $criteria = [ 'id'=>$criteria ];
        }
        /**
         * 先不走缓存
        $cache_data = Cache::factory()->get($this->cache_name($criteria['id']));
        if ($cache_data !== FALSE) {
            $data = $cache_data;
        }
         */
        if (!$no_fetch && !$data) {
            $data = $this->fetchRPC((array)$criteria);
        }
        /*
         * 先不走缓存
        if ($data['id']) {
            Cache::factory()->set($this->cache_name($data['id']), $data);
        }
         */

        $this->set_data($data);
    }

}
