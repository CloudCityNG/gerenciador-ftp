<?php

// includes and security
define('MIN_ACCESS_LEVEL', 10); // allow moderators
include_once('../_local_auth.inc.php');

// prep url
$url = 'https://mfscripts.com/_script_internal/yetishare.php?ref='.urlencode(WEB_ROOT);
if(themeHelper::getCurrentProductType() == 'image_hosting')
{
	$url = 'https://mfscripts.com/_script_internal/reservo.php?ref='.urlencode(WEB_ROOT);
}
elseif(themeHelper::getCurrentProductType() == 'cloudable')
{
	$url = 'https://mfscripts.com/_script_internal/cloudable.php?ref='.urlencode(WEB_ROOT);
}

// check for script upgrade
$fileContents = coreFunctions::getRemoteUrlContent($url);
if(($fileContents) && (strlen($fileContents)))
{
    $lines = explode("\n", $fileContents);
    $newVersion = (float)$lines[0];
    $upgradeMessage = trim($lines[1]);
    
    // check against current version
    if(version_compare($newVersion, _CONFIG_SCRIPT_VERSION) > 0)
    {
        echo $upgradeMessage;
    }
}
