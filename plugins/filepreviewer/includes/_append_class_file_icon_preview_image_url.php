<?php
// available params
// $params['iconUrl']
// $params['fileArr']

// load plugin details
$pluginObj = pluginHelper::getInstance('filepreviewer');
$pluginDetails  = pluginHelper::pluginSpecificConfiguration('filepreviewer');
$pluginSettings = json_decode($pluginDetails['data']['plugin_settings'], true);

// check this is an image
$fileObj = file::hydrate($params['fileArr']);
if ($fileObj->isImage())
{
    // only for active files
    if($params['fileArr']['statusId'] == 1)
    {
        $w = 99;
        if((int)$params['width'])
        {
            $w = (int)$params['width'];
        }
        
        $h = 60;
        if((int)$params['height'])
        {
            $h = (int)$params['height'];
        }
		
		// control for thumbnails
		$continue = true;
		if(($pluginSettings['preview_image_show_thumb'] == 0) && ($h <= 300))
		{
			$continue = false;
		}
        
		if($continue == true)
		{
			$m = 'middle';
			if(trim($params['type']))
			{
				$m = trim($params['type']);
			}
			
			$o = 'jpg';
			if(in_array($params['fileArr']['extension'], $pluginObj->getAnimatedFileExtensions()))
			{
				$o = 'gif';
			}
			
			//$params['iconUrl'] = _CONFIG_SITE_PROTOCOL . '://' . file::getFileDomainAndPath($params['fileArr']['id'], $params['fileArr']['serverId'], true) . '/' . PLUGIN_DIRECTORY_NAME . '/filepreviewer/site/resize_image_inline.php?f='.($params['fileArr']['id']).'&w='.$w.'&h='.$h.'&m='.$m;
			$params['iconUrl'] = $pluginObj->createImageCacheUrl($params['fileArr'], $w, $h, $m, $o);
		}
    }
}
// pdf
elseif (in_array(strtolower($params['fileArr']['extension']), array('pdf')))
{
    // only for active files
    if(isset($params['fileArr']['statusId']) && ($params['fileArr']['statusId'] == 1))
    {		
		// check for imagemagick
		if(($pluginSettings['preview_document_pdf_thumbs'] == 1) && (class_exists("imagick")))
		{
			$w = 99;
			if((int)$params['width'])
			{
				$w = (int)$params['width'];
			}
			
			$h = 60;
			if((int)$params['height'])
			{
				$h = (int)$params['height'];
			}
			
			$m = 'middle';
			
			// url
			$params['iconUrl'] = _CONFIG_SITE_PROTOCOL . '://' . file::getFileDomainAndPath($params['fileArr']['id'], $params['fileArr']['serverId'], true) . '/' . PLUGIN_DIRECTORY_NAME .'/filepreviewer/site/pdf_thumbnail.php?f='.$params['fileArr']['id'].'&w='.$w.'&h='.$h.'&m='.$m;
		}
    }
}