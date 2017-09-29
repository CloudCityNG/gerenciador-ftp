<?php
// includes and security
include_once('../_local_auth.inc.php');

// prepare result
$rs = array();
$rs['error'] = true;
$rs['msg'] = 'Failed loading file server(s) for selected files, please try again later or remove individually.';

// security, pulled locally so we can provide useful error messages over ajax
if($Auth->hasAccessLevel(10) == false)
{
    $rs['msg'] = 'Could not authenticate user.';
    echo json_encode($rs);
    die();
}

// if we only know the user id
$fileIds = array();
if(isset($_REQUEST['userId']))
{
    $fileRows = $db->getRows('SELECT id FROM file WHERE userId = '.(int)$_REQUEST['userId']);
    if($fileRows)
    {
        foreach($fileRows AS $fileRow)
        {
            $fileIds[] = $fileRow['id'];
        }
    }
    
    // don't error if no files
    if(COUNT($fileIds) == 0)
    {
        $rs['filePaths'] = array();
        $rs['error'] = false;
        $rs['msg'] = '';
    }
}
else
{
    // get variables
    $fileIds = $_REQUEST['fileIds'];
}

// loop file ids and get paths
$filePaths = array();
if(COUNT($fileIds))
{
    foreach($fileIds AS $fileId)
    {
        $filePath = file::getFileDomainAndPath($fileId, null, true);
        if($filePath)
        {
            if(!is_array($filePaths[$filePath]))
            {
                // setup keys for cross site comms
                $keys = crossSiteAction::setData(array('user_id'=>$Auth->id));;
    
                $filePaths[$filePath] = array();
                $filePaths[$filePath]['fileIds'] = array();
                $filePaths[$filePath]['csaKey1'] = $keys['key1'];
                $filePaths[$filePath]['csaKey2'] = $keys['key2'];
            }
            $filePaths[$filePath]['fileIds'][] = $fileId;
        }
    }
}

if(COUNT($filePaths))
{
    $rs['filePaths'] = $filePaths;
    $rs['error'] = false;
    $rs['msg'] = '';
}

echo json_encode($rs);