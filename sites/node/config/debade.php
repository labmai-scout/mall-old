<?php

$config['queues'] = [
    'order' => [
        'addr' => 'tcp://172.17.0.1:3333',
        'queue' => 'mall',
    ],
    'transfer_statement' => [
        'addr' => 'tcp://172.17.0.1:3333',
    	'queue' => 'mall',
    ],
];
