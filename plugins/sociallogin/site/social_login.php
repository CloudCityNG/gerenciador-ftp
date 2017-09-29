<?php

require_once('../../../core/includes/master.inc.php');

$config = PLUGIN_DIRECTORY_ROOT . 'sociallogin/includes/hybridauth/config.php';
require_once(PLUGIN_DIRECTORY_ROOT . 'sociallogin/includes/hybridauth/Hybrid/Auth.php');

if (isset($_GET["provider"]) && $_GET["provider"])
{
    try
    {
        // create an instance for Hybridauth with the configuration file path as parameter
        $hybridauth = new Hybrid_Auth($config);

        // set selected provider name 
        $provider = @trim(strip_tags($_GET["provider"]));

        // try to authenticate the selected $provider
        if(!isset($_REQUEST['_ck']))
        {
            $adapter = $hybridauth->authenticate($provider);
        }
    }
    catch (Exception $e)
    {
        // In case we have errors 6 or 7, then we have to use Hybrid_Provider_Adapter::logout() to 
        // let hybridauth forget all about the user so we can try to authenticate again.
        // Display the recived error, 
        // to know more please refer to Exceptions handling section on the userguide
        switch ($e->getCode())
        {
            case 0 : $error = t('plugin_sociallogin_unspecified_error', 'Unspecified error');
                break;
            case 1 : $error = t('plugin_sociallogin_hybriauth_configuration_error', 'Hybriauth configuration error');
                break;
            case 2 : $error = t('plugin_sociallogin_provider_not_properly_configured', 'Provider not properly configured');
                break;
            case 3 : $error = t('plugin_sociallogin_unknown_or_disabled_provider', 'Unknown or disabled provider');
                break;
            case 4 : $error = t('plugin_sociallogin_missing_provider_application_credentials', 'Missing provider application credentials');
                break;
            case 5 : $error = t('plugin_sociallogin_authentication_failed_the_user_has_canceled_the_authentication_or_the_provider_refused_the_connection', 'Authentication failed. The user has canceled the authentication or the provider refused the connection');
                break;
            case 6 : $error = t('plugin_sociallogin_user_profile_request_failed_most_likely_the_user_is_not_connected_to_the_provider_and_he_should_to_authenticate_again', 'User profile request failed. Most likely the user is not connected to the provider and he should to authenticate again');
                $adapter->logout();
                break;
            case 7 : $error = t('plugin_sociallogin_user_not_connected_to_the_provider', 'User not connected to the provider');
                $adapter->logout();
                break;
        }

        // well, basically your should not display this to the end user, just give him a hint and move on..
        //$error .= "<br /><br /><b>Original error message:</b> " . $e->getMessage();
        //$error .= "<hr /><pre>Trace:<br />" . $e->getTraceAsString() . "</pre>";
    }
}

$Auth = Auth::getAuth();
if($Auth->loggedIn())
{
    coreFunctions::redirect(coreFunctions::getCoreSitePath().'/account_home.'.SITE_CONFIG_PAGE_EXTENSION);
}

if(isset($_REQUEST['error_description']))
{
    $error = $_REQUEST['error_description'];
}
coreFunctions::redirect(coreFunctions::getCoreSitePath()."/login.".SITE_CONFIG_PAGE_EXTENSION."?plugin_social_login_error=".urlencode($error));
