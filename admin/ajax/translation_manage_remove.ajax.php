<?php

// includes and security
include_once('../_local_auth.inc.php');

$languageId = (int) $_REQUEST['languageId'];

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
    $db->query('DELETE FROM language_content WHERE languageId = :languageId', array('languageId' => $languageId));
    $db->query('DELETE FROM language WHERE id = :languageId', array('languageId' => $languageId));
    if ($db->affectedRows() == 1)
    {
        $result['error'] = false;
        $result['msg']   = 'Language successfully removed.';
    }
    else
    {
        $result['error'] = true;
        $result['msg']   = 'Could not remove the language, please try again later.';
    }
}

echo json_encode($result);
exit;
