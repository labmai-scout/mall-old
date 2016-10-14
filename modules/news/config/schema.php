<?php

$config['news'] = array(
    'fields'=>array(
        'title' => array('type'=> 'varchar(50)', 'null'=>FALSE, 'default'=> ''),
        'content'=> array('type'=> 'varchar(500)', 'null'=> FALSE, 'default'=> ''),
        'ctime'=> array('type'=> 'int', 'null'=> FALSE, 'default'=>0),
        'atime'=> array('type'=> 'int', 'null'=> FALSE, 'default'=>0),
        'mtime'=> array('type'=> 'int', 'null'=> FALSE, 'default'=>0)
    ),
    'indexes'=>array(
        'title' => array('fields'=>array('title')),
        'content' => array('fields'=>array('content')),
        'ctime' => array('fields'=>array('ctime')),
        'atime' => array('fields'=>array('atime')),
        'mtime' => array('fields'=>array('mtime'))
    )
);
