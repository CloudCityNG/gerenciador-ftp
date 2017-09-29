<?php

// includes and security
include_once('../_local_auth.inc.php');

$contentId = (int)$_REQUEST['contentId'];

// prepare result
$result = array();
$result['error'] = false;
$result['msg']   = '';

if (_CONFIG_DEMO_MODE == true)
{
    $result['error'] = true;
    $result['msg']   = adminFunctions::t("no_changes_in_demo_mode");
}
else
{
	$currentState = (int)$db->getValue("SELECT is_locked FROM language_content WHERE id = ".(int)$contentId." LIMIT 1");
	$newState = 1;
	if($currentState == 1)
	{
		$newState = 0;
	}

    $db->query('UPDATE language_content SET is_locked = :is_locked WHERE id = :id LIMIT 1', array('is_locked' => $newState, 'id' => $contentId));
    if ($db->affectedRows() == 1)
    {
        $result['error'] = false;
        $result['msg']   = 'Content locked state updated.';
    }
    else
    {
        $result['error'] = true;
        $result['msg']   = 'Could not change the locked state, please try again later.';
    }
}

echo json_encode($result);
exit;
