<?php

$fileUpload    = $params['fileUpload'];
$userFolders   = $params['userFolders'];
$uploadSource  = $params['uploadSource'];
$fileParts     = explode(".", $fileUpload->name);
$fileExtension = strtolower(end($fileParts));
$previewImageUrlLarge = '';

// only reformat for 'direct' uploads
if($uploadSource == 'direct')
{
	// load file
	$file = file::loadByShortUrl($fileUpload->short_url);
	if ($file->isImage())
	{
		// layout settings
		$thumbnailType = themeHelper::getConfigValue('thumbnail_type');
		
		$sizingMethod = 'middle';
		if($thumbnailType == 'full')
		{
			$sizingMethod = 'cropped';
		}
		$previewImageUrlLarge = file::getIconPreviewImageUrl((array)$file, false, 48, false, 160, 134, $sizingMethod);
	}

	$params['success_result_html'] = $previewImageUrlLarge;
}
else
{
	// generate html
	$success_result_html = '';
	$success_result_html .= '<td class="cancel">';
	$success_result_html .= '   <img src="' . coreFunctions::getCoreSitePath() . '/themes/' . SITE_CONFIG_SITE_THEME . '/images/green_tick_small.png" height="16" width="16" alt="success"/>';
	$success_result_html .= '</td>';
	$success_result_html .= '<td class="name">';
	$success_result_html .= $fileUpload->name;
	$success_result_html .= '</td>';
	$success_result_html .= '<td class="rightArrow"><img src="' . coreFunctions::getCoreSitePath() . '/themes/' . SITE_CONFIG_SITE_THEME . '/images/blue_right_arrow.png" width="8" height="6" /></td>';
	$success_result_html .= '<td class="url urlOff">';
	$success_result_html .= '    <a href="' . $fileUpload->url . '" target="_blank">' . $fileUpload->url . '</a>';
	$success_result_html .= '    <div class="fileUrls hidden">' . $fileUpload->url . '</div>';
	$success_result_html .= '</td>';
	
	$params['success_result_html'] = $success_result_html;
}