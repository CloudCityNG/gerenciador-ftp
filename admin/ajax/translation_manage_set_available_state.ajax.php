<?php

// includes and security
include_once('../_local_auth.inc.php');

$languageId = (int)$_REQUEST['languageId'];
$state = (int)$_REQUEST['state'];

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
    $db->query('UPDATE language SET isActive = :state WHERE id = :id LIMIT 1', array('state' => $state, 'id' => $languageId));
    if ($db->affectedRows() == 1)
    {
        $result['error'] = false;
        $result['msg']   = 'Language set as '.($state==1?'active':'disabled').'.';
    }
    else
    {
        $result['error'] = true;
        $result['msg']   = 'Could not change the state of the language, please try again later.';
    }
}

echo json_encode($result);
exit;
