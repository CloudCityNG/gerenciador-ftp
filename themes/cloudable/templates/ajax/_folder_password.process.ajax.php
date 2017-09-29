<?php

//setup database
$db = Database::getDatabase();

// handle submission
if ((int) $_REQUEST['submitme'])
{
    // validation
    $folderId = (int)$_REQUEST['folderId'];
    $folderPassword = trim($_REQUEST['folderPassword']);
	
	// load folder
	$fileFolder = fileFolder::loadById($folderId);
	if(!$fileFolder)
	{
		notification::setError(t("problem_loading_folder", "There was a problem loading the folder, please try again later."));
	}

    // check password
    if (!notification::isErrors())
    {
		if (MD5($folderPassword) == $fileFolder->accessPassword)
		{
			// successful
			if(!isset($_SESSION['folderPassword']))
			{
				$_SESSION['folderPassword'] = array();
			}
			$_SESSION['folderPassword'][$fileFolder->id] = $fileFolder->accessPassword;
		}
		else
		{
			notification::setError(t("folder_password_is_invalid", "The folder password is invalid"));
		}
    }
}

// prepare result
$returnJson = array();
$returnJson['success'] = false;
$returnJson['msg'] = t("problem_updating_folder", "There was a problem accessing the folder, please try again later.");
if (notification::isErrors())
{
    // error
    $returnJson['success'] = false;
    $returnJson['msg'] = implode('<br/>', notification::getErrors());
}
else
{
    // success
    $returnJson['success'] = true;
    $returnJson['msg'] = implode('<br/>', notification::getSuccess());
}

echo json_encode($returnJson);