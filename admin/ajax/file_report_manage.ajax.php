<?php

// includes and security
define('MIN_ACCESS_LEVEL', 10); // allow moderators
include_once('../_local_auth.inc.php');

$iDisplayLength       = (int) $_REQUEST['iDisplayLength'];
$iDisplayStart        = (int) $_REQUEST['iDisplayStart'];
$sSortDir_0           = $_REQUEST['sSortDir_0'] ? $_REQUEST['sSortDir_0'] : "desc";
$filterText           = $_REQUEST['filterText'] ? $_REQUEST['filterText'] : "";
$filterByReportStatus = strlen($_REQUEST['filterByReportStatus']) ? $_REQUEST['filterByReportStatus'] : false;

// get sorting columns
$iSortCol_0     = (int) $_REQUEST['iSortCol_0'];
$sColumns       = trim($_REQUEST['sColumns']);
$arrCols        = explode(",", $sColumns);
$sortColumnName = $arrCols[$iSortCol_0];
$sort           = 'report_date';
switch ($sortColumnName)
{
    case 'report_date':
        $sort = 'report_date';
        break;
    case 'reported_by_name':
        $sort = 'reported_by_name';
        break;
    case 'file_name':
        $sort = 'file.originalFilename';
        break;
    case 'reported_by_ip':
        $sort = 'reported_by_ip';
        break;
    case 'report_status':
        $sort = 'report_status';
        break;
}

$sqlClause = "WHERE 1=1 ";
if ($filterText)
{
    $filterText = $db->escape($filterText);
    $sqlClause .= "AND (file_report.reported_by_name LIKE '%" . $filterText . "%' OR ";
    $sqlClause .= "file_report.reported_by_email LIKE '%" . $filterText . "%' OR ";
    $sqlClause .= "file_report.reported_by_address LIKE '%" . $filterText . "%' OR ";
    $sqlClause .= "file_report.reported_by_telephone_number LIKE '%" . $filterText . "%' OR ";
    $sqlClause .= "file_report.digital_signature LIKE '%" . $filterText . "%' OR ";
    $sqlClause .= "file_report.reported_by_ip LIKE '%" . $filterText . "%' OR ";
    $sqlClause .= "file.originalFilename LIKE '%" . $filterText . "%' OR ";
    $sqlClause .= "file.id = '" . $filterText . "')";
}

if ($filterByReportStatus)
{
    $sqlClause .= " AND file_report.report_status = " . $db->quote($filterByReportStatus);
}

$totalRS   = $db->getValue("SELECT COUNT(file_report.id) AS total FROM file_report LEFT JOIN file ON file_report.file_id = file.id " . $sqlClause);
$limitedRS = $db->getRows("SELECT file_report.*, file.originalFilename, file.extension, file.id AS fileId, file.statusId FROM file_report LEFT JOIN file ON file_report.file_id = file.id " . $sqlClause . " ORDER BY " . $sort . " " . $sSortDir_0 . " LIMIT " . $iDisplayStart . ", " . $iDisplayLength);

$data = array();
if (COUNT($limitedRS) > 0)
{
    foreach ($limitedRS AS $row)
    {
        $lRow = array();
        $icon = 'assets/images/icons/file_types/16px/' . $row['extension'] . '.png';
        if (!file_exists(ADMIN_ROOT . '/' . $icon))
        {
            $icon = 'assets/images/icons/file_types/16px/_page.png';
        }
        $lRow[] = '<img src="' . $icon . '" width="16" height="16" title="' . $row['extension'] . '" alt="' . $row['extension'] . '"/>';
        $lRow[] = coreFunctions::formatDate($row['report_date'], SITE_CONFIG_DATE_FORMAT);

        if ($row['statusId'] == 1)
        {
            $lRow[] = '<a href="' . file::getFileUrl($row['fileId']) . '" target="_blank" title="' . file::getFileUrl($row['fileId']) . '">' . adminFunctions::makeSafe(adminFunctions::limitStringLength($row['originalFilename'], 35)) . '</a>';
        }
        else
        {
            $lRow[] = adminFunctions::makeSafe(adminFunctions::limitStringLength($row['originalFilename'], 35));
        }

        $lRow[]    = adminFunctions::makeSafe($row['reported_by_name']);
        $lRow[]    = adminFunctions::makeSafe($row['reported_by_ip']);
        $statusRow = '<span class="statusText' . str_replace(" ", "", adminFunctions::makeSafe(UCWords($row['report_status']))) . '"';
        $statusRow .= '>' . $row['report_status'] . '</span>';
        $lRow[]    = $statusRow;

        $links   = array();
        $links[] = '<a href="#" onClick="viewReport(' . (int) $row['id'] . ', \'Removed after abuse report received on ' . coreFunctions::formatDate($row['report_date'], SITE_CONFIG_DATE_FORMAT) . '. Abuse report #' . $row['id'] . '.\', ' . (int) $row['fileId'] . ', \''.$row['report_status'].'\'); return false;">view</a>';
        if ($row['statusId'] == 1)
        {
            $links[] = '<a href="#" onClick="confirmRemoveFile(' . (int) $row['id'] . ', \'Removed after abuse report received on ' . coreFunctions::formatDate($row['report_date'], SITE_CONFIG_DATE_FORMAT) . '. Abuse report #' . $row['id'] . '.\', ' . (int) $row['fileId'] . '); return false;">remove file</a>';
            $links[] = '<a href="#" onClick="declineReport(' . (int) $row['id'] . '); return false;">decline</a>';
        }
        else
        {
            if ($row['report_status'] == 'pending')
            {
                $links[] = '<a href="#" onClick="acceptReport(' . (int) $row['id'] . '); return false;">approve</a>';
                $links[] = '<a href="#" onClick="declineReport(' . (int) $row['id'] . '); return false;">decline</a>';
            }
        }
        $lRow[] = implode(" | ", $links);

        $data[] = $lRow;
    }
}

$resultArr                         = array();
$resultArr["sEcho"]                = intval($_GET['sEcho']);
$resultArr["iTotalRecords"]        = (int) $totalRS;
$resultArr["iTotalDisplayRecords"] = $resultArr["iTotalRecords"];
$resultArr["aaData"]               = $data;

echo json_encode($resultArr);
