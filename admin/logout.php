<?php
define('ADMIN_IGNORE_LOGIN', true);
require_once('_local_auth.inc.php');
$Auth->logout();
coreFunctions::redirect('login.php');
exit;