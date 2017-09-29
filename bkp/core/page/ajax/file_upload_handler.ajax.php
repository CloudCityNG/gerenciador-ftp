<?php

error_reporting(E_ALL | E_STRICT);

/* setup includes */
require_once('../../../core/includes/master.inc.php');

// for cross domain access
coreFunctions::allowCrossSiteAjax();

// log
log::breakInLogFile();
log::info('Upload request to uploadHandler.php: '.http_build_query($_REQUEST));

// no caching
header('Pragma: no-cache');
header('Cache-Control: private, no-cache');

// process csaKeys and authenticate user
$csaKey1 = trim($_REQUEST['csaKey1']);
$csaKey2 = trim($_REQUEST['csaKey2']);
if(strlen($csaKey1) && strlen($csaKey1))
{
    crossSiteAction::setAuthFromKeys($csaKey1, $csaKey2, false);
}

// double check user is logged in if required
$Auth = Auth::getAuth();
if (UserPeer::getAllowedToUpload() == false)
{
    echo coreFunctions::createUploadError(t('unavailable', 'Unavailable.'), t('uploading_has_been_disabled', 'Uploading has been disabled.'));
    exit;
}

// check for banned ip
$bannedIP = bannedIP::getBannedType();
if (strtolower($bannedIP) == "uploading")
{
    echo coreFunctions::createUploadError(t('unavailable', 'Unavailable.'), t('uploading_has_been_disabled', 'Uploading has been disabled.'));
    exit;
}

// check that the user has not reached their max permitted uploads
$fileRemaining = UserPeer::getRemainingFilesToday();
if ($fileRemaining == 0)
{
    echo coreFunctions::createUploadError(t('max_uploads_reached', 'Max uploads reached.'), t('reached_maximum_uploads', 'You have reached the maximum permitted uploads for today.'));
    exit;
}

// check the user hasn't reached the maximum storage on their account
if((UserPeer::getAvailableFileStorage($Auth->id) !== NULL) && (UserPeer::getAvailableFileStorage($Auth->id) <= 0))
{
    echo coreFunctions::createUploadError(t('file_upload_space_full', 'File upload space full.'), t('file_upload_space_full_text', 'Upload storage full, please delete some active files and try again.'));
    exit;
}

if($_SERVER['REQUEST_METHOD'] == 'POST')
{
    // make sure the server meets the min upload size limits
    $uploadChunks = 100000000;
    if (isset($_REQUEST['maxChunkSize']))
    {
        $uploadChunks = (int) trim($_REQUEST['maxChunkSize']);
        if ($uploadChunks == 0)
        {
            $uploadChunks = 100000000;
        }
    }
    if (coreFunctions::getPHPMaxUpload() < $uploadChunks)
    {
        echo coreFunctions::createUploadError(t('file_upload_max_upload_php_limit', 'PHP Upload Limit.'), t('file_upload_max_upload_php_limit_text', 'Your PHP limits on [[[SERVER_NAME]]] need to be set to at least [[[MAX_SIZE]]] to allow larger files to be uploaded (currently [[[CURRENT_LIMIT]]]). Contact your host to set.', array('MAX_SIZE' => coreFunctions::formatSize($uploadChunks), 'SERVER_NAME'=>_CONFIG_SITE_HOST_URL, 'CURRENT_LIMIT' => coreFunctions::formatSize(coreFunctions::getPHPMaxUpload()))));
        exit;
    }
}

// on error
if ($fileUploadError !== null)
{
    $fileUploadError          = json_decode($fileUploadError, true);
    $fileUploadError          = $fileUploadError[0];
    $fileUploadError['rowId'] = $rowId;
    // allow sub-domains for remote file servers
    echo "<script>document.domain = '" . _CONFIG_CORE_SITE_HOST_URL . "';</script>";
    $upload_handler->remote_url_event_callback(array("done" => $fileUploadError));
    exit;
}

switch ($_SERVER['REQUEST_METHOD'])
{
    case 'HEAD':
    case 'GET':
        header('Content-Disposition: inline; filename="files.json"');
        $upload_handler = new uploader(
            array(
                'max_chunk_size'=>(int)$_REQUEST['maxChunkSize'],
                'folder_id'=>(int)$_REQUEST['folderId'],
            ));
        $upload_handler->get();
        break;
    case 'POST':
        header('Content-Disposition: inline; filename="files.json"');
        $upload_handler = new uploader(
            array(
                'max_chunk_size'=>(int)$_REQUEST['maxChunkSize'],
                'folder_id'=>(int)$_REQUEST['folderId'],
            ));
        $upload_handler->post();
        break;
    case 'OPTIONS':
        // do nothing
        break;
    default:
        header('HTTP/1.0 405 Method Not Allowed');
}
