<?php

// includes and security
include_once('../_local_auth.inc.php');

// import any new plugins as uninstalled
adminFunctions::registerPlugins();
$pluginConfigs = pluginHelper::getPluginConfiguration();

$sQL       = "SELECT * FROM plugin ORDER BY plugin_name";
$totalRS   = $db->getRows($sQL);
$limitedRS = $db->getRows($sQL);
$data      = array();
if (COUNT($limitedRS) > 0)
{
    foreach ($limitedRS AS $row)
    {
		// preload version number
		$configPath = PLUGIN_DIRECTORY_ROOT . $row['folder_name'] . '/_plugin_config.inc.php';
		$pluginVersion = 'NOT FOUND';
		if(file_exists($configPath))
		{
			include($configPath);
			$pluginVersion = 'v'.$pluginConfig['plugin_version'];
		}
		
        $lRow = array();

        $icon   = 'local';
        $lRow[] = '<img src="' . WEB_ROOT . '/plugins/' . $row['folder_name'] . '/assets/img/icons/16px.png" width="16" height="16" title="' . $row['plugin_name'] . '" alt="' . $row['plugin_name'] . '"/>';
        $lRow[] = (($row['is_installed'] == 1)?('<a href="' . PLUGIN_WEB_ROOT . '/' . $row['folder_name'] . '/admin/settings.php?id=' . $row['id'] . '">'):'').adminFunctions::makeSafe($row['plugin_name']).(($row['is_installed'] == 1)?'</a>':'').'<br/><span style="color: #777;">'.adminFunctions::makeSafe($row['plugin_description']).'</span>';
        $lRow[] = '/' . adminFunctions::makeSafe($row['folder_name']);
        $lRow[] = '<span class="statusText' . (($row['is_installed'] == 1) ? 'Yes' : 'No') . '">' . (($row['is_installed'] == 1) ? 'Yes' : 'No') . '</span>';
		$lRow[] = $pluginVersion;

        $links = array();
        if ($row['is_installed'] == 1)
        {
            // link in settings
            $settingsPath = PLUGIN_DIRECTORY_ROOT . $row['folder_name'] . '/admin/settings.php';
            if (file_exists($settingsPath))
            {
                $links[] = '<a href="' . PLUGIN_WEB_ROOT . '/' . $row['folder_name'] . '/admin/settings.php?id=' . $row['id'] . '">settings</a>';
            }
			
			// add any plugin specific links
            if (isset($pluginConfigs[$row{'folder_name'}]['config']['admin_settings']['plugin_manage_nav']))
            {
                foreach ($pluginConfigs[$row{'folder_name'}]['config']['admin_settings']['plugin_manage_nav'] AS $pluginLinks)
                {
                    $links[] = '<a href="' . PLUGIN_WEB_ROOT . '/' . $row['folder_name'] . '/' . adminFunctions::makeSafe($pluginLinks['link_url']) . '">' . strtolower(adminFunctions::makeSafe($pluginLinks['link_text'])) . '</a>';
                }
            }
			
			// uninstall link
			$links[] = '<a href="#" onClick="confirmUninstallPlugin(' . (int) $row['id'] . '); return false;" class="plugin_uninstall_'.$row['folder_name'].'">uninstall</a>';
        }
        elseif($pluginVersion != 'NOT FOUND')
        {
            $links[] = '<a href="#" onClick="confirmInstallPlugin(' . (int) $row['id'] . '); return false;" class="plugin_install_'.$row['folder_name'].'">install</a>';
        }
		
		if (($row['is_installed'] != 1) || ($pluginVersion == 'NOT FOUND'))
        {
			$links[] = '<a href="#" onClick="confirmDeletePlugin(' . (int) $row['id'] . '); return false;" class="plugin_delete_'.$row['folder_name'].'">delete</a>';
		}
        $lRow[] = implode(" <span class='plugin_option_divider'>|</span> ", $links);

        $data[] = $lRow;
    }
}

$resultArr                         = array();
$resultArr["sEcho"]                = intval($_GET['sEcho']);
$resultArr["iTotalRecords"]        = (int) COUNT($totalRS);
$resultArr["iTotalDisplayRecords"] = $resultArr["iTotalRecords"];
$resultArr["aaData"]               = $data;

echo json_encode($resultArr);