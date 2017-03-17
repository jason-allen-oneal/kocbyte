<?php

class player{
	public $id = 0;
	public $name = '';
	public $prefix = '';
	public $aId = 0;
	public $type = 4;
	public $cityCt = 0;
	public $might = 0;
	public $lastLogin = 0;
	public $truced = false;
	public $truceExpires = 0;
	public $mistExpires = 0;
	public $glory = 0;
	public $gloryLife = 0;
	public $gloryMax = 0;
	public $lastUpdated = 0;
	public $fbid = 0;
	public $cities = array();
	
	public function __construct($data){
		$this->id = (int) $data['userId'];
		$this->name = $data['displayName'];
		$this->prefix = ($data['playerSex'] == "M") ? 'Lord' : 'Lady';
		$this->aId = ((int) $data['allianceId']) ? (int) $data['allianceId'] : 0;
		$this->type = (int) $data['officerType'];
		$this->cityCt = (int) $data['numCities'];
		
		$cParams = array('pid' => $this->id);
		$genInfo = comms::sendRequest('viewCourt', $cParams);
		if(isset($genInfo['playerInfo'])){
			$this->might = $genInfo['playerInfo']['might'];
			if($genInfo['playerInfo']['lastLogin'] !== null){
				$this->lastLogin = strtotime($genInfo['playerInfo']['lastLogin']);
			}
			if($genInfo['playerInfo']['warStatus']){
				$this->truced = false;
			}else{
				$this->truced = true;
			}
			$this->mistExpires = $genInfo['playerInfo']['fogExpireTimestamp'];
			$this->truceExpires = $genInfo['playerInfo']['truceExpireTimestamp'];
			$this->fbid = $genInfo['playerInfo']['fbuid'];
		}
		
		$uParams = array(
			'ctrl' => 'PlayerProfile',
			'action' => 'get',
			'userId' => $this->id
		);
		$profile = comms::sendRequest('_dispatch', $uParams);
		if($profile['ok']){
			$this->glory = (int) $profile['profile']['glory'];
			$this->gloryLife = (int) $profile['profile']['lifetimeGlory'];
			$this->gloryMax = (int) $profile['profile']['maxGlory'];
		}
		
		$fParams = array(
			'userId' => $this->id,
			'type' => "might",
			'page' => 1,
		);
		$fetched = comms::sendRequest('getUserLeaderboard', $fParams);
		$cities = $fetched['results'][0]['cities'];
		foreach($cities as $city){
			$this->cities[] = array(
				'owner' => (int) $this->id,
				'id' => (int) $city['cityId'],
				'name' => $city['cityName'],
				'x' => (int) $city['xCoord'],
				'y' => (int) $city['yCoord'],
				'tid' => (int) $city['tileId'],
				'province' => $city['tileProvinceId'],
				'lvl' => $city['tileLevel'],
			);
		}
	}
	
	public function process(){
		if($this->checkIfExists()){
			$this->update();
			$cities = new cities($this->cities);
			$cities->process();
		}else{
			$this->insert();
		}
		echo $this->id.' - Done!<br />';
		return;
	}
	
	public function checkIfExists(){
		global $db;
		
		$sql = 'SELECT p_id FROM '.PLAYERS.' WHERE p_uid = '.$this->id.' AND p_domain = '.scanner::$domain;
		$result = $db->sql_query($sql);
		$p = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);
		if($p){
			return true;
		}else{
			return false;
		}
	}
	
	public function update(){
		global $db;
		
		$sql = 'SELECT * FROM '.PLAYERS.' WHERE p_uid = '.$this->id.' AND p_domain = '.scanner::$domain;
		$result = $db->sql_query($sql);
		$p = $db->sql_fetchrow($result);
		
		$change = false;
		if($p['p_type'] != $this->type){
			$change = true;
			$sqlAry['p_type'] = $this->type;
		}
		if($p['p_last_login'] != $this->lastLogin){
			$change = true;
			$sqlAry['p_last_login'] = $this->lastLogin;
		}
		if($p['p_name'] != $db->sql_escape($this->name)){
			$change = true;
			$sqlAry['p_name'] = $db->sql_escape($this->name);
			
			new nameChange($p['p_uid'], $p['p_name'], $db->sql_escape($this->name));
		}
		if($p['p_alli'] != $this->aId){
			$change = true;
			$sqlAry['p_alli'] = $this->aId;
			
			new allianceChange($p['p_uid'], $p['p_alli'], $this->aId);
		}
		if($p['p_might'] != (int) round($this->might)){
			$sqlAry['p_might'] = (int) round($this->might);
			$change = true;
		}
		if($p['p_glory'] != $this->glory){
			$sqlAry['p_glory'] = $this->glory;
			$change = true;
		}
		
		if($p['p_glory_max'] != $this->gloryMax){
			$sqlAry['p_glory_max'] = $this->gloryMax;
			$change = true;
		}
		
		if($p['p_glory_life'] != $this->gloryLife){
			$change = true;
			$sqlAry['p_glory_life'] = $this->gloryLife;
		}
		
		if($p['p_last_seen'] != time()){
			$change = true;
			$sqlAry['p_last_seen'] = time();
		}
		
		if($p['p_misted'] == 1){
			$change = true;
			$sqlAry['p_misted'] = 0;
		}
		
		if($change){
			$sql = 'UPDATE '.PLAYERS.' SET '.$db->sql_build_array('UPDATE', $sqlAry).' WHERE p_uid = '.$p['p_uid'].' AND p_domain = '.scanner::$domain;
			$db->sql_query($sql);
		}
	}
	
	public function insert(){
		global $db;
		
		$sqlAry = array(
			'p_uid' => $this->id,
			'p_domain' => scanner::$domain,
			'p_name' => $db->sql_escape($this->name),
			'p_prefix' => $this->prefix,
			'p_might' => (int) round($this->might),
			'p_alli' => (int) $this->aId,
			'p_misted' => 0,
			'p_glory' => $this->glory,
			'p_glory_max' => $this->gloryMax,
			'p_glory_life' => $this->gloryLife,
			'p_type' => $this->type,
			'p_last_login' => $this->lastLogin,
			'p_last_seen' => time(),
			'p_fbid' => $this->fbid,
		);
		$sql = 'INSERT INTO '.PLAYERS.' '.$db->sql_build_array('INSERT', $sqlAry);
		$db->sql_query($sql);
	}
}
