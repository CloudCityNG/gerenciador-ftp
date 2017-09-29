<?php

// require login
$Auth->requireUser(WEB_ROOT . '/login.' . SITE_CONFIG_PAGE_EXTENSION);

//setup database
$db = Database::getDatabase(true);

// load folder structure as array
$folderListing = fileFolder::loadAllForSelect($Auth->id);

// handle submission
if ((int) $_REQUEST['submitme'])
{
    // validation
    $folderName     = trim($_REQUEST['folderName']);
    $isPublic       = (int) trim($_REQUEST['isPublic']);
    $enablePassword = false;
    if(isset($_REQUEST['enablePassword']))
    {
        $enablePassword = true;
        $password    = trim($_REQUEST['password']);
    }
	$watermarkPreviews = (int) trim($_REQUEST['watermarkPreviews']);
	$showDownloadLinks = (int) trim($_REQUEST['showDownloadLinks']);
    
    $parentId = (int)$_REQUEST['parentId'];
    if (!strlen($folderName))
    {
        notification::setError(t("please_enter_the_foldername", "Please enter the folder name"));
    }
    elseif(coreFunctions::inDemoMode() == true)
    {
        notification::setError(t("no_changes_in_demo_mode"));
    }
    else
    {
        $editFolderId = null;
        if((int)$_REQUEST['editFolderId'])
        {
            // load existing folder data
            $fileFolder = fileFolder::loadById((int)$_REQUEST['editFolderId']);
            if ($fileFolder)
            {
                // check current user has permission to edit the fileFolder
                if ($fileFolder->userId == $Auth->id)
                {
                    // setup edit folder
                    $editFolderId = $fileFolder->id;
                }
            }
        }
        
        $extraClause = '';
        if($editFolderId !== null)
        {
            $extraClause = ' AND id != '.(int)$editFolderId;
        }
        
        // check for existing folder
        $rs = $db->getRow('SELECT id FROM file_folder WHERE folderName = ' . $db->quote($folderName) . ' AND parentId '.($parentId=='-1'?('IS NULL'):('= '.(int)$parentId)).' AND userId = ' . (int) $Auth->id . $extraClause);
        if ($rs)
        {
            if (COUNT($rs))
            {
                notification::setError(t("already_an_folder_with_that_name", "You already have an folder with that name, please use another"));
            }
        }
    }

    // create the folder
    if (!notification::isErrors())
    {
        // make sure the user owns the parent folder to stop tampering
        if(!isset($folderListing[$parentId]))
        {
            $parentId = 0;
        }
        
        if($parentId == 0)
        {
            $parentId = NULL;
        }

        // get database connection
        $db = Database::getDatabase(true);
        
        // update folder
        if($editFolderId !== null)
        {
            $rs = $db->query('UPDATE file_folder SET folderName = :folderName, parentId = :parentId, isPublic = :isPublic, date_updated = NOW(), watermarkPreviews = :watermarkPreviews, showDownloadLinks = :showDownloadLinks WHERE id = :id', array('folderName' => $folderName, 'isPublic'   => $isPublic, 'parentId'       => $parentId, 'watermarkPreviews'       => $watermarkPreviews, 'showDownloadLinks'       => $showDownloadLinks, 'id'         => $editFolderId));
            if ($rs)
            {
                // success
                notification::setSuccess(t("folder_updated", "Folder updated."));
            }
            else
            {
                notification::setError(t("problem_updating_folder", "There was a problem updating the folder, please try again later."));
            }
        }
        // add folder
        else
        {
            $rs = $db->query('INSERT INTO file_folder (folderName, isPublic, userId, parentId, date_added, watermarkPreviews, showDownloadLinks) VALUES (:folderName, :isPublic, :userId, :parentId, NOW(), :watermarkPreviews, :showDownloadLinks)', array('folderName'     => $folderName, 'isPublic'       => $isPublic, 'userId'         => $Auth->id, 'parentId'         => $parentId, 'watermarkPreviews' => (int)$watermarkPreviews, 'showDownloadLinks' => (int)$showDownloadLinks));
            if ($rs)
            {
                // success
                notification::setSuccess(t("folder_created", "Folder created."));
                $editFolderId = $db->insertId();
            }
            else
            {
                notification::setError(t("problem_adding_folder", "There was a problem adding the folder, please try again later."));
            }
        }
        
        // update password
        if($rs)
        {
            // update password
            $passwordHash = '';
            if($enablePassword == true)
            {
                if((strlen($password)) && ($password != '**********'))
                {
                    $passwordHash = MD5($password);
                }
            }
            else
            {
                // remove existing password
                $passwordHash = NULL;
            }
            
            if(($passwordHash === NULL) || (strlen($passwordHash)))
            {
                $db->query('UPDATE file_folder SET accessPassword = :accessPassword WHERE id = :id', array('accessPassword'=>$passwordHash, 'id' => $editFolderId));
            }
        }
		
		// if the watermark option has changed, ensure we remove any cached previews
		if(($editFolderId !== null) && ((int)$fileFolder->watermarkPreviews != (int)$watermarkPreviews))
        {
			$files = file::loadAllActiveByFolderId($editFolderId);
			if($files)
			{
				$pluginObj = pluginHelper::getInstance('filepreviewer');
				foreach($files AS $file)
				{
					$pluginObj->deleteImagePreviewCache($file['id']);
				}
			}
		}
    }
}

// prepare result
$returnJson = array();
$returnJson['success'] = false;
$returnJson['msg'] = t("problem_updating_folder", "There was a problem updating the folder, please try again later.");
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
$returnJson['folder_id'] = $editFolderId;

// rebuild folder html
$folderArr = array();
if($Auth->loggedIn())
{
	// clear any cache to allow for the new folder
	cache::clearCache('FOLDER_OBJECTS_BY_USERID_'.(int)$Auth->id);
    $folderArr = fileFolder::loadAllForSelect($Auth->id);
}
$returnJson['folder_listing_html'] = '<select id="upload_folder_id" name="upload_folder_id" class="form-control" '.(!$Auth->loggedIn() ? 'DISABLED="DISABLED"' : '').'>';
$returnJson['folder_listing_html'] .= '	<option value="">'.(!$Auth->loggedIn() ? t("index_login_to_enable", "- login to enable -") : t("index_default", "- default -")).'</option>';
if(COUNT($folderArr))
{
	foreach($folderArr AS $id => $folderLabel)
	{
		$returnJson['folder_listing_html'] .= '<option value="'.(int)$id.'"';
		if($fid == (int)$id)
		{
			$returnJson['folder_listing_html'] .= ' SELECTED';
		}
		$returnJson['folder_listing_html'] .= '>'.validation::safeOutputToScreen($folderLabel).'</option>';
	}
}
$returnJson['folder_listing_html'] .= '</select>';

echo json_encode($returnJson);