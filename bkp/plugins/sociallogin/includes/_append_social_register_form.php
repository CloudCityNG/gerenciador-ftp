<?php
// load hybridauth
$config = PLUGIN_DIRECTORY_ROOT . 'sociallogin/includes/hybridauth/config.php';
require_once(PLUGIN_DIRECTORY_ROOT . 'sociallogin/includes/hybridauth/Hybrid/Auth.php');

// load plugin details
$pluginDetails  = pluginHelper::pluginSpecificConfiguration('sociallogin');
$pluginConfig   = $pluginDetails['config'];
$pluginSettings = json_decode($pluginDetails['data']['plugin_settings'], true);
?>
<link rel="stylesheet" href="<?php echo PLUGIN_WEB_ROOT; ?>/<?php echo $pluginConfig['folder_name']; ?>/assets/css/styles.css" type="text/css" charset="utf-8" />
<style type="text/css">
body { 
	background-color: #fff !important;
}
.pluginSocialLoginButtons, .pluginSocialLoginbuttons a {
	font-weight:normal;
	font-size:12px;	
	font-family:'Lucida Grande', Tahoma, sans-serif;
	padding-bottom:-2px;
}

.pluginSocialMainLoginWrapper .pluginSocialLoginSignin .pluginSocialLoginButtons .zocial {
    width: 320px;
    background-image: inherit;
    line-height: 2.3em;
    margin-bottom: 11px;
}

.pluginSocialMainLoginWrapper .pluginSocialLoginSignin .pluginSocialLoginButtons .zocial, .pluginSocialMainLoginWrapper .pluginSocialLoginButtons a.zocial {
    color: #ffffff;
    font: inherit;
    text-align: left;
    text-shadow: inherit;
    box-shadow: inherit;
    border-color: transparent;
}

.pluginSocialMainLoginWrapper .pluginSocialLoginSignin .pluginSocialLoginButtons .zocial::before {
    height: 32px;
    margin: 0 0.7em 0 0;
    padding: 0.2em 0.6em;
    width: 30px;
}

.pluginSocialMainLoginWrapper .pluginSocialLoginSignin
{
	padding-top: 14px;
}
</style>
<div class="pluginSocialMainLoginWrapper">
    <div class="pluginSocialLoginDivider">
        &nbsp;
    </div>
    <div class="clear"><!-- --></div>

    <div id="pageHeader">
        <h2><?php echo t("plugin_sociallogin_social_login", "Social Login"); ?></h2>
    </div>
	<div>
        <p class="introText">
            <?php echo t("plugin_sociallogin_social_register_intro_text", "Use your existing social network account to register securely below."); ?>
        </p>

        <div class="pluginSocialLoginSignin">
            <span class="fieldWrapper">
                <div class="clear"><!-- --></div>
                <div class="pluginSocialLoginButtons">
                    <?php if((int)$pluginSettings['facebook_enabled'] == 1): ?>
                    <a href="<?php echo PLUGIN_WEB_ROOT; ?>/<?php echo $pluginConfig['folder_name']; ?>/site/social_login.php?provider=Facebook" class="zocial facebook"><span>Register with Facebook</span></a>
                    <?php endif; ?>
                    
                    <?php if((int)$pluginSettings['twitter_enabled'] == 1): ?>
                    <a href="<?php echo PLUGIN_WEB_ROOT; ?>/<?php echo $pluginConfig['folder_name']; ?>/site/social_login.php?provider=Twitter" class="zocial twitter"><span>Register with Twitter</span></a>
                    <?php endif; ?>
                    
                    <?php if((int)$pluginSettings['google_enabled'] == 1): ?>
                    <a href="<?php echo PLUGIN_WEB_ROOT; ?>/<?php echo $pluginConfig['folder_name']; ?>/site/social_login.php?provider=Google" class="zocial googleplus"><span>Register with Google</span></a>
                    <?php endif; ?>
                    
                    <?php if((int)$pluginSettings['aol_enabled'] == 1): ?>
                    <a href="<?php echo PLUGIN_WEB_ROOT; ?>/<?php echo $pluginConfig['folder_name']; ?>/site/social_login.php?provider=AOL" class="zocial aol"><span>Register with AOL</span></a>
                    <?php endif; ?>
                    
                    <?php if((int)$pluginSettings['instagram_enabled'] == 1): ?>
                    <a href="<?php echo PLUGIN_WEB_ROOT; ?>/<?php echo $pluginConfig['folder_name']; ?>/site/social_login.php?provider=Instagram" class="zocial instagram"><span>Register with Instagram</span></a>
                    <?php endif; ?>
                    
                    <?php if((int)$pluginSettings['foursquare_enabled'] == 1): ?>
                    <a href="<?php echo PLUGIN_WEB_ROOT; ?>/<?php echo $pluginConfig['folder_name']; ?>/site/social_login.php?provider=Foursquare" class="zocial foursquare"><span>Register with Foursquare</span></a>
                    <?php endif; ?>
                    
                    <?php if((int)$pluginSettings['linkedin_enabled'] == 1): ?>
                    <a href="<?php echo PLUGIN_WEB_ROOT; ?>/<?php echo $pluginConfig['folder_name']; ?>/site/social_login.php?provider=LinkedIn" class="zocial linkedin"><span>Register with LinkedIn</span></a>
                    <?php endif; ?>
                    <div class="clear"><!-- --></div>
                </div>
            </span>
            <div class="clear"><!-- --></div>
        </div>
    </div>
</div>
