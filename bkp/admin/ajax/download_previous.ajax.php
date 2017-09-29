<?php

// includes and security
include_once('../_local_auth.inc.php');

$iDisplayLength = (int) $_REQUEST['iDisplayLength'];
$iDisplayStart  = (int) $_REQUEST['iDisplayStart'];
$sSortDir_0     = $_REQUEST['sSortDir_0'] ? $_REQUEST['sSortDir_0'] : "asc";
$filterText     = $_REQUEST['filterText'] ? $_REQUEST['filterText'] : null;

// get sorting columns
$iSortCol_0     = (int) $_REQUEST['iSortCol_0'];
$sColumns       = trim($_REQUEST['sColumns']);
$arrCols        = explode(",", $sColumns);
$sortColumnName = $arrCols[$iSortCol_0];
$sort           = 'stats.download_date';
switch ($sortColumnName)
{
    case 'date_started':
        $sort = 'stats.download_date';
        break;
    case 'ip_address':
        $sort = 'stats.ip';
        break;
    case 'username':
        $sort = 'users.username';
        break;
}

$sqlClause = "WHERE stats.file_id =".(int)$_REQUEST['fileId'];

$sQL  = "SELECT stats.download_date, stats.ip, stats.user_id, users.username ";
$sQL .= "FROM stats ";
$sQL .= "LEFT JOIN users ON stats.user_id = users.id ";
$sQL .= $sqlClause . " ";
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

        $icon   = 'assets/images/icons/file_types/16px/_page.png';
        $lRow[] = '<img src="' . $icon . '" width="16" height="16" title="' . $row['extension'] . '" alt="' . $row['extension'] . '"/>';
        $lRow[] = coreFunctions::formatDate($row['download_date'], SITE_CONFIG_DATE_TIME_FORMAT);
		$lRow[] = adminFunctions::makeSafe($row['ip']);
        $lRow[] = strlen($row['username'])?(adminFunctions::makeSafe($row['username'])):'<span style="color: #aaa;" title="[not logged in]">[not logged in]</span>';

        $data[] = $lRow;
    }
}

$resultArr = array();
$resultArr["sEcho"]                = intval($_GET['sEcho']);
$resultArr["iTotalRecords"]        = (int) $totalRS;
$resultArr["iTotalDisplayRecords"] = $resultArr["iTotalRecords"];
$resultArr["aaData"]               = $data;

echo json_encode($resultArr);
