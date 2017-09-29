<?php

// validation
$fileId     = (int) $_REQUEST['f'];
$uniqueHash = !isset($_REQUEST['uh']) ? null : $_REQUEST['uh'];
$embedToken = !isset($_REQUEST['idt']) ? null : $_REQUEST['idt'];
$width      = (int) $_REQUEST['w'];
$height     = (int) $_REQUEST['h'];
$method     = isset($_REQUEST['m']) ? $_REQUEST['m'] : '';
if (($method != 'padded') && ($method != 'middle'))
{
    $method = 'cropped';
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
    header("HTTP/1.0 404 Not Found");
    exit;
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
$cacheFileName = (int) $width . 'x' . (int) $height . '_' . $method . '.jpg';
$fullCachePath = $cacheFilePath . $cacheFileName;

// setup includes
require_once('../../../core/includes/master.inc.php');

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
    // no file found
    coreFunctions::output404();
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
if ((int) $pluginSettings['caching'] == 1)
{
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
    $cacheFileName = (int) $width . 'x' . (int) $height . '_' . $method . '.jpg';
    $fullCachePath = $cacheFilePath . $cacheFileName;
}

// check for cache
$imageExtension = 'jpg';
if (((int) $pluginSettings['caching'] == 0) || (!cache::checkCacheFileExists($fullCachePath)))
{
    // get image contents
    header('Content-Type: image/jpeg');
	
	// create holding cache, if it doesn't already exist
	$pluginObj->setupImageMetaAndCache($file);
	
    // get holding cache if it exists, saves loading from main image
	$contents = '';
    if(($width <= $pluginObj->getHoldingCacheSize()) && ($height <= $pluginObj->getHoldingCacheSize()))
    {
        $contents = $pluginObj->getHoldingCache($file);
		if($contents)
		{
			$imageExtension = 'jpg';
		}
    }
	if(!strlen($contents))
    {
        // use original image
        $contents = $file->download(false);
    }

	// GD
	if($pluginObj->getImageLibrary() == 'gd')
	{
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
	}
	// Imagemagick
	else
	{
		// save image in tmp for Imagick
		$tmpStorage = uploader::getLocalTempStorePath();
		$tmpImageFile = $tmpStorage.'tmp-'.MD5(microtime()).'.'.$imageExtension;
		$tmpImage = fopen($tmpImageFile, 'w+');
		fwrite($tmpImage, $contents);
		fclose($tmpImage);

		// start Imagick
		try
		{
			$imagick = new Imagick($tmpImageFile);
		}
		catch (Exception $e)
		{
			unlink($tmpImageFile);
			log::outputFormattedError((array)$e);
			log::error(print_r($e, true));
			exit;
		}
		
		// set the background to white
        $imagick->setImageBackgroundColor('white');
		
		// flatten the image to remove layers and transparency
        $imagick = $imagick->flattenImages();
		
		// remove any meta data for privacy
		$imagick->stripImage();
		
		// set as jpg
		$imagick->setImageFormat('jpeg');
		$imagick->setCompressionQuality($pluginObj->getThumbnailImageQuality());

		// resize
		if ($method == 'middle')
		{
			$imagick->cropThumbnailImage($width, $height);
		}
		elseif ($method == 'padded')
		{
			$imagick->scaleImage($width, $height, true);
			$imagick->setImageBackgroundColor('white');
			$w = $imagick->getImageWidth();
			$h = $imagick->getImageHeight();
			$imagick->extentImage($width, $height, ($w-$width)/2, ($h-$height)/2);
		}
		elseif($method == 'cropped')
		{
			//$imagick->scaleImage($width, $height, true);
			$w = $imagick->getImageWidth();
			if ($w > $width) 
			{ 
				$imagick->thumbnailImage($width, null, 0); 
			}
			 
			//now check height
			$h = $imagick->getImageHeight();
			if ($h > $height) 
			{ 
				$imagick->thumbnailImage(null, $height, 0); 
			}
		}
		else
		{
			$imagick->scaleImage($width, $height, true);
		}
		
		// add on the watermark after resizing
		if($hasWatermark == true)
		{
			// open the watermark
			$watermark = new Imagick();
			$watermark->readImage($tmpFile);
			
			// calculate watermark positions
			$posArr = $pluginObj->calculateWatermarkPosition($pluginSettings['watermark_position'], $imagick->getImageWidth(), $imagick->getImageHeight(), $watermark->getImageWidth(), $watermark->getImageHeight(), (int)$pluginSettings['watermark_padding'], (int)$pluginSettings['watermark_padding']);
			
			// apply watermark
			$imagick->compositeImage($watermark, imagick::COMPOSITE_OVER, $posArr['x'], $posArr['y']);
		}
	}

    $rs = false;
    if ((int) $pluginSettings['caching'] == 1)
    {
        // save image
        ob_start();
		if($pluginObj->getImageLibrary() == 'gd')
		{
			$img->output('jpg', $pluginObj->getThumbnailImageQuality());
		}
		else
		{
			echo $imagick;
		}
        $imageContent = ob_get_clean();
        $rs           = cache::saveCacheToFile('plugins/filepreviewer/' . $fileId . '/' . ($uniqueHash!=null?($uniqueHash.'/'):'') . $cacheFileName, $imageContent);
    }

    if (!$rs)
    {
        // failed saving cache (or caching disabled), just output
		if($pluginObj->getImageLibrary() == 'gd')
		{
			$img->output('jpg', $pluginObj->getThumbnailImageQuality());
		}
		else
		{
			header("Content-Type: image/jpg");
			echo $imagick;
			
			// tidy up
			@unlink($tmpImageFile);
		}
		
		// tidy up
		if($hasWatermark == true)
		{
			@unlink($tmpFile);
		}
        exit;
    }
	
	// tidy up
	if($pluginObj->getImageLibrary() != 'gd')
	{
		@unlink($tmpImageFile);
	}
	if($hasWatermark == true)
	{
		@unlink($tmpFile);
	}
}

$size     = $width . 'x' . $height;
$filename = $file->originalFilename;
$filename = str_replace(array('.' . $file->extension), "", $filename);
$filename .= '_' . $size;
$filename .= '.' . $imageExtension;
$filename = str_replace("\"", "", $filename);

// output some headers
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Pragma: public");
header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
header("Content-Description: File Transfer");
echo cache::getCacheFromFile('plugins/filepreviewer/' . $fileId . '/' . ($uniqueHash!=null?($uniqueHash.'/'):'') . $cacheFileName);
