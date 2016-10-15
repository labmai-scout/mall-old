<?php


$config['root']='mysql://genee:83719730@{{{DOCKER0IP}}}/%database';
$config['prefix'] = 'mall_';

$config['@sphinx.url']='mysql://sphinx:9306/%database';

$config['default_engine'] = 'InnoDB';
