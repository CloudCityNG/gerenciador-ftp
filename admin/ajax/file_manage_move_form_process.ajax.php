<?php

// includes and security
include_once('../_local_auth.inc.php');

$gFileIds    = $_REQUEST['gFileIds'];
$serverIds = (int)$_REQUEST['serverIds'];

// preload file servers for lookups
$fileServerArr = array();
$fileServers = $db->getRows('SELECT file_server.id, file_server.serverType FROM file_server WHERE (statusId=2 OR statusId=3) AND serverType IN (\'local\', \'direct\') ORDER BY serverLabel ASC');
foreach($fileServers AS $fileServer)
{
    $fileServerArr[] = $fileServer['id'];
}

// prepare result
$result          = array();
$result['error'] = false;
$result['msg']   = '';

// validate submission
if ($serverIds == 0)
{
    $result['error'] = true;
    $result['msg']   = adminFunctions::t("please_select_the_server", "Please select the new server.");
}
elseif (_CONFIG_DEMO_MODE == true)
{
    $result['error'] = true;
    $result['msg']   = adminFunctions::t("no_changes_in_demo_mode");
}

$errorTracker = array();
if (strlen($result['msg']) == 0)
{
    // load server details
	$server = file::loadServerDetails($serverIds);
	
	// loop files and add to move queue
	foreach($gFileIds AS $gFileId)
	{
		$file = file::loadById($gFileId);
        
        // ignore files on non local or direct file servers
        if(in_array($file->serverId, $fileServerArr))
        {
            $file->scheduleServerMove($server['id']);
        }
	}
    
    // finish up
	$result['error'] = false;
	$result['msg']   = 'File move has been scheduled. The file(s) will be moved when processed by the <a href="file_manage_action_queue.php">file action queue</a>.';
}

echo json_encode($result);
exit;
