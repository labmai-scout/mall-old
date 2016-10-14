<?php

class Order_Item_Rating_Model extends Presentable_Model {

	/*
	  为了系统更灵活, 打分项都写在了配置里 (xiaopei.li@2012-07-18)
	*/
	static function get_rating_subjects() {
		return Config::get('comment.rating_subjects');
	}
	static function get_product_rating_subjects() {
		return Config::get('comment.product_rating_subjects');
	}

	static function get_rating_summary($object, $strategy = 'average') {
		$method = "get_{$strategy}_ratings";

		if (method_exists('Order_Item_Rating_Model', $method)) {

			$subjects = array_keys(self::get_rating_subjects());

			$ratings = array();

			foreach ($subjects as $subject) {
				//打包问题：打包的时候变量会被混淆，
                //而self::$method($object, $sub)这样的调用方式，不会被混淆，导致找不到变量$method。
                $ratings[$subject] = call_user_func(array(self, $method), $object, $subject);
			}

			return $ratings;
		}
		else {
			throw new Exception("$strategy not exists");
		}

	}

	static function get_average_ratings($object, $subject) {

		switch($object->name()) {
		case 'vendor':
			$sql = "SELECT avg( t7.rating ) as 'value', count( * ) as 'count' FROM `order_item_rating` `t7` INNER JOIN ( `order_item_comment` `t5` , `order_item` `t3` , `order` `t1` , `vendor` `t0` ) ON ( t0.id = '%id' AND `t7`.`subject` = '%subject' AND `t1`.`vendor_id` = `t0`.`id` AND `t3`.`order_id` = `t1`.`id` AND `t5`.`order_item_id` = `t3`.`id` AND `t7`.`order_item_comment_id` = `t5`.`id` )";
			$sql = strtr($sql, array(
							 '%id' => $object->id,
							 '%subject' => $subject,
							 ));
			break;
		case 'product':
			$id = $object->id;
			$sql = "SELECT avg( t5.rating ) as 'value', count( * ) as 'count' FROM `order_item_rating` `t5` INNER JOIN (`order_item_comment` `t3`, `order_item` `t1`, `product` `t0`) ON (`t0`.`id`='%id' AND `t1`.`product_id`=`t0`.`id` AND `t3`.`order_item_id`=`t1`.`id` AND `t5`.`subject`='%subject' AND `t5`.`order_item_comment_id`=`t3`.`id`)";
			$sql = strtr($sql, array(
							 '%id' => $id,
							 '%subject' => $subject,
							 ));
			break;
		default:
			return FALSE;
		}

		/*
		$sql = strtr($sql, array(
						'%id' => $object->id,
						'%subject' => $subject,
					));
		*/

		$db = Database::factory();

		if ($db->table_exists('order_item') &&
			$db->table_exists('order_item_comment') &&
			$db->table_exists('order_item_rating')) {

			$ret = $db->query($sql)->row();
			return array(
				'value' => $ret->value,
				'count' => $ret->count,
				);

		}
	}
}
