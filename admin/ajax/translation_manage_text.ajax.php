<?php

// includes and security
include_once('../_local_auth.inc.php');

$languageId = (int) $_REQUEST['languageId'];

// try to load the language
$sQL            = "SELECT * FROM language WHERE id = " . (int) $languageId . " LIMIT 1";
$languageDetail = $db->getRow($sQL);
if (!$languageDetail)
{
    die();
}

// make sure we have all content records populated
$getMissingRows = $db->getRows("SELECT id, languageKey, defaultContent FROM language_key WHERE id NOT IN (SELECT languageKeyId FROM language_content WHERE languageId = " . (int) $languageDetail['id'] . ")");
if (COUNT($getMissingRows))
{
    foreach ($getMissingRows AS $getMissingRow)
    {
        $dbInsert = new DBObject("language_content", array("languageKeyId", "languageId", "content"));
        $dbInsert->languageKeyId = $getMissingRow['id'];
        $dbInsert->languageId = (int) $languageDetail['id'];
        $dbInsert->content = $getMissingRow['defaultContent'];
        $dbInsert->insert();
    }
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
$sort           = 'config_group';
switch ($sortColumnName)
{
    case 'language_key':
        $sort = 'language_key.languageKey';
        break;
    case 'english_content':
        $sort = 'language_key.defaultContent';
        break;
    case 'translated_content':
        $sort = 'language_content.content';
        break;
}

$sqlClause = "WHERE language_content.languageId = ".(int)$languageDetail['id'];
if ($filterText)
{
    $filterText = $db->escape($filterText);
    $sqlClause .= " AND (language_content.content LIKE '%" . $filterText . "%' OR ";
    $sqlClause .= "language_key.languageKey LIKE '%" . $filterText . "%' OR ";
    $sqlClause .= "language_key.defaultContent LIKE '%" . $filterText . "%')";
}

$totalRS   = $db->getValue("SELECT COUNT(language_content.id) AS total FROM language_content LEFT JOIN language_key ON language_content.languageKeyId = language_key.id " . $sqlClause);
$limitedRS = $db->getRows("SELECT language_content.id, language_content.content, language_key.languageKey, language_key.id AS languageKeyId, language_key.defaultContent, language_content.is_locked FROM language_content LEFT JOIN language_key ON language_content.languageKeyId = language_key.id " . $sqlClause . " ORDER BY " . $sort . " " . $sSortDir_0 . " LIMIT " . $iDisplayStart . ", " . $iDisplayLength);

$data = array();
if (COUNT($limitedRS) > 0)
{
    foreach ($limitedRS AS $row)
    {
        $lRow = array();
        $icon        = 'assets/images/icons/flags/'.$languageDetail['flag'].'.png';
        $lRow[]      = '<img src="' . $icon . '" width="16" height="11" title="configuration" alt="configuration"/>';
        $lRow[]      = adminFunctions::makeSafe($row['languageKey']);
        
        $defaultContent = $row['defaultContent'];
        if (strlen($defaultContent) > 200)
        {
            $defaultContent = substr($defaultContent, 0, 200) . ' ...';
        }
        $lRow[]      = nl2br(adminFunctions::makeSafe($defaultContent));
        
        $content = $row['content'];
        if (strlen($content) > 200)
        {
            $content = substr($content, 0, 200) . ' ...';
        }
        $lRow[]      = nl2br(adminFunctions::makeSafe($content));
		
		$image = 'unlock';
        $title = 'Translation is not locked. It will be updated if you run the auto-translate via tool. You can still manually edit the content.';
        $style = ' style="cursor:pointer;" onClick="toggleLock(\''.adminFunctions::makeSafe($row['id']).'\'); return false;"';
        if($row['is_locked'] == 1)
        {
            $image = 'lock';
            $title = 'Translation is locked. It will not be updated on an automatic translation import. You can still manually edit the content.';
			$style = ' style="cursor:pointer;" onClick="toggleLock(\''.adminFunctions::makeSafe($row['id']).'\'); return false;"';
        }
        $lRow[]      = '<img src="assets/images/icons/system/16x16/' . $image . '.png" width="16" height="16" title="'.$title.'" alt="'.$title.'" '.$style.'/>';

        $links = array();
        $links[] = '<a href="#" onClick="editTranslationForm(' . (int) $row['id'] . '); return false;">edit</a>';
        $links[] = '<a href="#" onClick="deleteTranslation(' . (int) $row['languageKeyId'] . '); return false;">delete</a>';
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
