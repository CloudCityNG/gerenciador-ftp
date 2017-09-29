<?php

/**
 * Entry point. All requests are forwarded via .htaccess/mod_rewrite
 *
 * i.e. index.php?url=http://urlsample.com/controller/action/
 *
 * @author      MFScripts.com - info@mfscripts.com
 * @version     1.0
 */
// get requesting url
$url = isset($_GET['_page_url']) ? $_GET['_page_url'] : 'index.html';
$url = str_replace(array('../'), '', $url);
define('_INT_PAGE_URL', $url);

// setup environment
require_once('core/includes/master.inc.php');

// include template
$templateFile = SITE_TEMPLATES_PATH . '/' . $url;
if (file_exists($templateFile) && is_file($templateFile))
{
    require_once(SITE_TEMPLATES_PATH . '/' . $url);
    exit;
}

// if it's a folder, try the relating index.html file
if(substr($url, strlen($url)-1, 1) == '/')
{
	$filePath = SITE_TEMPLATES_PATH.'/'.$url.'index.html';
	if (file_exists($filePath) && is_file($filePath))
	{
		require_once($filePath);
		exit;
	}
}

// handle sub folders in template
$subPath = current(explode('/', $url));
if(file_exists(SITE_TEMPLATES_PATH.'/'.$subPath))
{
	// assume this is a 'pretty url' so pass it to the index.html if it exists
	if(file_exists(SITE_TEMPLATES_PATH.'/'.$subPath.'/index.html'))
	{
		include_once(SITE_TEMPLATES_PATH.'/'.$subPath.'/index.html');
	}
}

// compatibility with old .php files
if(strpos($url, '.html') !== false)
{
	$filePath = DOC_ROOT.'/'.str_replace('.html', '.php', $url);
	if (file_exists($filePath))
	{
		coreFunctions::redirect(WEB_ROOT.'/'.str_replace('.html', '.php', $url));
	}
}

// assume file related
$filePath = null;
if (strpos($url, '~') !== false)
{
    $endPart = strtolower(substr($url, strlen($url) - 2, 2));
    switch ($endPart)
    {
        // stats page
        case '~s':
            $filePath = 'stats.html';
            break;
        // delete page
        case '~d':
            $filePath = 'delete_file.html';
            break;
        // share page
        case '~i':
            $filePath = 'share_file.html';
            break;
        // view folder page
        case '~f':
            $filePath = 'view_folder.html';
            break;
    }
}

if ($filePath !== null)
{
    if (file_exists(SITE_TEMPLATES_PATH . '/' . $filePath))
    {
        require_once(SITE_TEMPLATES_PATH . '/' . $filePath);
        exit;
    }
}

// try file download
require_once(CORE_PAGE_DIRECTORY_ROOT . '/file_download.php');