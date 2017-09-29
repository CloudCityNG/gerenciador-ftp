<?php

// includes and security
include_once('../_local_auth.inc.php');

$iDisplayLength = (int) $_REQUEST['iDisplayLength'];
$iDisplayStart  = (int) $_REQUEST['iDisplayStart'];
$sSortDir_0     = $_REQUEST['sSortDir_0'] ? $_REQUEST['sSortDir_0'] : "desc";
$filterText     = $_REQUEST['filterText'] ? $_REQUEST['filterText'] : null;

// get sorting columns
$iSortCol_0     = (int) $_REQUEST['iSortCol_0'];
$sColumns       = trim($_REQUEST['sColumns']);
$arrCols        = explode(",", $sColumns);
$sortColumnName = $arrCols[$iSortCol_0];
$sort           = 'ipAddress';
switch ($sortColumnName)
{
    case 'ip_address':
        $sort = 'ipAddress';
        break;
    case 'date_banned':
        $sort = 'dateBanned';
        break;
    case 'ban_type':
        $sort = 'banType';
        break;
    case 'ban_expiry':
        $sort = 'banExpiry';
        break;
    case 'ban_notes':
        $sort = 'banNotes';
        break;
}

$sqlClause = "WHERE 1=1 ";
if ($filterText)
{
    $filterText = $db->escape($filterText);
    $sqlClause .= "AND (ipAddress LIKE '%" . $filterText . "%' OR ";
    $sqlClause .= "banType LIKE '%" . $filterText . "%' OR ";
    $sqlClause .= "banNotes LIKE '%" . $filterText . "%')";
}

$totalRS   = $db->getValue("SELECT COUNT(id) AS total FROM banned_ips " . $sqlClause);
$limitedRS = $db->getRows("SELECT * FROM banned_ips " . $sqlClause . " ORDER BY " . $sort . " " . $sSortDir_0 . " LIMIT " . $iDisplayStart . ", " . $iDisplayLength);

$data = array();
if (COUNT($limitedRS) > 0)
{
    foreach ($limitedRS AS $row)
    {
        $lRow = array();
        $icon        = 'assets/images/icons/system/16x16/block.png';
        $lRow[]      = '<img src="' . $icon . '" width="16" height="16" title="banned ip" alt="banned ip"/>';
        $lRow[]      = adminFunctions::makeSafe($row['ipAddress']);
        $lRow[]      = adminFunctions::makeSafe(coreFunctions::formatDate($row['dateBanned'], SITE_CONFIG_DATE_FORMAT));
        $lRow[]      = adminFunctions::makeSafe($row['banType']);
        $lRow[]      = (strlen($row['banExpiry'])?(coreFunctions::formatDate($row['banExpiry'])):'-');
        $banNotes = $row['banNotes'];
        $lRow[]      = adminFunctions::makeSafe($banNotes);

        $links = array();
        $links[] = '<a href="#" onClick="deleteBannedIp(' . (int) $row['id'] . '); return false;">delete</a>';
        $lRow[]  = implode(" | ", $links);

        $data[] = $lRow;
    }
}

$resultArr = array();
$resultArr["sEcho"]                = intval($_GET['sEcho']);
$resultArr["iTotalRecords"]        = (int) $totalRS;
$resultArr["iTotalDisplayRecords"] = $resultArr["iTotalRecords"];
$resultArr["aaData"]               = $data;

echo json_encode($resultArr);
