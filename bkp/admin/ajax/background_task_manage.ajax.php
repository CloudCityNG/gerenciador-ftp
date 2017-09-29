<?php

// includes and security
define('MIN_ACCESS_LEVEL', 10); // allow moderators
include_once('../_local_auth.inc.php');

$iDisplayLength = (int) $_REQUEST['iDisplayLength'];
$iDisplayStart  = (int) $_REQUEST['iDisplayStart'];
$sSortDir_0     = $_REQUEST['sSortDir_0'] ? $_REQUEST['sSortDir_0'] : "asc";
$filterText     = $_REQUEST['filterText'] ? $_REQUEST['filterText'] : "";
$filterByStatus = strlen($_REQUEST['filterByStatus']) ? $_REQUEST['filterByStatus'] : false;
$filterByServer = strlen($_REQUEST['filterByServer']) ? (int) $_REQUEST['filterByServer'] : false;

// get sorting columns
$iSortCol_0     = (int) $_REQUEST['iSortCol_0'];
$sColumns       = trim($_REQUEST['sColumns']);
$arrCols        = explode(",", $sColumns);
$sortColumnName = $arrCols[$iSortCol_0];
$sort           = 'task';

$sqlClause = "WHERE 1=1 ";

$totalRS   = $db->getValue("SELECT COUNT(background_task.id) AS total FROM background_task " . $sqlClause);
$limitedRS = $db->getRows("SELECT * FROM background_task " . $sqlClause . " ORDER BY " . $sort . " " . $sSortDir_0 . " LIMIT " . $iDisplayStart . ", " . $iDisplayLength);

$data = array();
if (COUNT($limitedRS) > 0)
{
    foreach ($limitedRS AS $row)
    {
        $lRow = array();
        $icon = ADMIN_WEB_ROOT.'/assets/images/icons/system/16x16/';
        switch($row['status'])
        {
            case 'running':
                $icon .= 'clock.png';
                break;
			case 'not_run':
				$icon .= 'warning.png';
				break;
            default:
                $icon .= 'accept.png';
                break;
        }

        $typeIcon = '<span style="vertical-align: middle;"><img src="' . $icon . '" width="16" height="16" title="' . $row['status'] . '" alt="' . $row['status'] . '" style="margin-right: 5px;"/></span>';
        $lRow[] = $typeIcon;
		$lRow[] = adminFunctions::makeSafe($row['task']);
        $lRow[] = coreFunctions::formatDate($row['last_update'], SITE_CONFIG_DATE_TIME_FORMAT);
        $statusRow = '<span class="statusText'.str_replace(" ", "", adminFunctions::makeSafe(UCWords($row['status']))).'"';
        $statusRow .= '>'.UCWords(str_replace('_', ' ', $row['status'])).'</span>';
        if((strlen($row['action_date'])) && ($row['status'] == 'pending'))
        {
            $statusRow .= '<br/><span style="color: #999999;">('.coreFunctions::formatDate($row['action_date']).')</span>';
        }
        $lRow[] = $statusRow;

        $links = array();
		$links[] = '<a href="background_task_manage_log.php?task_id='.$row['id'].'">view history</a>';
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
