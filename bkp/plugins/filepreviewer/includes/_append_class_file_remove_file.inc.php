<?php
$ext = array('jpg', 'jpeg', 'png', 'gif');

// load plugin details
$pluginObj      = pluginHelper::getInstance('filepreviewer');
$pluginDetails  = pluginHelper::pluginSpecificConfiguration('filepreviewer');
$pluginSettings = json_decode($pluginDetails['data']['plugin_settings'], true);

/*
 * available params
 * 
 * $params['actioned'];
 * $params['filePath'];
 * $params['storageType'];
 * $params['storageLocation'];
 * $params['file'];
 * */

$file = $params['file'];
if(in_array(strtolower($file->extension), $ext))
{
    // queue cache for delete
    $pluginObj->deleteImageCache($file->id);
}