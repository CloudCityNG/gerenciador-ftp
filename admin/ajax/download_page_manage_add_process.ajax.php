<?php

// includes and security
include_once('../_local_auth.inc.php');

$download_page = trim($_REQUEST['download_page']);
$user_level_id = (int)$_REQUEST['user_level_id'];
$page_order = (int)$_REQUEST['page_order'];
$optional_timer = (int)$_REQUEST['optional_timer'];
$additional_javascript_code = trim($_REQUEST['additional_javascript_code']);
$additional_settings = '';
if($optional_timer > 0)
{
    $additional_settings = json_encode(array('download_wait' => $optional_timer));
}

if (isset($_REQUEST['pageId']))
{
    $pageId = (int) $_REQUEST['pageId'];
}

// check highest order value
$highestOrder = $db->getValue('SELECT page_order FROM download_page WHERE user_level_id = '.(int)$user_level_id.' ORDER BY page_order DESC LIMIT 1');
if($highestOrder)
{
    if(($page_order) > (int)$highestOrder)
    {
        $page_order = (int)$highestOrder;
    }
}

// prepare result
$result = array();
$result['error'] = false;
$result['msg']   = '';

if (_CONFIG_DEMO_MODE == true)
{
    $result['error'] = true;
    $result['msg']   = adminFunctions::t("no_changes_in_demo_mode");
}
elseif($page_order <= 0)
{
    $result['error'] = true;
    $result['msg']   = adminFunctions::t("download_page_page_order_can_not_be_zero_or_less", "Page order can not be zero or less.");
}
else
{
    if ($pageId)
    {
        // get page position which is to be removed
        $pageData = $db->getRow('SELECT page_order, user_level_id FROM download_page WHERE id = '.(int)$pageId);

        // fix page ordering
        if($page_order < (int)$pageData['page_order'])
        {
            $db->query('UPDATE download_page SET page_order = page_order+1 WHERE user_level_id = '.(int)$user_level_id.' AND page_order >= '.(int)$page_order.' AND page_order < '.(int)$pageData['page_order']);
        }
        if($page_order > (int)$pageData['page_order'])
        {
            $db->query('UPDATE download_page SET page_order = page_order-1 WHERE user_level_id = '.(int)$user_level_id.' AND page_order > '.(int)$pageData['page_order'].' AND page_order <= '.(int)$page_order);
        }
        
        // update
        $rs = $db->query('UPDATE download_page SET download_page = :download_page, user_level_id = :user_level_id, page_order = :page_order, additional_javascript_code = :additional_javascript_code, additional_settings = :additional_settings WHERE id = :id', array('download_page' => $download_page, 'user_level_id' => $user_level_id, 'page_order' => $page_order, 'additional_javascript_code' => $additional_javascript_code, 'additional_settings' => $additional_settings, 'id' => $pageId));
        if (!$rs)
        {
            $result['error'] = true;
            $result['msg']   = adminFunctions::t("error_problem_download_page_record_update", "There was a problem updating the download page, please try again.");
        }
        else
        {
            $result['error'] = false;
            $result['msg']   = 'Download page has been updated.';
        }
    }
    else
    {
        // fix page ordering
        $db->query('UPDATE download_page SET page_order = page_order+1 WHERE user_level_id = '.(int)$user_level_id.' AND page_order >= '.(int)$page_order);
        
        // add the new language
        $dbInsert = new DBObject("download_page", array("download_page", "user_level_id", "page_order", "additional_javascript_code", "additional_settings"));
        $dbInsert->download_page = $download_page;
        $dbInsert->user_level_id = $user_level_id;
        $dbInsert->page_order = $page_order;
        $dbInsert->additional_javascript_code = $additional_javascript_code;
        $dbInsert->additional_settings = $additional_settings;
        $rs = $dbInsert->insert();
        if (!$rs)
        {
            $result['error'] = true;
            $result['msg']   = adminFunctions::t("error_problem_download_page_record", "There was a problem adding the download page, please try again.");
        }
        else
        {
            $result['error'] = false;
            $result['msg']   = 'Download page has been added for user type.';
        }
    }
}

echo json_encode($result);
exit;
