<?php

// setup result array
$rs = array();

// do login
$emailAddress         = $_POST["emailAddress"];
$forgotPasswordStatus = 'invalid';
$rs['error'] = '';

// initial validation
if (!strlen($emailAddress))
{
    // log failure
    $rs['error'] = t("please_enter_your_email_address", "Please enter the account email address");
}

if (strlen($rs['error']) == 0)
{
    $checkEmail = UserPeer::loadUserByEmailAddress($emailAddress);
    if (!$checkEmail)
    {
        // email exists
        $rs['error'] = t("account_not_found", "Account with that email address not found");
    }
}

$redirectUrl = '';
if (strlen($rs['error']) == 0)
{
    $userAccount = UserPeer::loadUserByEmailAddress($emailAddress);
    if ($userAccount)
    {
        // create password reset hash
        $resetHash = UserPeer::createPasswordResetHash($userAccount->id);

        $subject = t('forgot_password_email_subject', 'Password reset instructions for account on [[[SITE_NAME]]]', array('SITE_NAME' => SITE_CONFIG_SITE_NAME));

        $replacements   = array(
            'FIRST_NAME'     => $userAccount->firstname,
            'SITE_NAME'      => SITE_CONFIG_SITE_NAME,
            'WEB_ROOT'       => WEB_ROOT,
            'USERNAME'       => $userAccount->username,
            'PAGE_EXTENSION' => SITE_CONFIG_PAGE_EXTENSION,
            'ACCOUNT_ID'     => $userAccount->id,
            'RESET_HASH'     => $resetHash
        );
        $defaultContent = "Dear [[[FIRST_NAME]]],<br/><br/>";
        $defaultContent .= "We've received a request to reset your password on [[[SITE_NAME]]] for account [[[USERNAME]]]. Follow the url below to set a new account password:<br/><br/>";
        $defaultContent .= "<a href='[[[WEB_ROOT]]]/forgot_password_reset.[[[PAGE_EXTENSION]]]?u=[[[ACCOUNT_ID]]]&h=[[[RESET_HASH]]]'>[[[WEB_ROOT]]]/forgot_password_reset.[[[PAGE_EXTENSION]]]?u=[[[ACCOUNT_ID]]]&h=[[[RESET_HASH]]]</a><br/><br/>";
        $defaultContent .= "If you didn't request a password reset, just ignore this email and your existing password will continue to work.<br/><br/>";
        $defaultContent .= "Regards,<br/>";
        $defaultContent .= "[[[SITE_NAME]]] Admin";
        $htmlMsg        = t('forgot_password_email_content', $defaultContent, $replacements);

        coreFunctions::sendHtmlEmail($emailAddress, $subject, $htmlMsg, SITE_CONFIG_DEFAULT_EMAIL_ADDRESS_FROM, strip_tags(str_replace("<br/>", "\n", $htmlMsg)));
        $redirectUrl          = WEB_ROOT . "/forgot_password." . SITE_CONFIG_PAGE_EXTENSION . "?s=1&emailAddress=".urlencode($emailAddress);
        $forgotPasswordStatus = 'success';
    }
}

$rs['forgot_password_status'] = $forgotPasswordStatus;

// login success
if ($rs['forgot_password_status'] == 'success')
{
    // Set the redirect url after successful login
    $rs['redirect_url'] = $redirectUrl;
}

echo json_encode($rs);