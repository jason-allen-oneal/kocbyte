<?php

$search = request_var('search', false);
$aId = request_var('alliance', $submode);
$domain = request_var('d', $mode);
$playerSortKey = request_var('sort', 'pos_asc');

$playerOrderBy = 'ORDER BY ';
switch($playerSortKey){
	case 'name_asc':
		$playerOrderBy .= 'p_name ASC';
	break;
	
	case 'name_dsc':
		$playerOrderBy .= 'p_name DESC';
	break;
	
	case 'might_asc':
		$playerOrderBy .= 'p_might ASC';
	break;
	
	case 'might_dsc':
		$playerOrderBy .= 'p_might DESC';
	break;
	
	default:
	case 'pos_asc':
		$playerOrderBy .= 'p_type ASC';
	break;
	
	case 'pos_dsc':
		$playerOrderBy .= 'p_type DESC';
	break;
}

if($search){
	$sql = 'SELECT * FROM '.ALLIS.' WHERE a_name LIKE "%'.$aId.'%" AND a_domain = '.$domain;
	$result = $db->sql_query($sql);
	while($row = $db->sql_fetchrow($result)){
		$template->assign_block_vars('alli', array(
			'NAME' => $row['a_name'],
			'U_ALLIANCE' => append_sid($rootPath.'alliance/'.$domain.'/'.$row['a_aid'].'/'),
		));
	}
	$template->assign_vars(array(
		'IN_SEARCH' => true,
		'DOMAIN' => $domain
	));
	$title = 'Alliance Search';
}else{
	$sql = 'SELECT * FROM '.ALLIS.' WHERE a_aid = '.$aId.' AND a_domain = '.$domain;
	$result = $db->sql_query($sql);
	$alliance = $db->sql_fetchrow($result);
	if($alliance){
		$found = true;
	}else{
		$found = false;
	}
	$hasMembers = false;
	
	$sql = 'SELECT * FROM '.HQS.' WHERE hq_alli = '.$aId.' AND hq_domain = '.$domain;
	$result = $db->sql_query($sql);
	$hq = $db->sql_fetchrow($result);
	if($hq){
		$hqFound = true;
		$hqX = $hq['hq_x'];
		$hqY = $hq['hq_y'];
	}else{
		$hqFound = false;
		$hqX = 0;
		$hqY = 0;
	}
	
	$sql = 'SELECT * FROM '.PLAYERS.' WHERE p_alli = '.$aId.' AND p_domain = '.$domain.' '.$playerOrderBy;
	$result = $db->sql_query($sql);
	$i = 1;
	while($row = $db->sql_fetchrow($result)){
		$hasMembers = true;
		$even = ($i & 1) ? true : false;
		
		if($row['p_mist_expire'] > time()){
			$misted = true;
		}else{
			$misted = false;
		}
		
		$elapsedTime = time() - $row['p_last_login'];
		$timeSince = floor($elapsedTime / (60*60*24));
		
		if($timeSince <= 3){
			$activity = 'active';
		}
		if($timeSince > 3 && $timeSince < 7){
			$activity = 'moderate';
		}
		if($timeSince > 7 && $timeSince < 14){
			$activity = 'inactive';
		}
		if($timeSince >= 14){
			$activity = 'dormant';
		}
		if(isset($row['p_type'])){
			if($row['p_type']){
				$pos = $officers[$row['p_type']];
			}else{
				$pos = $officers[4];
			}
		}else{
			$pos = $officers[4];
		}
		
		$template->assign_block_vars('member', array(
			'PREFIX' => $row['p_prefix'],
			'ACTIVITY' => $activity,
			'NAME' => stripslashes($row['p_name']),
			'MIGHT' => number_format($row['p_might']),
			'IS_EVEN' => $even,
			'POS' => $pos,
			'U_P' => append_sid($rootPath.'player/'.$domain.'/'.$row['p_uid'].'/'),
			'MISTED' => $misted,
		));
		$i++;
	}
	
	$sortAry = array('position_asc' => 'Position - Descending', 'position_dsc' => 'Position - Ascending', 'might_dsc' => 'Might - Descending', 'might_asc' => 'Might - Ascending', 'name_asc' => 'Name - Ascending', 'name_dsc' => 'Name - Descending');
	
	$sortOpts = '';
	foreach($sortAry as $k => $v){
		$selected = ($playerSortKey == $k) ? ' selected="selected"' : '';
		$sortOpts .= '<option'.$selected.' value="'.$k.'">'.$v.'</option>';
	}
	
	$template->assign_vars(array(
		'IN_SEARCH' => false,
		'S_SORT' => append_sid($rootPath.'alliance/'.$domain.'/'.$aId.'/'),
		'SORT_OPTS' => $sortOpts,
		'ID' => $alliance['a_id'],
		'NAME' => $alliance['a_name'],
		'DESC'=> $alliance['a_desc'],
		'MIGHT' => number_format($alliance['a_might']),
		'GLORY' => number_format($alliance['a_glory']),
		'RANK' => $alliance['a_rank'],
		'MEMBERS' => $alliance['a_members'],
		'HAS_MEMBERS' => $hasMembers,
		'HQ_FOUND' => $hqFound,
		'HQ_X' => $hqX,
		'HQ_Y' => $hqY,
		'DOMAIN' => $domain,
		'FOUND' => $found
	));
	
	$title = 'Alliance - '.$alliance['a_name'];
}

// Output page
page_header($title);

$template->set_filenames(array(
	'body' => 'alliance.html')
);

page_footer();

?>