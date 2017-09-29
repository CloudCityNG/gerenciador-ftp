<?php

error_reporting(E_ALL | E_STRICT);
set_time_limit(60*60*4); // allow for a long time to get the files

/* setup includes */
require_once('../../../core/includes/master.inc.php');

// log
log::breakInLogFile();
log::info('Remote upload request to urlUploadHandler.php: ' . http_build_query($_REQUEST));

// no caching
header('Pragma: no-cache');
header('Cache-Control: private, no-cache');

// get url
$url   = !empty($_REQUEST["url"]) && stripslashes($_REQUEST["url"]) ? stripslashes($_REQUEST["url"]) : null;
$rowId = (int) $_REQUEST['rowId'];

// process csaKeys and authenticate user
$csaKey1 = trim($_REQUEST['csaKey1']);
$csaKey2 = trim($_REQUEST['csaKey2']);
if(strlen($csaKey1) && strlen($csaKey2))
{
    crossSiteAction::setAuthFromKeys($csaKey1, $csaKey2, false);
}

// double check user is logged in if required
$Auth = Auth::getAuth();
$userId = null;
if ($Auth->loggedIn())
{
    $userId = $Auth->id;
}
$folderId = (int)$_REQUEST['folderId'];

// start uploader class
$upload_handler  = new uploader(array(
    'folder_id'=>(int)$folderId,
    'user_id'=>(int)$userId,
	'upload_source'=>'remote',
));
$fileUploadError = null;

// double check user is logged in if required
$Auth = Auth::getAuth();

// check the url structure is valid
if (($fileUploadError === null) && (filter_var($url, FILTER_VALIDATE_URL) === false))
{
    $fileUploadError = coreFunctions::createUploadError(t('url_is_invalid', 'Url is invalid.'), t('url_is_invalid_please_check', 'The structure of the url is invalid, please check and try again.'));
}

// check user is allowed to upload
if (($fileUploadError === null) && (UserPeer::getAllowedToUpload() == false))
{
    $fileUploadError = coreFunctions::createUploadError(t('unavailable', 'Unavailable.'), t('uploading_has_been_disabled', 'Uploading has been disabled.'));
}

// check for banned ip
$bannedIP = bannedIP::getBannedType();
if (($fileUploadError === null) && (strtolower($bannedIP) == "uploading"))
{
    $fileUploadError = coreFunctions::createUploadError(t('unavailable', 'Unavailable.'), t('uploading_has_been_disabled', 'Uploading has been disabled.'));
}

// check that the user has not reached their max permitted uploads
$fileRemaining = UserPeer::getRemainingFilesToday();
if (($fileUploadError === null) && ($fileRemaining == 0))
{
    $fileUploadError = coreFunctions::createUploadError(t('max_uploads_reached', 'Max uploads reached.'), t('reached_maximum_uploads', 'You have reached the maximum permitted uploads for today.'));
}

// check the user hasn't reached the maximum storage on their account
if (($fileUploadError === null) && ((UserPeer::getAvailableFileStorage($Auth->id) !== NULL) && (UserPeer::getAvailableFileStorage($Auth->id) <= 0)))
{
    $fileUploadError = coreFunctions::createUploadError(t('file_upload_space_full', 'File upload space full.'), t('file_upload_space_full_text', 'Upload storage full, please delete some active files and try again.'));
}

// on error
if ($fileUploadError !== null)
{
    $fileUploadError          = json_decode($fileUploadError, true);
    $fileUploadError          = $fileUploadError[0];
    $fileUploadError['rowId'] = $rowId;
    // allow sub-domains for remote file servers
	echo coreFunctions::getDocumentDomainScript();
    $upload_handler->remote_url_event_callback(array("done" => $fileUploadError));
    exit;
}

// if background uploading, for logged in users only
if((SITE_CONFIG_REMOTE_URL_DOWNLOAD_IN_BACKGROUND == 'yes') && ($Auth->loggedIn()))
{
	uploader::addUrlToBackgroundQueue($url, $Auth->id, $folderId);
	// allow sub-domains for remote file servers
	echo coreFunctions::getDocumentDomainScript();
	$upload_handler->remote_url_event_callback(array("done" => 'Done'));
	exit;
}

// include plugin code
$params = pluginHelper::includeAppends('url_upload_handler.php', array('url'   => $url, 'rowId' => $rowId));
$url    = $params['url'];

// 1KB of initial data, required by Webkit browsers
echo "<span>" . str_repeat("0", 1000) . "</span>";

// allow sub-domains for remote file servers
echo coreFunctions::getDocumentDomainScript();

$upload_handler->handleRemoteUrlUpload($url, $rowId);
