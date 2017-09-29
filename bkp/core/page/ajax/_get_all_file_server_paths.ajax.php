<?php

/* setup includes */
require_once('../../../core/includes/master.inc.php');

// require login
$Auth->requireUser(WEB_ROOT.'/login.'.SITE_CONFIG_PAGE_EXTENSION);

// prepare result
$rs = array();
$rs['error'] = true;
$rs['msg'] = 'Failed loading file server(s) for selected files, please try again later or remove individually.';

// get variables
$fileIds = $_REQUEST['fileIds'];

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
            $filePaths[$filePath]['fileIds'][] = (int)$fileId;
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