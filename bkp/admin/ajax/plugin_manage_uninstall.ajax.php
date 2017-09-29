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
    $plugin = $db->getRow('SELECT id, plugin_name, folder_name, is_installed FROM plugin WHERE id = ' . (int) $plugin_id . ' LIMIT 1');
    if (!is_array($plugin))
    {
        $result['error'] = true;
        $result['msg']   = adminFunctions::t("could_not_locate_plugin", "Could not locate plugin within the database, please try again later.");
    }
    elseif ($plugin['is_installed'] == 0)
    {
        $result['error'] = true;
        $result['msg']   = adminFunctions::t("plugin_already_uninstalled", "The plugin you've selected has already been uninstalled.");
    }
    else
    {
        // install plugin
        $pluginPath      = PLUGIN_DIRECTORY_ROOT . $plugin['folder_name'] . '/';
        $pluginClassFile = $pluginPath . 'plugin' . UCFirst(strtolower($plugin['folder_name'])) . '.class.php';
        $pluginClassName = 'Plugin' . UCFirst(strtolower($plugin['folder_name']));

        // make sure we have the main class file
        if (!file_exists($pluginClassFile))
        {
			// failed loading the config, just set the record as uninstalled
			$db->getRow('UPDATE plugin SET is_installed = 0 WHERE id = ' . (int) $plugin['id'] . ' LIMIT 1');
			
			// error reporting
            $result['msg']   = adminFunctions::t("plugin_code_not_found_error", "Could not locate the plugin code within the plugins folder, although we have marked the plugin as uninstalled within the database.");
			echo json_encode($result);
			exit;
        }
        else
        {
            try
            {
                // include the plugin code
                include_once($pluginClassFile);
                
                // create an instance of the plugin
                $instance = new $pluginClassName();

                // call the install method
                $instance->uninstall();
            }
            catch (Exception $e)
            {
                $result['error'] = true;
                $result['msg']   = "Exception: " . $e->getMessage();
            }
        }

        if($result['error'] == false)
        {
            $result['msg']   = 'Plugin \'' . $plugin['plugin_name'] . '\' successfully uninstalled.';
        }
    }
}

echo json_encode($result);
exit;
