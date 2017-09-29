<?php

// setup includes
require_once('../../../core/includes/master.inc.php');

// require login
$Auth->requireUser(WEB_ROOT.'/login.'.SITE_CONFIG_PAGE_EXTENSION);
$folderId = (int)$_REQUEST['folderId'];
$registeredEmailAddress = trim($_REQUEST['registeredEmailAddress']);
$registeredEmailAddress = strtolower($registeredEmailAddress);
$registeredEmailAddressExp = explode(',', $registeredEmailAddress);
$permissionType = trim($_REQUEST['permissionType']);
if(!in_array($permissionType, array('view', 'upload_download', 'all')))
{
	$permissionType = 'view';
}

// prepare result
$result = array();
$result['error'] = false;
$result['msg']   = t('share_folder_internally_success', 'If the email address exists within our system, this folder will now be available to the user within their account.');
$result['folderId'] = $folderId;

if(strlen($registeredEmailAddress) == 0)
{
	$result['error'] = true;
	$result['msg']   = t('please_enter_an_email_address_to_share_with', 'Please enter an existing account email address to share this folder with.');
}
else
{
	$fileFolder = fileFolder::loadById($folderId);
	if ($fileFolder)
	{
		// check user id
		if ($fileFolder->userId != $Auth->id)
		{
			$result['error'] = true;
			$result['msg']   = t('could_not_load_folder', 'Could not load folder.');
		}
	}
}

if($result['error'] == false)
{
	// add user(s) to folder
	foreach($registeredEmailAddressExp AS $registeredEmailAddressItem)
	{
		// lookup account based on email
		$userId = (int)$db->getValue('SELECT id FROM users WHERE email = '.$db->quote($registeredEmailAddressItem).' LIMIT 1');
		if($userId)
		{
			// make sure the user isn't adding themselves
			if ($userId == $Auth->id)
			{
				continue;
			}
			
			// add the share
			$fileFolder->createUniqueSharingUrl($userId, $permissionType);
		}
	}
}

echo json_encode($result);
exit;
