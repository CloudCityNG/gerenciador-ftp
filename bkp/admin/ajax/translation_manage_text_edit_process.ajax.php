<?php

// includes and security
include_once('../_local_auth.inc.php');

$translation_item_id = (int) $_REQUEST['translation_item_id'];
$translated_content  = trim($_REQUEST['translated_content']);

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
	// update translation and also make sure it's locked so language imports don't overwrite it
    $rs = $db->query('UPDATE language_content SET content = :content, is_locked = 1 WHERE id = :id', array('content' => $translated_content, 'id'      => $translation_item_id));
    if ($rs)
    {
        $result['error'] = false;
        $result['msg']   = 'Translation updated.';
    }
    else
    {
        $result['error'] = true;
        $result['msg']   = 'Could not update the translation.';
    }
}

echo json_encode($result);
exit;
