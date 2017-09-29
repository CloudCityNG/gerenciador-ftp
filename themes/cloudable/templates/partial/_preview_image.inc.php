<?php

// load script options
define('PRE_CACHE_NEXT_IMAGE', true);
define('PRE_CACHE_PREV_IMAGE', true);

// get image
$imageLink = file::getIconPreviewImageUrl((array) $file, false, 160, false, 1100, 800, 'cropped');
$fullScreenWidth = 1100;
$fullScreenHeight = 800;
if(($imageWidth == 0) || ($imageHeight == 0))
{
	if($foundMeta == true)
	{
		$size = @getimagesize($imageLink);
		if($size)
		{
			if((int)$size[0] && (int)$size[1])
			{
				$fullScreenWidth = (int)$size[0];
				$fullScreenHeight = (int)$size[1];
				
				// update size value in the database for next time
				$imageData = $db->query('UPDATE plugin_filepreviewer_meta SET width = '.(int)$fullScreenWidth.', height = '.(int)$fullScreenHeight.' WHERE file_id = ' . (int)$file->id . ' LIMIT 1');
			}
		}
	}
}
else
{
	$fullScreenWidth = $imageWidth;
	$fullScreenHeight = $imageHeight;
}

echo '<div class="image-fullscreen-link">';
echo '	<a href="#" onClick="showFullScreen(\''.$imageLink.'\', '.(int)$fullScreenWidth.', '.(int)$fullScreenHeight.'); return false;"><i class="entypo-resize-full"></i></a>';
echo '</div>';
echo '<img src="'.$imageLink.'" class="image-preview background-loader" onLoad="$(\'.content-preview-wrapper\').removeClass(\'loader\');"/>';

echo '<!-- pre cache next and previous images -->';
echo '<div class="pre-image-cache-wrapper">';
if(($next !== null) && (PRE_CACHE_NEXT_IMAGE == true))
{
	$fileNext = file::loadById($next);
	if($fileNext)
	{
		$imageLink = file::getIconPreviewImageUrl((array) $fileNext, false, 160, false, 1100, 800, 'cropped');
		echo '<img src="'.$imageLink.'"/>';
	}
}
if(($prev !== null) && (PRE_CACHE_PREV_IMAGE == true))
{
	$filePrev = file::loadById($prev);
	if($filePrev)
	{
		$imageLink = file::getIconPreviewImageUrl((array) $filePrev, false, 160, false, 1100, 800, 'cropped');
		echo '<img src="'.$imageLink.'"/>';
	}
}
echo '</div>';