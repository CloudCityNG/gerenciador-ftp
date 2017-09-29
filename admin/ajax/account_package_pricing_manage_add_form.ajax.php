<?php

// security
include_once('../_local_auth.inc.php');

// prepare page variables
$pricing_label = '';
$package_pricing_type = 'period';
$period        = '1M';
$download_allowance = '';
$user_level_id = 2;
$price     = '';
$price_gbp     = '';
$price_eur     = '';

// check if this is an edit?
$fileServerId = null;
$formType     = 'add the';
if (isset($_REQUEST['gEditPricingId']))
{
    $gEditPricingId = (int) $_REQUEST['gEditPricingId'];
    if ($gEditPricingId)
    {
        $sQL            = "SELECT * FROM user_level_pricing WHERE id=" . (int) $gEditPricingId;
        $packageDetails = $db->getRow($sQL);
        if ($packageDetails)
        {
            $pricing_label = $packageDetails['pricing_label'];
            $package_pricing_type = $packageDetails['package_pricing_type'];
			$period        = $packageDetails['period'];
			$download_allowance = $packageDetails['download_allowance'];
            $user_level_id = $packageDetails['user_level_id'];
            $price     = $packageDetails['price'];

            $formType = 'update the';
        }
    }
}

// prepare result
$result          = array();
$result['error'] = false;
$result['msg']   = '';

$result['html'] .= '<p style="padding-bottom: 4px;">Use the form below to ' . $formType . ' pricing details.</p>';
$result['html'] .= '<span id="popupMessageContainer"></span>';
$result['html'] .= '<form id="addPricingForm">';

$result['html'] .= '<div class="form">';
$result['html'] .= '<div class="clearfix alt-highlight">
                        <label>' . UCWords(adminFunctions::t("package_pricing_label", "package pricing label")) . ':</label>
                        <div class="input">
                            <input name="pricing_label" id="pricing_label" type="text" value="' . adminFunctions::makeSafe($pricing_label) . '" class="large"/><br/>
                            <span style="margin-top: 4px; display: block; width: 500px;">
                                The description of the pricing shown to the user. i.e. 1 Year Plan or 5GB Download
                            </span>
                        </div>
                    </div>';
$result['html'] .= '<div class="clearfix">
                        <label>' . UCWords(adminFunctions::t("package_type", "package type")) . ':</label>
                        <div class="input">
                            <select name="package_pricing_type" id="package_pricing_type" class="xxlarge" onChange="updateAddPricingOpt();">';
$options = array('period'  => 'By Period - Upgrade the account for a fixed length.', 'bandwidth'  => 'By Bandwidth - Upgrade the account until a download filesize limit is reached.');
foreach ($options AS $k => $option)
{
    $result['html'] .= '        <option value="' . $k . '"';
    if ($package_pricing_type == $k)
    {
        $result['html'] .= '        SELECTED';
    }
    $result['html'] .= '        >' . UCWords($option) . '</option>';
}
$result['html'] .= '        </select><br/>
                            <span style="margin-top: 4px; display: block; width: 500px;">
                                The type of upgrade.
                            </span>
                        </div>
                    </div>';
					
$result['html'] .= '<div class="clearfix alt-highlight period-class">
                        <label>' . UCWords(adminFunctions::t("payment_period", "payment period")) . ':</label>
                        <div class="input">
                            <select name="period" id="period" class="medium">';
$options = array('1D'  => '1 Day', '2D'  => '2 Days', '3D'  => '3 Days', '7D'  => '7 Days', '10D' => '10 Days', '14D' => '14 Days', '21D' => '21 Days', '28D' => '28 Days', '1M'  => '1 Month', '2M'  => '2 Months', '3M'  => '3 Months', '4M'  => '4 Months', '5M'  => '5 Months', '6M'  => '6 Months', '9M'  => '9 Months', '1Y'  => '1 Year', '2Y'  => '2 Years');
foreach ($options AS $k => $option)
{
    $result['html'] .= '        <option value="' . $k . '"';
    if ($period == $k)
    {
        $result['html'] .= '        SELECTED';
    }
    $result['html'] .= '        >' . UCWords($option) . '</option>';
}
$result['html'] .= '        </select><br/>
                            <span style="margin-top: 4px; display: block; width: 500px;">
                                The length of the upgraded membership.
                            </span>
                        </div>
                    </div>';
$result['html'] .= '<div class="clearfix alt-highlight bandwidth-class">
                        <label>' . UCWords(adminFunctions::t("download_allowance", "download allowance")) . ':</label>
                        <div class="input">
                            <input name="download_allowance" id="download_allowance" type="text" value="' . adminFunctions::makeSafe($download_allowance) . '" class="medium"/>&nbsp;&nbsp;bytes<br/>
                            <span style="margin-top: 4px; display: block; width: 500px;">1GB = 1073741824, 25GB = 26843545600, 100GB = 107374182400</span>
                        </div>
                    </div>';

$result['html'] .= '<div class="clearfix">
                        <label>User Package:</label>
                        <div class="input">
                            <select name="user_level_id" id="user_level_id" class="medium">';
$options = $db->getRows('SELECT level_id, label FROM user_level WHERE level_type = \'paid\' ORDER BY level_id ASC');
foreach ($options AS $option)
{
    $result['html'] .= '        <option value="' . $option['level_id'] . '"';
    if ($user_level_id == $option['level_id'])
    {
        $result['html'] .= '        SELECTED';
    }
    $result['html'] .= '        >' . UCWords($option['label']) . '</option>';
}
$result['html'] .= '        </select><br/>
                            <span style="margin-top: 4px; display: block; width: 500px;">
                                Choose the package this pricing applies to. Only \'paid\' packages are shown above.
                            </span>
                        </div>
                    </div>';
$result['html'] .= '<div class="clearfix alt-highlight">
                        <label>' . UCWords(adminFunctions::t("package_price", "package price")) . ':</label>
                        <div class="input">
                            '.SITE_CONFIG_COST_CURRENCY_SYMBOL.' <input name="price" id="price" type="text" value="' . adminFunctions::makeSafe(number_format($price, 2)) . '" class="small"/>&nbsp;&nbsp;'.SITE_CONFIG_COST_CURRENCY_CODE.'
                        </div>
                    </div>';

$result['html'] .= '</form>';

echo json_encode($result);
exit;
