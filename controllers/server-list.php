<?php

$sql = 'SELECT * FROM '.DOMAINS.' ORDER BY d_domain DESC';
$result = $db->sql_query($sql);
while($row = $db->sql_fetchrow($result)){
	$serverName = $serverList[$row['d_domain']];
	$template->assign_block_vars('d', array(
		'U_DOMAIN' => append_sid($rootPath.$row['d_domain'].'/'),
		'NAME' => $serverName
	));
}

// Output page
page_header('Server List');

$template->set_filenames(array(
	'body' => 'serverList.html')
);

page_footer();

?>