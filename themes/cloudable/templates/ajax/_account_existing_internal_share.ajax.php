<?php

// require login
$Auth->requireUser(WEB_ROOT . '/login.' . SITE_CONFIG_PAGE_EXTENSION);

// setup database
$db = Database::getDatabase(true);

// load existing folder data
$fileFolder = fileFolder::loadById((int)$_REQUEST['folderId']);
if ($fileFolder)
{
	// load the folder url
	$pageUrl = $fileFolder->getFolderUrl();
	
	// check current user has permission to access the fileFolder
	if ($fileFolder->userId != $Auth->id)
	{
		// setup edit folder
		die('No access permitted.');
	}
}

// get list of shares
$folderShares = $fileFolder->getAllSharedUsers();
if(COUNT($folderShares))
{
	?>
	<p><?php echo t('edit_folder_internal_share_existing_intro', 'Existing Shares:'); ?></p>
	<table class="table table-striped table-bordered">
		<thead>
			<th><?php echo t('edit_folder_internal_share_email', 'Registered Email Address:'); ?></th>
			<th class="center" style="width: 140px;"><?php echo t('edit_folder_internal_access_level', 'Access Level:'); ?></th>
			<th class="center" style="width: 50px;"></th>
		</thead>
		<tbody>
			<?php foreach($folderShares AS $folderShare): ?>
			<tr>
				<td><?php echo validation::safeOutputToScreen($folderShare['email']); ?></td>
				<td class="center"><?php echo validation::safeOutputToScreen(t('folder_share_permission_'.$folderShare['share_permission_level'], UCWords(str_replace('_', ' ', $folderShare['share_permission_level'])))); ?></td>
				<td class="center"><a href="#" onClick="shareFolderInternallyRemove(<?php echo $folderShare['id']; ?>);"><span class="glyphicon glyphicon-remove text-danger"></span></a></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php
}
?>