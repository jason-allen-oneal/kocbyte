<?php

chdir(__DIR__);
ini_set('max_execution_time', 3600);

define('IN_SITE', true);
$rootPath = $root_path = './../../';
$phpEx = 'php';
include($rootPath.'inc/common.php');
include($rootPath.'lib/worker.php');
include($rootPath.'lib/scan.php');

$user->session_begin();
$user->setup();

$domain = 460;
new worker($domain, 'deep_scan_'.$domain, 'deep-scan');
worker::load();


foreach(scan::$provinces as $p){
	echo 'Starting '.$p['name']."\n";
	$x = $p['center_x'];
	$y = $p['center_y'];
	
	for($xx = 1; $xx < 4; $xx++){
		for($yy = 1; $yy < 4; $yy++){
			new scan($x, $y);
			$blockList = worker::generateBlockList($x, $y, scan::$maxBlocks);
		
			$result = scan::lookup($blockList);
			if(isset($result['data'])){
				foreach($result['data'] as $block => $tile){
					worker::scannerProcessTile($tile);
				}
			}
			$x = $x + 25;
		}
		$y = $y + 25;
	}
}

?>