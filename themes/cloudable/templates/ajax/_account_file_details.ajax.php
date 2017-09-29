<?php

// for failed auth
$javascript = '';

// load file
$userOwnsFile = false;
$folder = null;
if (isset($_REQUEST['u']))
{
    $file = file::loadById($_REQUEST['u']);
    if (!$file)
    {
        // failed lookup of file
		$returnJson = array();
		$returnJson['html'] = 'File not found.';
		$returnJson['javascript'] = 'window.location = "'.WEB_ROOT.'";';
		echo json_encode($returnJson);
		exit;
    }
	
	// load folder for later
	if($file->folderId !== NULL)
	{
		$folder = $file->getFolderData();
	}

    // check current user has permission to view the file
    if (($file->userId != $Auth->id) && ($Auth->level_id < 10))
    {
		// if this is a private file
		if(coreFunctions::getOverallPublicStatus($file->userId, $file->folderId, $file->id) == false)
		{
			// output response
			$returnJson['html'] = '<div class="ajax-error-image"><!-- --></div>';
			$returnJson['page_title'] = UCWords(t('error', 'Error'));
			$returnJson['page_url'] = '';
			$returnJson['javascript'] = 'showErrorNotification("'.str_replace("\"", "'", UCWords(t('error', 'Error'))).'", "'.str_replace("\"", "'", t('file_is_not_publicly_shared_please_contact', 'File is not publicly shared. Please contact the owner and request they update the privacy settings.')).'");';
			echo json_encode($returnJson);
			exit;
		}
		
		// check if folder needs a password
		if(($folder) && (strlen($folder->accessPassword) > 0))
		{
			// see if we have it in the session already
			$askPassword = true;
			if(!isset($_SESSION['folderPassword']))
			{
				$_SESSION['folderPassword'] = array();
			}
			elseif(isset($_SESSION['folderPassword'][$folder->id]))
			{
				if($_SESSION['folderPassword'][$folder->id] == $folder->accessPassword)
				{
					$askPassword = false;
				}
			}
			
			if($askPassword == true)
			{
				// output response
				$returnJson['html'] = '<div class="ajax-error-image"><!-- --></div><div id="albumPasswordModel" data-backdrop="static" data-keyboard="false" class="albumPasswordModel modal fade custom-width general-modal"><div class="modal-dialog"><div class="modal-content"><form id="folderPasswordForm" action="'.WEB_ROOT.'/ajax/_folder_password.process.ajax.php" autocomplete="off" onSubmit="$(\'#password-submit-btn\').click(); return false;"><div class="modal-body">';
				
				$returnJson['html'] .= '<div class="row">';
				$returnJson['html'] .= '	<div class="col-md-4">';
				$returnJson['html'] .= '		<div class="tile-title tile-orange"> <div class="icon"> <i class="glyphicon glyphicon-lock"></i> </div> <div class="title"> <h3>'.t('password_protected', 'Password Protected').'</h3> <p></p> </div> </div>';
				$returnJson['html'] .= '	</div>';
				$returnJson['html'] .= '	<div class="col-md-8">';
				$returnJson['html'] .= '		<h4>'.t('password_required', 'Password Required').'</h4><hr style="margin-top: 5px;"/>';
				$returnJson['html'] .= '		<div class="form-group">';
				$returnJson['html'] .= '			<p>'.t('this_folder_has_a_password_set', 'This folder requires a password to gain access. Use the form below to enter the password, then click "unlock".').'</p>';
				$returnJson['html'] .= '		</div>';
				
				$returnJson['html'] .= '		<div class="form-group">';
				$returnJson['html'] .= '			<label for="folderName" class="control-label">'.UCWords(t('access_password', 'Access Password')).':</label>';
				$returnJson['html'] .= '			<div class="input-grsoup">';
				$returnJson['html'] .= '				<input type="password" name="folderPassword" id="folderPassword" class="form-control" placeholder="************"/>';
				$returnJson['html'] .= '			</div>';
				$returnJson['html'] .= '		</div>';
				$returnJson['html'] .= '	</div>';
				$returnJson['html'] .= '</div>';
				
				$returnJson['html'] .= '</div><div class="modal-footer" style="margin-top: 0px;">';
				$returnJson['html'] .= '<input type="hidden" value="'.(int)$folder->id.'" id="folderId" name="folderId"/>';
				$returnJson['html'] .= '<input type="hidden" value="1" id="submitme" name="submitme"/>';
				$returnJson['html'] .= '<button type="button" class="btn btn-default" data-dismiss="modal">'.t('cancel', 'Cancel').'</button>';
				$returnJson['html'] .= '<button type="button" class="btn btn-info" id="password-submit-btn" onClick="processAjaxForm(this, function() { $(\'.modal\').modal(\'hide\'); $(\'.modal-backdrop\').remove(); showImage('.(int)$file->id.'); }); return false;">'.t('unlock', 'Unlock').' <i class="entypo-check"></i></button>';
				$returnJson['html'] .= '</div></form></div></div></div>';
				$returnJson['javascript'] = "jQuery('.albumPasswordModel').modal('show');";
				$returnJson['page_title'] = $pageTitle;
				$returnJson['page_url'] = $pageUrl;
				echo json_encode($returnJson);
				exit;
			}
		}
    }
	else
	{
		if($Auth->loggedIn() && $file->userId == $Auth->id)
		{
			$userOwnsFile = true;
		}
	}
}
else
{
	$returnJson = array();
	$returnJson['html'] = 'No access.';
	$returnJson['javascript'] = 'window.location = "'.WEB_ROOT.'";';
	echo json_encode($returnJson);
	exit;
}

// update stats
$rs = Stats::track($file, $file->id);
if ($rs)
{
	$file->updateLastAccessed();
}

// load file meta data
$imageWidth = 0;
$imageHeight = 0;
$imageRawData = '';
$imageDateTaken = $file->uploadedDate;
$foundMeta = false;
$imageData = $db->getRow('SELECT width, height, raw_data, date_taken FROM plugin_filepreviewer_meta WHERE file_id = ' . (int)$file->id . ' LIMIT 1');
if($imageData)
{
    $imageWidth = (int)$imageData['width'];
    $imageHeight = (int)$imageData['height'];
    $imageRawData = trim($imageData['raw_data']);
    $imageDateTaken = $imageData['date_taken'];
	$foundMeta = true;
}

// setup max sizes
$maxImagePreviewWidth = 1100;
$maxImagePreviewHeight = 800;
if(($imageWidth > 0) && ($imageWidth < $maxImagePreviewWidth))
{
	$maxImagePreviewWidth = $imageWidth;
}
if(($imageHeight > 0) && ($imageHeight < $maxImagePreviewHeight))
{
	$maxImagePreviewHeight = $imageHeight;
}

$imageRawDataArr = array();
if(strlen($imageRawData))
{
    $imageRawDataArr = json_decode($imageRawData, true);
    if(!$imageRawDataArr)
    {
        $imageRawDataArr = array();
    }
}

// get filepreviewer object
$filePreviewerObj = pluginHelper::getInstance('filepreviewer');

// load filepreviewer plugin details
$pluginDetails  = pluginHelper::pluginSpecificConfiguration('filepreviewer');
$pluginConfig   = $pluginDetails['config'];
$pluginSettings = json_decode($pluginDetails['data']['plugin_settings'], true);

// load file type
$generalFileType = $filePreviewerObj->getGeneralFileType($file);

// get folder details
$coverId = null;
if($folder)
{
	$coverData = fileFolder::getFolderCoverData($folder->id);
	$coverId = $coverData['file_id'];
	$coverUniqueHash = $coverData['unique_hash'];
}

// get owner details
$owner = null;
if((int)$file->userId)
{
	$owner = UserPeer::loadUserById($file->userId);
}

// get next and previous file
$themeObj = themeHelper::getLoadedInstance();
$similarImages = $themeObj->getSimilarFiles($file);
$totalImages = COUNT($similarImages);
$prev = null;
$next = null;
if($totalImages)
{
    // find index of currently selected
    $selectedIndex = null;
	foreach($similarImages AS $k=>$similarImage)
	{
		if($similarImage->id == $file->id)
		{
			$selectedIndex = $k;
		}
	}
    
	if((int)$selectedIndex >= 1)
	{
		$prev = $similarImages[$selectedIndex-1]->id;
	}
	
    if((int)$selectedIndex < ($totalImages-1))
	{
		$next = $similarImages[$selectedIndex+1]->id;
	}
}

// public status
$isPublic = 1;
if(coreFunctions::getOverallPublicStatus($file->userId, $file->folderId, $file->id) == false)
{
    $isPublic = 0;
}
?>

<?php
ob_start();
?>

<div class="file-browse-container-wrapper">
	<div class="file-preview-wrapper">
		<div class="row">
			<div class="col-md-9">
				<?php if ($file->statusId == 1): ?>
				<div class="section-wrapper image-preview-wrapper">
					<?php if($prev !== null): ?>
						<a href="#" class="prev-link" onClick="showImage(<?php echo (int)$prev; ?>); return false;"><i class="entypo-left-open-big"></i></a>
					<?php endif; ?>
					
					<?php if($next !== null): ?>
						<a href="#" class="next-link" onClick="showImage(<?php echo (int)$next; ?>); return false;"><i class="entypo-right-open-big"></i></a>
					<?php endif; ?>

					<div class="image">
						<div class="content-preview-wrapper loader">
							<?php
							// include the previewer
							$filePath = SITE_TEMPLATES_PATH.'/partial/_preview_'.$generalFileType.'.inc.php';
							if(!file_exists($filePath))
							{
								$filePath = SITE_TEMPLATES_PATH.'/partial/_preview_download.inc.php';
							}
							include($filePath);
							?>
						</div>
					</div>
				</div>
				<div class="clear"></div>
				<?php endif; ?>
				
				<div class="section-wrapper">
					<?php if($isPublic == true): ?>
						<div class="image-social-sharing">
							<div class="row mobile-social-share">
								<div id="socialHolder">
									<div id="socialShare" class="btn-group share-group">
										<a data-toggle="dropdown" class="btn btn-info">
											 <i class="entypo-share"></i>
										</a>
										<button href="#" data-toggle="dropdown" class="btn btn-info dropdown-toggle share">
											<span class="caret"></span>
										</button>
										<ul class="dropdown-menu">
											<li>
												<a href="https://twitter.com/intent/tweet?url=<?php echo validation::safeOutputToScreen($file->getFullShortUrl()); ?>&text=<?php echo validation::safeOutputToScreen($file->originalFilename); ?>" data-original-title="Twitter" data-toggle="tooltip" href="#" class="btn btn-twitter" data-placement="left" target="_blank">
													<i class="fa fa-twitter"></i>
												</a>
											</li>
											<li>
												<a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo validation::safeOutputToScreen($file->getFullShortUrl()); ?>" data-original-title="Facebook" data-toggle="tooltip" href="#" class="btn btn-facebook" data-placement="left" target="_blank">
													<i class="fa fa-facebook"></i>
												</a>
											</li>					
											<li>
												<a href="https://plus.google.com/share?url=<?php echo validation::safeOutputToScreen($file->getFullShortUrl()); ?>" data-original-title="Google+" data-toggle="tooltip" href="#" class="btn btn-google" data-placement="left" target="_blank">
													<i class="fa fa-google-plus"></i>
												</a>
											</li>
											<li>
												<a href="http://www.linkedin.com/shareArticle?mini=true&url=<?php echo validation::safeOutputToScreen($file->getFullShortUrl()); ?>" data-original-title="LinkedIn" data-toggle="tooltip" href="#" class="btn btn-linkedin" data-placement="left" target="_blank">
													<i class="fa fa-linkedin"></i>
												</a>
											</li>
											<li>
												<a href="http://pinterest.com/pin/create/button/?url=<?php echo validation::safeOutputToScreen($file->getFullShortUrl()); ?>" data-original-title="Pinterest" data-toggle="tooltip" class="btn btn-pinterest" data-placement="left" target="_blank">
													<i class="fa fa-pinterest"></i>
												</a>
											</li>
										</ul>
									</div>
								</div>
							</div>
						</div>
					<?php endif; ?>
					<div class="image-name-title">
						<?php echo validation::safeOutputToScreen($file->originalFilename); ?>
					</div>
					<div class="clear"></div>
					<div class="similar-images"><!-- --></div>
				</div>
			</div>
			<div class="col-md-3">
				<?php if($owner !== null): ?>
				<div class="section-wrapper">
					<?php if($coverId): ?>
						<?php
						$folderCoverFile = file::loadById($coverId);
						$folderCoverLink = file::getIconPreviewImageUrl((array) $folderCoverFile, false, 64, false, 280, 280, 'middle');
						?>
						<a href="#" onClick="loadImages(<?php echo (int)$folder->id; ?>); return false;"><img width="60" height="60" class="img-rounded" alt="<?php echo validation::safeOutputToScreen($owner->getAccountScreenName()); ?>" src="<?php echo $folderCoverLink; ?>"/></a>
						<span class="text-section">
							<a href="#" class="text-section-1" onClick="loadImages(<?php echo (int)$folder->id; ?>); return false;"><?php echo validation::safeOutputToScreen($folder->folderName); ?></a>
								<?php echo t('profile_by', 'by'); ?>
							<?php echo validation::safeOutputToScreen($owner->getAccountScreenName()); ?>
						</span>
					<?php else: ?>
						<?php
							// see if smaller filetype icon exists
							$iconLink = WEB_ROOT.'/page/view_avatar.php?id='.(int)$file->userId.'&width=60&height=60';
							$fileTypeLink = '/images/file_icons/160px/'.$file->extension.'.png';
							if(file_exists(SITE_THEME_DIRECTORY_ROOT.'/cloudable/'.$fileTypeLink))
							{
								$iconLink = SITE_THEME_PATH.'/'.$fileTypeLink;
							}
						?>
						<a href="#" onClick="loadImages(<?php echo (int)$folder->id; ?>); return false;"><img width="60" class="img-rounded" alt="<?php echo validation::safeOutputToScreen($owner->getAccountScreenName()); ?>" src="<?php echo $iconLink; ?>"/></a>
						<span class="text-section">
							<a href="#" class="text-section-1" onClick="loadImages(<?php echo (int)$folder->id; ?>); return false;"><?php echo validation::safeOutputToScreen($folder->folderName); ?></a>
								<?php echo t('profile_by', 'by'); ?>
							<?php echo validation::safeOutputToScreen($owner->getAccountScreenName()); ?>
						</span>
					<?php endif; ?>
				</div>
				<?php endif; ?>
				
				<div class="section-wrapper">
					<table class="table table-bordered table-striped">
						<tbody>
							<tr>
								<td class="view-file-details-first-row">
									<?php echo UCWords(t('uploaded', 'uploaded')); ?>:
								</td>
								<td class="responsiveTable">
									<?php echo coreFunctions::formatDate($file->uploadedDate); ?>
								</td>
							</tr>
							<tr>
								<td class="view-file-details-first-row">
									<?php echo UCWords(t('filesize', 'filesize')); ?>:
								</td>
								<td class="responsiveTable">
									<?php echo coreFunctions::formatSize($file->fileSize); ?>
								</td>
							</tr>
							
							<?php if($userOwnsFile == true): ?>
							<tr>
								<td class="view-file-details-first-row">
									<?php echo UCWords(t('sharing', 'Sharing')); ?>:
								</td>
								<td class="responsiveTable">
									<?php echo ($isPublic == true) ? '<i class="entypo-lock-open"></i>' : '<i class="entypo-lock"></i>'; ?>
									<?php echo ($isPublic == true) ? t('public_file', 'Public File - Can be accessed directly by anyone that knows the file url.') : t('private_file', 'Private File - Only available via your account, or via a generated sharing url.'); ?>
								</td>
							</tr>
							<?php endif; ?>
							
							<?php if ($file->statusId != 1): ?>
							<tr>
								<td class="view-file-details-first-row">
									<?php echo UCWords(t('status', 'status')); ?>:
								</td>
								<td class="responsiveTable">
									<?php echo validation::safeOutputToScreen(UCWords(file::getStatusLabel($file->statusId))); ?>
								</td>
							</tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
				
				<?php
				// links
				$links = array();
				if($userOwnsFile == true)
				{
					if ($file->statusId == 1)
					{
						$links[] = '<button type="button" class="btn btn-default" data-dismiss="modal" onClick="showEditFileForm('.(int)$file->id.'); return false;" title="" data-original-title="'.addslashes(UCWords(t('account_file_details_edit_file', 'Edit File'))).'" data-placement="bottom" data-toggle="tooltip"><i class="entypo-pencil"></i></button>';
						$links[] = '<button type="button" class="btn btn-default" data-dismiss="modal" onClick="deleteFile('.(int)$file->id.', function() {loadImages('.((int)$file->folderId?$file->folderId:'-1').');}); return false;" title="" data-original-title="'.addslashes(UCWords(t('account_file_details_delete_file', 'Delete File'))).'" data-placement="bottom" data-toggle="tooltip"><i class="entypo-trash"></i></button>';
					}
				
					// make sure user is permitted to view stats
					if ($file->canViewStats() == true)
					{
						$links[] = '<button type="button" class="btn btn-default" onClick="showStatsPopup(\''.validation::safeOutputToScreen($file->id).'\'); return false;" title="" data-original-title="'.addslashes(UCWords(t('account_file_details_file_stats', 'File Stats'))).'" data-placement="bottom" data-toggle="tooltip"><i class="entypo-chart-line"></i></button>';
					}
				}
				
				// should we show the download link
				$showDownloadLink = (bool)$folder->showDownloadLinks;
				if($userOwnsFile == true)
				{
					// override if this user owns the file
					$showDownloadLink = true;
				}
				
				if (($file->statusId == 1) && ($showDownloadLink == true))
				{
					if($generalFileType == 'image')
					{
						$downloadLinks = '<button type="button" class="btn btn-info" data-toggle="dropdown">'.addslashes(UCWords(t('account_file_details_download', 'Download'))).'</button> <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown"> <i class="entypo-down"></i> </button>';
						$downloadLinks .= '<ul class="dropdown-menu dropdown-info account-dropdown-resize-menu" role="menu">';
						$downloadLinks .= '<li><a href="#" onClick="triggerFileDownload('.(int)$file->id.', \''.$file->getFileHash().'\'); return false;"><i class="entypo-right"></i>'.strtoupper($file->extension).' '.t('account_file_details_original', 'Original').'</a> </li>';
						
						// add resize links, skip if we don't have the file dimentions
						if(($imageWidth > 0) && ($imageHeight > 0))
						{
							$downloadLinks .= '<li class="divider"></li>';
							rsort($pluginConfig['scaledPercentages']);
							foreach ($pluginConfig['scaledPercentages'] AS $percentage)
							{
								$linkWidth  = ceil(($imageWidth/100)*$percentage);
								$linkHeight = ceil(($imageHeight/100)*$percentage);
								
								if(($linkWidth <= PluginFilepreviewer::HOLDING_CACHE_SIZE) && ($linkHeight <= PluginFilepreviewer::HOLDING_CACHE_SIZE))
								{
									$downloadLinks .= '<li><a href="'._CONFIG_SITE_PROTOCOL . '://' . file::getFileDomainAndPath($file->id, $file->serverId, true) . '/' . PLUGIN_DIRECTORY_NAME.'/filepreviewer/site/resize_image.php?uh='.$file->getFileHash().'&f='.(int) $file->id.'&w='.$linkWidth.'&h='.$linkHeight.'"><i class="entypo-right"></i>JPG '.$linkWidth.' x '.$linkHeight.' px</a> </li>';
								}
							}
						}
						$downloadLinks .= '</ul>';
					}
					else
					{
						$downloadLinks = '<button type="button" class="btn btn-info" onClick="triggerFileDownload('.(int)$file->id.', \''.$file->getFileHash().'\'); return false;">'.addslashes(UCWords(t('account_file_details_download', 'Download'))).' <i class="entypo-down"></i></button>';
					}
					
					$links[] = $downloadLinks;
				}
				?>

				<?php if(COUNT($links)): ?>
				<div class="section-wrapper">
					<div class="button-wrapper responsiveMobileAlign">						
						<?php foreach($links AS $link): ?>
							<div class="btn-group responsiveMobileMargin">
								<?php echo $link; ?>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
				<?php endif; ?>
				
				<?php if ($file->statusId == 1): ?>
				<div role="tabpanel">
					<ul class="nav nav-tabs file-info-tabs" role="tablist">
						<li role="presentation" class="active"><a href="#details" aria-controls="details" role="tab" data-toggle="tab"><i class="entypo-share"></i><span> <?php echo UCWords(t("sharing_code", "sharing code")); ?></span></a></li>
						<li role="presentation"><a href="#send-via-email" aria-controls="send-via-email" role="tab" data-toggle="tab"><i class="entypo-mail"></i><span> <?php echo UCWords(t("email", "email")); ?></span></a></li>
						<?php if(COUNT($imageRawDataArr)): ?>
							<li role="presentation"><a href="#image-data" aria-controls="image-data" role="tab" data-toggle="tab"><i class="entypo-info-circled"></i><span> <?php echo UCWords(t("meta", "Meta")); ?></span></a></li>
						<?php endif; ?>
					</ul>
					<div class="tab-content">
						<div class="tab-pane active" id="details">
							<h4><strong><?php echo t('file_page_link', 'File Page Link'); ?></strong></h4>
							<pre><section onClick="selectAllText(this); return false;"><?php echo $file->getFullShortUrl(); ?></section></pre>
							
							<?php if ((int) $pluginSettings['show_embedding'] != 1): ?>
								<h4><strong><?php echo t('html_code', 'HTML Code'); ?></strong></h4>
								<pre><section onClick="selectAllText(this); return false;"><?php echo $file->getHtmlLinkCode(); ?></section></pre>
								
								<h4><strong><?php echo UCWords(t('forum_code', 'forum code')); ?></strong></h4>
								<pre><section onClick="selectAllText(this); return false;"><?php echo $file->getForumLinkCode(); ?></section></pre>
							<?php else: ?>
								<h4><strong><?php echo UCWords(t('html_code', 'HTML Code')); ?></strong></h4>
								<pre><section onClick="selectAllText(this); return false;">&lt;a href=&quot;<?php echo $file->getFullShortUrl(); ?>&quot; target=&quot;_blank&quot; title=&quot;<?php echo t('view_on', 'View on'); ?> <?php echo SITE_CONFIG_SITE_NAME; ?>&quot;&gt;&lt;img src=&quot;<?php echo WEB_ROOT; ?>/plugins/filepreviewer/site/thumb.php?s=<?php echo $file->shortUrl; ?>&amp;/<?php echo $file->getSafeFilenameForUrl(); ?>&quot;/&gt;&lt;/a&gt;</section></pre>
								
								<h4><strong><?php echo UCWords(t('forum_code', 'Forum Code')); ?></strong></h4>
								<pre><section onClick="selectAllText(this); return false;">[URL=<?php echo $file->getFullShortUrl(); ?>][IMG]<?php echo WEB_ROOT; ?>/plugins/filepreviewer/site/thumb.php?s=<?php echo $file->shortUrl; ?>&/<?php echo $file->getSafeFilenameForUrl(); ?>[/IMG][/URL]</section></pre>
							<?php endif; ?>
						</div>

						<div role="tabpanel" class="tab-pane" id="send-via-email">
							<div class="row">
								<form action="<?php echo WEB_ROOT; ?>/ajax/_account_file_details_send_email.process.ajax.php" autocomplete="off">
									<div class="col-md-12">
										<div class="form-group">
											<p><?php echo t('account_file_details_intro_user_the_form_below_send_email', 'Use the form below to share this file via email. The recipient will receive a link to download the file.') ; ?></p>
											<?php if($isPublic == false): ?>
												<div class="alert alert-danger"><?php echo t('account_file_details_folder_not_publicly_shared', 'This file is not publicly shared. You will need to make it public before the recipient can view it.') ; ?></div>
											<?php endif; ?>
										</div>

										<div class="form-group">
											<label class="control-label" for="shareRecipientName"><?php echo UCWords(t("recipient_name", "recipient full name")); ?>:</label>
											<input type="text" id="shareRecipientName" name="shareRecipientName" class="form-control"/>
										</div>
						
										<div class="form-group">
											<label class="control-label" for="shareEmailAddress"><?php echo UCWords(t("recipient_email_address", "recipient email address")); ?>:</label>
											<input type="text" id="shareEmailAddress" name="shareEmailAddress" class="form-control"/>
										</div>
						
										<div class="form-group">
											<label class="control-label" for="shareExtraMessage"><?php echo UCWords(t("extra_message", "extra message (optional)")); ?>:</label>
											<textarea id="shareExtraMessage" name="shareExtraMessage" class="form-control"></textarea>
										</div>

										<div class="form-group">
											<input type="hidden" name="submitme" id="submitme" value="1"/>
											<input type="hidden" value="<?php echo (int) $file->id; ?>" name="fileId"/>
											<button type="button" class="btn btn-info" onClick="processAjaxForm(this, function() { $('#shareRecipientName').val(''); $('#shareEmailAddress').val(''); $('#shareExtraMessage').val(''); }); return false;"><?php echo UCWords(t("send_email", "send email")); ?> <i class="entypo-mail"></i></button>
										</div>
									</div>
								</form>
							</div>
						</div>
						
						<?php if(COUNT($imageRawDataArr)): ?>
						<div class="tab-pane image-data" id="image-data">
							<table class="table table-bordered table-striped">
								<tbody>
									<?php
									foreach($imageRawDataArr AS $k=>$imageRawDataItem)
									{
									   ?> 
										<tr>
											<td class="view-file-details-first-row">
												<?php echo $filePreviewerObj->formatExifName($k); ?>:
											</td>
											<td>
												<?php echo $imageRawDataItem; ?>
											</td>
										</tr>
										<?php
									}
									?>
								</tbody>
							</table>
						</div>
						<?php endif; ?>
					</div>
				</div>
				<?php endif; ?>
				
			</div>
		</div>
	</div>
</div>

<?php
$html = ob_get_contents();
ob_end_clean();

// prepare result
$returnJson = array();
$returnJson['success'] = true;
$returnJson['html'] = $html;
$returnJson['page_title'] = $file->originalFilename;
$returnJson['page_url'] = $file->getFullShortUrl();
$returnJson['javascript'] = $javascript;

echo json_encode($returnJson);