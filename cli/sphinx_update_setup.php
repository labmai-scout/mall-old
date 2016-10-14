<?php
	// SITE_ID=nankai php sphinx_update_setup.php
	// 进程数建议选择8个
	require('base.php');
	fwrite(STDOUT, '请选择进程数: ');	
	$limit = (int)fgets(STDIN);
	if (!$limit) {
		$limit = 1;
	}
	elseif ($limit > 10) {
		echo '进程数多于10个，不建议执行';
		exit;
	}
	echo '开始时间:'.Date::time();
	$table_name = 'product';
	$db = Database::factory();
	$sphinx = Database::factory('@sphinx');
	$types = Config::get('product.types');
	$index_name = Search_Iterator::get_index_name($table_name);
	$sphinx->query("truncate rtindex $index_name");
	foreach ($types as $type => $foo) {
		$sub_index_name = Search_Iterator::get_index_name($table_name.'_'.$type);
		$sphinx->query("truncate rtindex $sub_index_name");
	}
	$total_count = (int)$db->query('SELECT COUNT(*) FROM product')->value();
	$per_thread_limit = ceil($total_count/$limit);
	$var = $db->query('SELECT MAX(`id`) as max,MIN(`id`) as min FROM `product`')->row();
	$id_start = $var->min - 1;

	for ($thread=0; $thread < $limit; $thread++) { 
		$id_end   = $db->value("SELECT id FROM product WHERE id>$id_start ORDER BY id ASC LIMIT $per_thread_limit,1;");
		if (!$id_end) $id_end = $var->max;
		$cmd = strtr('php ' . ROOT_PATH . 'cli/sphinx_update.php  %start %end > /dev/null 2>&1 &', array(
						 '%start' => $id_start,
						 '%end' => $id_end,
						 ));
		exec($cmd);
		$id_start = $id_end;
	}
?>