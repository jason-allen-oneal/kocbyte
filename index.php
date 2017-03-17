<?php

define('IN_SITE', true);
$rootPath = $root_path = './';
$phpEx = 'php';
include($rootPath.'inc/common.php');

$user->session_begin();
$user->setup();

include $rootPath.'lib/router.php';
new Router($_SERVER['REQUEST_URI']);
Router::parseRoute();

?>