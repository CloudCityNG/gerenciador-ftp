<?php
session_start();

require_once("settings.inc.php");
require_once("functions.inc.php");
require_once("languages.inc.php");

$program_already_installed = false;

ob_start();

if (function_exists('phpinfo'))
{
    @phpinfo(-1);
}
$phpinfo = array('phpinfo' => array());
if (preg_match_all('#(?:<h2>(?:<a name=".*?">)?(.*?)(?:</a>)?</h2>)|(?:<tr(?: class=".*?")?><t[hd](?: class=".*?")?>(.*?)\s*</t[hd]>(?:<t[hd](?: class=".*?")?>(.*?)\s*</t[hd]>(?:<t[hd](?: class=".*?")?>(.*?)\s*</t[hd]>)?)?</tr>)#s', ob_get_clean(), $matches, PREG_SET_ORDER))
{
    foreach ($matches as $match)
    {
        if (strlen($match[1]))
        {
            $phpinfo[$match[1]] = array();
        }
        else if (isset($match[3]))
        {
            $phpinfo[end(array_keys($phpinfo))][$match[2]] = isset($match[4]) ? array($match[3], $match[4]) : $match[3];
        }
        else
        {
            $phpinfo[end(array_keys($phpinfo))][] = $match[2];
        }
    }
}

$is_error = false;
$error_mg = array();
if (EI_CHECK_PHP_MINIMUM_VERSION && (version_compare(phpversion(), EI_PHP_MINIMUM_VERSION, '<')))
{
    $is_error              = true;
    $alert_min_version_php = lang_key('alert_min_version_php');
    $alert_min_version_php = str_replace("_PHP_VERSION_", EI_PHP_MINIMUM_VERSION, $alert_min_version_php);
    $alert_min_version_php = str_replace("_PHP_CURR_VERSION_", phpversion(), $alert_min_version_php);
    $error_mg[]            = $alert_min_version_php;
}

$mysqlAvailable = defined('PDO::MYSQL_ATTR_LOCAL_INFILE');
if (!$mysqlAvailable)
{
    $is_error   = true;
    $error_mg[] = 'You will need database support to run your site, please ensure the PDO MySQL extensions are enabled.';
}

$gdAvailable = extension_loaded('gd');
if (!$gdAvailable)
{
    $is_error   = true;
    $error_mg[] = 'GD functions are needed to process images, please ensure your PHP install is compiled with GD enabled.';
}

$freetypeAvailable = function_exists('imagettftext');
if (!$freetypeAvailable)
{
    $is_error   = true;
    $error_mg[] = 'Freetype (in GD) is required to use ttf fonts within images.';
}

$jsonAvailable = function_exists('json_encode');
if (!$jsonAvailable)
{
    $is_error   = true;
    $error_mg[] = 'JSON functions (json_encode/json_decode) are required to manage sessions and interpret ajax responses.';
}

$ftpAvailable = function_exists('ftp_connect');
if (!$ftpAvailable)
{
    $is_error   = true;
    $error_mg[] = 'FTP support is required in PHP for remote file server support.';
}

$safeModeOff = ini_get('safe_mode');
if ($safeModeOff)
{
    $is_error   = true;
    $error_mg[] = 'Safe Mode must be turned OFF.';
}

$memoryLimit = ((int) ini_get('memory_limit') >= 32 ? false : true);
if ($memoryLimit)
{
    $is_error   = true;
    $error_mg[] = 'PHP memory limit should be greater than or equaling 32M.';
}

$curlSupport = !function_exists('curl_init');
if ($curlSupport)
{
    $is_error   = true;
    $error_mg[] = 'We could not detect Curl support within PHP on your host.';
}

$mcryptSupport = !function_exists('mcrypt_create_iv');
if ($mcryptSupport)
{
    $is_error   = true;
    $error_mg[] = 'We could not detect Mcrypt support within PHP on your host. This is needed for the storage of secure account passwords. Your host can enable this via PHP.';
}

$foundHost = (int)strlen(trim(getInstallHost()));
if (!$foundHost)
{
    $is_error   = true;
    $error_mg[] = 'We could not automatically detect your installation url as $_SERVER["SERVER_NAME"] returned nothing on your web host. Either correct this or use the manual install process above.';
}

// check for .htaccess
if (!file_exists('../.htaccess'))
{
    $is_error   = true;
    $error_mg[] = 'Could not find the .htaccess within the root of your script install. This is used to rewrite the urls within the site. If you can\'t see the file within the root of the script zip download, your operating system may be set to hide it. Change your OS to show files starting with a dot and you should see it.';
}

$phpversion = function_exists("phpversion") ? "<span class='found'>" . phpversion() . "</span>" : "<span class='unknown'>" . lang_key('unknown') . "</span>";

$system          = isset($phpinfo['phpinfo']['System']) ? "<span class='found'>" . $phpinfo['phpinfo']['System'] . "</span>" : "<span class='unknown'>" . lang_key('unknown') . "</span>";
$server_api      = isset($phpinfo['phpinfo']['Server API']) ? "<span class='found'>" . $phpinfo['phpinfo']['Server API'] . "</span>" : "<span class='unknown'>" . lang_key('unknown') . "</span>";
$session_support = isset($phpinfo['session']['Session Support']) ? $phpinfo['session']['Session Support'] : lang_key('unknown');
$session_support = ($session_support == "enabled") ? "<span class='found'>" . UCWords($session_support) . "</span>" : "<span class='disabled'>" . UCWords($session_support) . "</span>";
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
                        Use these pages to install or upgrade the script. For new installations you can use the wizard below to automatically setup the script. For upgrades you'll need to manually apply the changes. Select your installation type below to start.<br /><br />
                        <table width="100%" cellspacing="0" cellpadding="0" border="0">
                            <tbody>
                                <tr>
                                    <td class="text">
                                        <input type="radio" name="install_type" id="install_type_wizard" onclick="toggleInstructions(1)" checked='checked' /><label for="install_type_wizard"><?php echo lang_key("follow_the_wizard"); ?></label>
                                        <input type="radio" name="install_type" id="install_type_manual" onclick="toggleInstructions(2)" /><label for="install_type_manual"><?php echo lang_key("perform_manual_installation"); ?></label>
                                        <input type="radio" name="install_type" id="install_type_upgrade" onclick="toggleInstructions(3)" /><label for="install_type_upgrade"><?php echo lang_key("perform_upgrade_installation"); ?></label>
                                    </td>                
                                    <td class="text" align="right" valign="middle">
                                        <?php
                                        if (count($arr_active_languages) > 1)
                                        {
                                            echo lang_key("language") . ": ";
                                            echo "<select onchange=\"javascript:goTo('install.php?lang='+this.value)\">";
                                            foreach ($arr_active_languages as $key => $val)
                                            {
                                                echo "<option " . (($key == $curr_lang) ? "selected=selected" : "") . " value='" . $key . "'>" . $val . "</option>";
                                            }
                                            echo "</select>";
                                        }
                                        ?>

                                    </td>                
                                </tr>
                                <tr><td colspan="2" nowrap="nowrap" height="8px"></td></tr>
                                <tr>
                                    <td class="gray_table" colspan="2">                
                                        <table width="100%" cellspacing="0" cellpadding="0" border="0">
                                            <tbody>
                                                <tr><td class="ltcorner"></td><td></td><td class="rtcorner"></td></tr>
                                                <tr>
                                                    <td></td>
                                                    <td align="middle">                       
                                                        <div id="divWizard">
                                                            <table class="text mainTable" width="99%" cellspacing="2" cellpadding="0" border="0">
                                                                <tbody>
                                                                    <tr>
                                                                        <td align="left" colspan="2"><h2>Checking Script Requirements (New Install)</h2></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td style="width: 200px;"><b>PHP Version:</b></td>
                                                                        <td><?php echo $phpversion; ?></td>
                                                                    </tr>
                                                                    <tr><td colspan="2" nowrap height="9px"></td></tr>
                                                                    <tr>
                                                                        <td><b>System:</b></td>
                                                                        <td><?php echo $system; ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td><b>Session Support:</b></td>
                                                                        <td><?php echo $session_support; ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td><b>Server API:</b></td>
                                                                        <td><?php echo $server_api; ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td><b>PHP PDO MySQL Module:</b></td>
                                                                        <td><?php echo $mysqlAvailable ? '<span class="found">Found</span>' : '<span class="disabled">Not Found</span>'; ?></td>
                                                                    </tr>

                                                                    <tr><td colspan="2" nowrap height="9px"></td></tr>
                                                                    <tr>
                                                                        <td><b>PHP GD Extension:</b></td>
                                                                        <td><?php echo $gdAvailable ? '<span class="found">Found</span>' : '<span class="disabled">Not Found</span>'; ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td><b>PHP FreeType Support:</b></td>
                                                                        <td><?php echo $freetypeAvailable ? '<span class="found">Found</span>' : '<span class="disabled">Not Found</span>'; ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td><b>PHP JSON Support:</b></td>
                                                                        <td><?php echo $jsonAvailable ? '<span class="found">Found</span>' : '<span class="disabled">Not Found</span>'; ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td><b>PHP FTP Support:</b></td>
                                                                        <td><?php echo $ftpAvailable ? '<span class="found">Found</span>' : '<span class="disabled">Not Found</span>'; ?></td>
                                                                    </tr>

                                                                    <tr><td colspan="2" nowrap height="9px"></td></tr>
                                                                    <tr>
                                                                        <td><b>PHP Safe Mode OFF:</b></td>
                                                                        <td><?php echo!$safeModeOff ? '<span class="found">OFF</span>' : '<span class="disabled">ON</span>'; ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td><b>PHP Memory Limit:</b></td>
                                                                        <td><?php echo!$memoryLimit ? '<span class="found">OK</span>' : '<span class="disabled">ERROR</span>'; ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td><b>Curl Support:</b></td>
                                                                        <td><?php echo!$curlSupport ? '<span class="found">Found</span>' : '<span class="disabled">Not Found</span>'; ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td><b>Mcrypt Support:</b></td>
                                                                        <td><?php echo!$mcryptSupport ? '<span class="found">Found</span>' : '<span class="disabled">Not Found</span>'; ?></td>
                                                                    </tr>
                                                                    
                                                                    <tr><td colspan="2" nowrap height="9px"></td></tr>
                                                                    <tr>
                                                                        <td><b>Detect Site Url:</b></td>
                                                                        <td><?php echo $foundHost ? '<span class="found">'.getInstallHost().'</span>' : '<span class="disabled">Not Found</span>'; ?></td>
                                                                    </tr>
                                                                    <?php if($foundHost): ?>
                                                                    <tr>
                                                                        <td><b>Install Path:</b></td>
                                                                        <td><?php echo '<span class="found">'.getInstallPath().'</span>'; ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td><b>Use HTTPS:</b></td>
                                                                        <td><?php echo $_SERVER["SERVER_PORT"] != 80 ? '<span class="found">Yes</span>' : '<span class="found">No</span>'; ?></td>
                                                                    </tr>
                                                                    <?php endif; ?>

                                                                    <tr><td colspan="2">&nbsp;</td></tr>
                                                                    <tr>
                                                                        <td align="left" colspan="2"><h2>Folder/File Permissions</h2></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td><b>/_config.inc.php</b></td>
                                                                        <td>
                                                                            <?php
                                                                            if (!is_writable('../_config.inc.php'))
                                                                            {
                                                                                $is_error   = true;
                                                                                $error_mg[] = 'Ensure the /_config.inc.php file in the web root has write permissions. (CHMOD 777 or 755 depending on your host)';
                                                                                echo '<span class="disabled">Problem</span>';
                                                                            }
                                                                            else
                                                                            {
                                                                                echo '<span class="found">Writable</span>';
                                                                            }
                                                                            ?>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td><b>/files/</b></td>
                                                                        <td>
                                                                            <?php
                                                                            if (!is_writable('../files/'))
                                                                            {
                                                                                $is_error   = true;
                                                                                $error_mg[] = 'Ensure the /files/ folder in the web root has write permissions. (CHMOD 777 or 755 depending on your host)';
                                                                                echo '<span class="disabled">Problem</span>';
                                                                            }
                                                                            else
                                                                            {
                                                                                echo '<span class="found">Writable</span>';
                                                                            }
                                                                            ?>
                                                                        </td>                                                                    </tr>
                                                                    <tr>
                                                                        <td><b>/plugins/</b></td>
                                                                        <td>
                                                                            <?php
                                                                            if (!is_writable('../plugins/'))
                                                                            {
                                                                                $is_error   = true;
                                                                                $error_mg[] = 'Ensure the /plugins/ folder in the web root has write permissions. (CHMOD 777 or 755 depending on your host)';
                                                                                echo '<span class="disabled">Problem</span>';
                                                                            }
                                                                            else
                                                                            {
                                                                                echo '<span class="found">Writable</span>';
                                                                            }
                                                                            ?>
                                                                        </td>                                                                    </tr>
																	<tr>
                                                                        <td><b>/core/logs/</b></td>
                                                                        <td>
                                                                            <?php
                                                                            if (!is_writable('../core/logs/'))
                                                                            {
                                                                                $is_error   = true;
                                                                                $error_mg[] = 'Ensure the /core/logs/ folder in the web root has write permissions. (CHMOD 777 or 755 depending on your host)';
                                                                                echo '<span class="disabled">Problem</span>';
                                                                            }
                                                                            else
                                                                            {
                                                                                echo '<span class="found">Writable</span>';
                                                                            }
                                                                            ?>
                                                                        </td>                                                                    </tr>
                                                                    <tr>
                                                                        <td><b>/core/cache/</b></td>
                                                                        <td>
                                                                            <?php
                                                                            if (!is_writable('../core/cache/'))
                                                                            {
                                                                                $is_error   = true;
                                                                                $error_mg[] = 'Ensure the /core/cache/ folder in the web root has write permissions. (CHMOD 777 or 755 depending on your host)';
                                                                                echo '<span class="disabled">Problem</span>';
                                                                            }
                                                                            else
                                                                            {
                                                                                echo '<span class="found">Writable</span>';
                                                                            }
                                                                            ?>
                                                                        </td>                                                                    </tr>

                                                                    <tr><td colspan="2">&nbsp;</td></tr>
                                                                    <?php
                                                                    if (!$is_error)
                                                                    {
                                                                        echo ($program_already_installed) ? "<tr><td align='left' colspan='2'><span class='alert'>* " . lang_key("alert_unable_to_install") . "</span></td></tr>" : "";
                                                                    }
                                                                    else
                                                                    {
                                                                        echo "<tr><td align='left' colspan='2'><br/><span class='alert'><strong>ERRORS:</strong></span><br/><br/></td></tr>";
                                                                        foreach ($error_mg as $msg)
                                                                        {
                                                                            echo "<tr><td colspan='2' align='left'><span class='alert'>" . $msg . "</span></td></tr>";
                                                                        }
                                                                    }
                                                                    ?>
                                                                </tbody>
                                                            </table>

                                                            <?php
                                                            if (!$is_error)
                                                            {
                                                                ?>
                                                                <table width="100%" border="0" cellspacing="0" cellpadding="2" class="text">
                                                                    <tr>
                                                                        <td align="left" valign="middle" width="70px">
                                                                            <a href="step1.php"><img class="form_button" src="language/<?php echo $curr_lang; ?>/buttons/button_start.gif" name="submit" id='button_start' onmouseover="buttonOver('button_start')" onmouseout="buttonOut('button_start')" title="<?php echo lang_key("click_to_start_installation"); ?>" alt="" /></a>
                                                                        </td>
                                                                        <td align="left" valign="middle">
                                                                            <?php
                                                                            if (!$is_error)
                                                                            {
                                                                                echo " - &nbsp;" . lang_key("click_start_button");
                                                                            }
                                                                            ?>										
                                                                        </td>
                                                                    </tr>
                                                                </table>						
                                                            <?php } ?>
                                                        </div>
                                                        
                                                        <div id='divUpgradev4'>
                                                            <?php include_once(EI_MANUAL_INSTALLATION_DIR . $arr_upgradev4_installations[$curr_lang]); ?>
                                                        </div>

                                                        <div id='divManually'>
                                                            <?php include_once(EI_MANUAL_INSTALLATION_DIR . $arr_manual_installations[$curr_lang]); ?>
                                                        </div>

                                                        <div id='divUpgrade'>
                                                            <?php include_once(EI_MANUAL_INSTALLATION_DIR . $arr_upgrade_installations[$curr_lang]); ?>
                                                        </div>

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