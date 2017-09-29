<?php
// initial constants
define('ADMIN_SELECTED_PAGE', 'plugins');
define('ADMIN_SELECTED_SUB_PAGE', 'plugin_manage');

// includes and security
include_once('../../../core/includes/master.inc.php');
include_once(DOC_ROOT . '/' . ADMIN_FOLDER_NAME . '/_local_auth.inc.php');

// load plugin details
$pluginId = (int) $_REQUEST['id'];
$plugin   = $db->getRow("SELECT * FROM plugin WHERE id = " . (int) $pluginId . " LIMIT 1");
if (!$plugin)
{
    adminFunctions::redirect(ADMIN_WEB_ROOT . '/plugin_manage.php?error=' . urlencode('There was a problem loading the plugin details.'));
}
define('ADMIN_PAGE_TITLE', $plugin['plugin_name'] . ' Plugin Settings');

// prepare variables
$plugin_enabled                 = (int) $plugin['plugin_enabled'];
$facebook_enabled               = 0;
$facebook_application_id        = '';
$facebook_application_secret    = '';
$twitter_enabled                = 0;
$twitter_application_key        = '';
$twitter_application_secret     = '';
$google_enabled                 = 0;
$google_application_id          = '';
$google_application_secret      = '';
$aol_enabled                    = 0;
$instagram_enabled              = 0;
$instagram_application_key      = '';
$instagram_application_secret   = '';
$foursquare_enabled            = 0;
$foursquare_application_id     = '';
$foursquare_application_secret = '';
$linkedin_enabled               = 0;
$linkedin_application_key       = '';
$linkedin_application_secret    = '';

// load existing settings
if (strlen($plugin['plugin_settings']))
{
    $plugin_settings = json_decode($plugin['plugin_settings'], true);
    if ($plugin_settings)
    {
        $facebook_enabled           = $plugin_settings['facebook_enabled'];
        $facebook_application_id    = $plugin_settings['facebook_application_id'];
        $twitter_enabled            = $plugin_settings['twitter_enabled'];
        $twitter_application_key    = $plugin_settings['twitter_application_key'];
        $google_enabled             = $plugin_settings['google_enabled'];
        $google_application_id      = $plugin_settings['google_application_id'];
        $aol_enabled                = $plugin_settings['aol_enabled'];
        $instagram_enabled          = $plugin_settings['instagram_enabled'];
        $instagram_application_key  = $plugin_settings['instagram_application_key'];
        $foursquare_enabled        = $plugin_settings['foursquare_enabled'];
        $foursquare_application_id = $plugin_settings['foursquare_application_id'];
        $linkedin_enabled           = $plugin_settings['linkedin_enabled'];
        $linkedin_application_key   = $plugin_settings['linkedin_application_key'];

        // hide secret keys in demo mode
        if (_CONFIG_DEMO_MODE == true)
        {
            $twitter_application_secret     = '[hidden]';
            $google_application_secret      = '[hidden]';
            $instagram_application_secret   = '[hidden]';
            $foursquare_application_secret = '[hidden]';
            $facebook_application_secret    = '[hidden]';
            $linkedin_application_secret    = '[hidden]';
        }
        else
        {
            $twitter_application_secret     = $plugin_settings['twitter_application_secret'];
            $google_application_secret      = $plugin_settings['google_application_secret'];
            $instagram_application_secret   = $plugin_settings['instagram_application_secret'];
            $foursquare_application_secret = $plugin_settings['foursquare_application_secret'];
            $facebook_application_secret    = $plugin_settings['facebook_application_secret'];
            $linkedin_application_secret    = $plugin_settings['linkedin_application_secret'];
        }
    }
}

// handle page submissions
if (isset($_REQUEST['submitted']))
{
    // get variables
    $plugin_enabled                 = (int) $_REQUEST['plugin_enabled'];
    $plugin_enabled                 = $plugin_enabled != 1 ? 0 : 1;
    $facebook_enabled               = (int) $_REQUEST['facebook_enabled'];
    $facebook_application_id        = trim($_REQUEST['facebook_application_id']);
    $facebook_application_secret    = trim($_REQUEST['facebook_application_secret']);
    $twitter_enabled                = (int) $_REQUEST['twitter_enabled'];
    $twitter_application_key        = trim($_REQUEST['twitter_application_key']);
    $twitter_application_secret     = trim($_REQUEST['twitter_application_secret']);
    $google_enabled                 = (int) $_REQUEST['google_enabled'];
    $google_application_id          = trim($_REQUEST['google_application_id']);
    $google_application_secret      = trim($_REQUEST['google_application_secret']);
    $aol_enabled                    = (int) $_REQUEST['aol_enabled'];
    $instagram_enabled              = (int) $_REQUEST['instagram_enabled'];
    $instagram_application_key      = trim($_REQUEST['instagram_application_key']);
    $instagram_application_secret   = trim($_REQUEST['instagram_application_secret']);
    $foursquare_enabled            = (int) $_REQUEST['foursquare_enabled'];
    $foursquare_application_id     = trim($_REQUEST['foursquare_application_id']);
    $foursquare_application_secret = trim($_REQUEST['foursquare_application_secret']);
    $linkedin_enabled               = (int) $_REQUEST['linkedin_enabled'];
    $linkedin_application_key       = trim($_REQUEST['linkedin_application_key']);
    $linkedin_application_secret    = trim($_REQUEST['linkedin_application_secret']);

    // validate submission
    if (_CONFIG_DEMO_MODE == true)
    {
        adminFunctions::setError(adminFunctions::t("no_changes_in_demo_mode"));
    }
    elseif ($facebook_enabled == 1)
    {
        // validation
        if (strlen($facebook_application_id) == 0)
        {
            adminFunctions::setError(adminFunctions::t("plugin_sociallogin_set_facebook_application_id", "Please set the Facebook application id."));
        }
        elseif (strlen($facebook_application_secret) == 0)
        {
            adminFunctions::setError(adminFunctions::t("plugin_sociallogin_set_facebook_application_secret", "Please set the Facebook application secret."));
        }
    }
    elseif ($twitter_enabled == 1)
    {
        // validation
        if (strlen($twitter_application_key) == 0)
        {
            adminFunctions::setError(adminFunctions::t("plugin_sociallogin_set_twitter_application_key", "Please set the Twitter application key."));
        }
        elseif (strlen($twitter_application_secret) == 0)
        {
            adminFunctions::setError(adminFunctions::t("plugin_sociallogin_set_twitter_application_secret", "Please set the Twitter application secret."));
        }
    }
    elseif ($google_enabled == 1)
    {
        // validation
        if (strlen($google_application_id) == 0)
        {
            adminFunctions::setError(adminFunctions::t("plugin_sociallogin_set_google_application_id", "Please set the Google application id."));
        }
        elseif (strlen($google_application_secret) == 0)
        {
            adminFunctions::setError(adminFunctions::t("plugin_sociallogin_set_google_application_secret", "Please set the Google application secret."));
        }
    }
    elseif ($instagram_enabled == 1)
    {
        // validation
        if (strlen($instagram_application_key) == 0)
        {
            adminFunctions::setError(adminFunctions::t("plugin_sociallogin_set_instagram_application_key", "Please set the Instagram application key."));
        }
        elseif (strlen($instagram_application_secret) == 0)
        {
            adminFunctions::setError(adminFunctions::t("plugin_sociallogin_set_instagram_application_secret", "Please set the Instagram application secret."));
        }
    }
    elseif ($foursquare_enabled == 1)
    {
        // validation
        if (strlen($foursquare_application_id) == 0)
        {
            adminFunctions::setError(adminFunctions::t("plugin_sociallogin_set_foursquare_application_id", "Please set the Disqus application id."));
        }
        elseif (strlen($foursquare_application_secret) == 0)
        {
            adminFunctions::setError(adminFunctions::t("plugin_sociallogin_set_foursquare_application_secret", "Please set the Disqus application secret."));
        }
    }
    elseif ($linkedin_enabled == 1)
    {
        // validation
        if (strlen($linkedin_application_key) == 0)
        {
            adminFunctions::setError(adminFunctions::t("plugin_sociallogin_set_linkedin_application_key", "Please set the LinkedIn application key."));
        }
        elseif (strlen($linkedin_application_secret) == 0)
        {
            adminFunctions::setError(adminFunctions::t("plugin_sociallogin_set_linkedin_application_secret", "Please set the LinkedIn application secret."));
        }
    }

    // update the settings
    if (adminFunctions::isErrors() == false)
    {
        // compile new settings
        $settingsArr                                   = array();
        $settingsArr['facebook_enabled']               = $facebook_enabled;
        $settingsArr['facebook_application_id']        = $facebook_application_id;
        $settingsArr['facebook_application_secret']    = $facebook_application_secret;
        $settingsArr['twitter_enabled']                = $twitter_enabled;
        $settingsArr['twitter_application_key']        = $twitter_application_key;
        $settingsArr['twitter_application_secret']     = $twitter_application_secret;
        $settingsArr['google_enabled']                 = $google_enabled;
        $settingsArr['google_application_id']          = $google_application_id;
        $settingsArr['google_application_secret']      = $google_application_secret;
        $settingsArr['aol_enabled']                    = $aol_enabled;
        $settingsArr['instagram_enabled']              = $instagram_enabled;
        $settingsArr['instagram_application_key']      = $instagram_application_key;
        $settingsArr['instagram_application_secret']   = $instagram_application_secret;
        $settingsArr['foursquare_enabled']            = $foursquare_enabled;
        $settingsArr['foursquare_application_id']     = $foursquare_application_id;
        $settingsArr['foursquare_application_secret'] = $foursquare_application_secret;
        $settingsArr['linkedin_enabled']               = $linkedin_enabled;
        $settingsArr['linkedin_application_key']       = $linkedin_application_key;
        $settingsArr['linkedin_application_secret']    = $linkedin_application_secret;
        $settings                                      = json_encode($settingsArr);

        // update the user
        $dbUpdate                  = new DBObject("plugin", array("plugin_enabled", "plugin_settings"), 'id');
        $dbUpdate->plugin_enabled  = $plugin_enabled;
        $dbUpdate->plugin_settings = $settings;
        $dbUpdate->id              = $pluginId;
        $dbUpdate->update();

        // update plugin config
        pluginHelper::loadPluginConfigurationFiles(true);
        adminFunctions::setSuccess('Plugin settings updated.');
    }
}

// check for curl
if(function_exists('curl_version') == false)
{
    adminFunctions::setError(adminFunctions::t("plugin_sociallogin_curl_required", "Could not find Curl functions in your PHP configuration. Please contact your host to enable Curl otherwise this plugin wont work."));
}

// page header
include_once(ADMIN_ROOT . '/_header.inc.php');
?>

<script>
    $(document).ready(function() {
        $(".socialToggle").each(function(index) {
            toggleSocial(this);
        });
    });

    function toggleSocial(ele)
    {
        if ($(ele).val() == 1)
        {
            $(ele).parents('.form').find('.socialToggleWrapper').show();
        }
        else
        {
            $(ele).parents('.form').find('.socialToggleWrapper').hide();
        }
    }
</script>

<div class="row clearfix">
    <div class="col_12">
        <div class="sectionLargeIcon" style="background: url(../assets/img/icons/128px.png) no-repeat;"></div>
        <div class="widget clearfix">
            <h2>Plugin Settings</h2>
            <div class="widget_inside">
                <?php echo adminFunctions::compileNotifications(); ?>
                <form method="POST" action="settings.php" name="pluginForm" id="pluginForm" autocomplete="off">
                    <div class="clearfix col_12">
                        <div class="col_4">
                            <h3>Plugin State</h3>
                            <p>Whether the social login plugin is enabled.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>Plugin Enabled:</label>
                                    <div class="input">
                                        <select name="plugin_enabled" id="plugin_enabled" class="medium validate[required]">
                                            <?php
                                            $enabledOptions = array(0 => 'No', 1 => 'Yes');
                                            foreach ($enabledOptions AS $k => $enabledOption)
                                            {
                                                echo '<option value="' . $k . '"';
                                                if ($plugin_enabled == $k)
                                                {
                                                    echo ' SELECTED';
                                                }
                                                echo '>' . $enabledOption . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- FACEBOOK -->
                    <div class="clearfix col_12 social_login_facebook">
                        <div class="col_4">
                            <h3>Facebook</h3>
                            <p>Whether to allow Facebook logins and your API details.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>Enable Facebook:</label>
                                    <div class="input">
                                        <select name="facebook_enabled" id="facebook_enabled" class="medium socialToggle" onChange="toggleSocial(this);
        return false;">
                                                    <?php
                                                    $enabledOptions = array(0 => 'No', 1 => 'Yes');
                                                    foreach ($enabledOptions AS $k => $enabledOption)
                                                    {
                                                        echo '<option value="' . $k . '"';
                                                        if ($facebook_enabled == $k)
                                                        {
                                                            echo ' SELECTED';
                                                        }
                                                        echo '>' . $enabledOption . '</option>';
                                                    }
                                                    ?>
                                        </select>
                                    </div>
                                </div>
                                <span class="socialToggleWrapper">
                                    <div class="clearfix">
                                        <label>App ID:</label>
                                        <div class="input">
                                            <input id="facebook_application_id" name="facebook_application_id" type="text" class="large" value="<?php echo adminFunctions::makeSafe($facebook_application_id); ?>"/>
                                        </div>
                                    </div>
                                    <div class="clearfix alt-highlight">
                                        <label>App Secret:</label>
                                        <div class="input">
                                            <input id="facebook_application_secret" name="facebook_application_secret" type="text" class="large" value="<?php echo adminFunctions::makeSafe($facebook_application_secret); ?>"/>
                                        </div>
                                    </div>
                                    <div class="clearfix">
                                        <div style="margin: 8px;">
                                            <strong>Facebook API Access:</strong><br/>
                                            <br/>
                                            1. Go to <a href="https://developers.facebook.com/apps" target="_blank">https://developers.facebook.com/apps</a> and create a new application.<br/>
                                            <br/>
                                            2. Fill out any required fields such as the application name and description.<br/>
                                            <br/>
                                            3. Confirm the initial application settings and open the new application for editing.<br/>
                                            <br/>
                                            4. On the left navigation click 'Settings', then '+ Add Platform'.<br/>
                                            <br/>
                                            5. Click on 'Website'. Set 'Site Url' as:<br/>
                                            <br/><code><?php echo WEB_ROOT; ?></code><br/>
                                            <br/>
                                            6. Set 'Contact Email' as your website email address. Save changes.<br/>
                                            <br/>
                                            7. Click on 'Status & Review' and set 'Do you want to make this app and all its live features available to the general public?' to 'YES'.<br/>
                                            <br/>
                                            8. Save changes. Once you have finished, copy and paste the created application credentials above. They can be found on the app 'Dashboard'.
                                        </div>
                                    </div>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- TWITTER -->
                    <div class="clearfix col_12 social_login_twitter">
                        <div class="col_4">
                            <h3>Twitter</h3>
                            <p>Whether to allow Twitter logins and your API details.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>Enable Twitter:</label>
                                    <div class="input">
                                        <select name="twitter_enabled" id="twitter_enabled" class="medium socialToggle" onChange="toggleSocial(this);
        return false;">
                                                    <?php
                                                    $enabledOptions = array(0 => 'No', 1 => 'Yes');
                                                    foreach ($enabledOptions AS $k => $enabledOption)
                                                    {
                                                        echo '<option value="' . $k . '"';
                                                        if ($twitter_enabled == $k)
                                                        {
                                                            echo ' SELECTED';
                                                        }
                                                        echo '>' . $enabledOption . '</option>';
                                                    }
                                                    ?>
                                        </select>
                                    </div>
                                </div>
                                <span class="socialToggleWrapper">
                                    <div class="clearfix">
                                        <label>Consumer Key:</label>
                                        <div class="input">
                                            <input id="twitter_application_key" name="twitter_application_key" type="text" class="large" value="<?php echo adminFunctions::makeSafe($twitter_application_key); ?>"/>
                                        </div>
                                    </div>
                                    <div class="clearfix alt-highlight">
                                        <label>Consumer Secret:</label>
                                        <div class="input">
                                            <input id="twitter_application_secret" name="twitter_application_secret" type="text" class="large" value="<?php echo adminFunctions::makeSafe($twitter_application_secret); ?>"/>
                                        </div>
                                    </div>
                                    <div class="clearfix">
                                        <div style="margin: 8px;">
                                            <strong>Twitter API Access:</strong><br/>
                                            <br/>
                                            1. Go to <a href="https://dev.twitter.com/apps" target="_blank">https://dev.twitter.com/apps</a> and create a new application.<br/>
                                            <br/>
                                            2. Fill out any required fields such as the application name and description.<br/>
                                            <br/>
                                            3. Put the 'Website' as:<br/>
                                            <br/><code><?php echo WEB_ROOT; ?></code><br/>
                                            <br/>
                                            4. Put the 'Callback URL' as:<br/>
                                            <br/><code><?php echo PLUGIN_WEB_ROOT; ?>/sociallogin/includes/hybridauth/?hauth.done=Twitter</code><br/>
                                            <br/>
                                            5. Agree the terms and click 'create application' button.<br/>
                                            <br/>
                                            6. Once created, open the 'Settings' tab of the application. Set the 'Application Type' to 'Read, Write and Access direct messages'.<br/>
                                            <br/>
                                            7. Click 'Update' and copy &amp; paste the created application credentials above.
                                        </div>
                                    </div>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- GOOGLE -->
                    <div class="clearfix col_12 social_login_google">
                        <div class="col_4">
                            <h3>Google</h3>
                            <p>Whether to allow Google logins and your API details.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>Enable Google:</label>
                                    <div class="input">
                                        <select name="google_enabled" id="google_enabled" class="medium socialToggle" onChange="toggleSocial(this);
        return false;">
                                                    <?php
                                                    $enabledOptions = array(0 => 'No', 1 => 'Yes');
                                                    foreach ($enabledOptions AS $k => $enabledOption)
                                                    {
                                                        echo '<option value="' . $k . '"';
                                                        if ($google_enabled == $k)
                                                        {
                                                            echo ' SELECTED';
                                                        }
                                                        echo '>' . $enabledOption . '</option>';
                                                    }
                                                    ?>
                                        </select>
                                    </div>
                                </div>
                                <span class="socialToggleWrapper">
                                    <div class="clearfix">
                                        <label>Client ID:</label>
                                        <div class="input">
                                            <input id="google_application_id" name="google_application_id" type="text" class="large" value="<?php echo adminFunctions::makeSafe($google_application_id); ?>"/>
                                        </div>
                                    </div>
                                    <div class="clearfix alt-highlight">
                                        <label>Client Secret</label>
                                        <div class="input">
                                            <input id="google_application_secret" name="google_application_secret" type="text" class="large" value="<?php echo adminFunctions::makeSafe($google_application_secret); ?>"/>
                                        </div>
                                    </div>
                                    <div class="clearfix">
                                        <div style="margin: 8px;">
                                            <strong>Google API Access:</strong><br/>
                                            <br/>
                                            1. Go to <a href="https://code.google.com/apis/console/" target="_blank">https://code.google.com/apis/console/</a> and create a new application.<br/>
                                            <br/>
                                            2. Fill out any required fields such as the application name and description.<br/>
                                            <br/>
                                            3. On the "Create Client ID" popup switch to advanced settings by clicking on (more options).<br/>
                                            <br/>
                                            4. Provide this URL as the 'Redirect URIs:' for your application:<br/>
                                            <br/><code><?php echo PLUGIN_WEB_ROOT; ?>/sociallogin/includes/hybridauth/?hauth.done=Google</code><br/>
                                            <br/>
                                            5. Once you have finished, copy and paste the created application credentials above.
                                        </div>
                                    </div>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- AOL -->
                    <div class="clearfix col_12 social_login_aol">
                        <div class="col_4">
                            <h3>AOL</h3>
                            <p>Whether to allow AOL logins. No API settings needed.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>Enable AOL:</label>
                                    <div class="input">
                                        <select name="aol_enabled" id="aol_enabled" class="medium socialToggle">
                                            <?php
                                            $enabledOptions = array(0 => 'No', 1 => 'Yes');
                                            foreach ($enabledOptions AS $k => $enabledOption)
                                            {
                                                echo '<option value="' . $k . '"';
                                                if ($aol_enabled == $k)
                                                {
                                                    echo ' SELECTED';
                                                }
                                                echo '>' . $enabledOption . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- INSTAGRAM -->
                    <div class="clearfix col_12 social_login_instagram">
                        <div class="col_4">
                            <h3>Instagram</h3>
                            <p>Whether to allow Instagram logins and your API details.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>Enable Instagram:</label>
                                    <div class="input">
                                        <select name="instagram_enabled" id="instagram_enabled" class="medium socialToggle" onChange="toggleSocial(this);
        return false;">
                                                    <?php
                                                    $enabledOptions = array(0 => 'No', 1 => 'Yes');
                                                    foreach ($enabledOptions AS $k => $enabledOption)
                                                    {
                                                        echo '<option value="' . $k . '"';
                                                        if ($instagram_enabled == $k)
                                                        {
                                                            echo ' SELECTED';
                                                        }
                                                        echo '>' . $enabledOption . '</option>';
                                                    }
                                                    ?>
                                        </select>
                                    </div>
                                </div>
                                <span class="socialToggleWrapper">
                                    <div class="clearfix">
                                        <label>Client Id:</label>
                                        <div class="input">
                                            <input id="instagram_application_key" name="instagram_application_key" type="text" class="large" value="<?php echo adminFunctions::makeSafe($instagram_application_key); ?>"/>
                                        </div>
                                    </div>
                                    <div class="clearfix alt-highlight">
                                        <label>Client Secret:</label>
                                        <div class="input">
                                            <input id="instagram_application_secret" name="instagram_application_secret" type="text" class="large" value="<?php echo adminFunctions::makeSafe($instagram_application_secret); ?>"/>
                                        </div>
                                    </div>
                                    <div class="clearfix">
                                        <div style="margin: 8px;">
                                            <strong>Instagram API Access:</strong><br/>
                                            <br/>
                                            Go to <a href="http://instagram.com/developer/clients/manage/" target="_blank">http://instagram.com/developer/clients/manage/</a> and create a new application (Register new Client).<br/>
                                            <br/>
                                            2. Fill out any required fields such as the application name and description.<br/>
                                            <br/>
                                            3. Provide the following url as the 'Website':<br/>
                                            <br/><code><?php echo WEB_ROOT; ?></code><br/>
                                            <br/>
                                            4. Provide the following url as the 'OAuth redirect_uri' (callback url):<br/>
                                            <br/><code><?php echo PLUGIN_WEB_ROOT; ?>/sociallogin/includes/hybridauth/?hauth.done=Instagram</code><br/>
                                            <br/>
                                            5. Once you have finished, copy and paste the created application credentials above.
                                        </div>
                                    </div>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- DISQUS -->
                    <div class="clearfix col_12 social_login_foursquare">
                        <div class="col_4">
                            <h3>Foursquare</h3>
                            <p>Whether to allow Foursquare logins and your API details.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>Enable Foursquare:</label>
                                    <div class="input">
                                        <select name="foursquare_enabled" id="foursquare_enabled" class="medium socialToggle" onChange="toggleSocial(this);
        return false;">
                                                    <?php
                                                    $enabledOptions = array(0 => 'No', 1 => 'Yes');
                                                    foreach ($enabledOptions AS $k => $enabledOption)
                                                    {
                                                        echo '<option value="' . $k . '"';
                                                        if ($foursquare_enabled == $k)
                                                        {
                                                            echo ' SELECTED';
                                                        }
                                                        echo '>' . $enabledOption . '</option>';
                                                    }
                                                    ?>
                                        </select>
                                    </div>
                                </div>
                                <span class="socialToggleWrapper">
                                    <div class="clearfix">
                                        <label>Client ID:</label>
                                        <div class="input">
                                            <input id="foursquare_application_id" name="foursquare_application_id" type="text" class="large" value="<?php echo adminFunctions::makeSafe($foursquare_application_id); ?>"/>
                                        </div>
                                    </div>
                                    <div class="clearfix alt-highlight">
                                        <label>Client Secret:</label>
                                        <div class="input">
                                            <input id="foursquare_application_secret" name="foursquare_application_secret" type="text" class="large" value="<?php echo adminFunctions::makeSafe($foursquare_application_secret); ?>"/>
                                        </div>
                                    </div>
                                    <div class="clearfix">
                                        <div style="margin: 8px;">
                                            <strong>Foursquare API Access:</strong><br/>
                                            <br/>
                                            1. Go to <a href="https://foursquare.com/developers/register" target="_blank">https://foursquare.com/developers/register</a> and create a new application.<br/>
                                            <br/>
                                            2. Fill out any required fields such as the application name and description.<br/>
                                            <br/>
                                            3. Set 'Download / welcome page url' as:<br/>
                                            <br/><code><?php echo WEB_ROOT; ?></code><br/>
                                            <br/>
                                            4. Set 'Redirect URI(s)' as:<br/>
                                            <br/><code><?php echo PLUGIN_WEB_ROOT; ?>/sociallogin/includes/hybridauth/?hauth.done=Foursquare</code></code><br/>
                                            <br/>
                                            5. Save changes. Once you have finished, copy and paste the created application credentials above.
                                        </div>
                                    </div>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- LINKEDIN -->
                    <div class="clearfix col_12 social_login_linkedin">
                        <div class="col_4">
                            <h3>LinkedIn</h3>
                            <p>Whether to allow LinkedIn logins and your API details.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>Enable LinkedIn:</label>
                                    <div class="input">
                                        <select name="linkedin_enabled" id="linkedin_enabled" class="medium socialToggle" onChange="toggleSocial(this);
        return false;">
                                                    <?php
                                                    $enabledOptions = array(0 => 'No', 1 => 'Yes');
                                                    foreach ($enabledOptions AS $k => $enabledOption)
                                                    {
                                                        echo '<option value="' . $k . '"';
                                                        if ($linkedin_enabled == $k)
                                                        {
                                                            echo ' SELECTED';
                                                        }
                                                        echo '>' . $enabledOption . '</option>';
                                                    }
                                                    ?>
                                        </select>
                                    </div>
                                </div>
                                <span class="socialToggleWrapper">
                                    <div class="clearfix">
                                        <label>API Key:</label>
                                        <div class="input">
                                            <input id="linkedin_application_key" name="linkedin_application_key" type="text" class="large" value="<?php echo adminFunctions::makeSafe($linkedin_application_key); ?>"/>
                                        </div>
                                    </div>
                                    <div class="clearfix alt-highlight">
                                        <label>Secret Key:</label>
                                        <div class="input">
                                            <input id="linkedin_application_secret" name="linkedin_application_secret" type="text" class="large" value="<?php echo adminFunctions::makeSafe($linkedin_application_secret); ?>"/>
                                        </div>
                                    </div>
                                    <div class="clearfix">
                                        <div style="margin: 8px;">
                                            <strong>LinkedIn API Access:</strong><br/>
                                            <br/>
                                            1. Go to <a href="https://www.linkedin.com/secure/developer" target="_blank">https://www.linkedin.com/secure/developer</a> and create a new application.<br/>
                                            <br/>
                                            2. Fill out any required fields such as the application name and description.<br/>
                                            <br/>
                                            3. Put the following url in the 'Website URL' field:<br/>
                                            <br/><code><?php echo WEB_ROOT; ?></code><br/>
                                            <br/>
                                            4. Set the 'Default Scope' to 'r_basicprofile' & 'r_emailaddress'.<br/>
                                            <br/>
                                            5. Once you have finished, copy and paste the created application credentials above.
                                        </div>
                                    </div>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="clearfix col_12">
                        <div class="col_4 adminResponsiveHide">&nbsp;</div>
                        <div class="col_8 last">
                            <div class="clearfix">
                                <div class="input no-label">
                                    <input type="submit" value="Submit" class="button blue">
                                    <input type="reset" value="Cancel" class="button" onClick="window.location='<?php echo ADMIN_WEB_ROOT; ?>/plugin_manage.php';"/>
                                </div>
                            </div>
                        </div>
                    </div>

                    <input name="submitted" type="hidden" value="1"/>
                    <input name="id" type="hidden" value="<?php echo $pluginId; ?>"/>
                </form>
            </div>
        </div>   
    </div>
</div>

<?php
include_once(ADMIN_ROOT . '/_footer.inc.php');
?>