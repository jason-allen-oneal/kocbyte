<?php

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

$lang = array_merge($lang, array(
	'FB_CONNECT_USER_DENY' => 'We\'re sorry you decided not to connect through facebook. Please contact us at <a href="mailto:%s">%s</a>',
	
	'FB_LINK_ACCOUNTS' => 'Link Accounts',
	'FB_LINK_ACCOUNTS_CONFIRM' => 'The email address associated with your Facebook account is already in use. Would you like to connect your Facebook account with this one?',
	
	'FB_LOGIN' => 'Facebook Login',
	
	'FB_STATE_MISMATCH' => 'You may be a victim of CSFR Attacks. Try <a  href="%s">logging in</a> again.',
	
	'FB_UPDATE_ACCOUNTS' => 'Update Profile',
	'FB_UPDATE_ACCOUNTS_CONFIRM' => 'Would you like to update your profile with data from your Facebook account?',
	
	
));

?>