<?php

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
        // override logout link
        $user_data = (array)unserialize($_SESSION['socialData']);
        if(strlen($user_data['photoURL']))
        {
            $params['photoURL'] = $user_data['photoURL'];
        }
    }
}
