<?php

// setup result array
$rs = array();

// do login
$loginUsername = $_POST["username"];
$loginPassword = $_POST["password"];
$loginStatus   = 'invalid';
$rs['error'] = '';

// clear any expired IPs
bannedIP::clearExpiredBannedIps();

// check user isn't banned from logging in
$bannedIp = bannedIP::getBannedIPData();
if ($bannedIp)
{
    if ($bannedIp['banType'] == 'Login')
    {
        $rs['error'] = t("login_ip_banned", "You have been temporarily blocked from logging in due to too many failed login attempts. Please try again [[[EXPIRY_TIME]]].", array('EXPIRY_TIME' => ($bannedIp['banExpiry'] != null ? coreFunctions::formatDate($bannedIp['banExpiry']) : t('later', 'later'))));
    }
}

// initial validation
if (strlen($rs['error']) == 0)
{
    if (!strlen($loginUsername))
    {
        // log failure
        Auth::logFailedLoginAttempt(coreFunctions::getUsersIPAddress(), $loginUsername);

        $rs['error'] = t("please_enter_your_username", "Please enter your username");
    }
    elseif (!strlen($loginPassword))
    {
        // log failure
        Auth::logFailedLoginAttempt(coreFunctions::getUsersIPAddress(), $loginUsername);

        $rs['error'] = t("please_enter_your_password", "Please enter your password");
    }
}

// check for mcrypt, required for login
if (strlen($rs['error']) == 0)
{
    if(!function_exists('mcrypt_create_iv'))
    {
        $rs['error'] = t("mcrypt_not_found", "Mcypt functions not found within PHP, please ask support to install and try again.");
    }
}

$redirectUrl = '';
if (strlen($rs['error']) == 0)
{
    $loginResult = $Auth->login($loginUsername, $loginPassword, true);
    if ($loginResult)
    {
        // if we know the file
        if (isset($_POST['loginShortUrl']))
        {
            // download file
            $file = file::loadByShortUrl(trim($_POST['loginShortUrl']));
            if ($file)
            {
                $redirectUrl = $file->getFullShortUrl();
            }
        }
        else
        {
            // successful login
            $redirectUrl = coreFunctions::getCoreSitePath() . '/index.' . SITE_CONFIG_PAGE_EXTENSION;
        }

        $loginStatus = 'success';
    }
    else
    {
        // login failed
        $rs['error'] = t("username_and_password_is_invalid", "Your username and password are invalid");
    }
}

$rs['login_status'] = $loginStatus;

// login success
if ($rs['login_status'] == 'success')
{
    // Set the redirect url after successful login
    $rs['redirect_url'] = $redirectUrl;
}

echo json_encode($rs);