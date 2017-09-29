<?php

/*
 * Title: Sanity check files
 * Author: YetiShare.com
 * Period: As required
 * 
 * Description:
 * Script to check the stored files on the file system against what YetiShare has
 * listed in the database. It will also work on external file servers, just make
 * sure it's always called from this directory and via the command line.
 * 
 * Note: This script may take some time to run and it iterates over all your
 * stored files.
 *
 * How To Call:
 * On the command line via PHP, like this:
 * php test_sanity_check_files.php
 */

// setup environment
define('CLI_MODE', true);
define('ADMIN_IGNORE_LOGIN', true);

// output type
if(($argv[0] == '--listfailed') || (isset($argv[1]) && ($argv[1] == '--listfailed')) || (isset($argv[2]) && ($argv[2] == '--listfailed')))
{
    define('LISTFAILED', true);
}
else
{
    define('LISTFAILED', false);
}

// should we move into _deleted
if(($argv[0] == '--deletefailed') || (isset($argv[1]) && ($argv[1] == '--deletefailed')) || (isset($argv[2]) && ($argv[2] == '--deletefailed')))
{
    define('DELETEFAILED', true);
}
else
{
    define('DELETEFAILED', false);
}

// includes and security
include_once('../_local_auth.inc.php');

// first get the id of the current server
$currentServerId = file::getCurrentServerId();

// get list of active files on that server for lookups
$activeFiles     = $db->getRows('SELECT localFilePath FROM file WHERE statusId = 1 GROUP BY localFilePath');
$activeFilePaths = array();
foreach ($activeFiles AS $activeFile)
{
    $activeFilePaths[] = $activeFile['localFilePath'];
}
unset($activeFiles);

// get root server storage path
$storageType         = 'local';
$storageLocation     = _CONFIG_FILE_STORAGE_PATH;
$uploadServerDetails = file::loadServerDetails($currentServerId);
$serverName          = 'Local Default';
if ($uploadServerDetails != false)
{
    $storageLocation = $uploadServerDetails['storagePath'];
    $storageType     = $uploadServerDetails['serverType'];
    $serverName      = $uploadServerDetails['serverLabel'];

    // if no storage path set & local, use system default
    if ((strlen($storageLocation) == 0) && ($storageType == 'local'))
    {
        $storageLocation = _CONFIG_FILE_STORAGE_PATH;
    }
}

// make sure path is absolute
$storageLocation = str_replace(DOC_ROOT, '', $storageLocation);
if(substr($storageLocation, strlen($storageLocation)-1, 1) == '/')
{
	$storageLocation = substr($storageLocation, 0, strlen($storageLocation)-1);
}
$storageLocation = DOC_ROOT . '/'.$storageLocation;
$storageLocation .= '/';
define('STORAGE_LOCATION', $storageLocation);

// log
echo "\n";

// log
sanityChecker::output('*******************************************************************');
sanityChecker::output('Server Name: ' . $serverName);
sanityChecker::output('Server ID: ' . $currentServerId);
sanityChecker::output('File Storage Path: ' . $storageLocation);
sanityChecker::output('*******************************************************************');

// loop over files and log any which exist on the file system but not in our database
sanityChecker::checkFiles($storageLocation, $activeFilePaths);

// log
sanityChecker::output('*******************************************************************');
sanityChecker::output('Finished.');
sanityChecker::output('*******************************************************************');
sanityChecker::output('Matched Files: '.sanityChecker::$found);
sanityChecker::output('Failed Matches: '.sanityChecker::$fails);
sanityChecker::output('Deleted: '.sanityChecker::$deleted);
sanityChecker::output('*******************************************************************');
if((sanityChecker::$fails > 0) && (sanityChecker::$deleted == 0))
{
    sanityChecker::output('You can list just the failed files by re-running this script and');
    sanityChecker::output('adding --listfailed onto the end. Like this:');
    sanityChecker::output('$ php test_sanity_check_files.php --listfailed');
    sanityChecker::output('*******************************************************************');
    sanityChecker::output('You can also move any failed matches into /files/_deleted/ by');
    sanityChecker::output('adding --deletefailed onto the end. Like this:');
    sanityChecker::output('$ php test_sanity_check_files.php --deletefailed');
    sanityChecker::output('Once moved, manually remove the contents of _deleted when you\'re');
    sanityChecker::output('happy everything is working as it should be.');
    sanityChecker::output('USE WITH CARE THOUGH! YOU SHOULD BE SURE THESE FILES AREN\'T USED');
    sanityChecker::output('BEFORE MOVING THEM. IF ERRORS OCCUR, JUST COPY THE FILES BACK.');
    sanityChecker::output('*******************************************************************');
}
else
{
    sanityChecker::output('No failed matches. Everything is ok.');
    sanityChecker::output('*******************************************************************');
}

// log
echo "\n";

// local functions
class sanityChecker
{

    static public $fails       = 0;
    static public $found       = 0;
    static public $deleted     = 0;
    static public $failedPaths = array();

    static public function checkFiles($localPath, $activeFilePaths)
    {
        // get items
        $items = coreFunctions::getDirectoryListing($localPath);
        if (COUNT($items))
        {
            foreach ($items AS $item)
            {
                if (is_dir($item))
                {
                    // ignores
                    $partialPath = str_replace(STORAGE_LOCATION, '', $item);
					if(substr($partialPath, 0, 1) == '/')
					{
						$partialPath = substr($partialPath, 1, strlen($partialPath)-1);
					}
                    if(in_array($partialPath, array('_tmp', '_deleted')))
                    {
                        continue;
                    }
                    
                    // loop again for files within
                    sanityChecker::checkFiles($item, $activeFilePaths);
                }
                else
                {
                    // compare with database data
                    $partialPath = str_replace(STORAGE_LOCATION, '', $item);
					if(substr($partialPath, 0, 1) == '/')
					{
						$partialPath = substr($partialPath, 1, strlen($partialPath)-1);
					}

                    // ignores
                    if(in_array($partialPath, array('.htaccess', 'Thumbs.db')))
                    {
                        continue;
                    }
                    
                    // check data
                    $inData      = in_array($partialPath, $activeFilePaths);
                    if ($inData)
                    {
                        self::addFound();
                    }
                    else
                    {
                        self::addFail($partialPath);
                        if(DELETEFAILED == true)
                        {
                            self::delete($item, $partialPath);
                        }
                        
                        if(LISTFAILED == true)
                        {
                            echo $partialPath."\n";
                        }
                        else
                        {
                            sanityChecker::output('Error: Data not found in database (' . $partialPath . ')');
                        }
                    }
                }
            }
        }
    }
    
    static function delete($fullPath, $partialPath)
    {
        // move file into _deleted
        $finalPath = STORAGE_LOCATION.'_deleted/'.$partialPath;
        if(!file_exists(dirname($finalPath)))
        {
            @mkdir(dirname($finalPath), 0755, true);
        }

        rename($fullPath, $finalPath);
        
        self::addDeleted();
    }

    static public function output($msg = '', $exit = false)
    {
        if(LISTFAILED == true)
        {
            return;
        }
        echo $msg . "\n";
    }

    static function addFail($failedPath)
    {
        self::$fails++;
        self::$failedPaths[] = $failedPath;
    }

    static function addFound()
    {
        self::$found++;
    }
    
    static function addDeleted()
    {
        self::$deleted++;
    }

}