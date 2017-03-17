<?php

chdir(__DIR__);

define('IN_SITE', true);
$rootPath = $root_path = './../';
$phpEx = 'php';
include($rootPath.'inc/common.php');
include($rootPath.'lib/worker.php');

$user->session_begin();
$user->setup();

$data = json_decode($_POST['data'], true);
$domain = $data['domain'];
$uid = $data['uid'];
$alliId = $data['allianceId'];
$userName = $data['userName'];

new worker($domain, 'listener', 'other');
worker::load($_POST['data']);

$sql = 'SELECT * FROM '.S_USERS.' WHERE su_uid = '.(int) $uid.' AND su_domain = '.(int) $domain;
$result = $db->sql_query($sql);
$su = $db->sql_fetchrow($result);
if($su){
	if($su['su_name'] != $userName){
		$sql_ary['su_name'] = $userName;
	}
	
	$sql_ary['su_last_sent'] = time();
	
	$sql = 'UPDATE '.S_USERS.' SET '.$db->sql_build_array('UPDATE', $sql_ary).' WHERE su_uid = '.(int) $uid.' AND su_domain = '.(int) worker::$domain;
	$db->sql_query($sql);
	$sql_ary = array();
}else{
	$sqlAry = array(
		'su_uid' => (int) $uid,
		'su_domain' => (int) worker::$domain,
		'su_alliance' => (int) $alliId,
		'su_name' => $userName,
		'su_last_sent' => time()
	);
	worker::insert(S_USERS, $sqlAry);
	$sqlAry = array();
}

if(!file_exists($rootPath.'data/'.$domain)){
	mkdir($rootPath.'data/'.$domain, 0777, true);
}

$res = file_put_contents($rootPath.'data/'.$domain.'/'.$uid.'.json', json_encode($data));

$sql = 'SELECT * FROM '.PLAYERS.' WHERE p_uid = '.(int) $uid.' AND p_domain = '.(int) $domain;
$result = $db->sql_query($sql);
$row = $db->sql_fetchrow($result);
if(!$row){
	$player = array(
		'uid' => $uid,
		'name' => $data['userName'],
		'prefix' => '',
		'status' => 0,
		'might' => 0,
		'alliance' => (isset($data['allianceId'])) ? $data['allianceId'] : 0,
		'type' => 0,
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
		'userId' => $uid
	);
	$profile = worker::sendRequest('_dispatch', $uParams);
	if($profile){
		print_r($profile);
		$player['glory'] = (int) $profile['profile']['glory'];
		$player['lifeGlory'] = (int) $profile['profile']['lifetimeGlory'];
		$player['maxGlory'] = (int) $profile['profile']['maxGlory'];
	}
	$cParams = array('pid' => $uid);
	$genInfo = worker::sendRequest('viewCourt', $cParams);
	if(isset($genInfo['playerInfo'])){
		$player['prefix'] = ($genInfo['playerInfo']['playerSex'] == "M") ? 'Lord' : 'Lady';
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
	
	
	$sqlAry = array(
		'p_uid' => (int) $uid,
		'p_domain' => (int) worker::$domain,
		'p_name' => $db->sql_escape($player['name']),
		'p_prefix' => $player['prefix'],
		'p_might' => (int) round($player['might']),
		'p_alli' => (int) $player['alliance'],
		'p_misted' => 0,
		'p_glory' => (int) (isset($player['glory'])) ? $player['glory'] : 0,
		'p_glory_max' => (int) (isset($player['gloryMax'])) ? $player['gloryMax'] : 0,
		'p_type' => $player['type'],
		'p_last_login' => ($player['lastLogin'] === NULL) ? 0 : $player['lastLogin']
	);
	worker::insert(PLAYERS, $sqlAry);
}

foreach($data['cities'] as $city){
	$sql = 'SELECT * FROM '.CITIES.' WHERE c_cid = '.(int) $city[0].' AND c_domain = '.(int) worker::$domain;
	$result = $db->sql_query($sql);
	$c = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);
	if($c){
		$change = false;
		if($c['c_name'] != $city[1]){
			$sqlAry['c_name'] = $city[1];
			$change = true;
		}
		
		if($c['c_owner'] != $uid){
			$sqlAry['c_owner'] = (int) $uid;
			$change = true;
		}
		
		if($c['c_x'] != $city[2]){
			$sqlAry['c_x'] = (int) $city[2];
			$change = true;
		}
		
		if($c['c_y'] != $city[3]){
			$sqlAry['c_y'] = (int) $city[3];
			$change = true;
		}
		
		if($c['c_province'] != $city[4]){
			$sqlAry['c_province'] = (int) $city[4];
			$change = true;
		}
		
		if($c['c_tid'] != $city[5]){
			$sqlAry['c_tid'] = (int) $city[5];
			$change = true;
		}
		
		if($change){
			$sql = 'UPDATE '.CITIES.' SET '.$db->sql_build_array('UPDATE', $sqlAry).' WHERE c_cid = '.(int) $c['c_cid'].' AND c_domain = '.(int) worker::$domain;
			$db->sql_query($sql);
			$sqlAry = array();
		}
	}else{
		$sqlAry = array(
			'c_cid' => (int) $city[0],
			'c_owner' => (int) $uid,
			'c_name' => $city[1],
			'c_x' => (int) $city[2],
			'c_y' => (int) $city[3],
			'c_province' => (int) $city[4],
			'c_tid' => (int) $city[5],
			'c_lvl' => 0,
			'c_domain' => (int) worker::$domain
		);
		worker::insert(CITIES, $sqlAry);
		$sqlAry = array();
	}
}

if($res !== false){
	$msg = 'Info File placed.';
}else{
	$msg = 'Failed placing file!';
}
echo json_encode(array('res' => $msg));

?>