<?php

chdir(__DIR__);
ini_set('max_execution_time', 3600);

define('IN_SITE', true);
$rootPath = $root_path = './../../';
$phpEx = 'php';
include($rootPath.'inc/common.php');
include($rootPath.'lib/worker.php');

$user->session_begin();
$user->setup();

$domain = 458;

new worker($domain, 'tools_'.$domain, 'data');
worker::load();

worker::processDomain();

worker::quit();
?>