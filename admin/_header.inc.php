<?php
if (!defined('ADMIN_PAGE_TITLE'))
{
    define('ADMIN_PAGE_TITLE', adminFunctions::t("admin_area", "admin area"));
}
if (!defined('ADMIN_SELECTED_PAGE'))
{
    define('ADMIN_SELECTED_PAGE', 'dashboard');
}
if (!defined('ADMIN_SELECTED_SUB_PAGE'))
{
    define('ADMIN_SELECTED_SUB_PAGE', 'dashboard');
}
$AuthUser                = Auth::getAuth();
$db                      = Database::getDatabase();
$totalReports            = (int) $db->getValue("SELECT COUNT(id) AS total FROM file_report WHERE report_status='pending'");
$totalPendingFileActions = (int) $db->getValue('SELECT COUNT(id) AS total FROM file_action WHERE status=\'pending\' OR status=\'processing\'');
?>
<html lang="en-us">

    <head>
        <meta http-equiv="X-UA-Compatible" content="IE=Edge;chrome=1" >
        <meta charset="utf-8" />

        <link rel="apple-touch-con" href="" />

        <title><?php echo adminFunctions::makeSafe(UCwords(ADMIN_PAGE_TITLE)); ?> - Admin</title>

        <meta name="viewport" content="width=device-width, minimum-scale=1.0, maximum-scale=1.0">

        <!-- The Columnal Grid and mobile stylesheet -->
        <link rel="stylesheet" href="<?php echo ADMIN_WEB_ROOT; ?>/assets/styles/columnal/columnal.css" type="text/css" media="screen" />

        <!-- Fixes for IE -->

        <!--[if lt IE 9]>
            <link rel="stylesheet" href="<?php echo ADMIN_WEB_ROOT; ?>/assets/styles/columnal/ie.css" type="text/css" media="screen" />
            <link rel="stylesheet" href="<?php echo ADMIN_WEB_ROOT; ?>/assets/styles/ie8.css" type="text/css" media="screen" />
            <script src="<?php echo ADMIN_WEB_ROOT; ?>/assets/scripts/IE9.js"></script>
        <![endif]-->        


        <!-- Use CDN on production server -->
        <script src="<?php echo ADMIN_WEB_ROOT; ?>/assets/scripts/jquery.min.js"></script>
        <script src="<?php echo ADMIN_WEB_ROOT; ?>/assets/scripts/jquery-ui.min.js"></script>

        <!-- Now that all the grids are loaded, we can move on to the actual styles. --> 
        <link rel="stylesheet" href="<?php echo ADMIN_WEB_ROOT; ?>/assets/scripts/jqueryui/jqueryui.css" type="text/css" media="screen" />
        <link rel="stylesheet" href="<?php echo ADMIN_WEB_ROOT; ?>/assets/styles/style.css" type="text/css" media="screen" />
        <link rel="stylesheet" href="<?php echo ADMIN_WEB_ROOT; ?>/assets/styles/global.css" type="text/css" media="screen" />
        <link rel="stylesheet" href="<?php echo ADMIN_WEB_ROOT; ?>/assets/styles/config.css" type="text/css" media="screen" />
        <link rel="stylesheet" href="<?php echo ADMIN_WEB_ROOT; ?>/assets/styles/responsive.css" type="text/css" media="screen" />

        <!-- Adds HTML5 Placeholder attributes to those lesser browsers (i.e. IE) -->
        <script type="text/javascript" src="<?php echo ADMIN_WEB_ROOT; ?>/assets/scripts/jquery.placeholder.1.2.min.shrink.js"></script>

        <!-- Sortable, searchable DataTable -->
        <script src="<?php echo ADMIN_WEB_ROOT; ?>/assets/scripts/jquery.dataTables.min.js"></script>

        <!-- Adds HTML5 Placeholder attributes to those lesser browsers (i.e. IE) -->
        <script type="text/javascript" src="<?php echo ADMIN_WEB_ROOT; ?>/assets/scripts/jquery.placeholder.1.2.min.shrink.js"></script>

        <!-- Adds charts -->
        <script type="text/javascript" src="<?php echo ADMIN_WEB_ROOT; ?>/assets/scripts/flot/jquery.flot.min.js"></script>
        <script type="text/javascript" src="<?php echo ADMIN_WEB_ROOT; ?>/assets/scripts/flot/jquery.flot.pie.min.js"></script>
        <script type="text/javascript" src="<?php echo ADMIN_WEB_ROOT; ?>/assets/scripts/flot/jquery.flot.stack.min.js"></script>

        <!-- Form Validation Engine -->
        <script src="<?php echo ADMIN_WEB_ROOT; ?>/assets/scripts/formvalidator/jquery.validationEngine.js"></script>
        <script src="<?php echo ADMIN_WEB_ROOT; ?>/assets/scripts/formvalidator/jquery.validationEngine-en.js"></script>
        <link rel="stylesheet" href="<?php echo ADMIN_WEB_ROOT; ?>/assets/scripts/formvalidator/validationEngine.jquery.css" type="text/css" media="screen" />

        <!-- Custom Tooltips -->
        <script src="<?php echo ADMIN_WEB_ROOT; ?>/assets/scripts/twipsy.js"></script>

        <!-- WYSIWYG Editor -->
        <script src="<?php echo ADMIN_WEB_ROOT; ?>/assets/scripts/cleditor/jquery.cleditor.min.js"></script>
        <link rel="stylesheet" href="<?php echo ADMIN_WEB_ROOT; ?>/assets/scripts/cleditor/jquery.cleditor.css" type="text/css" media="screen" />

        <!-- Colorbox is a lightbox alternative-->
        <script src="<?php echo ADMIN_WEB_ROOT; ?>/assets/scripts/colorbox/jquery.colorbox-min.js"></script>
        <link rel="stylesheet" href="<?php echo ADMIN_WEB_ROOT; ?>/assets/scripts/colorbox/colorbox.css" type="text/css" media="screen" />

        <!-- Menu -->
        <link rel="stylesheet" href="<?php echo ADMIN_WEB_ROOT; ?>/assets/scripts/superfish/superfish.css" type="text/css" media="screen" />
        <script src="<?php echo ADMIN_WEB_ROOT; ?>/assets/scripts/superfish/superfish.js"></script>

        <!-- ddslick, for images in dropdown menus -->
        <script src="<?php echo ADMIN_WEB_ROOT; ?>/assets/scripts/jquery.ddslick.min.js"></script>

        <!-- Js used in the theme -->
        <script src="<?php echo ADMIN_WEB_ROOT; ?>/assets/scripts/global.js"></script>
		
		<!-- append any theme admin css -->
		<?php
		$adminThemeCss = themeHelper::getAdminThemeCss();
		if($adminThemeCss)
		{
			echo '<link rel="stylesheet" href="'.$adminThemeCss.'" type="text/css" media="screen" />';
		}
		?>

    </head>
    <body>
        <div id="wrap">
            <div id="main">
                <header class="container">
                    <div class="row clearfix">
                        <div class="responsiveHeaderWrapper">
                            <div class="left">
                                <a href="<?php echo ADMIN_WEB_ROOT; ?>/index.php" id="logo"><?php echo adminFunctions::t("admin_area", "admin area"); ?></a>
                            </div>

                            <div class="right">
                                <ul id="toolbar">
                                    <li><span><?php echo adminFunctions::t("logged_in_as", "Logged in as"); ?></span> <a class="user" href="<?php echo ADMIN_WEB_ROOT; ?>/user_edit.php?id=<?php echo $AuthUser->id; ?>"><?php echo $AuthUser->username; ?></a></li>
                                    <?php
                                    $systemAlertErrorStr     = '';

                                    // output error
                                    if (file_exists('../install/'))
                                    {
                                        $systemAlertErrorStr = '<strong>IMPORTANT:</strong><br/><br/>Remove the /install/ folder within your webroot asap.';
                                    }

                                    // output error
                                    if (strlen($systemAlertErrorStr))
                                    {
                                        ?>
                                        <li>
                                            <a id="alertYellow" href="#" onClick="$('#alertMessage').dialog('open');
                                                    return false;"><?php echo adminFunctions::t("alert", "Alert"); ?></a>
                                        </li>
                                        <?php
                                    }
                                    ?>
									<li><a id="settings" href="<?php echo ADMIN_WEB_ROOT; ?>/setting_manage.php">Settings</a></li>
                                    <li><a id="search" href="#"><?php echo adminFunctions::t("search", "Search"); ?></a></li>
                                </ul>
                                <div id="searchdrop">
                                    <form action="<?php echo ADMIN_WEB_ROOT; ?>/file_manage.php" method="POST">
                                        <input type="text" id="searchbox" name="filterText" placeholder="<?php echo adminFunctions::t('header_search_files', 'Search files...'); ?>">
                                    </form>
                                </div>
                            </div>
                        </div>  
                    </div>
                </header>


                <nav class="container" style="background-color: #3B4966;">
                    <select class="mobile-only row" onchange="window.location = this.options[this.selectedIndex].value;">
                        <optgroup label="<?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('your_account', 'Your Account')))); ?>">
                            <option <?php if (ADMIN_SELECTED_SUB_PAGE == "dashboard") echo 'selected'; ?> value="<?php echo ADMIN_WEB_ROOT; ?>/index.php"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('dashboard', 'dashboard')))); ?></option>
                            <option value="<?php echo ADMIN_WEB_ROOT; ?>/logout.php"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('logout', 'logout')))); ?></option>
                        </optgroup>

                        <optgroup label="<?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('files', 'files')))); ?>">
                            <option <?php if (ADMIN_SELECTED_SUB_PAGE == "file_manage") echo 'selected'; ?> value="<?php echo ADMIN_WEB_ROOT; ?>/file_manage.php"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('manage_files', 'manage files')))); ?></option>
                            
                            <option <?php if (ADMIN_SELECTED_SUB_PAGE == "file_manage_queue") echo 'selected'; ?> value="<?php echo ADMIN_WEB_ROOT; ?>/file_manage_action_queue.php"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('action_queue', 'action queue')))); ?> (<?php echo $totalPendingFileActions; ?>)</option>
                            
                            
                            <?php if ($AuthUser->hasAccessLevel(20)): ?>
                                <option <?php if (ADMIN_SELECTED_SUB_PAGE == "download_current") echo 'selected'; ?> value="<?php echo ADMIN_WEB_ROOT; ?>/download_current.php" class="active_downloads"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('active_downloads', 'active downloads')))); ?></option>
                            <?php endif; ?>
                            <option <?php if (ADMIN_SELECTED_SUB_PAGE == "file_report_manage") echo 'selected'; ?> value="<?php echo ADMIN_WEB_ROOT; ?>/file_report_manage.php"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('abuse_reports', 'abuse reports')))); ?><?php if ($totalReports > 0): ?> (<?php echo $totalReports; ?>)<?php endif; ?></option>
                            <option <?php if (ADMIN_SELECTED_SUB_PAGE == "server_manage") echo 'selected'; ?> value="<?php echo ADMIN_WEB_ROOT; ?>/server_manage.php" <?php if (ADMIN_SELECTED_PAGE == 'file_servers') echo ' class="active"'; ?>><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('file_servers', 'file servers')))); ?></option>
                        </optgroup>

                        <?php if ($AuthUser->hasAccessLevel(20)): ?>
                            <optgroup label="<?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('users', 'users')))); ?>">
                                <option <?php if (ADMIN_SELECTED_SUB_PAGE == "user_manage") echo 'selected'; ?> value="<?php echo ADMIN_WEB_ROOT; ?>/user_manage.php"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('manage_users', 'manage users')))); ?></option>
                                <option <?php if (ADMIN_SELECTED_SUB_PAGE == "user_add") echo 'selected'; ?> value="<?php echo ADMIN_WEB_ROOT; ?>/user_add.php"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('add_user', 'add user')))); ?></option>
                                <option <?php if (ADMIN_SELECTED_SUB_PAGE == "payment_manage") echo 'selected'; ?> value="<?php echo ADMIN_WEB_ROOT; ?>/payment_manage.php" class="nav_received_payments"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('received_payments', 'received payments')))); ?></option>
                            </optgroup>
                        <?php endif; ?>

                        <?php
						// add any theme navigation
                        echo themeHelper::getThemeAdminNavDropdown();
						
                        // add any plugin navigation
                        echo pluginHelper::getPluginAdminNavDropdown();
                        ?>

                        <?php if ($AuthUser->hasAccessLevel(20)): ?>
                            <optgroup label="<?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('plugins', 'Plugins')))); ?>" class="nav_plugins">
                                <option <?php if (ADMIN_SELECTED_SUB_PAGE == "plugin_manage") echo 'selected'; ?> value="<?php echo ADMIN_WEB_ROOT; ?>/plugin_manage.php"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('manage_plugins', 'manage plugins')))); ?></option>
                                <option <?php if (ADMIN_SELECTED_SUB_PAGE == "plugin_manage_add") echo 'selected'; ?> value="<?php echo ADMIN_WEB_ROOT; ?>/plugin_manage_add.php"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('add_plugin', 'add plugin')))); ?></option>
                                <option value="<?php echo themeHelper::getCurrentProductUrl(); ?>/plugins.html" target="_blank"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('get_plugin', 'get plugins')))); ?></option>
                            </optgroup>


                            <optgroup label="<?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('Configuration', 'Configuration')))); ?>">
                                <option <?php if (ADMIN_SELECTED_SUB_PAGE == "setting_manage") echo 'selected'; ?> value="<?php echo ADMIN_WEB_ROOT; ?>/setting_manage.php"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('site_settings', 'site settings')))); ?></option>
                                <option <?php if (ADMIN_SELECTED_SUB_PAGE == "translation_manage") echo 'selected'; ?> value="<?php echo ADMIN_WEB_ROOT; ?>/translation_manage.php"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('translations', 'translations')))); ?></option>
                                <option <?php if (ADMIN_SELECTED_SUB_PAGE == "download_page_manage") echo 'selected'; ?> value="<?php echo ADMIN_WEB_ROOT; ?>/download_page_manage.php" class="nav_manage_download_pages"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('manage_download_pages', 'manage download pages')))); ?></option>            
                                <option <?php if (ADMIN_SELECTED_SUB_PAGE == "account_level") echo 'selected'; ?> value="<?php echo ADMIN_WEB_ROOT; ?>/account_package_manage.php" class="nav_account_packages"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('account_packages', 'account packages')))); ?></option>
                                <option <?php if (ADMIN_SELECTED_SUB_PAGE == "banned_ip_manage") echo 'selected'; ?> value="<?php echo ADMIN_WEB_ROOT; ?>/banned_ip_manage.php"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('banned_ips', 'banned ips')))); ?></option>
                                <option <?php if (ADMIN_SELECTED_SUB_PAGE == "theme_manage") echo 'selected'; ?> value="<?php echo ADMIN_WEB_ROOT; ?>/theme_manage.php"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('manage_themes', 'manage themes')))); ?></option>
                                <option <?php if (ADMIN_SELECTED_SUB_PAGE == "log_file_viewer") echo 'selected'; ?> value="<?php echo ADMIN_WEB_ROOT; ?>/log_file_viewer.php"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('system_logs', 'system logs')))); ?></option>
                                <option <?php if (ADMIN_SELECTED_SUB_PAGE == "background_task") echo 'selected'; ?> value="<?php echo ADMIN_WEB_ROOT; ?>/background_task_manage.php"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('background_task_logs', 'background task logs')))); ?></option>
                                <option <?php if (ADMIN_SELECTED_SUB_PAGE == "support_info") echo 'selected'; ?> value="<?php echo ADMIN_WEB_ROOT; ?>/support_info.php"><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('support_info', 'support info')))); ?></option>
                            
                            </optgroup>

                        <?php endif; ?>
                    </select>


                    <ul class="sf-menu mobile-hide row clearfix">
                        <li class="<?php if (ADMIN_SELECTED_PAGE == 'dashboard') echo 'active'; ?> iconed"><a href="<?php echo ADMIN_WEB_ROOT; ?>/index.php?t=dashboard"><span><img src="<?php echo ADMIN_WEB_ROOT; ?>/assets/images/header/icon_dashboard.png" /> <?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('dashboard', 'dashboard')))); ?></span></a></li>
                        <li<?php if ((ADMIN_SELECTED_PAGE == 'files') || (ADMIN_SELECTED_PAGE == 'downloads')) echo ' class="active"'; ?>><a href="<?php echo ADMIN_WEB_ROOT; ?>/file_manage.php"><span><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('files', 'files')))); ?></span></a>
                            <ul>
                                <li><a href="<?php echo ADMIN_WEB_ROOT; ?>/file_manage.php"><span><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('manage_files', 'manage files')))); ?></span></a></li>
                                <?php if ($AuthUser->hasAccessLevel(20)): ?>
                                    <li class="active_downloads nav_active_downloads"><a href="<?php echo ADMIN_WEB_ROOT; ?>/download_current.php"><span><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('active_downloads', 'active downloads')))); ?></span></a></li>
                                    <li><a href="<?php echo ADMIN_WEB_ROOT; ?>/file_manage_action_queue.php"><span><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('action_queue', 'action queue')))); ?> (<?php echo $totalPendingFileActions; ?>)</span></a></li>
                                <?php endif; ?>
                                <li class="separator"><a href="<?php echo ADMIN_WEB_ROOT; ?>/file_report_manage.php"><span><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('abuse_reports', 'abuse reports')))); ?><?php if ($totalReports > 0): ?> (<?php echo $totalReports; ?>)<?php endif; ?></span></a></li>
                            </ul>
                        </li>

                        <?php if ($AuthUser->hasAccessLevel(20)): ?>
                            <li<?php if (ADMIN_SELECTED_PAGE == 'users') echo ' class="active"'; ?>><a href="<?php echo ADMIN_WEB_ROOT; ?>/user_manage.php"><span><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('users', 'users')))); ?></span></a>
                                <ul>
                                    <li class="nav_manage_users"><a href="<?php echo ADMIN_WEB_ROOT; ?>/user_manage.php"><span><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('manage_users', 'manage users')))); ?></span></a></li>
                                    <li class="nav_add_user"><a href="<?php echo ADMIN_WEB_ROOT; ?>/user_add.php"><span><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('add_user', 'add user')))); ?></span></a></li>
                                    <li class="nav_received_payments"><a href="<?php echo ADMIN_WEB_ROOT; ?>/payment_manage.php"><span><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('received_payments', 'received payments')))); ?></span></a></li>
                                </ul>
                            </li>
                            <li<?php if (ADMIN_SELECTED_PAGE == 'file_servers') echo ' class="active"'; ?>><a href="<?php echo ADMIN_WEB_ROOT; ?>/server_manage.php"><span><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('file_servers', 'file servers')))); ?></span></a></li>

                            <?php
							
							// add any theme navigation
							echo themeHelper::getThemeAdminNav();
						
                            // add any plugin navigation
                            echo pluginHelper::getPluginAdminNav();
                            ?>

                        <?php endif; ?>

                        <li style="float: right;"><a href="<?php echo ADMIN_WEB_ROOT; ?>/logout.php"><span><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('logout', 'logout')))); ?></span></a></li>

                        <?php if ($AuthUser->hasAccessLevel(20)): ?>
                            <li<?php if (ADMIN_SELECTED_PAGE == 'configuration') echo ' class="active"'; ?> style="float: right;"><a href="#"><span><?php echo adminFunctions::t("configuration", "Configuration"); ?></span></a>
                                <ul>
                                    <li><a href="<?php echo ADMIN_WEB_ROOT; ?>/setting_manage.php"><span><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('site_settings', 'site settings')))); ?></span></a></li>
									<li><a href="#"><span><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('user_settings', 'user settings')))); ?></span></a>
                                        <ul>
											<li class="nav_manage_download_pages"><a href="<?php echo ADMIN_WEB_ROOT; ?>/download_page_manage.php"><span><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('manage_download_pages', 'manage download pages')))); ?></span></a></li>
											<li class="nav_account_packages"><a href="<?php echo ADMIN_WEB_ROOT; ?>/account_package_manage.php"><span><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('account_packages', 'account packages')))); ?></span></a></li>
											<li><a href="<?php echo ADMIN_WEB_ROOT; ?>/banned_ip_manage.php"><span><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('banned_ips', 'banned ips')))); ?></span></a></li>
                                        </ul>
                                    </li>

                                    <li><a href="<?php echo ADMIN_WEB_ROOT; ?>/translation_manage.php"><span><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('translations', 'translations')))); ?></span></a></li>

                                    <li><a href="<?php echo ADMIN_WEB_ROOT; ?>/theme_manage.php"><span><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('themes', 'themes')))); ?></span></a>
                                        <ul>
                                            <li><a href="<?php echo ADMIN_WEB_ROOT; ?>/theme_manage.php"><span><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('manage_themes', 'manage themes')))); ?></span></a></li>
                                            <li><a href="<?php echo ADMIN_WEB_ROOT; ?>/theme_manage_add.php"><span><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('add_theme', 'add theme')))); ?></span></a></li>
                                            <li><a href="<?php echo themeHelper::getCurrentProductUrl(); ?>/themes.html" target="_blank"><span><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('get_themes', 'get themes')))); ?></span></a></li>
                                        </ul>
                                    </li>
                                    <li><a href=""><span><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('system_tools', 'system tools')))); ?></span></a>
                                        <ul>
                                            <li><a href="<?php echo ADMIN_WEB_ROOT; ?>/log_file_viewer.php"><span><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('system_logs', 'system logs')))); ?></span></a></li>
                                            <li><a href="<?php echo ADMIN_WEB_ROOT; ?>/background_task_manage.php"><span><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('background_task_logs', 'background task logs')))); ?></span></a></li>
                                            <li><a href="<?php echo ADMIN_WEB_ROOT; ?>/database_browser.php?username=&db=<?php echo _CONFIG_DB_NAME; ?>"><span><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('database_browser', 'database browser')))); ?></span></a></li>
											<li><a href="<?php echo ADMIN_WEB_ROOT; ?>/backup_manage.php"><span><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('backups', 'backups')))); ?></span></a></li>
                                            <li><a href="<?php echo ADMIN_WEB_ROOT; ?>/server_info.php"><span><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('server_info', 'server info')))); ?></span></a></li>
											<li><a href="<?php echo ADMIN_WEB_ROOT; ?>/support_info.php"><span><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('support_info', 'support info')))); ?></span></a></li>
                                        </ul>
                                    </li>
                                </ul>
                            </li>

                            <?php
                            $sQL        = "SELECT * FROM plugin WHERE is_installed = 1 ORDER BY plugin_name";
                            $pluginList = $db->getRows($sQL);
                            ?>

                            <li class="nav_plugins<?php if (ADMIN_SELECTED_PAGE == 'plugins') echo ' active'; ?>" style="float: right;"><a href="#"><span><?php echo adminFunctions::t("plugins", "Plugins"); ?></span></a>
                                <ul>
                                    <li><a href="<?php echo ADMIN_WEB_ROOT; ?>/plugin_manage.php"><span><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('manage_plugins', 'manage plugins')))); ?></span></a>
                                        <?php if (COUNT($pluginList)): ?>
                                            <ul>
                                                <?php
                                                foreach ($pluginList AS $k => $pluginItem)
                                                {
                                                    if ($k < 10)
                                                    {
                                                        ?>
                                                        <li><a href="<?php echo PLUGIN_WEB_ROOT; ?>/<?php echo adminFunctions::makeSafe($pluginItem['folder_name']); ?>/admin/settings.php?id=<?php echo (int) $pluginItem['id']; ?>"><span><?php echo adminFunctions::makeSafe($pluginItem['plugin_name']); ?></span></a></li>
                                                        <?php
                                                    }
                                                }
                                                ?>
                                                <li><a href="<?php echo ADMIN_WEB_ROOT; ?>/plugin_manage.php"><span><?php echo adminFunctions::t("more", "more...."); ?></span></a></li>
                                            </ul>
                                        <?php endif; ?>
                                    </li>
                                    <li><a href="<?php echo ADMIN_WEB_ROOT; ?>/plugin_manage_add.php"><span><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('add_plugin', 'add plugin')))); ?></span></a></li>
                                    <li><a href="<?php echo themeHelper::getCurrentProductUrl(); ?>/plugins.html" target="_blank"><span><?php echo adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t('get_plugin', 'get plugins')))); ?></span></a></li>
                                </ul>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>

                <div id="titlediv">
                    <div class="clearfix container" id="pattern">
                        <div class="row">
                            <div class="col_12">
                                <h1><?php echo adminFunctions::makeSafe(UCwords(ADMIN_PAGE_TITLE)); ?></h1>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="container" id="actualbody">
                    <div class="notificationHeader" id="notificationHeader"></div>