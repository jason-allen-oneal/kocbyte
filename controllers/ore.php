<?php

$domain = request_var('d', $mode);

$sql = 'SELECT * FROM '.WILDS.' WHERE w_type = 40 AND w_level = 10 AND w_domain = '.(int) $domain;
$result = $db->sql_query($sql);
while($row = $db->sql_fetchrow($result)){
	$ores[] = array(
		'x' => $row['w_x'],
		'y' => $row['w_y']
	);
}

$sql = 'SELECT * FROM '.ORE.' WHERE ore_domain = '.(int) $domain;
$result = $db->sql_query($sql);
while($row = $db->sql_fetchrow($result)){
	$ores[] = array(
		'x' => $row['ore_x'],
		'y' => $row['ore_y']
	);
}

foreach($ores as $ore){
	$template->assign_block_vars('o', array(
		'X' => $ore['x'],
		'Y' => $ore['y'],
	));
}

$title = '';
$template->assign_vars(array(
	'DOMAIN' => $domain,
));

// Output page
$pageTitle = 'Level 10 Mountains ('.$domain.')'.(($title != '') ? ' - '.$title : '');
page_header($pageTitle);

$template->set_filenames(array(
	'body' => 'ore.html')
);

page_footer();

?>