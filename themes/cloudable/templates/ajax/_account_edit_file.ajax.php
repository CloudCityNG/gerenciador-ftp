<?php

// require login
$Auth->requireUser(WEB_ROOT . '/login.' . SITE_CONFIG_PAGE_EXTENSION);

// setup database
$db = Database::getDatabase(true);

// load file
$fileId = (int)$_REQUEST['fileId'];
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

// load the image url
$pageUrl = $file->getFullShortUrl();

// load folder structure as array
$folderListing = fileFolder::loadAllForSelect($Auth->id);

?>

<form action="<?php echo WEB_ROOT; ?>/ajax/_account_edit_file.process.ajax.php" autocomplete="off">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title"><?php echo t("edit_existing_item", "Edit Existing Item"); ?> (<?php echo validation::safeOutputToScreen($file->originalFilename, null, 55); ?>)</h4>
    </div>

    <div class="modal-body">
		<div class="row">
			
			<div class="col-md-3">
				<div class="modal-icon-left"><img src="<?php echo SITE_IMAGE_PATH; ?>/modal_icons/document_edit.png"/></div>
			</div>
			
			<div class="col-md-9">
	
				<div class="row">
					<div class="col-md-12">
						<div class="form-group">
							<label for="folderName" class="control-label"><?php echo t('edit_image_sharing_url', 'Sharing Url:'); ?></label>
							<div class="input-group">
								<input type="text" class="form-control" value="<?php echo validation::safeOutputToScreen($pageUrl); ?>" readonly/>
								<span class="input-group-btn">
									<button type="button" class="btn btn-primary" onClick="window.open('<?php echo validation::safeOutputToScreen($pageUrl); ?>'); return false;"><i class="entypo-link"></i></button>
								</span>
							</div>
						</div>
					</div>
				</div>
				
				<div class="row">
					<div class="col-md-12">
						<div class="form-group">
							<label for="filename" class="control-label"><?php echo UCWords(t("filename", "filename")); ?></label>
							<input type="text" class="form-control" name="filename" id="filename" value="<?php echo validation::safeOutputToScreen($file->getFilenameExcExtension()); ?>"/>
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-md-12">
						<div class="form-group">
							<label for="folder" class="control-label"><?php echo UCWords(t("file_folder", "file folder")); ?></label>
							<select class="form-control" name="folder" id="folder">
								<option value=""><?php echo t('_default_', '- Default -'); ?></option>
								<?php
								foreach ($folderListing AS $k => $folderListingItem)
								{
									if($editFolderId !== null)
									{
										// ignore this folder and any children
										if(substr($folderListingItem, 0, strlen($currentFolderStr)) == $currentFolderStr)
										{
											continue;
										}
									}
									
									echo '<option value="' . (int) $k . '"';
									if ($file->folderId == (int) $k)
									{
										echo ' SELECTED';
									}
									echo '>' . validation::safeOutputToScreen($folderListingItem) . '</option>';
								}
								?>
							</select>
						</div>
					</div>
				</div>
				
				<div class="row">
					<div class="col-md-4">
						<div class="form-group">
							<label for="reset_stats" class="control-label"><?php echo UCWords(t("reset_stats", "reset stats")); ?></label>
							<select class="form-control" name="reset_stats" id="reset_stats">
								<option value="0" SELECTED><?php echo t('no_keep_stats', 'No, keep stats'); ?></option>
								<option value="1"><?php echo t('yes_remove_stats', 'Yes, remove stats'); ?></option>
							</select>
						</div>
					</div>
				</div>
			</div>
		</div>
    </div>

    <div class="modal-footer">
        <input type="hidden" name="submitme" id="submitme" value="1"/>
        <input type="hidden" value="<?php echo (int) $fileId; ?>" name="fileId"/>
        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo t("cancel", "cancel"); ?></button>
        <button type="button" class="btn btn-info" onClick="processAjaxForm(this, function() { reloadPreviousAjax(); $('.modal').modal('hide'); }); return false;"><?php echo UCWords(t("update_item", "update item")); ?> <i class="entypo-check"></i></button>
    </div>
</form>