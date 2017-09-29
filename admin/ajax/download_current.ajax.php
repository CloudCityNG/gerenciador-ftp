<?php

// includes and security
include_once('../_local_auth.inc.php');

// clear any expired download trackers
downloadTracker::clearTimedOutDownloads();

$iDisplayLength = (int) $_REQUEST['iDisplayLength'];
$iDisplayStart  = (int) $_REQUEST['iDisplayStart'];
$sSortDir_0     = $_REQUEST['sSortDir_0'] ? $_REQUEST['sSortDir_0'] : "asc";
$filterText     = $_REQUEST['filterText'] ? $_REQUEST['filterText'] : null;

// get sorting columns
$iSortCol_0     = (int) $_REQUEST['iSortCol_0'];
$sColumns       = trim($_REQUEST['sColumns']);
$arrCols        = explode(",", $sColumns);
$sortColumnName = $arrCols[$iSortCol_0];
$sort           = 'download_tracker.date_started';
switch ($sortColumnName)
{
    case 'date_started':
        $sort = 'download_tracker.date_started';
        break;
    case 'ip_address':
        $sort = 'download_tracker.ip_address';
        break;
    case 'file_name':
        $sort = 'file.originalFilename';
        break;
    case 'file_size':
        $sort = 'file.fileSize';
        break;
    case 'status':
        $sort = 'download_tracker.status';
        break;
    case 'total_threads':
        $sort = 'COUNT(download_tracker.id)';
        break;
}

$sqlClause = "WHERE download_tracker.status='downloading' ";

$sQL     = "SELECT COUNT(download_tracker.id) AS total_threads, download_tracker.date_started, download_tracker.ip_address, download_tracker.status, file.originalFilename, file.fileSize, file.shortUrl, file.extension ";
$sQL .= "FROM download_tracker ";
$sQL .= "LEFT JOIN file ON download_tracker.file_id = file.id ";
$sQL .= $sqlClause . " ";
$sQL .= "GROUP BY download_tracker.ip_address, download_tracker.file_id ";
$totalRS = $db->numRows($sQL);

$sQL .= "ORDER BY " . $sort . " " . $sSortDir_0 . " ";
$sQL .= "LIMIT " . $iDisplayStart . ", " . $iDisplayLength;
$limitedRS = $db->getRows($sQL);

$data = array();
if (COUNT($limitedRS) > 0)
{
    foreach ($limitedRS AS $row)
    {
        $lRow = array();

        $icon = 'assets/images/icons/file_types/16px/' . $row['extension'] . '.png';
        if (!file_exists(ADMIN_ROOT . '/' . $icon))
        {
            $icon   = 'assets/images/icons/file_types/16px/_page.png';
        }
        $lRow[] = '<img src="' . $icon . '" width="16" height="16" title="' . $row['extension'] . '" alt="' . $row['extension'] . '"/>';
        $lRow[] = coreFunctions::formatDate($row['date_started'], SITE_CONFIG_DATE_TIME_FORMAT);
        $lRow[] = strlen($row['download_username'])?(adminFunctions::makeSafe($row['download_username']).'<br/>'.adminFunctions::makeSafe($row['ip_address'])):'<span style="color: #aaa;" title="[not logged in]">'.adminFunctions::makeSafe($row['ip_address']).'</span>';
        $lRow[] = '<a href="file_manage.php?filterText=/' . $row['shortUrl'] . '" title="' . (file::getFileUrl($row['id'])) . '">' . adminFunctions::makeSafe(adminFunctions::limitStringLength($row['originalFilename'], 35)) . '</a>';
        $lRow[] = adminFunctions::makeSafe(adminFunctions::formatSize($row['fileSize']));
        $lRow[] = (int)$row['total_threads'];
        $lRow[] = '<span class="statusText' . str_replace(" ", "", UCWords($row['status'])) . '">' . $row['status'] . '</span>';

        $data[] = $lRow;
    }
}

$resultArr = array();
$resultArr["sEcho"]                = intval($_GET['sEcho']);
$resultArr["iTotalRecords"]        = (int) $totalRS;
$resultArr["iTotalDisplayRecords"] = $resultArr["iTotalRecords"];
$resultArr["aaData"]               = $data;

echo json_encode($resultArr);
