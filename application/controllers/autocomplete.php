<?php

class Autocomplete_Controller extends AJAX_Controller {

	function user() {

		$s = Q::quote(Input::form('s'));

		if ($s) {

			$users = Q("user[atime>0][name*={$s} | name_abbr^={$s}]:limit(5)");

			$count = $users->length();

			if (!$count) {
				Output::$AJAX[] = array(
					'html' => (string)V('application:autocomplete/special/empty'),
					'special' => TRUE
				);
			}
			else {
				foreach ($users as $user) {
					Output::$AJAX[] = array(
						'html' => (string) V('application:autocomplete/user', array('user'=>$user)),
						'alt' => $user->id,
						'text' => $user->name,
					);
				}

				$rest = $users->total_count() - $count;
				if ($rest > 0) {
					Output::$AJAX[] = array(
						'html' => (string) V('application:autocomplete/special/rest', array('rest' => $rest)),
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
			$users = Q("user[atime>0][name*={$s} | name_abbr^={$s}][!lims_user]:not($customer<member user):limit(5)");

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

	function customer_add_owner($customer_id = 0) {

	}

	function customer_add_member($customer_id = 0) {

	}

	function customer_member($customer_id = 0) {

	}

	function vendor() {

		$s = Q::quote(Input::form('s'));

		if ($s) {
			$vendors = Q("vendor[name*={$s} | short_name*={$s}]:limit(5)");

			$count = $vendors->length();

			if (!$count) {
				Output::$AJAX[] = array(
					'html' => (string)V('autocomplete/special/empty'),
					'special' => TRUE
				);
			}
			else {
				foreach ($vendors as $vendor) {
					Output::$AJAX[] = array(
						'html' => (string) V('autocomplete/vendor', array('vendor'=>$vendor)),
						'alt' => $vendor->id,
						'text' => $vendor->name,
					);
				}

				$rest = $vendors->total_count() - $count;
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
