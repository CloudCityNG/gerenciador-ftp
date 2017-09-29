<?php
// setup includes
require_once('../../core/includes/master.inc.php');

// setup Auth
$Auth = Auth::getAuth();

// pick up request
if(trim($_REQUEST['theme']))
{
    $themeFolderName = trim($_REQUEST['theme']);
    
    // if admin user or in demo mode
    if(($Auth->level_id >= 20) || (_CONFIG_DEMO_MODE == true))
    {
        // get database
        $db = Database::getDatabase();
        
        // make sure theme exists in the database
        $exists = $db->getValue('SELECT id FROM theme WHERE folder_name = '.$db->quote($themeFolderName).' LIMIT 1');
        if($exists)
        {
            $_SESSION['_current_theme'] = $themeFolderName;
        }
    }
}

// redirect to homepage
coreFunctions::redirect(WEB_ROOT);