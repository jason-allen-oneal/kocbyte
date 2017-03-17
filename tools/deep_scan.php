<?php

chdir(__DIR__);
ini_set('max_execution_time', 3600);

define('IN_SITE', true);
$rootPath = $root_path = './../';
$phpEx = 'php';
include($rootPath.'inc/common.php');
include($rootPath.'lib/worker.php');

$user->session_begin();
$user->setup();

class scan {
	public static $startX = 0;
	public static $startY = 0;
	public static $maxDistance = 25;
	public static $maxBlocks = 20;
	public static $provinces = array(
		1 => array(
			'name' => 'Tintagel',
			'center_x' => 0,
			'center_y' => 0
		),
		2 => array(
			'name' => 'Cornwall',
			'center_x' => 150,
			'center_y' => 0
		),
		3 => array(
			'name' => 'Astolat',
			'center_x' => 300,
			'center_y' => 0
		),
		4 => array(
			'name' => 'Lyonesse',
			'center_x' => 450,
			'center_y' => 0
		),
		5 => array(
			'name' => 'Corbenic',
			'center_x' => 600,
			'center_y' => 0
		),
		6 => array(
			'name' => 'Paimpont',
			'center_x' => 0,
			'center_y' => 150
		),
		7 => array(
			'name' => 'Cameliard',
			'center_x' => 150,
			'center_y' => 150
		),
		8 => array(
			'name' => 'Sarras',
			'center_x' => 300,
			'center_y' => 150
		),
		9 => array(
			'name' => 'Canoel',
			'center_x' => 450,
			'center_y' => 150
		),
		10 => array(
			'name' => 'Avalon',
			'center_x' => 600,
			'center_y' => 150
		),
		11 => array(
			'name' => 'Carmathen',
			'center_x' => 0,
			'center_y' => 300
		),
		12 => array(
			'name' => 'Shallot',
			'center_x' => 150,
			'center_y' => 300
		),
		13 => array(
			'name' => 'Cadbury',
			'center_x' => 450,
			'center_y' => 300
		),
		14 => array(
			'name' => 'Glastonbury',
			'center_x' => 600,
			'center_y' => 300
		),
		15 => array(
			'name' => 'Camlann',
			'center_x' => 50,
			'center_y' => 450
		),
		16 => array(
			'name' => 'Orkney',
			'center_x' => 150,
			'center_y' => 450
		),
		17 => array(
			'name' => 'Dore',
			'center_x' => 300,
			'center_y' => 450
		),
		18 => array(
			'name' => 'Logres',
			'center_x' => 450,
			'center_y' => 450
		),
		19 => array(
			'name' => 'Caerleon',
			'center_x' => 600,
			'center_y' => 450
		),
		20 => array(
			'name' => 'Parmenie',
			'center_x' => 50,
			'center_y' => 600
		),
		21 => array(
			'name' => 'Bodmin Moor',
			'center_x' => 150,
			'center_y' => 600
		),
		22 => array(
			'name' => 'Cellwig',
			'center_x' => 300,
			'center_y' => 600
		),
		23 => array(
			'name' => 'Listeneise',
			'center_x' => 450,
			'center_y' => 600
		),
		24 => array(
			'name' => 'Albion',
			'center_x' => 600,
			'center_y' => 600
		),
	);
	
	public function __construct($x, $y){
		self::$startX = $x;
		self::$startY = $y;
	}
	
	public static function lookup($blocks){
		$params = array('blocks' => $blocks);
		return worker::sendRequest('fetchMapTiles', $params);
	}
}

$sql = 'SELECT d_domain FROM '.DOMAINS;
$result = $db->sql_query($sql);
while($d = $db->sql_fetchrow($result)){
	$domain = $d['d_domain'];
	echo 'Loading '.$domain."\n\n";
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
}

echo 'Done!';

?>