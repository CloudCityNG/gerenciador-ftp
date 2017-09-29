<?php

// setup includes
require_once ('../../../core/includes/master.inc.php');

// require login
$Auth->requireUser(WEB_ROOT . '/login.' . SITE_CONFIG_PAGE_EXTENSION);

$gRemoveUrlId = (int)$_REQUEST['gRemoveUrlId'];

// prepare result
$result = array();
$result['error'] = false;
$result['msg'] = '';

// load url details
$urlData = $db->getRow('SELECT id FROM remote_url_download_queue WHERE id=' .
    (int)$gRemoveUrlId . ' AND user_id = '.(int)$Auth->id.' LIMIT 1');
if (!$urlData)
{
    $result['error'] = true;
    $result['msg'] = t("could_not_find_url_download",
        "Could not find url download.");
}
else
{
    // delete record
    $db->query('DELETE FROM remote_url_download_queue WHERE id = :id',
        array('id' => $urlData['id']));
    if ($db->affectedRows() == 1)
    {
        $result['error'] = false;
        $result['msg'] = 'Url download removed.';
    }
    else
    {
        $result['error'] = true;
        $result['msg'] = 'Could not remove the download task, please try again later.';
    }
}

echo json_encode($result);
exit;
