<?php

// includes and security
include_once('../_local_auth.inc.php');

$existing_file_server_id = (int) $_REQUEST['existing_file_server_id'];
$server_label            = trim($_REQUEST['server_label']);
$status_id               = (int) $_REQUEST['status_id'];
$server_type             = trim($_REQUEST['server_type']);
$storage_path            = trim($_REQUEST['storage_path']);
$ftp_host                = trim(strtolower($_REQUEST['ftp_host']));
$ftp_port                = (int) $_REQUEST['ftp_port'];
$ftp_username            = trim($_REQUEST['ftp_username']);
$ftp_password            = trim($_REQUEST['ftp_password']);
$file_server_domain_name = trim(strtolower($_REQUEST['file_server_domain_name']));
$script_path             = trim($_REQUEST['script_path']);
$max_storage_space       = str_replace(array(',', '.', '-', 'M', 'm', 'G', 'g', 'k', 'K', 'bytes', '(', ')', 'b', 'B', ' '), '', trim($_REQUEST['max_storage_space']));
$max_storage_space       = strlen($max_storage_space) ? $max_storage_space : 0;
$server_priority         = (int) trim($_REQUEST['server_priority']);
$route_via_main_site     = (int) $_REQUEST['route_via_main_site'];
$ftp_server_type         = trim($_REQUEST['ftp_server_type']);
$ftp_passive_mode        = trim($_REQUEST['ftp_passive_mode']);
$dlAccelerator           = (int)$_REQUEST['dlAccelerator'];

$file_server_direct_ip_address 				= trim($_REQUEST['file_server_direct_ip_address']);
$file_server_direct_ssh_port 				= 22;
$file_server_direct_ssh_username 			= '';
$file_server_direct_ssh_password 			= '';
$file_server_direct_server_path_to_storage 	= '';
if(strlen($file_server_direct_ip_address) > 0)
{
	$file_server_direct_ssh_port 				= (int)$_REQUEST['file_server_direct_ssh_port'];
	$file_server_direct_ssh_username 			= trim($_REQUEST['file_server_direct_ssh_username']);
	$file_server_direct_ssh_password 			= trim($_REQUEST['file_server_direct_ssh_password']);
	$file_server_direct_server_path_to_storage 	= trim($_REQUEST['file_server_direct_server_path_to_storage']);
	if(strlen($file_server_direct_server_path_to_storage) > 1)
	{
		// remove forward slash from path
		if(substr($file_server_direct_server_path_to_storage, strlen($file_server_direct_server_path_to_storage)-1, 1) == '/')
		{
			$file_server_direct_server_path_to_storage = substr($file_server_direct_server_path_to_storage, 0, strlen($file_server_direct_server_path_to_storage)-1);
		}
	}
}

// prepare result
$result          = array();
$result['error'] = false;
$result['msg']   = '';

// validate submission
if (strlen($server_label) == 0)
{
    $result['error'] = true;
    $result['msg']   = adminFunctions::t("server_label_invalid", "Please specify the server label.");
}
elseif (_CONFIG_DEMO_MODE == true)
{
    $result['error'] = true;
    $result['msg']   = adminFunctions::t("no_changes_in_demo_mode");
}
elseif ($server_type == 'local')
{
	if((strlen($file_server_direct_ip_address) > 0) && ((validation::validIPAddress($file_server_direct_ip_address) == false) && ($file_server_direct_ip_address != 'localhost')))
    {
        $result['error'] = true;
        $result['msg']   = adminFunctions::t("server_file_server_ssh_ipaddress_invalid", "The server IP address is invalid.");
    }
}
elseif ($server_type == 'ftp')
{
    if (strlen($ftp_host) == 0)
    {
        $result['error'] = true;
        $result['msg']   = adminFunctions::t("server_ftp_host_invalid", "Please specify the server ftp host.");
    }
    elseif ($ftp_port == 0)
    {
        $result['error'] = true;
        $result['msg']   = adminFunctions::t("server_ftp_port_invalid", "Please specify the server ftp port.");
    }
    elseif (strlen($ftp_username) == 0)
    {
        $result['error'] = true;
        $result['msg']   = adminFunctions::t("server_ftp_username_invalid", "Please specify the server ftp username.");
    }
}
elseif ($server_type == 'direct')
{
    $file_server_domain_name = str_replace(array('http://', 'https://'), '', $file_server_domain_name);
    if (strlen($file_server_domain_name) == 0)
    {
        $result['error'] = true;
        $result['msg']   = adminFunctions::t("server_file_server_domain_name_empty", "Please specify the file server domain name.");
    }
    elseif (strlen($script_path) == 0)
    {
        $script_path = '/';
    }
    elseif (strlen($script_path) != strlen(str_replace(' ', '', $script_path)))
    {
        $result['error'] = true;
        $result['msg']   = adminFunctions::t("server_file_server_path", "The file server path can not contain spaces.");
    }
	elseif((strlen($file_server_direct_ip_address) > 0) && (validation::validIPAddress($file_server_direct_ip_address) == false))
    {
        $result['error'] = true;
        $result['msg']   = adminFunctions::t("server_file_server_ssh_ipaddress_invalid", "The server IP address is invalid.");
    }

    // remove trailing forward slash
    if (substr($file_server_domain_name, strlen($file_server_domain_name) - 1, 1) == '/')
    {
        $file_server_domain_name = substr($file_server_domain_name, 0, strlen($file_server_domain_name) - 1);
    }
}

if (strlen($result['msg']) == 0)
{
    $row = $db->getRow('SELECT id FROM file_server WHERE serverLabel = ' . $db->quote($server_label) . ' AND id != ' . $existing_file_server_id);
    if (is_array($row))
    {
        $result['error'] = true;
        $result['msg']   = adminFunctions::t("server_label_already_in_use", "That server label has already been used, please choose another.");
    }
    else
    {
		// load some existing settings
		$sQL           = "SELECT serverAccess FROM file_server WHERE id=" .(int)$existing_file_server_id." LIMIT 1";
        $serverDetails = $db->getRow($sQL);
		$serverAccessArr = array();
        if ($serverDetails)
        {
			// server login data
			$server_access = $serverDetails['serverAccess'];
			if (strlen($server_access))
            {
				$server_access = coreFunctions::decryptValue($server_access);
                $serverAccessArr = json_decode($server_access, true);
            }
		}
		
        // prepare server config json
        $serverConfigArr                     = array();
        $serverConfigArr['ftp_server_type']  = $ftp_server_type;
        $serverConfigArr['ftp_passive_mode'] = $ftp_passive_mode;
		
		// prepare server access json
        $serverAccessArr['file_server_direct_ip_address']  				= $file_server_direct_ip_address;
        $serverAccessArr['file_server_direct_ssh_port'] 				= $file_server_direct_ssh_port;
		$serverAccessArr['file_server_direct_ssh_username'] 			= $file_server_direct_ssh_username;
		if(strlen($file_server_direct_ssh_password))
		{
			$serverAccessArr['file_server_direct_ssh_password'] 		= $file_server_direct_ssh_password;
		}
		$serverAccessArr['file_server_direct_server_path_to_storage'] 	= $file_server_direct_server_path_to_storage;

        if ($existing_file_server_id > 0)
        {
            // update the existing record
            $dbUpdate                       = new DBObject("file_server", array("serverLabel", "serverType", "ipAddress", "ftpPort", "ftpUsername", "ftpPassword", "statusId", "storagePath", "fileServerDomainName", "scriptPath", "maximumStorageBytes", "priority", "routeViaMainSite", "serverConfig", "dlAccelerator", "serverAccess"), 'id');
            $dbUpdate->serverLabel          = $server_label;
            $dbUpdate->serverType           = $server_type;
            $dbUpdate->statusId             = $status_id;
            $dbUpdate->ipAddress            = $ftp_host;
            $dbUpdate->ftpPort              = $ftp_port;
            $dbUpdate->ftpUsername          = $ftp_username;
            $dbUpdate->ftpPassword          = $ftp_password;
            $dbUpdate->storagePath          = $storage_path;
            $dbUpdate->fileServerDomainName = $file_server_domain_name;
            $dbUpdate->scriptPath           = $script_path;
            $dbUpdate->maximumStorageBytes  = $max_storage_space;
            $dbUpdate->priority             = $server_priority;
            $dbUpdate->routeViaMainSite     = $route_via_main_site;
            $dbUpdate->serverConfig         = json_encode($serverConfigArr);
            $dbUpdate->dlAccelerator        = $dlAccelerator;
			$dbUpdate->serverAccess         = coreFunctions::encryptValue(json_encode($serverAccessArr));

            $dbUpdate->id = $existing_file_server_id;
            $dbUpdate->update();

            $result['error'] = false;
            $result['msg']   = 'File server \'' . $server_label . '\' updated.';
        }
        else
        {
            // add the file server
            $dbInsert                       = new DBObject("file_server", array("serverLabel", "serverType", "ipAddress", "ftpPort", "ftpUsername", "ftpPassword", "statusId", "storagePath", "fileServerDomainName", "scriptPath", "maximumStorageBytes", "priority", "routeViaMainSite", "serverConfig", "dlAccelerator", "serverAccess"));
            $dbInsert->serverLabel          = $server_label;
            $dbInsert->serverType           = $server_type;
            $dbInsert->ipAddress            = $ftp_host;
            $dbInsert->ftpPort              = $ftp_port;
            $dbInsert->ftpUsername          = $ftp_username;
            $dbInsert->ftpPassword          = $ftp_password;
            $dbInsert->statusId             = $status_id;
            $dbInsert->storagePath          = $storage_path;
            $dbInsert->fileServerDomainName = $file_server_domain_name;
            $dbInsert->scriptPath           = $script_path;
            $dbInsert->maximumStorageBytes  = $max_storage_space;
            $dbInsert->priority             = $server_priority;
            $dbInsert->routeViaMainSite     = $route_via_main_site;
            $dbInsert->serverConfig         = json_encode($serverConfigArr);            
            $dbInsert->dlAccelerator        = $dlAccelerator;
			$dbInsert->serverAccess         = coreFunctions::encryptValue(json_encode($serverAccessArr));
            if (!$dbInsert->insert())
            {
                $result['error'] = true;
                $result['msg']   = adminFunctions::t("file_server_error_problem_record", "There was a problem adding the file server, please try again.");
            }
            else
            {
                $result['error'] = false;
                $result['msg']   = 'File server \'' . $server_label . '\' has been added.';
            }
        }
    }
}

echo json_encode($result);
exit;
