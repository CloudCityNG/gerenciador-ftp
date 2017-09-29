<?php

if(!isset($_SESSION['browse']['viewType']))
{
	$_SESSION['browse']['viewType'] = 'fileManagerIcon';
	if(SITE_CONFIG_FILE_MANAGER_DEFAULT_VIEW == 'list')
	{
		$_SESSION['browse']['viewType'] = 'fileManagerList';
	}
}

// update view in session
$viewType = trim($_REQUEST['viewType']);
if(in_array($viewType, array('fileManagerIcon', 'fileManagerList')))
{
	$_SESSION['browse']['viewType'] = $viewType;
}
