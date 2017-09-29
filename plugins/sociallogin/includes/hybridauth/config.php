<?php

// includes
include_once(dirname(__FILE__).'/../../../../core/includes/master.inc.php');

// load plugin details
$pluginDetails  = pluginHelper::pluginSpecificConfiguration('sociallogin');
$pluginConfig   = $pluginDetails['config'];
$pluginSettings = json_decode($pluginDetails['data']['plugin_settings'], true);

$data               = array();
$data["base_url"]   = PLUGIN_WEB_ROOT . "/sociallogin/includes/hybridauth/";
$data["debug_mode"] = false;
$data["debug_file"] = '';

// disabled
$data["providers"]                          = array();
$data["providers"]["OpenID"]                = array();
$data["providers"]["OpenID"]["enabled"]     = false;

// optional
$data["providers"]["AOL"]            = array();
$data["providers"]["AOL"]["enabled"] = false;
if ((int) $pluginSettings['aol_enabled'] == 1)
{
    $data["providers"]["AOL"]["enabled"] = true;
}

$data["providers"]["Google"]            = array();
$data["providers"]["Google"]["enabled"] = false;
if ((int) $pluginSettings['google_enabled'] == 1)
{
    $data["providers"]["Google"]["enabled"] = true;
    $data["providers"]["Google"]["keys"]    = array("id"     => $pluginSettings['google_application_id'], "secret" => $pluginSettings['google_application_secret']);
}

$data["providers"]["Facebook"]            = array();
$data["providers"]["Facebook"]["enabled"] = false;
if ((int) $pluginSettings['facebook_enabled'] == 1)
{
    $data["providers"]["Facebook"]["enabled"] = true;
    $data["providers"]["Facebook"]["keys"]    = array("id"     => $pluginSettings['facebook_application_id'], "secret" => $pluginSettings['facebook_application_secret']);
	$data["providers"]["Facebook"]["scope"]   = 'email, user_about_me, user_birthday, user_hometown, user_website';
	$data["providers"]["Facebook"]["trustForwarded"]    = false;
}

$data["providers"]["Twitter"]            = array();
$data["providers"]["Twitter"]["enabled"] = false;
if ((int) $pluginSettings['twitter_enabled'] == 1)
{
    $data["providers"]["Twitter"]["enabled"] = true;
    $data["providers"]["Twitter"]["keys"]    = array("key"    => $pluginSettings['twitter_application_key'], "secret" => $pluginSettings['twitter_application_secret']);
}

$data["providers"]["Foursquare"]            = array();
$data["providers"]["Foursquare"]["enabled"] = false;
if ((int) $pluginSettings['foursquare_enabled'] == 1)
{
    $data["providers"]["Foursquare"]["enabled"] = true;
    $data["providers"]["Foursquare"]["keys"]    = array("id"     => $pluginSettings['foursquare_application_id'], "secret" => $pluginSettings['foursquare_application_secret']);
}

$data["providers"]["Instagram"]            = array();
$data["providers"]["Instagram"]["enabled"] = false;
if ((int) $pluginSettings['instagram_enabled'] == 1)
{
    $data["providers"]["Instagram"]["enabled"] = true;
    $data["providers"]["Instagram"]["keys"]    = array("id"    => $pluginSettings['instagram_application_key'], "secret" => $pluginSettings['instagram_application_secret']);
}

$data["providers"]["LinkedIn"]            = array();
$data["providers"]["LinkedIn"]["enabled"] = false;
if ((int) $pluginSettings['linkedin_enabled'] == 1)
{
    $data["providers"]["LinkedIn"]["enabled"] = true;
    $data["providers"]["LinkedIn"]["keys"]    = array("key"    => $pluginSettings['linkedin_application_key'], "secret" => $pluginSettings['linkedin_application_secret']);
}

return $data;