<?php

chdir(__DIR__);
ini_set('max_execution_time', 28800);
ini_set('memory_limit','64M');

define('IN_SITE', true);
$rootPath = $root_path = './../';
$phpEx = 'php';
include($rootPath.'inc/common.php');

include($rootPath.'lib/scan/scanner.php');
include($rootPath.'lib/scan/tile.php');

$user->session_begin();
$user->setup();

$start = time();

$domain = 460;
new scanner($domain);
scanner::load();

echo 'Starting '.$domain.' at '.date('M-d-Y h:i a', time())."\n\n";


foreach(scanner::$provinces as $province){
	$x = $province['center_x'];
	$y = $province['center_y'];
	
	for($xx = 1; $xx < 7; $xx++){
		for($yy = 1; $yy < 7; $yy++){
			$blockList = scanner::generateBlockList($x, $y);
		
			$result = scanner::lookup($blockList);
			if(isset($result['data'])){
				foreach($result['data'] as $block => $tile){
					scanner::process($tile);
				}
			}
			$x = $x + 25;
		}
		$y = $y + 25;
	}
}

echo 'Done at '.date('M-d-Y h:i a', time())."\n";

?>