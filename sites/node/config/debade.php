<?php

$config['queues'] = [
    'order' => [
        'addr' => 'tcp://{{{DOCKER0IP}}}:3333',
        'queue' => 'mall',
    ],
    'transfer_statement' => [
        'addr' => 'tcp://{{{DOCKER0IP}}}:3333',
    	'queue' => 'mall',
    ],
];
