<?php

// validation
$fileId     = (int)$_REQUEST['f'];
$width      = 1024;
$height     = 1024;

// check and show cache before loading environment
define('_LOCAL_DOC_ROOT', realpath(dirname(__FILE__) . '/../../../../'));
$cacheFilePath = _LOCAL_DOC_ROOT.'/core/cache/themes/reservo/';
if (!file_exists($cacheFilePath))
{
    mkdir($cacheFilePath, 0777, true);
}
$cacheFileName = 'homepage-backgroud-' . (int)$fileId . '.jpg';
$fullCachePath = $cacheFilePath . $cacheFileName;
if (file_exists($fullCachePath))
{
    // output some headers
    header("Cache-Control: private, max-age=10800, pre-check=10800");
    header('Content-Type: image/jpeg');
    header("Pragma: public");
    echo file_get_contents($fullCachePath);
    exit;
}

// setup includes
require_once(_LOCAL_DOC_ROOT.'/core/includes/master.inc.php');

// load reward details
$pluginObj      = pluginHelper::getInstance('filepreviewer');
$pluginDetails  = pluginHelper::pluginSpecificConfiguration('filepreviewer');
$pluginSettings = json_decode($pluginDetails['data']['plugin_settings'], true);

// try to load the file object
$file = null;
if ($fileId)
{
    $file = file::loadById($fileId);
}

// load file details
if (!$file)
{
    // fail
	coreFunctions::redirect($pluginObj->getDefaultImageWebPath());
}

// make sure it's an image
if(!$file->isImage())
{
	// fail
	coreFunctions::redirect($pluginObj->getDefaultImageWebPath());
}

// make sure it's public
if(!$file->isPublic())
{
	// fail
	coreFunctions::redirect($pluginObj->getDefaultImageWebPath());
}


// check for cache
if (!cache::checkCacheFileExists($fullCachePath))
{
	// use original image
	$contents = $file->download(false);

	// load image 
	include(DOC_ROOT.'/core/includes/image_resizer/CustomSimpleImage.php');
	$img = new CustomSimpleImage();
	$rs = $img->load_from_image_content($contents);
    if (!$rs)
    {
        // fail
		coreFunctions::redirect($pluginObj->getDefaultImageWebPath());
    }

	// resize image
	$img->best_fit($width, $height);

	// save image
	ob_start();
	$img->output('jpg', $pluginObj->getThumbnailImageQuality());
	$imageContent = ob_get_clean();
	$rs           = cache::saveCacheToFile('themes/reservo/' . $cacheFileName, $imageContent);
    if (!$rs)
    {
        // failed saving cache (or caching disabled), just output
        $img->output('jpg', $pluginObj->getThumbnailImageQuality());
        exit;
    }
}

// output some headers
header("Expires: 0");
header("Pragma: public");
echo cache::getCacheFromFile('themes/reservo/' . $cacheFileName);
