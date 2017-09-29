<?php

// allow some time to run
set_time_limit(60*60*4);

// set max allowed total filesize, 1GB
define('MAX_PERMITTED_ZIP_FILE_BYTES', 1024*1024*1024*1);

// allow 1.2GB of memory to run
ini_set('memory_limit', '1200M');

// some initial headers
header("HTTP/1.0 200 OK");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Pragma: no-cache");

// setup initial params
$folderId = (int)$_REQUEST['folderId'];
$folder = fileFolder::loadById($folderId);
if(!$folder)
{
	echo t('download_zip_file_failed_loading_folder', 'Error: Failed loading folder, please try again later or contact support.');
	exit;
}

// make sure folder has download access for public users
if(($folder->userId != $Auth->id) && ((int)$folder->showDownloadLinks != 1))
{
	// no download access
	echo t('download_zip_no_downloads_folder', 'Error: You do not have download permissions to this folder, please try again later or contact support.');
	exit;
}

// privacy
if(((int)$folder->userId > 0) && ($folder->userId != $Auth->id))
{
	if(coreFunctions::getOverallPublicStatus($folder->userId, $folder->id) == false)
	{
		// private folder
		echo t('download_zip_private_folder', 'Error: Folder is private, please contact the owner or try again later.');
		exit;
	}
}

// check if folder needs a password, ignore if logged in as the owner
if((strlen($folder->accessPassword) > 0) && ($folder->userId != $Auth->id))
{
	// see if we have it in the session already
	$askPassword = true;
	if(!isset($_SESSION['folderPassword']))
	{
		$_SESSION['folderPassword'] = array();
	}
	elseif(isset($_SESSION['folderPassword'][$folder->id]))
	{
		if($_SESSION['folderPassword'][$folder->id] == $folder->accessPassword)
		{
			$askPassword = false;
		}
	}
	
	if($askPassword == true)
	{
		// password required folder
		echo t('download_zip_password_folder', 'Error: Folder requires a password, please contact the owner or try again later.');
		exit;
	}
}

?>
<style>
html, body
{
    margin:			0;
    padding:		5px;
}
</style>
<link rel="stylesheet" href="<?php echo SITE_CSS_PATH; ?>/bootstrap.css" type="text/css" charset="utf-8" />
<?php

// setup database
$db = Database::getDatabase(true);

// make sure user owns folder
$folderData = $db->getRow('SELECT * FROM file_folder WHERE id = '.(int)$folder->id.' LIMIT 1');
if(!$folderData)
{
	echo t('account_home_can_not_locate_folder', 'Error: Can not locate folder.');
	exit;
}

// check for zip class
if(!class_exists('ZipArchive'))
{
	echo t('account_home_ziparchive_class_no_exists', 'Error: The ZipArchive class was not found within PHP. Please enable it within php.ini and try again.');
	exit;
}

// build folder and file tree
$fileData = zipFile::getFolderStructureAsArray($folderId, $folderId);
$totalFileCount = zipFile::getTotalFileCount($fileData[$folderData{'folderName'}]);
$totalFilesize = zipFile::getTotalFileSize($fileData[$folderData{'folderName'}]);
$zipFilename = md5(serialize($fileData));

// error if no files
if($totalFileCount == 0)
{
	echo t('account_home_no_active_files_in_folder', 'Error: No active files in folder.');
	exit;
}

// check total filesize
if($totalFilesize > MAX_PERMITTED_ZIP_FILE_BYTES)
{
	echo t('account_home_too_many_files_size', 'Error: Selected files are greater than [[[MAX_FILESIZE]]] (total [[[TOTAL_SIZE_FORMATTED]]]). Can not create zip.', array('MAX_FILESIZE' => coreFunctions::formatSize(MAX_PERMITTED_ZIP_FILE_BYTES), 'TOTAL_SIZE_FORMATTED' => coreFunctions::formatSize($totalFilesize)));
	exit;
}

// setup output buffering
zipFile::outputInitialBuffer();

// create blank zip file
$zip = new zipFile($zipFilename);

// remove any old zip files
zipFile::cleanOldBatchDownloadZipFiles();

// output progress
zipFile::outputBufferToScreen('Found '.$totalFileCount.' file'.($totalFileCount!=1?'s':'').'.');

// loop all files and download locally
foreach($fileData AS $fileDataItem)
{	
	// add files (ignore any sub folders for publicly shared folders)
	$zip->addFilesTopZip($fileDataItem);
}

// output progress
zipFile::outputBufferToScreen('Saving zip file...', null, ' ');

// close zip
$zip->close();

// get path for later
$fullZipPathAndFilename = $zip->fullZipPathAndFilename;

// output progress
zipFile::outputBufferToScreen('Done!', 'green');
echo '<br/>';

// output link to zip file
$downloadZipName = $folderData['folderName'];
$downloadZipName = str_replace(' ', '_', $downloadZipName);
$downloadZipName = validation::removeInvalidCharacters($downloadZipName, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890_-0');

echo '<a class="btn btn-info" href="'.WEB_ROOT.'/ajax/_download_all_folder_files_zip_shared.ajax.php?t='.str_replace('.zip', '', $zipFilename).'&n='.urlencode($downloadZipName).'" target="_parent">'.t('account_home_download_zip_file', 'Download Zip File').'&nbsp;&nbsp;('.coreFunctions::formatSize(filesize($fullZipPathAndFilename)).')</a>';
zipFile::scrollIframe();

echo '<br/><br/>';
zipFile::scrollIframe();