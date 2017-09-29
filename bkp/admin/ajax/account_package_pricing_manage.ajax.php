<?php

// includes and security
include_once('../_local_auth.inc.php');

$iDisplayLength = (int) $_REQUEST['iDisplayLength'];
$iDisplayStart  = (int) $_REQUEST['iDisplayStart'];
$sSortDir_0     = $_REQUEST['sSortDir_0'] ? $_REQUEST['sSortDir_0'] : "asc";
$filterText     = $_REQUEST['filterText'] ? $_REQUEST['filterText'] : null;
$filterLevelId     = $_REQUEST['level_id'] ? $_REQUEST['level_id'] : null;

// get sorting columns
$iSortCol_0     = (int) $_REQUEST['iSortCol_0'];
$sColumns       = trim($_REQUEST['sColumns']);
$arrCols        = explode(",", $sColumns);
$sortColumnName = $arrCols[$iSortCol_0];
$sort           = 'user_level_pricing.user_level_id, user_level_pricing.price';

$sqlClause = "WHERE 1=1 ";
if ($filterText)
{
    $filterText = $db->escape($filterText);
    $sqlClause .= "AND (user_level_pricing.pricing_label LIKE '%" . $filterText . "%')";
}

if($filterLevelId)
{
    $sqlClause .= "AND user_level_id = ".(int)$filterLevelId;
}

$sQL     = "SELECT user_level_pricing.*, user_level.label AS user_level_label FROM user_level_pricing LEFT JOIN user_level ON user_level_pricing.user_level_id = user_level.level_id ";
$sQL .= $sqlClause . " ";
$totalRS = $db->getRows($sQL);

$sQL .= "ORDER BY " . $sort . " " . $sSortDir_0 . " ";
$sQL .= "LIMIT " . $iDisplayStart . ", " . $iDisplayLength;
$limitedRS = $db->getRows($sQL);

$data = array();
if (COUNT($limitedRS) > 0)
{
    foreach ($limitedRS AS $row)
    {
        $lRow = array();

        $lRow[] = '<img src="assets/images/icons/system/16x16/tag_blue.png" width="16" height="16" title="Package Pricing" alt="Package Pricing"/>';
        $lRow[] = adminFunctions::makeSafe($row['pricing_label']);
		$lRow[] = adminFunctions::makeSafe(UCWords($row['user_level_label']));
		
		$packageTypeStr = '';
		if($row['package_pricing_type'] == 'bandwidth')
		{
			$packageTypeStr .= 'Download Allowance: '.coreFunctions::formatSize($row['download_allowance']);
		}
		else
		{
			$packageTypeStr .= 'Premium Access: '.$row['period'];
		}
        $lRow[] = adminFunctions::makeSafe($packageTypeStr);
        $lRow[] = SITE_CONFIG_COST_CURRENCY_SYMBOL.adminFunctions::makeSafe(number_format($row['price'], 2)).' '.SITE_CONFIG_COST_CURRENCY_CODE;

        $links   = array();
        $links[] = '<a href="#" onClick="editPackagePricingForm(' . (int) $row['id'] . '); return false;">edit</a>';
        $links[] = '<a href="account_package_pricing_manage.php?del=' . (int) $row['id'] . '" onClick="return confirm(\'Please confirm you want to remove this package pricing item?\');">remove</a>';
        $lRow[]  = implode(" | ", $links);

        $data[] = $lRow;
    }
}

$resultArr                         = array();
$resultArr["sEcho"]                = intval($_GET['sEcho']);
$resultArr["iTotalRecords"]        = (int) COUNT($totalRS);
$resultArr["iTotalDisplayRecords"] = $resultArr["iTotalRecords"];
$resultArr["aaData"]               = $data;

echo json_encode($resultArr);
