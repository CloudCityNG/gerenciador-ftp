<?php

// includes and security
include_once('../_local_auth.inc.php');

// update storage stats
file::updateFileServerStorageStats();

$iDisplayLength = (int) $_REQUEST['iDisplayLength'];
$iDisplayStart  = (int) $_REQUEST['iDisplayStart'];
$sSortDir_0     = $_REQUEST['sSortDir_0'] ? $_REQUEST['sSortDir_0'] : "asc";
$filterText     = $_REQUEST['filterText'] ? $_REQUEST['filterText'] : null;

// get sorting columns
$iSortCol_0     = (int) $_REQUEST['iSortCol_0'];
$sColumns       = trim($_REQUEST['sColumns']);
$arrCols        = explode(",", $sColumns);
$sortColumnName = $arrCols[$iSortCol_0];
$sort           = 'file_server.serverLabel';
switch ($sortColumnName)
{
    case 'server_label':
        $sort = 'file_server.serverLabel';
        break;
    case 'server_type':
        $sort = 'file_server.serverType';
        break;
    case 'storage_path':
        $sort = 'file_server.storagePath';
        break;
    case 'total_space_used':
        $sort = 'file_server.totalSpaceUsed';
        break;
    case 'total_files':
        $sort = 'file_server.totalFiles';
        break;
    case 'status':
        $sort = 'file_server_status.label';
        break;
}

$sqlClause = "WHERE 1=1 ";
if ($filterText)
{
    $filterText = $db->escape($filterText);
    $sqlClause .= "AND (file_server.serverLabel LIKE '%" . $filterText . "%' OR ";
    $sqlClause .= "file_server.ipAddress LIKE '%" . $filterText . "%' OR ";
    $sqlClause .= "file_server.serverType = '" . $filterText . "' OR ";
    $sqlClause .= "file_server.storagePath LIKE '%" . $filterText . "%')";
}

$sQL     = "SELECT file_server.*, file_server_status.label AS statusLabel, totalSpaceUsed, totalFiles ";
$sQL .= "FROM file_server ";
$sQL .= "LEFT JOIN file_server_status ON file_server.statusId = file_server_status.id ";
$sQL .= $sqlClause . " ";
$totalRS = $db->getRows($sQL);

$sQL .= "ORDER BY " . $sort . " " . $sSortDir_0 . " ";
$sQL .= "LIMIT " . $iDisplayStart . ", " . $iDisplayLength;
$limitedRS = $db->getRows($sQL);

$data = array();
if (COUNT($limitedRS) > 0)
{
    foreach ($limitedRS AS $row)
    {
        $lRow = array();

        $icon = 'local';
        if ($row['serverType'] == 'ftp')
        {
            $icon   = 'ftp';
        }
        elseif ($row['serverType'] == 'sftp')
        {
            $icon   = 'sftp';
        }
        $lRow[] = '<img src="assets/images/icons/server/16x16/' . $icon . '.png" width="16" height="16" title="' . $icon . ' file server" alt="' . $icon . ' file server"/>';
        $label = adminFunctions::makeSafe($row['serverLabel']);
        if(strlen($row['ipAddress']))
        {
            $label .= ' (' . adminFunctions::makeSafe($row['ipAddress']) . ') ';
        }
        elseif(strlen($row['fileServerDomainName']))
        {
            $label .= ' (' . adminFunctions::makeSafe($row['fileServerDomainName']) . ') ';
        }
        $lRow[] = $label;
        $lRow[] = UCWords(adminFunctions::makeSafe(str_replace('_', ' ', $row['serverType'])));
        $lRow[] = adminFunctions::makeSafe($row['storagePath']);
        $lRow[] = adminFunctions::makeSafe(adminFunctions::formatSize($row['totalSpaceUsed'], 2));
        $lRow[] = '<a href="file_manage.php?filterByServer='.(int) $row['id'].'">'.adminFunctions::makeSafe($row['totalFiles']).'</a>';
        $lRow[] = '<span class="statusText' . str_replace(" ", "", UCWords($row['statusLabel'])) . '">' . $row['statusLabel'] . '</span>';

        $links = array();
        $links[] = '<a href="file_manage.php?filterByServer='.(int) $row['id'].'">'.strtolower(adminFunctions::t('files', 'Files')).'</a>';
        if ($row['serverLabel'] != 'Local Default')
        {
            $links[] = '<a href="#" onClick="editServerForm(' . (int) $row['id'] . '); return false;">edit</a>';
            $links[] = '<a href="#" onClick="confirmRemoveFileServer(' . (int) $row['id'] . ', \''.adminFunctions::makeSafe($row['serverLabel']).'\', '.(int)$row['totalFiles'].'); return false;">remove</a>';
        }
        else
        {
            $links[] = '<a href="#" onClick="editServerForm(' . (int) $row['id'] . '); return false;">edit</a>';
        }
        
        if ($row['serverType'] == 'ftp')
        {
            $links[] = '<a href="#" onClick="testFtpFileServer(' . (int) $row['id'] . '); return false;">test '.$icon.'</a>';
        }
        elseif ($row['serverType'] == 'sftp')
        {
            $links[] = '<a href="#" onClick="testSftpFileServer(' . (int) $row['id'] . '); return false;">test '.$icon.'</a>';
        }
        elseif ($row['serverType'] == 'direct')
        {
            $links[] = '<a href="#" onClick="testDirectFileServer(' . (int) $row['id'] . '); return false;">test</a>';
        }
        $lRow[]  = implode(" | ", $links);

        $data[] = $lRow;
    }
}

$resultArr = array();
$resultArr["sEcho"]                = intval($_GET['sEcho']);
$resultArr["iTotalRecords"]        = (int) COUNT($totalRS);
$resultArr["iTotalDisplayRecords"] = $resultArr["iTotalRecords"];
$resultArr["aaData"]               = $data;

echo json_encode($resultArr);