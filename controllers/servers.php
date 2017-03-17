<?php

$uid = request_var('uid', $submode);

if($mode == 'search'){
	$sql = 'SELECT p_prefix, p_name, p_domain, p_uid FROM '.PLAYERS.' WHERE p_uid = '.(int) $uid;
	$result = $db->sql_query($sql);
	while($row = $db->sql_fetchrow($result)){
		$template->assign_block_vars('p', array(
			'PREFIX' => $row['p_prefix'],
			'NAME' => $row['p_name'],
			'DOMAIN' => $row['p_domain'],
			'U_D' => append_sid($rootPath.'player/'.$row['p_domain'].'/'.$row['p_uid'].'/')
		));
	}

	$template->assign_vars(array(
		'UID' => $submode,
	));
}else{
	$sql = 'SELECT p_prefix, p_name, p_uid FROM '.PLAYERS.' WHERE p_uid = '.(int) $uid.' AND p_domain = '.$mode;
	$result = $db->sql_query($sql);
	while($row = $db->sql_fetchrow($result)){
		$template->assign_vars(array(
			'PREFIX' => $row['p_prefix'],
			'NAME' => $row['p_name'],
			'U_D' => append_sid($rootPath.'player/'.$row['p_domain'].'/'.$row['p_uid'].'/')
		));
	}

	$template->assign_vars(array(
		'UID' => $submode,
	));
}

$template->assign_vars(array(
	'MODE' => $mode
));

// Output page
page_header('Server UID Lookup - '.$submode);

$template->set_filenames(array(
	'body' => 'servers.html')
);

page_footer();

?>