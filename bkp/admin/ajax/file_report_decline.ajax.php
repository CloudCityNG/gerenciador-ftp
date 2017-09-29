<?php

// includes and security
define('MIN_ACCESS_LEVEL', 10); // allow moderators
include_once('../_local_auth.inc.php');

$reportId = (int) $_REQUEST['reportId'];

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
    // update to cancelled
    $db->query('UPDATE file_report SET report_status = \'cancelled\' WHERE id = :reportId LIMIT 1', array('reportId' => $reportId));
    if ($db->affectedRows() == 1)
    {
        $result['error'] = false;
        $result['msg']   = 'Report cancelled.';
    }
    else
    {
        $result['error'] = true;
        $result['msg']   = 'Could not cancel report, please try again later.';
    }
}

echo json_encode($result);
exit;
