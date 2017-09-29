<?php

// includes and security
include_once('../_local_auth.inc.php');

// load site default language
$defaultLanguageRow = $db->getRow("SELECT * FROM site_config WHERE config_key = 'site_language' LIMIT 1");
$defaultLanguage = '';
if($defaultLanguageRow)
{
    $defaultLanguage = $defaultLanguageRow['config_value'];
}

$iDisplayLength = (int) $_REQUEST['iDisplayLength'];
$iDisplayStart  = (int) $_REQUEST['iDisplayStart'];
$sSortDir_0     = $_REQUEST['sSortDir_0'] ? $_REQUEST['sSortDir_0'] : "desc";
$filterText     = $_REQUEST['filterText'] ? $_REQUEST['filterText'] : null;

// get sorting columns
$iSortCol_0     = (int) $_REQUEST['iSortCol_0'];
$sColumns       = trim($_REQUEST['sColumns']);
$arrCols        = explode(",", $sColumns);
$sortColumnName = $arrCols[$iSortCol_0];
$sort           = 'languageName';
switch ($sortColumnName)
{
    case 'language':
        $sort = 'languageName';
        break;
}

$sqlClause = "WHERE 1=1 ";
if ($filterText)
{
    $filterText = $db->escape($filterText);
    $sqlClause .= "AND (languageName LIKE '%" . $filterText . "%')";
}

$totalRS   = $db->getValue("SELECT COUNT(id) AS total FROM language " . $sqlClause);
$limitedRS = $db->getRows("SELECT * FROM language " . $sqlClause . " ORDER BY " . $sort . " " . $sSortDir_0 . " LIMIT " . $iDisplayStart . ", " . $iDisplayLength);

$data = array();
if (COUNT($limitedRS) > 0)
{
    foreach ($limitedRS AS $row)
    {
        $lRow = array();
        $icon        = 'assets/images/icons/flags/'.$row['flag'].'.png';
        $lRow[]      = '<img src="' . $icon . '" width="16" height="11" title="'.adminFunctions::makeSafe($row['languageName']).'" alt="'.adminFunctions::makeSafe($row['languageName']).'"/>';
        $lRow[]      = adminFunctions::makeSafe($row['languageName']);
        
        $image = 'delete';
        $title = 'Click to set as the site default language.';
        $style = ' style="cursor:pointer;" onClick="setDefault(\''.adminFunctions::makeSafe($row['languageName']).'\'); return false;"';
        if($defaultLanguage == $row['languageName'])
        {
            $image = 'accept';
            $title = 'Is the site default language.';
            $style = '';
        }
        $lRow[]      = '<img src="assets/images/icons/system/16x16/' . $image . '.png" width="16" height="16" title="'.$title.'" alt="'.$title.'" '.$style.'/>';
        
        $image = 'delete';
        $title = 'Click to set the language available on the site in the language selector.';
        $style = ' style="cursor:pointer;" onClick="setAvailableState('.adminFunctions::makeSafe($row['id']).', 1); return false;"';
        if(($defaultLanguage == $row['languageName']))
        {
            $image = 'accept';
            $title = 'Available.';
            $style = '';
        }
        elseif($row['isActive'] == 1)
        {
            $image = 'accept';
            $title = 'Click to make this language unavailable from the site.';
            $style = ' style="cursor:pointer;" onClick="setAvailableState('.adminFunctions::makeSafe($row['id']).', 0); return false;"';
        }
        $lRow[]      = '<img src="assets/images/icons/system/16x16/' . $image . '.png" width="16" height="16" title="'.$title.'" alt="'.$title.'" '.$style.'/>';
        
        // text direction
        $lRow[]      = adminFunctions::makeSafe($row['direction']);
        
        $links = array();
        $links[] = '<a href="translation_manage_text.php?languageId=' . (int) $row['id'] . '">manage translations</a>';
        if($row['isLocked'] != 1)
        {
            $links[] = '<a href="#" onClick="editLanguageForm(' . (int) $row['id'] . '); return false;">edit</a>';
            $links[] = '<a href="#" onClick="deleteLanguage(' . (int) $row['id'] . '); return false;">delete</a>';
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
