<?php

// includes and security
include_once('../_local_auth.inc.php');

$iDisplayLength = (int) $_REQUEST['iDisplayLength'];
$iDisplayStart  = (int) $_REQUEST['iDisplayStart'];
$sSortDir_0     = $_REQUEST['sSortDir_0'] ? $_REQUEST['sSortDir_0'] : "desc";
$filterText     = $_REQUEST['filterText'] ? $_REQUEST['filterText'] : null;
$filterUserId   = $_REQUEST['filterUserId'] ? $_REQUEST['filterUserId'] : null;

// get sorting columns
$iSortCol_0     = (int) $_REQUEST['iSortCol_0'];
$sColumns       = trim($_REQUEST['sColumns']);
$arrCols        = explode(",", $sColumns);
$sortColumnName = $arrCols[$iSortCol_0];
$sort           = 'date_created';
switch ($sortColumnName)
{
    case 'payment_date':
        $sort = 'date_created';
        break;
    case 'user_name':
        $sort = 'users.username';
        break;
    case 'description':
        $sort = 'description';
        break;
    case 'amount':
        $sort = 'amount';
        break;
}

$sqlClause = "WHERE 1=1 ";
if ($filterText)
{
    $filterText = $db->escape($filterText);
    $sqlClause .= "AND (users.username LIKE '%" . $filterText . "%' OR ";
    $sqlClause .= "description LIKE '%" . $filterText . "%' OR ";
    $sqlClause .= "from_email LIKE '%" . $filterText . "%')";
}

if (strlen($filterByUser))
{
    $sqlClause .= " AND user_id = " . (int)$filterByUser;
}

if(strlen($filterUserId))
{
    $sqlClause .= " AND user_id = " . (int)$filterUserId;
}

$totalRS   = $db->getValue("SELECT COUNT(payment_log.id) AS total FROM payment_log LEFT JOIN users ON payment_log.user_id = users.id " . $sqlClause);
$limitedRS = $db->getRows("SELECT payment_log.id, payment_log.date_created, payment_log.description, payment_log.amount, payment_log.currency_code, users.username, users.id AS user_id FROM payment_log LEFT JOIN users ON payment_log.user_id = users.id " . $sqlClause . " ORDER BY " . $sort . " " . $sSortDir_0 . " LIMIT " . $iDisplayStart . ", " . $iDisplayLength);

$data = array();
if (COUNT($limitedRS) > 0)
{
    foreach ($limitedRS AS $row)
    {
        $lRow = array();
        $icon        = 'assets/images/icons/system/16x16/process.png';
        $lRow[]      = '<img src="' . $icon . '" width="16" height="16" title="payment" alt="payment"/>';
        $lRow[]      = coreFunctions::formatDate($row['date_created'], SITE_CONFIG_DATE_TIME_FORMAT);
        $lRow[]      = '<a href="user_manage.php?filterByAccountId='.urlencode($row['user_id']).'">'.adminFunctions::makeSafe($row['username']).'</a>';
        $lRow[]      = adminFunctions::makeSafe($row['description']);
        $lRow[]      = adminFunctions::makeSafe($row['amount']).' '.adminFunctions::makeSafe($row['currency_code']);

        $links = array();
        $links[] = '<a href="#" onClick="viewPaymentDetail(' . (int) $row['id'] . '); return false;">view</a>';
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
