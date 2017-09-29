<?php

/*
 * Title: Process Background Remote Url Downloads
 * Author: YetiShare.com
 * Period: Run every minute
 * 
 * Description:
 * Script to process any pending remote file downloads.
 *
 * How To Call:
 * On the command line via PHP, like this:
 * php process_remote_file_downloads.cron.php
 * 
 * Configure as a cron like this:
 * * * * * * php /path/to/yetishare/install/admin/tasks/process_remote_file_downloads.cron.php
 */

// setup environment
define('CLI_MODE', true);
define('ADMIN_IGNORE_LOGIN', true);
define('LOCAL_ADMIN_PATH', dirname(dirname(__FILE__)));

// includes and security
include_once(LOCAL_ADMIN_PATH.'/_local_auth.inc.php');

// background task logging
$task = new backgroundTask();
$task->start();

// limit concurrent downloads
define('CONCURRENT_DOWNLOADS', 30);

// check concurrent downloads
$totalDownloads = (int)$db->getValue('SELECT COUNT(id) FROM remote_url_download_queue WHERE job_status=\'downloading\'');
if($totalDownloads <= CONCURRENT_DOWNLOADS)
{
	processDownloads($db);
}

function processDownloads($db)
{
	// sync all data
	$urlDownloadData = $db->getRow('SELECT * FROM remote_url_download_queue WHERE job_status=\'pending\' AND file_server_id = '.(int)file::getCurrentServerId().' ORDER BY `created` ASC LIMIT 1');
	if(!$urlDownloadData)
	{
		return false;
	}

	// set pending
	$db->query('UPDATE remote_url_download_queue SET job_status=\'processing\', started=NOW() WHERE id='.(int)$urlDownloadData['id'].' LIMIT 1');

	// update any completed downloading
	$db->query('UPDATE remote_url_download_queue SET job_status=\'downloading\' WHERE id='.(int)$urlDownloadData['id'].' LIMIT 1');
	
	// get user max filesize
	$userObj = UserPeer::loadUserById((int)$urlDownloadData['user_id']);
	
	// start uploader class
	$upload_handler  = new uploader(array(
		'folder_id'=>(int)$urlDownloadData['folder_id'],
		'user_id'=>(int)$urlDownloadData['user_id'],
		'background_queue_id'=>(int)$urlDownloadData['id'],
		'max_file_size'=>UserPeer::getMaxUploadFilesize($userObj->level_id),
		'upload_source'=>'remote',
	));
	
	// include plugin code
	$url = $urlDownloadData['url'];
	$params = pluginHelper::includeAppends('url_upload_handler.php', array('url'   => $url, 'rowId' => 0, 'urlDownloadData' => $urlDownloadData));
	$url    = $params['url'];

	// start download
	$upload_handler->handleRemoteUrlUpload($url);
	
	// on complete
	if($upload_handler->fileUpload->error != null)
	{
		$db->query('UPDATE remote_url_download_queue SET job_status=\'failed\', finished=NOW(), notes='.$db->quote($upload_handler->fileUpload->error).' WHERE id='.(int)$urlDownloadData['id'].' LIMIT 1');
	}
	else
	{
		$db->query('UPDATE remote_url_download_queue SET job_status=\'complete\', finished=NOW(), new_file_id='.(int)$upload_handler->fileUpload->file_id.' WHERE id='.(int)$urlDownloadData['id'].' LIMIT 1');
	}
}

// background task logging
$task->end();