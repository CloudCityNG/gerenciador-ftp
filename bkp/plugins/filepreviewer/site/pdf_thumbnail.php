<?php

// setup includes
require_once('../../../core/includes/master.inc.php');

// load reward details
$pluginConfig   = pluginHelper::pluginSpecificConfiguration('filepreviewer');
$pluginSettings = json_decode($pluginConfig['data']['plugin_settings'], true);

function outputPdfFileIcon()
{
    $url = file::getIconPreviewImageUrlLarge(array('extension'=>'pdf'), false, false);
    header('Content-Type: image/jpeg');
    echo file_get_contents($url);
    exit;
}

function outputPdfFileIconLarge()
{
    $url = file::getIconPreviewImageUrl(array('extension'=>'pdf'), false, 512);
    header('Content-Type: image/jpeg');
    echo file_get_contents($url);
    exit;
}

// validation
$fileId     = (int) $_REQUEST['f'];
$width      = (int) $_REQUEST['w'];
$height     = (int) $_REQUEST['h'];
$method     = $_REQUEST['m'];
if(($method != 'padded') && ($method != 'middle'))
{
    $method = 'cropped';
}

// validate width & height
if(($width == 0) || ($height == 0))
{
    outputPdfFileIcon();
}

// memory saver
if(($width > 5000) || ($height > 5000))
{
    outputPdfFileIcon();
}

// check the pdf option is enabled
/*
if ((int)$pluginSettings['pdf_thumbnails'] == 0)
{
    // failed reading image
	if(($width > 160) || ($height > 160))
	{
		outputPdfFileIconLarge();
	}
	else
	{
		outputPdfFileIcon();
	}
}
*/

// check for imagick
if(!class_exists("imagick"))
{
    // failed reading image
	if(($width > 160) || ($height > 160))
	{
		outputPdfFileIconLarge();
	}
	else
	{
		outputPdfFileIcon();
	}
}

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
    outputPdfFileIcon();
}

// cache paths
$cacheFilePath = CACHE_DIRECTORY_ROOT . '/plugins/filepreviewer/'.(int)$file->id.'/pdf/';
$fullCachePath = null;
if (!is_dir($cacheFilePath))
{
    @mkdir($cacheFilePath, 0777, true);
}

// create original image if we need to
$originalCacheFileName = 'original_image.jpg';
$originalCachePath = $cacheFilePath . $originalCacheFileName;
if(!file_exists($originalCachePath))
{
	// get original pdf file
	if($file->serverId == file::getCurrentServerId())
	{
		// local so use path
		$filePath = $file->getFullFilePath(_CONFIG_FILE_STORAGE_PATH);
	}
	else
	{
		// remote to use url
		$filePath = $file->generateDirectDownloadUrlForMedia();
	}

	// create and save screenshot of first page from pdf
	$im = new imagick();
	$im->setResolution(200, 200);
	$im->readImage($filePath.'[0]');
	$im->setimageformat("jpg");
	$im->flattenImages();
	$im->writeimage($originalCachePath);
	$im->clear();
	$im->destroy();
}

// make sure we have the original screenshot file
if(!file_exists($originalCachePath))
{
	// failed reading image
	if(($width > 160) || ($height > 160))
	{
		outputPdfFileIconLarge();
	}
	else
	{
		outputPdfFileIcon();
	}
}

// create resized version
$cacheFileName = (int) $width . 'x' . (int) $height . '_' . $method.'_'. MD5(json_encode($pluginSettings)) . '.jpg';
$fullCachePath = $cacheFilePath . $cacheFileName;

// check for cache
if (($fullCachePath == null) || (!file_exists($fullCachePath)))
{
	header('Content-Type: image/jpeg');

    // load into memory
    $im = imagecreatefromjpeg($originalCachePath);
    if ($im === false)
    {
        // failed reading image
        if(($width > 160) || ($height > 160))
        {
            outputPdfFileIconLarge();
        }
        else
        {
            outputPdfFileIcon();
        }
    }

    // get image size
    $imageWidth  = imagesx($im);
    $imageHeight = imagesy($im);

    $newwidth  = (int) $width;
    $newheight = ($imageHeight / $imageWidth) * $newwidth;
    if ($newwidth > $imageWidth)
    {
        $newwidth = $imageWidth;
    }
    if ($newheight > $imageHeight)
    {
        $newheight = $imageHeight;
    }
    $tmp  = imagecreatetruecolor($newwidth, $newheight);
    $tmpH = imagesy($tmp);

    // check height max
    if ($tmpH > (int) $height)
    {
        $newheight = (int) $height;
        $newwidth  = ($imageWidth / $imageHeight) * $newheight;
        $tmp       = imagecreatetruecolor($newwidth, $newheight);
    }
    
    // override method for small images
    if ($method == 'middle')
    {
        if($width > $imageWidth)
        {
            $method = 'padded';
        }
        elseif($height > $imageHeight)
        {
            $method = 'padded';
        }
    }
    
    if ($method == 'middle')
    {
        $tmp  = imagecreatetruecolor($width, $height);
        
        $newwidth  = (int) $width;
        $newheight = ($imageHeight / $imageWidth) * $newwidth;
        $destX = 0;
        $destY = 0;
        if ($newwidth > $imageWidth)
        {
            $newwidth = $imageWidth;
        }
        if ($newheight > $imageHeight)
        {
            $newheight = $imageHeight;
        }
        
        // calculate new x/y positions
        if ($newwidth > $width)
        {
            $destX = floor(($width-$newwidth)/2);
        }
        if ($newheight > $height)
        {
            //$destY = floor(($height-$newheight)/2);
			$destY = 0;
        }

        imagecopyresampled($tmp, $im, $destX, $destY, 0, 0, $newwidth, $newheight, $imageWidth, $imageHeight);
    }
    else
    {
        // this line actually does the image resizing, copying from the original
        // image into the $tmp image
        imagecopyresampled($tmp, $im, 0, 0, 0, 0, $newwidth, $newheight, $imageWidth, $imageHeight);
    }
    
    // add white padding
    if ($method == 'padded')
    {
        $w = $width;
        if($w > $imageWidth)
        {
            $w = $imageWidth;
        }
        $h = $height;
        if($h > $imageHeight)
        {
            $h = $imageHeight;
        }
        
        // create base image
        $bgImg = imagecreatetruecolor((int) $w, (int) $h);

        // set background white
        $background = imagecolorallocate($bgImg, 255, 255, 255);  // white
        //$background = imagecolorallocate($bgImg, 0, 0, 0);  // black

        imagefill($bgImg, 0, 0, $background);

        // add on the resized image
        imagecopyresampled($bgImg, $tmp, ((int) $w / 2) - ($newwidth / 2), ((int) $h / 2) - ($newheight / 2), 0, 0, $newwidth, $newheight, $newwidth, $newheight);

        // reassign variable so the image is output below
        imagedestroy($tmp);
        $tmp = $bgImg;
    }

    $rs = false;
    if ($fullCachePath != null)
    {
        // save image
        $rs = imagejpeg($tmp, $fullCachePath, 90);
    }

    if (!$rs)
    {
        // failed saving cache (or caching disabled), just output
        header('Content-Type: image/jpeg');
        imagejpeg($tmp, null, 90);
        exit;
    }

    // cleanup memory
    imagedestroy($tmp);
}

header('Content-Type: image/jpeg');
echo file_get_contents($fullCachePath);
