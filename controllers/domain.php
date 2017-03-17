<?php

$mode = Router::$domain;

$sql = 'SELECT d_last_update FROM '.DOMAINS.' WHERE d_domain = '.(int) $mode;
$result = $db->sql_query($sql);
$lastUpdateUnix = $db->sql_fetchfield('d_last_update');
$lastUpdate = date('F jS, Y g:i a', $lastUpdateUnix);

$sql = 'SELECT COUNT(p_id) AS pCt FROM '.PLAYERS.' WHERE p_domain = '.$mode;
$result = $db->sql_query($sql);
$playerCt = $db->sql_fetchfield('pCt');

$sql = 'SELECT COUNT(a_id) as aCt FROM '.ALLIS.' WHERE a_domain = '.$mode;
$result = $db->sql_query($sql);
$alliCt = $db->sql_fetchfield('aCt');

$sql = 'SELECT COUNT(mist_id) as mCt FROM '.MISTS.' WHERE mist_domain = '.$mode;
$result = $db->sql_query($sql);
$mistCt = $db->sql_fetchfield('mCt');

$sql = 'SELECT COUNT(hq_id) as hqCt FROM '.HQS.' WHERE hq_domain = '.$mode;
$result = $db->sql_query($sql);
$hqCt = $db->sql_fetchfield('hqCt');

$sql = 'SELECT COUNT(ore_id) as oreCt FROM '.ORE.' WHERE ore_domain = '.$mode;
$result = $db->sql_query($sql);
$oreCt = $db->sql_fetchfield('oreCt');

$sql = 'SELECT COUNT(w_id) as wildOreCt FROM '.WILDS.' WHERE w_type = 40 AND w_level = 10 AND w_domain = '.(int) $mode;
$result = $db->sql_query($sql);
$wildOreCt = $db->sql_fetchfield('wildOreCt');

$sql = 'SELECT p_name, p_uid, p_might FROM '.PLAYERS.' WHERE p_might = (SELECT MAX(p_might) FROM '.PLAYERS.' WHERE p_domain = '.$mode.')';
$result = $db->sql_query($sql);
$maxMight = $db->sql_fetchrow($result);

$sql = 'SELECT p_name, p_uid, p_glory FROM '.PLAYERS.' WHERE p_glory = (SELECT MAX(p_glory) FROM '.PLAYERS.' WHERE p_domain = '.$mode.')';
$result = $db->sql_query($sql);
$maxGlory = $db->sql_fetchrow($result);

$sql = 'SELECT p_name, p_uid, p_glory_life FROM '.PLAYERS.' WHERE p_glory_life = (SELECT MAX(p_glory_life) FROM '.PLAYERS.' WHERE p_domain = '.$mode.')';
$result = $db->sql_query($sql);
$maxGloryLife = $db->sql_fetchrow($result);

$a = 1;
$sql = 'SELECT * FROM '.ALLIS.' WHERE a_domain = '.$mode.' ORDER BY a_rank LIMIT 20';
$result = $db->sql_query($sql);
while($alli = $db->sql_fetchrow($result)){
	$break = ($a == 10) ? true : false;
	$template->assign_block_vars('a', array(
		'NAME' => $alli['a_name'],
		'U_ALLI' => append_sid($rootPath.'alliance/'.$mode.'/'.$alli['a_aid'].'/'),
		'RANK' => $a,
		'BREAK' => $break
	));
	$a++;
}


$template->assign_vars(array(
	'LAST_UPDATE' => $lastUpdate,
	'DOMAIN' => $mode,
	'P_CT' => number_format($playerCt),
	'A_CT' => number_format($alliCt),
	'M_CT' => number_format($mistCt),
	'HQ_CT' => number_format($hqCt),
	'ORE_CT' => number_format($oreCt + $wildOreCt),
	'S_MAX_MIGHT_PLAYER' => stripslashes($maxMight['p_name']),
	'U_MAX_MIGHT_PLAYER' => append_sid($rootPath.'player/'.$mode.'/'.$maxMight['p_uid'].'/'),
	'S_MAX_MIGHT_PLAYER_MIGHT' => number_format($maxMight['p_might']),
	'S_MAX_GLORY_PLAYER' => stripslashes($maxGlory['p_name']),
	'U_MAX_GLORY_PLAYER' => append_sid($rootPath.'player/'.$mode.'/'.$maxGlory['p_uid'].'/'),
	'S_MAX_GLORY_PLAYER_GLORY' => number_format($maxGlory['p_glory']),
	'S_MAX_LIFE_GLORY_PLAYER' => stripslashes($maxGloryLife['p_name']),
	'U_MAX_LIFE_GLORY_PLAYER' => append_sid($rootPath.'player/'.$mode.'/'.$maxGloryLife['p_uid'].'/'),
	'S_MAX_LIFE_GLORY_PLAYER_GLORY' => number_format($maxGloryLife['p_glory_life']),
	'U_MISTED' => append_sid($rootPath.'misted/'.$mode.'/'),
	'U_ORE' => append_sid($rootPath.'ore/'.$mode.'/'),
	'S_PLAYER' => append_sid($rootPath.'player/'.$mode.'/'),
	'S_ALLI' => append_sid($rootPath.'alliance/'.$mode.'/'),
));

// Output page
page_header('Domain - '.$mode);

$template->set_filenames(array(
	'body' => 'domain.html')
);

page_footer();

?>