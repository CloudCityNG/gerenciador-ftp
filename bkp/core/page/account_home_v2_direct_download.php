<?php

// allow some time to run
set_time_limit(60*60*4);

/* setup includes */
require_once('../../core/includes/master.inc.php');

// require login
$Auth->requireUser(WEB_ROOT.'/login.'.SITE_CONFIG_PAGE_EXTENSION);

// some initial headers
header("HTTP/1.0 200 OK");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Pragma: no-cache");

// setup initial params
$fileId = (int)$_REQUEST['fileId'];

// make sure user owns file
$fileData = $db->getRow('SELECT * FROM file WHERE id = '.$fileId.' AND userId = '.$Auth->id.' LIMIT 1');
if(!$fileData)
{
	coreFunctions::output404();
}

// create download token and redirect to file
$file = file::hydrate($fileData);
$directDownloadUrl = $file->generateDirectDownloadUrl();
coreFunctions::redirect($directDownloadUrl);