<?php

// includes and security
include_once('../_local_auth.inc.php');

// prepare variables
$server_label            = '';
$status_id               = '';
$server_type             = '';
$ftp_host                = '';
$ftp_port                = 21;
$ftp_username            = '';
$ftp_password            = '';
$storage_path            = 'files/';
$formType                = 'set the new';
$file_server_domain_name = '';
$script_path             = '/';
$max_storage_space       = 0;
$server_priority         = 0;
$route_via_main_site     = 1;
$download_accelerator    = 0;
$file_server_direct_ip_address 				= '';
$file_server_direct_ssh_port 				= 22;
$file_server_direct_ssh_username 			= '';
$file_server_direct_ssh_password 			= '';
$file_server_direct_server_path_to_storage 	= '';

// server config variables
$ftp_server_type  = 'linux';
$ftp_passive_mode = 'no';

// is this an edit?
$fileServerId = null;

if (isset($_REQUEST['gEditFileServerId']))
{
    $fileServerId = (int) $_REQUEST['gEditFileServerId'];
    if ($fileServerId)
    {
        $sQL           = "SELECT * FROM file_server WHERE id=" . $fileServerId;
        $serverDetails = $db->getRow($sQL);
        if ($serverDetails)
        {
            $server_label            = $serverDetails['serverLabel'];
            $status_id               = $serverDetails['statusId'];
            $server_type             = $serverDetails['serverType'];
            $ftp_host                = $serverDetails['ipAddress'];
            $ftp_port                = $serverDetails['ftpPort'];
            $ftp_username            = $serverDetails['ftpUsername'];
            $ftp_password            = $serverDetails['ftpPassword'];
            $storage_path            = $serverDetails['storagePath'];
            $formType                = 'update the';
            $file_server_domain_name = $serverDetails['fileServerDomainName'];
            $script_path             = $serverDetails['scriptPath'];
            $max_storage_space       = strlen($serverDetails['maximumStorageBytes']) ? $serverDetails['maximumStorageBytes'] : 0;
            $server_priority         = (int) $serverDetails['priority'];
            $route_via_main_site     = (int) $serverDetails['routeViaMainSite'];
            $download_accelerator    = (int) $serverDetails['dlAccelerator'];

            // @TODO - later move the above settings into here
            $server_config = $serverDetails['serverConfig'];
            if (strlen($server_config))
            {
                $server_config_array = json_decode($server_config, true);
                if (is_array($server_config_array))
                {
                    foreach ($server_config_array AS $k => $v)
                    {
                        // make available as local variables
                        $$k = $v;
                    }
                }
            }
			
			// server login data
			$server_access = $serverDetails['serverAccess'];
			if (strlen($server_access))
            {
				$server_access = coreFunctions::decryptValue($server_access);
                $server_access_array = json_decode($server_access, true);
                if (is_array($server_access_array))
                {
                    foreach ($server_access_array AS $k => $v)
                    {
                        // make available as local variables
                        $$k = $v;
                    }
                }
            }
        }
    }
}

// load all server statuses
$sQL           = "SELECT id, label FROM file_server_status ORDER BY label";
$statusDetails = $db->getRows($sQL);

// prepare whether we should disable local server or not
$isDefaultServer = false;
if ($server_label == 'Local Default')
{
    $isDefaultServer = true;
}

// prepare result
$result          = array();
$result['error'] = false;
$result['msg']   = '';
$result['html']  = 'Could not load the form, please try again later.';

$result['html'] = '<p style="padding-bottom: 4px;">Use the form below to ' . $formType . ' file server details.</p>';
$result['html'] .= '<span id="popupMessageContainer"></span>';
$result['html'] .= '<form id="addFileServerForm">';

$result['html'] .= '<div class="form">';
$result['html'] .= '<div class="clearfix alt-highlight">
                        <label>' . UCWords(adminFunctions::t("server_label", "server label")) . ':</label>
                        <div class="input">
                            <input name="server_label" id="server_label" placeholder="i.e. File Server 1" type="text" value="' . adminFunctions::makeSafe($server_label) . '" class="xlarge" ' . ($isDefaultServer ? 'DISABLED' : '') . '/>&nbsp;&nbsp;For your own reference only.
                        </div>
                    </div>';
$result['html'] .= '<div class="clearfix">
                        <label>' . UCWords(adminFunctions::t("status", "status")) . ':</label>
                        <div class="input">
                            <select name="status_id" id="status_id">';
foreach ($statusDetails AS $statusDetail)
{
    $result['html'] .= '        <option value="' . $statusDetail['id'] . '"';
    if ($status_id == $statusDetail['id'])
    {
        $result['html'] .= '        SELECTED';
    }
    $result['html'] .= '        >' . UCWords($statusDetail['label']) . '</option>';
}
$result['html'] .= '        </select>
                        </div>
                    </div>';
$result['html'] .= '</div>';
                    
$result['html'] .= '<div class="form" style="margin-top: 12px;">';
$result['html'] .= '<div class="clearfix alt-highlight">
                        <label>' . UCWords(adminFunctions::t("server_type", "server type")) . ':</label>
                        <div class="input">
                            <select name="server_type" id="server_type" class="xxlarge" onChange="showHideFTPElements(); return false;" ' . ($isDefaultServer ? 'DISABLED' : '') . '>
                                <option value="local"' . ($server_type == 'local' ? ' SELECTED' : '') . '>Local (storage located on the same server as your site)</option>
                                <option value="direct"' . ($server_type == 'direct' ? ' SELECTED' : '') . '>Remote Direct (users upload directly to remote file server)</option>
                                <option value="ftp"' . ($server_type == 'ftp' ? ' SELECTED' : '') . '>FTP (uses FTP via PHP to upload files into storage)</option>';

$params = pluginHelper::includeAppends('admin_server_manage_add_form_type_select.inc.php', array('html'        => '', 'server_type' => $server_type));
if (isset($params['html']))
{
    $result['html'] .= $params['html'];
}

$result['html'] .= '        </select>
                        </div>
                    </div>';

$result['html'] .= '<span class="localElements" style="display: none;">';
$result['html'] .= '<div class="clearfix">
                        <label>' . UCWords(adminFunctions::t("storage_path", "storage path")) . ':</label>
                        <div class="input">
                            <input name="storage_path" id="local_storage_path" type="text" value="' . adminFunctions::makeSafe($storage_path) . '" class="large" ' . ($isDefaultServer ? 'DISABLED' : '') . '/>
                        </div>
                    </div>';
$result['html'] .= '<div class="clearfix alt-highlight">
                        <label>' . UCWords(adminFunctions::t("improved_download_management", "Improved Downloads")) . ':</label>
                        <div class="input">
                            <select name="dlAccelerator" id="dlAccelerator1">';
$options = array(2 => 'XSendFile (Apache Only)', 1 => 'X-Accel-Redirect (Nginx Only)', 0 => 'Disabled');
foreach ($options AS $k => $option)
{
    $result['html'] .= '        <option value="' . $k . '"';
    if ($download_accelerator == $k)
    {
        $result['html'] .= '        SELECTED';
    }
    $result['html'] .= '        >' . UCWords($option) . '</option>';
}
$result['html'] .= '
                            </select>
                            <br/><br/><span style="width:520px; display: inline-block;">This dramatically increases server performance for busy sites by handing the process away from php to Apache or Nginx.<br/><br/>
                            - <a href="https://support.mfscripts.com/public/kb_view/1/" target="_blank" style="text-decoration: underline;">XSendFile for Apache Information</a>.<br/><br/>
                            - <a href="https://support.mfscripts.com/public/kb_view/2/" target="_blank" style="text-decoration: underline;">X-Accel-Redirect for Nginx Information</a>.</span>
                        </div>
                    </div>';
$result['html'] .= '</span>';

$result['html'] .= '<span class="ftpElements" style="display: none;">';
$result['html'] .= '<div class="clearfix">
                        <label>' . UCWords(adminFunctions::t("ftp_host", "ftp host")) . ':</label>
                        <div class="input">
                            <input name="ftp_host" id="ftp_host" type="text" value="' . adminFunctions::makeSafe($ftp_host) . '"/>
                        </div>

                        <label>' . UCWords(adminFunctions::t("ftp_port", "ftp port")) . ':</label>
                        <div class="input">
                            <input name="ftp_port" id="ftp_port" type="text" value="' . adminFunctions::makeSafe($ftp_port) . '" class="small"/>
                        </div>
                    </div>';
$result['html'] .= '<div class="clearfix alt-highlight">
                        <label>' . UCWords(adminFunctions::t("ftp_username", "ftp username")) . ':</label>
                        <div class="input">
                            <input name="ftp_username" id="ftp_username" type="text" value="' . adminFunctions::makeSafe($ftp_username) . '"/>
                        </div>
                        <label>' . UCWords(adminFunctions::t("ftp_password", "ftp password")) . ':</label>
                        <div class="input">
                            <input name="ftp_password" id="ftp_password" type="password" value="' . adminFunctions::makeSafe($ftp_password) . '"/>
                        </div>
                    </div>';
$result['html'] .= '<div class="clearfix">
                        <label>' . UCWords(adminFunctions::t("storage_path", "storage path")) . ':</label>
                        <div class="input">
                            <input name="storage_path" id="ftp_storage_path" type="text" value="' . adminFunctions::makeSafe($storage_path) . '" class="large"/><br/><br/>- As the FTP user would see it. Login with this FTP user using an FTP client to confirm<br/>the path to use.
                        </div>
                    </div>';
$result['html'] .= '<div class="clearfix alt-highlight">
                        <label>' . UCWords(adminFunctions::t("ftp_server_type", "ftp server type")) . ':</label>
                        <div class="input">
                            <select name="ftp_server_type" id="ftp_server_type" style="width: 180px;">';
$serverTypes = array('linux'   => 'Linux (for most)', 'windows' => 'Windows', 'windows_alt' => 'Windows Alternative');
foreach ($serverTypes AS $k => $serverType)
{
    $result['html'] .= '        <option value="' . $k . '"';
    if ($ftp_server_type == $k)
    {
        $result['html'] .= '        SELECTED';
    }
    $result['html'] .= '        >' . $serverType . '</option>';
}
$result['html'] .= '        </select>
                        </div>
                        <label>' . UCWords(adminFunctions::t("ftp_enable_passive_mode", "enable passive mode")) . ':</label>
                        <div class="input">
                            <select name="ftp_passive_mode" id="ftp_passive_mode">';
$serverPassiveOptions = array('no'  => 'No (default)', 'yes' => 'Yes');
foreach ($serverPassiveOptions AS $k => $serverPassiveOption)
{
    $result['html'] .= '        <option value="' . $k . '"';
    if ($ftp_passive_mode == $k)
    {
        $result['html'] .= '        SELECTED';
    }
    $result['html'] .= '        >' . $serverPassiveOption . '</option>';
}
$result['html'] .= '        </select>
                        </div>
                    </div>';
$result['html'] .= '</span>';

$result['html'] .= '<span class="directElements" style="display: none;">';
$result['html'] .= '<div class="clearfix">
                        <label>' . UCWords(adminFunctions::t("file_server_domain_name", "file server domain name")) . ':</label>
                        <div class="input">
                            <input name="file_server_domain_name" id="file_server_domain_name" placeholder="i.e. fs1.' . _CONFIG_SITE_HOST_URL . '" type="text" value="' . adminFunctions::makeSafe($file_server_domain_name) . '" onKeyUp="updateUrlParams();" class="xxlarge"/>
                        </div>
                    </div>';
$result['html'] .= '<div class="clearfix alt-highlight">
                        <label>' . UCWords(adminFunctions::t("site_path", "site path")) . ':</label>
                        <div class="input">
                            <input name="script_path" id="script_path" type="text" placeholder="/ - root, unless you installed into a sub-folder" value="' . adminFunctions::makeSafe($script_path) . '" class="large" onKeyUp="updateUrlParams();"/><br/><br/>Use /, unless you\'ve installed into a sub-folder.
                        </div>
                    </div>';
$result['html'] .= '<div class="clearfix">
                        <label>' . UCWords(adminFunctions::t("file_storage_path", "file storage path")) . ':</label>
                        <div class="input">
                            <input name="storage_path" id="direct_storage_path" type="text" value="' . adminFunctions::makeSafe($storage_path) . '" class="large"/><br/><br/>Which folder to store files in on the file server, relating to the script root. Normally files/
                        </div>
                    </div>';
$result['html'] .= '<div class="clearfix alt-highlight">
                        <label>' . UCWords(adminFunctions::t("use_main_site_url", "use main site url")) . ':</label>
                        <div class="input">
                            <select name="route_via_main_site" id="route_via_main_site">';
$options = array(1 => 'yes (recommended)', 0 => 'no');
foreach ($options AS $k => $option)
{
    $result['html'] .= '        <option value="' . $k . '"';
    if ($route_via_main_site == $k)
    {
        $result['html'] .= '        SELECTED';
    }
    $result['html'] .= '        >' . UCWords($option) . '</option>';
}
$result['html'] .= '
                            </select>
                            <br/><br/><span style="width:520px; display: inline-block;">If \'yes\' ' . _CONFIG_SITE_HOST_URL . ' will be used for all download urls generated on the site. Otherwise the above \'File Server Domain Name\' will be used. Changing this will not impact any existing download urls.</span>
                        </div>
                    </div>';
                    
$result['html'] .= '<div class="clearfix">
                        <label>' . UCWords(adminFunctions::t("improved_download_management", "Improved Downloads")) . ':</label>
                        <div class="input">
                            <select name="dlAccelerator" id="dlAccelerator2">';
$options = array(2 => 'XSendFile (Apache Only)', 1 => 'X-Accel-Redirect (Nginx Only)', 0 => 'Disabled');
foreach ($options AS $k => $option)
{
    $result['html'] .= '        <option value="' . $k . '"';
    if ($download_accelerator == $k)
    {
        $result['html'] .= '        SELECTED';
    }
    $result['html'] .= '        >' . UCWords($option) . '</option>';
}
$result['html'] .= '
                            </select>
                            <br/><br/><span style="width:520px; display: inline-block;">This dramatically increases server performance for busy sites by handing the process away from php to Apache or Nginx. Ensure you properly configure your server before enabling this:<br/><br/>
                            - <a href="https://support.mfscripts.com/public/kb_view/1/" target="_blank" style="text-decoration: underline;">XSendFile for Apache Information</a>.<br/><br/>
                            - <a href="https://support.mfscripts.com/public/kb_view/2/" target="_blank" style="text-decoration: underline;">X-Accel-Redirect for Nginx Information</a>.</span>
                        </div>
                    </div>';
$result['html'] .= '</div>';
                    
$result['html'] .= '</span>';



$result['html'] .= '<span class="localElements serverAccessWrapper" style="display: none;">';
$result['html'] .= '<div class="form" style="margin-top: 12px;">';
$result['html'] .= '<div class="clearfix alt-highlight">
                        <label>' . UCWords(adminFunctions::t("local_server_ssh_details_this_server", "local server SSH details (This Server)")) . ':</label>
                        <span style="width:550px; display: inline-block; padding-top: 6px; padding-left: 20px;">
                            The following information should be filled in if you\'re using the media converter plugin or archive manager.<br/><br/>
							If you have openssl_encrypt() functions available within your server PHP setup, these details will be encrypted in your database using AES256.<br/><br/>
							In a future release we\'ll be able to use these details to automatically update your site.
                        </span>
                    </div>';
$result['html'] .= '<div class="clearfix">
                        <label>' . UCWords(adminFunctions::t("local_server_direct_ip_address", "local server ip address")) . ':</label>
                        <div class="input">
                            <input name="file_server_direct_ip_address" id="file_server_direct_ip_address_2" placeholder="i.e. 124.194.125.34" type="text" value="' . adminFunctions::makeSafe($file_server_direct_ip_address) . '" class="medium"/>
                        </div>
                    </div>';
$result['html'] .= '<div class="clearfix alt-highlight">
                        <label>' . UCWords(adminFunctions::t("local_server_direct_ssh_port", "local SSH port")) . ':</label>
                        <div class="input">
                            <input name="file_server_direct_ssh_port" id="file_server_direct_ssh_port_2" type="text" placeholder="22" value="' . adminFunctions::makeSafe($file_server_direct_ssh_port) . '" class="small"/>&nbsp;&nbsp;Normally port 22.
                        </div>
                    </div>';
$result['html'] .= '<div class="clearfix">
                        <label>' . UCWords(adminFunctions::t("local_server_direct_ssh_username", "local SSH username")) . ':</label>
                        <div class="input">
                            <input name="file_server_direct_ssh_username" id="file_server_direct_ssh_username_2" placeholder="user" type="text" value="' . adminFunctions::makeSafe($file_server_direct_ssh_username) . '" class="medium"/>&nbsp;&nbsp;Root equivalent user.
                        </div>
                    </div>';
$result['html'] .= '<div class="clearfix alt-highlight">
                        <label>' . UCWords(adminFunctions::t("local_server_direct_ssh_password", "local SSH password")) . ':</label>
                        <div class="input">
                            <input name="file_server_direct_ssh_password" id="file_server_direct_ssh_password_2" type="password" value="" class="medium"/>&nbsp;&nbsp;Leave blank to keep existing value, if updating.
                        </div>
                    </div>';
$result['html'] .= '<div class="clearfix">
                        <label>' . UCWords(adminFunctions::t("local_server_direct_server_path_to_install", "local path to install")) . ':</label>
                        <div class="input">
                            <input name="file_server_direct_server_path_to_storage" id="file_server_direct_server_path_to_storage_2" placeholder="i.e. /home/yetishare" type="text" value="' . adminFunctions::makeSafe($file_server_direct_server_path_to_storage) . '" class="xxlarge"/><br/><br/>
							The full base path to your install. Exclude the final forward slash. i.e. /home/yetishare
						</div>
                    </div>';
$result['html'] .= '</div>';
                    
$result['html'] .= '<div class="form directElements" style="margin-top: 12px;">';
$result['html'] .= '<div class="clearfix alt-highlight">
                        <label>' . UCWords(adminFunctions::t("file_server_setup", "file server setup")) . ':</label>
                        <span style="width:550px; display: inline-block; padding-top: 6px; padding-left: 20px;">
                            Direct file server requirements: PHP5.3+, Apache Mod Rewrite or Nginx, remote access to your MySQL database.<br/><br/>
                            So that your direct file server can receive the uploads and process downloads, it needs a copy of the full codebase installed. Upload all the files from your main site (' . _CONFIG_SITE_HOST_URL . ') to your new file server. This includes any plugin files within the plugin folder.<br/><br/>
                            Once uploaded, replace the /_config.inc.php file on the new file server with the one listed below. Set your database password in the file (_CONFIG_DB_PASS). We\'ve removed it for security.<br/><br/>
                            <ul class="adminList"><li><a id="configLink" href="server_manage_direct_get_config_file.php?fileName=_config.inc.php" style="text-decoration: underline;">_config.inc.php</a></li></ul><br/>
                            In addition, if you\'re using Apache, replace the \'.htaccess\' on the file server with the one listed below.<br/><br/>
                            <ul class="adminList"><li><a id="htaccessLink" href="server_manage_direct_get_config_file.php?fileName=.htaccess&REWRITE_BASE=/" style="text-decoration: underline;">.htaccess</a></li></ul><br/>
                            For Nginx users, set your rules to the same as the main server. See /___NGINX_RULES.txt for details.<br/><br/>
                            Ensure the following folders are CHMOD 755 (or 777 depending on your host) on this file server:<br/><br/>
                            <ul class="adminList">
                                <li>/files/</li>
                                <li>/core/cache/</li>
                                <li>/core/logs/</li>
                                <li>/plugins/</li>
                            </ul>
                        </span>
                    </div>';
$result['html'] .= '</div>';
$result['html'] .= '</div>';
$result['html'] .= '</span>';



$result['html'] .= '<span class="directElements serverAccessWrapper" style="display: none;">';
$result['html'] .= '<div class="form" style="margin-top: 12px;">';
$result['html'] .= '<div class="clearfix alt-highlight">
                        <label>' . UCWords(adminFunctions::t("file_server_ssh_details", "file server SSH details")) . ':</label>
                        <span style="width:550px; display: inline-block; padding-top: 6px; padding-left: 20px;">
                            The following information should be filled in if you\'re using the media converter plugin or archive manager.<br/><br/>
							If you have openssl_encrypt() functions available within your server PHP setup, these details will be encrypted in your database using AES256.<br/><br/>
							In a future release we\'ll be able to use these details to automatically create and upgrade your file servers.
                        </span>
                    </div>';
$result['html'] .= '<div class="clearfix">
                        <label>' . UCWords(adminFunctions::t("file_server_direct_ip_address", "file server ip address")) . ':</label>
                        <div class="input">
                            <input name="file_server_direct_ip_address" id="file_server_direct_ip_address" placeholder="i.e. 124.194.125.34" type="text" value="' . adminFunctions::makeSafe($file_server_direct_ip_address) . '" class="medium"/>
                        </div>
                    </div>';
$result['html'] .= '<div class="clearfix alt-highlight">
                        <label>' . UCWords(adminFunctions::t("file_server_direct_ssh_port", "server SSH port")) . ':</label>
                        <div class="input">
                            <input name="file_server_direct_ssh_port" id="file_server_direct_ssh_port" type="text" placeholder="22" value="' . adminFunctions::makeSafe($file_server_direct_ssh_port) . '" class="small"/>&nbsp;&nbsp;Normally port 22.
                        </div>
                    </div>';
$result['html'] .= '<div class="clearfix">
                        <label>' . UCWords(adminFunctions::t("file_server_direct_ssh_username", "server SSH username")) . ':</label>
                        <div class="input">
                            <input name="file_server_direct_ssh_username" id="file_server_direct_ssh_username" placeholder="user" type="text" value="' . adminFunctions::makeSafe($file_server_direct_ssh_username) . '" class="medium"/>&nbsp;&nbsp;Root equivalent user.
                        </div>
                    </div>';
$result['html'] .= '<div class="clearfix alt-highlight">
                        <label>' . UCWords(adminFunctions::t("file_server_direct_ssh_password", "server SSH password")) . ':</label>
                        <div class="input">
                            <input name="file_server_direct_ssh_password" id="file_server_direct_ssh_password" type="password" value="" class="medium"/>&nbsp;&nbsp;Leave blank to keep existing value, if updating.
                        </div>
                    </div>';
$result['html'] .= '<div class="clearfix">
                        <label>' . UCWords(adminFunctions::t("file_server_direct_server_path_to_install", "server path to install")) . ':</label>
                        <div class="input">
                            <input name="file_server_direct_server_path_to_storage" id="file_server_direct_server_path_to_storage" placeholder="i.e. /home/yetishare" type="text" value="' . adminFunctions::makeSafe($file_server_direct_server_path_to_storage) . '" class="xxlarge"/><br/><br/>
							The full base path to your install. Exclude the final forward slash. i.e. /home/yetishare
						</div>
                    </div>';
$result['html'] .= '</div>';
                    
$result['html'] .= '<div class="form directElements" style="margin-top: 12px;">';
$result['html'] .= '<div class="clearfix alt-highlight">
                        <label>' . UCWords(adminFunctions::t("file_server_setup", "file server setup")) . ':</label>
                        <span style="width:550px; display: inline-block; padding-top: 6px; padding-left: 20px;">
                            Direct file server requirements: PHP5.3+, Apache Mod Rewrite or Nginx, remote access to your MySQL database.<br/><br/>
                            So that your direct file server can receive the uploads and process downloads, it needs a copy of the full codebase installed. Upload all the files from your main site (' . _CONFIG_SITE_HOST_URL . ') to your new file server. This includes any plugin files within the plugin folder.<br/><br/>
                            Once uploaded, replace the /_config.inc.php file on the new file server with the one listed below. Set your database password in the file (_CONFIG_DB_PASS). We\'ve removed it for security.<br/><br/>
                            <ul class="adminList"><li><a id="configLink" href="server_manage_direct_get_config_file.php?fileName=_config.inc.php" style="text-decoration: underline;">_config.inc.php</a></li></ul><br/>
                            In addition, if you\'re using Apache, replace the \'.htaccess\' on the file server with the one listed below.<br/><br/>
                            <ul class="adminList"><li><a id="htaccessLink" href="server_manage_direct_get_config_file.php?fileName=.htaccess&REWRITE_BASE=/" style="text-decoration: underline;">.htaccess</a></li></ul><br/>
                            For Nginx users, set your rules to the same as the main server. See /___NGINX_RULES.txt for details.<br/><br/>
                            Ensure the following folders are CHMOD 755 (or 777 depending on your host) on this file server:<br/><br/>
                            <ul class="adminList">
                                <li>/files/</li>
                                <li>/core/cache/</li>
                                <li>/core/logs/</li>
                                <li>/plugins/</li>
                            </ul>
                        </span>
                    </div>';
$result['html'] .= '</div>';
$result['html'] .= '</div>';
$result['html'] .= '</span>';




$result['html'] .= '<div class="form" style="margin-top: 12px;">';
$result['html'] .= '<div class="clearfix alt-highlight">
                        <label>' . UCWords(adminFunctions::t("max_storage_bytes", "max storage (bytes)")) . ':</label>
                        <div class="input">
                            <input name="max_storage_space" id="max_storage_space" type="text" value="' . adminFunctions::makeSafe($max_storage_space) . '" class="medium" placeholder="2199023255552 = 2TB"/>&nbsp;bytes. Use zero for unlimited.
                        </div>
                    </div>';
$result['html'] .= '<div class="clearfix">
                        <label>' . UCWords(adminFunctions::t("server_priority", "server priority")) . ':</label>
                        <div class="input">
                            <input name="server_priority" id="server_priority" type="text" value="' . adminFunctions::makeSafe($server_priority) . '" class="medium"/>&nbsp;A number. In order from lowest. 0 to ignore.<br/><br/>- Use for multiple servers when others are full. So when server with priority of 1 is full, server<br/>with priority of 2 will be used next for new uploads. 3 next and so on. "Server selection method"<br/>must be set to "Until Full" to enable this functionality.
                        </div>
                    </div>';
$result['html'] .= '</div>';

$result['html'] .= '</form>';

echo json_encode($result);
exit;
