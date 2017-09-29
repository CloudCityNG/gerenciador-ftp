<?php

// includes and security
define('MIN_ACCESS_LEVEL', 10); // allow moderators
include_once('../_local_auth.inc.php');

$iDisplayLength = (int) $_REQUEST['iDisplayLength'];
$iDisplayStart  = (int) $_REQUEST['iDisplayStart'];
$sSortDir_0     = $_REQUEST['sSortDir_0'] ? $_REQUEST['sSortDir_0'] : "desc";
$filterText     = $_REQUEST['filterText'] ? $_REQUEST['filterText'] : "";
$filterByStatus = strlen($_REQUEST['filterByStatus']) ? $_REQUEST['filterByStatus'] : false;
$filterByServer = strlen($_REQUEST['filterByServer']) ? (int) $_REQUEST['filterByServer'] : false;

// get sorting columns
$iSortCol_0     = (int) $_REQUEST['iSortCol_0'];
$sColumns       = trim($_REQUEST['sColumns']);
$arrCols        = explode(",", $sColumns);
$sortColumnName = $arrCols[$iSortCol_0];
$sort           = 'date_created';
switch ($sortColumnName)
{
    case 'date_added':
        $sort = 'date_created';
        break;
    case 'file_path':
        $sort = 'file_path';
        break;
    case 'server':
        $sort = 'file_server.serverLabel';
        break;
    case 'file_action':
        $sort = 'file_action';
        break;
    case 'status':
        $sort = 'status';
        break;
}

$sqlClause = "WHERE 1=1 ";

if ($filterByStatus)
{
    $sqlClause .= " AND file_action.status = " . $db->quote($filterByStatus);
}

if ($filterByServer)
{
    $sqlClause .= " AND file_action.server_id = " . $filterByServer;
}

if ($filterText)
{
    $filterText = $db->escape($filterText);
    $sqlClause .= " AND (file_action.file_path LIKE '%" . $filterText . "%')";
}

$totalRS   = $db->getValue("SELECT COUNT(file_action.id) AS total FROM file_action LEFT JOIN file_server ON file_action.server_id = file_server.id " . $sqlClause);
$limitedRS = $db->getRows("SELECT file_server.serverLabel, file_action.* FROM file_action LEFT JOIN file_server ON file_action.server_id = file_server.id " . $sqlClause . " ORDER BY " . $sort . " " . $sSortDir_0 . " LIMIT " . $iDisplayStart . ", " . $iDisplayLength);

$data = array();
if (COUNT($limitedRS) > 0)
{
    foreach ($limitedRS AS $row)
    {
        $lRow = array();
        $icon = ADMIN_WEB_ROOT.'/assets/images/icons/system/16x16/';
        switch($row['status'])
        {
            case 'complete':
                $icon .= 'accept.png';
                break;
            case 'failed':
                $icon .= 'block.png';
                break;
            case 'processing':
                $icon .= 'clock.png';
                break;
            case 'cancelled':
                $icon .= 'delete_page.png';
                break;
			case 'restore':
				$icon .= 'restore.png';
				break;
            default:
                $icon .= 'clock.png';
                break;
        }

        $typeIcon = '<span style="vertical-align: middle;"><img src="' . $icon . '" width="16" height="16" title="' . $row['status'] . '" alt="' . $row['status'] . '" style="margin-right: 5px;"/></span>';
        $lRow[] = $typeIcon;
        $lRow[] = coreFunctions::formatDate($row['date_created'], SITE_CONFIG_DATE_TIME_FORMAT);
        $lRow[] = adminFunctions::makeSafe($row['serverLabel']);
        $lRow[] = adminFunctions::makeSafe($row['file_path']);
        $lRow[] = adminFunctions::makeSafe(UCWords($row['file_action']));
        $statusRow = '<span class="statusText'.str_replace(" ", "", adminFunctions::makeSafe(UCWords($row['status']))).'"';
        $statusRow .= '>'.UCWords($row['status']).'</span>';
        if((strlen($row['action_date'])) && ($row['status'] == 'pending'))
        {
            $statusRow .= '<br/><span style="color: #999999;">('.coreFunctions::formatDate($row['action_date']).')</span>';
        }
        $lRow[] = $statusRow;

        $links = array();
        if($row['status'] == 'pending')
        {
            $links[] = '<a href="#" onClick="cancelItem('.$row['id'].'); return false;">cancel</a>';
			
        }
		if(stristr(adminFunctions::makeSafe($row['file_path']), '_deleted') && ($row['status'] == 'pending') && ($row['file_action'] != 'restore'))
		{
			$links[] = '<a href="#" onClick="restoreItem('.$row['id'].'); return false;">restore</a>';
		}
        if(($row['status'] == 'complete') || ($row['status'] == 'failed') || ($row['status'] == 'cancelled'))
        {
            if($row['status_msg'] != NULL)
            {
                $links[] = '<a href="#" onClick="alert(\'Result: '.UCWords($row['status']).'\n\n'.adminFunctions::makeSafe(UCWords($row['status_msg'])).'\'); return false;">info</a>';
            }
        }
        if(COUNT($links) == 0)
        {
            $links[] = '-';
        }
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
