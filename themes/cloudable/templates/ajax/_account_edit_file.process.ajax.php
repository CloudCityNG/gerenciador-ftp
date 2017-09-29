<?php

// require login
$Auth->requireUser(WEB_ROOT . '/login.' . SITE_CONFIG_PAGE_EXTENSION);

//setup database
$db = Database::getDatabase(true);

// load file
$fileId = (int)$_REQUEST['fileId'];
$file = file::loadById($fileId);
if(!$file)
{
	// exit
	coreFunctions::output404();
}

// make sure the logged in user owns this file
if($file->userId != $Auth->id)
{
	// exit
	coreFunctions::output404();
}

// load folder structure as array
$folderListing = fileFolder::loadAllForSelect($Auth->id);

// handle submission
if ((int) $_REQUEST['submitme'])
{
    // validation
    $filename     = trim($_REQUEST['filename']);
    $filename = strip_tags($filename);
    $filename = str_replace(array("'", "\""), "", $filename);
    $reset_stats = (int) trim($_REQUEST['reset_stats']);
    $folder      = (int) trim($_REQUEST['folder']);
    
    if (!strlen($filename))
    {
        notification::setError(t("please_enter_the_filename", "Please enter the filename"));
    }
    elseif (coreFunctions::inDemoMode() == true)
    {
        notification::setError(t("no_changes_in_demo_mode"));
    }
    else
    {
        // check for files in same folder
        $foundExistingFile = (int)$db->getValue('SELECT COUNT(id) FROM file WHERE originalFilename = '.$db->quote($filename.'.'.$file->extension).' AND statusId = 1 AND folderId '.((int)$file->folderId>0?('='.$file->folderId):'IS NULL').' AND fileId != '.(int)$file->id);
        if($foundExistingFile)
        {
            notification::setError(t("active_file_with_same_name_found", "Active file with same name found in the same folder. Please ensure the file name is unique."));
        }
    }

    // no errors
    if (!notification::isErrors())
    {
        if($folder == 0)
        {
            $folder = null;
        }

        // update file
        $db = Database::getDatabase(true);
        $rs = $db->query('UPDATE file SET originalFilename = :originalFilename, folderId = :folderId WHERE id = :id', array('originalFilename' => $filename . '.' . $file->extension, 'folderId'         => $folder, 'id'               => $file->id));
        if ($rs)
        {
            // clean stats if needed
            if ($reset_stats == 1)
            {
                $db->query('UPDATE file SET visits = 0 WHERE id = :id', array('id' => $file->id));
                $db->query("DELETE FROM stats WHERE file_id = :id", array('id' => $file->id));
            }
			
			// clear preview cache
			$pluginObj = pluginHelper::getInstance('filepreviewer');
			$pluginObj->deleteImagePreviewCache((int)$file->id);

            // success
            notification::setSuccess(t('file_item_updated', 'File updated.'));
        }
        else
        {
            notification::setError(t("problem_updating_item", "There was a problem updating the item, please try again later."));
        }
    }
}

// prepare result
$returnJson = array();
$returnJson['success'] = false;
$returnJson['msg'] = t("problem_updating_item", "There was a problem updating the item, please try again later.");
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