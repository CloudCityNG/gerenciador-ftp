<?php

// setup includes
require_once('../../../core/includes/master.inc.php');

// require login
$Auth->requireUser(WEB_ROOT.'/login.'.SITE_CONFIG_PAGE_EXTENSION);

// prepare result
$result = array();
$result['error'] = true;
$result['msg']   = 'Error generating url.';

$folderId = (int)$_REQUEST['folderId'];

$fileFolder = fileFolder::loadById($folderId);
if ($fileFolder)
{
	// check user id
	if ($fileFolder->userId == $Auth->id)
	{
		// create url
		$result['error'] = false;
		$result['msg']   = $fileFolder->createUniqueSharingUrl();
	}
	else
	{
		$fileFolder = null;
	}
}

if(!$fileFolder)
{
	$result['error'] = true;
	$result['msg']   = t('could_not_load_folder', 'Could not load folder.');
}

echo json_encode($result);
exit;
