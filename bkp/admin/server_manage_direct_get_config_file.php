<?php

// includes and security
include_once('_local_auth.inc.php');

// which file
$fileName = trim($_REQUEST['fileName']);

// local shortcut functions
function replaceConstantValue($constantName, $newValue, $contentStr)
{
    $constantName = strtoupper($constantName);
    $newContent = array();
    $contentStrExp = explode("\n", $contentStr);
    foreach($contentStrExp AS $contentLine)
    {
        $oldDefineStart = 'define("'.$constantName.'",';
        if(substr($contentLine, 0, strlen($oldDefineStart)) == $oldDefineStart)
        {
            $newContent[] = $oldDefineStart . "\t\t\"".str_replace("\"", "\\\"", $newValue).'");';
        }
        else
        {
            $newContent[] = $contentLine;
        }
    }

    return implode("\n", $newContent);
}

$contentStr = "";

if (_CONFIG_DEMO_MODE == true)
{
    $contentStr .= 'Unavailable in demo mode.';
}
else
{
    // create content
    switch ($fileName)
    {
        case '.htaccess':
            $REWRITE_BASE = trim($_REQUEST['REWRITE_BASE']);
            if(strlen($REWRITE_BASE) == 0)
            {
                $REWRITE_BASE = '/';
            }
            if(substr($REWRITE_BASE, 0, 1) != '/')
            {
                $REWRITE_BASE = '/'.$REWRITE_BASE;
            }
            if(substr($REWRITE_BASE, strlen($REWRITE_BASE)-1, 1) != '/')
            {
                $REWRITE_BASE = $REWRITE_BASE.'/';
            }
            $contentStr .= "RewriteEngine On\n";
            $contentStr .= "RewriteBase ".$REWRITE_BASE."\n";
            $contentStr .= "RewriteRule ^(.+)\~s$ ".WEB_ROOT."/$1~s [L]\n";
            $contentStr .= "RewriteRule ^(.+)\~i$ ".WEB_ROOT."/$1~i [QSA,L]\n";
            $contentStr .= "RewriteCond %{REQUEST_FILENAME} !-f\n";
            $contentStr .= "RewriteCond %{REQUEST_FILENAME} !-d\n";
            $contentStr .= "RewriteRule ^(.*)$ index.php?_page_url=$1 [QSA]\n";
            break;
        case '_config.inc.php':
            $SITE_HOST = trim($_REQUEST['SITE_HOST']);
            if(substr($SITE_HOST, strlen($SITE_HOST)-1, 1) == '/')
            {
                $SITE_HOST = substr($SITE_HOST, 0, strlen($SITE_HOST)-1);
            }
            $REWRITE_BASE = trim($_REQUEST['REWRITE_BASE']);
            if(substr($REWRITE_BASE, strlen($REWRITE_BASE)-1, 1) == '/')
            {
                $REWRITE_BASE = substr($REWRITE_BASE, 0, strlen($REWRITE_BASE)-1);
            }
            if(strlen($REWRITE_BASE))
            {
                if(substr($REWRITE_BASE, 0, 1) != '/')
                {
                    $REWRITE_BASE = '/'.$REWRITE_BASE;
                }
            }
            $REWRITE_BASE = $SITE_HOST.$REWRITE_BASE;
            $contentStr = file_get_contents(DOC_ROOT . '/_config.inc.php');
            $contentStr = replaceConstantValue('_CONFIG_SITE_HOST_URL', $SITE_HOST, $contentStr);
            $contentStr = replaceConstantValue('_CONFIG_SITE_FULL_URL', $REWRITE_BASE, $contentStr);
            $contentStr = replaceConstantValue('_CONFIG_CORE_SITE_HOST_URL', _CONFIG_SITE_HOST_URL, $contentStr);
            $contentStr = replaceConstantValue('_CONFIG_CORE_SITE_FULL_URL', _CONFIG_SITE_FULL_URL, $contentStr);

            // if database is localhost, update to main site host
            if(_CONFIG_DB_HOST == 'localhost')
            {
                $contentStr = replaceConstantValue('_CONFIG_DB_HOST', _CONFIG_SITE_HOST_URL, $contentStr);
            }
            
            // clear password
            $contentStr = replaceConstantValue('_CONFIG_DB_PASS', '', $contentStr);
            break;
        default:
            coreFunctions::output404();
    }
}

// send file
header("Content-Type: application/octet-stream");
header("Content-Transfer-Encoding: Binary");
header("Content-disposition: attachment; filename=\"" . $fileName . "\"");
echo $contentStr;
?>
