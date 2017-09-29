<?php
// load hybridauth
$config = PLUGIN_DIRECTORY_ROOT . 'sociallogin/includes/hybridauth/config.php';
require_once(PLUGIN_DIRECTORY_ROOT . 'sociallogin/includes/hybridauth/Hybrid/Auth.php');

// load plugin details
$pluginDetails  = pluginHelper::pluginSpecificConfiguration('sociallogin');
$pluginConfig   = $pluginDetails['config'];
$pluginSettings = json_decode($pluginDetails['data']['plugin_settings'], true);

// only if user is logged in
$Auth = Auth::getAuth();
if ($Auth->loggedIn() == true)
{
    if(isset($_SESSION['socialLogin']))
    {
        // remove settings link
        unset($params['settings']);
        $logoutLink = '';
        
        // override logout link
        $user_data = (array)unserialize($_SESSION['socialData']);
        $logoutLink = '<a href="'.coreFunctions::getCoreSitePath().'/logout.'.SITE_CONFIG_PAGE_EXTENSION.'">'.t('logout', 'logout').'</a>';
        if(strlen($user_data['photoURL']))
        {
            $imageText = t('plugin_sociallogin_logged_in_as', 'Logged in as').' \''.$user_data['displayName'].'\' '.t('plugin_sociallogin_via', 'via').' '.UCWords($_SESSION['socialProvider']);
            $logoutLink .= '&nbsp;&nbsp;<span class="pluginSocialLoginProfileThumb"><a href="'.coreFunctions::getCoreSitePath().'/account_home.'.SITE_CONFIG_PAGE_EXTENSION.'"><img src="'.$user_data['photoURL'].'" style="width: 24px; height: 24px;" title="'.  validation::safeOutputToScreen($imageText).'"/></a></span>';
        }
        elseif(strlen($user_data['displayName']))
        {
            $logoutLink .= '&nbsp;('.validation::safeOutputToScreen($user_data['displayName'], null, 20).')';
        }
        $params['logout'] = $logoutLink;
    }
}
?>
