<?php

class PluginFilepreviewer extends Plugin
{
    const HOLDING_CACHE_SIZE = 1100;
    
    public $config    = null;
    public $data      = null;
    public $settings  = null;
    public $cachePath = null;
	public $permCachePath = null;

    public function __construct()
    {
        // setup database
        $db = Database::getDatabase();

        // get the plugin config
        include('_plugin_config.inc.php');

        // load config into the object
        $this->config = $pluginConfig;
		
		// cache result in local memory cache
		if(cache::cacheExists('PLUGIN_FILEPREVIEWER_CONSTRUCT_DATA') == false)
		{
			$data = $db->getRow('SELECT * FROM plugin WHERE folder_name = ' . $db->quote($this->config['folder_name']) . ' LIMIT 1');
			cache::setCache('PLUGIN_FILEPREVIEWER_CONSTRUCT_DATA', $data);
		}
		
        $this->data = cache::getCache('PLUGIN_FILEPREVIEWER_CONSTRUCT_DATA');
        if ($this->data)
        {
            $this->settings = json_decode($this->data['plugin_settings'], true);
        }
        $this->cachePath = CACHE_DIRECTORY_ROOT . '/plugins/filepreviewer/';
		$this->permCachePath = CACHE_DIRECTORY_ROOT . '/perm_cache/filepreviewer/';
    }

    public function getPluginDetails()
    {
        return $this->config;
    }

    public function uninstall()
    {
        // setup database
        $db = Database::getDatabase();

        // remove plugin specific tables
        $sQL = 'DROP TABLE plugin_filepreviewer_embed_token';
        $db->query($sQL);
        $sQL = 'DROP TABLE plugin_filepreviewer_watermark';
        $db->query($sQL);

        return parent::uninstall();
    }
    
    function getHoldingCacheSize()
    {
        return self::HOLDING_CACHE_SIZE;
    }
	
	public function deleteImagePreviewCache($fileId)
	{
		$this->deleteImageCache($fileId, true, false);
	}

    public function deleteImageCache($fileId, $instant = false, $doPermCache = true)
    {
        // get cache path
        $cacheFilePath = $this->cachePath . (int)$fileId . '/';
		if($instant == true)
		{
			cache::removeCacheSubFolder('plugins/filepreviewer/' . (int)$fileId);
			if($doPermCache == true)
			{
				cache::removeCacheSubFolder('perm_cache/filepreviewer/' . (int)$fileId);
			}
		}

        // queue cache for delete
		$file     = file::loadById($fileId);		
		$serverId = file::getDefaultLocalServerId(); 
        if ($serverId)
        {
            // get all file listing
            $files = coreFunctions::getDirectoryListing($cacheFilePath);
            if (COUNT($files))
            {
                foreach ($files AS $file)
                {
                    fileAction::queueDeleteFile($serverId, $file, $fileId);
                }
            }

            // add folder aswell
            fileAction::queueDeleteFile($serverId, $cacheFilePath, $fileId);
			
			// remove any perm_cache
			if($doPermCache == true)
			{
				fileAction::queueDeleteFile($serverId, $this->permCachePath . (int)$fileId . '/', $fileId);
			}
        }
    }

    public function isAnimatedGif($imageFileContents)
    {
        $str_loc = 0;
        $count   = 0;
        while ($count < 2) # There is no point in continuing after we find a 2nd frame
        {
            $where1 = strpos($imageFileContents, "\x00\x21\xF9\x04", $str_loc);
            if ($where1 === false)
            {
                break;
            }
            else
            {
                $str_loc = $where1 + 1;
                $where2  = strpos($imageFileContents, "\x00\x2C", $str_loc);
                if ($where2 === false)
                {
                    break;
                }
                else
                {
                    if ($where1 + 8 == $where2)
                    {
                        $count++;
                    }
                    $str_loc = $where2 + 1;
                }
            }
        }

        if ($count > 1)
        {
            return true;
        }

        return false;
    }

    public function createImageCacheUrl($fileArr, $thumbnailWidth, $thumbnailHeight, $method = 'cropped', $extension = 'jpg')
    {
        //$url = _CONFIG_SITE_PROTOCOL . '://' . file::getFileDomainAndPath($fileArr['id'], $fileArr['serverId'], true) . '/core/' . CACHE_DIRECTORY_NAME . '/plugins/filepreviewer/'.$fileArr['id'].'/'.(int)$thumbnailWidth.'x'.(int)$thumbnailHeight.'_'.$method.'.jpg';
		
		$fileUniqueHash = $fileArr['unique_hash'];
		if(strlen($fileUniqueHash) == 0)
		{
			$fileUniqueHash = file::createUniqueFileHash($fileArr['id']);
		}
		$url = _CONFIG_SITE_PROTOCOL . '://' . file::getFileDomainAndPath($fileArr['id'], $fileArr['serverId'], true) . '/core/' . CACHE_DIRECTORY_NAME . '/plugins/filepreviewer/'.$fileArr['id'].'/'.$fileUniqueHash.'/'.(int)$thumbnailWidth.'x'.(int)$thumbnailHeight.'_'.$method.'.'.$extension;

        return $url;
    }
    
    public function createImagePHPUrl($fileId, $thumbnailWidth, $thumbnailHeight, $method = 'cropped', $serverId = null, $fileUniqueHash = null, $fileType = 'jpg')
    {
        $url = _CONFIG_SITE_PROTOCOL . '://' . file::getFileDomainAndPath($fileId, $serverId, true) . '/' . PLUGIN_DIRECTORY_NAME . '/filepreviewer/site/resize_image_inline.php?f='.(int)$fileId.'&w='.(int)$thumbnailWidth.'&h='.(int)$thumbnailHeight.'&m='.$method.'&uh='.$fileUniqueHash.'&o='.$fileType;
        
        return $url;
    }
    
    public function getCacheUrlPHPFromBrowserUrl($browserUrl)
    {
        // example url - core/cache/plugins/filepreviewer/2966/006c03e9e4dd38da330032daff94088bc44d1ff541b9573d114b86d755657b6a/190x190_maximum.jpg / .gif
        $w = 0;
        $h = 0;
        $m = 'cropped';
        $fileId = 0;
		$fileType = 'jpg';
		if(substr($browserUrl, strlen($browserUrl)-3, 3) == 'gif')
		{
			$fileType = 'gif';
		}
        
        // break apart url and loop to find data
        $urlParts = explode('/', $browserUrl);
        $useNext = false;
        foreach($urlParts AS $k=>$urlPart)
        {
            if($fileId != 0)
            {
                continue;
            }
            
            if($useNext == true)
            {
                $fileId = $urlParts[$k];
				$fileUniqueHash = $urlParts[$k+1];
                $fileName = $urlParts[$k+2];
                $fileNameParts = preg_split("/(_|\.)/", $fileName);
                $sizeParts = explode('x', $fileNameParts[0]);
                $w = (int)$sizeParts[0];
                if(isset($sizeParts[1]))
                {
                    $h = (int)$sizeParts[1];
                }
                
                if(isset($fileNameParts[1]))
                {
                    $m = $fileNameParts[1];
                }
            }
            
            if($urlPart == 'filepreviewer')
            {
                $useNext = true;
            }
        }
		
		// setup database
        $db = Database::getDatabase();
		
		// lookup fileId
		$fileId = $db->getValue('SELECT id FROM file WHERE unique_hash = '.$db->quote($fileUniqueHash).' AND id = '.$fileId.' LIMIT 1');
        
        return self::createImagePHPUrl($fileId, $w, $h, $m, null, $fileUniqueHash, $fileType);
    }
    
    // get smaller image to be used for thumbnail image creation, rather than original image, creates if not exists
    public function getHoldingCache($file)
    {
        $cacheFilePath = CACHE_DIRECTORY_ROOT.'/perm_cache/filepreviewer/';
        $cacheFilePath .= $file->id . '/' . $file->getFileHash() . '/';
        if (!file_exists($cacheFilePath))
        {
            mkdir($cacheFilePath, 0777, true);
        }
        $cacheFileName = (int) $this->getHoldingCacheSize() . 'x' . (int) $this->getHoldingCacheSize() . '_cropped.jpg';
        $fullCachePath = $cacheFilePath . $cacheFileName;
        if(!file_exists($fullCachePath))
        {
            // create original file
            $contents = $file->download(false);
			
			// if using GD
			if($this->getImageLibrary() == 'gd')
			{
				// load into memory
				$im = imagecreatefromstring($contents);
				if ($im === false)
				{
					// failed reading image
					return false;
				}
		
				// get image size
				$imageWidth  = imagesx($im);
				$imageHeight = imagesy($im);
			
				$newwidth  = (int) $this->getHoldingCacheSize();
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
			
				// set background to white for transparent images
				$back = imagecolorallocate($tmp, 255, 255, 255);
				imagefilledrectangle($tmp, 0, 0, $newwidth, $newheight, $back);
			
				// preserve transparency in gifs
				if ($file->extension == 'gif')
				{
					imagecolortransparent($tmp, imagecolorallocatealpha($tmp, 0, 0, 0, 127));
				}
				
				// image into the $tmp image
				imagecopyresampled($tmp, $im, 0, 0, 0, 0, $newwidth, $newheight, $imageWidth, $imageHeight);
				
				// save image
				ob_start();
				imagejpeg($tmp, null, 100);
				$imageContent = ob_get_clean();
				$rs           = cache::saveCacheToFile('perm_cache/filepreviewer/' . $file->id . '/' . $cacheFileName, $imageContent);
				
				// cleanup memory
				imagedestroy($tmp);
				
				if(!$rs)
				{
					return false;
				}
			}
			else
			{
				// @TODO - add support for ImageMagick
				return false;
			}
        }
        
        return file_get_contents($fullCachePath);
    }
    
    function _mirrorImage($imgsrc)
    {
        $width = imagesx($imgsrc);
        $height = imagesy($imgsrc);
    
        $src_x = $width -1;
        $src_y = 0;
        $src_width = -$width;
        $src_height = $height;
    
        $imgdest = imagecreatetruecolor($width, $height);
        if (imagecopyresampled($imgdest, $imgsrc, 0, 0, $src_x, $src_y, $width, $height, $src_width, $src_height))
        {
            return $imgdest;
        }

        return $imgsrc;
    }
    
    function autoRotateImage($file)
    {
        if(!function_exists('exif_read_data'))
        {
            return false;
        }
        
        // get image contents
        $contents = $file->download(false);
        
        // temp save image in cache for exif function
		$imageFilename = 'plugins/filepreviewer/_tmp/' . md5(microtime() . $file->id) . '.' . $file->extension;
		$cachePath     = cache::saveCacheToFile($imageFilename, $contents);

        // rotate
        $exif = exif_read_data($cachePath);
        if($exif && isset($exif['Orientation']))
        {
            $orientation = (int)$exif['Orientation'];
            if($orientation != 1)
            {
                $img = imagecreatefromstring($contents);
    
                $mirror = false;
                $deg    = 0;
                switch ($orientation)
                {
                  case 2:
                    $mirror = true;
                    break;
                  case 3:
                    $deg = 180;
                    break;
                  case 4:
                    $deg = 180;
                    $mirror = true;  
                    break;
                  case 5:
                    $deg = 270;
                    $mirror = true; 
                    break;
                  case 6:
                    $deg = 270;
                    break;
                  case 7:
                    $deg = 90;
                    $mirror = true; 
                    break;
                  case 8:
                    $deg = 90;
                    break;
                }
                
                if ($deg)
                {
                    $img = imagerotate($img, $deg, 0); 
                }
                
                if ($mirror)
                {
                    $img = $this->_mirrorImage($img);
                }
                
                // load image info memory
                ob_start();
                imagejpeg($img, null, 100);
                $imageContent = ob_get_clean();

                // update image
                $file->setFileContent($imageContent);
                
                // cleanup memory
                imagedestroy($img);
            }
        }
        
        // clear cached file
		cache::removeCacheFile($imageFilename);

        return true;
    }

	// TODO - test with FTP storage and S3
	function rotateImage($file, $direction = 'r')
    {
		// only for jpg, png, gif at the moment
		if(!in_array(strtolower($file->extension), array('jpg', 'jpeg', 'png', 'gif')))
		{
			return false;
		}
		
		// block if the file is a duplicate, @TODO
		if($file->isDuplicate())
		{
			return false;
		}
		
		// setup database
        $db = Database::getDatabase();
		
        // get image contents
        $contents = $file->download(false);

        // rotate
		$img = imagecreatefromstring($contents);
		if(!$img)
		{
			return false;
		}

		// figure out how to rotate
		$deg = 0;
		switch ($direction)
		{
			case 'l':
				$deg = 90;
				break;
			case 'r':
			default:
				$deg = 270;
				break;
		}
		
		if ($deg)
		{
			$img = imagerotate($img, $deg, 0); 
		}
		
		// load image info memory
		ob_start();
		imagejpeg($img, null, 100);
		$imageContent = ob_get_clean();

		// update image
		$file->setFileContent($imageContent);
		
		// update new md5 file hash
		$fileHash = MD5($imageContent);
		$db->query('UPDATE file SET fileHash = '.$db->quote($fileHash).' WHERE id = '.(int)$file->id.' LIMIT 1');
		
		// unique hash
		file::createUniqueFileHash($file->id);
		
		// cleanup memory
		imagedestroy($img);

        return true;
    }
    
    public function getDateTakenFromExifData($file, $exifData = array())
    {
        $dateTime = '';
        if(COUNT($exifData))
        {
            if((isset($exifData['DateTimeOriginal'])) && (strlen($exifData['DateTimeOriginal'])))
            {
                $dateTime = $exifData['DateTimeOriginal'];
            }
            elseif((isset($exifData['CreateDate'])) && (strlen($exifData['CreateDate'])))
            {
                $dateTime = $exifData['CreateDate'];
            }
            elseif((isset($exifData['DateTime'])) && (strlen($exifData['DateTime'])))
            {
                $dateTime = $exifData['DateTime'];
            }
            elseif((isset($exifData['ModifyDate'])) && (strlen($exifData['ModifyDate'])))
            {
                $dateTime = $exifData['ModifyDate'];
            }
            
            // format date time
            if(strlen($dateTime))
            {
                $datePieces = explode(' ', $dateTime);
                if(COUNT($datePieces) == 2)
                {
                    return date('Y-m-d H:i:s', strtotime(str_replace(":", "-", $datePieces[0])." ".$datePieces[1]));
                }
            }
        }
        
        // fallback to todays date
        return date('Y-m-d H:i:s');
    }
    
    public function getThumbnailImageQuality()
    {
        if((int)$this->settings['image_quality'])
        {
            return (int)$this->settings['image_quality'];
        }
        
        return 90;
    }
    
    public function formatExifName($str)
    {
        $str = str_replace('_', '', $str);
        
        return preg_replace('/(?!^)[A-Z]{2,}(?=[A-Z][a-z])|[A-Z][a-z]/', ' $0', $str);
    }
	
	public function getDefaultImageWebPath()
	{
		return PLUGIN_WEB_ROOT.'/filepreviewer/assets/img/default-album.png';
	}

	
	public function getImageColors($fileId)
	{
		// get datbaase
		$db = Database::getDatabase();
		
		// get image colors
		$imageColorsArr = array();
		$imageColors = $db->getValue('SELECT image_colors FROM plugin_filepreviewer_meta WHERE file_id = '.(int)$fileId.' LIMIT 1');
		if(strlen($imageColors))
		{
			$imageColorsArr = explode(',', $imageColors);
		}
		
		return $imageColorsArr;
	}
	
	// which PHP image handling library to use
	public function getImageLibrary()
	{
		if(isset($this->settings['image_library']))
        {
			if(in_array($this->settings['image_library'], array('gd', 'imagemagick')))
			{
				return $this->settings['image_library'];
			}
        }
        
        return 'gd';
	}
	
	public function calculateWatermarkPosition($positionStr, $rsImageW, $rsImageH, $watermarkImageW, $watermarkImageH, $xPadding = 0, $yPadding = 0)
	{
		// defaults
		$x = 0;
		$y = 0;
		switch($positionStr)
		{
			case 'top-left':
				$x = $xPadding; $y = $yPadding;
				break;
			case 'top-middle':
				$x = (floor($rsImageW/2)-floor($watermarkImageW/2)); $y = $yPadding;
				break;
			case 'top-right':
				$x = $rsImageW-$watermarkImageW-$xPadding; $y = $yPadding;
				break;
			case 'right':
				$x = $rsImageW-$watermarkImageW-$xPadding; $y = floor($rsImageH/2)-floor($watermarkImageH/2);
				break;
			case 'bottom-right':
				$x = $rsImageW-$watermarkImageW-$xPadding; $y = $rsImageH-$watermarkImageH-$yPadding;
				break;
			case 'bottom-middle':
				$x = floor($rsImageW/2)-floor($watermarkImageW/2); $y = $rsImageH-$watermarkImageH-$yPadding;
				break;
			case 'bottom-left':
				$x = $xPadding; $y = $rsImageH-$watermarkImageH-$yPadding;
				break;
			case 'left':
				$x = $xPadding; $y = floor($rsImageH/2)-floor($watermarkImageH/2);
				break;
			case 'middle':
				$x = floor($rsImageW/2)-floor($watermarkImageW/2); $y = floor($rsImageH/2)-floor($watermarkImageH/2);
				break;
		}
		
		return array('x'=>$x, 'y'=>$y);
	}
	
	public function getAnimatedFileExtensions()
	{
		return array('gif', 'mng');
	}
	
	public function setupImageMetaAndCache(file $file)
	{
		$rawData       = array();
		
		// get image size
		$imageWidth  = 0;
		$imageHeight = 0;
	
		// load plugin details
		$pluginObj      = pluginHelper::getInstance('filepreviewer');
		$pluginDetails  = pluginHelper::pluginSpecificConfiguration('filepreviewer');
		$pluginSettings = json_decode($pluginDetails['data']['plugin_settings'], true);

		// copy existing colors if file already exists
		$imageColors = array();
		$db = Database::getDatabase();
		$originalFileId = $db->getValue('SELECT id FROM file WHERE fileHash = '.$db->quote($file->fileHash).' AND fileHash IS NOT NULL AND id != '.(int)$file->id.' LIMIT 1');
		if($originalFileId)
		{
			$metaData = $db->getRow('SELECT * FROM plugin_filepreviewer_meta WHERE file_id = '.(int)$originalFileId.' LIMIT 1');
			if($metaData)
			{
				$imageColors = explode(',', $metaData['image_colors']);
			}
		}
		
		// get file contents
		$contents = $file->download(false);
		if(!$contents)
		{
			return false;
		}
		
		// which size the cache should be (same as the main image preview)
		$width = $this->getHoldingCacheSize();
		$height = $this->getHoldingCacheSize();
		$holdingCacheExists = false;
		if ((int) $pluginSettings['caching'] == 1)
		{
			// prepare cache path
			$cacheFilePath = CACHE_DIRECTORY_ROOT.'/perm_cache/filepreviewer/';
			$cacheFilePath .= $file->id . '/' . $file->getFileHash() . '/';
			if (!file_exists($cacheFilePath))
			{
				mkdir($cacheFilePath, 0777, true);
			}
			$cacheFileName = $width . 'x' . $height . '_cropped.jpg';
			$fullCachePath = $cacheFilePath . $cacheFileName;
			if(file_exists($fullCachePath))
			{
				$holdingCacheExists = true;
				return true;
			}
		}
		
		// create holding cache
		if($pluginObj->getImageLibrary() == 'gd')
		{
			// get exif data
			if(function_exists('exif_read_data'))
			{
				$imageFilename = 'plugins/filepreviewer/_tmp/' . md5(microtime() . $file->id) . '.' . $file->extension;
				$cachePath     = cache::saveCacheToFile($imageFilename, $contents);
				$exif = exif_read_data($cachePath, 0, true);
				if($exif)
				{
					foreach ($exif as $key => $section)
					{
						// only log certain types of data
						if(!in_array($key, array('IFD0', 'EXIF', 'COMMENT')))
						{
							continue;
						}

						foreach ($section as $name => $val)
						{
							// stop really long data
							if(COUNT($rawData) > 200)
							{
								continue;
							}

							// limit text length just encase someone if trying to feed it invalid data
							$rawData[substr($name, 0, 200)] = substr($val, 0, 500);
						}
					}
				}
				
				// clear cached file
				cache::removeCacheFile($imageFilename);
			}
			
			// create holding cache
			if ((int) $pluginSettings['caching'] == 1)
			{
				// load image 
				include(DOC_ROOT.'/core/includes/image_resizer/CustomSimpleImage.php');
				$img = new CustomSimpleImage();
				$rs = $img->load_from_image_content($contents);
				
				// get image size
				$imageWidth  = $img->get_width();
				$imageHeight = $img->get_height();
				
				// if holding cache does not exist
				if($holdingCacheExists == false)
				{
					$img->best_fit($width, $height);
					if((int)$pluginObj->settings['auto_rotate'] == 1)
					{
						$img->auto_orient();
					}
					
					// save image
					ob_start();
					$img->output('jpg', 100);
					$imageContent = ob_get_clean();
					file_put_contents($fullCachePath, $imageContent);
				}
			}
		}
		else
		{
			// save image in tmp for Imagick
			$tmpImageFile = tempnam('/tmp', 'img-').'.'.$file->extension;
			$tmpImage = fopen($tmpImageFile, 'w+');
			fwrite($tmpImage, $contents);
			fclose($tmpImage);
			
			// start Imagick
			$imagick = new Imagick($tmpImageFile);
			
			// get image size
			$imageWidth  = $imagick->getImageWidth();
			$imageHeight = $imagick->getImageHeight();
			
			// get exif data
			$exif = $imagick->getImageProperties("*");
			foreach ($exif as $name => $val)
			{
				// stop really long data
				if(COUNT($rawData) > 200)
				{
					continue;
				}
				
				// only log certain types of data
				if((substr($name, 0, 5) != 'date:') && (substr($name, 0, 5) != 'exif:'))
				{
					continue;
				}
				
				// tidy name
				$name = trim(substr($name, 5));

				// limit text length just encase someone if trying to feed it invalid data
				$rawData[substr($name, 0, 200)] = substr($val, 0, 500);
			}

			// create cache
			if ((int) $pluginSettings['caching'] == 1)
			{
				if($holdingCacheExists == false)
				{
					// set as jpg/gif
					if(in_array($file->extension, $pluginObj->getAnimatedFileExtensions()))
					{
						// get first frame for static preview
						$firstFrameImagick = $imagick->coalesceImages();
						foreach ($firstFrameImagick as $k=>$frame)
						{
							if($k == 0)
							{
								$imagick = $frame;
							}
						}
					}
					else
					{
						// set the background to white
						$imagick->setImageBackgroundColor('white');
						
						// flatten the image to remove layers and transparency
						$imagick = $imagick->flattenImages();
						
						// set as jpg
						$imagick->setImageFormat('jpeg');
					}
					
					// set the background to white
					$imagick->setImageBackgroundColor('white');
					
					// set as jpg
					$imagick->setImageFormat('jpeg');
					
					// check width
					$w = $imagick->getImageWidth();
					if ($w > $width) 
					{ 
						$imagick->thumbnailImage($width, null, 0); 
					}
					 
					// now check height
					$h = $imagick->getImageHeight();
					if ($h > $height) 
					{ 
						$imagick->thumbnailImage(null, $height, 0); 
					}
					
					// should we auto rotate the preview image
					if((int)$pluginObj->settings['auto_rotate'] == 1)
					{
						$orientation = $imagick->getImageOrientation(); 
						switch($orientation)
						{ 
							case imagick::ORIENTATION_BOTTOMRIGHT: 
								$imagick->rotateimage("#000", 180); // rotate 180 degrees 
							break; 

							case imagick::ORIENTATION_RIGHTTOP: 
								$imagick->rotateimage("#000", 90); // rotate 90 degrees CW 
							break; 

							case imagick::ORIENTATION_LEFTBOTTOM: 
								$imagick->rotateimage("#000", -90); // rotate 90 degrees CCW 
							break; 
						}
					}
					
					// remove any meta data for privacy
					$imagick->stripImage();

					// save jpg for later in cache
					$imagick->writeImage($fullCachePath);
				}
				
				// EXTRACT THE COMMON IMAGE COLORS
				if(COUNT($imageColors) == 0)
				{
					// reduce the amount of colors to 6
					$imagick->quantizeImage(6, Imagick::COLORSPACE_RGB, 0, true, false);

					// only save one pixel of each color
					$imagick->uniqueImageColors();

					// get ImagickPixelIterator
					$it = $imagick->getPixelIterator();

					// reset the iterator
					$it->resetIterator();

					// loop through rows
					while($row = $it->getNextIteratorRow())
					{
						// loop through columns
						foreach ($row as $pixel)
						{      
							// covert pixel to hex color
							$color = $pixel->getColor();
							$imageColors[] = '#'.strtoupper(sprintf('%02x', $color['r']) . sprintf('%02x', $color['g']) . sprintf('%02x', $color['b']));
						}
					}
				}
			}

			// tidy up
			$imagick->clear();
			@unlink($tmpImageFile);
		}
		
		// get date taken
		$dateTaken = $pluginObj->getDateTakenFromExifData($file, $rawData);
		
		// DISALBED FOR NOW DUE TO INCREASE IN CPU/MEMORY USAGE ON GD, NEEDS MOVED ABOVE INTO THE GD SECTION
		/*
		if(!COUNT($imageColors))
		{
			// get color map
			include_once(dirname(__FILE__).'/ext/colorsofimage.class.php');
			if(file_exists($tmpFile))
			{
				$imageLink = $tmpFile;
				$pixelSpacing = floor(($imageWidth*$imageHeight)/2500);
			}
			else
			{
				$imageLink = file::getIconPreviewImageUrl((array) $file, false, 64, false, 160, 160, 'cropped');
				$pixelSpacing = 10;
			}
			
			$colorsOfImage = new ColorsOfImage($imageLink, $pixelSpacing, 6);
			
			// make sure the image preview is created, we can pass this straight to the function below as it sometimes fails to create the thumb in time
			file_get_contents($imageLink);
			
			$imageColors = $colorsOfImage->getProminentColors();
			$bgColor = $colorsOfImage->getBackgroundColor();
		}
		*/

		// double check we don't have a record already
		$rs = $db->getRow('SELECT id FROM plugin_filepreviewer_meta WHERE file_id = '.(int)$file->id.' LIMIT 1');
		if(!$rs)
		{
			// store meta data
			$dbInsert             = new DBObject("plugin_filepreviewer_meta", array("file_id", "width", "height", "raw_data", "date_taken", "image_colors", "image_bg_color"));
			$dbInsert->file_id    = $file->id;
			$dbInsert->width      = $imageWidth;
			$dbInsert->height     = $imageHeight;
			$dbInsert->raw_data   = json_encode($rawData);
			$dbInsert->date_taken = $dateTaken;
			$dbInsert->image_colors = implode(',', $imageColors);
			$dbInsert->image_bg_color = implode(',', $bgColor);
			$dbInsert->insert();
		}
	}
	
	public function getGeneralFileType(file $file)
	{
		$ext = strtolower($file->extension);
		if(($this->settings['enable_preview_image'] == 1) && (in_array($ext, explode(',', $this->settings['supported_image_types']))))
		{
			return 'image';
		}
		
		if(($this->settings['enable_preview_document'] == 1) && (in_array($ext, explode(',', $this->settings['preview_document_ext']))))
		{
			return 'document';
		}
		
		if(($this->settings['enable_preview_video'] == 1) && (in_array($ext, explode(',', $this->settings['preview_video_ext']))))
		{
			return 'video';
		}
		
		if(($this->settings['enable_preview_audio'] == 1) && (in_array($ext, explode(',', $this->settings['preview_audio_ext']))))
		{
			return 'audio';
		}
		
		return 'download';
	}
}
