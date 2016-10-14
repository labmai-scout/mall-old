<?php
/*
输出指定 site 的 crontab(xiaopei.li@2012-06-29)

usage: SITE_ID=cf php get_cron.php -u|--user=www-data
*/

require dirname(__FILE__) . '/base.php';

$shortopts = "u:r::";
$longopts = array(
	'user:root::',
	);


$opts = getopt($shortopts, $longopts);

if (isset($opts['u']) || isset($opts['user'])) {
	$user = $opts['u'] ? : $opts['user'];
}
else {
	die("usage: SITE_ID=cf  php get_cron.php -u|--user=www-data\n");
}

if (isset($opts['r']) || isset($opts['root'])) {
	$root = $opts['r'] ? : $opts['root'];
}
else {
	$root = ROOT_PATH;
}

// 测试读取 user:
// echo $user;
// echo "\n";
// die;

echo "请将一下信息copy到/etc/cron.d/目录下的某个文件\n";
echo "如果是在docker内，请在宿主机执行docker-cron\n";

echo '# lims2 crontabs of SITE_ID=' . SITE_ID;
echo  "\n";

$cron_jobs = Config::get('cron');

$envs = 'Q_ROOT_PATH=' . $root . ' SITE_ID=' . SITE_ID;

if ($cron_jobs) foreach ($cron_jobs as $job) {
	if ($job) {
		echo "# " . $job['title'] . "\n";
		echo $job['cron'] . ' ' . $user . ' ' . $envs .  ' php ' . strtr($job['job'], array(ROOT_PATH => $root)) . "\n";
	}
}

