<?php

// setup includes
require_once('../_local_auth.inc.php');

// prepare the variables
$rawPassword = "newpassword";

// create the password hash and output to screen
echo Password::createHash($rawPassword);