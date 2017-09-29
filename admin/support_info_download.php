<?php

include_once('_local_auth.inc.php');
if (_CONFIG_DEMO_MODE == true)
{
	die();
}
$plugins  = $db->getRows("SELECT * FROM plugin ORDER BY plugin_name ASC");
$servers  = $db->getRows("SELECT * FROM file_server ORDER BY id DESC");
$phparr   = adminFunctions::phpinfoArray();
$dt       = new DateTime();
$mysqlTime = $db->getValue('SELECT NOW();');
// Get all the info from the server
$content = "Server Information for "._CONFIG_SITE_HOST_URL.".\r\r";
$content .= "Operating System: ". php_uname()."\n";
$content .= "Current Server Time: ". $dt->format('d-m-Y H:i:s')."\n";
$content .= "Web Server: ". $_SERVER['SERVER_SIGNATURE'] ? $_SERVER['SERVER_SIGNATURE'] : $_SERVER['SERVER_SOFTWARE']."\n";
$content .= "Script Domain Name: "._CONFIG_SITE_HOST_URL."\n";
$content .= "Server Hostname: ". $_SERVER['SERVER_SIGNATURE']?$_SERVER['SERVER_SIGNATURE']:$_SERVER['SERVER_SOFTWARE']."\n";
$content .= "Server IP Address: ". $_SERVER['SERVER_ADDR']."\n";
$content .= "Document Root: ". $_SERVER['DOCUMENT_ROOT']."\r\r";
// MySQL Information
$content .= "MySQLi Information.\r\r";
$content .= "MySQL Client Version: ". $phparr['mysqli']['Client API library version']."\n";
$content .= "MySQL Server Version: ". $db->getValue("SELECT version();")."\n";
$content .= "MySQL Server Time: ".$db->getValue('SELECT NOW();')."\n";
$content .= "PDO Installed: ". $phparr['PDO']['PDO drivers']."\n";
$content .= "PDO Version: ". $phparr['pdo_mysql']['Client API version']."\r\r";
// PHP information
$content .= "PHP Information.\r\r";
$content .= "PHP Version: ". phpversion()."\n";
$content .= "Current PHP Time: ".date('Y-m-d H:i:s')."\n";
$content .= "php.ini Location: ". php_ini_loaded_file()."\n";
$content .= "Max Execution Time: ". $phparr['Core']['max_execution_time']."\n";
$content .= "Max Input Time: ". $phparr['Core']['max_input_time']."\n";
$content .= "Memory Limit: ". $phparr['Core']['memory_limit']."\n";
$content .= "Post Max Size: ". $phparr['Core']['post_max_size']."\n";
$content .= "Upload Max Filesize: ". $phparr['Core']['upload_max_filesize']."\n";
$content .= "cURL Enabled: ". ucfirst($phparr['curl']['cURL support'])."\n";
$content .= "cURL Version: ". $phparr['curl']['cURL Information']."\n";
$content .= "Default Timezone: ". $phparr['date']['Default timezone']."\n";
$content .= "GD Enabled: ". ucfirst($phparr['gd']['GD Support'])."\n";
$content .= "GD Version: ". $phparr['gd']['GD Version']."\n";
$content .= "Mcrypt Version: ". $phparr['mcrypt']['Version']."\n";
$content .= "Default Timezone: ". $phparr['date']['Default timezone']."\n";
$content .= "Loaded Extensions: ". implode(get_loaded_extensions(), ', ')."\r\r";
//Script Information
$content .= "Script Information\r\r";
$content .= "Script Version: v"._CONFIG_SCRIPT_VERSION."\n";
$content .= "Site Protocol: ". _CONFIG_SITE_PROTOCOL."\r\r";
$content .= "Plugins Installed.\r\r";

if($plugins)
{
    foreach ($plugins AS $plugin)
    {
        include_once(DOC_ROOT.'/plugins/'.$plugin['folder_name'].'/_plugin_config.inc.php');
        $content .= $plugin['plugin_name']." --- Version: ".$pluginConfig['plugin_version']."\n";
    }
}
else
{
    $content .= "No plugins installed.\r\r";
}

// File Servers
if($servers)
{
   $content .= "\r\r";
   $content .= "File Servers.";
   $content .= "\r\r";
   
   foreach($servers AS $server)
   {
        $content .= 'Server Label: '.$server['serverLabel']."\n";
        $content .= 'Server Type: '.$server['serverType']."\n";
        if($server['statusId'] == 1)
        {
            $content .= "Server Status: Disabled.\n";
        }
        elseif($server['statusId'] == 2)
        {
            $content .= "Server Status: Active.\n";
        }
        elseif($server['statusId'] == 3)
        {
            $content .= "Server Status: Read Only.\n";
        }
        $content .= "Space Used: ". adminFunctions::formatSize($server['totalSpaceUsed'])."\n";
        $totalFiles = $db->getValue("SELECT COUNT(id) FROM file WHERE serverId = ".$server['id']." AND statusId = 1");
        $content .= "Total Files: ". $totalFiles ."\n";
        $content .= "Storage Path: ". $server['storagePath']."\n";  
        $content .= "\r";      
   }
}

header("Pragma: public");
header("Expires: 0"); 
header("Cache-Control: must-revalidate"); 
header("Cache-Control: private", false); // required for certain browsers 
header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment; filename="._CONFIG_SITE_HOST_URL.".txt"); 
echo $content;
//exit();