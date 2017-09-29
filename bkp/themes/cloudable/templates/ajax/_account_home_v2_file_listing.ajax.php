<?php

// require login
$Auth->requireUser(WEB_ROOT . '/login.' . SITE_CONFIG_PAGE_EXTENSION);

// layout settings
$thumbnailType = themeHelper::getConfigValue('thumbnail_type');

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
}

// folder listing
if((int)$nodeId > 0)
{
    $clause .= " AND folderId = ".(int)$nodeId;
}

// filter by date range
if($filterUploadedDateRange !== null)
{
    // validate date
    $expDate = explode('|', $filterUploadedDateRange);
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

    if((validation::validDate($startDate, 'Y-m-d')) && (validation::validDate($endDate, 'Y-m-d')))
    {
        // dates are valid
        $clause .= " AND UNIX_TIMESTAMP(uploadedDate) >= ".coreFunctions::convertDateToTimestamp($startDate, SITE_CONFIG_DATE_FORMAT)." AND UNIX_TIMESTAMP(uploadedDate) <= ".(coreFunctions::convertDateToTimestamp($endDate, SITE_CONFIG_DATE_FORMAT)+(60*60*24)-1);
    }
}

// get file total for this account and filter
$allStats = $db->getRow('SELECT COUNT(id) AS totalFileCount, SUM(fileSize) AS totalFileSize FROM file '.$clause);

// load limited page filtered
$files = $db->getRows('SELECT file.*, plugin_filepreviewer_meta.width, plugin_filepreviewer_meta.height FROM file LEFT JOIN plugin_filepreviewer_meta ON file.id = plugin_filepreviewer_meta.file_id '.$clause.' ORDER BY '.$sortColName.' '.$sortDir.' LIMIT '.$s.','.$l);

if ($files)
{
    echo '<ul id="fileListing" class="fileListing">';
    
    // header for list view
    echo '<li class="fileListingHeader ignoreFormatting">';
    echo '<span class="filesize">'.UCWords(t('filesize', 'filesize')).'</span>';
    echo '<span class="fileUploadDate">'.UCWords(t('added', 'added')).'</span>';
    echo '<span class="downloads">'.UCWords(t('downloads', 'downloads')).'</span>';
    echo '<span class="filename">'.UCWords(t('filename', 'filename')).'</span>';
    echo '</li>';
    
    // some presets
    $thumbnailWidth = 190;
    $thumbnailHeight = 190;
    
    // output data
    foreach ($files AS $file)
    {
        $fileObj = file::hydrate($file);
        
        $sizingMethod = 'middle';
        if($thumbnailType == 'masonry')
        {
            $sizingMethod = 'maximum';
        }
        $previewImageUrlLarge = file::getIconPreviewImageUrl($file, false, 48, false, $thumbnailWidth, $thumbnailHeight, $sizingMethod);
        $previewImageUrlMedium = file::getIconPreviewImageUrlMedium($file);
        
        $extraMenuItems = array();
		$params  = pluginHelper::includeAppends('account_home_file_list_menu_item.php', array('fileObj' => $fileObj, 'extraMenuItems' => $extraMenuItems));
        $extraMenuItems = $params['extraMenuItems'];

		$menuItemsStr = '';
		if(COUNT($extraMenuItems))
		{
			$menuItemsStr = json_encode($extraMenuItems);
		}
        
        $width = null;
        $height = null;
        if($sizingMethod == 'middle')
        {
            if(($file['width'] >= $thumbnailWidth) || ($file['height'] >= $thumbnailHeight))
            {
                $width = $thumbnailWidth;
                $height = $thumbnailHeight;
            }
        }

        if(($width === null) && ($height === null))
        {
            // convert image dimensions
            $ratio = $file['width']/$file['height'];
            if($ratio > 1)
            {
                $width = $thumbnailWidth;
                $height = $thumbnailHeight/$ratio;
            }
            else
            {
                $width = $thumbnailWidth;
                $height = $thumbnailHeight/$ratio;
            }
        }
  
        echo '<li dttitle="'.validation::safeOutputToScreen($file['originalFilename']).'" dtsizeraw="'.validation::safeOutputToScreen($file['fileSize']).'" dtuploaddate="'.validation::safeOutputToScreen(coreFunctions::formatDate($file['uploadedDate'])).'" dtfullurl="'.validation::safeOutputToScreen($fileObj->getFullShortUrl()).'" dtfilename="'.validation::safeOutputToScreen($file['originalFilename']).'" dtstatsurl="'.validation::safeOutputToScreen($fileObj->getStatisticsUrl()).'" dturlhtmlcode="'.validation::safeOutputToScreen($fileObj->getHtmlLinkCode()).'" dturlbbcode="'.validation::safeOutputToScreen($fileObj->getForumLinkCode()).'" dtextramenuitems="'.validation::safeOutputToScreen($menuItemsStr).'" title="'.validation::safeOutputToScreen($file['originalFilename']).' ('.validation::safeOutputToScreen(coreFunctions::formatSize($file['fileSize'])).')" fileId="'.$file['id'].'" class="image-thumb image-thumb-'.$sizingMethod.' fileItem'.$file['id'].' fileIconLi '.($file['statusId']!=1?'fileDeletedLi':'').'" onDblClick="dblClickFile('.$file['id'].'); return false;" style="height: '.($thumbnailHeight+12).'px;">';
        
        echo '<span class="filesize">'.validation::safeOutputToScreen(coreFunctions::formatSize($file['fileSize'])).'</span>';
        echo '<span class="fileUploadDate">'.validation::safeOutputToScreen(coreFunctions::formatDate($file['uploadedDate'])).'</span>';
        echo '<span class="downloads">'.validation::safeOutputToScreen($file['visits']).'</span>';
        echo '<div class="thumbIcon">';
        echo '<a name="link"><img src="'.((substr($previewImageUrlLarge, 0, 4)=='http')?$previewImageUrlLarge:(SITE_IMAGE_PATH.'/trans_1x1.gif')).'" alt="" class="'.((substr($previewImageUrlLarge, 0, 4)!='http')?$previewImageUrlLarge:'#').'" style="max-width: 100%; max-height: 100%; min-width: 30px; min-height: 30px;"></a>';
        echo '</div>';
        echo '<div class="back">';
        echo '<p>'.validation::safeOutputToScreen($file['originalFilename']).'</p>';
        echo '</div>';
        
        echo '<div class="thumbList">';
        echo '<a name="link"><img src="'.$previewImageUrlMedium.'" alt=""></a>';
        echo '</div>';
        echo '<span class="filename">'.validation::safeOutputToScreen($file['originalFilename']).'</span>';
        
        echo '  <div class="fileOptions">';
        echo '    <a class="fileDownload" href="#"><i class="entypo-down"></i></a>';
        echo '  </div>';
        echo '</li>';
    }
    echo '</ul>';
}
else
{
    echo '<h2>'.t('no_files_found', 'No files found.').' '.t('click_to_upload', 'Click to <a href="#" onClick="uploadFiles(); return false;">Upload</a>').'</h2>';
}

// stats
echo '<input id="rspFolderTotalFiles" value="'.(int)$allStats['totalFileCount'].'" type="hidden"/>';
echo '<input id="rspFolderTotalSize" value="'.$allStats['totalFileSize'].'" type="hidden"/>';
echo '<input id="rspTotalPerPage" value="'.(int)$l.'" type="hidden"/>';
echo '<input id="rspTotalResults" value="'.(int)$allStats['totalFileCount'].'" type="hidden"/>';
echo '<input id="rspCurrentStart" value="'.(int)$s.'" type="hidden"/>';
echo '<input id="rspCurrentPage" value="'.ceil(((int)$s+(int)$l)/(int)$l).'" type="hidden"/>';
echo '<input id="rspTotalPages" value="'.ceil((int)$allStats['totalFileCount']/(int)$l).'" type="hidden"/>';
