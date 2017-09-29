<?php

// require login
$Auth->requireUser(WEB_ROOT . '/login.' . SITE_CONFIG_PAGE_EXTENSION);

// prepare result
$result          = array();
$result['error'] = false;
$result['msg']   = 'Files moved.';

if (_CONFIG_DEMO_MODE == true)
{
    $result['error'] = true;
    $result['msg']   = t("no_changes_in_demo_mode");
}
elseif(corefunctions::getUsersAccountLockStatus($Auth->id) == 1)
{
    $result['error'] = true;
    $result['msg']   = t('account_locked_folder_edit_error_message', 'This account has been locked, please unlock the account to regain full functionality.');
}
else
{
    $folderId = NULL;

    $fileFolder = fileFolder::loadById((int) $_REQUEST['folderId']);
    if ($fileFolder)
    {
        // check user id
        if ($fileFolder->userId == $Auth->id)
        {
            $folderId = (int) $fileFolder->id;
        }
    }

    // update files
    $fileIds = $_REQUEST['fileIds'];
    if (COUNT($fileIds))
    {
        $filteredIds = array();
        foreach ($fileIds AS $fileId)
        {
            $filteredIds[] = (int) $fileId;
        }

        // load all original filenames to check for duplicates
        $files             = $db->getRows('SELECT originalFilename FROM file WHERE id IN (' . implode(',', $filteredIds) . ') AND userId = ' . $Auth->id);
        $originalFilenames = array();
        foreach ($files AS $file)
        {
            $originalFilenames[] = $db->quote($file['originalFilename']);
        }

        // make sure files don't exist already in folder
        $total = (int) $db->getValue('SELECT COUNT(id) AS total FROM file WHERE originalFilename IN (' . implode(',', $originalFilenames) . ') AND statusId = 1 AND folderId ' . ($folderId == NULL ? '= NULL' : '= ' . (int) $folderId) . ' AND userId = ' . $Auth->id);
        if ($total > 0)
        {
            $result['error'] = true;
            $result['msg']   = t("items_with_same_name_in_folder", "There are already [[[TOTAL_SAME]]] file(s) with the same filename in that folder. Files can not be moved.", array('TOTAL_SAME' => $total));
        }
        else
        {
            $db->query('UPDATE file SET folderId ' . ($folderId == NULL ? '= NULL' : '= ' . (int) $folderId) . ' WHERE id IN (' . implode(',', $filteredIds) . ') AND userId = ' . $Auth->id);
			
			// clear file preview cache
			if(COUNT($filteredIds))
			{
				$pluginObj = pluginHelper::getInstance('filepreviewer');
				foreach($filteredIds AS $fileId)
				{
					$pluginObj->deleteImagePreviewCache((int)$fileId);
				}
			}
        }
    }
}

echo json_encode($result);
exit;
