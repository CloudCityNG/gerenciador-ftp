<?php

// allow some time to run
set_time_limit(60*60*4);

// some initial headers
header("HTTP/1.0 200 OK");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Pragma: no-cache");

$fileName = isset($_REQUEST['t'])?$_REQUEST['t']:'';
$downloadZipName = isset($_REQUEST['n'])?$_REQUEST['n']:date('d-m-Y');
if(strlen($fileName) == 0)
{
	coreFunctions::output404();
}

// make safe
$fileName = str_replace(array('.', '/', '\\', ','), '', $fileName);
$fileName = validation::removeInvalidCharacters($fileName, 'abcdefghijklmnopqrstuvwxyz12345678900');
$downloadZipName = str_replace(array('.', '/', '\\', ','), '', $downloadZipName);
$downloadZipName = validation::removeInvalidCharacters($downloadZipName, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890_-0');

// check for existance
$zipFilePath = CACHE_DIRECTORY_ROOT.'/zip/'.$fileName.'.zip';
if(!file_exists($zipFilePath))
{
    echo t("error_zip_file_no_longer_available", "ERROR: Zip file no longer available, please regenerate to download again.");
    exit;
}

// clear any buffering to stop possible memory issues with readfile()
@ob_end_clean(); 

// download file
$filesize = filesize($zipFilePath);
header("Content-Disposition: attachment;filename=\"".$downloadZipName.".zip\"");
header('Content-Type: application/zip');
header("Pragma: public");
header("Expires: -1");
header("Cache-Control: no-cache");
header("Cache-Control: public, must-revalidate, post-check=0, pre-check=0");
header("Content-Length: ".$filesize);
header('Content-Transfer-Encoding: binary');
readfile($zipFilePath);
@unlink($zipFilePath);