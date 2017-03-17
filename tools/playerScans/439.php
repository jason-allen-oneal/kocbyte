<?php

chdir(__DIR__);
ini_set('max_execution_time', 28800);
ini_set('memory_limit','64M');

define('IN_SITE', true);
$rootPath = $root_path = './../';
$phpEx = 'php';
include($rootPath.'inc/common.php');

include($rootPath.'lib/scan/scanner.php');
include($rootPath.'lib/scan/player.php');
include($rootPath.'lib/scan/city.php');
include($rootPath.'lib/scan/allianceChange.php');
include($rootPath.'lib/scan/nameChange.php');
include($rootPath.'lib/scan.php');

$user->session_begin();
$user->setup();

$start = time();

$domain = 439;
new scanner($domain);
scanner::load();
new scan($domain);

?>