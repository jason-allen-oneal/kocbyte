<?php

//init stuff
chdir(__DIR__);
ini_set('max_execution_time', 3600);

define('IN_SITE', true);
$rootPath = $root_path = './../';
$phpEx = 'php';
include($rootPath.'inc/common.php');
include($rootPath.'lib/worker.php');

$user->session_begin();
$user->setup();
//init done

$domain = $_REQUEST['domain'];

new worker($domain, 'map_listener', 'other');
worker::load($_POST['data']);

foreach(worker::$data['data'] as $tiles){
	foreach($tiles as $tile){
		if(isset($tile['tileType'])){
			if($tile['tileType'] == 51){
				if($tile['cityNum'] != null && $tile['cityNum'] != ''){
					$sql = 'SELECT * FROM '.CITIES.' WHERE c_cid = '.(int) $tile['tileCityId'].' AND c_domain = '.(int) worker::$domain;
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
							$sql = 'UPDATE '.CITIES.' SET '.$db->sql_build_array('UPDATE', $sqlArray).' WHERE c_cid = '.(int) $tile['tileCityId'];
							$db->sql_query($sql);
							$sqlArray = array();
						}
					}else{
						$sql = 'SELECT * FROM '.CITIES.' WHERE c_domain = '.(int) worker::$domain.' AND c_tid = '.(int) $tile['tileId'];
						$result = $db->sql_query($sql);
						$city = $db->sql_fetchrow($result);
						
						if($city){
							$sqlArray['p_misted'] = 1;
							$sql = 'UPDATE '.PLAYERS.' SET '.$db->sql_build_array('UPDATE', $sqlArray).' WHERE p_uid = '.(int) $city['c_owner'].' AND p_domain = '.(int) worker::$domain;
							$db->sql_query($sql);
							$sqlArray = array();
						}else{
							$sql = 'SELECT * FROM '.CITIES.' WHERE c_x = '.(int) $tile['xCoord'].' AND c_y = '.(int) $tile['yCoord'].' AND c_domain = '.(int) worker::$domain;
							$result = $db->sql_query($sql);
							$city = $db->sql_fetchrow($result);
							if($city){
								$sqlArray['p_misted'] = 1;
								$sql = 'UPDATE '.PLAYERS.' SET '.$db->sql_build_array('UPDATE', $sqlArray).' WHERE p_uid = '.(int) $city['c_owner'];
								$db->sql_query($sql);
								$sqlArray = array();
							}else{
								$sqlAry = array(
									'c_cid' => (int) $tile['tileCityId'],
									'c_owner' => (int) $tile['tileUserId'],
									'c_name' => $tile['cityName'],
									'c_x' => (int) $tile['xCoord'],
									'c_y' => (int) $tile['yCoord'],
									'c_province' => (int) $tile['tileProvinceId'],
									'c_lvl' => (int) $tile['tileLevel'],
									'c_tid' => (int) $tile['tileId'],
									'c_domain' => worker::$domain
								);
								worker::insert(CITIES, $sqlAry);
								$sqlAry = array();
							}
						}
					}
				}
			}else{
				if($tile['premiumTile']){
					$sql = 'SELECT * FROM '.HQS.' WHERE hq_hid = '.$tile['allianceHq']['hqId'].' AND hq_domain = '.worker::$domain;
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
							$sql = 'UPDATE '.HQS.' SET '.$db->sql_build_array('UPDATE', $sqlAry).' WHERE hq_hid = '.(int) $tile['allianceHq']['hqId'].' AND hq_domain = '.worker::$domain;
							$db->sql_query($sql);
							$sqlAry = array();
						}
					}else{
						$sqlAry = array(
							'hq_hid' => (int) $tile['allianceHq']['hqId'],
							'hq_alli' => (int) $tile['allianceHq']['allianceId'],
							'hq_x' => (int) $tile['xCoord'],
							'hq_y' => (int) $tile['yCoord'],
							'hq_domain' => worker::$domain,
							'hq_level' => (int) $tile['allianceHq']['level']
						);
						
						worker::insert(HQS, $sqlAry);
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
									$sqlAry = (int) $tile['tileCityId'];
								}
								if(($wild['w_uid'] != (int) $tile['tileUserId']) && $tile['tileUserId'] != 0){
									$change = true;
									$sqlAry = (int) $tile['tileUserId'];
								}
								
								if($tile['tileCityId'] != 0 && $tile['tileUserId'] == 0){
									$sql = 'SELECT c_owner FROM '.CITIES.' WHERE c_cid = '.$tile['tileCityId'].' AND c_domain = '.(int) worker::$domain;
									$result = $db->sql_query($sql);
									$uid = $db->sql_fetchfield('c_owner');
									
									$sqlArray['p_misted'] = 1;
									$sql = 'UPDATE '.PLAYERS.' SET '.$db->sql_build_array('UPDATE', $sqlArray).' WHERE p_uid = '.(int) $uid.' AND p_domain = '.(int) worker::$domain;
									$db->sql_query($sql);
									$sqlArray = array();
								}
								
								if($change){
									$sql = 'UPDATE '.WILDS.' SET '.$db->sql_build_array('UPDATE', $sqlAry).' WHERE w_tid = '.(int) $tile['tileId'].' AND w_domain = '.(int) worker::$domain;
									$db->sql_query($sql);
									$sqlAry = array();
								}
							}else{
								$sql = 'SELECT p_alli FROM '.PLAYERS.' WHERE p_domain = '.(int) worker::$domain.' AND p_uid = '.(int) $tile['tileUserId'];
								$result = $db->sql_query($sql);
								$alli = $db->sql_fetchfield('p_alliance');
								
								$sqlAry = array(
									'w_tid' => $tile['tileId'],
									'w_x' => $tile['xCoord'],
									'w_y' => $tile['yCoord'],
									'w_type' => $tile['tileType'],
									'w_level' => $tile['tileLevel'],
									'w_cid' => (isset($cityId)) ? $cityId : 0,
									'w_uid' => $tile['tileUserId'],
									'w_aid' => ($alli) ? $alli : 0,
									'w_domain' => (int) worker::$domain
								);
								
								worker::insert(WILDS, $sqlAry);
								$sqlAry = array();
							}
						}else{
							if($tile['tileLevel'] == 10 && $tile['tileType'] == 40){
								$sql = 'SELECT ore_id FROM '.ORE.' WHERE ore_x = '.(int) $tile['xCoord'].' AND ore_y = '.(int) $tile['yCoord'].' AND ore_domain = '.(int) worker::$domain;
								$result = $db->sql_query($sql);
								$row = $db->sql_fetchrow($result);
								if(!$row){
									$sqlAry = array(
										'ore_x' => (int) $tile['xCoord'],
										'ore_y' => (int) $tile['yCoord'],
										'ore_owned' => ($tile['tileUserId']) ? (int) $tile['tileUserId'] : 0,
										'ore_domain' => (int) worker::$domain
									);
									worker::insert(ORE, $sqlAry);
									$sqlAry = array();
								}else{
									$change = false;
									if($row['ore_owned'] != $tile['tileUserId']){
										$change = true;
										$sqlAry['ore_owned'] = $tile['tileUserId'];
									}
									$sql = 'UPDATE '.ORE.' SET '.$db->sql_build_array('UPDATE', $sqlAry).' WHERE ore_id = '.(int) $row['ore_id'].' AND ore_domain = '.(int) worker::$domain;
									$db->sql_query($sql);
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



echo json_encode(array('res' => 'complete'));