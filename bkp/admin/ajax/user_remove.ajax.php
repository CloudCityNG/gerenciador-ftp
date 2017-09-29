<?php

// includes and security
define('MIN_ACCESS_LEVEL', 10); // allow moderators
include_once('../_local_auth.inc.php');

$userId     = (int) $_REQUEST['userId'];

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
    // delete user
    $user = UserPeer::loadUserById($userId);
    if($user)
    {
        $username = $user->username;
        $rs = $user->deleteUserData();
        if($rs)
        {
            $result['error'] = false;
            $result['msg']   = 'User \'' . $username . '\' and all associated data removed.';
        }
        else
        {
            $result['error'] = true;
            $result['msg']   = 'Could not delete the user, please try again later.';
        }
    }
    else
    {
        $result['error'] = true;
        $result['msg']   = 'Could not find the user to delete.';
    }
}

echo json_encode($result);
exit;
