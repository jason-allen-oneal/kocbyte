<?php

$search = request_var('search', false);
$player = request_var('player', $submode);
$domain = request_var('d', $mode);

if($search){
	$sql = 'SELECT * FROM '.PLAYERS.' WHERE p_name LIKE "%'.$player.'%" AND p_domain = '.$domain;
	$result = $db->sql_query($sql);
	while($row = $db->sql_fetchrow($result)){
		$template->assign_block_vars('player', array(
			'NAME' => $row['p_name'],
			'PREFIX' => $row['p_prefix'],
			'U_PLAYER' => append_sid($rootPath.'player/'.$row['p_domain'].'/'.$row['p_uid'].'/'),
		));
	}
	$template->assign_vars(array(
		'IN_SEARCH' => true,
		'DOMAIN' => $domain
	));
	$title = 'Player Search';
}else{
	$sql = 'SELECT * FROM '.PLAYERS.' WHERE p_uid = '.$player.' AND p_domain = '.$domain;
	$result = $db->sql_query($sql);
	$p = $db->sql_fetchrow($result);
	
	if($p){
		if($p['p_alli']){
			$sql = 'SELECT * FROM '.ALLIS.' WHERE a_aid = '.$p['p_alli'].' AND a_domain = '.$domain;
			$result = $db->sql_query($sql);
			$alliance = $db->sql_fetchrow($result);
			
			$hasAlli = true;
		}else{
			$hasAlli = false;
		}
		
		$sql = 'SELECT * FROM '.CITIES.' WHERE c_owner = '.$player.' AND c_domain = '.$domain;
		$result = $db->sql_query($sql);
		while($row = $db->sql_fetchrow($result)){
			$cities[$row['c_cid']] = array(
				'name' => $row['c_name'],
				'x' => $row['c_x'],
				'y' => $row['c_y']
			);
		}
		
		if(isset($cities)){
			foreach($cities as $k => $v){
				$wilds = [];
				
				$sql = 'SELECT * FROM '.WILDS.' WHERE w_uid = '.$player.' AND w_cid = '.$k.' AND w_domain = '.$domain;
				$result = $db->sql_query($sql);
				while($row = $db->sql_fetchrow($result)){
					$wilds[] = array(
						'x' => $row['w_x'],
						'y' => $row['w_y'],
						'type' => $row['w_type'],
						'lvl' => $row['w_level']
					);
				}
				
				$template->assign_block_vars('city', array(
					'NAME' => $v['name'],
					'X' => $v['x'],
					'Y' => $v['y'],
					'HAS_WILDS' => (count($wilds)) ? true : false,
				));
				
				foreach($wilds as $w){
					$sql = 'SELECT * FROM '.T_TYPES.' WHERE tt_id = '.$w['type'];
					$result = $db->sql_query($sql);
					$tType = $db->sql_fetchrow($result);
					
					$template->assign_block_vars('city.wild', array(
						'X' => $w['x'],
						'Y' => $w['y'],
						'TYPE' => $tType['tt_name'],
						'LVL' => $w['lvl']
					));
				}
			}
		}
		
		$sql = 'SELECT * FROM '.N_CHANGE.' WHERE nc_uid = '.$player.' AND nc_domain = '.$domain;
		$result = $db->sql_query($sql);
		while($row = $db->sql_fetchrow($result)){
			$template->assign_block_vars('nc', array(
				'DATE' => date('M d \'y', $row['nc_date']),
				'PREV' => stripslashes($row['nc_prev']),
				'CURR' => stripslashes($row['nc_curr'])
			));
		}
		
		$sql = 'SELECT * FROM '.A_CHANGE.' WHERE ac_uid = '.$player.' AND ac_domain = '.$domain;
		$result = $db->sql_query($sql);
		while($row = $db->sql_fetchrow($result)){
			$alliChanges[] = array(
				'date' => date('M d \'y', $row['ac_date']),
				'prev' => $row['ac_prev'],
				'curr' => $row['ac_curr']
			);
		}
		
		if(isset($alliChanges)){
			foreach($alliChanges as $alliChange){
				$sql = 'SELECT a_name, a_aid FROM '.ALLIS.' WHERE a_domain = '.$domain.' AND a_aid = '.$alliChange['prev'];
				$result = $db->sql_query($sql);
				$prev = $db->sql_fetchrow($result);
				
				$sql = 'SELECT a_name, a_aid FROM '.ALLIS.' WHERE a_domain = '.$domain.' AND a_aid = '.$alliChange['curr'];
				$result = $db->sql_query($sql);
				$curr = $db->sql_fetchrow($result);
				
				$template->assign_block_vars('ac', array(
					'DATE' => $alliChange['date'],
					'PREV' => $prev['a_name'],
					'U_PREV' => append_sid($rootPath.'alliance/'.$domain.'/'.$prev['a_aid'].'/'),
					'CURR' => $curr['a_name'],
					'U_CURR' => append_sid($rootPath.'alliance/'.$domain.'/'.$curr['a_aid'].'/')
				));
			}
		}
		
		if($p['p_mist_expire']){
			if($p['p_mist_expire'] < time()){
				$misted = false;
			}else{
				$misted = date('M d \'y', $p['p_mist_expire']);
			}
		}else{
			$misted = false;
		}
		
		if($p['p_truce_expire']){
			if($p['p_truce_expire'] < time()){
				$truced = false;
			}else{
				$truced = date('M d \'y', $p['p_truce_expire']);
			}
		}else{
			$truced = false;
		}
		
		$template->assign_vars(array(
			'IN_SEARCH' => false,
			'HAS_ALLI' => $hasAlli,
			'ID' => $p['p_id'],
			'UID' => $player,
			'DOMAIN' => $domain,
			'U_D' => append_sid($rootPath.'servers/search/'.$player.'/'),
			'PREFIX' => $p['p_prefix'],
			'NAME' => stripslashes($p['p_name']),
			'MIGHT' => number_format($p['p_might']),
			'GLORY' => number_format($p['p_glory']),
			'MAX_GLORY' => number_format($p['p_glory_max']),
			'U_A' => append_sid($rootPath.'alliance/'.$domain.'/'.$alliance['a_aid'].'/'),
			'A_NAME' => $alliance['a_name'],
			'MISTED' => $misted,
			'TRUCED' => $truced,
			'FOUND_PLAYER' => true
		));
		
		$title = 'Player - '.stripslashes($p['p_name']);
	}else{
		$template->assign_vars(array(
			'FOUND_PLAYER' => false
		));
		$title = 'Player not found';
	}
}

// Output page
page_header($title);

$template->set_filenames(array(
	'body' => 'player.html')
);

page_footer();

?>