<?php

// core plugin config
$pluginConfig = array();
$pluginConfig['plugin_name']             = 'File Previewer';
$pluginConfig['folder_name']             = 'filepreviewer';
$pluginConfig['plugin_description']      = 'Display files directly within the file manager.';
$pluginConfig['plugin_version']          = 2;
$pluginConfig['required_script_version'] = "4.2";
$pluginConfig['database_sql']            = 'offline/database.sql';

// resizing sizes
$pluginConfig['fixedSizes'] = array('16x16', '32x32', '64x64', '125x125', '180x150', '250x250', '300x250', '120x240', '160x600', '500x500', '800x800');
$pluginConfig['scaledPercentages'] = array('10', '25', '35', '50', '70', '85');