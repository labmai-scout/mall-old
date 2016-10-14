<?php

class Number {

	/*
	NO.BUG#099
	2010.11.05
	朱洪杰
	*/
    static function currency($num, $with_sign=TRUE) {
		if ($with_sign) return Config::get('site.currency_sign').number_format(floatval($num), 2);
		return number_format(floatval($num), 2);
	}
	
	static function fill($num, $length=6, $pad_string='0', $pad_type=STR_PAD_LEFT) {
		return str_pad($num, $length, $pad_string, $pad_type);
	}
	
	static function degree($num) {
	
		$degree = floor($num);
		$float = 60 * ($num - $degree);

		$min = floor($float);
		
		$sec = 60 * ($float - $min);
		
		return sprintf("%d° %d' %0.2f\"", $degree , $min, $sec);

	}

}
