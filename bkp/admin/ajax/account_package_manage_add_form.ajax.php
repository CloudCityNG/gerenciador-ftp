<?php

// includes and security
include_once('../_local_auth.inc.php');

// prepare variables
$label                         = '';
$can_upload                    = 0;
$wait_between_downloads        = '';
$download_speed                = '';
$max_storage_bytes             = '';
$show_site_adverts             = '';
$show_upgrade_screen           = '';
$days_to_keep_inactive_files   = '';
$concurrent_uploads            = '';
$concurrent_downloads          = '';
$downloads_per_24_hours        = '';
$max_download_filesize_allowed = '';
$max_remote_download_urls      = '';
$max_upload_size               = '';
$level_type               = 'paid';
$on_upgrade_page               = 0;

// is this an edit?
$gEditUserLevelId = null;
$formType     = 'add the';
if (isset($_REQUEST['gEditUserLevelId']))
{
    $gEditUserLevelId = (int) $_REQUEST['gEditUserLevelId'];
	$sQL            = "SELECT * FROM user_level WHERE id=" . (int) $gEditUserLevelId;
	$packageDetails = $db->getRow($sQL);
	if ($packageDetails)
	{
		$label                         = $packageDetails['label'];
		$can_upload                    = $packageDetails['can_upload'];
		$wait_between_downloads        = $packageDetails['wait_between_downloads'];
		$download_speed                = $packageDetails['download_speed'];
		$max_storage_bytes             = $packageDetails['max_storage_bytes'];
		$show_site_adverts             = $packageDetails['show_site_adverts'];
		$show_upgrade_screen           = $packageDetails['show_upgrade_screen'];
		$days_to_keep_inactive_files   = $packageDetails['days_to_keep_inactive_files'];
		$concurrent_uploads            = $packageDetails['concurrent_uploads'];
		$concurrent_downloads          = $packageDetails['concurrent_downloads'];
		$downloads_per_24_hours        = $packageDetails['downloads_per_24_hours'];
		$max_download_filesize_allowed = $packageDetails['max_download_filesize_allowed'];
		$max_remote_download_urls      = $packageDetails['max_remote_download_urls'];
		$max_upload_size               = $packageDetails['max_upload_size'];
		$level_type                    = $packageDetails['level_type'];
		$on_upgrade_page               = $packageDetails['on_upgrade_page'];

		$formType = 'update the';
	}
}

// prepare result
$result          = array();
$result['error'] = false;
$result['msg']   = '';

$result['html'] = '<p style="padding-bottom: 4px;">Use the form below to ' . $formType . ' user package details.</p>';
$result['html'] .= '<span id="popupMessageContainer"></span>';
$result['html'] .= '<form id="addUserPackageForm" class="user_package_form">';

$result['html'] .= '<div class="form">';
$result['html'] .= '<div class="clearfix alt-highlight">
                        <label>' . UCWords(adminFunctions::t("label", "label")) . ':</label>
                        <div class="input">
                            <input name="label" id="label" type="text" value="' . adminFunctions::makeSafe($label) . '" class="xxlarge"/>
                        </div>
                    </div>';
$result['html'] .= '</div><br/>';


$result['html'] .= '<div class="form">';
$result['html'] .= '<div class="clearfix">
                        <label>Users Can Upload:</label>
                        <div class="input">
                            <select name="can_upload" id="can_upload">';
$options = array(0 => 'No', 1 => 'Yes');
foreach ($options AS $k => $option)
{
    $result['html'] .= '        <option value="' . $k . '"';
    if ($can_upload == $k)
    {
        $result['html'] .= '        SELECTED';
    }
    $result['html'] .= '        >' . UCWords($option) . '</option>';
}
$result['html'] .= '        </select><br/>
                            <span style="margin-top: 4px; display: block; width: 500px;">
                                Allow users to upload.
                            </span>
                        </div>
                    </div>';

$result['html'] .= '<div class="clearfix alt-highlight wait_between_downloads">
                        <label>Wait Between Downloads:</label>
                        <div class="input">
                            <input name="wait_between_downloads" id="wait_between_downloads" type="text" value="' . adminFunctions::makeSafe($wait_between_downloads) . '" class="small"/> seconds<br/>
                            <span style="margin-top: 4px; display: block; width: 500px;">
                                How long a user must wait between downloads, in seconds. Set to 0 (zero) to disable. Note: Ensure the \'downloads_track_current_downloads\' is also set to \'yes\' in site settings to enable this.
                            </span>
                        </div>
                    </div>';

$result['html'] .= '<div class="clearfix download_speed">
                        <label>Download Speed:</label>
                        <div class="input">
                            <input name="download_speed" id="download_speed" type="text" value="' . adminFunctions::makeSafe($download_speed) . '" class="small"/> bytes<br/>
                            <span style="margin-top: 4px; display: block; width: 500px;">
                                Maximum download speed for users, in bytes per second. i.e. 50000. Use 0 for unlimited.
                            </span>
                        </div>
                    </div>';

$result['html'] .= '<div class="clearfix alt-highlight">
                        <label>Storage Allowance:</label>
                        <div class="input">
                            <input name="max_storage_bytes" id="max_storage_bytes" type="text" value="' . adminFunctions::makeSafe($max_storage_bytes) . '" class="small"/> bytes<br/>
                            <span style="margin-top: 4px; display: block; width: 500px;">
                                Maximum storage permitted for users, in bytes. Use 0 (zero) for no limits.
                            </span>
                        </div>
                    </div>';

$result['html'] .= '<div class="clearfix">
                        <label>Show Adverts:</label>
                        <div class="input">
                            <select name="show_site_adverts" id="show_site_adverts">';
$options = array(0 => 'No', 1 => 'Yes');
foreach ($options AS $k => $option)
{
    $result['html'] .= '        <option value="' . $k . '"';
    if ($show_site_adverts == $k)
    {
        $result['html'] .= '        SELECTED';
    }
    $result['html'] .= '        >' . UCWords($option) . '</option>';
}
$result['html'] .= '        </select><br/>
                            <span style="margin-top: 4px; display: block; width: 500px;">
                                Show adverts for users across the site.
                            </span>
                        </div>
                    </div>';

$result['html'] .= '<div class="clearfix alt-highlight">
                        <label>Show Upgrade Page:</label>
                        <div class="input">
                            <select name="show_upgrade_screen" id="show_upgrade_screen">';
$options = array(0 => 'No', 1 => 'Yes');
foreach ($options AS $k => $option)
{
    $result['html'] .= '        <option value="' . $k . '"';
    if ($show_upgrade_screen == $k)
    {
        $result['html'] .= '        SELECTED';
    }
    $result['html'] .= '        >' . UCWords($option) . '</option>';
}
$result['html'] .= '        </select><br/>
                            <span style="margin-top: 4px; display: block; width: 500px;">
                                Show the premium account upgrade page for users.
                            </span>
                        </div>
                    </div>';

$result['html'] .= '<div class="clearfix">
                        <label>Inactive Files Days:</label>
                        <div class="input">
                            <input name="days_to_keep_inactive_files" id="days_to_keep_inactive_files" type="text" value="' . adminFunctions::makeSafe($days_to_keep_inactive_files) . '" class="small"/> days<br/>
                            <span style="margin-top: 4px; display: block; width: 500px;">
                                The amount of days after non-active files are removed for users. Leave blank for unlimited.
                            </span>
                        </div>
                    </div>';

$result['html'] .= '<div class="clearfix alt-highlight">
                        <label>Concurrent Uploads:</label>
                        <div class="input">
                            <input name="concurrent_uploads" id="concurrent_uploads" type="text" value="' . adminFunctions::makeSafe($concurrent_uploads) . '" class="small"/> files<br/>
                            <span style="margin-top: 4px; display: block; width: 500px;">
                                The maximum amount of files that can be uploaded at the same time for users.
                            </span>
                        </div>
                    </div>';

$result['html'] .= '<div class="clearfix concurrent_downloads">
                        <label>Concurrent Downloads:</label>
                        <div class="input">
                            <input name="concurrent_downloads" id="concurrent_downloads" type="text" value="' . adminFunctions::makeSafe($concurrent_downloads) . '" class="small"/> files<br/>
                            <span style="margin-top: 4px; display: block; width: 500px;">
                                The maximum concurrent downloads a user can do at once. Set to 0 (zero) for no limit. Note: Ensure the \'downloads_track_current_downloads\' is also set to \'yes\' in site settings to enable this.
                            </span>
                        </div>
                    </div>';

$result['html'] .= '<div class="clearfix alt-highlight downloads_per_day">
                        <label>Downloads Per Day:</label>
                        <div class="input">
                            <input name="downloads_per_24_hours" id="downloads_per_24_hours" type="text" value="' . adminFunctions::makeSafe($downloads_per_24_hours) . '" class="small"/> files<br/>
                            <span style="margin-top: 4px; display: block; width: 500px;">
                                The maximum files a user can download in a 24 hour period. Set to 0 (zero) to disable.
                            </span>
                        </div>
                    </div>';

$result['html'] .= '<div class="clearfix">
                        <label>Max Download Size:</label>
                        <div class="input">
                            <input name="max_download_filesize_allowed" id="max_download_filesize_allowed" type="text" value="' . adminFunctions::makeSafe($max_download_filesize_allowed) . '" class="small"/> bytes
                                <br/>
                            <span style="margin-top: 4px; display: block; width: 500px;">
                                The maximum filesize a user can download (in bytes). Set to 0 (zero) to ignore.
                            </span>
                        </div>
                    </div>';

$result['html'] .= '<div class="clearfix alt-highlight">
                        <label>Max Remote Urls:</label>
                        <div class="input">
                            <input name="max_remote_download_urls" id="max_remote_download_urls" type="text" value="' . adminFunctions::makeSafe($max_remote_download_urls) . '" class="small"/> urls
                                <br/>
                            <span style="margin-top: 4px; display: block; width: 500px;">
                                The maximum remote urls a user can specify at once.
                            </span>
                        </div>
                    </div>';

$result['html'] .= '<div class="clearfix">
                        <label>Max Upload Size:</label>
                        <div class="input">
                            <input name="max_upload_size" id="max_upload_size" type="text" value="' . adminFunctions::makeSafe($max_upload_size) . '" class="small"/> bytes
                                <br/>
                            <span style="margin-top: 4px; display: block; width: 500px;">
                                The max upload filesize for users (in bytes)
                            </span>
                        </div>
                    </div>';
$result['html'] .= '</div>';

$result['html'] .= '<br/>';

$result['html'] .= '<div class="form">';
$result['html'] .= '<div class="clearfix alt-highlight">
                        <label>Package Type:</label>
                        <div class="input">
                            <select name="level_type" id="level_type" class="xlarge">';
$options = array('free' => 'Free', 'paid' => 'Paid', 'moderator' => 'Moderator', 'admin' => 'Admin', 'nonuser' => 'Non User (do not use - system use only)');
foreach ($options AS $k => $option)
{
    $result['html'] .= '        <option value="' . $k . '"';
    if ($level_type == $k)
    {
        $result['html'] .= '        SELECTED';
    }
    $result['html'] .= '        >' . UCWords($option) . '</option>';
}
$result['html'] .= '        </select><br/>
                            <span style="margin-top: 4px; display: block; width: 500px;">
                                The type of account. Note that Moderator &amp; Admin have access to the admin area.
                            </span>
                        </div>
                    </div>';
$result['html'] .= '<div class="clearfix">
                        <label>On Upgrade Page:</label>
                        <div class="input">
                            <select name="on_upgrade_page" id="on_upgrade_page">';
$options = array(0 => 'No', 1 => 'Yes');
foreach ($options AS $k => $option)
{
    $result['html'] .= '        <option value="' . $k . '"';
    if ($on_upgrade_page == $k)
    {
        $result['html'] .= '        SELECTED';
    }
    $result['html'] .= '        >' . UCWords($option) . '</option>';
}
$result['html'] .= '        </select><br/>
                            <span style="margin-top: 4px; display: block; width: 500px;">
                                Whether to show this package on the upgrade page.
                            </span>
                        </div>
                    </div>';
$result['html'] .= '</div><br/>';
					
$result['html'] .= pluginHelper::getPluginAdminPackageSettingsForm($gEditUserLevelId);

$result['html'] .= '</form>';

echo json_encode($result);
exit;
