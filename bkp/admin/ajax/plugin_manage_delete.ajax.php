<?php

// includes and security
include_once('../_local_auth.inc.php');

$plugin_id = (int) $_REQUEST['plugin_id'];

// prepare result
$result = array();
$result['error'] = false;
$result['msg']   = '';

// validate submission
if ($plugin_id == 0)
{
    $result['error'] = true;
    $result['msg']   = adminFunctions::t("plugin_id_not_found", "Plugin id not found.");
}
elseif (_CONFIG_DEMO_MODE == true)
{
    $result['error'] = true;
    $result['msg']   = adminFunctions::t("no_changes_in_demo_mode");
}

if (strlen($result['msg']) == 0)
{
    $plugin = $db->getRow('SELECT * FROM plugin WHERE id = ' . (int) $plugin_id . ' LIMIT 1');
    if (!is_array($plugin))
    {
        $result['error'] = true;
        $result['msg']   = adminFunctions::t("could_not_locate_plugin", "Could not locate plugin within the database, please try again later.");
    }
    elseif($plugin['is_installed'] == 1)
    {
        $result['error'] = true;
        $result['msg'] = adminfunctions::t('uninstall_plugin_before_deleting', 'Please uninstall the plugin before deleting.');   
    }
    elseif(strlen($result['msg']) == 0)
    {
        // Delete the plugin
        $pluginPath = realpath(PLUGIN_DIRECTORY_ROOT . $plugin['folder_name']);
        if (file_exists($pluginPath))
        {
            if(adminFunctions::recursiveDelete($pluginPath) == false)
            {
                if(!rmdir($pluginPath))
                {
                    $result['error'] = true;
                    $result['msg'] = adminfunctions::t('Could_not_delete_some_plugin_files', 'Could not delete some files, please delete them manually.');
                } 
            }
        }        
        if(strlen($result['msg']) == 0)
        {
            $db->query("DELETE FROM plugin WHERE id = '".$plugin_id."'");
            $result['msg'] = adminfunctions::t('plugin_successfully_deleted', 'Plugin successfully deleted.');
        }
    }
}

echo json_encode($result);
exit;
