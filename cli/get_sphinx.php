<?php
/*
   输出指定 SITE 的 sphinx 配置

   usage: SITE_ID=nankai php get_sphinx.php
 */

require dirname(__FILE__) . '/base.php';

echo '# mall sphinx conf for SITE_ID=' . SITE_ID . "\n";

$sphinx_confs = Config::get('sphinx');

if ($sphinx_confs) foreach ($sphinx_confs as $conf_key => $conf_content) {

    //prefix是用于进行前缀设定
    if ($conf_key == 'prefix' || $conf_key == 'dir') continue;

    if ($conf_content) {
        generate_sphinx_conf($conf_key, $conf_content);
    }
}

echo "\n# -- EOF --\n";


function generate_sphinx_conf($conf_key, $conf_content) {

    echo "# " . $conf_key . "\n";

    $prefix = Config::get('sphinx.prefix');
    $dir = Config::get('sphinx.dir');

    echo strtr(strtr( "index %prefix%index: mall_rt_default\n", array(
                    '%prefix'=> $prefix,
                    '%index' => $conf_key,
                    )), '-', '_');

    echo "{\n";

    echo strtr( "path = /var/lib/sphinxsearch/data/%dir/%index\n", array(
                '%site_id' => SITE_ID,
                '%index' => $conf_key,
                '%dir'=> $dir
                ));

    foreach ((array)$conf_content['options'] as $key => $opts) {
        echo strtr( "%key = %value\n", array(
                    '%key' => $key,
                    '%value' => $opts['value'],
                    ));
    }

    foreach ((array)$conf_content['fields'] as $field => $opts) {
        echo strtr( "%type = %field\n", array(
                    '%type' => $opts['type'],
                    '%field' => $field,
                    ));
    }

    echo "}\n";
}
