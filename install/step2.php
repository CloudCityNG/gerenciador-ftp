<?php
session_start();

require_once("shared.inc.php");
require_once("settings.inc.php");
require_once("database.inc.php");
require_once("functions.inc.php");
require_once("languages.inc.php");

$program_already_installed = false;

$completed = false;
$error_mg  = array();
$task      = isset($_POST['task']) ? $_POST['task'] : "";

define('CRON_PATH', realpath(dirname(__FILE__).'/../admin/tasks'));

if ($task == "step2")
{
    $username          = isset($_POST['username']) ? stripcslashes($_POST['username']) : "";
    $password          = isset($_POST['password']) ? stripcslashes($_POST['password']) : "";
    $database_host     = isset($_POST['database_host']) ? prepare_input($_POST['database_host']) : "";
    $database_name     = isset($_POST['database_name']) ? prepare_input($_POST['database_name']) : "";
    $database_username = isset($_POST['database_username']) ? prepare_input($_POST['database_username']) : "";
    $database_password = isset($_POST['database_password']) ? prepare_input($_POST['database_password']) : "";
    $database_prefix   = isset($_POST['database_prefix']) ? stripcslashes($_POST['database_prefix']) : "";
    $install_type      = isset($_POST['install_type']) ? $_POST['install_type'] : "create";
	$secret_key        = genRandomString(128);

    if (empty($database_host))
    {
        $error_mg[] = lang_key("alert_db_host_empty");
    }
    if (empty($database_name))
    {
        $error_mg[] = lang_key("alert_db_name_empty");
    }
    if (empty($database_username))
    {
        $error_mg[] = lang_key("alert_db_username_empty");
    }

    if (empty($username))
    {
        $error_mg[] = lang_key("alert_admin_username_empty");
    }
    elseif (!preg_match('/^[a-zA-Z0-9]{4,}$/', $username))
    {
        $error_mg[] = "Username must be a minimum of 4 characters and alpha-numeric only.";
    }
    if (empty($password))
    {
        $error_mg[] = lang_key("alert_admin_password_empty");
    }
    elseif (strlen($password) < 6)
    {
        $error_mg[] = "Password must be at least 6 characters in length.";
    }

    if (empty($error_mg))
    {
        $db = Database::GetInstance($database_host, $database_name, $database_username, $database_password, EI_DATABASE_TYPE);
        if (EI_DATABASE_CREATE && ($install_type == "create") && !$db->Create())
        {
            $error_mg[] = $db->Error();
        }
        else if ($db->Open())
        {
            if (EI_CHECK_DB_MINIMUM_VERSION && (version_compare($db->GetVersion(), EI_DB_MINIMUM_VERSION, '<')))
            {
                $alert_min_version_db = lang_key("alert_min_version_db");
                $alert_min_version_db = str_replace("_DB_VERSION_", "<b>" . EI_DB_MINIMUM_VERSION . "</b>", $alert_min_version_db);
                $alert_min_version_db = str_replace("_DB_CURR_VERSION_", "<b>" . $db->GetVersion() . "</b>", $alert_min_version_db);
                $alert_min_version_db = str_replace("_DB_", "<b>" . $db->GetDbDriver() . "</b>", $alert_min_version_db);
                $error_mg[]           = $alert_min_version_db;
            }
            else
            {
                // read sql dump file
                if (false == ($db_error = db_install(EI_SQL_DUMP_FILE_CREATE)))
                {
                    $error_mg[] = lang_key("error_sql_executing");
                }
                else
                {
                    // write additional operations here, like setting up system preferences etc.
                    $completed = true;

                    // now try to create file and write information
                    $config_file = file_get_contents(EI_CONFIG_FILE_TEMPLATE);
                    $config_file = str_replace("<SITE_HOST_URL>", getInstallHost(), $config_file);
                    $config_file = str_replace("<SITE_FULL_URL>", getInstallPath(), $config_file);
                    $config_file = str_replace("<DB_HOST>", $database_host, $config_file);
                    $config_file = str_replace("<DB_NAME>", $database_name, $config_file);
                    $config_file = str_replace("<DB_USER>", $database_username, $config_file);
                    $config_file = str_replace("<DB_PASSWORD>", $database_password, $config_file);
					$config_file = str_replace("<SECRET_KEY>", $secret_key, $config_file);
                    
                    if ($_SERVER["SERVER_PORT"] == "443")
                    {
                        $config_file = str_replace("<SITE_PROTOCOL>", 'https', $config_file);
                    }
                    else
                    {
                        $config_file = str_replace("<SITE_PROTOCOL>", 'http', $config_file);
                    }

                    @chmod(EI_CONFIG_FILE_PATH, 0755);
                    $f = @fopen(EI_CONFIG_FILE_PATH, "w+");
                    if (!@fwrite($f, $config_file) > 0)
                    {
                        $error_mg[] = str_replace("_CONFIG_FILE_PATH_", EI_CONFIG_FILE_PATH, lang_key("error_can_not_open_config_file"));
                    }
                    @fclose($f);

                    // update user in database
                    $sQL = 'UPDATE users SET username=\'' . $username . '\', password=\'' . md5($password) . '\' WHERE id=1 LIMIT 1';
                    $db->Query($sQL);
                }
            }
        }
        else
        {
            $error_mg[] = str_replace("_ERROR_", "", lang_key("error_check_db_connection"));
        }
    }
}
?>	


<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head>
        <title><?php echo lang_key("installation_guide"); ?></title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <link rel="stylesheet" type="text/css" href="css/styles.css"></link>
        <!--[if IE]>
        <link rel="stylesheet" type="text/css" href="css/stylesIE.css"></link>
        <![endif]-->
        <script type="text/javascript">
            var EI_LOCAL_PATH = "language/<?php echo $curr_lang; ?>/";
        </script>
        <script type="text/javascript" src="js/main.js"></script>
        <script type="text/javascript" src="js/jquery-1.4.2.min.js"></script>
    </head>
    <body>

        <table align="center" width="1000" cellspacing="0" cellpadding="0" border="0">
            <tbody>
                <tr>
                    <td class="text" valign="top">
                        <h2><?php echo EI_APPLICATION_NAME; ?> - Installation Script</h2>
                        Follow the Wizard to setup your site configuration, database and initial admin area login.<br /><br />
                        <table width="100%" cellspacing="0" cellpadding="0" border="0">
                            <tbody>
                                <tr>
                                    <td class="gray_table">
                                        <table width="100%" cellspacing="0" cellpadding="0" border="0">
                                            <tbody>
                                                <tr><td class="ltcorner"></td><td></td><td class="rtcorner"></td></tr>
                                                <tr>
                                                    <td></td>
                                                    <td align="middle">
                                                        <table class="text mainTable" width="99%" cellspacing="0" cellpadding="0" border="0">
                                                            <tbody>
                                                                <?php
                                                                if (!$completed)
                                                                {
                                                                    ?>
                                                                    <tr>
                                                                        <td align="left">
                                                                            <h2>Site Configuration Error</h2>
                                                                        </td>
                                                                    </tr>
                                                                    <?php
                                                                    foreach ($error_mg as $msg)
                                                                    {
                                                                        echo "<tr><td class='text' align='left'><span style='color:#bb5500;'>&#8226; " . $msg . "</span></td></tr>";
                                                                    }
                                                                    ?>
                                                                    <tr><td nowrap height="25px">&nbsp;</td></tr>
                                                                    <tr>
                                                                        <td align="left">	
                                                                            <img class="form_button" src="language/<?php echo $curr_lang; ?>/buttons/button_back.gif" name="button_back" id="button_back" onmouseover="buttonOver('button_back')" onmouseout="buttonOut('button_back')" alt="" onclick="javascript: history.go(-1);" />
                                                                            &nbsp;&nbsp;&nbsp;&nbsp;
                                                                            <img class="form_button" src="language/<?php echo $curr_lang; ?>/buttons/button_retry.gif" name="button_retry" id="button_retry" onmouseover="buttonOver('button_retry')" onmouseout="buttonOut('button_retry')" alt="" onclick="javascript: location.reload();" />
                                                                        </td>
                                                                    </tr>							
                                                                    <?php
                                                                }
                                                                else
                                                                {
                                                                    ?>
                                                                    <tr><td align="left"><h2 style='color: green;'><?php echo lang_key("step_2_installation_completed"); ?></h2></td></tr>	
                                                                    <tr>
                                                                        <td align="left">
                                                                            <?php echo str_replace("_CONFIG_FILE_", EI_CONFIG_FILE_PATH, lang_key("file_successfully_created")); ?><br />
                                                                            <?php echo EI_POST_INSTALLATION_TEXT; ?><br /><br />
                                                                            
                                                                            <span class='alert'><strong>IMPORTANT</strong></span>
                                                                            <span><?php echo lang_key("alert_remove_files"); ?></span>
                                                                            <br /><br /><br />
                                                                            
                                                                            <h3><b>Final Step: Setup Cron Tasks.</b></h3>
                                                                            <hr>
                                                                            <p><?php echo EI_APPLICATION_NAME; ?> uses a number of cron (background) tasks to ensure redundant files are deleted, accounts are auto downgraded etc. Details of these are below. You can leave these until later if you want to test the installation first. See <a href="http://www.cyberciti.biz/faq/how-do-i-add-jobs-to-cron-under-linux-or-unix-oses/" target="_blank">here for more information</a> on cron tasks.</p>
                                                                            <ul style="font-family: courier,Consolas,monospace;">
                                                                                <li>0 0 * * * php <?php echo CRON_PATH; ?>/auto_prune.cron.php >> /dev/null 2>&amp;1</li>
                                                                                <li>0 0 * * * php <?php echo CRON_PATH; ?>/create_internal_notifications.cron.php >> /dev/null 2>&amp;1</li>
                                                                                <li>0 * * * * php <?php echo CRON_PATH; ?>/delete_redundant_files.cron.php >> /dev/null 2>&amp;1</li>
                                                                                <li>0 0 * * * php <?php echo CRON_PATH; ?>/downgrade_accounts.cron.php >> /dev/null 2>&amp;1</li>
                                                                                <li>*/5 * * * * php <?php echo CRON_PATH; ?>/process_file_queue.cron.php >> /dev/null 2>&amp;1</li>
																				<li>0 1 * * * php <?php echo CRON_PATH; ?>/create_email_notifications.cron.php >> /dev/null 2>&amp;1</li>
                                                                            </ul>
                                                                            
                                                                            <br />
                                                                            Remove the "install" folder then <a href="<?php echo "../" . EI_APPLICATION_START_FILE; ?>"><?php echo lang_key("proceed_to_login_page"); ?></a> or <a href="<?php echo "../" . EI_APPLICATION_START_FILE; ?>admin/"><?php echo lang_key("proceed_to_admin_page"); ?></a>
                                                                        </td>
                                                                    </tr>
                                                                    <?php
                                                                }
                                                                ?>
                                                            </tbody>
                                                        </table>
                                                        <br />
                                                    </td>
                                                    <td></td>
                                                </tr>
                                                <tr><td class="lbcorner"></td><td></td><td class="rbcorner"></td></tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <?php include_once("footer.php"); ?>        
                    </td>
                </tr>
            </tbody>
        </table>

    </body>
</html>