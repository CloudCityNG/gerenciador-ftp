<?php

// Determine our absolute document root
define('ADMIN_ROOT', realpath(dirname(__FILE__)));

// ignore maintenance mode
define('IGNORE_MAINTENANCE_MODE', true);

// make sure this is only run in cli mode is it's set in CLI_MODE
// DISABLED 17/11/14. Not always reported as 'cli'.
//if(defined('CLI_MODE') && (CLI_MODE == true) && (php_sapi_name() != "cli"))
//{
    //die('This script can only be run on the command line, not via a browser. See the comments in the header of this file for more information.');
//}

// global includes
require_once(ADMIN_ROOT . '/../core/includes/master.inc.php');

// for cross domain access
coreFunctions::allowCrossSiteAjax();

// admin functions
require_once(ADMIN_ROOT . '/_admin_functions.inc.php');

// process csaKeys and authenticate user
$csaKey1 = isset($_REQUEST['csaKey1'])?trim($_REQUEST['csaKey1']):'';
$csaKey2 = isset($_REQUEST['csaKey2'])?trim($_REQUEST['csaKey2']):'';
if(strlen($csaKey1) && strlen($csaKey2))
{
    crossSiteAction::setAuthFromKeys($csaKey1, $csaKey2);
}

if (!defined('ADMIN_IGNORE_LOGIN'))
{
    if (defined('MIN_ACCESS_LEVEL'))
    {
        $Auth->requireAccessLevel(MIN_ACCESS_LEVEL, ADMIN_WEB_ROOT . "/login.php");
    }
    else
    {
        $Auth->requireAdmin();
    }
    $userObj = $Auth->getAuth();
}

// setup database
$db = Database::getDatabase();

header('Content-Disposition: inline; filename="files.json"');
