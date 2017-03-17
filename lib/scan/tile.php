<?php

class tile{
	public static $type = 0;
	public static $id = 0;
	public static $lvl = 0;
	public static $x = 0;
	public static $y = 0;
	public static $city = false;
	public static $mist = false;
	public static $province = 0;
	
	public function __construct($tile){
		self::$type = (int) $tile['tileType'];
		self::$id = (int) $tile['tileId'];
		self::$x = (int) $tile['xCoord'];
		self::$y = (int) $tile['yCoord'];
		self::$lvl = (int) $tile['tileLevel'];
		self::$province = (int) $tile['tileProvinceId'];
		
		self::$city = (self::$type == 51) ? true : false;
	}
	
	public static function checkMist(){
		global $db;
		
		$sql = 'SELECT mist_id AS id FROM '.MISTS.' WHERE mist_x = '.self::$x.' AND mist_y = '.self::$y.' AND mist_domain = '.(int) scanner::$domain;
		$result = $db->sql_query($sql);
		return $db->sql_fetchfield('id');
	}
	
	public static function insertMist(){
		global $db;
		
		$sqlAry = array(
			'mist_x' => self::$x,
			'mist_y' => self::$y,
			'mist_prov' => self::$province,
			'mist_domain' => scanner::$domain
		);
		$sql = 'INSERT INTO '.MISTS.' '.$db->sql_build_array('INSERT', $sqlAry);
		$db->sql_query($sql);
	}
	
	public static function checkMistsAndRemove(){
		global $db;
		
		$mistId = self::checkMist();
		if($mistId){
			$sql = 'DELETE FROM '.MISTS.' WHERE mist_id = '.$mistId.' AND mist_domain = '.(int) scanner::$domain;
			$db->sql_query($sql);
		}
	}
}