<?php

// setup includes
require_once('../../../core/includes/master.inc.php');

// require login
$Auth->requireUser(WEB_ROOT.'/login.'.SITE_CONFIG_PAGE_EXTENSION);

// prepare result
$result = array();
$result['error'] = true;
$result['msg']   = 'Error removing folder.';

if (_CONFIG_DEMO_MODE == true)
{
    $result['error'] = true;
    $result['msg']   = t("no_changes_in_demo_mode");
}
elseif(coreFunctions::getUsersAccountLockStatus($Auth->id) == 1)
{
	$result['error'] = true;
	$result['msg']   = t('account_locked_error_message', 'This account has been locked, please unlock the account to regain full functionality.');
}
else
{
    $folderId = (int)$_REQUEST['folderId'];
    
    $fileFolder = fileFolder::loadById($folderId);
    if ($fileFolder)
    {
        // check user id
        if ($fileFolder->userId == $Auth->id)
        {
            $fileFolder->removeByUser();
            
            $result['error'] = false;
            $result['msg']   = 'Folder deleted.';
        }
    }
}

echo json_encode($result);
exit;
