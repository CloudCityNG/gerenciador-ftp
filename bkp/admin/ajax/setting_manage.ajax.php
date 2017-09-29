<?php

// includes and security
include_once('../_local_auth.inc.php');

$iDisplayLength = (int) $_REQUEST['iDisplayLength'];
$iDisplayStart  = (int) $_REQUEST['iDisplayStart'];
$sSortDir_0     = $_REQUEST['sSortDir_0'] ? $_REQUEST['sSortDir_0'] : "desc";
$filterText     = $_REQUEST['filterText'] ? $_REQUEST['filterText'] : null;
$filterByGroup  = $_REQUEST['filterByGroup'] ? $_REQUEST['filterByGroup'] : "";

// get sorting columns
$iSortCol_0     = (int) $_REQUEST['iSortCol_0'];
$sColumns       = trim($_REQUEST['sColumns']);
$arrCols        = explode(",", $sColumns);
$sortColumnName = $arrCols[$iSortCol_0];
$sort           = 'config_key';
switch ($sortColumnName)
{
    case 'config_description':
        $sort = 'config_description';
        break;
    case 'config_value':
        $sort = 'config_value';
        break;
}

$sqlClause = "WHERE config_group != 'system' ";
if ($filterText)
{
    $filterText = $db->escape($filterText);
    $sqlClause .= "AND (config_group = '" . $filterText . "' OR ";
    $sqlClause .= "config_description LIKE '%" . $filterText . "%' OR ";
    $sqlClause .= "config_value LIKE '%" . $filterText . "%' OR ";
    $sqlClause .= "config_key = '" . $filterText . "')";
}

if (strlen($filterByGroup))
{
    $sqlClause .= " AND config_group = '" . $db->escape($filterByGroup) . "'";
}

$totalRS   = $db->getValue("SELECT COUNT(id) AS total FROM site_config " . $sqlClause);
$limitedRS = $db->getRows("SELECT * FROM site_config " . $sqlClause . " ORDER BY " . $sort . " " . $sSortDir_0 . " LIMIT " . $iDisplayStart . ", " . $iDisplayLength);

$data = array();
if (COUNT($limitedRS) > 0)
{
    foreach ($limitedRS AS $row)
    {
        $lRow = array();
        $icon        = 'assets/images/icons/system/16x16/process.png';
        $lRow[]      = '<img src="' . $icon . '" width="16" height="16" title="configuration" alt="configuration"/>';
        $lRow[]      = '<span title="Key: '.adminFunctions::makeSafe($row['config_key']).'">'.$row['config_description'].'</span>';
        $configValue = $row['config_value'];
        if (strlen($configValue) > 200)
        {
            $configValue = substr($configValue, 0, 200) . ' ...';
        }
        $lRow[]      = nl2br(adminFunctions::makeSafe($configValue));

        $links = array();
        $links[] = '<a href="#" onClick="editConfigurationForm(' . (int) $row['id'] . '); return false;">edit</a>';
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
