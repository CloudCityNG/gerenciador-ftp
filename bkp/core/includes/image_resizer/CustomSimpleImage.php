<?php

include(DOC_ROOT.'/core/includes/image_resizer/SimpleImage.php');

if (!class_exists('CustomSimpleImage'))
{
class CustomSimpleImage extends SimpleImage
{
	public $quality = 90;
	public $tmpFile = null;
	
	function load_from_image_content($string)
	{
        if (!extension_loaded('gd'))
		{
            throw new Exception('Required extension GD is not loaded.');
        }
        
		// create temp file
		$path = CACHE_DIRECTORY_ROOT . '/plugins/imageviewer/_tmp';
		if(!file_exists($path))
		{
			mkdir($path, 0777, true);
		}

		$this->tmpFile = $path.'/img-'.MD5(microtime());

		$tmp = fopen($this->tmpFile, 'w+');
		fwrite($tmp, $string);
		fclose($tmp);

		// load from filename
		$rs = $this->load($this->tmpFile);
		
		// delete temp file
		unlink($this->tmpFile);
		
		return $rs;
    }
	
	function padded_image($width, $height)
	{
		return $this->best_fit($width, $height);
	}
	
	function best_fit($max_width, $max_height) {

        // If it already fits, there's nothing to do
        if ($this->width <= $max_width && $this->height <= $max_height) {
            return $this;
        }

        // Determine aspect ratio
        $aspect_ratio = $this->height / $this->width;

        // Make width fit into new dimensions
        if ($this->width > $max_width) {
            $width = $max_width;
            $height = $width * $aspect_ratio;
        } else {
            $width = $this->width;
            $height = $this->height;
        }

        // Make height fit into new dimensions
        if ($height > $max_height) {
            $height = $max_height;
            $width = $height / $aspect_ratio;
        }

        $imgObj = $this->resize($width, $height);
		
		return $imgObj;
    }
	
	function apply_watermark($watermark_path, $position, $margin, $opacity = '0.5')
	{
		// calculate positioning
		switch ($position)
		{
			case 'top-left':
				$waterX = $margin;
				$waterY = $margin;
				break;
			case 'top-middle':
			case 'top':
				$waterX = 0;
				$waterY = $margin;
				break;
			case 'top-right':
				$waterX = 0 - $margin;
				$waterY = $margin;
				break;
			case 'right':
				$waterX = 0 - $margin;
				$waterY = 0;
				break;
			case 'bottom-right':
				$waterX = 0 - $margin;
				$waterY = 0 - $margin;
				break;
			case 'bottom-middle':
			case 'bottom':
				$waterX = 0;
				$waterY = 0 - $margin;
				break;
			case 'bottom-left':
				$waterX = $margin;
				$waterY = 0 - $margin;
				break;
			case 'left':
				$waterX = $margin;
				$waterY = 0;
				break;
			case 'middle':
			case 'center':
				$waterX = 0;
				$waterY = 0;
				break;
		}
		
		return $this->overlay($watermark_path, str_replace('-', ' ', $position), $opacity, (int) $waterX, (int) $waterY);
	}
	
	// override thumbnail function to stop smaller images from stretching to the thumnail size
	function thumbnail($width, $height = null) {
        // determine height
        if($height == null)
		{
			$height = $height ?: $width;
		}
		
		$original_info = $this->get_original_info();

        // determine aspect ratios
        $current_aspect_ratio = $original_info['height'] / $original_info['width'];
        $new_aspect_ratio = $height / $width;

		if(($original_info['width'] > $width) || ($original_info['height'] > $height))
		{
			// fit to height/width
			if ($new_aspect_ratio > $current_aspect_ratio) {
				$this->fit_to_height($height);
			} else {
				$this->fit_to_width($width);
			}
		}
		
        $left = floor(($this->width / 2) - ($width / 2));
        $top = floor(($this->height / 2) - ($height / 2));

        // return trimmed image
        $imgObj = $this->crop($left, $top, $width + $left, $height + $top);

		if(($original_info['width'] > $width) || ($original_info['height'] > $height))
		{
		}
		else
		{
			// force white background
			$white = imagecolorallocate($imgObj->image, 255, 255, 255);
			imagefill($imgObj->image, 0, 0, $white);
			
		}
		
		return $imgObj;
    }
}
}
