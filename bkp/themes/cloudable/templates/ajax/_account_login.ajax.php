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
        $rs['error'] = t("login_ip_banned", "Você foi impedido temporariamente de fazer logon devido a muitas tentativas de login falhadas. Por favor, tente novamente [[[EXPIRY_TIME]]].", array('EXPIRY_TIME' => ($bannedIp['banExpiry'] != null ? coreFunctions::formatDate($bannedIp['banExpiry']) : t('later', 'later'))));
    }
}

// initial validation
if (strlen($rs['error']) == 0)
{
    if (!strlen($loginUsername))
    {
        // log failure
        Auth::logFailedLoginAttempt(coreFunctions::getUsersIPAddress(), $loginUsername);

        $rs['error'] = t("please_enter_your_username", "Por favor insira seu nome de usuário");
    }
    elseif (!strlen($loginPassword))
    {
        // log failure
        Auth::logFailedLoginAttempt(coreFunctions::getUsersIPAddress(), $loginUsername);

        $rs['error'] = t("please_enter_your_password", "Por favor, insira sua senha");
    }
}

// check for mcrypt, required for login
if (strlen($rs['error']) == 0)
{
    if(!function_exists('mcrypt_create_iv'))
    {
        $rs['error'] = t("mcrypt_not_found", "Funções do Mcypt não encontradas no PHP, por favor solicite suporte para instalar e tente novamente.");
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
        $rs['error'] = t("username_and_password_is_invalid", "Seu nome de usuário e senha são inválidos");
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