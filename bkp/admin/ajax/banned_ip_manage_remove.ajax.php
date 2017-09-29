<?php

// includes and security
include_once('../_local_auth.inc.php');

$bannedIpId = (int) $_REQUEST['bannedIpId'];

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
    $db->query('DELETE FROM banned_ips WHERE id = :bannedIpId', array('bannedIpId' => $bannedIpId));
    if ($db->affectedRows() == 1)
    {
        $result['error'] = false;
        $result['msg']   = 'IP address removed from banned list.';
    }
    else
    {
        $result['error'] = true;
        $result['msg']   = 'Could not remove the banned IP address, please try again later.';
    }
}

echo json_encode($result);
exit;
