<?php

// includes and security
include_once('../_local_auth.inc.php');

$enText  	= trim($_REQUEST['enText']);
$toLangCode	= trim($_REQUEST['toLangCode']);

// prepare result
$result = array();
$result['error'] = false;
$result['msg']   = '';

if (_CONFIG_DEMO_MODE == true)
{
    $result['error'] = true;
    $result['msg']   = adminFunctions::t("no_changes_in_demo_mode");
}
else
{
	$googleTranslate = new googleTranslate($toLangCode);
	$translation = $googleTranslate->translate($enText);
    if ($translation != false)
    {
        $result['error'] 		= false;
        $result['msg']   		= 'Text successfully translated.';
		$result['translation']  = $translation;
    }
    else
    {
        $result['error'] = true;
        $result['msg']   = $googleTranslate->getError();
    }
}

echo json_encode($result);
exit;
