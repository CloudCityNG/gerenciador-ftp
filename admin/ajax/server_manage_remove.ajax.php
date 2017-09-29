<?php

// includes and security
include_once('../_local_auth.inc.php');

$serverId = (int) $_REQUEST['serverId'];
$serverLabel = $db->getValue('SELECT serverLabel FROM file_server WHERE id='.$serverId);

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
    // load any files on the server
    $files = $db->getRows('SELECT id, statusId FROM file WHERE serverId = '.$serverId);
    foreach($files AS $file)
    {
        // get file object
        $fileObj = file::loadById($file['id']);
        
        // only active files
        if($file['statusId'] == 1)
        {
            // remove file
            $fileObj->removeBySystem();
        }
        
        // remove any statistical data
        $db->query('DELETE FROM stats WHERE file_id = :fileId', array('fileId' => $file['id']));
    
        // remove any download tracker data
        $db->query('DELETE FROM download_tracker WHERE file_id = :fileId', array('fileId' => $file['id']));

        // remove any file
        $db->query('DELETE FROM file WHERE id = :fileId', array('fileId' => $file['id']));
    }
    
    // make sure the file server is not set as the current server to use for uploads
    if($serverLabel == SITE_CONFIG_DEFAULT_FILE_SERVER)
    {
        $db->query('UPDATE site_config SET config_value = '.$db->quote('Local Default').' WHERE config_key=\'default_file_server\' LIMIT 1');
    }

    // delete the server record
    $db->query('DELETE FROM file_server WHERE id = :serverId', array('serverId' => $serverId));
    if ($db->affectedRows() == 1)
    {
        $result['error'] = false;
        $result['msg']   = 'Server, files and any relating data removed.';
    }
    else
    {
        $result['error'] = true;
        $result['msg']   = 'Could not remove the file server, please try again later.';
    }
}

echo json_encode($result);
exit;
