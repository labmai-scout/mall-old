<?php

class Autocomplete_Controller extends AJAX_Controller {

	function user($customer_id = 0, $relation = 'in') {

		$me = L('ME');
		$customer = O('customer', $customer_id);
		if (!$customer->id || $me->is_allowed_to('以买方查看', $customer)) {
			return;
		}

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

	function user_token_not_in_customer($customer_id = 0) {

		$s = Q::quote(Input::form('s'));

		if ($s) {

			$customer = O('customer', $customer_id);

			// 管理员可将所有不在此 customer 的用户加入 customer
			$users = Q("user[token*={$s}][!lims_user]:not($customer<member user):limit(5)");

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

	function user_not_in_customer($customer_id = 0) {

		$s = Q::quote(Input::form('s'));

		if ($s) {

			$customer = O('customer', $customer_id);

			// 管理员可将所有不在此 customer 的用户加入 customer
			$users = Q("user[name*={$s} | name_abbr^={$s}][!lims_user]:not($customer<member user):limit(5)");

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
