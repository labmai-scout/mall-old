<?php

$config['default_backend'] = 'database';

$config['backends']['database'] = array(
	'handler' => 'database',
	'database.table' => '_auth',
	'title' => '本地用户',
);

$config['vendor_backend'] = 'database';