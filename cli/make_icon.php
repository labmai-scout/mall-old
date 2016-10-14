#!/usr/bin/env php
<?php

include "base.php";

//e.g. make_icon /path/to/file target_dir

$icon_file = realpath($argv[1]);
$target_dir = realpath($argv[2]);

