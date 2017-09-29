<?php

/**
 * API class for remote file management.
 * 
 * See /api/index.php for full usage details.
 */
class api
{

    public $apiKey   = '';
    public $userName = '';
    public $userData = '';
	
	private $_internalStatusTracker = false;

    function __construct($apiKey, $userName)
    {
        $this->apiKey   = $apiKey;
        $this->userName = $userName;
    }

    /*
     * Validate API access.
     */
    public function validateAccess()
    {
        // make sure username and key is valid, currently this API is for admin users only
        $db       = Database::getDatabase();
        $userData = $db->getRow('SELECT * FROM users WHERE apikey = ' . $db->quote($this->apiKey) . ' AND username = ' . $db->quote($this->userName) . ' AND level_id IN (SELECT id FROM user_level WHERE level_type = \'admin\') AND status = \'active\' LIMIT 1');
        if ($userData)
        {
            $this->userData = $userData;
        }

        return $userData;
    }

    /*
     * List all files within the account.
     */
    public function apiList($params)
    {
        // get all files for the current account
        $db    = Database::getDatabase();
        $items = $db->getRows('SELECT * FROM file WHERE userId = ' . (int) $this->userData['id'] . ' ORDER BY uploadedDate DESC');
        $rs    = array();
        foreach ($items AS $item)
        {
            $file          = file::hydrate($item);
            $rs[$file->id] = $file->getFullShortUrl();
        }

        return self::produceSuccess(array('total' => COUNT($rs), 'data'  => $rs));
    }

    /*
     * Get detailed information for a specific file.
     */
    public function apiInfo($params)
    {
        // make sure we have the file_id
        $file_id = null;
        if (isset($params['file_id']))
        {
            $file_id = (int) $params['file_id'];
        }

        if ((int) $file_id == 0)
        {
            return self::produceError('Error: Method requires a valid file_id within this account.');
        }

        // get all files for the current account
        $db = Database::getDatabase();
        $rs = $db->getRow('SELECT file.id AS file_id, originalFileName AS file_name, shortUrl AS short_url, fileType AS file_type, extension, fileSize AS file_size, visits AS total_downloads, uploadedDate AS uploaded_date, file_status.label AS status, folderId AS folder_id, fileHash AS file_hash FROM file LEFT JOIN file_status ON file.statusId = file_status.id WHERE userId = ' . (int) $this->userData['id'] . ' AND file.id=' . (int) $file_id . ' ORDER BY uploadedDate DESC');
        if (!$rs)
        {
            return self::produceError('Error: Method requires a valid file_id within this account.');
        }

        // only keep kvp
        $data = array();
        foreach ($rs AS $k => $v)
        {
            if (is_int($k))
            {
                continue;
            }
            $data[$k] = $v;
        }

        // add on full url, stats url and delete url
        $file               = file::loadById($file_id);
        $data['full_url']   = $file->getFullShortUrl();
        $data['stats_url']  = $file->getStatisticsUrl();
        $data['delete_url'] = $file->getDeleteUrl();
        $data['info_url']   = $file->getShortInfoUrl();

        return self::produceSuccess($data);
    }

    /*
     * Delete a file.
     */
    public function apiDelete($params)
    {
        // validate user
        $rs = $this->validateAccess();
        if($rs == false)
        {
            return self::outputError('Error: User invalid.');
        }
        
        // make sure we have the file_id
        $file_id = null;
        if (isset($params['file_id']))
        {
            $file_id = (int) $params['file_id'];
        }

        if ((int) $file_id == 0)
        {
            return self::produceError('Error: Method requires a valid file_id within this account.');
        }

        // load file
        $file = file::loadById($file_id);

        // ensure the current user owns the file
        if ($file->userId != $this->userData['id'])
        {
            return self::produceError('Error: Method requires a valid file_id within this account.');
        }

        // make sure the file is active
        if ($file->statusId != 1)
        {
            return self::produceError('Error: File is not active.');
        }

        // remove file
        $rs = $file->removeByUser();
        if (!$rs)
        {
            return self::produceError('Error: There was a problem removing the file.');
        }

        $data = 'File deleted.';
        return self::produceSuccess($data);
    }

    /*
     * Move a file to another server.
     */
    public function apiMovefile($params)
    {
        // validate user
        $rs = $this->validateAccess();
        if($rs == false)
        {
            return self::outputError('Error: User invalid.');
        }
        
        // admin only
        if ($this->userData['level_id'] != 20)
        {
            return self::outputError('Error: Invalid action.');
        }

        // make sure we have the file_id
        $file_id = null;
        if (isset($params['file_id']))
        {
            $file_id = (int) $params['file_id'];
        }

        if ((int) $file_id == 0)
        {
            return self::produceError('Error: Method requires a valid file_id.');
        }

        // load file
        $file = file::loadById($file_id);

        // make sure the file is active
        if ($file->statusId != 1)
        {
            return self::produceError('Error: File is not active.');
        }

        // this method can only be used on the receiving server, if this isn't is, forward the request
        $newServerDetails = null;
        if (($params['server_id'] != file::getCurrentServerId()) && (!isset($params['___internal_redirect_move_file'])))
        {
            // set flag to stop loops of doom!
            $params['___internal_redirect_move_file'] = true;

            // create new url
            $correctServerPath = file::getFileDomainAndPath($fileId, $params['server_id'], true);
            $url               = api::createApiUrl($correctServerPath, $this->apiKey, $this->userName, 'movefile', $params);
            $rs                = coreFunctions::getRemoteUrlContent($url);
            if (strlen($rs) == 0)
            {
                // try to get the url headers for better errors
                $headers = get_headers($url);
                if(isset($headers[0]))
                {
                    return self::produceError('Error: Problem contacting new file server to move file. '.$headers[0]);
                }
                return self::produceError('Error: Problem contacting new file server to move file, does the api exist?');
            }

			$this->_internalStatusTracker = true;
			
            return $rs;
        }
        elseif ($params['server_id'] != file::getCurrentServerId())
        {
            // fail for non local servers
            $newServerDetails = file::loadServerDetails($params['server_id']);
            if($newServerDetails['serverType'] != 'local')
            {
                return self::produceError('('.file::getCurrentServerId().' '.$params['server_id'].') Error: Problem contacting new file server to move file.');
            }
        }

        // by this stage we should be on the receiving file server, get the file contents
        if(!$newServerDetails)
        {
            $newServerDetails = file::loadServerDetails($params['server_id']);
        }
        $storagePath = _CONFIG_FILE_STORAGE_PATH;
        if (strlen($newServerDetails['storagePath']))
        {
            $storagePath = fileServer::getDocRoot($params['server_id']).'/'.$newServerDetails['storagePath'];
        }
        if (substr($storagePath, strlen($storagePath) - 1, 1) == '/')
        {
            $storagePath = substr($storagePath, 0, strlen($storagePath) - 1);
        }
        $storagePath .= '/';
		
		// currently this api method only works for local or direct file servers
		if(($newServerDetails['serverType'] != 'direct') && ($newServerDetails['serverType'] != 'local'))
		{
			return self::produceError('Error: Only \'direct\' or \'local\' file servers support this API method.');
		}
		
		// check if already on this server
		if($file->serverId == $params['server_id'])
		{
			return self::produceError('Error: File already exists on server #'.$params['server_id'].' ('.$newServerDetails['serverLabel'].').');
		}


		// get file contents
        $downloadToken = $file->generateDirectDownloadToken(0, 0);
        if (!$downloadToken)
        {
            // fail
            return self::produceError('Error: Could not create url (token) to get file.');
        }

        // compile full url
        $downloadUrl = $file->getFullShortUrl(true) . '?' . file::DOWNLOAD_TOKEN_VAR . '=' . $downloadToken;
        if (!$downloadUrl)
        {
            return self::produceError('Error: Could not get file contents.');
        }

        if (!fopen($downloadUrl, 'rb'))
        {
            return self::produceError('Error: Failed getting file from file server.');
        }
		
		// see if we have an amendment to the storage path, i.e. for caching
		if(isset($params['storage_path_append']))
		{
			$storagePath .= $params['storage_path_append'].'/';
			@mkdir($storagePath);
		}
        
		// make sure sub-folder exists
        $subFolder = current(explode('/', $file->localFilePath));
        @mkdir($storagePath.$subFolder);

        // prepare full file path
        $newServerFilePath = $storagePath.$file->localFilePath;

		// save file locally
		$rs = file_put_contents($newServerFilePath, fopen($downloadUrl, 'rb'));
		if(!$rs)
		{
            // create folder and try again
            $fullPath = dirname($newServerFilePath);
            if(!is_dir($fullPath))
            {
                mkdir($fullPath);
            }
            $rs = file_put_contents($newServerFilePath, fopen($downloadUrl, 'rb'));
            if(!$rs)
            {
                return self::produceError('Error: Failed writing file on local server. ('.$newServerFilePath.')');
            }
		}
        
        // delete original file
		$paramsDelete = $params;
		$paramsDelete['server_id'] = $file->serverId;
		$paramsDelete['file_path'] = $file->localFilePath;
        $rs = $this->apiRawdeletefile($paramsDelete);
		
		// update database including
		$db = Database::getDatabase();
        if(strlen($file->fileHash))
        {
            // update all with the same file hash
            $db->query('UPDATE file SET serverId = '.(int)$params['server_id'].' WHERE fileHash='.$db->quote($file->fileHash));
        }
        else
        {
            // no file hash found, update database
            $db->query('UPDATE file SET serverId = '.(int)$params['server_id'].' WHERE id='.(int)$file->id.' LIMIT 1');
        }

        // finish up
        $data = 'File moved to '.$newServerDetails['serverLabel'].' ('.$newServerFilePath.').';
        return self::produceSuccess($data);
    }
	
	/*
     * Move a file to another server.
     */
    public function apiCopyfile($params)
    {
        // admin only
        if ($this->userData['level_id'] != 20)
        {
            return self::outputError('Error: Invalid action.');
        }

        // make sure we have the file_id
        $file_id = null;
        if (isset($params['file_id']))
        {
            $file_id = (int) $params['file_id'];
        }

        if ((int) $file_id == 0)
        {
            return self::produceError('Error: Method requires a valid file_id.');
        }

        // load file
        $file = file::loadById($file_id);

        // make sure the file is active
        if ($file->statusId != 1)
        {
            return self::produceError('Error: File is not active.');
        }

        // this method can only be used on the receiving server, if this isn't is, forward the request
        if (($params['server_id'] != file::getCurrentServerId()) && (!isset($params['___internal_redirect_copy_file'])))
        {
            // set flag to stop loops of doom!
            $params['___internal_redirect_copy_file'] = true;

            // create new url
            $correctServerPath = file::getFileDomainAndPath($fileId, $params['server_id'], true);
            $url               = api::createApiUrl($correctServerPath, $this->apiKey, $this->userName, 'movefile', $params);
            $rs                = coreFunctions::getRemoteUrlContent($url);
            if (strlen($rs) == 0)
            {
                // try to get the url headers for better errors
                $headers = get_headers($url);
                if(isset($headers[0]))
                {
                    return self::produceError('Error: Problem contacting new file server to copy file. '.$headers[0]);
                }
                return self::produceError('Error: Problem contacting new file server to copy file, does the api exist?');
            }

			$this->_internalStatusTracker = true;
            return $rs;
        }
        elseif ($params['server_id'] != file::getCurrentServerId())
        {
            return self::produceError('Error: Problem contacting new file server to copy file.');
        }

        // by this stage we should be on the receiving file server, get the file contents
        $newServerDetails = file::loadServerDetails($params['server_id']);
        $storagePath = _CONFIG_FILE_STORAGE_PATH;
        if (strlen($newServerDetails['storagePath']))
        {
            $storagePath = fileServer::getDocRoot($params['server_id']).'/'.$newServerDetails['storagePath'];
        }
        if (substr($storagePath, strlen($storagePath) - 1, 1) == '/')
        {
            $storagePath = substr($storagePath, 0, strlen($storagePath) - 1);
        }
        $storagePath .= '/';
		
		// currently this api method only works for local or direct file servers
		if(($newServerDetails['serverType'] != 'direct') && ($newServerDetails['serverType'] != 'local'))
		{
			return self::produceError('Error: Only \'direct\' or \'local\' file servers support this API method.');
		}
		
		// check if already on this server
		if($file->serverId == $params['server_id'])
		{
			return self::produceError('Error: File already exists on server #'.$params['server_id'].' ('.$newServerDetails['serverLabel'].').');
		}

        // get file contents
        $downloadToken = $file->generateDirectDownloadToken(0, 0);
        if (!$downloadToken)
        {
            // fail
            return self::produceError('Error: Could not create url (token) to get file.');
        }

        // compile full url
        $downloadUrl = $file->getFullShortUrl(true) . '?' . file::DOWNLOAD_TOKEN_VAR . '=' . $downloadToken;
        if (!$downloadUrl)
        {
            return self::produceError('Error: Could not get file contents.');
        }


        if (!fopen($downloadUrl, 'rb'))
        {
            return self::produceError('Error: Failed getting file from file server.');
        }

		
		// see if we have an amendment to the storage path, i.e. for caching
		if(isset($params['storage_path_append']))
		{
			$storagePath .= $params['storage_path_append'].'/';
			@mkdir($storagePath);
		}
        
		// make sure sub-folder exists
        $subFolder = current(explode('/', $file->localFilePath));
        @mkdir($storagePath.$subFolder);
        
        // prepare full file path
        $newServerFilePath = $storagePath.$file->localFilePath;

		// save file locally
		$rs = file_put_contents($newServerFilePath, fopen($downloadUrl, 'rb'));
		if(!$rs)
		{
			return self::produceError('Error: Failed writing file on local server.');
		}

        // finish up
        $data = 'File copied to '.$newServerDetails['serverLabel'].' ('.$newServerFilePath.').';
        return self::produceSuccess($data);
    }
	
	/*
     * Get the contents of a file from the file server
     */
    public function apiRawgetfilecontent($params)
    {
        // admin only
        if ($this->userData['level_id'] != 20)
        {
            return self::outputError('Error: Invalid action.');
        }

        // make sure we have the file_id
        $file_id = null;
        if (isset($params['file_id']))
        {
            $file_id = (int) $params['file_id'];
        }

        if ((int) $file_id == 0)
        {
            return self::produceError('Error: Method requires a valid file_id.');
        }

        // load file
        $file = file::loadById($file_id);

        // make sure the file is active
        if ($file->statusId != 1)
        {
            return self::produceError('Error: File is not active.');
        }

        // get file contents
        $downloadToken = $file->generateDirectDownloadToken(0, 0);
        if (!$downloadToken)
        {
            // fail
            return self::produceError('Error: Could not create url (token) to get file.');
        }

        // compile full url
        $downloadUrl = $file->getFullShortUrl(true) . '?' . file::DOWNLOAD_TOKEN_VAR . '=' . $downloadToken;
        if (!$downloadUrl)
        {
            return self::produceError('Error: Could not get file contents.');
        }

        $this->_internalStatusTracker = true;
        return file_get_contents($downloadUrl);
    }
    
    /*
     * Get the contents of a file from the file server
     */
    public function apiRawdeletefile($params)
    {
        // admin only
        if ($this->userData['level_id'] != 20)
        {
            return self::outputError('Error: Invalid action.');
        }

        // make sure we have the file_path
        $filePath = null;
        if (isset($params['file_path']))
        {
            $filePath = $params['file_path'];
        }

        if ($filePath === null)
        {
            return self::produceError('Error: File path (file_path) not found in rawdeletefile action.');
        }
        
        // make sure we have a server_id to delete from
        if (!isset($params['server_id']))
        {
            return self::produceError('Error: Server id (server_id) not found in rawdeletefile action.');
        }

        // this method can only be used on the original file store server, if this isn't it, forward the request
        if (($params['server_id'] != file::getCurrentServerId()) && (!isset($params['___internal_redirect_raw_delete'])))
        {
            // set flag to stop loops of doom!
            $params['___internal_redirect_raw_delete'] = true;

            // create new url
            $correctServerPath = file::getFileDomainAndPath(null, $params['server_id'], true);
            $url               = api::createApiUrl($correctServerPath, $this->apiKey, $this->userName, 'rawdeletefile', $params);
            $rs                = coreFunctions::getRemoteUrlContent($url);
            if (strlen($rs) == 0)
            {
                // try to get the url headers for better errors
                $headers = get_headers($url);
                if(isset($headers[0]))
                {
                    return self::produceError('Error: Problem contacting file server to delete stored file. '.$headers[0]);
                }
                return self::produceError('Error: Problem contacting file server to to delete stored file, does the api exist?');
            }

			$this->_internalStatusTracker = true;
            return $rs;
        }
        elseif ($params['server_id'] != file::getCurrentServerId())
        {
            return self::produceError('Error: Problem contacting file server to delete stored file.');
        }

        // by this stage we should be on the file server with the file stored, get the file contents
        $newServerDetails = file::loadServerDetails($params['server_id']);
        $storagePath = _CONFIG_FILE_STORAGE_PATH;
        if (strlen($newServerDetails['storagePath']))
        {
            $storagePath = fileServer::getDocRoot($params['server_id']).'/'.$newServerDetails['storagePath'];
        }
        if (substr($storagePath, strlen($storagePath) - 1, 1) == '/')
        {
            $storagePath = substr($storagePath, 0, strlen($storagePath) - 1);
        }
        $storagePath .= '/';
		
		// full file path on the file system
		$filePath = $storagePath.$filePath;

		// make sure file exists
		if(!file_exists($filePath))
		{
			return self::produceError('Error: File does not exist on '.$newServerDetails['serverLabel'].' ('.$filePath.').');
		}

        // delete file
        $rs = unlink($filePath);
        if(!$rs)
		{
			return self::produceError('Error: Failed removing file on '.$newServerDetails['serverLabel'].' ('.$filePath.').');
		}

		// echo file content
		$this->_internalStatusTracker = true;
		$data = 'File deleted from '.$newServerDetails['serverLabel'].'.';
        return self::produceSuccess($data);
	}

    /*
     * Create success output.
     */
    public static function produceSuccess($dataArr)
    {
        $rs                  = array();
        $rs['success']       = true;
        $rs['response_time'] = time();
        $rs['result']        = $dataArr;

        return json_encode($rs);
    }

    public static function outputSuccess($dataArr)
    {
        $successStr = self::produceSuccess($dataArr);
        echo $successStr;
        exit;
    }

    /*
     * Create error output.
     */
    public static function produceError($errorMsg)
    {
        $rs               = array();
        $rs['error']      = true;
        $rs['error_time'] = time();
        $rs['error_msg']  = $errorMsg;

        return json_encode($rs);
    }

    public static function outputError($errorMsg)
    {
        $errorStr = self::produceError($errorMsg);
        echo $errorStr;
        exit;
    }

    /**
     * Create api url
     * 
     * @param type $apiPath
     * @param type $privateKey
     * @param type $username
     * @param type $action
     * @param type $params
     * @return string
     */
    public static function createApiUrl($apiPath, $privateKey, $username, $action, $params = array())
    {
        if (substr($apiPath, strlen($apiPath) - 1, 1) == '/')
        {
            $apiPath = substr($apiPath, 0, strlen($apiPath) - 1);
        }

        // check for duplicates
        if (isset($params['key']))
        {
            unset($params['key']);
        }
        if (isset($params['username']))
        {
            unset($params['username']);
        }
        if (isset($params['action']))
        {
            unset($params['action']);
        }

        // prepare extra params
        $extraParams = '';
        if (COUNT($params))
        {
            foreach ($params AS $k => $param)
            {
                $extraParams .= $k . '=' . urlencode($param) . '&';
            }
        }

        return _CONFIG_SITE_PROTOCOL . '://' . $apiPath . '/api/?key=' . urlencode($privateKey) . '&username=' . urlencode($username) . '&action=' . urlencode($action) . '&' . $extraParams;
    }

}
