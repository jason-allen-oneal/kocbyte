<?php

class cities extends player{
	public $data = array();
	
	public function __construct($cities){
		$this->data = $cities;
	}
	
	public function process(){
		global $db, $player;
		
		// check if city exists in mist table by x,y
		foreach($this->data as $city){
			$this->checkMist($city['x'], $city['y']);
		}
		
		// delete all cities by user
		$sql = 'DELETE FROM '.CITIES.' WHERE c_owner = '.$this->id.' AND c_domain = '.(int) scanner::$domain;
		$db->sql_query($sql);
		
		// re-insert all user cities
		foreach($this->data as $city){
			if(!$this->checkIfExists($city['id'])){
				$this->insert($city);
			}
		}
	}
	
	public function checkIfExists($id){
		global $db;
		
		$sql = 'SELECT c_id FROM '.CITIES.' WHERE c_cid = '.$id.' AND c_domain = '.scanner::$domain;
		$result = $db->sql_query($sql);
		$cid = $db->sql_fetchrow($result);
		if($cid){
			return true;
		}else{
			return false;
		}
	}
	
	public function checkMist($x, $y){
		global $db;
		
		$sql = 'SELECT mist_id AS id FROM '.MISTS.' WHERE mist_x = '.$x.' AND mist_y = '.$y.' AND mist_domain = '.scanner::$domain;
		$result = $db->sql_query($sql);
		$mistId = $db->sql_fetchfield('id');
		if($mistId){
			$sql = 'DELETE FROM '.MISTS.' WHERE mist_id = '.$mistId.' AND mist_domain = '.(int) scanner::$domain;
			$db->sql_query($sql);
		}
	}
	
	public function insert($data){
		global $db;
		
		$sqlAry = array(
			'c_cid' => $data['id'],
			'c_owner' => $data['owner'],
			'c_name' => $data['name'],
			'c_x' => $data['x'],
			'c_y' => $data['y'],
			'c_province' => $data['province'],
			'c_lvl' => $data['lvl'],
			'c_tid' => $data['tid'],
			'c_domain' => scanner::$domain
		);
		$sql = 'INSERT INTO '.CITIES.' '.$db->sql_build_array('INSERT', $sqlAry);
		$db->sql_query($sql);
	}
}
