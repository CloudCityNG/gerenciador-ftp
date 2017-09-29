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
$sort           = 'start_time';

// pickup params
$taskId = null;
if(isset($_REQUEST['task_id']))
{
	$taskId = (int)$_REQUEST['task_id'];
}
if(!$taskId)
{
	coreFunctions::output404();
}

// load task
$task = $db->getRow('SELECT * FROM background_task WHERE id = '.(int)$taskId.' LIMIT 1');
if(!$task)
{
	coreFunctions::output404();
}

$sqlClause = "WHERE background_task_log.task_id = ".(int)$task['id'];

$totalRS   = $db->getValue("SELECT COUNT(background_task_log.id) AS total FROM background_task_log " . $sqlClause);
$limitedRS = $db->getRows("SELECT id, start_time, end_time, status, server_name FROM background_task_log " . $sqlClause . " ORDER BY " . $sort . " " . $sSortDir_0 . " LIMIT " . $iDisplayStart . ", " . $iDisplayLength);

$data = array();
if (COUNT($limitedRS) > 0)
{
    foreach ($limitedRS AS $row)
    {
        $lRow = array();
        $icon = ADMIN_WEB_ROOT.'/assets/images/icons/system/16x16/';
        switch($row['status'])
        {
            case 'started':
                $icon .= 'page_process.png';
                break;
            default:
                $icon .= 'accept_page.png';
                break;
        }

        $typeIcon = '<span style="vertical-align: middle;"><img src="' . $icon . '" width="16" height="16" title="' . $row['status'] . '" alt="' . $row['status'] . '" style="margin-right: 5px;"/></span>';
        $lRow[] = $typeIcon;
		$lRow[] = adminFunctions::makeSafe($row['server_name']);
        $lRow[] = coreFunctions::formatDate($row['start_time'], SITE_CONFIG_DATE_TIME_FORMAT);
		$lRow[] = coreFunctions::formatDate($row['end_time'], SITE_CONFIG_DATE_TIME_FORMAT);

        $statusRow = '<span class="statusText'.str_replace(" ", "", adminFunctions::makeSafe(UCWords($row['status']))).'"';
        $statusRow .= '>'.UCWords($row['status']).'</span>';
        if((strlen($row['action_date'])) && ($row['status'] == 'pending'))
        {
            $statusRow .= '<br/><span style="color: #999999;">('.coreFunctions::formatDate($row['action_date']).')</span>';
        }
        $lRow[] = $statusRow;

        $data[] = $lRow;
    }
}

$resultArr = array();
$resultArr["sEcho"]                = intval($_GET['sEcho']);
$resultArr["iTotalRecords"]        = (int) $totalRS;
$resultArr["iTotalDisplayRecords"] = $resultArr["iTotalRecords"];
$resultArr["aaData"]               = $data;

echo json_encode($resultArr);
