<?php

// try to load the file object
$file = null;
if (isset($_REQUEST['_page_url']))
{
    // only keep the initial part if there's a forward slash
    $shortUrl = current(explode("/", $_REQUEST['_page_url']));
    $file     = file::loadByShortUrl($shortUrl);
}

/* load file details */
if (!$file)
{
    /* if no file found, redirect to home page */
    coreFunctions::redirect(WEB_ROOT . "/index." . SITE_CONFIG_PAGE_EXTENSION);
}

// for page footer link
if(!defined('REPORT_URL'))
{
    define('REPORT_URL', $file->getFullShortUrl());
}

// load plugin details
$pluginDetails  = pluginHelper::pluginSpecificConfiguration('filepreviewer');
$pluginConfig   = $pluginDetails['config'];
$pluginSettings = json_decode($pluginDetails['data']['plugin_settings'], true);

// Initialize current user
$Auth = Auth::getAuth();
$showPage = true;

// if this is a download request

	define('_INT_FILE_ID', (int)$file->id);
	include_once(SITE_TEMPLATES_PATH.'/index.html');
	exit;
