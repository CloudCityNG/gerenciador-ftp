<?php

// includes and security
include_once('../_local_auth.inc.php');

$existing_user_level_id        = $_REQUEST['existing_user_level_id'];
if($existing_user_level_id == 'null')
{
	$existing_user_level_id = null;
}
$label                         = trim($_REQUEST['label']);
$can_upload                    = (int) trim($_REQUEST['can_upload']);
$download_speed                = trim($_REQUEST['download_speed']);
$max_storage_bytes             = trim($_REQUEST['max_storage_bytes']);
$show_site_adverts             = (int) trim($_REQUEST['show_site_adverts']);
$show_upgrade_screen           = trim($_REQUEST['show_upgrade_screen']);
$days_to_keep_inactive_files   = (int) trim($_REQUEST['days_to_keep_inactive_files']);
$concurrent_uploads            = (int) trim($_REQUEST['concurrent_uploads']);
$concurrent_downloads          = (int) trim($_REQUEST['concurrent_downloads']);
$downloads_per_24_hours        = (int) trim($_REQUEST['downloads_per_24_hours']);
$max_download_filesize_allowed = trim($_REQUEST['max_download_filesize_allowed']);
$max_remote_download_urls      = (int) trim($_REQUEST['max_remote_download_urls']);
$max_upload_size               = trim($_REQUEST['max_upload_size']);
$level_type                    = trim($_REQUEST['level_type']);
$on_upgrade_page               = (int) $_REQUEST['on_upgrade_page'];
$wait_between_downloads        = (int) $_REQUEST['wait_between_downloads'];

// prepare result
$result          = array();
$result['error'] = false;
$result['msg']   = '';

// validate submission
if (strlen($label) == 0)
{
    $result['error'] = true;
    $result['msg']   = adminFunctions::t("account_level_label_invalid", "Please specify the label.");
}
elseif (_CONFIG_DEMO_MODE == true)
{
    $result['error'] = true;
    $result['msg']   = adminFunctions::t("no_changes_in_demo_mode");
}

if (strlen($result['msg']) == 0)
{
    $row = $db->getRow('SELECT id FROM user_level WHERE label = ' . $db->quote($label) . ' AND id != ' . $existing_user_level_id);
    if (is_array($row))
    {
        $result['error'] = true;
        $result['msg']   = adminFunctions::t("account_level_label_already_in_use", "That label has already been used, please choose another.");
    }
    else
    {
        if (strlen($existing_user_level_id) > 0)
        {
            // update the existing record
            $dbUpdate                                = new DBObject("user_level", array("level_type", "label", "can_upload", "wait_between_downloads", "download_speed", "max_storage_bytes", "show_site_adverts", "show_upgrade_screen", "days_to_keep_inactive_files", "concurrent_uploads", "concurrent_downloads", "downloads_per_24_hours", "max_download_filesize_allowed", "max_remote_download_urls", "max_upload_size", "level_type", "on_upgrade_page"), 'id');
            $dbUpdate->level_type                    = 'paid';
            $dbUpdate->label                         = $label;
            $dbUpdate->can_upload                    = $can_upload;
            $dbUpdate->wait_between_downloads        = $wait_between_downloads;
            $dbUpdate->download_speed                = $download_speed;
            $dbUpdate->max_storage_bytes             = $max_storage_bytes;
            $dbUpdate->show_site_adverts             = $show_site_adverts;
            $dbUpdate->show_upgrade_screen           = $show_upgrade_screen;
            $dbUpdate->days_to_keep_inactive_files   = $days_to_keep_inactive_files;
            $dbUpdate->concurrent_uploads            = $concurrent_uploads;
            $dbUpdate->concurrent_downloads          = $concurrent_downloads;
            $dbUpdate->downloads_per_24_hours        = $downloads_per_24_hours;
            $dbUpdate->max_download_filesize_allowed = $max_download_filesize_allowed;
            $dbUpdate->max_remote_download_urls      = $max_remote_download_urls;
            $dbUpdate->max_upload_size               = $max_upload_size;
            $dbUpdate->level_type                    = $level_type;
            $dbUpdate->on_upgrade_page               = $on_upgrade_page;

            $dbUpdate->id = $existing_user_level_id;
            $dbUpdate->update();

            $result['error'] = false;
            $result['msg']   = 'User package \'' . $label . '\' updated.';
			
			// do plugin settings
			pluginHelper::updatePluginPackageSettings($_REQUEST, $dbUpdate->id);
        }
        else
        {
            // get new level id
            $level_id = (int) $db->getValue('SELECT level_id FROM user_level WHERE level_id < 10 ORDER BY level_id DESC LIMIT 1') + 1;

            // add the file server
            $dbInsert                                = new DBObject("user_level", array("level_type", "level_id", "label", "can_upload", "wait_between_downloads", "download_speed", "max_storage_bytes", "show_site_adverts", "show_upgrade_screen", "days_to_keep_inactive_files", "concurrent_uploads", "concurrent_downloads", "downloads_per_24_hours", "max_download_filesize_allowed", "max_remote_download_urls", "max_upload_size", "level_type", "on_upgrade_page"));
            $dbInsert->level_type                    = 'paid';
            $dbInsert->level_id                      = $level_id;
            $dbInsert->label                         = $label;
            $dbInsert->can_upload                    = $can_upload;
            $dbInsert->wait_between_downloads        = $wait_between_downloads;
            $dbInsert->download_speed                = $download_speed;
            $dbInsert->max_storage_bytes             = $max_storage_bytes;
            $dbInsert->show_site_adverts             = $show_site_adverts;
            $dbInsert->show_upgrade_screen           = $show_upgrade_screen;
            $dbInsert->days_to_keep_inactive_files   = $days_to_keep_inactive_files;
            $dbInsert->concurrent_uploads            = $concurrent_uploads;
            $dbInsert->concurrent_downloads          = $concurrent_downloads;
            $dbInsert->downloads_per_24_hours        = $downloads_per_24_hours;
            $dbInsert->max_download_filesize_allowed = $max_download_filesize_allowed;
            $dbInsert->max_remote_download_urls      = $max_remote_download_urls;
            $dbInsert->max_upload_size               = $max_upload_size;
            $dbInsert->level_type                    = $level_type;
            $dbInsert->on_upgrade_page               = $on_upgrade_page;
            if (!$dbInsert->insert())
            {
                $result['error'] = true;
                $result['msg']   = adminFunctions::t("user_level_error_problem_record", "There was a problem adding the package, please try again.");
            }
            else
            {
				// update to sync id & level_id
				$db->query('UPDATE user_level SET level_id = id');
				
                $result['error'] = false;
                $result['msg']   = 'User package \'' . $label . '\' has been added.';
            }
			
			// do plugin settings
			pluginHelper::updatePluginPackageSettings($_REQUEST, $dbInsert->id);
        }
    }
}

echo json_encode($result);
exit;
