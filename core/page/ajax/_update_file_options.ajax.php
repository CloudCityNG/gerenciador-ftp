<?php

error_reporting(E_ALL | E_STRICT);

// setup includes
require_once('../../../core/includes/master.inc.php');

// receive varables
$fileToEmail = trim($_REQUEST['fileToEmail']);
$filePassword = trim($_REQUEST['filePassword']);
$fileFolder = (int)$_REQUEST['fileFolder'];
$fileDeleteHashes = $_REQUEST['fileDeleteHashes'];
$fileShortUrls = $_REQUEST['fileShortUrls'];

// make sure we have some items
if(COUNT($fileDeleteHashes) == 0)
{
    exit;
}

if(COUNT($fileDeleteHashes) != COUNT($fileShortUrls))
{
    exit;
}

// loop items, load from the database and create email content/set password
$fullUrls = array();
foreach($fileDeleteHashes AS $id=>$fileDeleteHash)
{
    // get short url
    $shortUrl = $fileShortUrls[$id];
    
    // load file
    $file = file::loadByShortUrl($shortUrl);
    if(!$file)
    {
        // failed lookup of file
        continue;
    }
    
    // make sure it matches the delete hash
    if($file->deleteHash != $fileDeleteHash)
    {
        continue;
    }
    
    // update password
    if(strlen($filePassword))
    {
        $file->updatePassword($filePassword);
    }
    
    // update folder
    if(($Auth->loggedIn()) && ($fileFolder > 0))
    {
        // make sure folder is within their account
        $folders = fileFolder::loadAllForSelect($Auth->id);
        if(isset($folders[$fileFolder]))
        {
            $file->updateFolder($fileFolder);
        }
    }
    
    // add full url to local array for email
    if(strlen($fileToEmail))
    {
        $fullUrls[] = '<a href="'.$file->getFullShortUrl().'">'.$file->getFullShortUrl().'</a>';
    }
}

// send email
if((COUNT($fullUrls)) && validation::validEmail($fileToEmail))
{
    $subject = t('send_urls_by_email_subject', 'Your url links from [[[SITE_NAME]]]', array('SITE_NAME' => SITE_CONFIG_SITE_NAME));

    $replacements = array(
        'FILE_URLS'     => implode("<br/>", $fullUrls),
        'SITE_NAME'      => SITE_CONFIG_SITE_NAME,
        'WEB_ROOT'       => WEB_ROOT,
        'PAGE_EXTENSION' => SITE_CONFIG_PAGE_EXTENSION,
        'UPDATE_COMPLETED_DATE_TIME' => date(SITE_CONFIG_DATE_TIME_FORMAT)
    );
    $defaultContent .= "Copies of your urls, which completed uploading on [[[UPDATE_COMPLETED_DATE_TIME]]] are below:<br/><br/>";
    $defaultContent .= "[[[FILE_URLS]]]<br/><br/>";
    $defaultContent .= "Regards,<br/>";
    $defaultContent .= "[[[SITE_NAME]]] Admin";
    $htmlMsg         = t('send_urls_by_email_html_content', $defaultContent, $replacements);

    coreFunctions::sendHtmlEmail($fileToEmail, $subject, $htmlMsg, SITE_CONFIG_DEFAULT_EMAIL_ADDRESS_FROM, strip_tags(str_replace("<br/>", "\n", $htmlMsg)));
}
