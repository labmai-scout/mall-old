<?php

class Layout_Controller extends _Layout_Controller {

	function _before_call($method, &$params) {
		parent::_before_call($method, $params);

		$this->add_css('mall form tab table button tooltip user dialog dropdown comment tag token_box widgets/rateit autocomplete rte rte_container');
		$this->add_js('lims hint tooltip number_box dialog dropdown popfade autogrow tag_selector token_box widgets/jquery.rateit autocomplete rte rte.toolbar');
	}

	function _after_call($method, &$params) {
		parent::_after_call($method, $params);

		$this->add_css('theme');

		if (!isset($this->layout->header)) {
			$this->layout->header = V('application:header');
		}

		if (!isset($this->layout->footer)) {
			$this->layout->footer = V('application:footer');
		}


		if (Input::route() != $_SESSION['system.current_layout_url']) {
			$_SESSION['system.last_layout_url'] = $_SESSION['system.current_layout_url'];
		}

		$_SESSION['system.current_layout_url'] = Input::route();

	}
}
