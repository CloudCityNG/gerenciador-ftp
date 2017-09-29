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

// privacy check
$isPublic = true;
$shareLink = $pageUrl;
if(coreFunctions::getOverallPublicStatus(0, $fileFolder->id) == false)
{
	$isPublic = false;
	$shareLink = 'SHARE_LINK';
}

define('SHARE_URLS_TEMPLATE', '<!-- just add href= for your links, like this: -->
	<a href="https://www.facebook.com/sharer/sharer.php?u='.validation::safeOutputToScreen($shareLink).'" data-placement="bottom" data-toggle="tooltip" data-original-title="'.t("share_on", "Share On").' Facebook" target="_blank" class="btn btn-social-icon btn-facebook"><i class="fa fa-facebook"></i></a>
	<a href="https://twitter.com/share?url='.validation::safeOutputToScreen($shareLink).'" data-placement="bottom" data-toggle="tooltip" data-original-title="'.t("share_on", "Share On").' Twitter" target="_blank" class="btn btn-social-icon btn-twitter"><i class="fa fa-twitter"></i></a>							
	<a href="https://plus.google.com/share?url='.validation::safeOutputToScreen($shareLink).'" data-placement="bottom" data-toggle="tooltip" data-original-title="'.t("share_on", "Share On").' Google Plus" target="_blank" class="btn btn-social-icon btn-google-plus"><i class="fa fa-google-plus"></i></a>
	<a href="https://www.linkedin.com/cws/share?url='.validation::safeOutputToScreen($shareLink).'" data-placement="bottom" data-toggle="tooltip" data-original-title="'.t("share_on", "Share On").' Linkedin" target="_blank" class="btn btn-social-icon btn-linkedin"><i class="fa fa-linkedin"></i></a>
	
	<a href="http://reddit.com/submit?url='.validation::safeOutputToScreen($shareLink).'&title='.urlencode(validation::safeOutputToScreen($fileFolder->folderName)).'" data-placement="bottom" data-toggle="tooltip" data-original-title="'.t("share_on", "Share On").' Reddit" target="_blank" class="btn btn-social-icon btn-reddit"><i class="fa fa-reddit-alien"></i></a>
	<a href="http://www.stumbleupon.com/submit?url='.validation::safeOutputToScreen($shareLink).'&title='.urlencode(validation::safeOutputToScreen($fileFolder->folderName)).'" data-placement="bottom" data-toggle="tooltip" data-original-title="'.t("share_on", "Share On").' StumbleUpon" target="_blank" class="btn btn-social-icon btn-stumbleupon"><i class="fa fa-stumbleupon"></i></a>
	<a href="http://digg.com/submit?url='.validation::safeOutputToScreen($shareLink).'&title='.urlencode(validation::safeOutputToScreen($fileFolder->folderName)).'" data-placement="bottom" data-toggle="tooltip" data-original-title="'.t("share_on", "Share On").' Digg" target="_blank" class="btn btn-social-icon btn-digg"><i class="fa fa-digg"></i></a>
	<a href="https://www.tumblr.com/widgets/share/tool?canonicalUrl='.validation::safeOutputToScreen($shareLink).'&title='.urlencode(validation::safeOutputToScreen($fileFolder->folderName)).'&caption='.urlencode(validation::safeOutputToScreen($fileFolder->folderName)).'" data-placement="bottom" data-toggle="tooltip" data-original-title="'.t("share_on", "Share On").' Tumblr" target="_blank" class="btn btn-social-icon btn-tumblr"><i class="fa fa-tumblr"></i></a>');
?>
<script>loadExistingInternalShareTable(<?php echo (int)$fileFolder->id; ?>);</script>

<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
	<h4 class="modal-title"><?php echo t("share_folder", "share folder"); ?>: <?php echo validation::safeOutputToScreen($fileFolder->folderName); ?></h4>
</div>

<div class="modal-body">
	<div class="row">
	
		<div class="col-md-3">
			<div class="modal-icon-left"><img src="<?php echo SITE_IMAGE_PATH; ?>/modal_icons/share.png"/></div>
		</div>
		
		<div class="col-md-9">
			<!-- Nav tabs -->
			<ul class="nav nav-tabs" role="tablist">
				<?php // SHARE CODE - DISABLED UNTIL THE NEXT RELEASE
				//<li role="presentation" class="active"><a href="#usershare" aria-controls="usershare" role="tab" data-toggle="tab"><i class="entypo-user-add"></i> Internal User</a></li>
				?>
				<li role="presentation" class="active"><a href="#publicshare" aria-controls="publicshare" role="tab" data-toggle="tab"><i class="entypo-share"></i> Externally Share</a></li>
				<li role="presentation"><a href="#viaemail" aria-controls="viaemail" role="tab" data-toggle="tab"><i class="entypo-mail"></i> Send via Email</a></li>
			</ul>

			<div class="tab-content">
				<?php // SHARE CODE - DISABLED UNTIL THE NEXT RELEASE
				/*
				<div role="tabpanel" class="tab-pane active" id="usershare">
					<div class="row">
						<div class="col-md-12" style="margin-bottom: 20px;">
							<p><?php echo t('edit_folder_internal_share_intro', 'You can internally share this folder with other users on the site. Simply enter their email address and permission level below. They\'ll see the new folder listed within their file manager.'); ?></p>
							<p><?php echo t('edit_folder_internal_share_intro_2', 'You can share with more than 1 user at a time by comma separating each email address.'); ?></p>
						</div>
					</div>
					<div class="row">
						<form action="<?php echo WEB_ROOT; ?>/ajax/_email_folder_url.process.ajax.php" autocomplete="off">
							<div class="col-md-12">
								<div class="form-group" style="margin-bottom: 7px;">
									<label for="registeredEmailAddress" class="control-label"><?php echo t('edit_folder_internal_share_email', 'Registered Email Address:'); ?></label>
									<div class="input-group">
										<input type="text" class="form-control" name="registeredEmailAddress" id="registeredEmailAddress" placeholder="<?php echo UCWords(t("recipient_email_address", "recipient email address")); ?>"/>
										<span class="input-group-btn">
											<button id="shareFolderInternallyBtn" type="button" class="btn btn-info" onClick="shareFolderInternally(<?php echo (int)$fileFolder->id; ?>); return false;"><?php echo UCWords(t("grant_access", "grant access")); ?> <i class="entypo-lock"></i></button>
										</span>
									</div>
								</div>
							</div>
							<div class="col-md-12">
								<div class="form-group">
									<div class="row">
										<div class="col-md-12" style="margin: 6px;">
											<div class="radio radio-replace color-blue" style="display: inline-block;"> <input type="radio" id="permission_radio_view" name="permission_radio" value="view" checked=""><label> View Only</label> </div>
											<div class="radio radio-replace color-blue" style="display: inline-block; margin-left: 20px;"> <input type="radio" id="permission_radio_upload_download" name="permission_radio" value="upload_download"> <label> Upload, Download &amp; View</label></div>
											<div class="radio radio-replace color-blue" style="display: inline-block; margin-left: 20px;"> <input type="radio" id="permission_radio_all" name="permission_radio" value="all"> <label> Full Access</label></div>
										</div>
									</div>
								</div>
							</div>
						</form>
					</div>
					
					<div class="row">
						<div id="existingInternalShareTable" class="col-md-12" style="margin-top: 20px;"></div>
					</div>
				</div>
				*/
				?>
				
				<div role="tabpanel" class="tab-pane active" id="publicshare">
					<div class="row">
						<div class="col-md-12" style="margin-bottom: 20px;">
							<p>
							<?php
								if($isPublic == true)
								{
									echo "As this is a <strong>Public Folder</strong>, you can share the folder url below for direct access to the files, wuthout being logged in. Any sub-folders which are set as Public will also be available.";
								}
								else
								{
									echo "As this is a <strong>Private Folder</strong>, you will need to generate a sharing url to enable access to the files. Click the icon below to create a secure url that you can share without setting the folder as publicly accessible.";
								}
							?>
							</p>
						</div>
					</div>
					
					<?php if($isPublic == true): ?>
					<div class="row">
						<div class="col-md-12">
							<div class="form-group">
								<label for="folderName" class="control-label"><?php echo t('edit_folder_sharing_url', 'Sharing Url:'); ?></label>
								<div class="input-group">
									<pre style="margin: 0px; cursor: pointer; white-space: normal;"><section onClick="selectAllText(this); return false;" id="folderUrlSection"><?php echo validation::safeOutputToScreen($pageUrl); ?></section></pre>
									<span class="input-group-btn" style="vertical-align: top;">
										<button id="copyToClipboardBtn" type="button" class="btn btn-primary" data-clipboard-action="copy" data-clipboard-target="#folderUrlSection" style="padding: 7px 12px;" data-placement="bottom" data-toggle="tooltip" data-original-title="Copy Url to Clipboard" onClick="copyToClipboard('#copyToClipboardBtn'); return false;"><i class="entypo-clipboard"></i></button>
									</span>
								</div>

								<div class="social-wrapper">
									<?php echo SHARE_URLS_TEMPLATE; ?>
								</div>
							</div>
						</div>
					</div>
					<?php else: ?>
					<div class="row">
						<div class="col-md-12">
							<div class="form-group">
								<label for="folderName" class="control-label"><?php echo t('edit_folder_sharing_url', 'Sharing Url:'); ?></label>
								<div class="input-group">
									<pre style="margin: 0px; cursor: pointer; white-space: normal;"><section id="sharingUrlInput" onClick="selectAllText(this); return false;">Click 'refresh' button to generate...</section></pre>
									<span class="input-group-btn" style="vertical-align: top;">
										<button type="button" class="btn btn-primary" onClick="generateFolderSharingUrl(<?php echo (int)$fileFolder->id; ?>); return false;" title="Click to generate the sharing url..." style="    padding: 7px 12px;"><i class="glyphicon glyphicon-refresh"></i></button>
									</span>
								</div>
								
								<div id="nonPublicSharingUrls" class="social-wrapper" style="display: none;">
									<?php echo SHARE_URLS_TEMPLATE; ?>
								</div>
								
								<div class="social-wrapper-template" style="display: none;">
									<?php echo SHARE_URLS_TEMPLATE; ?>
								</div>
							</div>
						</div>
					</div>
					<?php endif; ?>
					
					<div class="row">
						<div class="col-md-12" style="margin-top: 14px;">
							<p>You can change whether this folder is Private or Public via the 'edit folder' option. Note that if a parent folder is set as Private, all child folders are also private.</p>
						</div>
					</div>
				</div>
				<div role="tabpanel" class="tab-pane" id="viaemail">					
					<div class="row">
						<form action="<?php echo WEB_ROOT; ?>/ajax/_email_folder_url.process.ajax.php" autocomplete="off">
							<div class="col-md-12">
								<div class="form-group" style="margin-bottom: 7px;">
									<label for="shareEmailAddress" class="control-label"><?php echo t('edit_folder_send_via_email', 'Send via Email:'); ?></label>
									<div class="input-group">
										<input type="text" class="form-control" name="shareEmailAddress" id="shareEmailAddress" placeholder="<?php echo UCWords(t("recipient_email_address", "recipient email address")); ?>"/>
										<span class="input-group-btn">
											<button type="button" class="btn btn-info" onClick="processAjaxForm(this, function() { $('#shareEmailAddress').val(''); $('#shareExtraMessage').val(''); }); return false;"><?php echo UCWords(t("send_email", "send email")); ?> <i class="entypo-mail"></i></button>
										</span>
									</div>
								</div>
							</div>
							<div class="col-md-12">
								<div class="form-group">
									<textarea id="shareExtraMessage" name="shareExtraMessage" class="form-control" placeholder="<?php echo UCWords(t("extra_message", "extra message (optional)")); ?>"></textarea>
									<input name="shareEmailFolderUrl" id="shareEmailFolderUrl" type="hidden" value="<?php echo ($isPublic == true)?validation::safeOutputToScreen($pageUrl):''; ?>"/>
									<input type="hidden" name="submitme" id="submitme" value="1"/>
									<input type="hidden" value="<?php echo (int) $fileFolder->id; ?>" name="folderId"/>
								</div>
							</div>
						</form>
					</div>
					
					<div class="row">
						<div class="col-md-12" style="margin-top: 14px;">
							<p>You can change whether this folder is Private or Public via the 'edit folder' option. Note that if a parent folder is set as Private, all child folders are also private.</p>
						</div>
					</div>
				</div>
				
			</div>
			
		</div>
	</div>
</div>

<div class="modal-footer">
	<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo t("close", "close"); ?></button>
</div>