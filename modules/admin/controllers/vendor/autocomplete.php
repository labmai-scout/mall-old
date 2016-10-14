<?php

class Vendor_Autocomplete_Controller extends AJAX_Controller {

	function user($vid = 0) {

		$vendor = O('vendor', $vid);

		$s = Q::quote(Input::form('s'));

		if ($s) {

            $selector = "user[!hidden][atime][name*={$s} | name_abbr^={$s}]:limit(5)";

			$users = Q($selector);
			$count = $users->length();

			if (!$count) {
				Output::$AJAX[] = array(
					'html' => (string)V('autocomplete/special/empty'),
					'special' => TRUE
				);
			}
			else {
				foreach ($users as $user) {
					Output::$AJAX[] = array(
						'html' => (string) V('vendor/autocomplete/user', array('user'=>$user)),
						'alt' => $user->id,
						'text' => $user->name,
					);
				}

				$rest = $users->total_count() - $count;
				if ($rest > 0) {
					Output::$AJAX[] = array(
						'html' => (string) V('autocomplete/special/rest', array('rest' => $rest)),
						'special' => TRUE
					);
				}
			}
		}
	}
}
