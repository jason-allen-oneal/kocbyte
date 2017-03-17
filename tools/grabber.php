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

if($handle = opendir($rootPath.'data/')){
	while(false !== ($entry = readdir($handle))){
		if($entry != "." && $entry != ".."){
			$sql = 'SELECT d_domain AS d FROM '.DOMAINS.' WHERE d_domain = '.$entry;
			$result = $db->sql_query($sql);
			$d = $db->sql_fetchfield('d');
			if(!$d){
				$sqlAry = array(
					'd_domain' => $entry,
					'd_last_update' => 0,
					'd_last_update_user' => 0
				);
				
				$sql = 'INSERT INTO '.DOMAINS.' '.$db->sql_build_array('INSERT', $sqlAry);
				$db->sql_query($sql);
			}
		}
	}
	closedir($handle);
}

echo 'done';

?>