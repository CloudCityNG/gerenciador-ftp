<?php

// includes and security
include_once('../_local_auth.inc.php');

// defaults
$iDisplayLength = (int) $_REQUEST['iDisplayLength'];
$iDisplayStart  = (int) $_REQUEST['iDisplayStart'];

// preload user levels
$userLevels = $db->getRows('SELECT id, label FROM user_level');
$userLevelsArr = array();
$userLevelsArr[0] = 'Guest';
foreach($userLevels AS $userLevel)
{
    $userLevelsArr[$userLevel{'id'}] = $userLevel['label'];
}

// get pages
$limitedRS = $db->getRows("SELECT * FROM download_page ORDER BY user_level_id ASC, page_order ASC LIMIT " . $iDisplayStart . ", " . $iDisplayLength);
$totalRS = $limitedRS;

$data = array();
if (COUNT($limitedRS) > 0)
{
    foreach ($limitedRS AS $row)
    {
        $lRow = array();
        $icon        = 'assets/images/icons/system/16x16/download.png';
        $lRow[]      = '<img src="' . $icon . '" width="16" height="16" alt="download page"/>';
        $lRow[]      = adminFunctions::makeSafe(UCWords($userLevelsArr[$row{'user_level_id'}]).' (Page '.((int)$row['page_order']).')');
        $lRow[]      = adminFunctions::makeSafe($row['download_page']);

        $links = array();
        $links[] = '<a href="#" onClick="editDownloadPageForm(' . (int) $row['id'] . '); return false;">edit</a>';
        $links[] = '<a href="#" onClick="deletePageType(' . (int) $row['id'] . '); return false;">delete</a>';
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
