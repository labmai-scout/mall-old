<?php
class Product {

	static function product_ACL($e, $user, $action, $object, $options) {
		$e->return_value = TRUE;
		return FALSE;
	}
}
