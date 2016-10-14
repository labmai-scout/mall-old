<?php

class Date extends _Date {
	static function get_work_days($start_date,$end_date,$is_workday = true){
		if (strtotime($start_date) > strtotime($end_date)) list($start_date, $end_date) = array($end_date, $start_date);
		$start_reduce = $end_add = 0;
		$start_N = date('N',strtotime($start_date));
		$start_reduce = ($start_N == 7) ? 1 : 0;
		$end_N = date('N',strtotime($end_date));
		in_array($end_N,array(6,7)) && $end_add = ($end_N == 7) ? 2 : 1;
		$alldays = abs(strtotime($end_date) - strtotime($start_date))/86400 + 1;
		$weekend_days = floor(($alldays + $start_N - 1 - $end_N) / 7) * 2 - $start_reduce + $end_add;
		
		if ($is_workday){
			$workday_days = $alldays - $weekend_days;
			return $workday_days;
		}
		return $weekend_days;
	} 

    static function format($time=NULL, $format=NULL) {
        if (!$time) $time = time();

        $date = getdate($time);

        if (!$format) $format = Date::default_format();

		$format = T($format);
		if (Config::get('debug.i18n_ipe')) {
			$format = preg_replace('/\{\[.+?\]\}/', '', $format);
		}

        return date($format, $time);
    }

}

