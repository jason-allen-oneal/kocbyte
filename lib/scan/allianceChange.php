<?php

class allianceChange{
	public function __construct($uid, $previous, $current){
		global $db;
		
		$sqlArray = array(
			'ac_uid' => $uid,
			'ac_date' => time(),
			'ac_prev' => $previous,
			'ac_curr' => $current,
			'ac_domain' => scanner::$domain
		);
		$sql = 'INSERT INTO '.A_CHANGE.' '.$db->sql_build_array('INSERT', $sqlArray);
		$db->sql_query($sql);
	}
}