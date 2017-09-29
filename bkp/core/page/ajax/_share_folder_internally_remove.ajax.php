<?php

// setup includes
require_once('../../../core/includes/master.inc.php');

// require login
$Auth->requireUser(WEB_ROOT.'/login.'.SITE_CONFIG_PAGE_EXTENSION);
$folderShareId = (int)$_REQUEST['folderShareId'];

// prepare result
$result = array();
$result['error'] = false;
$result['msg']   = t('share_folder_internally_success', 'If the email address exists within our system, this folder will now be available to the user within their account.');

// load folder share
$folderId = (int)$db->getValue('SELECT folder_id FROM file_folder_share WHERE id = '.(int)$folderShareId.' AND created_by_user_id = '.$Auth->id.' LIMIT 1');
if(!$folderId)
{
	$result['error'] = true;
	$result['msg']   = t('could_not_load_folder_share', 'Could not load folder share.');
}

$fileFolder = fileFolder::loadById($folderId);
if ($fileFolder)
{
	// check user id, only the original owner can manage shares
	if ($fileFolder->userId != $Auth->id)
	{
		$result['error'] = true;
		$result['msg']   = t('could_not_load_folder', 'Could not load folder.');
	}
}

if($result['error'] == false)
{
	// for the refresh after the ajax call
	$result['folderId'] = $folderId;
	
	// remove the share
	$fileFolder->removeUniqueSharingUrl($folderShareId);
}

echo json_encode($result);
exit;
