<?php
error_reporting(E_ALL);
// initial constants
define('ADMIN_PAGE_TITLE', 'Themes');
define('ADMIN_SELECTED_PAGE', 'themes');
define('ADMIN_SELECTED_SUB_PAGE', 'theme_manage');

// includes and security
include_once('_local_auth.inc.php');

// import any new themes as uninstalled
themeHelper::clearCachedThemeSettings();
themeHelper::registerThemes();

// update theme config cache
themeHelper::loadThemeConfigurationFiles(true);

if(isset($_REQUEST['activate']))
{
    // validate submission
    if (_CONFIG_DEMO_MODE == true)
    {
        adminFunctions::setError(adminFunctions::t("no_changes_in_demo_mode"));
    }
    
    if (adminFunctions::isErrors() == false)
    {
        $folderName = trim($_REQUEST['activate']);

        // double check the folder exists
        $themeExists = (int)$db->getValue('SELECT COUNT(id) AS total FROM theme WHERE folder_name = '.$db->quote($folderName));
        if($themeExists)
        {
            // activate theme
            $db->query('UPDATE theme SET is_installed = 0 WHERE is_installed = 1');
            $db->query('UPDATE theme SET is_installed = 1 WHERE folder_name = '.$db->quote($folderName));
            $db->query('UPDATE site_config SET config_value = '.$db->quote($folderName).' WHERE config_key = \'site_theme\' LIMIT 1');

            // success message
            adminFunctions::setSuccess('Theme successfully set to '.adminFunctions::makeSafe($folderName));
        }
        else
        {
            adminFunctions::setError('Can not find theme to set active.');
        }
    }
}

if(isset($_REQUEST['delete']))
{
    $delete = trim($_REQUEST['delete']);
    // validate submission
    if (_CONFIG_DEMO_MODE == true)
    {
        adminFunctions::setError(adminFunctions::t("no_changes_in_demo_mode"));
    } 
    elseif(strlen($delete) == 0)
    {
        adminFunctions::setError('Can not find a theme to delete.');
    }

    if(adminFunctions::isErrors() == false)
    {
        $themeDetails = $db->getRow("SELECT * FROM theme WHERE folder_name = '".$db->escape($delete)."' AND is_installed = '0' LIMIT 1");
        if(!$themeDetails)
        {
            adminFunctions::setError('Could not get the theme details, please try again.');
        }
        else
        {
            $themePath = SITE_THEME_DIRECTORY_ROOT . $themeDetails['folder_name'];
            if (file_exists($themePath))
            {
                if(adminFunctions::recursiveThemeDelete($themePath) == false)
                {
                    adminFunctions::setError('Could not delete some files, please delete them manually.');
                }
            }
            if(file_exists($themePath))
            {
                if(!rmdir($themePath))
                {
                    adminFunctions::setError('Could not delete some files, please delete them manually.');
                }
            }            
        }
        if(adminFunctions::isErrors() == false)
        {
            $db->query("DELETE FROM theme WHERE folder_name = '".$themeDetails['folder_name']."'");
            adminFunctions::redirect(ADMIN_WEB_ROOT.'/theme_manage.php?de=1');
        }
    }
}

// error/success messages
if (isset($_REQUEST['sa']))
{
    adminFunctions::setSuccess('Theme successfully added. Activate it below.');
}
elseif(isset($_REQUEST['de']))
{
    adminFunctions::setSuccess('Theme successfully deleted.');
}
elseif (isset($_REQUEST['error']))
{
    adminFunctions::setError(urldecode($_REQUEST['error']));
}

// load current theme from config, can not use the SITE_CONFIG_SITE_THEME constant encase it's been changed
$siteTheme = $db->getValue('SELECT config_value FROM site_config WHERE config_key = \'site_theme\' LIMIT 1');

// load all themes
$sQL  = "SELECT * FROM theme ORDER BY theme_name";
$limitedRS = $db->getRows($sQL);

// page header
include_once('_header.inc.php');
?>
<div class="row clearfix">
    <div class="sectionLargeIcon largeThemeIcon"></div>
    <div class="widget clearfix">
        <h2>Manage Themes</h2>
        <div class="widget_inside themeContainer">
            <?php echo adminFunctions::compileNotifications(); ?>
            <span id="messageContainer"></span>
            <?php
            $tracker = 1;
            foreach ($limitedRS AS $row)
            {
				// check for settings file
				$settingsPath = '';
				if(file_exists(SITE_THEME_DIRECTORY_ROOT.$row['folder_name'].'/admin/settings.php'))
				{
					$settingsPath = SITE_THEME_WEB_ROOT.$row['folder_name'].'/admin/settings.php';
				}
                ?>
                <div class="col_12" style="padding-bottom: 12px;">
                    <div class="col_3">
                        <img src="<?php echo SITE_THEME_WEB_ROOT; ?><?php echo adminFunctions::makeSafe($row['folder_name']); ?>/thumb_preview.png"/>
                    </div>
                    <div class="col_9 last">
                        <div class="manage-theme-title">
                            <h3><?php echo adminFunctions::makeSafe($row['theme_name']); ?><?php echo $row['folder_name']==$siteTheme?('&nbsp;&nbsp;<span style="color: green;">(active)</a>'):''; ?></h3>
                        </div>
                        <div class="adminResponsiveClear"></div>
                        <div class="manage-theme-buttons">
                        <?php if($row['folder_name'] != $siteTheme): ?>
                          <a class="button red" type="submit" href="<?php echo ADMIN_WEB_ROOT; ?>/theme_manage.php?delete=<?php echo adminFunctions::makeSafe($row['folder_name']); ?>" onClick="return confirm('Are you sure that you want to completely delete the theme files from the server.');"><span>Delete</span></a>
                          <?php endif; ?>
							<?php if(strlen($settingsPath)): ?>
								<a class="button black" href="<?php echo $settingsPath; ?>"><span>Settings</span></a>
							<?php endif; ?>
                            <?php if($row['folder_name'] != $siteTheme): ?>
								<a class="button" onClick="return confirm('This will set your current logged in session to use this theme, switch back by logging out or by clicking the preview for the original on this page.');" href="<?php echo CORE_PAGE_WEB_ROOT; ?>/set_theme.php?theme=<?php echo adminFunctions::makeSafe($row['folder_name']); ?>" target="_blank"><span>Preview</span></a>
                                <a class="button blue" onClick="return confirm('Are you sure you want to enable this theme? The website will be immediately updated.');" href="theme_manage.php?activate=<?php echo adminFunctions::makeSafe($row['folder_name']); ?>"><span>Activate</span></a>
                            <?php endif; ?>
                        </div>
                        <table class="datatable">
                            <tbody>
                                <tr>
                                    <td>Description</td>
                                    <td><?php echo adminFunctions::makeSafe($row['theme_description']); ?></td>
                                </tr>
                                <tr>
                                    <td>Author</td>
                                    <td><?php echo adminFunctions::makeSafe($row['author_name']); ?><?php echo strlen($row['author_website'])?(' (<a href="'.adminFunctions::makeSafe($row['author_website']).'" target="_blank">'.adminFunctions::makeSafe($row['author_website']).'</a>)'):''; ?></td>
                                </tr>
                                <tr>
                                    <td>Directory</td>
                                    <td><?php echo adminFunctions::makeSafe($row['folder_name']); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php
                $tracker++;
            }
            ?>
            
            <div class="clear" style="padding-bottom: 10px;"><!-- --></div>
            <input type="submit" value="Add Theme" class="button blue" onClick="window.location='theme_manage_add.php';"/>
            <input type="submit" value="Get Themes" class="button blue" onClick="window.open('<?php echo themeHelper::getCurrentProductUrl(); ?>/themes.html','_blank');"/>
        </div>
    </div>
</div>

<?php
include_once('_footer.inc.php');
?>