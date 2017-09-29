<?php

// allow some time to run
set_time_limit(60*60*4);

// some initial headers
header("HTTP/1.0 200 OK");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Pragma: no-cache");

// setup initial params
$fileId = (int)$_REQUEST['fileId'];
$fileHash = trim($_REQUEST['fileHash']);

// load file
$file = file::loadById($fileId);
if(!$file)
{
	coreFunctions::output404();
}

// check file hash
if($file->getFileHash() != $fileHash)
{
	coreFunctions::output404();
}

// load image viewer plugin details
$pluginDetails  = pluginHelper::pluginSpecificConfiguration('filepreviewer');
$pluginConfig   = $pluginDetails['config'];
$pluginSettings = json_decode($pluginDetails['data']['plugin_settings'], true);

// check if file needs a password and that the user is allowed to download
$folder = null;
if($Auth->id != $file->userId)
{
	if($file->folderId !== NULL)
	{
		$folder = $file->getFolderData();
	}
	if(($folder) && ((bool)$folder->showDownloadLinks == false))
	{
		coreFunctions::redirect(file::getFileUrl($file->id));
	}
	if(($folder) && (strlen($folder->accessPassword) > 0))
	{
		// see if we have it in the session already
		$askPassword = true;
		if(!isset($_SESSION['folderPassword']))
		{
			$_SESSION['folderPassword'] = array();
		}
		elseif(isset($_SESSION['folderPassword'][$folder->id]))
		{
			if($_SESSION['folderPassword'][$folder->id] == $folder->accessPassword)
			{
				$askPassword = false;
			}
		}
		
		if($askPassword)
		{
			// redirect to main page which requests for a password
			coreFunctions::redirect(file::getFileUrl($file->id));
		}
	}
}

// public status
$isPublic = 1;
if(coreFunctions::getOverallPublicStatus($file->userId, $file->folderId, $file->id) == false)
{
    $isPublic = 0;
}

if(($isPublic == 0) && ($Auth->id != $file->userId))
{
	header("HTTP/1.1 401 Unauthorized");
	exit;
}

// create download token and redirect to file
$directDownloadUrl = $file->generateDirectDownloadUrl();
coreFunctions::redirect($directDownloadUrl);