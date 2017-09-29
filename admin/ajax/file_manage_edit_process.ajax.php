<?php

// includes and security
define('MIN_ACCESS_LEVEL', 10); // allow moderators
include_once('../_local_auth.inc.php');

// get params
$existing_file_id = (int) $_REQUEST['existing_file_id'];
$filename         = trim($_REQUEST['filename']);
$filename         = strip_tags($filename);
$filename         = str_replace(array("'", "\""), "", $filename);
$file_owner       = trim($_REQUEST['file_owner']);
$short_url        = trim($_REQUEST['short_url']);
$enablePassword   = $_REQUEST['enablePassword'] == 'true' ? true : false;
$password         = trim($_REQUEST['password']);
$mime_type        = trim($_REQUEST['mime_type']);
$min_user_level   = trim($_REQUEST['min_user_level']);
$admin_notes      = trim($_REQUEST['admin_notes']);

// prepare result
$result          = array();
$result['error'] = false;
$result['msg']   = '';

// load file
$file = file::loadById($existing_file_id);
if (!$file)
{
    $result['error'] = true;
    $result['msg']   = 'Failed loading file to edit.';
    echo json_encode($result);
    exit;
}

// validate submission
if (strlen($filename) == 0)
{
    $result['error'] = true;
    $result['msg']   = adminFunctions::t("please_enter_the_filename", "Please enter the filename");
}
elseif (_CONFIG_DEMO_MODE == true)
{
    $result['error'] = true;
    $result['msg']   = adminFunctions::t("no_changes_in_demo_mode");
}
else
{
    // double check for files with the same name in the same folder
    $foundExistingFile = (int) $db->getValue('SELECT COUNT(id) FROM file WHERE originalFilename = ' . $db->quote($filename . '.' . $file->extension) . ' AND statusId = 1 AND folderId ' . ((int) $file->folderId > 0 ? ('=' . $file->folderId) : 'IS NULL') . ' AND fileId != ' . (int) $file->id);
    if ($foundExistingFile)
    {
        $result['error'] = true;
        $result['msg']   = adminFunctions::t("active_file_with_same_name_found", "Active file with same name found in the same folder. Please ensure the file name is unique.");
    }
}

if (strlen($result['msg']) == 0)
{
    // lookup user id if set
    $userId = NULL;
    if (strlen($file_owner))
    {
        $userId = $db->getValue('SELECT id FROM users WHERE username = ' . $db->quote($file_owner) . ' LIMIT 1');
        if (!$userId)
        {
            $result['error'] = true;
            $result['msg']   = adminFunctions::t("edit_file_could_not_find_username", "Could not find file owner username. Leave blank to set the file with no owner.");
        }
    }
}

if (strlen($result['msg']) == 0)
{
    // make sure there's no disallowed characters in the short url
    if (validation::containsInvalidCharacters($short_url, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ12345678900'))
    {
        $result['error'] = true;
        $result['msg']   = adminFunctions::t("edit_file_short_url_is_invalid", "Short url structure is invalid. Only alphanumeric values are allowed.");
    }
    else
    {
        // check short url not already used
        $existingFileCheck = $db->getValue('SELECT id FROM file WHERE id != ' . (int) $file->id . ' AND shortUrl = ' . $db->quote($short_url));
        if ($existingFileCheck)
        {
            $result['error'] = true;
            $result['msg']   = adminFunctions::t("edit_file_file_with_same_short_url_exist", "Short url already exists on another file.");
        }
    }
}

if (strlen($result['msg']) == 0)
{
    $accessPassword = NULL;
    if ($enablePassword === true)
    {
        $accessPassword = $file->accessPassword != NULL ? $file->accessPassword : NULL;
        if ((strlen($password)) && ($password != '**********'))
        {
            $accessPassword = MD5($password);
        }
    }
}

// no errors
if (strlen($result['msg']) == 0)
{
    // update the existing record
    $dbUpdate                   = new DBObject("file", array("originalFilename", "userId", "shortUrl", "accessPassword", "fileType", "minUserLevel", "adminNotes"), 'id');
    $dbUpdate->originalFilename = $filename . '.' . $file->extension;
    $dbUpdate->userId           = $userId;
    $dbUpdate->shortUrl         = $short_url;
    $dbUpdate->accessPassword   = $accessPassword;
    $dbUpdate->fileType         = $mime_type;
    $dbUpdate->minUserLevel     = strlen($min_user_level) ? (int) $min_user_level : NULL;
    $dbUpdate->adminNotes       = $admin_notes;
    $dbUpdate->id               = $existing_file_id;
    $dbUpdate->update();

    $result['error'] = false;
    $result['msg']   = 'File \'' . $dbUpdate->originalFilename . '\' updated.';
}

echo json_encode($result);
exit;
