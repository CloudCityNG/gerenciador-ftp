<?php

/**
 * main file server class
 */
class fileServer
{
	static function getAvailableServerId()
	{
        // connect db
		$db = Database::getDatabase(true);
        
        // if user logged in, check for server override
        $Auth = Auth::getAuth();
        if($Auth->loggedIn() == true)
        {
            // user is logged in, check for server override
            if((int)$Auth->user->uploadServerOverride)
            {
                // double check via the database
                $uploadServerOverride = (int)$db->getValue('SELECT uploadServerOverride FROM users WHERE id = '.(int)$Auth->id.' LIMIT 1');
                if($uploadServerOverride)
                {
                    return $uploadServerOverride;
                }
            }
        }

		// check plugins for server to use
		$params = pluginHelper::includeAppends('functions_get_available_server_id.php', array('serverId' => null));
		if ((int) $params['serverId'])
		{
			return $params['serverId'];
		}

		// choose server
		switch (SITE_CONFIG_C_FILE_SERVER_SELECTION_METHOD)
		{
			case 'Least Used Space':
				$sQL = "SELECT file_server.id ";
				$sQL .= "FROM file_server ";
				$sQL .= "WHERE statusId = 2 ";
				$sQL .= "ORDER BY totalSpaceUsed ASC";

				$serverDetails = $db->getRow($sQL);
				if (is_array($serverDetails))
				{
					return $serverDetails['id'];
				}

				// none found so return the default local
				return 1;

				break;
			case 'Until Full':
				$sQL = "SELECT file_server.id ";
				$sQL .= "FROM file_server ";
				$sQL .= "WHERE IF(maximumStorageBytes > 0, totalSpaceUsed <= maximumStorageBytes, 1=1) AND statusId = 2 ";
				$sQL .= "ORDER BY priority ASC, id ASC";

				$serverDetails = $db->getRow($sQL);
				if (is_array($serverDetails))
				{
					return $serverDetails['id'];
				}

				// none found so return the default local
				return 1;

				break;
			default:
				$sQL           = "SELECT id FROM file_server WHERE serverLabel = " . $db->quote(SITE_CONFIG_DEFAULT_FILE_SERVER) . " AND statusId = 2 LIMIT 1";
				$serverDetails = $db->getRow($sQL);
				if (is_array($serverDetails))
				{
					return $serverDetails['id'];
				}

				// none found so return the default local
				return 1;

				break;
		}

		// fall back
		return 1;
	}
    
    static function nginxXAccelRedirectEnabled($serverId)
    {
		// connect db
		$db = Database::getDatabase(true);
		
        // nginx, look for config value
		$nginx = $db->getRow("SELECT dlAccelerator FROM file_server WHERE id = ".(int)$serverId." AND (serverType = 'direct' OR serverType = 'local') LIMIT 1");
        if ((int)$nginx['dlAccelerator'] == 1)
        {
            return true;
        }
        
        return false;
    }
    
    static function apacheXSendFileEnabled($serverId)
    {
		// connect db
		$db = Database::getDatabase(true);
		
        // apache, look for config value
		$apache = $db->getRow("SELECT dlAccelerator FROM file_server WHERE id = ".(int)$serverId." AND (serverType = 'direct' OR serverType = 'local') LIMIT 1");
        if ((int)$apache['dlAccelerator'] == 2)
        {
            return true;
        }
        
        return false;
    }
    
    static function setDocRootData($fileServerId, $docRoot)
    {
        $db = Database::getDatabase();
        
        // get file server data
        $serverData = $db->getValue("SELECT serverConfig FROM file_server WHERE id = ".(int)$fileServerId." LIMIT 1");
        if($serverData === false)
        {
            return false;
        }
        
        $serverDataArr = array();
        if(strlen($serverData))
        {
            $serverDataArr = json_decode($serverData, true);
        }
        
        $serverDataArr['server_doc_root'] = $docRoot;
        
        // update in database
        $db->query('UPDATE file_server SET serverConfig = :serverConfig WHERE id = '.(int)$fileServerId.' LIMIT 1', array('serverConfig' => json_encode($serverDataArr)));
        
        return true;
    }
    
    static function getDocRoot($fileServerId)
    {
        $db = Database::getDatabase();
        
        // get file server data
        $serverData = $db->getValue("SELECT serverConfig FROM file_server WHERE id = ".(int)$fileServerId." LIMIT 1");
        if($serverData === false)
        {
            return false;
        }
        
        $serverDataArr = array();
        if(strlen($serverData))
        {
            $serverDataArr = json_decode($serverData, true);
        }
        
        $docRoot = isset($serverDataArr['server_doc_root'])?$serverDataArr['server_doc_root']:null;
        if($docRoot === null)
        {
            // get server detail
            $sQL = "SELECT file_server.* ";
            $sQL .= "FROM file_server ";
            $sQL .= "WHERE id=" . (int) $fileServerId;
            $row = $db->getRow($sQL);
            switch($row['serverType'])
            {
                case 'direct':
                    // set new doc root
                    $url = crossSiteAction::appendUrl('http://'.$row['fileServerDomainName'].$row['scriptPath'].'/'.ADMIN_FOLDER_NAME.'/ajax/server_manage_get_server_detail.ajax.php');
                    $responseJson = coreFunctions::getRemoteUrlContent($url);
                    $responseArr = json_decode($responseJson, true);
                    if(!is_array($responseArr))
                    {
                        return false;
                    }
        
                    $docRoot = $responseArr['server_doc_root'];
                    if(strlen($docRoot))
                    {
                        fileServer::setDocRootData($fileServerId, $docRoot);
                    }
                    break;
                default:
                    $docRoot = _CONFIG_SCRIPT_ROOT;
                    fileServer::setDocRootData($fileServerId, $docRoot);
            }
            
        }
        
        return $docRoot;
    }
	
	static function getFileServerAccessDetails($directFileServers = array())
	{
		// setup database
		$db = Database::getDatabase();
		
		// gte local default server id for later
		$serverId = file::getDefaultLocalServerId();
		
		// get server access details
		$serverDetails = $db->getRows('SELECT id, serverAccess, storagePath FROM file_server WHERE serverAccess IS NOT NULL AND serverType IN (\'local\', \'direct\')');
		if($serverDetails)
		{
			foreach($serverDetails AS $serverDetail)
			{
				$serverAccess = coreFunctions::decryptValue($serverDetail['serverAccess']);
                $serverAccessArray = json_decode($serverAccess, true);
                if (!is_array($serverAccessArray))
                {
                    continue;
                }
				
				$storagePath = $serverDetail['storagePath'];
				if(strlen($storagePath) == 0)
				{
					$storagePath = "files/";
				}
				
				// remove trailing forward slash
				if(substr($storagePath, strlen($storagePath)-1, 1) == '/')
				{
					$storagePath = substr($storagePath, 0, strlen($storagePath)-1);
				}
				
				// override any existing entries
				foreach($directFileServers AS $k=>$directFileServer)
				{
					if($directFileServer['file_server_id'] == $serverDetail['id'])
					{
						unset($directFileServers[$k]);
					}
				}
				
				// log which is the primary server
				$primaryServer = false;
				if($serverId == $serverDetail['id'])
				{
					$primaryServer = true;
				}
				
				$directFileServers[] = array(
					'file_server_id' => $serverDetail['id'],
					'ssh_host' => $serverAccessArray['file_server_direct_ip_address'],
					'ssh_port' => $serverAccessArray['file_server_direct_ssh_port'],
					'ssh_username' => $serverAccessArray['file_server_direct_ssh_username'],
					'ssh_password' => $serverAccessArray['file_server_direct_ssh_password'],
					'file_storage_path' => $serverAccessArray['file_server_direct_server_path_to_storage'].'/'.$storagePath,
					'base_storage_path' => $serverAccessArray['file_server_direct_server_path_to_storage'],
					'primary_local_server' => $primaryServer,
				 );
			}
		}
		
		return $directFileServers;
	}
}
