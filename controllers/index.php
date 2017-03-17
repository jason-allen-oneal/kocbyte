<?php

$sql = 'SELECT COUNT(p_id) AS pCt FROM '.PLAYERS.' WHERE p_alli != 0';
$result = $db->sql_query($sql);
$alliedPlayerCt = $db->sql_fetchfield('pCt');

$sql = 'SELECT COUNT(p_id) AS pCt FROM '.PLAYERS.' WHERE p_alli = 0';
$result = $db->sql_query($sql);
$unalliedPlayerCt = $db->sql_fetchfield('pCt');

$sql = 'SELECT COUNT(a_id) as aCt FROM '.ALLIS;
$result = $db->sql_query($sql);
$alliCt = $db->sql_fetchfield('aCt');

$sql = 'SELECT p_name, p_uid, p_might, p_domain FROM '.PLAYERS.' WHERE p_might = (SELECT MAX(p_might) FROM '.PLAYERS.')';
$result = $db->sql_query($sql);
$maxMight = $db->sql_fetchrow($result);

$sql = 'SELECT p_name, p_uid, p_glory, p_domain FROM '.PLAYERS.' WHERE p_glory = (SELECT MAX(p_glory) FROM '.PLAYERS.')';
$result = $db->sql_query($sql);
$maxGlory = $db->sql_fetchrow($result);

$sql = 'SELECT p_name, p_uid, p_glory_life, p_domain FROM '.PLAYERS.' WHERE p_glory_life = (SELECT MAX(p_glory_life) FROM '.PLAYERS.')';
$result = $db->sql_query($sql);
$maxGloryLife = $db->sql_fetchrow($result);

$template->assign_vars(array(
	'ALLIED_PLAYER_CT' => number_format($alliedPlayerCt),
	'UNALLIED_PLAYER_CT' => number_format($unalliedPlayerCt),
	'ALLI_CT' => number_format($alliCt),
	'U_MAX_MIGHT_PLAYER' => append_sid($rootPath.'player/'.$maxMight['p_domain'].'/'.$maxMight['p_uid'].'/'),
	'S_MAX_MIGHT_PLAYER' => stripslashes($maxMight['p_name']),
	'S_MAX_MIGHT_PLAYER_MIGHT' => number_format($maxMight['p_might']),
	'U_MAX_GLORY_PLAYER' => append_sid($rootPath.'player/'.$maxGlory['p_domain'].'/'.$maxGlory['p_uid'].'/'),
	'S_MAX_GLORY_PLAYER' => stripslashes($maxGlory['p_name']),
	'S_MAX_GLORY_PLAYER_GLORY' => number_format($maxGlory['p_glory']),
	'U_MAX_LIFE_GLORY_PLAYER' => append_sid($rootPath.'player/'.$maxGloryLife['p_domain'].'/'.$maxGloryLife['p_uid'].'/'),
	'S_MAX_LIFE_GLORY_PLAYER' => stripslashes($maxGloryLife['p_name']),
	'S_MAX_LIFE_GLORY_PLAYER_GLORY' => number_format($maxGloryLife['p_glory_life']),
));

// Output page
page_header('Home');

$template->set_filenames(array(
	'body' => 'body.html')
);

page_footer();

?>