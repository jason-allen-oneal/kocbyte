<?php

class worker{
	public static $endPoint = '';
	public static $domain = 0;
	public static $uD = array();
	public static $alliances = array();
	public static $players = array();
	private static $log;
	public static $output = '';
	public static $data = array();
	public static $tempType = array();
	public static $tempData = array();
	public static $tempUD = array();
	public static $type = '';
	public static $logFile;
	
	public function __construct($domain, $logFile, $type){
		global $rootPath;
		
		self::$domain = (int) $domain;
		self::$logFile = $logFile;
		self::$endPoint = 'https://www'.$domain.'.kingdomsofcamelot.com/fb/e2/src/ajax/';
		self::$type = $type;
	}
	
	public static function load($data = null, $temp = false, $typeOverride = ''){
		global $db, $rootPath;
		
		if(self::$type == 'data' || self::$type == 'clean' || self::$type == 'deep-scan' || self::$type == 'scouter'){
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
			
			$contents = file_get_contents($rootPath.'data/'.self::$domain.'/'.$jsonFile);

			if($contents === false){
				exit;
			}
		}
		
		if(self::$type != 'data' && self::$type != 'deep-scan' && self::$type != 'clean' && self::$type != 'scouter'){
			if($data != null){
				$contents = $data;
			}else{
				return false;
			}
		}
		
		if($temp){
			self::$tempData = json_decode($contents, true);
			self::$tempUD = self::$tempData['data'];
			self::$tempType = $typeOverride;
		}else{
			self::$data = json_decode($contents, true);
			self::$uD = self::$data['data'];
		}
	}
	
	public static function log($msg){
		echo $msg."\n";
	}
	
	public static function quit(){
		echo 'Done!';
	}
	
	public static function getData($page, $args){
		$ch = curl_init(self::$endPoint.$page.'.php');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
		curl_setopt($ch, CURLOPT_POST, 1);
		$result = curl_exec($ch);
		return json_decode($result, true);
	}
	
	public static function buildPayload($arr){
		$args = array_merge($arr, self::$uD);
		return http_build_query($args);
	}
	
	public static function sendRequest($page, $args){
		$query = self::buildPayload($args);
		return self::getData($page, $query);
	}
	
	public static function insert($table, $data){
		global $db;
		
		$sql = 'INSERT INTO '.$table.' '.$db->sql_build_array('INSERT', $data);
		$db->sql_query($sql);
		return $db->sql_affectedrows();
	}
	
	public static function generateBlockList($left, $top, $width){
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
	
	public static function processDomain(){
		global $db;
		
		$sql = 'SELECT d_id, d_last_update FROM '.DOMAINS.' WHERE d_domain = '.(int) self::$domain;
		$result = $db->sql_query($sql);
		$d = $db->sql_fetchrow($result);
		if(!$d){
			$sqlAry = array(
				'd_domain' => (int) self::$domain,
				'd_last_update' => time(),
				'd_last_update_user' => self::$data['uid'],
			);
			self::insert(DOMAINS, $sqlAry);
			$sqlAry = array();
		}else{
			if($d['d_last_update'] < time() - 21600){
				$sqlAry['d_last_update'] = time();
				$sqlAry['d_last_update_user'] = self::$data['uid'];
				$sql = 'UPDATE '.DOMAINS.' SET '.$db->sql_build_array('UPDATE', $sqlAry).' WHERE d_domain = '.(int) self::$domain;
				$db->sql_query($sql);
				$sqlAry = array();
			}else{
				exit;
			}
		}

		self::log('***** Data sent from '.self::$data['userName'].' of '.self::$data['allianceName'].' on '.(int) self::$domain." ******\n");
		self::log("Starting alliances\n");
		$array = array('cityId' => self::$data['cities'][0][0]);
		$result = self::sendRequest('allianceGetOtherInfo', $array);

		foreach($result['otherAlliances'] as $alli){
			self::$alliances[$alli['allianceId']] = array(
				'name' => $alli['name'],
				'desc' => $alli['description'],
				'members' => $alli['membersCount'],
				'might' => $alli['might'],
				'glory' => $alli['glory'],
				'rank' => $alli['ranking']
			);
			sleep(4);
		}

		for($page = 2; $page < $result['noOfPages']; $page++){
			$pArray = array('cityId' => self::$data['cities'][0][0], 'pageNo' => $page);
			$res = self::sendRequest('allianceGetOtherInfo', $pArray);
			
			foreach($res['otherAlliances'] as $alli){
				self::$alliances[$alli['allianceId']] = array(
					'name' => $alli['name'],
					'desc' => $alli['description'],
					'members' => $alli['membersCount'],
					'might' => $alli['might'],
					'glory' => $alli['glory'],
					'rank' => $alli['ranking']
				);
				sleep(4);
			}
			sleep(4);
		}

		foreach(self::$alliances as $a => $b){
			$sql = 'SELECT * FROM '.ALLIS.' WHERE a_aid = '.(int) $a.' AND a_domain = '.(int) self::$domain;
			$result = $db->sql_query($sql);
			$alli = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);
			if($alli){
				$change = false;
				
				if($alli['a_might'] != $b['might']){
					$change = true;
					$sqlAry['a_might'] = $b['might'];
				}
				
				if($alli['a_glory'] != $b['glory']){
					$change = true;
					$sqlAry['a_glory'] = $b['glory'];
				}
				
				if($alli['a_rank'] != $b['rank']){
					$change = true;
					$sqlAry['a_rank'] = $b['rank'];
				}
				
				if($alli['a_members'] != $b['members']){
					$change = true;
					$sqlAry['a_members'] = $b['members'];
				}
				
				if($change){
					$sql = 'UPDATE '.ALLIS.' SET '.$db->sql_build_array('UPDATE', $sqlAry).' WHERE a_aid = '.$a.' AND a_domain = '.(int) self::$domain;
					$db->sql_query($sql);
					$sqlAry = array();
				}
			}else{
				$sqlAry = array(
					'a_aid' => (int) $a,
					'a_name' => $db->sql_escape($b['name']),
					'a_desc' => $db->sql_escape($b['desc']),
					'a_domain' => (int) self::$domain,
					'a_might' => $b['might'],
					'a_glory' => $b['glory'],
					'a_rank' => (int) $b['rank'],
					'a_members' => (int) $b['members']
				);
				self::insert(ALLIS, $sqlAry);
				$sqlAry = array();
			}
		}

		self::log("Alliances done\n");

		foreach(self::$alliances as $k => $v){
			self::log('******** Starting #'.$v['rank']." ************\n");
			$params['allianceId'] = $k;
			$params['type'] = 'might';
			$params['page'] = 1;
			$params['perPage'] = 100;
			
			$alliData = self::sendRequest('getUserLeaderboard', $params);
			foreach($alliData['results'] as $p){
				if($p['userId'] == 0 || $p['displayName'] == "???"){
					continue;
				}
				
				$player = array(
					'uid' => $p['userId'],
					'name' => $p['displayName'],
					'prefix' => ($p['playerSex'] == 'M') ? 'Lord' : 'Lady',
					'status' => $p['warStatus'],
					'might' => $p['might'],
					'alliance' => $p['allianceId'],
					'type' => $p['officerType'],
					'cities' => $p['cities'],
					'truce' => 0,
					'glory' => 0,
					'lifeGlory' => 0,
					'maxGlory' => 0,
					'lastLogin' => 0,
					'mistExpire' => 0,
					'truceExpire' => 0
				);
				
				$uParams = array(
					'ctrl' => 'PlayerProfile',
					'action' => 'get',
					'userId' => $player['uid']
				);
				$profile = self::sendRequest('_dispatch', $uParams);
				if($profile){
					$player['glory'] = (int) $profile['profile']['glory'];
					$player['lifeGlory'] = (int) $profile['profile']['lifetimeGlory'];
					$player['maxGlory'] = (int) $profile['profile']['maxGlory'];
				}
				$cParams = array('pid' => $player['uid']);
				$genInfo = self::sendRequest('viewCourt', $cParams);
				if(isset($genInfo['playerInfo'])){
					$player['might'] = $genInfo['playerInfo']['might'];
					if($genInfo['playerInfo']['lastLogin'] !== null){
						$player['lastLogin'] = strtotime($genInfo['playerInfo']['lastLogin']);
					}
					
					if($genInfo['playerInfo']['fogExpireTimestamp'] != '0000-00-00 00:00:00'){
						$player['mistExpire'] = strtotime($genInfo['playerInfo']['fogExpireTimestamp']);
					}
					if($genInfo['playerInfo']['truceExpireTimestamp'] != '0000-00-00 00:00:00'){
						$player['truceExpire'] = strtotime($genInfo['playerInfo']['truceExpireTimestamp']);
					}
				}
				
				$sql = 'SELECT * FROM '.PLAYERS.' WHERE p_uid = '.(int) $player['uid'].' AND p_domain = '.(int) self::$data['domain'];
				$result = $db->sql_query($sql);
				$p = $db->sql_fetchrow($result);
				$db->sql_freeresult($result);
				if($p){
					$change = false;
					if($p['p_type'] != $player['type']){
						$change = true;
						$sqlAry['p_type'] = $player['type'];
					}
					if($p['p_last_login'] != $player['lastLogin']){
						$change = true;
						$sqlAry['p_last_login'] = $player['lastLogin'];
					}
					if($p['p_name'] != $player['name']){
						$change = true;
						$sqlAry['p_name'] = $db->sql_escape($player['name']);
						
						$sqlArray = array(
							'nc_uid' => (int) $p['p_uid'],
							'nc_date' => time(),
							'nc_prev' => $p['p_name'],
							'nc_curr' => $player['name'],
							'nc_domain' => (int) self::$domain
						);
						self::insert(N_CHANGE, $sqlArray);
						$sqlArray = array();
					}
					if($p['p_alli'] != $player['alliance']){
						$change = true;
						$sqlAry['p_alli'] = (int) $player['alliance'];
						
						$sqlArray = array(
							'ac_uid' => (int) $p['p_uid'],
							'ac_date' => time(),
							'ac_prev' => (int) $p['p_alli'],
							'ac_curr' => (int) $player['alliance'],
							'ac_domain' => (int) self::$domain
						);
						self::insert(A_CHANGE, $sqlArray);
						$sqlArray = array();
					}
					if($p['p_might'] != $player['might']){
						$sqlAry['p_might'] = (int) round($player['might']);
						$change = true;
					}
					if(isset($player['misted'])){
						if($p['p_misted'] != $player['misted']){
							$change = true;
							$sqlAry['p_misted'] = (int) $player['misted'];
						}
					}
					if(isset($player['mistExpire'])){
						if($p['p_mist_expire'] != $player['mistExpire']){
							$change = true;
							$sqlAry['p_mist_expire'] = $player['mistExpire'];
						}
					}else{
						if($p['p_mist_expire'] != 0){
							$change = true;
							$sqlAry['p_mist_expire'] = 0;
						}
					}
					if(isset($player['truceExpire'])){
						if($p['p_truce_expire'] != $player['truceExpire']){
							$change = true;
							$sqlAry['p_truce_expire'] = $player['truceExpire'];
						}
					}else{
						if($p['p_truce_expire'] != 0){
							$change = true;
							$sqlAry['p_truce_expire'] = 0;
						}
					}
					if(isset($player['glory'])){
						if($p['p_glory'] != $player['glory']){
							$sqlAry['p_glory'] = (int) round($player['glory']);
							$change = true;
						}
					}
					if(isset($player['gloryMax'])){
						if($p['p_glory_max'] != $player['gloryMax']){
							$sqlAry['p_glory_max'] = (int) round($player['maxGlory']);
							$change = true;
						}
					}
					if(isset($player['lifeGlory'])){
						if($p['p_glory_life'] != $player['lifeGlory']){
							$change = true;
							$sqlAry['p_glory_life'] = (int) $player['lifeGlory'];
						}
					}
					
					if($p['p_last_seen'] != time()){
						$change = true;
						$sqlAry['p_last_seen'] = time();
					}
					
					if($change){
						self::log('Updating... '.$p['p_uid']."\n");
						$sql = 'UPDATE '.PLAYERS.' SET '.$db->sql_build_array('UPDATE', $sqlAry).' WHERE p_uid = '.$p['p_uid'].' AND p_domain = '.(int) self::$domain;
						$db->sql_query($sql);
						$sqlAry = array();
					}
				}else{
					self::log('Inserting... '.$player['uid']."\n");
					$sqlAry = array(
						'p_uid' => (int) $player['uid'],
						'p_domain' => (int) self::$domain,
						'p_name' => $db->sql_escape($player['name']),
						'p_prefix' => $player['prefix'],
						'p_might' => (int) round($player['might']),
						'p_alli' => (int) $player['alliance'],
						'p_misted' => 0,
						'p_glory' => (int) (isset($player['glory'])) ? $player['glory'] : 0,
						'p_glory_max' => (int) (isset($player['gloryMax'])) ? $player['gloryMax'] : 0,
						'p_type' => $player['type'],
						'p_last_login' => ($player['lastLogin'] === NULL) ? 0 : $player['lastLogin'],
						'p_last_seen' => time()
					);
					self::insert(PLAYERS, $sqlAry);
					$sqlAry = array();
				}
				
				$sql = 'DELETE FROM '.CITIES.' WHERE c_owner = '.(int) $player['uid'].' AND c_domain = '.(int) self::$domain;
				$db->sql_query($sql);
				self::log('Deleting Player Cities... '.(int) $player['uid']."\n");
				
				foreach($player['cities'] as $city){
					self::log('Inserting city... '.$city['cityId']."\n");
					$sqlAry = array(
						'c_cid' => (int) $city['cityId'],
						'c_owner' => (int) $player['uid'],
						'c_name' => $city['cityName'],
						'c_x' => (int) $city['xCoord'],
						'c_y' => (int) $city['yCoord'],
						'c_province' => (int) $city['tileProvinceId'],
						'c_tid' => (int) $city['tileId'],
						'c_lvl' => (int) $city['tileLevel'],
						'c_domain' => (int) self::$domain
					);
					self::insert(CITIES, $sqlAry);
					$sqlAry = array();
				}
				sleep(4);
			}
		}
	}
	
	public static function scannerProcessTile($tile){
		global $db;
		if(isset($tile['tileType'])){
			if($tile['tileType'] == 51){
				$sql = 'SELECT mist_id AS id FROM '.MISTS.' WHERE mist_x = '.$tile['xCoord'].' AND mist_y = '.$tile['yCoord'].' AND mist_domain = '.(int) self::$domain;
				$result = $db->sql_query($sql);
				$mistId = $db->sql_fetchfield('id');
				if($mistId){
					$sql = 'DELETE FROM '.MISTS.' WHERE mist_id = '.$mistId.' AND mist_domain = '.(int) self::$domain;
					$db->sql_query($sql);
				}
				
				if($tile['cityNum'] != null && $tile['cityNum'] != ''){
					$sql = 'SELECT * FROM '.CITIES.' WHERE c_cid = '.(int) $tile['tileCityId'].' AND c_domain = '.(int) self::$domain;
					$result = $db->sql_query($sql);
					$city = $db->sql_fetchrow($result);
					if($city){
						$change = false;
						if($city['c_x'] != (int) $tile['xCoord']){
							$change = true;
							$sqlArray['c_x'] = (int) $tile['xCoord'];
						}
						if($city['c_y'] != (int) $tile['yCoord']){
							$change = true;
							$sqlArray['c_y'] = (int) $tile['yCoord'];
						}
						if($city['c_province'] != (int) $tile['tileProvinceId']){
							$change = true;
							$sqlArray['c_province'] = (int) $tile['tileProvinceId'];
						}
						if($city['c_name'] != $tile['cityName']){
							$change = true;
							$sqlArray['c_name'] = $tile['cityName'];
						}
						if($city['c_tid'] != (int) $tile['tileId']){
							$change = true;
							$sqlArray['c_tid'] = (int) $tile['tileId'];
						}
						if($city['c_lvl'] != (int) $tile['tileLevel']){
							$change = true;
							$sqlArray['c_lvl'] = (int) $tile['tileLevel'];
						}
						if($change){
							echo 'updating city #'.$tile['tileCityId']."\n";
							$sql = 'UPDATE '.CITIES.' SET '.$db->sql_build_array('UPDATE', $sqlArray).' WHERE c_cid = '.(int) $tile['tileCityId'];
							$db->sql_query($sql);
							$sqlArray = array();
						}
					}else{
						$sql = 'SELECT * FROM '.CITIES.' WHERE c_domain = '.(int) self::$domain.' AND c_tid = '.(int) $tile['tileId'];
						$result = $db->sql_query($sql);
						$city = $db->sql_fetchrow($result);
						
						if($city){
							$sqlArray['p_misted'] = 1;
							$sql = 'UPDATE '.PLAYERS.' SET '.$db->sql_build_array('UPDATE', $sqlArray).' WHERE p_uid = '.(int) $city['c_owner'].' AND p_domain = '.(int) self::$domain;
							$db->sql_query($sql);
							$sqlArray = array();
						}else{
							$sql = 'SELECT * FROM '.CITIES.' WHERE c_x = '.(int) $tile['xCoord'].' AND c_y = '.(int) $tile['yCoord'].' AND c_domain = '.(int) self::$domain;
							$result = $db->sql_query($sql);
							$city = $db->sql_fetchrow($result);
							if($city){
								$sqlArray['p_misted'] = 1;
								$sql = 'UPDATE '.PLAYERS.' SET '.$db->sql_build_array('UPDATE', $sqlArray).' WHERE p_uid = '.(int) $city['c_owner'];
								$db->sql_query($sql);
								$sqlArray = array();
							}else{
								echo 'inserting city #'.$tile['tileCityId']."\n";
								$sqlAry = array(
									'c_cid' => (int) $tile['tileCityId'],
									'c_owner' => (int) $tile['tileUserId'],
									'c_name' => $tile['cityName'],
									'c_x' => (int) $tile['xCoord'],
									'c_y' => (int) $tile['yCoord'],
									'c_province' => (int) $tile['tileProvinceId'],
									'c_lvl' => (int) $tile['tileLevel'],
									'c_tid' => (int) $tile['tileId'],
									'c_domain' => self::$domain
								);
								self::insert(CITIES, $sqlAry);
								$sqlAry = array();
							}
						}
					}
				}
			}elseif((int) $tile['tileType'] == 53){
				$sql = 'SELECT * FROM '.MISTS.' WHERE mist_x = '.(int) $tile['xCoord'].' AND mist_y = '.(int) $tile['yCoord'].' AND mist_domain = '.self::$domain;
				$result = $db->sql_query($sql);
				$row = $db->sql_fetchrow($result);
				if(!$row){
					echo 'inserting mist at '.$tile['xCoord'].','.$tile['yCoord']."\n";
					$sqlAry = array(
						'mist_x' => $tile['xCoord'],
						'mist_y' => $tile['yCoord'],
						'mist_prov' => $tile['tileProvinceId'],
						'mist_domain' => self::$domain
					);
					self::insert(MISTS, $sqlAry);
					$sqlAry = array();
				}
			}else{
				if($tile['tileType'] == 50){
					$sql = 'SELECT mist_id AS id FROM '.MISTS.' WHERE mist_x = '.$tile['xCoord'].' AND mist_y = '.$tile['yCoord'].' AND mist_domain = '.(int) self::$domain;
					$result = $db->sql_query($sql);
					$mistId = $db->sql_fetchfield('id');
					if($mistId){
						$sql = 'DELETE FROM '.MISTS.' WHERE mist_id = '.$mistId.' AND mist_domain = '.(int) self::$domain;
						$db->sql_query($sql);
					}
				}
				
				if(isset($tile['premiumTile'])){
					$sql = 'SELECT * FROM '.HQS.' WHERE hq_hid = '.$tile['allianceHq']['hqId'].' AND hq_domain = '.self::$domain;
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
							echo 'updating HQ: '.$tile['allianceHq']['hqId']."\n";
							$sql = 'UPDATE '.HQS.' SET '.$db->sql_build_array('UPDATE', $sqlAry).' WHERE hq_hid = '.(int) $tile['allianceHq']['hqId'].' AND hq_domain = '.self::$domain;
							$db->sql_query($sql);
							$sqlAry = array();
						}
					}else{
						echo 'inserting HQ: '.$tile['allianceHq']['hqId']."\n";
						$sqlAry = array(
							'hq_hid' => (int) $tile['allianceHq']['hqId'],
							'hq_alli' => (int) $tile['allianceHq']['allianceId'],
							'hq_x' => (int) $tile['xCoord'],
							'hq_y' => (int) $tile['yCoord'],
							'hq_domain' => self::$domain,
							'hq_level' => (int) $tile['allianceHq']['level']
						);
						
						self::insert(HQS, $sqlAry);
						$sqlAry = array();
					}
				}else{
					$sql = 'SELECT * FROM '.T_TYPES.' WHERE tt_id = '.$tile['tileType'];
					$result = $db->sql_query($sql);
					$tileType = $db->sql_fetchrow($result);
					
					if($tileType['tt_type'] == WILD){
						if($tile['tileCityId'] != null && $tile['tileCityId'] != '' && $tile['tileCityId'] != 0){
							$cityId = $tile['tileCityId'];
						}else{
							$cityId = 0;
						}
						
						if($cityId){ // owned wild
							$sql = 'SELECT * FROM '.WILDS.' WHERE w_tid = '.$tile['tileId'];
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
									echo 'updating wild #'.$tile['tileId']."\n";
									$sql = 'UPDATE '.WILDS.' SET '.$db->sql_build_array('UPDATE', $sqlAry).' WHERE w_tid = '.(int) $tile['tileId'].' AND w_domain = '.(int) self::$domain;
									$db->sql_query($sql);
									$sqlAry = array();
								}
							}else{
								echo 'inserting wild #'.$tile['tileId']."\n";
								$sql = 'SELECT p_alli FROM '.PLAYERS.' WHERE p_domain = '.(int) self::$domain.' AND p_uid = '.(int) $tile['tileUserId'];
								$result = $db->sql_query($sql);
								$alli = $db->sql_fetchfield('p_alliance');
								
								$sqlAry = array(
									'w_tid' => $tile['tileId'],
									'w_x' => $tile['xCoord'],
									'w_y' => $tile['yCoord'],
									'w_type' => $tile['tileType'],
									'w_level' => $tile['tileLevel'],
									'w_cid' => $cityId,
									'w_uid' => $tile['tileUserId'],
									'w_aid' => ($alli) ? $alli : 0,
									'w_domain' => (int) self::$domain
								);
								
								self::insert(WILDS, $sqlAry);
								$sqlAry = array();
							}
						}else{
							if((int) $tile['tileLevel'] == 10 && (int) $tile['tileType'] == 40){
								$sql = 'SELECT * FROM '.ORE.' WHERE ore_x = '.(int) $tile['xCoord'].' AND ore_y = '.(int) $tile['yCoord'].' AND ore_domain = '.(int) self::$domain;
								$result = $db->sql_query($sql);
								$row = $db->sql_fetchrow($result);
								if($row){
									$change = false;
									if($row['ore_owned'] != $tile['tileUserId']){
										$change = true;
										$sqlAry['ore_owned'] = $tile['tileUserId'];
									}
									if($change){
										echo 'updating ore #'.$tile['tileId']."\n";
										$sql = 'UPDATE '.ORE.' SET '.$db->sql_build_array('UPDATE', $sqlAry).' WHERE ore_id = '.(int) $row['ore_id'].' AND ore_domain = '.(int) self::$domain;
										$db->sql_query($sql);
										$sqlAry = array();
									}
								}else{
									echo 'inserting ore #'.$tile['tileId']."\n";
									$sqlAry = array(
										'ore_x' => (int) $tile['xCoord'],
										'ore_y' => (int) $tile['yCoord'],
										'ore_owned' => ($tile['tileUserId']) ? (int) $tile['tileUserId'] : 0,
										'ore_domain' => (int) self::$domain
									);
									self::insert(ORE, $sqlAry);
									$sqlAry = array();
								}
							}
						}
					}
				}
			}
		}
	}
}

?>