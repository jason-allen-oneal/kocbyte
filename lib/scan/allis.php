<?php

class allis{
	public static $data = array();
	
	public function __construct(){
		$array = array('cityId' => scanner::$data['cities'][0][0]);
		$result = comms::sendRequest('allianceGetOtherInfo', $array);
		
		foreach($result['otherAlliances'] as $alli){
			self::$data[$alli['allianceId']] = array(
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
			$pArray = array('cityId' => scanner::$data['cities'][0][0], 'pageNo' => $page);
			$res = comms::sendRequest('allianceGetOtherInfo', $pArray);
			
			foreach($res['otherAlliances'] as $alli){
				self::$data[$alli['allianceId']] = array(
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
	}
	
	public static function process(){
		global $db;
		
		foreach(self::$data as $a => $b){
			$sql = 'SELECT * FROM '.ALLIS.' WHERE a_aid = '.(int) $a.' AND a_domain = '.(int) self::$domain;
			$result = $db->sql_query($sql);
			$alli = $db->sql_fetchrow($result);
			if($alli){
				self::update($a, $b, $alli);
			}else{
				self::insert($a, $b);
			}
		}
	}
	
	public static function insert($a, $b){
		global $db;
		
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
		
		$sql = 'INSERT INTO '.ALLIS.' '.$db->sql_build_array('INSERT', $sqlAry);
		$db->sql_query($sql);
	}
	
	public static function update($a, $b, $alli){
		global $db;
		
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
			$sql = 'UPDATE '.ALLIS.' SET '.$db->sql_build_array('UPDATE', $sqlAry).' WHERE a_aid = '.$a.' AND a_domain = '.(int) scanner::$domain;
			$db->sql_query($sql);
			$sqlAry = array();
		}
	}
}
