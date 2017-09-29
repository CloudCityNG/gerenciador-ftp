<?php

// available params
// $params['url'] = page url in the browser

// see if we get passed a cached file
if (strpos($params['url'], 'core/cache/plugins/filepreviewer/') !== false)
{
    // a request has been made for thumbnail cache however it's failed to find it, we ended up here via the webserver rewrite rules, redirect to cache page
	
	// load plugin details
	$pluginObj = pluginHelper::getInstance('filepreviewer');
	
	// create url
	$url = $pluginObj->getCacheUrlPHPFromBrowserUrl($params['url']);
	
	// redirect to url
	coreFunctions::redirect($url);
}
elseif (strpos($params['url'], '/frontend_assets/images/reservo/logo/main-site-lg.png') !== false)
{
	// default logo
	coreFunctions::redirect(SITE_IMAGE_PATH.'/logo/logo.png');
}