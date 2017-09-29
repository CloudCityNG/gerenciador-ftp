<?php

// includes and security
include_once('../_local_auth.inc.php');

$iDisplayLength        = (int) $_REQUEST['iDisplayLength'];
$iDisplayStart         = (int) $_REQUEST['iDisplayStart'];
$sSortDir_0            = $_REQUEST['sSortDir_0'] ? $_REQUEST['sSortDir_0'] : "desc";
$filterText            = $_REQUEST['filterText'] ? $_REQUEST['filterText'] : "";
$filterByAccountType   = strlen($_REQUEST['filterByAccountType']) ? $_REQUEST['filterByAccountType'] : false;
$filterByAccountStatus = strlen($_REQUEST['filterByAccountStatus']) ? $_REQUEST['filterByAccountStatus'] : false;
$filterByAccountId = (int)$_REQUEST['filterByAccountId'] ? (int)$_REQUEST['filterByAccountId'] : false;

// account types
$accountTypeDetailsLookup = array();
$accountTypeDetails = $db->getRows('SELECT id, label FROM user_level ORDER BY id ASC');
foreach($accountTypeDetails AS $accountTypeDetails)
{
    $accountTypeDetailsLookup[$accountTypeDetails{'id'}] = $accountTypeDetails['label'];
}

// get sorting columns
$iSortCol_0     = (int) $_REQUEST['iSortCol_0'];
$sColumns       = trim($_REQUEST['sColumns']);
$arrCols        = explode(",", $sColumns);
$sortColumnName = $arrCols[$iSortCol_0];
$sort           = 'username';
switch ($sortColumnName)
{
    case 'username':
        $sort = 'username';
        break;
    case 'email_address':
        $sort = 'email';
        break;
    case 'account_type':
        $sort = 'level_id';
        break;
    case 'last_login':
        $sort = 'lastlogindate';
        break;
    case 'status':
        $sort = 'status';
        break;
    case 'space_used':
        $sort = '(SELECT SUM(fileSize) FROM file WHERE file.userId=users.id AND file.statusId=1)';
        break;
    case 'total_files':
        $sort = '(SELECT COUNT(id) FROM file WHERE file.userId=users.id AND file.statusId=1)';
        break;
}

$sqlClause = "WHERE 1=1 ";
if ($filterText)
{
    $filterText = $db->escape($filterText);
    $sqlClause .= "AND (users.status = '" . $filterText . "' OR ";
    $sqlClause .= "users.username LIKE '%" . $filterText . "%' OR ";
    $sqlClause .= "users.email LIKE '%" . $filterText . "%' OR ";
    $sqlClause .= "users.firstname LIKE '%" . $filterText . "%' OR ";
    $sqlClause .= "users.lastname LIKE '%" . $filterText . "%' OR ";
    $sqlClause .= "users.id = '" . $filterText . "')";
}

if ($filterByAccountType)
{
    $sqlClause .= " AND users.level_id = '" . $db->escape($filterByAccountType)."'";
}

if ($filterByAccountStatus)
{
    $sqlClause .= " AND users.status = '" . $db->escape($filterByAccountStatus)."'";
}

if ($filterByAccountId)
{
    $sqlClause .= " AND users.id = " . (int)$filterByAccountId;
}

$totalRS   = $db->getValue("SELECT COUNT(users.id) AS total FROM users " . $sqlClause);
$limitedRS = $db->getRows("SELECT users.*, (SELECT SUM(fileSize) FROM file WHERE file.userId=users.id AND file.statusId=1) AS totalFileSize, (SELECT COUNT(id) FROM file WHERE file.userId=users.id AND file.statusId=1) AS totalFiles FROM users " . $sqlClause . " ORDER BY " . $sort . " " . $sSortDir_0 . " LIMIT " . $iDisplayStart . ", " . $iDisplayLength);

$data = array();
if (COUNT($limitedRS) > 0)
{
    foreach ($limitedRS AS $row)
    {
        $lRow = array();
        $icon   = 'assets/images/icons/system/16x16/user.png';
        $lRow[] = '<img src="' . $icon . '" width="16" height="16" title="User" alt="User"/>';
        $lRow[] = adminFunctions::makeSafe($row['username']);
        $lRow[] = adminFunctions::makeSafe($row['email']);
        $lRow[] = UCWords(adminFunctions::makeSafe($accountTypeDetailsLookup[$row{'level_id'}]));
        $lRow[] = coreFunctions::formatDate($row['lastlogindate'], SITE_CONFIG_DATE_TIME_FORMAT);
        $lRow[] = (int) $row['totalFileSize'] > 0 ? adminFunctions::formatSize($row['totalFileSize']) : 0;
        $lRow[] = (int) $row['totalFiles'];
        $lRow[] = '<span class="statusText' . str_replace(" ", "", UCWords($row['status'])) . '">' . $row['status'] . '</span>';

        $links = array();
        $links[] = '<a href="user_edit.php?id='.$row['id'].'">edit</a>';
        $links[] = '<a href="file_manage.php?filterByUser='.$row['id'].'">'.strtolower(adminFunctions::t('files', 'Files')).'</a>';
        if(coreFunctions::formatDate($row['lastlogindate'], SITE_CONFIG_DATE_TIME_FORMAT) !== null)
        {
            $links[] = '<a href="user_login_history.php?id='.$row['id'].'">logins</a>';
        }
        if($Auth->id != $row['id'])
        {
            $links[] = '<a href="#" onClick="confirmRemoveUser('.$row['id'].'); return false;">delete</a>';
			$links[] = '<br/><a href="#" onClick="confirmImpersonateUser('.$row['id'].'); return false;">impersonate</a>';
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
