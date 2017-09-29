<?php

/* setup includes */
require_once('../../../core/includes/master.inc.php');

// for cross domain access
coreFunctions::allowCrossSiteAjax();

// process csaKeys and authenticate user
$csaKey1 = trim($_REQUEST['csaKey1']);
$csaKey2 = trim($_REQUEST['csaKey2']);
if(strlen($csaKey1) && strlen($csaKey1))
{
    crossSiteAction::setAuthFromKeys($csaKey1, $csaKey2);
}

// require login
$Auth->requireUser(WEB_ROOT.'/login.'.SITE_CONFIG_PAGE_EXTENSION);

// prepare result
$result = array();
$result['error'] = false;
$result['msg']   = '';

// pick up file ids
$fileIds     = $_REQUEST['fileIds'];

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
    $totalRemoved = 0;
    
    // load files
    if(COUNT($fileIds))
    {
        foreach($fileIds AS $fileId)
        {
            // load file and process if active and belongs to the currently logged in user
            $file = file::loadById($fileId);
            if (($file) && ($file->statusId == 1) && ($file->userId == $Auth->id))
            {
                // remove
                $rs = $file->removeByUser();
                if($rs)
                {
                    $totalRemoved++;
                }
            }
        }
    }
}

$result['msg'] = 'Removed '.$totalRemoved.' file'.($totalRemoved!=1?'s':'').'.';

echo json_encode($result);
exit;
