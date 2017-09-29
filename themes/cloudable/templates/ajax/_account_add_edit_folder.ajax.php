<?php

// require login
$Auth->requireUser(WEB_ROOT . '/login.' . SITE_CONFIG_PAGE_EXTENSION);

// setup database
$db = Database::getDatabase(true);

// load folder structure as array
$folderListing = fileFolder::loadAllForSelect($Auth->id);

// initial parent folder
$parentId = '-1';
if(isset($_REQUEST['parentId']))
{
    $parentId = (int)$_REQUEST['parentId'];
}

// defaults
$isPublic = 1;
$editFolderId = null;
$accessPassword = null;
$watermarkPreviews = 0;
$showDownloadLinks = 1;
if((int)$_REQUEST['editFolderId'])
{
    // load existing folder data
    $fileFolder = fileFolder::loadById((int)$_REQUEST['editFolderId']);
    if ($fileFolder)
    {
		// load the folder url
		$pageUrl = $fileFolder->getFolderUrl();
		
        // check current user has permission to edit the fileFolder
        if ($fileFolder->userId == $Auth->id)
        {
            // setup edit folder
            $editFolderId = $fileFolder->id;
            $folderName = $fileFolder->folderName;
            $parentId = $fileFolder->parentId;
            $isPublic = $fileFolder->isPublic;
            $accessPassword = $fileFolder->accessPassword;
			$watermarkPreviews = (int)$fileFolder->watermarkPreviews;
			$showDownloadLinks = (int)$fileFolder->showDownloadLinks;
        }
    }
}

$userIsPublic   = 1;
$folderIsPublic = 1;
$globalPublic   = 1;

if(coreFunctions::getUserPublicStatus($Auth->id) === false)
{
    $userIsPublic = 0;
}

if(corefunctions::getUserFoldersPublicStatus($editFolderId) === false || corefunctions::getUserFoldersPublicStatus($parentId) === false)
{
    $folderIsPublic = 0;
}

if(corefunctions::getOverallSitePrivacyStatus() === false)
{
    $globalPublic = 0;
}

?>

<form action="<?php echo WEB_ROOT; ?>/ajax/_account_add_edit_folder.process.ajax.php" autocomplete="off">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title"><?php echo $editFolderId==null?t("add_folder", "add folder"):(t("edit_existing_folder", "Edit Existing Folder").' ('.validation::safeOutputToScreen($fileFolder->folderName).')'); ?></h4>
    </div>

    <div class="modal-body">
        <div class="row">
		
			<div class="col-md-3">
				<?php
				$icon = 'edit';
				if($editFolderId == null)
				{
					$icon = 'plus';
				}
				?>
				<div class="modal-icon-left"><img src="<?php echo SITE_IMAGE_PATH; ?>/modal_icons/folder_yellow_<?php echo $icon; ?>.png"/></div>
			</div>
			
			<div class="col-md-9">				
				<div class="row">
					<div class="col-md-12">
						<div class="form-group">
							<label for="folderName" class="control-label"><?php echo t("edit_folder_name", "Folder Name:"); ?></label>
							<input type="text" class="form-control" name="folderName" id="folderName" value="<?php echo isset($folderName) ? validation::safeOutputToScreen($folderName) : ''; ?>"/>
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-md-12">
						<div class="form-group">
							<label for="parentId" class="control-label"><?php echo t('edit_folder_parent_folder', 'Parent Folder:'); ?></label>
							<select class="form-control" name="parentId" id="parentId">
								<option value="-1"><?php echo t('_none_', '- none -'); ?></option>
								<?php
								$currentFolderStr = $editFolderId!==null?$folderListing[$editFolderId]:0;
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
									if ($parentId == (int) $k)
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
					<div class="col-md-6">
						<div class="form-group">
							<label for="isPublic" class="control-label"><?php echo t('edit_folder_privacy', 'Folder Privacy:'); ?></label>            
							<select class="form-control" name="isPublic" id="isPublic">
								<?php if($userIsPublic != 0): ?>
								<option value="1" <?php echo ($isPublic == 1) ? 'SELECTED' : ''; ?>><?php echo t('privacy_public_limited_access', 'Public - Access if users know the folder url.'); ?></option>
								<?php endif; ?>
								<option value="0" <?php echo ($isPublic == 0) ? 'SELECTED' : ''; ?>><?php echo t('privacy_private_no_access', 'Private - No access outside of your account, unless you generate a unique access url.'); ?></option>
							</select>
						</div>
					</div>
					<div class="col-md-6">
						<div class="form-group">
							<label for="accessPassword" class="control-label"><?php echo t("edit_folder_optional_password", "Optional Password:"); ?></label>
							<div class="row">
								<div class="col-md-2 inline-checkbox">
									<input type="checkbox" name="enablePassword" id="enablePassword" value="1" <?php echo strlen($accessPassword)?'CHECKED':''; ?> onClick="toggleFolderPasswordField();">
								</div>
								<div class="col-md-10">
									<input type="password" class="form-control" name="password" id="password" autocomplete="off"<?php echo strlen($accessPassword)?' value="**********"':''; ?> <?php echo strlen($accessPassword)?'':'READONLY'; ?>/>
								</div>
							</div>
						</div>
					</div>
					
					<?php if($userIsPublic == 0): ?>
					<div class="col-md-12">
						<div class="form-group">
							<p>
							<?php
							 echo t('edit_folder_privacy_notice_note', 'Note: You can not update this folder privacy settings as your account settings are set to make all files private, or the parent folder is set to private.');
							?>
							</p>
						</div>
					</div>
					<?php endif; ?>
				</div>
				
				<div class="row">
					<div class="col-md-4">
						<div class="form-group">
							<label for="watermarkPreviews" class="control-label"><?php echo t('edit_folder_watermark_image_previews', 'Watermark Image Previews:'); ?> *</label>            
							<select class="form-control" name="watermarkPreviews" id="watermarkPreviews">
								<option value="1" <?php echo ($watermarkPreviews == 1) ? 'SELECTED' : ''; ?>><?php echo t('option_yes', 'Yes'); ?></option>
								<option value="0" <?php echo ($watermarkPreviews == 0) ? 'SELECTED' : ''; ?>><?php echo t('option_no', 'No'); ?></option>
							</select>
						</div>
					</div>
					<div class="col-md-4">
						<div class="form-group">
							<label for="showDownloadLinks" class="control-label"><?php echo t('edit_folder_allow_download_links', 'Allow Downloading When Shared:'); ?></label>            
							<select class="form-control" name="showDownloadLinks" id="showDownloadLinks">
								<option value="1" <?php echo ($showDownloadLinks == 1) ? 'SELECTED' : ''; ?>><?php echo t('option_yes', 'Yes'); ?></option>
								<option value="0" <?php echo ($showDownloadLinks == 0) ? 'SELECTED' : ''; ?>><?php echo t('option_no', 'No'); ?></option>
							</select>
						</div>
					</div>
					<div class="col-md-4"></div>
				</div>
				
				<div class="row">
					<div class="col-md-12">
						<div class="form-group">
							<p style="color: #aaa;">
							<?php
							 echo t('edit_folder_watermark_notice_extra', '* You can set or update your watermark via your <a href="[[[WEB_ROOT]]]/account_edit.html">account settings</a> page. The original images will not be watermarked.', array('WEB_ROOT'=>WEB_ROOT));
							?>
							</p>
						</div>
					</div>
				</div>
			</div>
		</div>
    </div>

    <div class="modal-footer">
        <input type="hidden" name="submitme" id="submitme" value="1"/>
        <?php if($editFolderId !== null): ?>
        <input type="hidden" value="<?php echo (int) $editFolderId; ?>" name="editFolderId"/>
        <?php endif; ?>
        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo t("cancel", "cancel"); ?></button>
        <button type="button" class="btn btn-info" onClick="processAjaxForm(this, function(data) { <?php if($editFolderId == null): ?>setUploaderFolderList(data['folder_listing_html']);loadImages(data['folder_id']);<?php endif; ?> refreshFolderListing(false);$('.modal').modal('hide'); updateStatsViaAjax(); }); return false;"><?php echo $editFolderId==null?t("add_folder", "add folder"):t("update_folder", "update folder"); ?> <i class="entypo-check"></i></button>
    </div>
</form>