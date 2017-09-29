<?php

/**
 * main file class
 */
class file
{

    public $errorMsg = null;

    const DOWNLOAD_TOKEN_VAR = 'download_token';
	const IMAGE_EXTENSIONS = 'gif|jpeg|jpg|png';

    function __construct()
    {
        $this->errorMsg = null;
    }

    public function download($forceDownload = true, $doPluginIncludes = true, $downloadToken = null, $fileTransfer = true)
    {
        // remove session
        if (isset($_SESSION['showDownload']))
        {
            $clearSession = true;

            // fixes android snag which requests files twice
            if (Stats::currentDeviceIsAndroid())
            {
                if (!isset($_SESSION['showDownloadFirstRun']))
                {
                    $_SESSION['showDownloadFirstRun'] = true;
                    $clearSession                     = false;
                }
                else
                {
                    $_SESSION['showDownloadFirstRun'] = null;
                    unset($_SESSION['showDownloadFirstRun']);
                }
            }

            if ($clearSession == true)
            {
                // reset session variable for next time
                $_SESSION['showDownload'] = null;
                unset($_SESSION['showDownload']);
                session_write_close();
            }
        }

        // setup mode
        $mode = 'SESSION';
        if ($downloadToken !== null)
        {
            $mode = 'TOKEN';
        }

        // for session downloads
        $userPackageId        = 0;
        $fileOwnerUserId    = 0;
        $speed              = null;
        $maxDownloadThreads = null;
        if ($mode == 'SESSION')
        {
            // get user
            $Auth = Auth::getAuth();

            // setup user level
            $userPackageId = $Auth->package_id;

            // file owner id
            $fileOwnerUserId = $Auth->id;
        }

        // for token downloads
        else
        {
            // get database
            $db = Database::getDatabase(true);

            // check token
            // $tokenData = $db->getRow('SELECT id, user_id, ip_address, file_id, download_speed, max_threads FROM download_token WHERE file_id = ' . $db->escape($this->id) . ' AND ip_address=' . $db->quote(coreFunctions::getUsersIPAddress()) . ' AND token = ' . $db->quote($downloadToken) . ' LIMIT 1');
            $tokenData = $db->getRow('SELECT id, user_id, ip_address, file_id, download_speed, max_threads FROM download_token WHERE file_id = ' . $db->escape($this->id) . ' AND  token = ' . $db->quote($downloadToken) . ' LIMIT 1');
            if ($tokenData == false)
            {
                return false;
            }

            // get user level
            if ((int) $tokenData['user_id'] > 0)
            {
                $fileOwnerUserId = (int) $tokenData['user_id'];
                $userPackageId     = (int)$db->getValue('SELECT level_id FROM users WHERE id=' . (int) $fileOwnerUserId . ' LIMIT 1');
            }

            $speed              = (int) $tokenData['download_speed'];
            $maxDownloadThreads = (int) $tokenData['max_threads'];
        }

        // clear any expired download trackers
        downloadTracker::clearTimedOutDownloads();
        downloadTracker::purgeDownloadData();

        // check for concurrent downloads for paid users
        if ($maxDownloadThreads === null)
        {
            $maxDownloadThreads = UserPeer::getMaxDownloadThreads($userPackageId);
        }
        if ((int) $maxDownloadThreads > 0)
        {
            // get database
            $db = Database::getDatabase(true);

            // allow for looping a number of times to allow older data to clear
            $loopCount = 0;
            while ($loopCount <= 3)
            {
                // get all active download data
                $sQL          = "SELECT COUNT(download_tracker.id) AS total_threads ";
                $sQL .= "FROM download_tracker ";
                $sQL .= "WHERE download_tracker.status='downloading' AND download_tracker.ip_address = " . $db->quote(coreFunctions::getUsersIPAddress()) . " ";
                $sQL .= "AND date_updated >= DATE_SUB(NOW(), INTERVAL 20 SECOND)";
                $sQL .= "GROUP BY download_tracker.ip_address ";
                $totalThreads = (int) $db->getValue($sQL);
                if ($totalThreads < (int) $maxDownloadThreads)
                {
                    $loopCount = 4;
                }
                else
                {
                    $loopCount++;
                    usleep(5000000);
                }
            }

            // exit if too many threads
            if ($totalThreads >= (int) $maxDownloadThreads)
            {
                // fail
                $db->close();
                header("HTTP/1.0 429 Too Many Requests");
                echo 'Error: Too many concurrent download requests.';
                exit;
            }
        }

        // php script timeout for long downloads (2 days!)
        if (false === strpos(ini_get('disable_functions'), 'set_time_limit'))
        {
            // suppress the warnings
            @set_time_limit(60 * 60 * 24 * 2);
        }

        // load the server the file is on
        $storageType         = 'local';
        $storageLocation     = _CONFIG_FILE_STORAGE_PATH;
        $uploadServerDetails = $this->loadServer();
        if ($uploadServerDetails != false)
        {
            $storageLocation = $uploadServerDetails['storagePath'];
            $storageType     = $uploadServerDetails['serverType'];

            // if no storage path set & local, use system default
            if ((strlen($storageLocation) == 0) && ($storageType == 'local'))
            {
                $storageLocation = _CONFIG_FILE_STORAGE_PATH;
            }
        }

        // get file path
        $fullPath = $this->getFullFilePath($storageLocation);

        // open file - via ftp
        if ($storageType == 'ftp')
        {
            // connect via ftp
            $conn_id = ftp_connect($uploadServerDetails['ipAddress'], $uploadServerDetails['ftpPort'], 30);
            if ($conn_id === false)
            {
                $this->errorMsg = 'Could not connect to ' . $uploadServerDetails['ipAddress'] . ' to upload file.';
                return false;
            }

            // authenticate
            $login_result = ftp_login($conn_id, $uploadServerDetails['ftpUsername'], $uploadServerDetails['ftpPassword']);
            if ($login_result === false)
            {
                $this->errorMsg = 'Could not login to ' . $uploadServerDetails['ipAddress'] . ' with supplied credentials.';
                return false;
            }

            // turn passive mode on
            if ((isset($uploadServerDetails['serverConfig']['ftp_passive_mode'])) && ($uploadServerDetails['serverConfig']['ftp_passive_mode'] == 'yes'))
            {
                // enable passive mode
                ftp_pasv($conn_id, true);
            }

            // setup ftp protocol
            $protocolFamily = STREAM_PF_UNIX; // linux / unix
            if ((isset($uploadServerDetails['serverConfig']['ftp_server_type'])) && ($uploadServerDetails['serverConfig']['ftp_server_type'] == 'windows' || $uploadServerDetails['serverConfig']['ftp_server_type'] == 'windows_alt'))
            {
                $protocolFamily = STREAM_PF_INET; // windows
            }

            // prepare the stream of data, unix
            $pipes = stream_socket_pair($protocolFamily, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
            if ($pipes === false)
            {
                $this->errorMsg = 'Could not create stream to download file on ' . $uploadServerDetails['ipAddress'];
                return false;
            }

            stream_set_write_buffer($pipes[0], 10000);
            stream_set_timeout($pipes[1], 10);
            stream_set_blocking($pipes[1], 0);

            $fail = false;
        }
        elseif ($storageType == 'direct')
        {
            $fullPath = _CONFIG_SCRIPT_ROOT . '/' . $fullPath;
        }

        // get download speed
        if ($speed === null)
        {
            $speed = (int) UserPeer::getMaxDownloadSpeed($userPackageId);
            if ($forceDownload == true)
            {
                // include any plugin includes
                $params = pluginHelper::includeAppends('class_file_download.php', array('speed' => $speed));
                $speed  = $params['speed'];
            }
        }

        // handle where to start in the download, support for resumed downloads
        $seekStart = 0;
        $seekEnd   = $this->fileSize - 1;
        if (isset($_SERVER['HTTP_RANGE']) || isset($HTTP_SERVER_VARS['HTTP_RANGE']))
        {
            if (isset($HTTP_SERVER_VARS['HTTP_RANGE']))
            {
                $seekRange = substr($HTTP_SERVER_VARS['HTTP_RANGE'], strlen('bytes='));
            }
            else
            {
                $seekRange = substr($_SERVER['HTTP_RANGE'], strlen('bytes='));
            }

            $range = explode('-', $seekRange);
            if ((int) $range[0] > 0)
            {
                $seekStart = intval($range[0]);
            }

            if ((int) $range[1] > 0)
            {
                $seekEnd = intval($range[1]);
            }
        }
		
		// should we use xSendFile
        $useXsendFile = false;
        if (($speed == 0) && ($forceDownload == true))
        {
            // check whether xsendfile is enabled
            if (fileServer::apacheXSendFileEnabled($this->serverId))
            {
                 $useXsendFile = true;
            }
        }

        if ($forceDownload == true)
        {
            // output some headers
            header("Expires: 0");
			
			// skip for xsendfile
			if ($useXsendFile == false)
            {
				header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
				header("Content-type: " . $this->fileType);
				header("Pragma: public");
				if($fileTransfer == true)
				{
					header("Content-Disposition: attachment; filename=\"" . str_replace("\"", "", $this->originalFilename) . "\"");
					header("Content-Description: File Transfer");
				}
			}
			
            header('Accept-Ranges: bytes');

            // allow plugins to request files cross domain
            header('Access-Control-Allow-Origin: ' . _CONFIG_SITE_PROTOCOL . '://' . _CONFIG_CORE_SITE_HOST_URL);
            header('Access-Control-Allow-Headers: Content-Type, Content-Range, Content-Disposition, Content-Description');
            header('Access-Control-Allow-Credentials: true');

			// skip for xsendfile
			if ($useXsendFile == false)
            {
				// allow for requests from iso devices for the first byte
				if (($seekStart > 0) || ($seekEnd == 1))
				{
					header("HTTP/1.0 206 Partial Content");
					header("Status: 206 Partial Content");
					header("Content-Length: " . ($seekEnd - $seekStart + 1));
					header("Content-Range: bytes " . $seekStart . '-' . $seekEnd . "/" . $this->fileSize);
				}
				else
				{
					header("Content-Length: " . $this->fileSize);
					header("Content-Range: bytes " . $seekStart . "-" . $seekEnd . "/" . $this->fileSize);
				}
			}
        }

        if (SITE_CONFIG_DOWNLOADS_TRACK_CURRENT_DOWNLOADS == 'yes')
        {
            // track downloads
            $downloadTracker = new downloadTracker($this);
            $downloadTracker->create($seekStart, $seekEnd);
        }

        // for returning the file contents
        $fileContent = '';

        if (function_exists('apache_setenv'))
        {
            // disable gzip HTTP compression so it would not alter the transfer rate
            apache_setenv('no-gzip', '1');
        }

        // clear old tokens
        file::purgeDownloadTokens();
		
		// reduce the bw amount on the account, for non owned files
		if ($fileOwnerUserId != $this->userId)
		{
			$db = Database::getDatabase();
			$remainingBWDownload = (int)$db->getValue('SELECT remainingBWDownload FROM users WHERE id = '.(int)$fileOwnerUserId.' LIMIT 1');
			if($remainingBWDownload != 0)
			{
				$totalDownloadSize = $this->fileSize;
				if (($seekStart > 0) || ($seekEnd == 1))
				{
					$totalDownloadSize = ($seekEnd - $seekStart + 1);
				}
				
				// security
				if($totalDownloadSize < 0)
				{
					$totalDownloadSize = 0;
				}

				$remainingBWDownload = $remainingBWDownload-$totalDownloadSize;
				if($remainingBWDownload <= 0)
				{
					$remainingBWDownload = null;
				}

				$db->query('UPDATE users SET remainingBWDownload = '.$db->escape($remainingBWDownload).' WHERE id = '.(int)$fileOwnerUserId.' LIMIT 1');
				
				// ensure the account is downgraded if they are non admin and reach 0
				if((UserPeer::getLevelIdFromPackageId($userPackageId) != 20) && ($remainingBWDownload == null))
				{
					$freeAccountTypeId = UserPeer::getDefaultFreeAccountTypeId();
					$db->query('UPDATE users SET level_id = '.(int)$freeAccountTypeId.', paidExpiryDate=NOW(), remainingBWDownload=null WHERE id = '.(int)$fileOwnerUserId.' LIMIT 1');
				}
			}
		}

        // include any plugins for other storage methods
        $params = pluginHelper::includeAppends('class_file_download_get_from_storage.inc.php', array('actioned'         => false, 'seekStart'        => $seekStart, 'seekEnd'          => $seekEnd, 'storageType'      => $storageType, 'fileContent'      => $fileContent, 'downloadTracker'  => $downloadTracker, 'forceDownload'    => $forceDownload, 'file'             => $this, 'doPluginIncludes' => $doPluginIncludes));
        if ($params['actioned'] == true)
        {
            $fileContent = $params['fileContent'];
        }
        else
        {
            // output file - via ftp
            $timeTracker = time();
            $length      = 0;
            if ($storageType == 'ftp')
            {
                // no need for database
                $db = Database::getDatabase();
                $db->close();

                if ((isset($uploadServerDetails['serverConfig']['ftp_server_type'])) && ($uploadServerDetails['serverConfig']['ftp_server_type'] == 'windows_alt'))
                {
                    // for some windows servers, not recommend as limited streaming support
                    $local_file  = "php://output";
                    ob_start();
                    ftp_get($conn_id, $local_file, $fullPath, FTP_BINARY);
                    $fileContent = ob_get_contents();
                    ob_end_clean();

                    if ($forceDownload == true)
                    {
                        echo $fileContent;
                    }
                    else
                    {
                        $fileContent .= $contents;
                    }
                }
                else
                {
                    // stream via ftp
                    $ret = ftp_nb_fget($conn_id, $pipes[0], $fullPath, FTP_BINARY, $seekStart);
                    while ($ret == FTP_MOREDATA)
                    {
                        // use fread as better supported
                        $contents = fread($pipes[1], $seekEnd + 1);
                        //$contents = stream_get_contents($pipes[1], $seekEnd + 1);
                        if ($contents !== false)
                        {
                            if ($forceDownload == true)
                            {
                                echo $contents;
                                coreFunctions::flushOutput();
                                if ($speed > 0)
                                {
                                    $usleep = strlen($contents) / $speed;
                                    if ($usleep > 0)
                                    {
                                        usleep($usleep * 1000000);
                                    }
                                }
                            }
                            else
                            {
                                $fileContent .= $contents;
                            }
                            $length = $length + strlen($contents);
                        }

                        $ret = ftp_nb_continue($conn_id);

                        // update download status every DOWNLOAD_TRACKER_UPDATE_FREQUENCY seconds
                        if (($timeTracker + DOWNLOAD_TRACKER_UPDATE_FREQUENCY) < time())
                        {
                            $timeTracker = time();
                            if (SITE_CONFIG_DOWNLOADS_TRACK_CURRENT_DOWNLOADS == 'yes')
                            {
                                $downloadTracker->update();
                            }
                        }
                    }
                }

                fclose($pipes[0]);
                fclose($pipes[1]);
            }
            // output file - local
            else
            {
                // no need for database
                $db = Database::getDatabase();
                $db->close();

                // attempt to send via XAccelRedirect to reduce load on the webserver
                // note that this hands off away from PHP so the download tracking
                // no longer works.
                if ($forceDownload == true)
                {
                    // check whether XAccelRedirect is enabled
                    if (fileServer::nginxXAccelRedirectEnabled($this->serverId))
                    {
                        // update stats
                        if (((int) $this->userId > 0) && ($fileOwnerUserId == $this->userId) || (UserPeer::getLevelIdFromPackageId($userPackageId) == 20))
                        {
                            // dont update stats, this was triggered by an admin user or file owner
                        }
                        else
                        {
                            // update stats
                            $rs = Stats::track($this, $this->id);
                            if ($rs)
                            {
                                $this->updateLastAccessed();
                            }
                        }

                        // reconnect database
                        $db = Database::getDatabase(true);

                        // finish off any plugins, we don't control at the end of the download
                        pluginHelper::includeAppends('class_file_download_complete.php', array('origin' => 'file.class.php', 'forceDownload'    => $forceDownload, 'fileOwnerUserId'  => $fileOwnerUserId, 'userLevelId'      => UserPeer::getLevelIdFromPackageId($userPackageId), 'file'             => $this, 'doPluginIncludes' => $doPluginIncludes));

                        if (SITE_CONFIG_DOWNLOADS_TRACK_CURRENT_DOWNLOADS == 'yes')
                        {
                            // close download
                            $downloadTracker->finish();
                        }

                        // log
                        log::info('Using Nginx XAccelRedirect to send the file to the user.');

                        // use XAccelRedirect
                        header("Content-type: " . $this->fileType);
                        header("Content-Disposition: attachment; filename=\"" . str_replace("\"", "", $this->originalFilename) . "\"");
                        if ($speed > 0)
                        {
                            header("X-Accel-Limit-Rate: " . $speed);
                        }
                        
                        // figure out storage location
                        $newStorageLocation = str_replace(DOC_ROOT, '', $storageLocation);
                        if(substr($newStorageLocation, strlen($newStorageLocation)-1, 1) == '/')
                        {
                        	$newStorageLocation = substr($newStorageLocation, 0, strlen($newStorageLocation)-1);
                        }
						if(substr($newStorageLocation, 0, 1) != '/')
                        {
                        	$newStorageLocation = '/'.$newStorageLocation;
                        }
						
                        header('X-Accel-Redirect: ' . $newStorageLocation . '/'. str_replace(array('../', '..'), '', $this->localFilePath));
                        //header('X-Accel-Redirect: /files/' . str_replace(array('../', '..'), '', $this->localFilePath));
                        exit;
                    }
                }

                // attempt to send via xsendfile to reduce load on the webserver
                // note that this hands off away from PHP so the download tracking
                // no longer works. It will also only work when there's no speed
                // restrictions for the download.
                if ($useXsendFile == true)
				{
					// update stats
					if (((int) $this->userId > 0) && ($fileOwnerUserId == $this->userId) || (UserPeer::getLevelIdFromPackageId($userPackageId) == 20))
					{
						// dont update stats, this was triggered by an admin user or file owner
					}
					else
					{
						// update stats
						$rs = Stats::track($this, $this->id);
						if ($rs)
						{
							$this->updateLastAccessed();
						}
					}

					// reconnect database
					$db = Database::getDatabase(true);

					// finish off any plugins, we don't control at the end of the download
					pluginHelper::includeAppends('class_file_download_complete.php', array('forceDownload'    => $forceDownload, 'fileOwnerUserId'  => $fileOwnerUserId, 'userLevelId'      => UserPeer::getLevelIdFromPackageId($userPackageId), 'file'             => $this, 'doPluginIncludes' => $doPluginIncludes));

					if (SITE_CONFIG_DOWNLOADS_TRACK_CURRENT_DOWNLOADS == 'yes')
					{
						// close download
						$downloadTracker->finish();
					}

					// log
					log::info('Using Apache X-Sendfile to send the file to the user.');

					// use xsendfile
					$etag = MD5(microtime());
					header("Using-X-Sendfile: true");
					header('X-Sendfile: ' . $fullPath);
					header("Content-type: " . $this->fileType);
					header("Content-Disposition: attachment; filename=\"" . str_replace("\"", "", $this->originalFilename) . "\"");
					header("Etag: \"" . $etag . "\"");
					exit;
				}

                // open file - locally
                $handle = @fopen($fullPath, "r");
                if (!$handle)
                {
					// log
					log::error('Could not open local file for reading in file.class.php: '.$fullPath);
					
                    $this->errorMsg = 'Could not open file for reading.';
                    return false;
                }

                // move to starting position
                fseek($handle, $seekStart);
                while (($buffer = fgets($handle, 4096)) !== false)
                {
                    if ($forceDownload == true)
                    {
                        echo $buffer;
                        coreFunctions::flushOutput();
                        if ($speed > 0)
                        {
                            $usleep = strlen($buffer) / $speed;
                            if ($usleep > 0)
                            {
                                usleep($usleep * 1000000);
                            }
                        }
                    }
                    else
                    {
                        $fileContent .= $buffer;
                    }

                    $length = $length + strlen($buffer);

                    // update download status every DOWNLOAD_TRACKER_UPDATE_FREQUENCY seconds
                    if (($timeTracker + DOWNLOAD_TRACKER_UPDATE_FREQUENCY) < time())
                    {
                        $timeTracker = time();
                        if (SITE_CONFIG_DOWNLOADS_TRACK_CURRENT_DOWNLOADS == 'yes')
                        {
                            $downloadTracker->update();
                        }
                    }
                }
                fclose($handle);
            }
        }

        // reconnect database
        $db = Database::getDatabase(true);
        
        // update stats
        if ($forceDownload == true)
        {
            // stats
            if (((int) $this->userId > 0) && ($fileOwnerUserId == $this->userId) || (UserPeer::getLevelIdFromPackageId($userPackageId) == 20))
            {
                // dont update stats, this was triggered by an admin user or file owner
            }
            else
            {
                // update stats
                $rs = Stats::track($this, $this->id);
                if ($rs)
                {
                    $this->updateLastAccessed();
                }
            }
        }

        // finish off any plugins
        pluginHelper::includeAppends('class_file_download_complete.php', array('forceDownload'    => $forceDownload, 'fileOwnerUserId'  => $fileOwnerUserId, 'userLevelId'      => UserPeer::getLevelIdFromPackageId($userPackageId), 'file'             => $this, 'doPluginIncludes' => $doPluginIncludes));

        if (SITE_CONFIG_DOWNLOADS_TRACK_CURRENT_DOWNLOADS == 'yes')
        {
            // close download
            $downloadTracker->finish();
        }

        // return file content
        if ($forceDownload == false)
        {
            return $fileContent;
        }

        exit();
    }

    public function loadServer()
    {
        // load the server the file is on
        if ((int) $this->serverId)
        {
            // load from the db
            $db                  = Database::getDatabase(true);
            $uploadServerDetails = $db->getRow('SELECT * FROM file_server WHERE id = ' . $db->quote((int) $this->serverId));
            $db->close();
            if (!$uploadServerDetails)
            {
                return false;
            }

            $serverConfigArr = '';
            if (strlen($uploadServerDetails['serverConfig']))
            {
                $serverConfig = json_decode($uploadServerDetails['serverConfig'], true);
                if (is_array($serverConfig))
                {
                    $serverConfigArr = $serverConfig;
                }
            }
            $uploadServerDetails['serverConfig'] = $serverConfigArr;

            return $uploadServerDetails;
        }

        return false;
    }

    public function getFullFilePath($prePath = '')
    {
        if (substr($prePath, strlen($prePath) - 1, 1) == '/')
        {
            $prePath = substr($prePath, 0, strlen($prePath) - 1);
        }

        return $prePath . '/' . $this->localFilePath;
    }

    /**
     * Get full short url path
     *
     * @return string
     */
    public function getFullShortUrl($finalDownloadBasePath = false)
    {
        if (SITE_CONFIG_FILE_URL_SHOW_FILENAME == 'yes')
        {
            return $this->getFullLongUrl($finalDownloadBasePath);
        }

        return $this->getShortUrlPath($finalDownloadBasePath);
    }

    public function getShortUrlPath($finalDownloadBasePath = false)
    {
        $fileServerPath = file::getFileDomainAndPath($this->id, $this->serverId, $finalDownloadBasePath);

        return _CONFIG_SITE_PROTOCOL . '://' . $fileServerPath . '/' . $this->shortUrl;
    }

    public function getStatisticsUrl($returnAccount = false)
    {
        return $this->getShortUrlPath() . '~s' . ($returnAccount ? ('&returnAccount=1') : '');
    }

    public function getDeleteUrl($returnAccount = false, $finalDownloadBasePath = false)
    {
        return $this->getShortUrlPath($finalDownloadBasePath) . '~d?' . $this->deleteHash . ($returnAccount ? ('&returnAccount=1') : '');
    }

    public function getInfoUrl($returnAccount = false)
    {
        return $this->getShortUrlPath() . '~i?' . $this->deleteHash . ($returnAccount ? ('&returnAccount=1') : '');
    }

    public function getShortInfoUrl($returnAccount = false)
    {
        return $this->getShortUrlPath() . '~i' . ($returnAccount ? ('&returnAccount=1') : '');
    }

    public function getOwnerUsername()
    {
        // get database
        $db = Database::getDatabase(true);

        // if no user id return false, i.e. this was uploaded anon
        if ($this->userId == NULL)
        {
            return false;
        }

        // lookup username
        return $db->getValue('SELECT username FROM users WHERE id = ' . (int) $this->userId . ' LIMIT 1');
    }

    /**
     * Get full long url including the original filename
     *
     * @return string
     */
    public function getFullLongUrl($finalDownloadBasePath = false)
    {
        return $this->getShortUrlPath($finalDownloadBasePath) . '/' . $this->getSafeFilenameForUrl();
    }
    
    public function getSafeFilenameForUrl()
    {
        return str_replace(array(" ", "\"", "'", ";", "#", "%"), "_", strip_tags($this->originalFilename));
    }

    /**
     * Method to increment visitors
     */
    public function updateVisitors()
    {
        $db = Database::getDatabase(true);
        $this->visits++;
        $db->query('UPDATE file SET visits = :visits WHERE id = :id', array('visits' => $this->visits, 'id'     => $this->id));
    }

    /**
     * Method to update last accessed
     */
    public function updateLastAccessed()
    {
        $db = Database::getDatabase(true);
        $db->query('UPDATE file SET lastAccessed = NOW() WHERE id = :id', array('id' => $this->id));
    }

    /**
     * Method to set folder
     */
    public function updateFolder($folderId = '')
    {
        $db       = Database::getDatabase(true);
        $folderId = (int) $folderId;
        if ($folderId == 0)
        {
            $folderId = '';
        }
        $db->query('UPDATE file SET folderId = :folderId WHERE id = :id', array('folderId' => $folderId, 'id'       => $this->id));
    }

    /**
     * Remove by user
     */
    public function removeByUser()
    {
        return $this->_removeByStatusId(2);
    }

    /**
     * Remove by system
     */
    public function removeBySystem()
    {
        return $this->_removeByStatusId(5);
    }

    /**
     * Remove by admin
     */
    public function removeByAdmin()
    {
        return $this->_removeByStatusId(3);
    }

    /**
     * Remove by status
     */
    public function _removeByStatusId($newStatusId = 3)
    {
        // get database
        $db = Database::getDatabase(true);

        // remove the actual file from storage
        $rs = $this->_removeFile();
        if ($rs == true)
        {
            // update db
            $rs = $db->query('UPDATE file SET statusId = ' . (int) $newStatusId . ', fileHash="" WHERE id = :id', array('id' => $this->id));
            if ($db->affectedRows() == 1)
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Removes the actual file, not the database entry
     */
    public function _removeFile()
    {
        // get the database
        $db = Database::getDatabase(true);

        // load the server the file is on
        $storageType         = 'local';
        $storageLocation     = _CONFIG_FILE_STORAGE_PATH;
        $uploadServerDetails = $this->loadServer();
        if ($uploadServerDetails != false)
        {
            $storageLocation = _CONFIG_SCRIPT_ROOT.'/'.$uploadServerDetails['storagePath'];
            $storageType     = $uploadServerDetails['serverType'];
            if ((strlen($uploadServerDetails['storagePath']) == 0) && ($storageType == 'local'))
            {
                $storageLocation = _CONFIG_FILE_STORAGE_PATH;
            }
        }

        // check if the file is shared, don't remove if so
        if ($this->_fileIsShared() == false)
        {
            // file path
            $filePath = $this->getFullFilePath($storageLocation);
            if (($storageType == 'ftp') || ($storageType == 'amazon_s3'))
            {
                $filePath = $this->getFullFilePath($uploadServerDetails['storagePath']);
            }

            // include any plugins for other storage methods
            $params = pluginHelper::includeAppends('class_file_remove_file.inc.php', array('actioned'        => false, 'filePath'        => $filePath, 'storageType'     => $storageType, 'storageLocation' => $storageLocation, 'file'            => $this));
            if ($params['actioned'] == true)
            {
                if ((isset($params['errorMsg'])) && (strlen($params['errorMsg'])))
                {
                    $this->errorMsg = $params['errorMsg'];
                    return false;
                }

                return true;
            }
            else
            {
                // remote - ftp
                if ($storageType == 'ftp')
                {
                    // connect via ftp
                    $conn_id = ftp_connect($uploadServerDetails['ipAddress'], $uploadServerDetails['ftpPort'], 30);
                    if ($conn_id === false)
                    {
                        $this->errorMsg = 'Could not connect to ' . $uploadServerDetails['ipAddress'] . ' to upload file.';
                        return false;
                    }

                    // authenticate
                    $login_result = ftp_login($conn_id, $uploadServerDetails['ftpUsername'], $uploadServerDetails['ftpPassword']);
                    if ($login_result === false)
                    {
                        $this->errorMsg = 'Could not login to ' . $uploadServerDetails['ipAddress'] . ' with supplied credentials.';
                        return false;
                    }

                    // remove file
                    if (!ftp_delete($conn_id, $filePath))
                    {
                        // make sure file still exists before erroring
                        $filePathNoName = str_replace(end(explode('/', $filePath)), '', $filePath);
                        $file_list      = ftp_nlist($conn_id, $filePathNoName);
                        if ($file_list)
                        {
                            if (in_array($filePath, $file_list))
                            {
                                $this->errorMsg = 'Could not remove file on ' . $uploadServerDetails['ipAddress'];
                                return false;
                            }
                        }
                    }

                    return true;
                }
                // enable removal of 'direct' stored files
                elseif ($storageType == 'direct')
                {
                    // check if we're on the direct file server
                    if ($uploadServerDetails['fileServerDomainName'] == _CONFIG_SITE_HOST_URL)
                    {
                        if (!file_exists($filePath))
                        {
                            $filePath = DOC_ROOT . '/' . $filePath;
                        }

                        /* old code to do the deletes directly, disabled in faviour of moving then deleting via the file_action queue
                        $rs = unlink($filePath);
                        if ($rs)
                        {
                            // check for any other files in the folder, remove if empty
                            $folderPath = dirname($filePath);
                            $otherFiles = coreFunctions::getDirectoryListing($folderPath);
                            if (COUNT($otherFiles) == 0)
                            {
                                rmdir($folderPath);
                            }
                        }
                         */
                        
                        // move file into _deleted
                        if(file_exists($filePath))
                        {
                            $partialPath = substr($this->localFilePath, 0, 2);
                            $finalPath = $storageLocation.'_deleted/'.$this->localFilePath;
                            if(!file_exists(dirname($finalPath)))
                            {
                                @mkdir(dirname($finalPath), 0755, true);
                            }

                            // move main file
                            $rs = @rename($filePath, $finalPath);
                            if($rs)
                            {
                                // schedule delete file action on main file (now in the _deleted folder), this gives time for it to be reversed if needed
                                fileAction::queueDeleteFile($this->serverId, $finalPath, $this->id, date('Y-m-d H:i:s', strtotime('+'.(int)SITE_CONFIG_PURGE_DELETED_FILES_PERIOD_MINUTES.' minutes')));
                            }
                            
                            return true;
                        }
                    }
                }

                if (file_exists($filePath))
                {
                    // schedule delete
                    $finalPath = $storageLocation.'_deleted/'.$this->localFilePath;
                    if(!file_exists(dirname($finalPath)))
                    {
                        @mkdir(dirname($finalPath), 0755, true);
                    }

                    // move main file
                    $rs = @rename($filePath, $finalPath);
                    if($rs)
                    {
                        // schedule delete file action on main file (now in the _deleted folder), this gives time for it to be reversed if needed
                        fileAction::queueDeleteFile($this->serverId, $finalPath, $this->id, date('Y-m-d H:i:s', strtotime('+'.(int)SITE_CONFIG_PURGE_DELETED_FILES_PERIOD_MINUTES.' minutes')));
                    }

                    return true;
                }
                else
                {
                    return true;
                }
            }

            return false;
        }

        return true;
    }

    private function _fileIsShared()
    {
        // get database
        $db = Database::getDatabase(true);

        // get file hash
        $fileShared = false;
        $fileHash   = $db->getValue("SELECT fileHash FROM file WHERE id=" . $this->id . " LIMIT 1");
        if (strlen($fileHash))
        {
            // check for other active files which share the stored file
            $findFile = $db->getRow("SELECT * FROM file WHERE fileHash=" . $db->quote($fileHash) . " AND statusId=1 AND id != " . $this->id . " LIMIT 1");
            if ($findFile)
            {
                $fileShared = true;
            }
        }

        return $fileShared;
    }

    public function getLargeIconPath()
    {
        $fileTypePath = DOC_ROOT . '/themes/' . SITE_CONFIG_SITE_THEME . '/images/file_icons/512px/' . strtolower($this->extension) . '.png';
        if (!file_exists($fileTypePath))
        {
            return false;
        }

        return SITE_IMAGE_PATH . '/file_icons/512px/' . strtolower($this->extension) . '.png';
    }

    public function getFilenameExcExtension()
    {
        $filename = $this->originalFilename;
		$extWithDot = '.' . $this->extension;
		if(substr($filename, (strlen($filename)-strlen($extWithDot)), strlen($extWithDot)) == $extWithDot)
		{
			$filename = substr($filename, 0, (strlen($filename)-strlen($extWithDot)));
		}

        return $filename;
    }

    /**
     * Method to set password
     */
    public function updatePassword($password = '')
    {
        $db          = Database::getDatabase(true);
        $md5Password = '';
        if (strlen($password))
        {
            $md5Password = md5($password);
        }

        $db->query('UPDATE file SET accessPassword = :accessPassword WHERE id = :id', array('accessPassword' => $md5Password, 'id'             => $this->id));
    }
	
	// not currently used, use album passwords instead
	public function hasPasswordDirectlySet()
	{
		return false;
	}
	
	// @TODO - also check folder
	public function isPasswordProtected()
	{
		return $this->hasPasswordDirectlySet();
	}

    public function getHtmlLinkCode()
    {
        return '&lt;a href=&quot;' . $this->getFullShortUrl() . '&quot; target=&quot;_blank&quot; title=&quot;' . t('download_from', 'Download from') . ' ' . SITE_CONFIG_SITE_NAME . '&quot;&gt;' . t('class_file_download', 'Download') . ' ' . validation::safeOutputToScreen(validation::safeOutputToScreen($this->originalFilename)) . ' ' . t('class_file_from', 'from') . ' ' . SITE_CONFIG_SITE_NAME . '&lt;/a&gt;';
    }

    public function getForumLinkCode()
    {
        return '[url]' . validation::safeOutputToScreen($this->getFullShortUrl()) . '[/url]';
    }

    /**
     * Create a copy of the file in the database
     */
    public function duplicateFile($copyProperties = array())
    {
        $db       = Database::getDatabase(true);
        $dbInsert = new DBObject("file", array("originalFilename", "shortUrl", "fileType", "extension", "fileSize", "localFilePath", "userId", "totalDownload", "uploadedIP", "uploadedDate", "statusId", "deleteHash", "serverId", "fileHash", "folderId"));

        $dbInsert->originalFilename = $this->originalFilename;
        $dbInsert->shortUrl         = $this->shortUrl;
        $dbInsert->fileType         = $this->fileType;
        $dbInsert->extension        = $this->extension;
        $dbInsert->fileSize         = $this->fileSize;
        $dbInsert->localFilePath    = $this->localFilePath;

        // add user id if user is logged in
        $dbInsert->userId = NULL;
        $Auth             = Auth::getAuth();
        if ($Auth->loggedIn())
        {
            $dbInsert->userId = (int) $Auth->id;
        }

        $dbInsert->totalDownload = 0;
        $dbInsert->uploadedIP    = coreFunctions::getUsersIPAddress();
        $dbInsert->uploadedDate  = coreFunctions::sqlDateTime();
        $dbInsert->statusId      = 1;
        $deleteHash              = md5($this->originalFilename . coreFunctions::getUsersIPAddress() . microtime());
        $dbInsert->deleteHash    = $deleteHash;
        $dbInsert->serverId      = $this->serverId;
        $dbInsert->fileHash      = $this->fileHash;
		$dbInsert->folderId      = NULL;

		// overwrite with any properties passed into the method
		foreach($copyProperties AS $k=>$v)
		{
			$dbInsert->$k = $v;
		}
		
		// attempt the insert
        if (!$dbInsert->insert())
        {
            return false;
        }

        // create short url
        $tracker  = 1;
        $shortUrl = file::createShortUrlPart($tracker . $dbInsert->id);
        $fileTmp  = file::loadByShortUrl($shortUrl);
        while ($fileTmp)
        {
            $shortUrl = file::createShortUrlPart($tracker . $dbInsert->id);
            $fileTmp  = file::loadByShortUrl($shortUrl);
            $tracker++;
        }

        // update short url
        file::updateShortUrl($dbInsert->id, $shortUrl);
        $file = file::loadByShortUrl($shortUrl);

        pluginHelper::includeAppends('class_file_duplicate_file.inc.php', array('oldFile' => $this, 'newFile' => $file));

        return $file;
    }
	
	/**
	 * checks if there is another file record in the database sharing the same real file
	 */
	public function isDuplicate()
	{
		$db = Database::getDatabase();
		$isDuplicate = (int)$db->getValue('SELECT COUNT(id) FROM file WHERE fileHash = '.$db->quote($this->fileHash).' AND statusId = 1 AND id != '.(int)$this->id.' LIMIT 1');
		if($isDuplicate > 0)
		{
			return true;
		}
		
		return false;
	}

    /**
     * Remove file and any database data
     */
    public function deleteFileIncData()
    {
        // get database
        $db = Database::getDatabase(true);

        // remove the actual file from storage
        $rs = $this->_removeFile();

        // stats
        $db->query('DELETE FROM stats WHERE file_id = ' . (int) $this->id);

        // file
        $db->query('DELETE FROM file WHERE id = ' . (int) $this->id . ' LIMIT 1');

        return true;
    }

    public function generateDirectDownloadToken($downloadSpeedOverride = null, $maxThreadsOverride = null)
    {
        // get database
        $db = Database::getDatabase(true);

        // get auth
        $Auth = Auth::getAuth();

        // make sure one doesn't already exist for the file
        $checkToken = true;
        while ($checkToken != false)
        {
            // generate unique hash
            $downloadToken = hash('sha256', $this->id . microtime() . rand(1000, 9999));
            $checkToken    = $db->getValue('SELECT id FROM download_token WHERE file_id=' . $db->escape($this->id) . ' AND token=' . $db->escape($downloadToken) . ' LIMIT 1');
        }

        // insert token into database
        $userId = '';
        if ($Auth->loggedIn())
        {
            $userId = $Auth->id;
        }

        if ($downloadSpeedOverride === null)
        {
            $downloadSpeedOverride = UserPeer::getMaxDownloadSpeed($Auth->package_id);
        }
        if ($maxThreadsOverride === null)
        {
            $maxThreadsOverride = UserPeer::getMaxDownloadThreads($Auth->package_id);
        }

        $dbInsert                 = new DBObject("download_token", array("token", "user_id", "ip_address", "file_id", "created", "expiry", "download_speed", "max_threads"));
        $dbInsert->token          = $downloadToken;
        $dbInsert->user_id        = $userId;
        $dbInsert->ip_address     = coreFunctions::getUsersIPAddress();
        $dbInsert->file_id        = $this->id;
        $dbInsert->created        = date('Y-m-d H:i:s');
        $dbInsert->expiry         = date('Y-m-d H:i:s', time() + (60 * 60 * 24));
        $dbInsert->download_speed = (int) $downloadSpeedOverride;
        $dbInsert->max_threads    = (int) $maxThreadsOverride;
        if (!$dbInsert->insert())
        {
            return false;
        }

        return $downloadToken;
    }

    /**
     * Generate a link for downloading files directly. Allows for download managers
     * and no reliance on sessions.
     */
    public function generateDirectDownloadUrl()
    {
        // get database
        $db = Database::getDatabase(true);

        // get download token
        $downloadToken = $this->generateDirectDownloadToken();
        if (!$downloadToken)
        {
            $errorMsg = 'Failed generating direct download link, please try again later.';
            return coreFunctions::getCoreSitePath() . "/error." . SITE_CONFIG_PAGE_EXTENSION . "?e=" . urlencode($errorMsg);
        }

        // compile full url
        return $this->getFullShortUrl(true) . '?' . file::DOWNLOAD_TOKEN_VAR . '=' . $downloadToken;
    }

    /**
     * Generate a link for streaming media files. Allows for no limits on speed or concurrent downloads.
     */
    public function generateDirectDownloadUrlForMedia()
    {
        // get database
        $db = Database::getDatabase(true);

        // get download token
        $downloadToken = $this->generateDirectDownloadToken(0, 10);
        if (!$downloadToken)
        {
            $errorMsg = 'Failed generating direct download link, please try again later.';
            return coreFunctions::getCoreSitePath() . "/error." . SITE_CONFIG_PAGE_EXTENSION . "?e=" . urlencode($errorMsg);
        }

        // compile full url
        return $this->getFullShortUrl(true) . '?' . file::DOWNLOAD_TOKEN_VAR . '=' . $downloadToken;
    }

    /**
     * Whether stats data is private and can only be viewed by the account owner
     * 
     * @return boolean
     */
    public function canViewStats()
    {
        // check for admin users, they should be allowed access to all
        $Auth = Auth::getAuth();
        if ($Auth->level_id >= 10)
        {
            return true;
        }

        // if file doesn't belong to an account, assume public
        if ((int) $this->userId == 0)
        {
            return true;
        }

        // if logged in user matches owner
        if ($Auth->id == $this->userId)
        {
            return true;
        }

        // user not logged in or other account, load file owner and see if flagged as private
        $owner = UserPeer::loadUserById($this->userId);
        if (!$owner)
        {
            return true;
        }

        // check if stats are public or private on account, 0 = public
        if ($owner->privateFileStatistics == 0)
        {
            return true;
        }

        return false;
    }
    
    /**
     * Schedule server move for stored file.
     * 
     * @param type $newServerId
     */
    public function scheduleServerMove($newServerId)
    {
        // make sure the new server is different from the existing
        if($this->serverId == $newServerId)
        {
            return false;
        }
        
        // load the server the file is on
        $storageType         = 'local';
        $storageLocation     = _CONFIG_FILE_STORAGE_PATH;
        $uploadServerDetails = $this->loadServer();
        if ($uploadServerDetails != false)
        {
			// fallback (shouldn't really be used)
			$storageLocation = _CONFIG_SCRIPT_ROOT.'/'.$uploadServerDetails['storagePath'];
			
			// direct servers
			if(strlen($uploadServerDetails['serverConfig']['server_doc_root']))
			{
				$storageLocation = $uploadServerDetails['serverConfig']['server_doc_root'].'/'.$uploadServerDetails['storagePath'];
				
				// allow for absolute paths in storagePath
				if(strlen($uploadServerDetails['serverConfig']['server_doc_root']) > 1)
				{
					if($uploadServerDetails['serverConfig']['server_doc_root'] == substr($uploadServerDetails['storagePath'], 0, strlen($uploadServerDetails['serverConfig']['server_doc_root'])))
					{
						$storageLocation = $uploadServerDetails['storagePath'];
					}
				}
			}
           
            $storageType     = $uploadServerDetails['serverType'];
            if ((strlen($uploadServerDetails['storagePath']) == 0) && ($storageType == 'local'))
            {
                $storageLocation = _CONFIG_FILE_STORAGE_PATH;
            }
        }
        
        // file path
        $filePath = $this->getFullFilePath($storageLocation);
        
        // queue for moving
        return fileAction::queueMoveFile($this->serverId, $filePath, $this->id, $newServerId);
    }
    
    public function hasPendingFileAction()
    {
        // get database
        $db = Database::getDatabase();

        $rs = (int)$db->getValue('SELECT COUNT(id) FROM file_action WHERE (status = \'pending\' OR status = \'processing\') AND file_id = '.$this->id);
        if($rs > 0)
        {
            return true;
        }
        
        return false;
    }
	
	public function getFolderData()
    {
		if($this->folderId == NULL)
		{
			return false;
		}
		
		
        $folder = fileFolder::loadById((int)$this->folderId);
		if(!$folder)
		{
			return false;
		}
		
		return $folder;
    }
	
	public function isImage()
	{
		return in_array($this->extension, file::getImageExtensionsArr());
	}
	
	public function isPublic()
	{
		return $this->isPublic > 0;
	}
	
	public function getFileHash()
	{
		$fileHash = $this->unique_hash;
		if(strlen($fileHash) == 0)
		{
			// create hash
			$fileHash = file::createUniqueFileHash($this->id);
		}
		
		return $fileHash;
	}
	
	public function blockFutureUploads()
	{
		if(strlen($this->fileHash) == 0)
		{
			return true;
		}
		
		// check to make sure we don't already have it blocked
		$isBlocked = file::checkFileHashBlocked($this->fileHash);
		if($isBlocked)
		{
			return true;
		}
		
		// block file hash
		$dbInsert                 = new DBObject("file_block_hash", array("file_hash", "date_created"));
        $dbInsert->file_hash      = $this->fileHash;
        $dbInsert->date_created   = coreFunctions::sqlDateTime();
		
        return $dbInsert->insert();
	}

    /**
     * Hydrate file data into a file object, save reloading from database is we already have the data
     * 
     * @param type $fileDataArr
     * @return file
     */
    static function hydrate($fileDataArr)
    {
        $fileObj = new file();
        foreach ($fileDataArr AS $k => $v)
        {
            $fileObj->$k = $v;
        }

        return $fileObj;
    }

    /**
     * Load by short url
     *
     * @param string $shortUrl
     * @return file
     */
    static function loadByShortUrl($shortUrl)
    {
        $db  = Database::getDatabase(true);
        $row = $db->getRow('SELECT * FROM file WHERE shortUrl = ' . $db->quote($shortUrl));
        if (!is_array($row))
        {
            return false;
        }

        $fileObj = new file();
        foreach ($row AS $k => $v)
        {
            $fileObj->$k = $v;
        }

        return $fileObj;
    }

    /**
     * Load by full url
     *
     * @param string $fileUrl
     * @return file
     */
    static function loadByFullUrl($fileUrl)
    {
        // figure out short url part
        $fileUrl = str_replace(array('http://', 'https://'), '', strtolower($fileUrl));

        // try to match domains
        $shortUrlSection = null;
        if (substr($fileUrl, 0, strlen(_CONFIG_SITE_FULL_URL)) == _CONFIG_SITE_FULL_URL)
        {
            $shortUrlSection = str_replace(_CONFIG_SITE_FULL_URL . '/', '', $fileUrl);
        }
        else
        {
            // load direct file servers
            $db          = Database::getDatabase(true);
            $fileServers = $db->getRows('SELECT fileServerDomainName FROM file_server WHERE LENGTH(fileServerDomainName) > 0 AND serverType = \'direct\'');
            if (COUNT($fileServers))
            {
                foreach ($fileServers AS $fileServer)
                {
                    if (substr($fileUrl, 0, strlen($fileServer['fileServerDomainName'])) == $fileServer['fileServerDomainName'])
                    {
                        $shortUrlSection = str_replace($fileServer['fileServerDomainName'] . '/', '', $fileUrl);
                    }
                }
            }
        }

        if ($shortUrlSection == null)
        {
            return false;
        }

        // break apart to get actual short url
        $shortUrl = current(explode("/", $shortUrlSection));

        return self::loadByShortUrl($shortUrl);
    }

    /**
     * Load by delete hash
     *
     * @param string $deleteHash
     * @return file
     */
    static function loadByDeleteHash($deleteHash)
    {
        $db  = Database::getDatabase(true);
        $row = $db->getRow('SELECT * FROM file WHERE deleteHash = ' . $db->quote($deleteHash));
        if (!is_array($row))
        {
            return false;
        }

        $fileObj = new file();
        foreach ($row AS $k => $v)
        {
            $fileObj->$k = $v;
        }

        return $fileObj;
    }

    /**
     * Load by id
     *
     * @param integer $shortUrl
     * @return file
     */
    static function loadById($id)
    {
        $db  = Database::getDatabase(true);
        $row = $db->getRow('SELECT * FROM file WHERE id = ' . (int) $id);
        if (!is_array($row))
        {
            return false;
        }

        $fileObj = new file();
        foreach ($row AS $k => $v)
        {
            $fileObj->$k = $v;
        }

        return $fileObj;
    }

    /**
     * Create short url
     *
     * @param integer $in
     * @return string
     */
    static function createShortUrlPart($in)
    {
        // note: no need to check whether it already exists as it's handled by the code which calls this
        switch (SITE_CONFIG_GENERATE_UPLOAD_URL_TYPE)
        {
            case 'Medium Hash':
                return substr(MD5($in . microtime()), 0, 16);
                break;
            case 'Long Hash':
                return MD5($in . microtime());
                break;
        }

        // Shortest
        $codeset  = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $cbLength = strlen($codeset);
        $out      = '';
        while ((int) $in > $cbLength - 1)
        {
            $out = $codeset[fmod($in, $cbLength)] . $out;
            $in  = floor($in / $cbLength);
        }

        return $codeset[$in] . $out;
    }

    /**
     * Update short url for file
     *
     * @param integer $id
     * @param string $shortUrl
     */
    static function updateShortUrl($id, $shortUrl = '')
    {
        $db = Database::getDatabase(true);
        $db->query('UPDATE file SET shortUrl = :shorturl WHERE id = :id', array('shorturl' => $shortUrl, 'id'       => $id));
    }

    /**
     * Load all by account id
     *
     * @param integer $accountId
     * @return array
     */
    static function loadAllByAccount($accountId)
    {
        $db = Database::getDatabase(true);
        $rs = $db->getRows('SELECT * FROM file WHERE userId = ' . $db->quote($accountId) . ' ORDER BY originalFilename');
        if (!is_array($rs))
        {
            return false;
        }

        return $rs;
    }

    /**
     * Load all active by folder id
     *
     * @param integer $folderId
     * @return array
     */
    static function loadAllActiveByFolderId($folderId)
    {
        $db = Database::getDatabase(true);
        $rs = $db->getRows('SELECT * FROM file WHERE folderId = ' . $db->quote($folderId) . ' AND statusId = 1 ORDER BY originalFilename');
        if (!is_array($rs))
        {
            return false;
        }

        return $rs;
    }

    /**
     * Load all active by account id
     *
     * @param integer $accountId
     * @return array
     */
    static function loadAllActiveByAccount($accountId)
    {
        $db = Database::getDatabase(true);
        $rs = $db->getRows('SELECT * FROM file WHERE userId = ' . $db->quote($accountId) . ' AND statusId = 1 ORDER BY originalFilename');
        if (!is_array($rs))
        {
            return false;
        }

        return $rs;
    }

    /**
     * Load recent files based on account id
     *
     * @param integer $accountId
     * @return array
     */
    static function loadAllRecentByAccount($accountId, $activeOnly = false)
    {
        $db = Database::getDatabase(true);
        $rs = $db->getRows('SELECT * FROM file WHERE userId = ' . $db->quote($accountId) . ($activeOnly === true ? ' AND statusId=1' : '') . ' ORDER BY uploadedDate DESC LIMIT 10');
        if (!is_array($rs))
        {
            return false;
        }

        return $rs;
    }

    /**
     * Load recent files based on IP address
     *
     * @param string $ip
     * @return array
     */
    static function loadAllRecentByIp($ip, $activeOnly = false)
    {
        $db = Database::getDatabase(true);
        $rs = $db->getRows('SELECT * FROM file WHERE uploadedIP = ' . $db->quote($ip) . ($activeOnly === true ? ' AND statusId=1' : '') . ' AND userId IS NULL ORDER BY uploadedDate DESC LIMIT 10');
        if (!is_array($rs))
        {
            return false;
        }

        return $rs;
    }

    /**
     * Get status label
     *
     * @param integer $statusId
     * @return string
     */
    static function getStatusLabel($statusId)
    {
        $db  = Database::getDatabase(true);
        $row = $db->getRow('SELECT label FROM file_status WHERE id = ' . (int) $statusId);
        if (!is_array($row))
        {
            return t('unknown', 'unknown');
        }

        return t('file_status_' . str_replace(' ', '_', strtolower($row['label'])), $row['label']);
    }

    static function getUploadUrl()
    {
		// first check cache
		if(cache::cacheExists('UPLOADER_UPLOAD_URL') !== false)
		{
			return cache::getCache('UPLOADER_UPLOAD_URL');
		}
		
        // get available file server
        $db            = Database::getDatabase(true);
        $fileServerId  = fileServer::getAvailableServerId();
        $sQL           = "SELECT serverType, fileServerDomainName, scriptPath FROM file_server WHERE id = " . (int) $fileServerId . " LIMIT 1";
        $serverDetails = $db->getRow($sQL);
        if ($serverDetails)
        {
            if ($serverDetails['serverType'] == 'direct')
            {
                $url = $serverDetails['fileServerDomainName'] . $serverDetails['scriptPath'];
                if (substr($url, strlen($url) - 1, 1) == '/')
                {
                    $url = substr($url, 0, strlen($url) - 1);
                }

				$uploadUrl = _CONFIG_SITE_PROTOCOL . "://" . $url;
				
				// store in cache for later
				cache::setCache('UPLOADER_UPLOAD_URL', $uploadUrl);
				
                return $uploadUrl;
            }
        }

        return WEB_ROOT;
    }

    /*
     * Get all direct file servers
     */

    static function getDirectFileServers()
    {
        $db  = Database::getDatabase(true);
        $sQL = "SELECT id, serverType, fileServerDomainName, scriptPath FROM file_server WHERE serverType='direct' ORDER BY fileServerDomainName";

        return $db->getRows($sQL);
    }

    static function getValidReferrers($formatted = false)
    {
        $pre = '';
        if ($formatted == true)
        {
            $pre = _CONFIG_SITE_PROTOCOL . '://';
        }

        $validUrls                               = array();
        $validUrls[$pre . _CONFIG_SITE_HOST_URL] = $pre . _CONFIG_SITE_HOST_URL;
        $directFileServers                       = self::getDirectFileServers();
        if (COUNT($directFileServers))
        {
            foreach ($directFileServers AS $directFileServer)
            {
                $validUrls[$pre . $directFileServer{'fileServerDomainName'}] = $pre . $directFileServer['fileServerDomainName'];
            }
        }

        return $validUrls;
    }

    static function getFileDomainAndPath($fileId = null, $fileServerId = null, $finalDownloadBasePath = false)
    {
        // get database connection
        $db = Database::getDatabase(true);
        if ($fileServerId == null)
        {
            $fileServerId = $db->getValue('SELECT serverId FROM file WHERE id = ' . (int) $fileId . ' LIMIT 1');
        }

        if ((int) $fileServerId)
        {
            // use caching for better database performance
            if (!cache::cacheExists('FILE_SERVERS'))
            {
                $fileServers = $db->getRows('SELECT id, fileServerDomainName, scriptPath, routeViaMainSite FROM file_server');
                foreach ($fileServers AS $fileServer)
                {
                    $rs[$fileServer{'id'}] = $fileServer;
                }
                cache::setCache('FILE_SERVERS', $rs);
            }

            $fileServers = cache::getCache('FILE_SERVERS');
            $fileServer  = $fileServers[$fileServerId];
            if ($fileServer)
            {
                if (strlen($fileServer['fileServerDomainName']))
                {
                    // get path from file server
                    $path = $fileServer['fileServerDomainName'] . $fileServer['scriptPath'];

                    // if not direct download link and file server is set to route via main site, override path to this site
                    if (($finalDownloadBasePath == false) && ($fileServer['routeViaMainSite'] == 1))
                    {
                        $path = _CONFIG_CORE_SITE_FULL_URL;
                    }

                    // tidy url
                    if (substr($path, strlen($path) - 1, 1) == '/')
                    {
                        $path = substr($path, 0, strlen($path) - 1);
                    }

                    return $path;
                }
            }
        }

        return _CONFIG_CORE_SITE_FULL_URL;
    }

    static function getFileUrl($fileId, $file = null)
    {
        if (!$file)
        {
            $file = file::loadById((int) $fileId);
        }

        if (!$file)
        {
            return false;
        }

        return $file->getFullShortUrl();
    }

    static function getFileStatisticsUrl($fileId, $file = null)
    {
        if (!$file)
        {
            $file = file::loadById((int) $fileId);
        }

        if (!$file)
        {
            return false;
        }

        return $file->getStatisticsUrl();
    }

    static function getFileDeleteUrl($fileId, $file = null)
    {
        if (!$file)
        {
            $file = file::loadById((int) $fileId);
        }

        if (!$file)
        {
            return false;
        }

        return $file->getDeleteUrl();
    }

    static function getFileInfoUrl($fileId, $file = null)
    {
        if (!$file)
        {
            $file = file::loadById((int) $fileId);
        }

        if (!$file)
        {
            return false;
        }

        return $file->getInfoUrl();
    }

    static function getFileShortInfoUrl($fileId, $file = null)
    {
        if (!$file)
        {
            $file = file::loadById((int) $fileId);
        }

        if (!$file)
        {
            return false;
        }

        return $file->getShortInfoUrl();
    }

    static function getTotalActiveFilesByUser($userId)
    {
        $db = Database::getDatabase();

        return $db->getValue('SELECT COUNT(id) AS total FROM file WHERE userId = ' . (int) $userId . ' AND statusId = 1');
    }
	
	static function getTotalActiveFileStats()
	{
		if(cache::cacheExists(__METHOD__))
		{
			return cache::getCache(__METHOD__);
		}
		
		$db = Database::getDatabase();
        $rs = $db->getRow('SELECT COUNT(file.id) AS totalFileCount, SUM(visits) AS totalVisits FROM file WHERE file.statusId = 1');
		cache::setCache(__METHOD__, $rs);
		
		return cache::getCache(__METHOD__);
	}
	
	static function getTotalActivePublicFileStats()
	{
		if(cache::cacheExists(__METHOD__))
		{
			return cache::getCache(__METHOD__);
		}
		
		$db = Database::getDatabase();
        $rs = $db->getRow('SELECT COUNT(file.id) AS totalFileCount, SUM(file.fileSize) AS totalFileSize FROM file LEFT JOIN file_folder ON file.folderId = file_folder.id WHERE file.statusId = 1 AND (file_folder.isPublic = 2 OR file.userId IS NULL)');
		cache::setCache(__METHOD__, $rs);
		
		return cache::getCache(__METHOD__);
	}
	
	static function getTotalActivePublicFiles()
    {
		if(cache::cacheExists(__METHOD__))
		{
			return cache::getCache(__METHOD__);
		}
		
		$rs = self::getTotalActivePublicFileStats();
		cache::setCache(__METHOD__, $rs['totalFileCount']);
		
		return cache::getCache(__METHOD__);
    }
	
	static function getTotalActivePublicFilesize()
    {
		if(cache::cacheExists(__METHOD__))
		{
			return cache::getCache(__METHOD__);
		}
		
		$rs = self::getTotalActivePublicFileStats();
		cache::setCache(__METHOD__, $rs['totalFileSize']);
		
		return cache::getCache(__METHOD__);
    }

    static function getTotalActiveFileSizeByUser($userId)
    {
        $db = Database::getDatabase();

        return $db->getValue('SELECT SUM(fileSize) AS total FROM file WHERE userId = ' . (int) $userId . ' AND statusId = 1');
    }

    static function getTotalDownloadsByUserOwnedFiles($userId)
    {
        $db = Database::getDatabase();

        return $db->getValue('SELECT SUM(visits) AS total FROM file WHERE userId = ' . (int) $userId);
    }

    static function getIconPreviewImageUrlLarger($fileArr, $ignorePlugins = false, $css = false)
    {
        return self::getIconPreviewImageUrl($fileArr, $ignorePlugins, 160, $css, 160, 160, 'padded');
    }

    static function getIconPreviewImageUrlLarge($fileArr, $ignorePlugins = false, $css = true)
    {
        return self::getIconPreviewImageUrl($fileArr, $ignorePlugins, 48, $css);
    }

    static function getIconPreviewImageUrlMedium($fileArr, $ignorePlugins = false)
    {
        return self::getIconPreviewImageUrl($fileArr, $ignorePlugins, 24);
    }

    static function getIconPreviewImageUrlSmall($fileArr, $ignorePlugins = false)
    {
        return self::getIconPreviewImageUrl($fileArr, $ignorePlugins, 16);
    }

    static function getIconPreviewImageUrl($fileArr, $ignorePlugins = false, $size, $css = false, $width = null, $height = null, $type = 'middle')
    {
        $iconFilePath = '/file_icons/' . $size . 'px/' . $fileArr['extension'] . '.png';
        $iconUrl      = SITE_IMAGE_PATH . $iconFilePath;
        if ($css == true)
        {
            // return css class instead
            $iconUrl = 'sprite_icon_' . str_replace(array('+'), '', $fileArr['extension']);
        }
        if (!file_exists(DOC_ROOT . '/themes/' . SITE_CONFIG_SITE_THEME . '/images' . $iconFilePath))
        {
            $iconUrl = SITE_IMAGE_PATH . '/file_icons/' . $size . 'px/_page.png';
            if ($css == true)
            {
                // return css class instead
                $iconUrl = 'sprite_icon__page';
            }
        }

        // plugin previews
        if (($size > 24) && ($ignorePlugins == false))
        {
            $params  = pluginHelper::includeAppends('class_file_icon_preview_image_url.php', array('iconUrl' => $iconUrl, 'fileArr' => $fileArr, 'width'   => $width, 'height'  => $height, 'type'    => $type));
            $iconUrl = $params['iconUrl'];
        }

        return $iconUrl;
    }

    /**
     * Update used file storage stats, only allow this once every 5 minutes
     */
    static function updateFileServerStorageStats($serverId = null, $force = false)
    {
		if($force == false)
		{
			$nextCheckTimestamp = (int) SITE_CONFIG_NEXT_CHECK_FOR_SERVER_STATS_UPDATE;
			if ($nextCheckTimestamp >= time())
			{
				return false;
			}
		}
		
        $db = Database::getDatabase();

        // update stats
        if ($serverId == null)
        {
            $servers = $db->getRows('SELECT id FROM file_server');
        }
        else
        {
            $servers   = array();
            $servers[] = array('id' => $serverId);
        }

        foreach ($servers AS $server)
        {
            // server id
            $serverId = (int) $server['id'];

            // get total space used
            $totalPre  = (float) $db->getValue('SELECT SUM(file.fileSize) AS total FROM file WHERE file.statusId = 1 AND fileHash IS NULL AND file.serverId = ' . $serverId . ' GROUP BY file.serverId');
            $totalPost = (float) $db->getValue('SELECT SUM(fileSelect.fileSize) AS total FROM (SELECT fileSize, statusId, fileHash, serverId FROM file WHERE file.fileHash IS NOT NULL AND serverId = ' . $serverId . ' GROUP BY file.fileHash) AS fileSelect WHERE fileSelect.statusId = 1 AND fileSelect.fileHash IS NOT NULL AND fileSelect.serverId = ' . $serverId . ' GROUP BY fileSelect.serverId');
			$totalFiles  = (int) $db->getValue('SELECT COUNT(1) AS total FROM file WHERE file.statusId = 1 AND file.serverId = ' . $serverId);

            // update with new totals
            $db->query('UPDATE file_server SET totalSpaceUsed = ' . (float) $db->escape($totalPre + $totalPost) . ', totalFiles = ' . (int) $db->escape($totalFiles) . ' WHERE id = ' . $serverId);
        }
		
		// set next check in 5 minutes time
		$nextCheckNew = time()+(60*5);
		$db->query("UPDATE site_config SET config_value=".$db->quote($nextCheckNew)." WHERE config_key='next_check_for_server_stats_update' LIMIT 1");
    }

    static function purgeDownloadTokens()
    {
        // get database
        $db = Database::getDatabase(true);

        // delete old token data
        $db->query('DELETE FROM download_token WHERE expiry < :expiry', array('expiry' => date('Y-m-d H:i:s')));
    }

    public function showDownloadPages($pageTracker = null)
    {
        // load user
        $Auth = Auth::getAuth();

        // get database
        $db = Database::getDatabase(true);

        // get total pages
        $maxDownloadPage = (int) $db->getValue('SELECT MAX(page_order) FROM download_page WHERE user_level_id = ' . (int) $Auth->package_id);
        if (!$maxDownloadPage)
        {
            return true;
        }

        // clear any issues in the session left over from previous requests
        if (isset($_SESSION['_download_page_next_page_'.$this->id]) && ((int) $_SESSION['_download_page_next_page_'.$this->id] < 1))
        {
            unset($_SESSION['_download_page_next_page_'.$this->id]);
        }

        // check for valid $pageTracker
        if ($pageTracker !== null)
        {
            $thisPageNumber = (int) $this->decodeNextPageHash($pageTracker);
            if ($thisPageNumber != $_SESSION['_download_page_next_page_'.$this->id])
            {
                // clear any paging to require $pageTracker token
                $_SESSION['_download_page_wait_'.$this->id] = 0;
                unset($_SESSION['_download_page_next_page_'.$this->id]);
            }
        }
        else
        {
            unset($_SESSION['_download_page_next_page_'.$this->id]);
        }

        // check if the user is requesting a new file
        if (isset($_SESSION['_download_page_file_id_'.$this->id]))
        {
            if ($_SESSION['_download_page_file_id_'.$this->id] != $this->id)
            {
                $_SESSION['_download_page_file_id_'.$this->id] = $this->id;
                $_SESSION['_download_page_wait_'.$this->id] = 0;
                unset($_SESSION['_download_page_next_page_'.$this->id]);
            }
        }

        // next page to show
        if (!isset($_SESSION['_download_page_next_page_'.$this->id]))
        {
            $_SESSION['_download_page_next_page_'.$this->id] = 1;
            $_SESSION['_download_page_wait_'.$this->id]      = 0;
        }

        // make sure we can actually go to the next page, because of waiting periods
        if ($_SESSION['_download_page_wait_'.$this->id] > 0)
        {
            if ($_SESSION['_download_page_load_time_'.$this->id] >= (time() - (int) $_SESSION['_download_page_wait_'.$this->id]))
            {
                $_SESSION['_download_page_next_page_'.$this->id] = $_SESSION['_download_page_next_page_'.$this->id] - 1;
                if ($_SESSION['_download_page_next_page_'.$this->id] < 1)
                {
                    $_SESSION['_download_page_next_page_'.$this->id] = 1;
                }
            }
        }

        // log load time for this page
        $_SESSION['_download_page_load_time_'.$this->id] = time();
        $_SESSION['_download_page_file_id_'.$this->id]   = $this->id;
        $_SESSION['_download_page_wait_'.$this->id]      = 0;

        $nextOrder    = $_SESSION['_download_page_next_page_'.$this->id];
        // load download pages for user level
        $downloadPage = $db->getRow('SELECT download_page, page_order, additional_javascript_code, additional_settings FROM download_page WHERE user_level_id = ' . (int) $Auth->package_id . ' AND page_order >= ' . (int) $nextOrder . ' ORDER BY page_order ASC LIMIT 1');
        if (!$downloadPage)
        {
            // reset to beginning for next load
            $_SESSION['_download_page_next_page_'.$this->id] = 1;
            $_SESSION['_download_page_wait_'.$this->id]      = 0;
            return true;
        }

        $filePath = SITE_TEMPLATES_PATH . '/partial/' . $downloadPage['download_page'];
        if (!file_exists($filePath))
        {
            die('Error: Download page does not exist: ' . $filePath);
        }

        // load additional settings
        $additionalSettings = array();
        if (strlen($downloadPage['additional_settings']))
        {
            $additionalSettings = json_decode($downloadPage['additional_settings'], true);
        }

        // set timer wait if exists in the page config
        $_SESSION['_download_page_wait_'.$this->id] = 0;
        if (isset($additionalSettings['download_wait']))
        {
            $_SESSION['_download_page_wait_'.$this->id] = (int) $additionalSettings['download_wait'];
        }

        // reassign file object for download pages
        $file = $this;

        // include header
        require_once(SITE_TEMPLATES_PATH . '/partial/_header.inc.php');

        // download page
        include_once($filePath);

        // for page footer link
        if (!defined('REPORT_URL'))
        {
            define('REPORT_URL', $file->getFullShortUrl());
        }

        // include footer
        require_once(SITE_TEMPLATES_PATH . '/partial/_footer.inc.php');

        // increment next order
        $_SESSION['_download_page_next_page_'.$this->id]++;
        exit();
    }

    /**
     * The main function to use on the download pages to track which pages have been viewed,
     * must be used to move user onto the next download page.
     * 
     * @return string
     */
    public function getNextDownloadPageLink()
    {
        return $this->getFullShortUrl() . '?pt=' . urlencode($this->createNextPageHash());
    }

    public function createNextPageHash()
    {
        $pageNumber = (int) $_SESSION['_download_page_next_page_'.$this->id] + 1;
        if (function_exists('mcrypt_encrypt'))
        {
            $key       = $this->getFullShortUrl() . $this->getFullFilePath() . $this->deleteHash;
            $ivSize    = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
            $iv        = mcrypt_create_iv($ivSize, MCRYPT_RAND);
            $encrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, md5($key), $pageNumber, MCRYPT_MODE_CBC, $iv);
            if (strlen($encrypted) == 0)
            {
                return $pageNumber;
            }

            $encrypted = base64_encode($iv . $encrypted);

            return $encrypted;
        }

        return $pageNumber;
    }

    public function decodeNextPageHash($hash)
    {
        if (function_exists('mcrypt_decrypt'))
        {
            $key           = $this->getFullShortUrl() . $this->getFullFilePath() . $this->deleteHash;
            $ciphertextDec = base64_decode($hash);
            $ivSize        = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
            $ivDec         = substr($ciphertextDec, 0, $ivSize);
            $ciphertextDec = substr($ciphertextDec, $ivSize);
            $decrypted     = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, md5($key), $ciphertextDec, MCRYPT_MODE_CBC, $ivDec), "\0");
            if (strlen($decrypted))
            {
                return $decrypted;
            }
        }

        return $hash;
    }

    static function loadServerDetails($serverId)
    {
        // load from the db
        $db                  = Database::getDatabase(true);
        $uploadServerDetails = $db->getRow('SELECT * FROM file_server WHERE id = ' . $db->escape((int) $serverId));
        $db->close();
        if (!$uploadServerDetails)
        {
            return false;
        }

        $serverConfigArr = '';
        if (strlen($uploadServerDetails['serverConfig']))
        {
            $serverConfig = json_decode($uploadServerDetails['serverConfig'], true);
            if (is_array($serverConfig))
            {
                $serverConfigArr = $serverConfig;
            }
        }
        $uploadServerDetails['serverConfig'] = $serverConfigArr;

        return $uploadServerDetails;
    }

    static function getFileServerData()
    {
        if (($fileServers = cache::getCache('FILE_SERVER_DATA')) === false)
        {
            $db          = Database::getDatabase();
            $fileServers = $db->getRows('SELECT * FROM file_server ORDER BY serverLabel');
            cache::setCache('FILE_SERVER_DATA', $fileServers);
        }

        return $fileServers;
    }

    static function getCurrentServerDetails()
    {
        // get file server cache
        $fileServers = self::getFileServerData();

        // get current file server id
        $fileServerId = self::getCurrentServerId();
        if ($fileServerId)
        {
            foreach ($fileServers AS $fileServer)
            {
                if ((int) $fileServer['id'] == $fileServerId)
                {
                    return $fileServer;
                }
            }
        }

        return false;
    }

    // load server id based on site config
    static function getCurrentServerId()
    {
        // get file server cache
        $fileServers = self::getFileServerData();

        // get server id for direct file servers
        foreach ($fileServers AS $fileServer)
        {
            if (($fileServer['fileServerDomainName'] == _CONFIG_SITE_HOST_URL) && ($fileServer['serverType'] == 'direct'))
            {
                return (int) $fileServer['id'];
            }
        }

        // fallback to local server
        $serverId = self::getDefaultLocalServerId();
        if ((int) $serverId)
        {
            return $serverId;
        }

        return false;
    }

    static function getDefaultLocalServerId()
    {
        // get file server cache
        $fileServers = self::getFileServerData();

        foreach ($fileServers AS $fileServer)
        {
            if (($fileServer['serverLabel'] == 'Local Default') && ($fileServer['serverType'] == 'local'))
            {
                return (int) $fileServer['id'];
            }
        }
        
        // load the first local server
        foreach ($fileServers AS $fileServer)
        {
            if ($fileServer['serverType'] == 'local')
            {
                return (int) $fileServer['id'];
            }
        }

        return false;
    }

    static function deleteRedundantFiles()
    {
        // connect db
        $db = Database::getDatabase(true);
		$limit = 1000;

        // setup server ids, we need this to be an array to allow for multiple drives on the same server
		$server = self::getCurrentServerDetails();
		$serverIds = array();
		if($server['serverType'] == 'local')
		{
			// load other servers
			$servers = $db->getRows('SELECT id FROM file_server WHERE serverType != \'direct\'');
			foreach($servers AS $serverItem)
			{
				$serverIds[] = (int)$serverItem['id'];
			}
		}
		else
		{
			$serverIds[] =(int)$server['id'];
		}

		// get all account types
		$accountTypes = $db->getRows('SELECT id, level_type FROM user_level ORDER BY id ASC');
		foreach($accountTypes AS $accountType)
		{
			// get after how long to remove
			$fileRemovalPeriod  = (int)trim(UserPeer::getDaysToKeepInactiveFiles($accountType['id']));
			
			// set a maximum of 5 years otherwise we hit unix timestamp calculation issues
			if ($fileRemovalPeriod > 1825)
			{
				$fileRemovalPeriod = 1825;
			}
			
			// block zero
			if($fileRemovalPeriod == 0)
			{
				continue;
			}
			
			// create sql to remove find files for account type
            $sQL = 'SELECT file.id ';
            $sQL .= 'FROM file LEFT JOIN users ';
            $sQL .= 'ON file.userId = users.id ';
            $sQL .= 'WHERE file.statusId = 1 AND ';
            $sQL .= 'UNIX_TIMESTAMP(file.uploadedDate) < ' . strtotime('-' . $fileRemovalPeriod . ' days') . ' AND ';
            $sQL .= '(UNIX_TIMESTAMP(file.lastAccessed) < ' . strtotime('-' . $fileRemovalPeriod . ' days') . ' OR file.lastAccessed IS NULL) ';
            $sQL .= 'AND (file.userId ';
			
			// non-accounts
			if($accountType['level_type'] == 'nonuser')
			{
				$sQL .= 'IS NULL';
			}
			// accounts
			else
			{
				$sQL .= 'IN (SELECT id FROM users WHERE level_id = '.(int)$accountType['id'].')';
			}
			
			$sQL .= ') AND file.serverId IN (' . implode(',', $serverIds) . ') LIMIT '.$limit.';';

            $rows = $db->getRows($sQL);
            if (is_array($rows))
            {
                foreach ($rows AS $row)
                {
                    // load file object
                    $file = file::loadById($row['id']);
                    if ($file)
                    {
                        // remove file
                        $file->removeBySystem();
                    }
                }
            }
		}
    }

    // returns a file's mimetype based on its extension
    static function estimateMimeTypeFromExtension($filename, $default = 'application/octet-stream')
    {
        $mimeTypes = array(
            '323'     => 'text/h323',
            'acx'     => 'application/internet-property-stream',
            'ai'      => 'application/postscript',
            'aif'     => 'audio/x-aiff',
            'aifc'    => 'audio/x-aiff',
            'aiff'    => 'audio/x-aiff',
            'asf'     => 'video/x-ms-asf',
            'asr'     => 'video/x-ms-asf',
            'asx'     => 'video/x-ms-asf',
            'au'      => 'audio/basic',
            'avi'     => 'video/x-msvideo',
            'axs'     => 'application/olescript',
            'bas'     => 'text/plain',
            'bcpio'   => 'application/x-bcpio',
            'bin'     => 'application/octet-stream',
            'bmp'     => 'image/bmp',
            'c'       => 'text/plain',
            'cat'     => 'application/vnd.ms-pkiseccat',
            'cdf'     => 'application/x-cdf',
            'cer'     => 'application/x-x509-ca-cert',
            'class'   => 'application/octet-stream',
            'clp'     => 'application/x-msclip',
            'cmx'     => 'image/x-cmx',
            'cod'     => 'image/cis-cod',
            'cpio'    => 'application/x-cpio',
            'crd'     => 'application/x-mscardfile',
            'crl'     => 'application/pkix-crl',
            'crt'     => 'application/x-x509-ca-cert',
            'csh'     => 'application/x-csh',
            'css'     => 'text/css',
            'dcr'     => 'application/x-director',
            'der'     => 'application/x-x509-ca-cert',
            'dir'     => 'application/x-director',
            'dll'     => 'application/x-msdownload',
            'dms'     => 'application/octet-stream',
            'doc'     => 'application/msword',
            'dot'     => 'application/msword',
            'dvi'     => 'application/x-dvi',
            'dxr'     => 'application/x-director',
            'eps'     => 'application/postscript',
            'etx'     => 'text/x-setext',
            'evy'     => 'application/envoy',
            'exe'     => 'application/octet-stream',
            'fif'     => 'application/fractals',
            'flac'    => 'audio/flac',
            'flr'     => 'x-world/x-vrml',
            'gif'     => 'image/gif',
            'gtar'    => 'application/x-gtar',
            'gz'      => 'application/x-gzip',
            'h'       => 'text/plain',
            'hdf'     => 'application/x-hdf',
            'hlp'     => 'application/winhlp',
            'hqx'     => 'application/mac-binhex40',
            'hta'     => 'application/hta',
            'htc'     => 'text/x-component',
            'htm'     => 'text/html',
            'html'    => 'text/html',
            'htt'     => 'text/webviewhtml',
            'ico'     => 'image/x-icon',
            'ief'     => 'image/ief',
            'iii'     => 'application/x-iphone',
            'ins'     => 'application/x-internet-signup',
            'isp'     => 'application/x-internet-signup',
            'jfif'    => 'image/pipeg',
            'jpe'     => 'image/jpeg',
            'jpeg'    => 'image/jpeg',
            'jpg'     => 'image/jpeg',
            'js'      => 'application/x-javascript',
            'latex'   => 'application/x-latex',
            'lha'     => 'application/octet-stream',
            'lsf'     => 'video/x-la-asf',
            'lsx'     => 'video/x-la-asf',
            'lzh'     => 'application/octet-stream',
            'm13'     => 'application/x-msmediaview',
            'm14'     => 'application/x-msmediaview',
            'm3u'     => 'audio/x-mpegurl',
            'm4v'     => 'video/mp4',
            'man'     => 'application/x-troff-man',
            'mdb'     => 'application/x-msaccess',
            'me'      => 'application/x-troff-me',
            'mht'     => 'message/rfc822',
            'mhtml'   => 'message/rfc822',
            'mid'     => 'audio/mid',
            'mny'     => 'application/x-msmoney',
            'mov'     => 'video/quicktime',
            'movie'   => 'video/x-sgi-movie',
            'mp2'     => 'video/mpeg',
            'mp3'     => 'audio/mpeg',
            'mp4'     => 'video/mp4',
            'mpa'     => 'video/mpeg',
            'mpe'     => 'video/mpeg',
            'mpeg'    => 'video/mpeg',
            'mpg'     => 'video/mpeg',
            'mpp'     => 'application/vnd.ms-project',
            'mpv2'    => 'video/mpeg',
            'ms'      => 'application/x-troff-ms',
            'mvb'     => 'application/x-msmediaview',
            'nws'     => 'message/rfc822',
            'oda'     => 'application/oda',
            'oga'     => 'audio/ogg',
            'ogg'     => 'audio/ogg',
            'ogv'     => 'video/ogg',
            'ogx'     => 'application/ogg',
            'p10'     => 'application/pkcs10',
            'p12'     => 'application/x-pkcs12',
            'p7b'     => 'application/x-pkcs7-certificates',
            'p7c'     => 'application/x-pkcs7-mime',
            'p7m'     => 'application/x-pkcs7-mime',
            'p7r'     => 'application/x-pkcs7-certreqresp',
            'p7s'     => 'application/x-pkcs7-signature',
            'pbm'     => 'image/x-portable-bitmap',
            'pdf'     => 'application/pdf',
            'pfx'     => 'application/x-pkcs12',
            'pgm'     => 'image/x-portable-graymap',
            'pko'     => 'application/ynd.ms-pkipko',
            'pma'     => 'application/x-perfmon',
            'pmc'     => 'application/x-perfmon',
            'pml'     => 'application/x-perfmon',
            'pmr'     => 'application/x-perfmon',
            'pmw'     => 'application/x-perfmon',
            'pnm'     => 'image/x-portable-anymap',
            'pot'     => 'application/vnd.ms-powerpoint',
            'ppm'     => 'image/x-portable-pixmap',
            'pps'     => 'application/vnd.ms-powerpoint',
            'ppt'     => 'application/vnd.ms-powerpoint',
            'prf'     => 'application/pics-rules',
            'ps'      => 'application/postscript',
            'pub'     => 'application/x-mspublisher',
            'qt'      => 'video/quicktime',
            'ra'      => 'audio/x-pn-realaudio',
            'ram'     => 'audio/x-pn-realaudio',
            'ras'     => 'image/x-cmu-raster',
            'rgb'     => 'image/x-rgb',
            'rmi'     => 'audio/mid',
            'roff'    => 'application/x-troff',
            'rtf'     => 'application/rtf',
            'rtx'     => 'text/richtext',
            'scd'     => 'application/x-msschedule',
            'sct'     => 'text/scriptlet',
            'setpay'  => 'application/set-payment-initiation',
            'setreg'  => 'application/set-registration-initiation',
            'sh'      => 'application/x-sh',
            'shar'    => 'application/x-shar',
            'sit'     => 'application/x-stuffit',
            'snd'     => 'audio/basic',
            'spc'     => 'application/x-pkcs7-certificates',
            'spl'     => 'application/futuresplash',
            'src'     => 'application/x-wais-source',
            'sst'     => 'application/vnd.ms-pkicertstore',
            'stl'     => 'application/vnd.ms-pkistl',
            'stm'     => 'text/html',
            'svg'     => "image/svg+xml",
            'sv4cpio' => 'application/x-sv4cpio',
            'sv4crc'  => 'application/x-sv4crc',
            't'       => 'application/x-troff',
            'tar'     => 'application/x-tar',
            'tcl'     => 'application/x-tcl',
            'tex'     => 'application/x-tex',
            'texi'    => 'application/x-texinfo',
            'texinfo' => 'application/x-texinfo',
            'tgz'     => 'application/x-compressed',
            'tif'     => 'image/tiff',
            'tiff'    => 'image/tiff',
            'tr'      => 'application/x-troff',
            'trm'     => 'application/x-msterminal',
            'tsv'     => 'text/tab-separated-values',
            'txt'     => 'text/plain',
            'uls'     => 'text/iuls',
            'ustar'   => 'application/x-ustar',
            'vcf'     => 'text/x-vcard',
            'vrml'    => 'x-world/x-vrml',
            'wav'     => 'audio/x-wav',
            'wcm'     => 'application/vnd.ms-works',
            'wdb'     => 'application/vnd.ms-works',
            'wks'     => 'application/vnd.ms-works',
            'wmf'     => 'application/x-msmetafile',
            'wps'     => 'application/vnd.ms-works',
            'wri'     => 'application/x-mswrite',
            'wrl'     => 'x-world/x-vrml',
            'wrz'     => 'x-world/x-vrml',
            'xaf'     => 'x-world/x-vrml',
            'xbm'     => 'image/x-xbitmap',
            'xla'     => 'application/vnd.ms-excel',
            'xlc'     => 'application/vnd.ms-excel',
            'xlm'     => 'application/vnd.ms-excel',
            'xls'     => 'application/vnd.ms-excel',
            'xlt'     => 'application/vnd.ms-excel',
            'xlw'     => 'application/vnd.ms-excel',
            'xof'     => 'x-world/x-vrml',
            'xpm'     => 'image/x-xpixmap',
            'xwd'     => 'image/x-xwindowdump',
            'z'       => 'application/x-compress',
            'zip'     => 'application/zip',
            'xlsx'    => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xltx'    => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
            'potx'    => 'application/vnd.openxmlformats-officedocument.presentationml.template',
            'ppsx'    => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
            'pptx'    => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'sldx'    => 'application/vnd.openxmlformats-officedocument.presentationml.slide',
            'docx'    => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'dotx'    => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
            'xlam'    => 'application/vnd.ms-excel.addin.macroEnabled.12',
            'xlsb'    => 'application/vnd.ms-excel.sheet.binary.macroEnabled.12');
        $ext       = pathinfo($filename, PATHINFO_EXTENSION);

        return isset($mimeTypes[$ext]) ? $mimeTypes[$ext] : $default;
    }

    public function setFileContent($content = '')
    {
        if(strlen($content) == 0)
        {
            return false;
        }
        
        // connect db
        $db = Database::getDatabase();
        
        // load the server the file is on
        $storageLocation     = _CONFIG_FILE_STORAGE_PATH;
        $storageType         = 'local';
        $uploadServerDetails = $this->loadServer();
        if ($uploadServerDetails != false)
        {
            $storageLocation = $uploadServerDetails['storagePath'];
            $storageType     = $uploadServerDetails['serverType'];

            // if no storage path set & local, use system default
            if ((strlen($storageLocation) == 0) && ($storageType == 'local'))
            {
                $storageLocation = _CONFIG_FILE_STORAGE_PATH;
            }
        }
        
        // get file path
        $fullPath = $this->getFullFilePath($storageLocation);

        // save
        if(($storageType == 'local') || ($storageType == 'direct'))
        {
            if(file_exists($fullPath))
            {
                $rs = file_put_contents($fullPath, $content);
                if ($rs)
                {
                    // update db
                    $rs = $db->query('UPDATE file SET fileHash=:fileHash, fileSize=:fileSize WHERE id = :id', array('id' => $this->id, 'fileHash' => md5_file($fullPath), 'fileSize' => filesize($fullPath)));
                    
                    return true;
                }
            }
        }
        // upload via FTP
        elseif($storageType == 'ftp')
        {
            $error = '';

            // connect ftp
            $conn_id = ftp_connect($uploadServerDetails['ipAddress'], $uploadServerDetails['ftpPort'], 30);
            if ($conn_id === false)
            {
                $error = t('classfile_could_not_connect_file_server', 'Could not connect to file server [[[IP_ADDRESS]]]', array('IP_ADDRESS' => $uploadServerDetails['ipAddress']));
            }

            // authenticate
            if (!$error)
            {
                $login_result = ftp_login($conn_id, $uploadServerDetails['ftpUsername'], $uploadServerDetails['ftpPassword']);
                if ($login_result === false)
                {
                    $error = t('classfile_could_not_authenticate_with_file_server', 'Could not authenticate with file server [[[IP_ADDRESS]]]', array('IP_ADDRESS' => $uploadServerDetails['ipAddress']));
                }
            }

            // upload via ftp
            if (!$error)
            {
                clearstatcache();
                
                // temp save image in cache for exif function
        		$imageFilename = 'plugins/imageviewer/_tmp/' . md5(microtime() . $this->id) . '.' . $this->extension;
        		$tmpFile     = cache::saveCacheToFile($imageFilename, $content);
                if ($tmpFile)
                {
                    // remove old file
                    ftp_delete($conn_id, $fullPath);
                    
                    // initiate ftp upload
                    $ret = ftp_nb_put($conn_id, $fullPath, $tmpFile, FTP_BINARY, FTP_AUTORESUME);
                    while ($ret == FTP_MOREDATA)
                    {
                        // continue uploading
                        $ret = ftp_nb_continue($conn_id);
                    }

                    if ($ret != FTP_FINISHED)
                    {
                        $error = t('classfile_there_was_problem_uploading_file', 'There was a problem uploading the file to [[[IP_ADDRESS]]]', array('IP_ADDRESS' => $uploadServerDetails['ipAddress']));
                    }
                    
                    $fileSize = filesize($tmpFile);
                    
                    // clear cached file
                    cache::removeCacheFile($imageFilename);
                }
                
                // log errors
                if(strlen($error))
                {
                    log::error($error);
                }
                else
                {
                    // update db
                    if($fileSize > 0)
                    {
                        $rs = $db->query('UPDATE file SET fileHash=:fileHash, fileSize=:fileSize WHERE id = :id', array('id' => $this->id, 'fileHash' => md5_file($fullPath), 'fileSize' => $fileSize));
                    }
                }
            }

            // close ftp connection
            ftp_close($conn_id);
        }
        
        // @TODO - S3
        
        return false;
    }
    
    static function getKeywordArrFromString($str = '')
    {
        $str = strtolower($str);
        
        // remove invalid characters
        $str = str_replace(array('_', '-', '.', ',', '(', ')'), ' ', $str);
        
        
        // remove double spaces
        $str = preg_replace('/\s+/', ' ', $str);
        
        // split apart
        $keywords = explode(' ', $str);
        
        return $keywords;
    }
	
	static function getImageExtStringForSql()
	{
		return '\''.implode('\',\'', self::getImageExtensionsArr()).'\'';
	}
	
	static function createUniqueFileHash($fileId)
    {
        // load from the db
		$db = Database::getDatabase();
		
		// create new hash
		$uniqueHash = self::createUniqueFileHashString();
		
		// update file data
		$db->query('UPDATE file SET unique_hash = '.$db->quote($uniqueHash).' WHERE id = '.(int)$fileId.' LIMIT 1');

        return $uniqueHash;
    }
	
	static function createUniqueFileHashString()
	{
		// load from the db
		$db = Database::getDatabase();
		
		$uniqueHashFound = true;
		while($uniqueHashFound == true)
		{
			$uniqueHash = md5(microtime().rand(0, 99999)).md5(microtime().rand(0, 99999));;
			$uniqueHashFound = $db->getValue('SELECT id FROM file WHERE unique_hash = '.$db->quote($uniqueHash).' LIMIT 1');
		}
		
		return $uniqueHash;
	}
	
	static public function getImageExtensionsArr()
	{
		// load from image viewer plugin if enabled
		if(pluginHelper::pluginEnabled('imageviewer'))
		{
			// load plugin details
			$pluginObj      = pluginHelper::getInstance('imageviewer');
			$pluginDetails  = pluginHelper::pluginSpecificConfiguration('imageviewer');
			$pluginSettings = json_decode($pluginDetails['data']['plugin_settings'], true);
			
			// look for supported image types
			if((isset($pluginSettings['supported_image_types'])) && (strlen($pluginSettings['supported_image_types'])))
			{
				return explode('|', strtolower($pluginSettings['supported_image_types']));
			}
		}
		
		// fallback
		return explode('|', self::IMAGE_EXTENSIONS);
	}
	
	static public function checkFileHashBlocked($fileHash)
	{
		// load from the db
		$db = Database::getDatabase();
		
		// look for the file block
		return (bool)$db->getValue('SELECT id FROM file_block_hash WHERE file_hash = '.$db->quote($fileHash).' LIMIT 1');
	}
	
	static function downloadingDisabled()
	{
		// check for admin user
		$Auth = Auth::getAuth();
        if ($Auth->loggedIn())
        {
            if($Auth->level_id == 20)
			{
				return false;
			}
        }

		if(defined('SITE_CONFIG_DOWNLOADS_BLOCK_ALL') && (SITE_CONFIG_DOWNLOADS_BLOCK_ALL == 'yes'))
		{
			return true;
		}
		
		return false;
	}
}
