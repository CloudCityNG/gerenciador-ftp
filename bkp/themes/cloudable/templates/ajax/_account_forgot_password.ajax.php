<?php

// setup result array
$rs = array();

// do login
$emailAddress         = $_POST["emailAddress"];
$forgotPasswordStatus = 'inválido';
$rs['error'] = '';

// initial validation
if (!strlen($emailAddress))
{
    // log failure
    $rs['error'] = t("please_enter_your_email_address", "Digite o endereço de e-mail da conta");
}

if (strlen($rs['error']) == 0)
{
    $checkEmail = UserPeer::loadUserByEmailAddress($emailAddress);
    if (!$checkEmail)
    {
        // email exists
        $rs['error'] = t("account_not_found", "Conta com esse endereço de e-mail não encontrado");
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

        $subject = t('forgot_password_email_subject', 'Instruções de reinicialização de senha para conta em [[[SITE_NAME]]]', array('SITE_NAME' => SITE_CONFIG_SITE_NAME));

        $replacements   = array(
            'FIRST_NAME'     => $userAccount->firstname,
            'SITE_NAME'      => SITE_CONFIG_SITE_NAME,
            'WEB_ROOT'       => WEB_ROOT,
            'USERNAME'       => $userAccount->username,
            'PAGE_EXTENSION' => SITE_CONFIG_PAGE_EXTENSION,
            'ACCOUNT_ID'     => $userAccount->id,
            'RESET_HASH'     => $resetHash
        );
        $defaultContent = "Caro [[[FIRST_NAME]]],<br/><br/>";
        $defaultContent .= "Recebemos um pedido para redefinir sua senha em [[[SITE_NAME]]] para conta [[[USERNAME]]]. Siga a url abaixo para definir uma nova senha da conta:<br/><br/>";
        $defaultContent .= "<a href='[[[WEB_ROOT]]]/forgot_password_reset.[[[PAGE_EXTENSION]]]?u=[[[ACCOUNT_ID]]]&h=[[[RESET_HASH]]]'>[[[WEB_ROOT]]]/forgot_password_reset.[[[PAGE_EXTENSION]]]?u=[[[ACCOUNT_ID]]]&h=[[[RESET_HASH]]]</a><br/><br/>";
        $defaultContent .= "Se você não solicitou uma reinicialização da senha, ignore este e-mail e sua senha existente continuará a funcionar.<br/><br/>";
        $defaultContent .= "Saudações,<br/>";
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