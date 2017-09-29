<?php

// includes and security
include_once('../_local_auth.inc.php');

$configId    = (int) $_REQUEST['configId'];
$configValue = $_REQUEST['configValue'];

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
    $rs = $db->query('UPDATE site_config SET config_value = :configValue WHERE id = :id', array('configValue' => $configValue, 'id'          => $configId));
    if ($rs)
    {
        $result['error'] = false;
        $result['msg']   = 'Configuration item updated.';
    }
    else
    {
        $result['error'] = true;
        $result['msg']   = 'Could not update the configuration item.';
    }
}

echo json_encode($result);
exit;
