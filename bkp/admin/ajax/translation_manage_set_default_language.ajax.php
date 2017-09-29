<?php

// includes and security
include_once('../_local_auth.inc.php');

$defaultLanguage = $_REQUEST['defaultLanguage'];

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
    $db->query('UPDATE site_config SET config_value = :defaultLanguage WHERE config_key = \'site_language\' LIMIT 1', array('defaultLanguage' => $defaultLanguage));
    if ($db->affectedRows() == 1)
    {
        // make sure the language is active
        $db->query('UPDATE language SET isActive = 1 WHERE languageName = :languageName LIMIT 1', array('languageName' => $defaultLanguage));
        
        $result['error'] = false;
        $result['msg']   = '\'' . $defaultLanguage . '\' set as the default language.';
    }
    else
    {
        $result['error'] = true;
        $result['msg']   = 'Could not set the default language, please try again later.';
    }
}

echo json_encode($result);
exit;
