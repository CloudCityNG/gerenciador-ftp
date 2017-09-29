<?php

// set max filesize allowed for each file, 500MB
define('MAX_PERMITTED_EACH_FILE_BYTES', 1024*1024*500);

class zipFile
{
    public $zip = null;
    public $fullZipPathAndFilename = null;
    
    function __construct($zipFilename)
    {
        $this->zip = new ZipArchive();
        $zipStoragePath = CACHE_DIRECTORY_ROOT.'/zip';
        if(!is_dir($zipStoragePath))
        {
            mkdir($zipStoragePath);
        }
        
        $zipFilename = $zipFilename.'.zip';
        $this->fullZipPathAndFilename = $zipStoragePath . '/' . $zipFilename;
        if(file_exists($this->fullZipPathAndFilename))
        {
            @unlink($this->fullZipPathAndFilename);
        }
        
        // setup zip
        if ($this->zip->open($this->fullZipPathAndFilename, ZipArchive::CREATE) !== true)
        {
            echo t('account_home_failed_creating_zip_file', 'Error: Failed creating zip file: '.$this->fullZipPathAndFilename);
            exit;
        }
    }
    
    public function addFileAndFolders($folderData)
    {
        foreach($folderData AS $folderItem)
        {
            // add directory
            $this->zip->addEmptyDir($folderItem['basePath'].$folderItem['folderName']);

            // check for subfolders
            if((isset($folderItem['folderName']['folders'])) && (COUNT($folderItem['folderName']['folders'])))
            {
                $this->addFileAndFolders($folderItem['folders']);
            }

            // output progress
            self::outputBufferToScreen(t('account_home_added_folder_to_zip', '- Added folder ').$folderItem['basePath'].$folderItem['folderName'].'/');

            // add files into directory
            $this->addFilesTopZip($folderItem, $folderItem['basePath'].$folderItem['folderName'].'/');
        }
    }
    
    public function close()
    {
        $this->zip->close();
    }
    
    public function getFullFilePath()
    {
        return $this->fullZipPathAndFilename;
    }

    public function addFilesTopZip($loopBaseData, $basePath = '')
    {
        if(COUNT($loopBaseData['files']) == 0)
        {
            return true;
        }

        foreach($loopBaseData['files'] AS $file)
        {
			// make sure filesize is less than MAX_PERMITTED_EACH_FILE_BYTES
			if($file['fileSize'] > MAX_PERMITTED_EACH_FILE_BYTES)
			{
				// output progress
				self::outputBufferToScreen(t('account_home_file_item_too_large_for_zip', '- File is too large to include in zip file ([[[FILE_NAME]]], [[[FILE_SIZE_FORMATTED]]])', array('FILE_NAME' => $basePath.$file['originalFilename'], 'FILE_SIZE_FORMATTED' => coreFunctions::formatSize($file['fileSize']))), 'red');
				continue;
			}
	
            // output progress
            self::outputBufferToScreen(t('account_home_getting', '- Getting ').$basePath.$file['originalFilename'].' ('.coreFunctions::formatSize($file['fileSize']).') ...', null, ' ');

            // get file content
            $fileObj = file::hydrate($file);
 
            // create download url
			$downloadUrl = $fileObj->generateDirectDownloadUrlForMedia();

			// get file content
            $fileContent = coreFunctions::getRemoteUrlContent($downloadUrl);

            // got content
            if(($fileContent) && (strlen($fileContent) == $file['fileSize']))
            {
                // output progress
                self::outputBufferToScreen('Done. Adding to zip file...', null, ' ');

                // add file to zip
                $rs = $this->zip->addFromString($basePath.$file['originalFilename'], $fileContent);
                if($rs)
                {
                    // output progress
                    self::outputBufferToScreen('File added.', 'green');
                }
                else
                {
                    // output progress
                    self::outputBufferToScreen('Error: Failed adding \''.$file['originalFilename'].'\' to zip file.', 'red');
                }
            }
            else
            {
                // output progress
                self::outputBufferToScreen('Error: Failed getting file contents ('.$file['originalFilename'].').', 'red');
            }
        }

        return true;
    }

    static function getFolderStructureAsArray($rootFolderId, $startFolderId, $basePathStr = '')
    {
        // setup database
        $db = Database::getDatabase(true);

        // load folder infomation
        $folderData = $db->getRow('SELECT folderName, parentId FROM file_folder WHERE id = '.$startFolderId.' LIMIT 1');

        // get file data
        $fileData = array();
        $fileData[$folderData['folderName']] = array('files'=>file::loadAllActiveByFolderId($startFolderId), 'folderName'=>$folderData['folderName'], 'basePath' => $basePathStr, 'folders'=>array());

        // get child folders and files
        $subArr = array();
        $folders = $db->getRows('SELECT id FROM file_folder WHERE parentId = '.$startFolderId.' ORDER BY folderName');
        if($folders)
        {
            foreach($folders AS $folder)
            {
                $rs = self::getFolderStructureAsArray($rootFolderId, $folder['id'], ($startFolderId != $rootFolderId ? ($folderData['folderName'] . '/') : ''));
                $subArr = $subArr + $rs;
            }
        }

        $fileData[$folderData{'folderName'}]['folders'] = $subArr;

        return $fileData;
    }

    static function getTotalFileCount($loopBaseData)
    {
        $total = 0;
        if(COUNT($loopBaseData['files']) > 0)
        {
            $total = $total + COUNT($loopBaseData['files']);
            if(COUNT($loopBaseData['folders']))
            {
                foreach($loopBaseData['folders'] AS $folder)
                {
                    $total = $total + self::getTotalFileCount($folder);
                }
            }
        }

        return $total;
    }

    static function getTotalFileSize($loopBaseData)
    {
        $total = 0;
        if(COUNT($loopBaseData['files']) > 0)
        {
            foreach($loopBaseData['files'] AS $file)
            {
                $total = $total + $file['fileSize'];
            }

            if(COUNT($loopBaseData['folders']))
            {
                foreach($loopBaseData['folders'] AS $folder)
                {
                    $total = $total + self::getTotalFileSize($folder);
                }
            }
        }

        return $total;
    }

    // local helper functions
    static function outputInitialBuffer()
    {
        // 1KB of initial data, required by Webkit browsers
        echo "<span><!--" . str_repeat("0", 1000) . "--></span>";
        ob_flush();
        flush();
    }

    static function outputBufferToScreen($str, $colour = null, $lineBreak = '<br/>')
    {
        if($colour !== null)
        {
            echo '<span style="color: '.$colour.'">';
        }
        echo validation::safeOutputToScreen($str);
        if($colour !== null)
        {
            echo '</span>';
        }
        self::scrollIframe();
        echo $lineBreak;
        ob_flush();
        flush();
    }

    static function scrollIframe()
    {
        echo '<script>window.scrollBy(0,50);</script>';
    }
    
    static function cleanOldBatchDownloadZipFiles()
    {
        // loop cache zip folder and clear any older than 3 days old
        $zipStoragePath = CACHE_DIRECTORY_ROOT . '/zip/';
        foreach (glob($zipStoragePath . "*.zip") as $file)
        {
            // protect the filename
            if (filemtime($file) < time() - 60 * 60 * 24 * 3)
            {
                // double check we're in the zip cache store
                if (substr($file, 0, strlen(CACHE_DIRECTORY_ROOT . '/zip/')) == CACHE_DIRECTORY_ROOT . '/zip/')
                {
                    @unlink($file);
                }
            }
        }
    }
}
