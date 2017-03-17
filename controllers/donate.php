<?php

function esc($str){
	global $db;
	
	if(ini_get('magic_quotes_gpc'))
		$str = stripslashes($str);
	
	return $db->sql_escape(htmlspecialchars(strip_tags($str)));
}

function validateUrl($str){
	return preg_match('/(http|ftp|https):\/\/[\w\-_]+(\.[\w\-_]+)+([\w\-\.,@?^=%&amp;:\/~\+#]*[\w\-\@?^=%&amp;\/~\+#])?/i',$str);
}

$url = generate_site_url();

if($mode == 'thanks'){
	if(isset($_POST['submitform']) && isset($_POST['txn_id'])){
		$_POST['nameField'] = esc($_POST['nameField']);
		$_POST['websiteField'] =  esc($_POST['websiteField']);
		$_POST['messageField'] = esc($_POST['messageField']);
		
		$error = array();
		
		if(mb_strlen($_POST['nameField'],"utf-8")<2){
			$error[] = 'Please fill in a valid name.';
		}
		
		if(mb_strlen($_POST['messageField'],"utf-8")<2){
			$error[] = 'Please fill in a longer message.';
		}
		
		if(!validateURL($_POST['websiteField'])){
			$error[] = 'The URL you entered is invalid.';
		}

		$errorString = '';
		if(count($error)){
			$errorString = join('<br />',$error);
			$template->assign_vars(array(
				'ERROR' => true,
				'ERR' => $errorString
			));
		}else{
			$sqlAry = array(
				'transaction_id' => esc($_POST['txn_id']),
				'name' => $_POST['nameField'],
				'url' => $_POST['websiteField'],
				'message' => $_POST['messageField']
			);
			
			$sql = 'INSERT INTO '.DONATION_COMMENTS.' '.$db->sql_build_array('INSERT', $sqlAry);
			$db->sql_query($sql);
			if($db->sql_affectedrows()){
				$msg = '<a href="'.append_sid($url.'/donate/').'">You were added to the donor list!</a>';
			}else{
				$msg = 'Something went wrong!';
			}
			
			$template->assign_vars(array(
				'ERROR' => false,
				'MSG' => $msg
			));
		}
	}else{
		$template->assign_vars(array(
			'REG' => true,
			'TXN_ID' => $_POST['txn_id']
		));
	}
}else{
	$goal = 55.31;

	// Fetching the number and the sum of the donations:
	$sql = 'SELECT COUNT(*) AS count, SUM(amount) AS sum FROM '.DONATIONS;
	$result = $db->sql_query($sql);
	$row = $db->sql_fetchrow($result);

	// Calculating how many percent of the goal are met:
	$percent = round(min(100 * ($row['sum'] / $goal), 100));

	// Building a URL with Google's Chart API:
	$chartUrl = 'http://chart.apis.google.com/chart?chf=bg,s,f9faf7&cht=p&chd=t:'.$percent.',-'.(100 - $percent).'&chs=200x200&chco=639600&chp=1.57';

	$sql = 'SELECT * FROM '.DONATION_COMMENTS.' ORDER BY id DESC';
	$result = $db->sql_query($sql);
	while($comment = $db->sql_fetchrow($result)){
		$template->assign_block_vars('c', array(
			'MSG' => nl2br($comment['message']),
			'NAME' => $comment['name'],
			'URL' => $comment['url']
		));
	}

	$template->assign_vars(array(
		'TOTAL_DONATIONS' => $row['count'],
		'SUM' => $row['sum'],
		'PERCENTAGE' => $percent,
		'CHART' => $chartUrl,
		'RETURN' => append_sid($url.'/donate/thanks/'),
		'URL' => $url
	));
}

$template->assign_vars(array(
	'MODE' => $mode
));

// Output page
page_header('Donate');

$template->set_filenames(array(
	'body' => 'donate.html')
);

page_footer();

?>