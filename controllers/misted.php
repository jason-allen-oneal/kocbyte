<?php

$province = request_var('province', $submode);
$domain = request_var('d', $mode);

if($province){
	$sql = 'SELECT * FROM '.MISTS.' WHERE mist_domain = '.$domain.' AND mist_prov = '.$province;
	$result = $db->sql_query($sql);
	while($row = $db->sql_fetchrow($result)){
		$template->assign_block_vars('m', array(
			'X' => $row['mist_x'],
			'Y' => $row['mist_y']
		));
	}
	$sql = 'SELECT * FROM '.PROVINCES.' WHERE prov_id = '.$province;
	$result = $db->sql_query($sql);
	$prov = $db->sql_fetchrow($result);
	
	$title = $prov['prov_name'];
}else{
	$sql = 'SELECT * FROM '.PROVINCES;
	$result = $db->sql_query($sql);
	while($row = $db->sql_fetchrow($result)){
		$mistProvs[] = array(
			'id' => $row['prov_id'],
			'name' => $row['prov_name']
		);
	}
	
	foreach($mistProvs as $prov){
		$sql = 'SELECT COUNT(mist_id) AS count FROM '.MISTS.' WHERE mist_domain = '.$domain.' AND mist_prov = '.$prov['id'];
		$result = $db->sql_query($sql);
		$count = $db->sql_fetchfield('count');
		
		$template->assign_block_vars('p', array(
			'U_PROV' => append_sid($rootPath.'misted/'.$domain.'/'.$prov['id'].'/'),
			'NAME' => $prov['name'],
			'COUNT' => $count
		));
	}
	$title = '';
}

$template->assign_vars(array(
	'IN_PROV' => ($province != '') ? true : false,
	'DOMAIN' => $domain,
));

// Output page
$pageTitle = 'Misted Cities ('.$domain.')'.(($title != '') ? ' - '.$title : '');
page_header($pageTitle);

$template->set_filenames(array(
	'body' => 'misted.html')
);

page_footer();

?>