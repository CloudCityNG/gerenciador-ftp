<?php

/**
 * main file action class
 */
class fileAction
{

    public $errorMsg = null;

    /**
     * Queue file to be deleted.
     * 
     * @param integer $serverId
     * @param string $filePath
     * @param integer $fileId
     * @param string $actionDate
     * @return boolean|\DBObject
     */
    static function queueDeleteFile($serverId, $filePath, $fileId = null, $actionDate = null)
    {
        // if no action date passed, assume it needs done straight away
        if ($actionDate == null)
        {
            $actionDate = coreFunctions::sqlDateTime();
        }

        $dbInsert = new DBObject("file_action", array("file_id", "server_id", "file_path", "file_action", "status", "date_created", "action_date"));

        $dbInsert->file_id      = $fileId;
        $dbInsert->server_id    = $serverId;
        $dbInsert->file_path    = $filePath;
        $dbInsert->file_action  = 'delete';
        $dbInsert->status       = 'pending';
        $dbInsert->date_created = coreFunctions::sqlDateTime();
        $dbInsert->action_date  = $actionDate;
        if (!$dbInsert->insert())
        {
            return false;
        }

        return $dbInsert;
    }

    static function processQueue($limitType = null, $limitActions = 100)
    {
        // get file servers
        $fileServers = file::getFileServerData();
		$db = Database::getDatabase();

		// setup server ids, we need this to be an array to allow for multiple drives on the same server
		$server = file::getCurrentServerDetails();
		$serverIds = array();
		if($server['serverType'] == 'local')
		{
			// load other servers
			$servers = $db->getRows('SELECT id FROM file_server WHERE serverType = \'local\'');
			foreach($servers AS $serverItem)
			{
				$serverIds[] = (int)$serverItem['id'];
			}
		}
		else
		{
			$serverIds[] =(int)$server['id'];
		}

        // load pending queue items
        $pendingItems = $db->getRows('SELECT file_action.id, file_action.action_data, file_action.file_path, file_action.file_action, file_action.file_id, file.localFilePath, file.fileHash, file.serverId FROM file_action LEFT JOIN file ON file_action.file_id = file.id WHERE file_action.server_id IN (' . implode(',', $serverIds) . ') AND file_action.status = \'pending\' AND file_action.action_date < NOW() '.($limitType!=null?('AND file_action.file_action = '.$db->quote($limitType)):'').'ORDER BY file_action.id ASC LIMIT '.$limitActions);
        if ($pendingItems)
        {
            // get an admin API details for remote calls
            $apiCredentials = UserPeer::getAdminApiDetails();
            if($apiCredentials === false)
            {
                // log
                log::error('Failed getting any admin API credentials.');
                log::setContext($oldContext);

                return false;
            }
            
            foreach ($pendingItems AS $pendingItem)
            {
                // reload item to make sure it's not been triggered by another instance of this script running and it's still pending
                $checkPending = $db->getValue('SELECT id FROM file_action WHERE id='.(int)$pendingItem['id'].' AND status=\'pending\' LIMIT 1');
                if(!$checkPending)
                {
                    continue;
                }
                
                // prepare file path
                $filePath = trim($pendingItem['file_path']);

                // validation
                if (strlen($filePath) <= 1)
                {
                    $db->query('UPDATE file_action SET status = \'failed\', status_msg=\'File has zero length or is in the root folder.\', last_updated=NOW() WHERE id = ' . (int) $pendingItem['id'] . ' LIMIT 1');
                    continue;
                }

                // make sure file exists
                if (!file_exists($filePath))
                {
                    $db->query('UPDATE file_action SET status = \'failed\', status_msg=\'File or folder does not exist.\', last_updated=NOW() WHERE id = ' . (int) $pendingItem['id'] . ' LIMIT 1');
                    continue;
                }
                
                // set processing
                $db->query('UPDATE file_action SET status = \'processing\', last_updated=NOW() WHERE id = ' . (int) $pendingItem['id'] . ' LIMIT 1');

                // do action
                switch($pendingItem['file_action'])
                {
                    case 'delete':
                        // delete file or folder, this is the only option in this function for now
                        if (is_dir($filePath))
                        {
                            // directory
                            $rs = rmdir($filePath);
                        }
                        else
                        {
                            // file
                            $rs = unlink($filePath);
                        }

                        if ($rs === false)
                        {
                            $db->query('UPDATE file_action SET status = \'failed\', status_msg=\'Could not delete file or folder, possible permissions issue or folder has contents.\', last_updated=NOW() WHERE id = ' . (int) $pendingItem['id'] . ' LIMIT 1');
                            continue;
                        }
                        break;
                    case 'move':
                        $actionDetails = json_decode($pendingItem['action_data'], true);
                        $params = array();
                        $params['file_id'] = (int)$pendingItem['file_id'];
                        $params['server_id'] = (int)$actionDetails['newServerId'];

                        // log
                        log::info('Move file request. file_action id = '.$pendingItem['id']);

                        $url = api::createApiUrl(_CONFIG_SITE_FULL_URL, $apiCredentials['apikey'], $apiCredentials['username'], 'movefile', $params);
                        $rs = coreFunctions::getRemoteUrlContent($url);

                        // log
                        log::info('Move file result: '.print_r($rs, true).' file_action id = '.$pendingItem['id']);
                        
                        // handle failures
                        if($rs === false)
                        {
                            // log
                            log::error('Failed move file. file_action id = '.$pendingItem['id']);
                            log::setContext($oldContext);
                            
                            continue;
                        }
                        break;
					case 'restore':					
						$restorePath      = str_replace('_deleted/', '', $pendingItem['localFilePath']);
						$newFileHash      = md5_file($pendingItem['file_path']);
						$rs               = rename($pendingItem['file_path'], $restorePath);
						
						// File restored, update the database
						if($rs === true)	
						{
							$db->query("UPDATE file_action SET status_msg = 'file restoration complete', last_updated=NOW() WHERE id = ".(int)$pendingItem['id']." LIMIT 1");
							$db->query("UPDATE file SET statusId = 1, fileHash = ".$db->quote($newFileHash)." WHERE id = ".(int)$pendingItem['id']." LIMIT 1");
							
							log::info('Restore file result: '.print_r($rs, true).' file_action id = '.$pendingItem['id']);
                            continue;
						}
						
						// File NOT restored, log the errors & update the db entry.
						if(($rs === false) || ($strlen($restorePath) == 0))
                        {
                            // log
                            log::error('Failed file restoration. file_action id = '.$pendingItem['id']);
                            log::setContext($oldContext);
							
							$db->query('UPDATE file_action SET status = \'failed\', status_msg=\'Could not restore file.\', last_updated=NOW() WHERE id = '.(int)$pendingItem['id'].' LIMIT 1');
                            continue;
						}
						break;
                }

                // update file record
                $db->query('UPDATE file_action SET status = \'complete\', last_updated=NOW() WHERE id = ' . (int) $pendingItem['id'] . ' LIMIT 1');
                continue;
            }
        }
    }

    /**
     * Queue file to be moved.
     * 
     * @param type $serverId
     * @param type $fromFilePath
     * @param type $fileId
     * @param type $newServerId
     * @return boolean|\DBObject
     */
    static function queueMoveFile($serverId, $fromFilePath, $fileId, $newServerId)
    {
        // if no action date passed, assume it needs done straight away
        if ($actionDate == null)
        {
            $actionDate = coreFunctions::sqlDateTime();
        }

        $dbInsert = new DBObject("file_action", array("file_id", "server_id", "file_path", "file_action", "action_data", "status", "date_created", "action_date"));

        $dbInsert->file_id      = $fileId;
        $dbInsert->server_id    = $serverId;
        $dbInsert->file_path    = $fromFilePath;
        $dbInsert->file_action  = 'move';
        $dbInsert->action_data  = json_encode(array('newServerId' => $newServerId));
        $dbInsert->status       = 'pending';
        $dbInsert->date_created = coreFunctions::sqlDateTime();
        $dbInsert->action_date  = $actionDate;
        if (!$dbInsert->insert())
        {
            return false;
        }

        return $dbInsert;
    }

    static function createSystemConfigTrackers()
    {
        // get file servers
        $fileServers = file::getFileServerData();
    }

}
