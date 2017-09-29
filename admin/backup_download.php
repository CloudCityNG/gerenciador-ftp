<?php

include_once('_local_auth.inc.php');
if (_CONFIG_DEMO_MODE == true)
{
	die(adminFunctions::t("no_changes_in_demo_mode"));
}

// setup the backup object for later
$backup = new backup();
$backupPath = $backup->getBackupPath();

// get params
$path = $_REQUEST['path'];
$path = str_replace(array('..'), '', $path);
$fullBackupPath = $backupPath.'/'.$path;

// some security
$fullBackupPath = realpath($fullBackupPath);
if($backupPath != substr($fullBackupPath, 0, strlen($backupPath)))
{
	exit;
}

header("Pragma: public");
header("Expires: 0"); 
header("Cache-Control: must-revalidate"); 
header("Cache-Control: private", false); // required for certain browsers 
header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment; filename=".$path); 
readfile($fullBackupPath);
