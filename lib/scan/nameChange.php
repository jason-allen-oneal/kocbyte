<?php

class nameChange{
	public function __construct($uid, $previous, $current){
		global $db;
		
		$sqlArray = array(
			'nc_uid' => $uid,
			'nc_date' => time(),
			'nc_prev' => $db->sql_escape($previous),
			'nc_curr' => $db->sql_escape($current),
			'nc_domain' => scanner::$domain
		);
		$sql = 'INSERT INTO '.N_CHANGE.' '.$db->sql_build_array('INSERT', $sqlArray);
		$db->sql_query($sql);
	}
}