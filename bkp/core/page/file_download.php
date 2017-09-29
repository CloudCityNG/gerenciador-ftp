<?php

// used later
define('_INT_DOWNLOAD_REQ', true);

// make sure uploading hasn't been disabled
if(file::downloadingDisabled() == true)
{
	$errorMsg = t("downloading_all_blocked", "Downloading is currently disabled on the site, please try again later.");
    coreFunctions::redirect(coreFunctions::getCoreSitePath() . "/error." . SITE_CONFIG_PAGE_EXTENSION . "?e=" . urlencode($errorMsg));
}

// try to load the file object
$file = null;
if (isset($_REQUEST['_page_url']))
{
	// sanitise the url for compatibility with migrated scripts
	if(substr($_REQUEST['_page_url'], 0, 6) == 'image/')
	{
		$_REQUEST['_page_url'] = str_replace('image/', '', $_REQUEST['_page_url']);
	}
	if(substr($_REQUEST['_page_url'], strlen($_REQUEST['_page_url'])-5) == '.html')
	{
		$_REQUEST['_page_url'] = str_replace('.html', '', $_REQUEST['_page_url']);
	}
	$pageUrl = trim($_REQUEST['_page_url']);
	
    // only keep the initial part if there's a forward slash
    $shortUrl = current(explode("/", $pageUrl));

	// allow for migrated sites
    if(substr($shortUrl, strlen($shortUrl)-4, 4) == '.htm')
	{
		$shortUrl = substr($shortUrl, 0, strlen($shortUrl)-4);
	}
	elseif(substr($shortUrl, strlen($shortUrl)-5, 5) == '.html')
	{
		$shortUrl = substr($shortUrl, 0, strlen($shortUrl)-5);
	}

	// load the file
    $file     = file::loadByShortUrl($shortUrl);
}

// could not load the file
if (!$file)
{
    coreFunctions::output404();
    //coreFunctions::redirect(coreFunctions::getCoreSitePath() . "/index." . SITE_CONFIG_PAGE_EXTENSION);
}

// do we have a download token?
$downloadToken = null;
if(isset($_REQUEST[file::DOWNLOAD_TOKEN_VAR]))
{
    $downloadToken = $_REQUEST[file::DOWNLOAD_TOKEN_VAR];
}

// check for download managers on original download url, ignore for token urls
if(($downloadToken === null) && (Stats::isDownloadManager($_SERVER['HTTP_USER_AGENT']) == true))
{
    // authenticate
    if (!isset($_SERVER['PHP_AUTH_USER']))
    {
        header('WWW-Authenticate: Basic realm="Please enter a valid username and password"');
        header('HTTP/1.0 401 Unauthorized');
        header('status: 401 Unauthorized');
        exit;
    }

    // attempt login
    $loggedIn = $Auth->attemptLogin(trim($_SERVER['PHP_AUTH_USER']), trim($_SERVER['PHP_AUTH_PW']), false);
    if ($loggedIn === false)
    {
        header('WWW-Authenticate: Basic realm="Please enter a valid username and password"');
        header('HTTP/1.0 401 Unauthorized');
        header('status: 401 Unauthorized');
        exit;
    }
    
    // check account doesn't have to wait for downloads, i.e. is allowed to download directly
    // paid only for now
    if($Auth->level_id >= 2)
    {
        // create token so file is downloaded below
        $downloadToken = $file->generateDirectDownloadToken();
    }
}

// download file
if($downloadToken !== null)
{
    $rs = $file->download(true, true, $downloadToken);
    if (!$rs)
    {
        $errorMsg = t("error_can_not_locate_file", "File can not be located, please try again later.");
        if ($file->errorMsg != null)
        {
            $errorMsg = t("file_download_error", "Error").': ' . $file->errorMsg;
        }
        coreFunctions::redirect(coreFunctions::getCoreSitePath() . "/error." . SITE_CONFIG_PAGE_EXTENSION . "?e=" . urlencode($errorMsg));
    }
}

/* setup page */
define("PAGE_NAME", $file->originalFilename);
define("PAGE_DESCRIPTION", t("file_download_description", "Download file"));
define("PAGE_KEYWORDS", t("file_download_keywords", "download, file, upload, mp3, avi, zip"));
define("TITLE_DESCRIPTION_LEFT", t("file_download_title_page_description_left", ""));
define("TITLE_DESCRIPTION_RIGHT", t("file_download_title_page_description_right", ""));

// clear any expired download trackers
downloadTracker::clearTimedOutDownloads();
downloadTracker::purgeDownloadData();

// has the file been removed
if ($file->statusId == 2)
{
    $errorMsg = t("error_file_has_been_removed_by_user", "File has been removed.");
    coreFunctions::redirect(coreFunctions::getCoreSitePath() . "/error." . SITE_CONFIG_PAGE_EXTENSION . "?e=" . urlencode($errorMsg));
}
elseif ($file->statusId == 3)
{
    $errorMsg = t("error_file_has_been_removed_by_admin", "File has been removed by the site administrator.");
    coreFunctions::redirect(coreFunctions::getCoreSitePath() . "/error." . SITE_CONFIG_PAGE_EXTENSION . "?e=" . urlencode($errorMsg));
}
elseif ($file->statusId == 4)
{
    $errorMsg = t("error_file_has_been_removed_due_to_copyright", "File has been removed due to copyright issues.");
    coreFunctions::redirect(coreFunctions::getCoreSitePath() . "/error." . SITE_CONFIG_PAGE_EXTENSION . "?e=" . urlencode($errorMsg));
}
elseif ($file->statusId == 5)
{
    $errorMsg = t("error_file_has_expired", "File has been removed due to inactivity.");
    coreFunctions::redirect(coreFunctions::getCoreSitePath() . "/error." . SITE_CONFIG_PAGE_EXTENSION . "?e=" . urlencode($errorMsg));
}

// initial variables
$skipCountdown = false;

// include any plugin includes
$params = pluginHelper::includeAppends('file_download_top.php', array('skipCountdown'=>$skipCountdown, 'file'=>$file));
$skipCountdown = $params['skipCountdown'];

// if the user is not logged in but we have http username/password. (for download managers)
if ($Auth->loggedIn() === false)
{
    if ((isset($_SERVER['PHP_AUTH_USER'])) && (isset($_SERVER['PHP_AUTH_PW'])))
    {
        $Auth->attemptLogin(trim($_SERVER['PHP_AUTH_USER']), MD5(trim($_SERVER['PHP_AUTH_PW'])), false);
        if ($Auth->loggedIn() === false)
        {
            header('WWW-Authenticate: Basic realm="Please enter a valid username and password"');
            header('HTTP/1.0 401 Unauthorized');
            header('status: 401 Unauthorized');
            exit;
        }
        else
        {
            // assume download manager
            $skipCountdown = true;
        }
    }
}

// whether to allow downloads or not if the user is not logged in
if ((!$Auth->loggedIn()) && (SITE_CONFIG_REQUIRE_USER_ACCOUNT_DOWNLOAD == 'yes'))
{
	coreFunctions::redirect(coreFunctions::getCoreSitePath() . "/register." . SITE_CONFIG_PAGE_EXTENSION. '?f=' . urlencode($file->shortUrl));
}

// check file permissions, allow owners, non user uploads and admin/mods
if($file->userId != null)
{
	if((($file->userId != $Auth->id) && ($Auth->level_id < 10)))
	{
		// if this is a private file
		if(coreFunctions::getOverallPublicStatus($file->userId, $file->folderId, $file->id) == false)
		{
			$errorMsg = t("error_file_is_not_publicly_shared", "File is not publicly available.");
			coreFunctions::redirect(coreFunctions::getCoreSitePath() . "/error." . SITE_CONFIG_PAGE_EXTENSION . "?e=" . urlencode($errorMsg));
		}
	}
}

// if we need to request the password
if (strlen($file->accessPassword) && (($Auth->id != $file->userId) || ($Auth->id == '')))
{
    if (!isset($_SESSION['allowAccess' . $file->id]))
    {
        $_SESSION['allowAccess' . $file->id] = false;
    }

    // make sure they've not already set it
    if ($_SESSION['allowAccess' . $file->id] === false)
    {
        coreFunctions::redirect(coreFunctions::getCoreSitePath() . "/file_password." . SITE_CONFIG_PAGE_EXTENSION . '?file=' . urlencode($file->shortUrl));
    }
}

// if the file is limited to a specific user type, check that they are permitted to see it
if($file->minUserLevel != NULL)
{
    // check that the user has the correct file level
    if((int)$Auth->level_id < (int)$file->minUserLevel)
    {
        if(($file->userId != NULL) && ($Auth->user_id == $file->userId))
        {
            // ignore the restriction if this is the original user which uploaded the file
        }
        else
        {
            $userTypeLabel = $db->getValue('SELECT label FROM user_level WHERE level_id = '.(int)$file->minUserLevel.' LIMIT 1');
            $errorMsg = t("error_you_must_be_a_x_user_to_download_this_file", "You must be a [[[USER_TYPE]]] to download this file.", array('USER_TYPE' => $userTypeLabel));
            coreFunctions::redirect(coreFunctions::getCoreSitePath() . "/error." . SITE_CONFIG_PAGE_EXTENSION . "?e=" . urlencode($errorMsg));
        }
    }
}

// free or non logged in users
if ($Auth->level_id <= 1)
{
    // make sure the user is permitted to download files of this size
    if ((int) UserPeer::getMaxDownloadSize() > 0)
    {
        if ((int) UserPeer::getMaxDownloadSize() < $file->fileSize)
        {
            $errorMsg = t("error_you_must_register_for_a_premium_account_for_filesize", "You must register for a premium account to download files of this size. Please use the links above to register or login.");
            coreFunctions::redirect(coreFunctions::getCoreSitePath() . "/error." . SITE_CONFIG_PAGE_EXTENSION . "?e=" . urlencode($errorMsg));
        }
    }

    $maxThreads = SITE_CONFIG_NON_USER_MAX_DOWNLOAD_THREADS;
    if($Auth->level_id == 1)
    {
        $maxThreads = SITE_CONFIG_FREE_USER_MAX_DOWNLOAD_THREADS;
    }
    // check if the user has reached the max permitted concurrent downloads
    if ((int) $maxThreads > 0)
    {
        // allow for the extra calls on an iphone
        if(($maxThreads == 1) && (Stats::currentDeviceIsIos()))
        {
            $maxThreads = 2;
        }
        
        $sQL          = "SELECT COUNT(download_tracker.id) AS total_threads ";
        $sQL .= "FROM download_tracker ";
        $sQL .= "WHERE download_tracker.status='downloading' AND download_tracker.ip_address = " . $db->quote(coreFunctions::getUsersIPAddress()) . " ";
        $sQL .= "GROUP BY download_tracker.ip_address ";
        $totalThreads = (int) $db->getValue($sQL);
        if ($totalThreads >= (int) $maxThreads)
        {
            $errorMsg = t("error_you_have_reached_the_max_permitted_downloads", "You have reached the maximum concurrent downloads. Please wait for your existing downloads to complete or register for a premium account above.");
            coreFunctions::redirect(coreFunctions::getCoreSitePath() . "/error." . SITE_CONFIG_PAGE_EXTENSION . "?e=" . urlencode($errorMsg));
        }
    }

    // make sure the user is permitted to download
    if ((int) UserPeer::getWaitTimeBetweenDownloads() > 0)
    {
        $sQL            = "SELECT (UNIX_TIMESTAMP()-UNIX_TIMESTAMP(date_updated)) AS seconds ";
        $sQL .= "FROM download_tracker ";
        $sQL .= "WHERE download_tracker.status='finished' AND download_tracker.ip_address = " . $db->quote(coreFunctions::getUsersIPAddress()) . " ";
        $sQL .= "ORDER BY download_tracker.date_updated DESC ";
        $longAgoSeconds = (int) $db->getValue($sQL);
        if (($longAgoSeconds > 0) && ($longAgoSeconds < (int) UserPeer::getWaitTimeBetweenDownloads()))
        {
            $errorMsg = t("error_you_must_wait_between_downloads", "You must wait [[[WAITING_TIME_LABEL]]] between downloads. Please try again later or register for a premium account above to remove the restriction.", array('WAITING_TIME_LABEL' => coreFunctions::secsToHumanReadable(UserPeer::getWaitTimeBetweenDownloads())));
            coreFunctions::redirect(coreFunctions::getCoreSitePath() . "/error." . SITE_CONFIG_PAGE_EXTENSION . "?e=" . urlencode($errorMsg));
        }
    }
}

// make sure the user is permitted to download files of this size
if ((int) UserPeer::getMaxDailyDownloads() > 0)
{
	// get total user downloads today
	$sQL            = "SELECT COUNT(id) AS total ";
	$sQL .= "FROM download_tracker ";
	$sQL .= "WHERE download_tracker.status='finished' AND download_tracker.ip_address = " . $db->quote(coreFunctions::getUsersIPAddress()) . " ";
	$sQL .= "AND UNIX_TIMESTAMP(date_updated) >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 1 day))";
	$totalDownloads24Hour = (int) $db->getValue($sQL);
	if ((int) UserPeer::getMaxDailyDownloads() < $totalDownloads24Hour)
	{
		$errorMsg = t("error_you_have_reached_the_maximum_permitted_downloads_in_the_last_24_hours", "You have reached the maximum permitted downloads in the last 24 hours.");
		coreFunctions::redirect(coreFunctions::getCoreSitePath() . "/error." . SITE_CONFIG_PAGE_EXTENSION . "?e=" . urlencode($errorMsg));
	}
}

// if user owns this file, skip download pages
if(((int)$file->userId > 0) && ($file->userId === $Auth->id))
{
    $skipCountdown = true;
}

// show the download pages
if($skipCountdown == false)
{
    $file->showDownloadPages(isset($_REQUEST['pt'])?$_REQUEST['pt']:null);
}

// do we need to display the captcha?
if (UserPeer::showDownloadCaptcha() == true)
{
    if(isset($_REQUEST['pt']))
    {
        $_SESSION['_download_page_next_page_'.$file->id] = $file->decodeNextPageHash($_REQUEST['pt']);
    }
    
    /* do we require captcha validation? */
    $showCaptcha = false;
    if (!isset($_REQUEST['g-recaptcha-response']))
    {
        $showCaptcha = true;
    }

    /* check captcha */
    if (isset($_REQUEST['g-recaptcha-response']))
    {
        $rs = coreFunctions::captchaCheck($_POST["g-recaptcha-response"]);
        if (!$rs)
        {
            notification::setError(t("invalid_captcha", "Captcha confirmation text is invalid."));
            $showCaptcha = true;
        }
    }

    if ($showCaptcha == true)
    {
        include_once(SITE_TEMPLATES_PATH . '/partial/_download_page_captcha.inc.php');
        exit();
    }
    else
    {
        if(isset($_REQUEST['pt']))
        {
            $_SESSION['_download_page_next_page_'.$file->id] = 1;
        }
    }
}

// include any plugin includes
pluginHelper::includeAppends('file_download_bottom.php');

// close database so we don't cause locks during the download
$db = Database::getDatabase();
$db->close();

// clear session tracker
$_SESSION['_download_page_next_page_'.$file->id] = 1;

// generate unique download url
$downloadUrl = $file->generateDirectDownloadUrl();
coreFunctions::redirect($downloadUrl);
