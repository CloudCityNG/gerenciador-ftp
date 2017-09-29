<?php

// setup result array
$rs = array();

// do login
$password             = $_POST["password"];
$confirmPassword      = $_POST["confirmPassword"];
$userId               = $_POST["u"];
$passwordHash         = $_POST["h"];
$rs['forgot_password_status'] = 'invalid';
$rs['error'] = '';

// check for pending hash
$user = UserPeer::loadUserByPasswordResetHash($passwordHash);
if (!$user)
{
    $rs['error'] = t("general_error", "General error");
    echo json_encode($rs);
    exit;
}

// check user id passed is valid
if ($user->id != $userId)
{
    $rs['error'] = t("general_error", "General error");
    echo json_encode($rs);
    exit;
}

// initial validation
if (!strlen($password))
{
    // log failure
    $rs['error'] = t("please_enter_your_password", "Please enter your new password");
}
elseif ($password != $confirmPassword)
{
    $rs['error'] = t("password_confirmation_does_not_match", "Your password confirmation does not match");
}

if (strlen($rs['error']) == 0)
{
	$passValid = passwordPolicy::validatePassword($password);
	if(is_array($passValid))
	{
		$rs['error'] = implode('<br/>', $passValid);
	}
}

$redirectUrl = '';
if (strlen($rs['error']) == 0)
{
    // update password
    $db = Database::getDatabase(true);
    $db->query('UPDATE users SET passwordResetHash = "", password = :password WHERE id = :id', array('password' => Password::createHash($password), 'id'       => $userId));

    // success
    $success = true;

    $redirectUrl          = WEB_ROOT . "/login." . SITE_CONFIG_PAGE_EXTENSION . "?s=1";
    $rs['forgot_password_status'] = 'success';
}

// login success
if ($rs['forgot_password_status'] == 'success')
{
    // Set the redirect url after successful login
    $rs['redirect_url'] = $redirectUrl;
}

echo json_encode($rs);