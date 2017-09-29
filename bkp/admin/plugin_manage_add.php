<?php
// initial constants
define('ADMIN_PAGE_TITLE', 'Add Plugin');
define('ADMIN_SELECTED_PAGE', 'plugins');
define('ADMIN_SELECTED_SUB_PAGE', 'plugin_manage_add');

// includes and security
include_once('_local_auth.inc.php');

// pclzip
include_once(CORE_ROOT . '/includes/pclzip/pclzip.lib.php');

// check for write permissions on the plugins folder
if(!is_writable(PLUGIN_DIRECTORY_ROOT))
{
    adminFunctions::setError(adminFunctions::t("error_plugin_folder_is_not_writable", "Plugin folder is not writable. Ensure you set the following folder to CHMOD 755 or 777: [[[PLUGIN_FOLDER]]]", array('PLUGIN_FOLDER'=>PLUGIN_DIRECTORY_ROOT)));
}

// handle page submissions
if (isset($_REQUEST['submitted']))
{
    // get variables
    $file = $_FILES['plugin_zip'];

    // delete existing tmp folder
    $tmpPath = PLUGIN_DIRECTORY_ROOT . '_tmp';
    if (file_exists($tmpPath))
    {
        adminFunctions::recursiveDelete($tmpPath);
    }

    // validate submission
    if (_CONFIG_DEMO_MODE == true)
    {
        adminFunctions::setError(adminFunctions::t("no_changes_in_demo_mode"));
    }
    elseif (strlen($file['name']) == 0)
    {
        adminFunctions::setError(adminFunctions::t("no_file_found", "No file found, please try again."));
    }
    elseif (strpos(strtolower($file['name']), '.zip') === false)
    {
        adminFunctions::setError(adminFunctions::t("not_a_zip_file", "The uploaded file does not appear to be a zip file."));
    }

    // add the account
    if (adminFunctions::isErrors() == false)
    {
        // attempt to extract the contents
        $zip = new PclZip($file['tmp_name']);
        if ($zip)
        {
            if (!mkdir($tmpPath))
            {
                adminFunctions::setError(adminFunctions::t("error_creating_plugin_folder", "There was a problem creating the plugin folder. Please ensure the following folder has CHMOD 777 permissions: " . PLUGIN_DIRECTORY_ROOT));
            }

            if (adminFunctions::isErrors() == false)
            {
                $zip->extract(PCLZIP_OPT_PATH, $tmpPath . '/');

                // try to read the plugin details
                if (!file_exists($tmpPath . '/_plugin_config.inc.php'))
                {
                    adminFunctions::setError(adminFunctions::t("error_reading_plugin_details", "Could not read the plugin settings file '_plugin_config.inc.php'."));
                }

                if (adminFunctions::isErrors() == false)
                {
                    include_once($tmpPath . '/_plugin_config.inc.php');
                    if (!isset($pluginConfig['folder_name']))
                    {
                        adminFunctions::setError(adminFunctions::t("error_reading_plugin_folder_name", "Could not read the plugin folder name from '_plugin_config.inc.php'."));
                    }

                    if (adminFunctions::isErrors() == false)
                    {
                        // rename tmp folder
                        if (!rename($tmpPath, PLUGIN_DIRECTORY_ROOT . $pluginConfig['folder_name']))
                        {
                            adminFunctions::setError(adminFunctions::t("error_renaming_plugin_folder", "Could not rename plugin folder, it may be that the plugin is already installed or a permissions issue."));
                        }
                        else
                        {
                            // redirect to plugin listing
                            adminFunctions::redirect('plugin_manage.php?sa=1');
                        }
                    }
                }
            }
        }
        else
        {
            adminFunctions::setError(adminFunctions::t("error_problem_unzipping_the_file", "There was a problem unzipping the file, please try and manually upload the zip files contents into the plugins directory or contact support."));
        }
    }
}

// page header
include_once('_header.inc.php');
?>

<div class="row clearfix">
    <div class="col_12">
        <div class="sectionLargeIcon largeServerIcon"></div>
        <div class="widget clearfix">
            <h2>Add Plugin</h2>
            <div class="widget_inside">
                <?php echo adminFunctions::compileNotifications(); ?>
                <form method="POST" action="plugin_manage_add.php" name="pluginForm" id="pluginForm" enctype="multipart/form-data">
                    <div class="clearfix col_12">
                        <div class="col_4">
                            <h3>Upload Plugin Package</h3>
                            <p>Upload the plugin package using the form on the right. The plugin package is supplied by <a href="<?php echo themeHelper::getCurrentProductUrl(); ?>" target="_blank"><?php echo themeHelper::getCurrentProductName(); ?></a> in zip format.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>Plugin Zip File:</label>
                                    <div class="input">
                                        <input name="plugin_zip" type="file" id="plugin_zip" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="clearfix col_12">
                        <div class="col_4 adminResponsiveHide">&nbsp;</div>
                        <div class="col_8 last">
                            <div class="clearfix">
                                <div class="input no-label">
                                    <input type="submit" value="Upload Plugin Package" class="button blue"/>
                                </div>
                            </div>
                        </div>
                    </div>

                    <input name="submitted" type="hidden" value="1"/>
                </form>
            </div>
        </div>   
    </div>
</div>

<?php
include_once('_footer.inc.php');
?>