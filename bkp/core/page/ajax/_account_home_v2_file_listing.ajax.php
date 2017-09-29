<?php

/* setup includes */
require_once('../../../core/includes/master.inc.php');

// require login
$Auth->requireUser(WEB_ROOT.'/login.'.SITE_CONFIG_PAGE_EXTENSION);

// some initial headers
header("HTTP/1.0 200 OK");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Pragma: no-cache");

// setup initial params
$s = (int)$_REQUEST['pageStart'];
$l = (int)$_REQUEST['perPage']>0?(int)$_REQUEST['perPage']:30;
$sortCol = $_REQUEST['filterOrderBy'];
$filterUploadedDateRange = strlen($_REQUEST['filterUploadedDateRange'])?$_REQUEST['filterUploadedDateRange']:null;
$sSearch = trim($_REQUEST['filterText']);
$nodeId = $_REQUEST['nodeId'];

$db = Database::getDatabase(true);
$clause = "WHERE userId = " . (int)$Auth->id;
if(strlen($sSearch))
{
    $clause .= " AND (originalFilename LIKE '%".$db->escape($sSearch)."%' OR shortUrl LIKE '%".$db->escape($sSearch)."%')";
}

$sortColName = 'originalFilename';
$sortDir = 'asc';
switch($sortCol)
{
    case 'order_by_filename_asc':
        $sortColName = 'originalFilename';
        $sortDir = 'asc';
        break;
    case 'order_by_filename_desc':
        $sortColName = 'originalFilename';
        $sortDir = 'desc';
        break;
    case 'order_by_uploaded_date_asc':
        $sortColName = 'uploadedDate';
        $sortDir = 'asc';
        break;
    case 'order_by_uploaded_date_desc':
        $sortColName = 'uploadedDate';
        $sortDir = 'desc';
        break;
    case 'order_by_downloads_asc':
        $sortColName = 'visits';
        $sortDir = 'asc';
        break;
    case 'order_by_downloads_desc':
        $sortColName = 'visits';
        $sortDir = 'desc';
        break;
    case 'order_by_filesize_asc':
        $sortColName = 'fileSize';
        $sortDir = 'asc';
        break;
    case 'order_by_filesize_desc':
        $sortColName = 'fileSize';
        $sortDir = 'desc';
        break;
    case 'order_by_last_access_date_asc':
        $sortColName = 'lastAccessed';
        $sortDir = 'asc';
        break;
    case 'order_by_last_access_date_desc':
        $sortColName = 'lastAccessed';
        $sortDir = 'desc';
        break;
}

// for recent uploads
if($nodeId == 'recent')
{
    $sortColName = 'uploadedDate';
    $sortDir = 'desc';
}

// trash can
if($nodeId == 'trash')
{
    $clause .= " AND statusId != 1";
}
else
{
    $clause .= " AND statusId = 1";
}

// root folder listing
if($nodeId == -1)
{
    $clause .= " AND folderId IS NULL";
    $fclause .= " WHERE file_folder.userId = " . (int)$Auth->id." AND file_folder.parentId IS NULL";
}

// folder listing
if((int)$nodeId > 0)
{
    $clause .= " AND folderId = ".(int)$nodeId;
    $fclause .= " WHERE file_folder.userId = " . (int)$Auth->id." AND file_folder.parentId = ".(int)$nodeId;
}

// filter by date range
if($filterUploadedDateRange)
{
    // validate date
    $expDate = explode(' - ', $filterUploadedDateRange);
    if(COUNT($expDate) == 2)
    {
        $startDate = $expDate[0];
        $endDate = $expDate[1];
    }
    else
    {
        $startDate = $expDate[0];
        $endDate = $expDate[0];
    }
    
    if((validation::validDate($startDate, SITE_CONFIG_DATE_FORMAT)) && (validation::validDate($endDate, SITE_CONFIG_DATE_FORMAT)))
    {
        // dates are valid
        $clause .= " AND UNIX_TIMESTAMP(uploadedDate) >= ".coreFunctions::convertDateToTimestamp($startDate, SITE_CONFIG_DATE_FORMAT)." AND UNIX_TIMESTAMP(uploadedDate) <= ".(coreFunctions::convertDateToTimestamp($endDate, SITE_CONFIG_DATE_FORMAT)+(60*60*24)-1);
    }
}

// get file total for this account and filter
$allStats = $db->getRow('SELECT COUNT(id) AS totalFileCount, SUM(fileSize) AS totalFileSize FROM file '.$clause);

// load limited page filtered
$files = $db->getRows('SELECT * FROM file '.$clause.' ORDER BY '.$sortColName.' '.$sortDir.' LIMIT '.$s.','.$l);
$folders = $db->getRows("SELECT file_folder.id, file_folder.parentId, file_folder.folderName, file_folder.isPublic, (SELECT COUNT(file.id) AS fileCount FROM file WHERE file.folderId = file_folder.id AND statusId = 1) AS fileCount FROM file_folder ". $fclause);

if ($files)
{
    echo '<ul class="fileListing">';
    
    if($folders)
    {
        foreach ($folders AS $folder)
        {
            echo '<li class="fileItem folderIconLi">';
            echo '<div class="thumbIcon" style="cursor: pointer;" onClick="loadFolderFiles('.$folder['id'].'); return false;">';
            if($folder['fileCount'] == 0 && $folder['isPublic'] == 1)
            {
                echo '<img src="'.SITE_IMAGE_PATH.'/folder_fm_grid.png" />';
            }
            elseif($folder['fileCount'] > 0 && $folder['isPublic'] == 1)
            {
                echo '<img src="'.SITE_IMAGE_PATH.'/folder_full_fm_grid.png" />';
            }
            elseif($folder['fileCount'] >= 0 && $folder['isPublic'] == 0)
            {
                echo '<img src="'.SITE_IMAGE_PATH.'/folder_lock_fm_grid.png" />';
            }
            else
            {
                echo '<img src="'.SITE_IMAGE_PATH.'/folder_full_fm_grid.png" />';
            }
            echo '</div>';
            echo '<div class="thumbList" style="cursor: pointer;" onClick="loadFolderFiles('.$folder['id'].'); return false;">';
            if($folder['fileCount'] == 0 && $folder['isPublic'] == 1)
            {
                echo '<img src="'.SITE_IMAGE_PATH.'/folder_fm_list.png" />';
            }
            elseif($folder['fileCount'] > 0 && $folder['isPublic'] == 1)
            {
                echo '<img src="'.SITE_IMAGE_PATH.'/folder_full_fm_list.png" />';
            }
            elseif($folder['fileCount'] >= 0 && $folder['isPublic'] == 0)
            {
                echo '<img src="'.SITE_IMAGE_PATH.'/folder_lock_fm_list.png" />';
            }
            else
            {
                echo '<img src="'.SITE_IMAGE_PATH.'/folder_full_fm_list.png" />';
            }            
            echo '</div>';
            echo '<span class="filename" style="cursor: pointer;" onClick="loadFolderFiles('.$folder['id'].'); return false;">'.validation::safeOutputToScreen($folder['folderName']).' '.($folder['fileCount'] > 0 ? "(".$folder['fileCount'].")" : "").'</span>';
            echo '</li>';
        }
    }
    
    foreach ($files AS $file)
    {
        $fileObj = file::hydrate($file);
        $previewImageUrlLarge = file::getIconPreviewImageUrlLarge($file);
        $previewImageUrlMedium = file::getIconPreviewImageUrlMedium($file);
        
        $extraMenuItems = array();
		$params  = pluginHelper::includeAppends('account_home_file_list_menu_item.php', array('fileObj' => $fileObj, 'extraMenuItems' => $extraMenuItems));
        $extraMenuItems = $params['extraMenuItems'];

		$menuItemsStr = '';
		if(COUNT($extraMenuItems))
		{
			$menuItemsStr = json_encode($extraMenuItems);
		}

        echo '<li dttitle="'.validation::safeOutputToScreen($file['originalFilename']).'" dtsizeraw="'.validation::safeOutputToScreen($file['fileSize']).'" dtuploaddate="'.validation::safeOutputToScreen(coreFunctions::formatDate($file['uploadedDate'])).'" dtfullurl="'.validation::safeOutputToScreen($fileObj->getFullShortUrl()).'" dtfilename="'.validation::safeOutputToScreen($file['originalFilename']).'" dtstatsurl="'.validation::safeOutputToScreen($fileObj->getStatisticsUrl()).'" dturlhtmlcode="'.validation::safeOutputToScreen($fileObj->getHtmlLinkCode()).'" dturlbbcode="'.validation::safeOutputToScreen($fileObj->getForumLinkCode()).'" dtextramenuitems="'.validation::safeOutputToScreen($menuItemsStr).'" title="'.validation::safeOutputToScreen($file['originalFilename']).' ('.validation::safeOutputToScreen(coreFunctions::formatSize($file['fileSize'])).')" fileId="'.$file['id'].'" class="fileItem'.$file['id'].' fileIconLi '.($file['statusId']!=1?'fileDeletedLi':'').'" onDblClick="dblClickFile('.$file['id'].'); return false;">';
        echo '<span class="filesize">'.validation::safeOutputToScreen(coreFunctions::formatSize($file['fileSize'])).'</span>';
        echo '<span class="fileUploadDate">'.validation::safeOutputToScreen(coreFunctions::formatDate($file['uploadedDate'])).'</span>';
        echo '<div class="thumbIcon">';
        echo '<a name="link"><img src="'.((substr($previewImageUrlLarge, 0, 4)=='http')?$previewImageUrlLarge:(SITE_IMAGE_PATH.'/trans_1x1.gif')).'" alt="" class="'.((substr($previewImageUrlLarge, 0, 4)!='http')?$previewImageUrlLarge:'#').'"></a>';
        echo '</div>';
        echo '<div class="thumbList">';
        echo '<a name="link"><img src="'.$previewImageUrlMedium.'" alt=""></a>';
        echo '</div>';
        echo '<span class="filename">'.validation::safeOutputToScreen($file['originalFilename']).'</span>';
        echo '</li>';
    }
    echo '</ul>';
}
else
{
    echo '<span class="infoText">'.t('no_files_found', 'No files found.').'</span>';
}

// stats
echo '<input id="rspFolderTotalFiles" value="'.(int)$allStats['totalFileCount'].'" type="hidden"/>';
echo '<input id="rspFolderTotalSize" value="'.(int)$allStats['totalFileSize'].'" type="hidden"/>';
echo '<input id="rspTotalPerPage" value="'.(int)$l.'" type="hidden"/>';
echo '<input id="rspTotalResults" value="'.(int)$allStats['totalFileCount'].'" type="hidden"/>';
echo '<input id="rspCurrentStart" value="'.(int)$s.'" type="hidden"/>';
echo '<input id="rspCurrentPage" value="'.ceil(((int)$s+(int)$l)/(int)$l).'" type="hidden"/>';
echo '<input id="rspTotalPages" value="'.ceil((int)$allStats['totalFileCount']/(int)$l).'" type="hidden"/>';
