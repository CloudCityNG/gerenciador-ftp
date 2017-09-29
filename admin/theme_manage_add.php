<?php
// initial constants
define('ADMIN_PAGE_TITLE', 'Add Theme');
define('ADMIN_SELECTED_PAGE', 'themes');
define('ADMIN_SELECTED_SUB_PAGE', 'theme_manage_add');

// includes and security
include_once('_local_auth.inc.php');

// pclzip
include_once(CORE_ROOT . '/includes/pclzip/pclzip.lib.php');

// check for write permissions on the themes folder
if(!is_writable(SITE_THEME_DIRECTORY_ROOT))
{
    adminFunctions::setError(adminFunctions::t("error_theme_folder_is_not_writable", "Theme folder is not writable. Ensure you set the following folder to CHMOD 755 or 777: [[[THEME_FOLDER]]]", array('THEME_FOLDER'=>SITE_THEME_DIRECTORY_ROOT)));
}

// handle page submissions
if (isset($_REQUEST['submitted']))
{
    // get variables
    $file = $_FILES['theme_zip'];

    // delete existing tmp folder
    $tmpPath = SITE_THEME_DIRECTORY_ROOT . '_tmp';
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
                adminFunctions::setError(adminFunctions::t("error_creating_theme_dir", "There was a problem creating the theme folder. Please ensure the following folder has CHMOD 777 permissions: [[[THEME_FOLDER]]] and the theme _tmp folder does NOT exist: [[[TMP_FOLDER]]]", array('THEME_FOLDER'=>SITE_THEME_DIRECTORY_ROOT, 'TMP_FOLDER'=>$tmpPath)));
            }

            if (adminFunctions::isErrors() == false)
            {
                $zip->extract(PCLZIP_OPT_PATH, $tmpPath . '/');

                // try to read the theme details
                if (!file_exists($tmpPath . '/_theme_config.inc.php'))
                {
                    adminFunctions::setError(adminFunctions::t("error_reading_theme_details", "Could not read the theme settings file '_theme_config.inc.php'."));
                }

                if (adminFunctions::isErrors() == false)
                {
                    include_once($tmpPath . '/_theme_config.inc.php');
                    if (!isset($themeConfig['folder_name']))
                    {
                        adminFunctions::setError(adminFunctions::t("error_reading_theme_folder_name", "Could not read the theme folder name from '_theme_config.inc.php'."));
                    }

                    if (adminFunctions::isErrors() == false)
                    {
                        // rename tmp folder
                        if (!rename($tmpPath, SITE_THEME_DIRECTORY_ROOT . $themeConfig['folder_name']))
                        {
                            adminFunctions::setError(adminFunctions::t("error_renaming_theme_folder", "Could not rename theme folder, it may be that the theme is already installed or a permissions issue."));
                        }
                        else
                        {
                            // redirect to theme listing
                            adminFunctions::redirect('theme_manage.php?sa=1');
                        }
                    }
                }
            }
        }
        else
        {
            adminFunctions::setError(adminFunctions::t("error_problem_unzipping_the_file", "There was a problem unzipping the file, please try and manually upload the zip files contents into the themes directory or contact support."));
        }
    }
}

// page header
include_once('_header.inc.php');
?>

<div class="row clearfix">
    <div class="col_12">
        <div class="sectionLargeIcon largeThemeIcon"></div>
        <div class="widget clearfix">
            <h2>Add Theme</h2>
            <div class="widget_inside">
                <?php echo adminFunctions::compileNotifications(); ?>
                <form method="POST" action="theme_manage_add.php" name="themeForm" id="themeForm" enctype="multipart/form-data">
                    <div class="clearfix col_12">
                        <div class="col_4">
                            <h3>Upload Theme Package</h3>
                            <p>Upload the theme package using the form on the right. The theme package is supplied by <a href="<?php echo themeHelper::getCurrentProductUrl(); ?>" target="_blank"><?php echo themeHelper::getCurrentProductName(); ?></a> in zip format.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>Theme Zip File:</label>
                                    <div class="input">
                                        <input name="theme_zip" type="file" id="theme_zip" />
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
                                    <input type="submit" value="Upload Theme Package" class="button blue"/>
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