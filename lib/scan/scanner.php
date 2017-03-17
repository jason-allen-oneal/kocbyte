<?php

include($rootPath.'lib/scan/comms.php');

class scanner{
	public static $domain = 0;
	public static $maxDistance = 25;
	public static $maxBlocks = 20;
	public static $data = array();
	public static $uD = array();
	public static $processedPlayers = array();
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
	
	public function __construct($domain){
		self::$domain = (int) $domain;
		comms::$endPoint = 'https://www'.self::$domain.'.kingdomsofcamelot.com/fb/e2/src/ajax/';
	}
	
	public static function load(){
		global $db, $rootPath;
		
		$sql = 'SELECT d_last_update AS last_update, d_last_update_user AS last_user FROM '.DOMAINS.' WHERE d_domain = '.self::$domain;
		$result = $db->sql_query($sql);
		$dom = $db->sql_fetchrow($result);
			
		$files = array();
		if($handle = opendir($rootPath.'data/'.self::$domain)){
			while(false !== ($entry = readdir($handle))){
				if($entry != "." && $entry != ".." && $entry != $dom['last_user'].'.json'){
					$files[] = $entry;
				}
			}
			closedir($handle);
		}
		
		if(!count($files)){
			if(($dom['last_update'] < time() - 21600)){
				$jsonFile = $dom['last_user'].'.json';
			}
		}else{
			$fileKey = array_rand($files);
			$jsonFile = $files[$fileKey];
		}
		list($newUserId, $ext) = explode('.', $jsonFile);
		
		$contents = file_get_contents($rootPath.'data/'.self::$domain.'/'.$jsonFile);
		
		self::$data = json_decode($contents, true);
		self::$uD = self::$data['data'];
		
		$sql = 'UPDATE '.DOMAINS.' SET d_last_update = '.time().', d_last_update_user = '.$newUserId.' WHERE d_domain = '.self::$domain;
		$db->sql_query($sql);
	}
	
	public static function generateBlockList($left, $top){
		$grids = 3;
		$blocks = [];
		for($x = 0; $x < $grids; $x++){
			$xx = $left + ($x*5);
			if($xx > 745){
				$xx = $xx - 750;
			}
			for($y = 0; $y < $grids; $y++){
				$yy = $top + ($y*5);
				if($yy > 745){
					$yy = $yy - 750;
				}
				$blocks[] = 'bl_'.$xx.'_bt_'.$yy;
			}
		}
		return implode(",", $blocks);
	}
	
	public static function lookup($blocks){
		$params = array('blocks' => $blocks);
		return comms::sendRequest('fetchMapTiles', $params);
	}
	
	public static function process($tile){
		global $db;
		
		new tile($tile);
		
		if(tile::$type == 51){
			tile::checkMistsAndRemove();
		}elseif(tile::$type == 53){
			// found a mist. process it if it's not already in the DB.
			if(!tile::checkMist()){
				tile::insertMist();
			}
			
			//check if a city used to be in this place, if so, and the mist is here, it means the player is under mist.
			$sql = 'SELECT c_owner FROM '.CITIES.' WHERE c_x = '.tile::$x.' AND c_y = '.tile::$y.' AND c_domain = '.scanner::$domain;
			$result = $db->sql_query($sql);
			$row = $db->sql_fetchrow($result);
			if($row){
				$sql = 'UPDATE '.PLAYERS.' SET p_misted = 1 WHERE p_uid = '.$row['c_owner'].' AND p_domain = '.scanner::$domain;
				$db->sql_query($sql);
			}
		}elseif(tile::$type == 40 && tile::$lvl){
			// found a lvl 10 mountain
			$sql = 'SELECT * FROM '.ORE.' WHERE ore_x = '.tile::$x.' AND ore_y = '.tile::$y.' AND ore_domain = '.scanner::$domain;
			$result = $db->sql_query($sql);
			$row = $db->sql_fetchrow($result);
			if($row){
				$change = false;
				if($row['ore_owned'] != tile::$uid){
					$change = true;
					if(tile::$uid == null){
						$sqlAry['ore_owned'] = 0;
					}else{
						$sqlAry['ore_owned'] = tile::$uid;
					}
				}
				if($change){
					$sql = 'UPDATE '.ORE.' SET '.$db->sql_build_array('UPDATE', $sqlAry).' WHERE ore_id = '.(int) $row['ore_id'].' AND ore_domain = '.scanner::$domain;
					$db->sql_query($sql);
					$sqlAry = array();
				}
			}else{
				$sqlAry = array(
					'ore_x' => tile::$x,
					'ore_y' => tile::$y,
					'ore_owned' => ($tile['tileUserId']) ? (int) $tile['tileUserId'] : 0,
					'ore_domain' => scanner::$domain
				);
				$sql = 'INSERT INTO '.ORE.' '.$db->sql_build_array('INSERT', $sqlAry);
				$db->sql_query($sql);
			}
		}elseif($tile['premiumTile']){
			$sql = 'SELECT * FROM '.HQS.' WHERE hq_hid = '.$tile['allianceHq']['hqId'].' AND hq_domain = '.scanner::$domain;
			$result = $db->sql_query($sql);
			$row = $db->sql_fetchrow($result);
			if($row){
				$change = false;
				
				if($row['hq_x'] != (int) $tile['xCoord']){
					$change = true;
					$sqlAry['hq_x'] = (int) $tile['xCoord'];
				}
				
				if($row['hq_y'] != (int) $tile['yCoord']){
					$change = true;
					$sqlAry['hq_y'] = (int) $tile['yCoord'];
				}
				
				if($row['hq_level'] != (int) $tile['allianceHq']['level']){
					$change = true;
					$sqlAry['hq_level'] = (int) $tile['allianceHq']['level'];
				}
				
				if($change){
					$sql = 'UPDATE '.HQS.' SET '.$db->sql_build_array('UPDATE', $sqlAry).' WHERE hq_hid = '.(int) $tile['allianceHq']['hqId'].' AND hq_domain = '.scanner::$domain;
					$db->sql_query($sql);
					$sqlAry = array();
				}
			}else{
				$sqlAry = array(
					'hq_hid' => (int) $tile['allianceHq']['hqId'],
					'hq_alli' => (int) $tile['allianceHq']['allianceId'],
					'hq_x' => tile::$x,
					'hq_y' => tile::$y,
					'hq_domain' => scanner::$domain,
					'hq_level' => (int) $tile['allianceHq']['level']
				);
				$sql = 'INSERT INTO '.HQS.' '.$db->sql_build_array('INSERT', $sqlAry);
				$db->sql_query($sql);
			}
		}elseif(tile::$type == 50){
			// found a plain. check the database to see if a city of a misted player was here
			tile::checkMistsAndRemove();
		}else{
			$sql = 'SELECT * FROM '.T_TYPES.' WHERE tt_id = '.tile::$type;
			$result = $db->sql_query($sql);
			$tileType = $db->sql_fetchrow($result);
			
			if($tileType['tt_type'] == WILD){
				if($tile['tileCityId'] != null && $tile['tileCityId'] != '' && $tile['tileCityId'] != 0){
					$cityId = $tile['tileCityId'];
				}else{
					$cityId = 0;
				}
				
				if($cityId){ // owned wild
					$sql = 'SELECT * FROM '.WILDS.' WHERE w_tid = '.tile::$id;
					$result = $db->sql_query($sql);
					$wild = $db->sql_fetchrow($result);
					
					if($wild){
						$change = false;
						
						if($wild['w_cid'] != (int) $tile['tileCityId']){
							$change = true;
							$sqlAry['w_cid'] = (int) $tile['tileCityId'];
						}
						if(($wild['w_uid'] != (int) $tile['tileUserId']) && $tile['tileUserId'] != 0){
							$change = true;
							$sqlAry['w_uid'] = (int) $tile['tileUserId'];
						}
						
						if($change){
							$sql = 'UPDATE '.WILDS.' SET '.$db->sql_build_array('UPDATE', $sqlAry).' WHERE w_tid = '.tile::$id.' AND w_domain = '.scanner::$domain;
							$db->sql_query($sql);
							$sqlAry = array();
						}
					}else{
						$sql = 'SELECT p_alli FROM '.PLAYERS.' WHERE p_domain = '.scanner::$domain.' AND p_uid = '.(int) $tile['tileUserId'];
						$result = $db->sql_query($sql);
						$alli = $db->sql_fetchfield('p_alliance');
						
						$sqlAry = array(
							'w_tid' => tile::$id,
							'w_x' => tile::$x,
							'w_y' => tile::$y,
							'w_type' => tile::$type,
							'w_level' => tile::$lvl,
							'w_cid' => $cityId,
							'w_uid' => $tile['tileUserId'],
							'w_aid' => ($alli) ? $alli : 0,
							'w_domain' => scanner::$domain
						);
						
						$sql = 'INSERT INTO '.WILDS.' '.$db->sql_build_array('INSERT', $sqlAry);
						$db->sql_query($sql);
					}
				}
			}
		}
	}
}
