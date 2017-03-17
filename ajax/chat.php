<?php

chdir(__DIR__);

define('IN_SITE', true);
$rootPath = $root_path = './../';
$phpEx = 'php';
include($rootPath.'inc/common.php');

$user->session_begin();
$user->setup();

$mode = request_var('mode', '');
$domain = request_var('d', 0);
$alli = request_var('a', 0);
$uid = request_var('u', 0);

if($mode != ''){
	switch($mode){
		case 'init_check':
			$sql = 'SELECT * FROM '.CHATS.' WHERE chat_domain = '.$domain.' AND chat_alli = '.$alli;
			$result = $db->sql_query($sql);
			$chat = $db->sql_fetchrow($result);
			if($chat){
				$res['result'] = true;
			}else{
				$res['result'] = false;
			}
		break;
		
		case 'init':
			$sql = 'SELECT * FROM '.CHATS.' WHERE chat_domain = '.$domain.' AND chat_alli = '.$alli;
			$result = $db->sql_query($sql);
			$chat = $db->sql_fetchrow($result);
			if($chat){
				$res['result'] = false;
				$res['message'] = 'Chat already started. Please enter the password.';
			}else{
				$pass = request_var('pass', '');
				$hash = site_hash($pass);
				
				$sqlAry = array(
					'chat_domain' => $domain,
					'chat_alli' => $alli,
					'chat_pass' => $hash
				);
				
				$sql = 'INSERT INTO '.CHATS.' '.$db->sql_build_array('INSERT', $sqlAry);
				$db->sql_query($sql);
				
				$res['result'] = true;
			}
		break;
	}
}else{

}

echo json_encode($res);

?>