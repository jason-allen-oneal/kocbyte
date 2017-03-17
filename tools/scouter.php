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

$domain = $_REQUEST['domain'];

new worker($domain, 'tools_'.$domain, 'scan');
worker::load($_REQUEST['data']);

$mists = array();
$sql = 'SELECT * FROM '.MISTS.' WHERE mist_domain = '.worker::$domain;
$result = $db->sql_query($sql);
while($row = $db->sql_fetchrow($result)){
	$mists[] = array(
		'cid' => worker::$data['cities'][0][0],
		'prov' => $row['mist_prov'],
		'x' => $row['mist_x'],
		'y' => $row['mist_y']
	);
}

foreach($mists as $mist){
	$marchId = 0;
	$marchParams = array('cid' => $mist['cid'], 'gold' => 0, 'kid' => 0, 'r1' => 0, 'r2' => 0, 'r3' => 0, 'r4' => 0, 'r5' => 0, 'type' => 3, 'u3' => 1, 'xcoord' => $mist['x'], 'ycoord' => $mist['y']);
	$marchResult = worker::sendRequest('march', $marchParams);
	if(isset($marchResult['marchId'])){
		$marchId = $marchResult['marchId'];
	}
	
	if($marchId){
		$fetchParams = array('rid' => $marchId);
		$fetchResult = worker::sendRequest('fetchMarch', $fetchParams);
		
		$cancelParams = array('cid' => $mist['cid'], 'mid' => $marchId);
		$cancelResult = worker::sendRequest('cancelMarch', $cancelParams);
		
		$target = array(
			'id' => $fetchResult['march']['toPlayerId'],
			'city' => $fetchResult['march']['toCityId'],
			'lvl' => $fetchResult['march']['toTileLevel'],
			'alli' => $fetchResult['march']['toAllianceId'],
			'tid' => $fetchResult['march']['toTileId']
		);
		
		if($target['id']){
			$giParams = array('uid' => $target['id']);
			$gi = worker::sendRequest('getUserGeneralInfo', $giParams);
			$info = $gi['userInfo'];
			
			$player['name'] = $info[0]['name'];
			
			$uParams = array(
				'ctrl' => 'PlayerProfile',
				'action' => 'get',
				'userId' => $target['id']
			);
			$profile = worker::sendRequest('_dispatch', $uParams);
			if($profile){
				if($player['name'] == '' || $player['name'] == null){
					$player['name'] = $profile['profile']['displayName'];
				}
				$player['prefix'] = ($profile['profile']['playerSex'] == "M") ? 'Lord' : 'Lady';
				$player['glory'] = (int) $profile['profile']['glory'];
				$player['lifeGlory'] = (int) $profile['profile']['lifetimeGlory'];
				$player['maxGlory'] = (int) $profile['profile']['maxGlory'];
			}
			
			$cParams = array('pid' => $target['id']);
			$genInfo = worker::sendRequest('viewCourt', $cParams);
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

			$sql = 'SELECT * FROM '.PLAYERS.' WHERE p_uid = '.$target['id'].' AND p_domain = '.worker::$domain;
			$result = $db->sql_query($sql);
			$p = $db->sql_fetchrow($result);
			if($p){
				$change = false;
				if($p['p_name'] != $player['name']){
					$change = true;
					$sqlAry['p_name'] = $db->sql_escape($player['name']);
					
					$sqlArray = array(
						'nc_uid' => (int) $p['p_uid'],
						'nc_date' => time(),
						'nc_prev' => $p['p_name'],
						'nc_curr' => $player['name'],
						'nc_domain' => (int) worker::$domain
					);
					worker::insert(N_CHANGE, $sqlArray);
					$sqlArray = array();
				}
				
				if($p['p_alli'] != $info[0]['allianceId']){
					$change = true;
					$sqlAry['p_alli'] = (int) $target['alli'];
					
					$sqlArray = array(
						'ac_uid' => (int) $p['p_uid'],
						'ac_date' => time(),
						'ac_prev' => (int) $p['p_alli'],
						'ac_curr' => (int) $target['alli'],
						'ac_domain' => (int) worker::$domain
					);
					worker::insert(A_CHANGE, $sqlArray);
					$sqlArray = array();
				}
				
				if($p['p_might'] != $info[0]['might']){
					$sqlAry['p_might'] = (int) round($info[0]['might']);
					$change = true;
				}
				
				if($p['p_misted'] != 1){
					$change = true;
					$sqlAry['p_misted'] = 1;
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
				
				if($change){
					worker::log('Updating... '.$p['p_uid']."\n");
					$sql = 'UPDATE '.PLAYERS.' SET '.$db->sql_build_array('UPDATE', $sqlAry).' WHERE p_uid = '.$p['p_uid'].' AND p_domain = '.(int) worker::$domain;
					$db->sql_query($sql);
					$sqlAry = array();
				}
			}else{
				worker::log('Inserting... '.$target['id']."\n");
				$sqlAry = array(
					'p_uid' => (int) $target['id'],
					'p_domain' => (int) worker::$domain,
					'p_name' => $db->sql_escape($player['name']),
					'p_prefix' => $player['prefix'],
					'p_might' => (int) round($player['might']),
					'p_alli' => (int) $target['alli'],
					'p_misted' => 1,
					'p_glory' => (int) (isset($player['glory'])) ? $player['glory'] : 0,
					'p_glory_max' => (int) (isset($player['gloryMax'])) ? $player['gloryMax'] : 0,
					'p_type' => 4,
					'p_last_login' => ($player['lastLogin'] === NULL) ? 0 : $player['lastLogin']
				);
				worker::insert(PLAYERS, $sqlAry);
				$sqlAry = array();
			}
			
			$sql = 'SELECT * FROM '.CITIES.' WHERE c_cid = '.(int) $target['city'].' AND c_domain = '.(int) worker::$domain;
			$result = $db->sql_query($sql);
			$c = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);
			if($c){
				$change = false;
				if($c['c_x'] != $mist['x']){
					$sqlAry['c_x'] = (int) $mist['x'];
					$change = true;
				}
				
				if($c['c_y'] != $mist['y']){
					$sqlAry['c_y'] = (int) $mist['y'];
					$change = true;
				}
				
				if($c['c_province'] != $mist['prov']){
					$sqlAry['c_province'] = (int) $mist['prov'];
					$change = true;
				}
				
				if($c['c_tid'] != $target['tid']){
					$sqlAry['c_tid'] = (int) $target['tid'];
					$change = true;
				}
				
				if($c['c_lvl'] != (int) $target['lvl']){
					$change = true;
					$sqlAry['c_lvl'] = (int) $target['lvl'];
				}
				
				if($change){
					worker::log('Updating city... '.$target['city']."\n");
					$sql = 'UPDATE '.CITIES.' SET '.$db->sql_build_array('UPDATE', $sqlAry).' WHERE c_cid = '.(int) $c['c_cid'].' AND c_domain = '.(int) worker::$domain;
					$db->sql_query($sql);
					$sqlAry = array();
				}
			}else{
				worker::log('Inserting city... '.$target['city']."\n");
				$sqlAry = array(
					'c_cid' => (int) $target['city'],
					'c_owner' => (int) $target['id'],
					'c_name' => '',
					'c_x' => (int) $mist['x'],
					'c_y' => (int) $mist['y'],
					'c_province' => (int) $mist['prov'],
					'c_tid' => (int) $target['tid'],
					'c_lvl' => (int) $target['lvl'],
					'c_domain' => (int) worker::$domain
				);
				worker::insert(CITIES, $sqlAry);
				$sqlAry = array();
			}
		}
	}
}

echo 'done';

?>