<?php

/* setup includes */
require_once('../../../core/includes/master.inc.php');

// require login
$Auth->requireUser(WEB_ROOT . '/login.' . SITE_CONFIG_PAGE_EXTENSION);

// prepare result
$rs          = array();
$rs['error'] = true;
$rs['msg']   = 'Failed loading selected files, please try again later.';

// get variables
$fileIds = $_REQUEST['fileIds'];

// loop file ids and get paths
$filePaths = array();
if (COUNT($fileIds))
{
    foreach ($fileIds AS $fileId)
    {
        // load file
        $file = file::loadById($fileId);
		
		// only allow users to duplicate their own files
		if($file->userId != $Auth->id)
		{
			continue;
		}
		
		// create unique filename
		$foundExistingFile = 1;
		$tracker           = 2;
		$newFilename       = $file->originalFilename;
		while ($foundExistingFile >= 1)
		{
			$foundExistingFile = (int) $db->getValue('SELECT COUNT(id) FROM file WHERE originalFilename = ' . $db->quote($newFilename) . ' AND statusId = 1 AND userId = ' . (int)$file->userId . ' AND folderId ' . ($file->folderId === NULL ? 'IS NULL' : ('= ' . $file->folderId)));
			if ($foundExistingFile >= 1)
			{
				$newFilename = substr($file->originalFilename, 0, strlen($file->originalFilename) - strlen($file->extension) - 1) . ' (' . $tracker . ').' . $file->extension;
				$tracker++;
			}
		}

        // setup properties
		$copyProperties = array();
		$copyProperties['originalFilename'] = $newFilename;
		$copyProperties['folderId'] = ((int) $file->folderId ? $file->folderId : NULL);
		$copyProperties['userId'] = (int)$file->userId;
		
		// duplicate
        $newFile = $file->duplicateFile($copyProperties);

        // if any previews exist, copy them
        $mediaConverterScreenPath = CACHE_DIRECTORY_ROOT . '/plugins/mediaconverter/' . $file->id . '/original_thumb.jpg';
        if (file_exists($mediaConverterScreenPath))
        {
            $newPath     = CACHE_DIRECTORY_ROOT . '/plugins/mediaconverter/' . $newFile->id . '/';
            mkdir($newPath, 0777, true);
            $newFilePath = $newPath . 'original_thumb.jpg';
            copy($mediaConverterScreenPath, $newFilePath);
        }
    }

    $rs['error'] = false;
    $rs['msg']   = t('file_manager_files_duplicated_success_message', 'Files duplicated in current folder.');
}

echo json_encode($rs);