<?php

// only if user is logged in
$Auth = Auth::getAuth();
$config    = PLUGIN_DIRECTORY_ROOT . 'sociallogin/includes/hybridauth/config.php';
require_once(PLUGIN_DIRECTORY_ROOT . 'sociallogin/includes/hybridauth/Hybrid/Auth.php');
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
        $params['logout'] = $logoutLink;
    }
}
?>
