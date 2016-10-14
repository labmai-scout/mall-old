<?php

class Customer_Autocomplete_Controller extends AJAX_Controller {

	function user($customer_id = 0, $relation = 'in') {

		$me = L('ME');
		$customer = O('customer', $customer_id);

		$s = Q::quote(Input::form('s'));

		if ($s) {

			switch ($relation) {
			case 'in':
				$selector = "$customer<member user[atime>0][name*={$s} | name_abbr^={$s}]:limit(5)";
				break;
			case 'not_in':
				$selector = "user[atime>0][name*={$s} | name_abbr^={$s}]:not(customer<member user):limit(5)";
				break;
			}

			$users = Q($selector);

			$count = $users->total_count();

			if (!$count) {
				Output::$AJAX[] = array(
					'html' => (string)V('autocomplete/special/empty'),
					'special' => TRUE
				);
			}
			else {
				foreach ($users as $user) {
					Output::$AJAX[] = array(
						'html' => (string) V('autocomplete/user', array('user'=>$user)),
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
