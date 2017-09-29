<?php

// includes
define('ADMIN_IGNORE_LOGIN', true);
include_once('../_local_auth.inc.php');

// process csaKeys and authenticate user
$csaKey1 = isset($_REQUEST['csaKey1'])?trim($_REQUEST['csaKey1']):'';
$csaKey2 = isset($_REQUEST['csaKey2'])?trim($_REQUEST['csaKey2']):'';
$dataArr = crossSiteAction::getData($csaKey1, $csaKey2);
if (!$dataArr)
{
	$Auth->requireAdmin();
}

// else user is fine
crossSiteAction::deleteData($csaKey1, $csaKey2);

$result = array();
$result['server_doc_root'] = DOC_ROOT;

echo json_encode($result);
exit;
