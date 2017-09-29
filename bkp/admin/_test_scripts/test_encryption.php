<?php

// setup includes
require_once('../_local_auth.inc.php');

if(!function_exists('openssl_encrypt'))
{
	print ("openssl_encrypt() cannot be found. Make sure it is installed.");
	exit;
}

$rawValue = _CONFIG_SITE_HOST_URL;
echo "Value to be encrypted: ".$rawValue."<br/><br/>";

$encrypted = coreFunctions::encryptValue($rawValue);
echo "Encrypted: ".$encrypted."<br/><br/>";

$decrypted = coreFunctions::decryptValue($encrypted);
echo "Decrypted: ".$decrypted."<br/><br/>";
