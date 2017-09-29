<?php

// Determine our absolute document root
define('DOC_ROOT', realpath(dirname(__FILE__) . '/../../'));
define('CORE_ROOT', DOC_ROOT . '/core');

// set timezone if not set, change to whatever timezone you want
if (!ini_get('date.timezone'))
{
    date_default_timezone_set('GMT');
}

// increase allocated memory
@ini_set('memory_limit', '512M');

// autoloader
spl_autoload_register( function($className)
{
    if (file_exists(CORE_ROOT . '/includes/' . lcfirst($className) . '.class.php'))
    {
        require_once(CORE_ROOT . '/includes/' . lcfirst($className) . '.class.php');
    }
});

require_once(CORE_ROOT . '/includes/objects.class.php');

// translation wrapper
function t($key, $defaultContent = '', $replacements = array(), $overwriteDefault = false)
{
	// comment out this line to force default translations to be added to the db
	//$overwriteDefault = true;

    return translate::getTranslation($key, $defaultContent, 0, $replacements, $overwriteDefault);
}

// add missing function
if (!function_exists('array_replace_recursive'))
{

    function array_replace_recursive($array, $array1)
    {
        $rs = array();
        foreach ($array AS $k => $arrayItem)
        {
            $rs[$k] = $arrayItem;
            if (isset($array1[$k]))
            {
                $rs[$k] = $array1[$k];
            }
        }

        return $rs;
    }

}

// fix magic quotes
if (get_magic_quotes_gpc())
{
    $_POST    = coreFunctions::fixSlashes($_POST);
    $_GET     = coreFunctions::fixSlashes($_GET);
    $_REQUEST = coreFunctions::fixSlashes($_REQUEST);
    $_COOKIE  = coreFunctions::fixSlashes($_COOKIE);
}

// load our config settings
$config = Config::getConfig();

// load db config settings into constants
Config::initConfigIntoMemory();

// make sure the url exactly matches the one set in _config.inc.php
// removed 1st Dec 2014, causes issues with cli scripts
//coreFunctions::ensureSiteMatchesConfig();

// setup database connection
$db = Database::getDatabase();

// setup error handler
log::initErrorHandler();

// store session info in the database?
if ($config->useDBSessions === true)
{
    DBSession::register();
}

// initialize our session
session_name($config->sessionName);

// how long to keep sessions active before expiring
session_set_cookie_params((int) SITE_CONFIG_SESSION_EXPIRY);

// start session
if (!isset($sessionStarted) || ($sessionStarted == false))
{
    session_start();
}

// the root plugin directory
define('PLUGIN_DIRECTORY_NAME', 'plugins');
define('PLUGIN_DIRECTORY_ROOT', DOC_ROOT . '/' . PLUGIN_DIRECTORY_NAME . '/');
define('PLUGIN_WEB_ROOT', WEB_ROOT . '/' . PLUGIN_DIRECTORY_NAME);

// pick up any requests to change the site language
if (isset($_REQUEST['_t']))
{
    // make sure the one passed is an active language
    $isValidLanguage = $db->getRow("SELECT languageName, flag FROM language WHERE isActive = 1 AND languageName = '" . $db->escape(trim($_REQUEST['_t'])) . "' LIMIT 1");
    if ($isValidLanguage)
    {
        $_SESSION['_t'] = trim($_REQUEST['_t']);
    }
    else
    {
        $_SESSION['_t'] = SITE_CONFIG_SITE_LANGUAGE;
    }
	pluginHelper::reloadSessionPluginConfig();
}
elseif (!isset($_SESSION['_t']))
{
    $_SESSION['_t'] = SITE_CONFIG_SITE_LANGUAGE;
}

// Initialize current user
$Auth = Auth::getAuth();

// whether to show the maintenance page
coreFunctions::decideShowMaintenancePage();

// Object for tracking and displaying error messages
//$Error = Error::getError();
// whether to use language specific images
$languageImagePath = '';
$languageDirection = 'LTR';
$languageDetails = $db->getRow("SELECT id, flag, direction FROM language WHERE isActive = 1 AND languageName = " . $db->quote($_SESSION['_t']) . " LIMIT 1");
if($languageDetails)
{
    $languageDirection = $languageDetails['direction'];
    if (SITE_CONFIG_LANGUAGE_SEPARATE_LANGUAGE_IMAGES == 'yes')
    {
        $languageImagePath = $languageDetails['flag'] . '/';
    }
	define('SITE_CURRENT_LANGUAGE_ID', (int)$languageDetails['id']);
}

// language
define('SITE_LANGUAGE_DIRECTION', $languageDirection);

// theme paths
$siteTheme = SITE_CONFIG_SITE_THEME;
if((isset($_SESSION['_current_theme'])) && (strlen($_SESSION['_current_theme'])))
{
    $siteTheme = $_SESSION['_current_theme'];
}
define('SITE_THEME_DIRECTORY_NAME', 'themes');
define('SITE_THEME_DIRECTORY_ROOT', DOC_ROOT . '/' . SITE_THEME_DIRECTORY_NAME . '/');
define('SITE_THEME_WEB_ROOT', WEB_ROOT . "/" . SITE_THEME_DIRECTORY_NAME . "/");
define("SITE_THEME_PATH", SITE_THEME_WEB_ROOT . $siteTheme);
define("SITE_IMAGE_PATH", SITE_THEME_PATH . "/" . $languageImagePath . "images");
define("SITE_CSS_PATH", SITE_THEME_PATH . "/" . $languageImagePath . "styles");
define("SITE_JS_PATH", SITE_THEME_PATH . "/" . $languageImagePath . "js");
define("SITE_TEMPLATES_PATH", DOC_ROOT . "/themes/" . $siteTheme . "/templates");

// path to core ajax files
define("CORE_APPLICATION_WEB_ROOT", WEB_ROOT . "/core");
define("CORE_PAGE_WEB_ROOT", CORE_APPLICATION_WEB_ROOT . "/page");
define("CORE_AJAX_WEB_ROOT", CORE_PAGE_WEB_ROOT . "/ajax");

// file paths
define("CORE_PAGE_DIRECTORY_ROOT", CORE_ROOT . "/page");

// how often to update the download tracker in seconds.
define('DOWNLOAD_TRACKER_UPDATE_FREQUENCY', 15);

// how long to keep the download tracker data, in days
define('DOWNLOAD_TRACKER_PURGE_PERIOD', 7);

// admin paths
define('ADMIN_FOLDER_NAME', 'admin');
define('ADMIN_WEB_ROOT', WEB_ROOT . '/' . ADMIN_FOLDER_NAME);

// cache store
define('CACHE_DIRECTORY_NAME', 'cache');
define('CACHE_DIRECTORY_ROOT', CORE_ROOT . '/' . CACHE_DIRECTORY_NAME);
define('CACHE_WEB_ROOT', CORE_APPLICATION_WEB_ROOT . '/' . CACHE_DIRECTORY_NAME);

/* check for banned ip */
$bannedIP = bannedIP::getBannedType();
if (strtolower($bannedIP) == "whole site")
{
    header('HTTP/1.1 404 Not Found');
    die();
}

// load old user level constants
coreFunctions::setupOldPaymentConstants();

// setup demo mode
if (_CONFIG_DEMO_MODE == true)
{
    if (isset($_REQUEST['_p']))
    {
        $_SESSION['_plugins'] = false;
        if ((int) $_REQUEST['_p'] == 1)
        {
            $_SESSION['_plugins'] = true;
        }
        pluginHelper::loadPluginConfigurationFiles(true);
    }

    if (!isset($_SESSION['_plugins']))
    {
        $_SESSION['_plugins'] = false;
        pluginHelper::loadPluginConfigurationFiles(true);
    }
}

// load plugin configuration
pluginHelper::loadPluginConfigurationFiles();

// append any plugin includes, passing the page url
$url = isset($url)?$url:null;
pluginHelper::includeAppends('master.inc.php', array('url' => $url));
