<?php

class Controller extends _Controller {
		
	function _before_call($method, &$params) {

		parent::_before_call($method, $params);
		
		if (!$this->_is_accessible()) URI::redirect('error/401', NULL, 401);

		$this->_controller_setup($method, $params);

	}

	private function _is_accessible() {

		$is_module = defined('MODULE_ID') ? TRUE : FALSE;
		
		$me = L('ME');
		if ($is_module) {
			if (!Module::is_installed(MODULE_ID)) {
				return FALSE;
			}
		}

		$path = Config::get('system.controller_path') . '/' . Config::get('system.controller_method');
		
		if ($is_module) {
			if (!$path) return TRUE;
			$path = '!'.MODULE_ID.'/'.$path;
		}

		$access_checked = FALSE;	//是否已检查过权限
		while ($path) {
			$requires = Config::get('access.'.$path);
			
			if ($requires === TRUE) {
				return TRUE;
			}
			elseif ($requires === FALSE) {
				return FALSE;
			}
			elseif(is_array($requires)){
				foreach($requires as $req){
					if (!$me->access($req)) return FALSE;
				}
				$access_checked = TRUE;
				break;
			}
			$path = preg_replace('/\/?[^\/]*$/', '', $path);
		}
		
		//is_module在之前module->is_accessible中就会判断是否有全局支持, 为了提高效率 对于is_module的不进行进一步判断
		if (! $access_checked ) {
			if ($is_module) {
				return Module::is_accessible(MODULE_ID);
			}
			else {
				$requires = Config::get('access.*');
				if ($requires === TRUE) {
					return TRUE;
				}
				elseif ($requires === FALSE) {
					return FALSE;
				}
				elseif(is_array($requires)){
					foreach($requires as $req){
						if (!$me->access($req)) return FALSE;
					}
				}
			}
		}
		
		return TRUE;
	}

	private function _controller_setup($method, $params) {
				
		$path = (defined('MODULE_ID') ? '!'.MODULE_ID.'/' :'')
						.Config::get('system.controller_path')
						.'/'
						.Config::get('system.controller_method');

		$setup_events = array();
		$ready_events = array();

		//绑定controller[*].setup/ready事件
		$event = 'controller[*].setup';
		if (Config::get('hooks.'.$event)) {
			$setup_events[] = $event;
		}
		$event = 'controller[*].ready';
		if (Config::get('hooks.'.$event)) {
			$ready_events[] = $event;
		}

		//绑定controller[path].setup/ready事件
		while ($path) {
			$event = 'controller['.$path.'].setup';
			if (Config::get('hooks.'.$event)) {
				$setup_events[] = $event;
			}
			$event = 'controller['.$path.'].ready';
			if (Config::get('hooks.'.$event)) {
				$ready_events[] = $event;
			}
			$path = preg_replace('/\/?[^\/]*$/', '', $path);			
		}
		
		Event::trigger($setup_events, $this, $method, $params);
		Event::trigger($ready_events, $this, $method, $params);

	}
	
}
