<?php

// constants
define('MAX_SIZE_NO_WATERMARK', 200);

// validation
$fileId     = (int) $_REQUEST['f'];
$uniqueHash = !isset($_REQUEST['uh']) ? null : $_REQUEST['uh'];
$embedToken = !isset($_REQUEST['idt']) ? null : $_REQUEST['idt'];
$width      = (int) $_REQUEST['w'];
$height     = (int) $_REQUEST['h'];
$method     = isset($_REQUEST['m']) ? $_REQUEST['m'] : '';
if (($method != 'padded') && ($method != 'middle') && ($method != 'maximum'))
{
    $method = 'cropped';
}
$outputFormat = isset($_REQUEST['o']) ? $_REQUEST['o'] : 'jpg';

// support only jpg or gif output
if($outputFormat != 'gif')
{
	$outputFormat = 'jpg';
}

// validate width & height
if ($width <= 0)
{
    $width = 8;
}
if ($height <= 0)
{
    $height = 8;
}

// memory saver
if (($width > 1100) || ($height > 1100))
{
	// fail
    coreFunctions::redirect($pluginObj->getDefaultImageWebPath());
}

// check and show cache before loading environment
$cacheFilePath = '../../../core/cache/plugins/filepreviewer/';
$cacheFilePath .= $fileId . '/';
if($uniqueHash != null)
{
	$cacheFilePath .= $uniqueHash . '/';
}
if (!file_exists($cacheFilePath))
{
    mkdir($cacheFilePath, 0777, true);
}
$cacheFileName = (int) $width . 'x' . (int) $height . '_' . $method . '.'.$outputFormat;
$fullCachePath = $cacheFilePath . $cacheFileName;

// setup includes
require_once('../../../core/includes/master.inc.php');

// load plugin details
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

// check if file needs a password
$album = null;
if($file->folderId !== NULL)
{
	$album = $file->getFolderData();
}
if($Auth->id != $file->userId)
{
	if(($album) && (strlen($album->accessPassword) > 0))
	{
		// see if we have it in the session already
		$askPassword = true;
		if(!isset($_SESSION['folderPassword']))
		{
			$_SESSION['folderPassword'] = array();
		}
		elseif(isset($_SESSION['folderPassword'][$album->id]))
		{
			if($_SESSION['folderPassword'][$album->id] == $album->accessPassword)
			{
				$askPassword = false;
			}
		}
		
		if($askPassword)
		{
			// redirect to main page which requests for a password
			coreFunctions::redirect(file::getFileUrl($file->id));
		}
	}
}

// cache paths
$cacheFilePath = '../../../core/cache/plugins/filepreviewer/';
$cacheFilePath .= $fileId . '/';
if($uniqueHash != null)
{
	$cacheFilePath .= $uniqueHash . '/';
}
if (!file_exists($cacheFilePath))
{
	mkdir($cacheFilePath, 0777, true);
}
$cacheFileName = (int) $width . 'x' . (int) $height . '_' . $method . '.'.$outputFormat;
$fullCachePath = $cacheFilePath . $cacheFileName;

// check for cache
if (!cache::checkCacheFileExists($fullCachePath))
{
    // load content from main image
	$contents = '';
	$imageExtension = $file->extension;
	
	// figure out if we're resizing to animated or static
	$animated = false;
	if((in_array($file->extension, $pluginObj->getAnimatedFileExtensions())) && ((($width >= $pluginObj->getHoldingCacheSize()) || ($height >= $pluginObj->getHoldingCacheSize()))) && ($outputFormat == 'gif'))
	{
		$animated = true;
	}

	// create holding cache, if it doesn't already exist
	$pluginObj->setupImageMetaAndCache($file);

	// ignore for animated file types, i.e. use the original image
	if(in_array($file->extension, $pluginObj->getAnimatedFileExtensions()) == false)
	{
		if(($width <= $pluginObj->getHoldingCacheSize()) && ($height <= $pluginObj->getHoldingCacheSize()))
		{
			$contents = $pluginObj->getHoldingCache($file);
			if($contents)
			{
				$imageExtension = $outputFormat;
			}
		}
	}
	
	if(!strlen($contents))
    {
        // use original image
        $contents = $file->download(false);
    }

	// if this is an animated gif just output it
	if($animated == true)
	{
		header("Expires: 0");
		header("Pragma: public");
		header("Content-Type: image/".$outputFormat);
		echo $contents;
		exit;
	}
	
	// load image 
	include(DOC_ROOT.'/core/includes/image_resizer/CustomSimpleImage.php');
	$img = new CustomSimpleImage();
	$rs = $img->load_from_image_content($contents);
	if (!$rs)
	{
		// fail
		coreFunctions::redirect($pluginObj->getDefaultImageWebPath());
	}

	if ($method == 'middle')
	{
		$img->thumbnail($width, $height);
	}
	elseif ($method == 'padded')
	{
		$img->padded_image($width, $height);
	}
	elseif($method == 'cropped')
	{
		$img->best_fit($width, $height);
	}
	else
	{
		$img->resize($width, $height);
	}
	
	// add on the watermark after resizing & if this isn't a thumbnail
	if($album)
	{
		if(($width >= MAX_SIZE_NO_WATERMARK) || ($height >= MAX_SIZE_NO_WATERMARK))
		{
			$watermarkCachePath = CACHE_DIRECTORY_ROOT.'/user/'.(int)$album->userId.'/watermark/watermark_original.png';
			if(((bool)$album->watermarkPreviews == true) && (file_exists($watermarkCachePath)))
			{
				// load user
				if((int)$file->userId)
				{
					$user = UserPeer::loadUserById((int)$file->userId);
					if($user)
					{
						// apply watermark
						$watermarkPadding = $user->getProfileValue('watermarkPadding')?$user->getProfileValue('watermarkPadding'):0;
						$watermarkPosition = $user->getProfileValue('watermarkPosition')?$user->getProfileValue('watermarkPosition'):'bottom right';
						$img->apply_watermark($watermarkCachePath, $watermarkPosition, $watermarkPadding, '1.0');
					}
				}
			}
		}
	}

    $rs = false;
	
	// save image
	ob_start();
	$img->output($outputFormat, $pluginObj->getThumbnailImageQuality());
	$imageContent = ob_get_clean();
	$rs = cache::saveCacheToFile('plugins/filepreviewer/' . $fileId . '/' . ($uniqueHash!=null?($uniqueHash.'/'):'') . $cacheFileName, $imageContent);

    if (!$rs)
    {
        // failed saving cache (or caching disabled), just output
		$img->output($outputFormat, $pluginObj->getThumbnailImageQuality());

        exit;
    }
	
	// tidy up
	if($pluginObj->getImageLibrary() != 'gd')
	{
		@unlink($tmpImageFile);
	}
}

$size     = $width . 'x' . $height;
$filename = $file->originalFilename;
$filename = str_replace(array('.' . $file->extension), "", $filename);
$filename .= '_' . $size;
$filename .= '.' . $file->extension;
$filename = str_replace("\"", "", $filename);

// output some headers
header("Expires: 0");
header("Pragma: public");
header("Content-Type: image/".$outputFormat);
echo cache::getCacheFromFile('plugins/filepreviewer/' . $fileId . '/' . ($uniqueHash!=null?($uniqueHash.'/'):'') . $cacheFileName);
