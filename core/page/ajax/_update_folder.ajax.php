<?php

error_reporting(E_ALL | E_STRICT);

// setup includes
require_once('../../../core/includes/master.inc.php');

// require login
$Auth->requireUser(WEB_ROOT.'/login.'.SITE_CONFIG_PAGE_EXTENSION);

// receive varables
$shortUrl = trim($_REQUEST['shortUrl']);
$folderId = (int)$_REQUEST['folderId'];

// load file
$file = file::loadByShortUrl($shortUrl);
if(!$file)
{
    // failed lookup of file
    exit;
}

// check current user has permission to edit file
if ($file->userId != $Auth->id)
{
    exit;
}

// load folder
if($folderId > 0)
{
    $fileFolder = fileFolder::loadById($folderId);
    if (!$fileFolder)
    {
        // failed lookup of the fileFolder
        exit;
    }

    // check current user has permission to edit the fileFolder
    if ($fileFolder->userId != $Auth->id)
    {
        exit;
    }
}

// finally, update the folder
$file->updateFolder($folderId);