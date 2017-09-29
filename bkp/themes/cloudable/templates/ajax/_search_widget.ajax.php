<?php

// setup result array
$rs = array();

// get variables
$rs = array();
$type = $_REQUEST["type"];
$query = $_REQUEST["query"];
if(strlen($query) == 0)
{
	echo json_encode($rs);
	exit;
}

// only images
$imageExtArr = file::getImageExtStringForSql();

switch($type)
{
	case 'images':
		// lookup images
		$images = $db->getRows('SELECT file.*, users.username, file_folder.folderName FROM file LEFT JOIN users ON file.userId = users.id LEFT JOIN file_folder ON file.folderId = file_folder.id WHERE (file.originalFilename LIKE "%'.$db->escape($query).'%" OR file.shortUrl LIKE "%'.$db->escape($query).'%") AND file.statusId = 1 AND file.userId = '.(int)$Auth->id.' ORDER BY uploadedDate DESC LIMIT 10');
		if($images)
		{
			foreach($images AS $image)
			{
				// hydrate so we have access to the file object
				$fileObj = file::hydrate($image);

				// prepare data
				$lRs = array();
				$lRs['id'] = $image['id'];
				$lRs['title'] = $image['originalFilename'];
				$lRs['url'] = $fileObj->getFullShortUrl();
				$lRs['thumbnail'] = file::getIconPreviewImageUrl($image, false, 48, false, 100, 100, 'middle');
				$lRs['owner'] = strlen($image['username'])?$image['username']:t('guest_user', 'Guest User');
				$lRs['uploaded_date'] = coreFunctions::formatDate($image['uploadedDate']);
				$lRs['folder_name'] = strlen($image['folderName'])?('in '.$image['folderName']):'';
				$lRs['none'] = '';
				
				// add to overall array
				$rs[] = $lRs;
			}
		}
		break;
	
	case 'folders':
		// lookup folders
		$folders = $db->getRows('SELECT file_folder.id, file_folder.userId, users.username, file_folder.parentId, file_folder.folderName, file_folder.accessPassword, file_folder.coverImageId, (SELECT COUNT(file.id) FROM file WHERE file.folderId = file_folder.id AND file.statusId = 1) AS total_files FROM file_folder LEFT JOIN users ON file_folder.userId = users.id WHERE (file_folder.folderName LIKE "%'.$db->escape($query).'%" ) AND file_folder.userId = '.(int)$Auth->id.' ORDER BY date_added DESC, folderName ASC LIMIT 10');
		if($folders)
		{
			foreach($folders AS $folder)
			{
				// hydrate so we have access to the folder object
				$folderObj = fileFolder::hydrate($folder);
				
				$icon = 'folder_full_fm_grid.png';
				if($folder['fileCount'] == 0 && $folder['isPublic'] == 1)
				{
					$icon = 'folder_fm_grid.png';
				}
				elseif($folder['fileCount'] > 0 && $folder['isPublic'] == 1)
				{
					$icon = 'folder_full_fm_grid.png';
				}
				elseif($folder['fileCount'] >= 0 && $folder['isPublic'] == 0)
				{
					$icon = 'folder_lock_fm_grid.png';
				}

				// prepare data
				$lRs = array();
				$lRs['id'] = $folder['id'];
				$lRs['title'] = $folder['folderName'];
				$lRs['url'] = $folderObj->getFolderUrl();
				$lRs['thumbnail'] = SITE_IMAGE_PATH.'/'.$icon;
				$lRs['owner'] = strlen($folder['username'])?$folder['username']:t('guest_user', 'Guest User');
				$lRs['total_files'] = $folder['total_files'].' '.($folder['total_files']==1?(t('file', 'file')):(t('files', 'files')));
				$lRs['none'] = '';
				
				// add to overall array
				$rs[] = $lRs;
			}
		}
		break;
}

echo json_encode($rs);