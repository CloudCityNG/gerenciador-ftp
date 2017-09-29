<?php

/* setup includes */
require_once('../../../core/includes/master.inc.php');

// require login
$Auth->requireUser(WEB_ROOT.'/login.'.SITE_CONFIG_PAGE_EXTENSION);

// prepare result
$result = array();
$result['error'] = false;
$result['msg']   = '';

// load all files
$rows = $db->getRows('SELECT id FROM file WHERE userId = '.(int)$Auth->id.' AND statusId != 1 LIMIT 10000');
if($rows)
{
    foreach($rows AS $row)
    {
        $fileId = $row['id'];

        // deletes
        $db->query('DELETE FROM download_tracker WHERE file_id = '.(int)$fileId);
        $db->query('DELETE FROM file_report WHERE file_id = '.(int)$fileId);
        $db->query('DELETE FROM stats WHERE file_id = '.(int)$fileId);
        $db->query('DELETE FROM file WHERE id = '.(int)$fileId);
    }
}

$result['error'] = false;
$result['msg']   = 'Trash emptied.';

echo json_encode($result);
exit;
