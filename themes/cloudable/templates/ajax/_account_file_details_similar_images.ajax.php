<?php
// load file
if (isset($_REQUEST['u']))
{
    $file = file::loadById($_REQUEST['u']);
    if (!$file)
    {
        // failed lookup of file
        coreFunctions::redirect(WEB_ROOT . '/index.' . SITE_CONFIG_PAGE_EXTENSION);
    }
}
else
{
    coreFunctions::redirect(WEB_ROOT . '/index.' . SITE_CONFIG_PAGE_EXTENSION);
}

$html = '';
$themeObj = themeHelper::getLoadedInstance();
$similarFiles = $themeObj->getSimilarFiles($file);
$totalFiles = COUNT($similarFiles);
if($totalFiles)
{
    // find index of currently selected
    $selectedIndex = 0;
    if($totalFiles > 11)
    {
        foreach($similarFiles AS $k=>$totalFile)
        {
            if($totalFile->id == $file->id)
            {
                $selectedIndex = $k;
            }
        }
    }
    
    $html .= '<div class="similar-images-list" data-slick=\'{"initialSlide": '.$selectedIndex.'}\'>';
    foreach($similarFiles AS $totalFile)
    {
        $imageLink = file::getIconPreviewImageUrl((array) $totalFile, false, 48, false, 160, 134, 'middle');
        $html .= '<div><div class="thumbIcon"><a href="#" onClick="showImage('.$totalFile->id.'); return false;"><img data-lazy="'.$imageLink.'"/></a></div><span class="filename">'.validation::safeOutputToScreen($totalFile->originalFilename).'</span></div>';
    }
    $html .= '</div>';
}

// prepare result
$returnJson = array();
$returnJson['success'] = true;
$returnJson['html'] = $html;

echo json_encode($returnJson);