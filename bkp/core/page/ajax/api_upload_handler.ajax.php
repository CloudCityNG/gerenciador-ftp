<?php

// note: An API key is required for this script to work.
error_reporting(E_ALL | E_STRICT);

/* setup includes */
require_once('../../../core/includes/master.inc.php');

// for cross domain access
coreFunctions::allowCrossSiteAjax();

// pickup action
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'upload';
log::info('POST request to API Uploader: '.print_r($_REQUEST));
// validate the api key
$apiKey = $_REQUEST['api_key'];
if (strlen($apiKey) == 0)
{
    $rs    = array();
    $rs[0] = array('error' => 'Please set the users API Key.');
    die(json_encode($rs));
}
else
{
    // check API key in db
    $userId = $db->getValue("SELECT id FROM users WHERE apikey = " . $db->quote($apiKey) . " LIMIT 1");
    if (!$userId)
    {
        $rs    = array();
        $rs[0] = array('error' => 'Invalid API Key.');
        die(json_encode($rs));
    }
}

switch ($action)
{
    // update raw file content
    case 'update_file_content':
        // look for file id
        if (!isset($_REQUEST['file_id']))
        {
            $rs    = array();
            $rs[0] = array('error' => 'File id not found.');
            die(json_encode($rs));
        }

        // load file
        $file = file::loadById((int) $_REQUEST['file_id']);
        if (!$file)
        {
            $rs    = array();
            $rs[0] = array('error' => 'Failed finding file.');
            die(json_encode($rs));
        }

        // check that the current user owns the file
        if ($file->userId != $userId)
        {
            $rs    = array();
            $rs[0] = array('error' => 'Current user does not own the file.');
            die(json_encode($rs));
        }

        // setup uploader
        $upload_handler = new uploader(array(
            'user_id'         => (int) $userId,
            'fail_zero_bytes' => false,
            'min_file_size'   => 0,
			'upload_source'   => 'api',
        ));

        // replace stored file
        $fileUpload        = new stdClass();
        $fileUpload->error = null;
        $rs                = $upload_handler->_storeFile($fileUpload, $_FILES['files']['tmp_name']);
        $file_size         = $rs['file_size'];
        $file_path         = $rs['file_path'];
        $uploadServerId    = $rs['uploadServerId'];
        $fileUpload        = $rs['fileUpload'];
        $relativeFilePath  = $rs['relative_file_path'];
        $fileHash          = $rs['fileHash'];
        $mimeType          = file::estimateMimeTypeFromExtension($file->originalFilename);
        if (strlen($fileUpload->error))
        {
            $rs    = array();
            $rs[0] = array('error' => 'Error storing file: ' . $fileUpload->error);
            die(json_encode($rs));
        }

        // update existing file record in database
        $db = Database::getDatabase(true);
        $db->query('UPDATE file SET fileSize = :fileSize, localFilePath = :localFilePath, fileHash = :fileHash, fileType = :fileType WHERE id = :id', array('fileSize' => $file_size, 'localFilePath' => $relativeFilePath, 'fileHash' => $fileHash, 'fileType' => $mimeType, 'id'       => $file->id));
        
        // output success
        $rs    = array();
        $rs[0] = array('success' => 'File updated.');
        die(json_encode($rs));
        break;
    // upload
    default:
        // setup uploader, allow zero file sizes
        if (!defined('PHP_INT_SIZE'))
        {
            define('PHP_INT_SIZE', 4);
        }
        $upload_handler = new uploader(array(
            'folder_id'       => (int) $_REQUEST['folderId'],
            'user_id'         => (int) $userId,
            'fail_zero_bytes' => false,
            'min_file_size'   => 0,
            'max_file_size'   => PHP_INT_SIZE===8?1072870912000:2147483647,
			'upload_source'   => 'api',
        ));

        // setup auth for current user
        $Auth = Auth::getAuth();
        $Auth->impersonate($userId);

        header('Content-Disposition: inline; filename="files.json"');
        switch ($_SERVER['REQUEST_METHOD'])
        {
            case 'POST':
                $upload_handler->post();
                break;
            case 'OPTIONS':
                // do nothing
                break;
            default:
                header('HTTP/1.0 405 Method Not Allowed');
        }
        break;
}
