<?php

// includes and security
define('MIN_ACCESS_LEVEL', 20); // allow admin only
include_once('../_local_auth.inc.php');

// prepare total file list
$filesToCheck = array();

// get all files with translations, start with the base
$dirFiles = adminFunctions::getDirectoryList(DOC_ROOT, null, false);
if($dirFiles)
{
    foreach($dirFiles AS $dirFile)
    {
        $path = DOC_ROOT.'/'.$dirFile;
        if(is_file($path))
        {
            $filesToCheck[] = $path;
        }
    }
}

// add sub folders
$subFolders = array('api', 'includes', PLUGIN_DIRECTORY_NAME, 'themes');
foreach($subFolders AS $subFolder)
{
    $dirFiles = adminFunctions::getDirectoryList(DOC_ROOT.'/'.$subFolder, null, true);
    $filesToCheck = array_merge($filesToCheck, $dirFiles);
}

// only keep .php files
$total = COUNT($filesToCheck);
for($i=0; $i<$total; $i++)
{
    $fileName = str_replace(DOC_ROOT, '', $filesToCheck[$i]);
    $fileName = strtolower($fileName);
    if(strpos($fileName, '.php') == false)
    {
        unset($filesToCheck[$i]);
    }
}

// loop files and pick up any translations
$translations = array();
foreach($filesToCheck AS $filePath)
{
    // get contents of file
    $fileContents = file_get_contents($filePath);
    $rs = translate::extractTranslationsFromText($fileContents);
    if(COUNT($rs))
    {
        $shortFileName = str_replace(DOC_ROOT.'/', '', $filePath);
        foreach($rs AS $k=>$item)
        {
            if(!isset($translations[$k]))
            {
                $translations[$k] = array('default_content'=>$item, 'file_source'=>array($shortFileName));
            }
            else
            {
                $translations[$k]['file_source'] = $shortFileName;
            }
        }
    }
}

// loop translations and populate default translation content
translate::setUpTranslationConstants();
$foundTotal = 0;
$addedTotal = 0;
$db->query('UPDATE language_key SET foundOnScan = 0');
foreach($translations AS $translationKey => $translation)
{
    /* return the language translation if we can find it */
    $constantName = "LANGUAGE_" . strtoupper($translationKey);
    if (!defined($constantName))
    {
        if (strlen($translation['default_content']))
        {
            // figure out if admin
            $isAdminArea = 1;
            foreach($translation['file_source'] AS $fileSource)
            {
                if(strpos($fileSource, ADMIN_FOLDER_NAME) === false)
                {
                    $isAdminArea = 0;
                }
            }
            
            // insert default key value
            $dbInsert                 = new DBObject("language_key", array("languageKey", "defaultContent", "isAdminArea", "foundOnScan"));
            $dbInsert->languageKey    = $translationKey;
            $dbInsert->defaultContent = $translation['default_content'];
            $dbInsert->isAdminArea    = (int) $isAdminArea;
            $dbInsert->foundOnScan    = 1;
            $dbInsert->insert();
            
            $addedTotal++;

            // set constant
            define("LANGUAGE_" . strtoupper($translationKey), $translation['default_content']);
        }
    }
    else
    {
        $db->query('UPDATE language_key SET foundOnScan = 1 WHERE languageKey='.$db->quote($translationKey).' LIMIT 1');
    }
    
    $foundTotal++;
}

echo 'Found Translations: '.$foundTotal.'<br/>';
echo 'Added Translations: '.$addedTotal.'<br/>';