<?php

// get variables
$fileId = (int)$_REQUEST['fileId'];

// load file
$file = file::loadById($fileId);
if(!$file)
{
	// exit
	coreFunctions::output404();
}

// make sure the logged in user owns this file
if($file->userId != $Auth->id)
{
	// exit
	coreFunctions::output404();
}

// load folder/folder
$folder = fileFolder::loadById($file->folderId);
if(!$folder)
{
	// exit
	coreFunctions::output404();
}

if(coreFunctions::inDemoMode() == true)
{
	$returnJson = array();
	$returnJson['success'] = false;
	$returnJson['msg'] = t("no_changes_in_demo_mode");

	echo json_encode($returnJson);
	exit;
}

// update cover id
$folder->setCoverId($file->id);

// prepare result
$returnJson = array();
$returnJson['success'] = true;
$returnJson['msg'] = t('account_set_cover_image_success', 'Cover image updated.');

echo json_encode($returnJson);