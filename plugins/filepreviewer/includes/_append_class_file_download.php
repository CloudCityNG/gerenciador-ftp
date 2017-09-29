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

// load plugin details
$pluginDetails = pluginHelper::pluginSpecificConfiguration('filepreviewer');
$pluginConfig  = $pluginDetails['config'];
