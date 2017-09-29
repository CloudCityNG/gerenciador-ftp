<?php

// includes and security
define('MIN_ACCESS_LEVEL', 10); // allow moderators
include_once('../_local_auth.inc.php');

$fileId = (int) $_REQUEST['fileId'];

// load file
$rs = array();
$rs['error'] = true;
$rs['msg'] = 'Failed loading file server.';
$filePath = file::getFileDomainAndPath($fileId, null, true);
if($filePath)
{
    $rs['filePath'] = $filePath;
    $rs['error'] = false;
    
    // setup keys for cross site comms
    $keys = crossSiteAction::setData(array('user_id'=>$Auth->id));;
    $rs['csaKey1'] = $keys['key1'];
    $rs['csaKey2'] = $keys['key2'];
}

echo json_encode($rs);