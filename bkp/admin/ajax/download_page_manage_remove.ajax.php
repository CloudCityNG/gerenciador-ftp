<?php

// includes and security
include_once('../_local_auth.inc.php');

$pageId = (int) $_REQUEST['pageId'];

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
    // get page position which is to be removed
    $pageData = $db->getRow('SELECT page_order, user_level_id FROM download_page WHERE id = '.(int)$pageId);

    // remove page
    $db->query('DELETE FROM download_page WHERE id = :pageId', array('pageId' => $pageId));
    if ($db->affectedRows() == 1)
    {
        // fix page ordering
        $db->query('UPDATE download_page SET page_order = page_order-1 WHERE user_level_id = '.(int)$pageData['user_level_id'].' AND page_order > '.(int)$pageData['page_order']);
        
        $result['error'] = false;
        $result['msg']   = 'Page successfully removed from download process for that user type.';
    }
    else
    {
        $result['error'] = true;
        $result['msg']   = 'Could not remove the page from the user type, please try again later.';
    }
}

echo json_encode($result);
exit;
