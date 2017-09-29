<?php
// whether to allow chunked uploaded. Recommend to keep as true unless you're experiencing issues.
define('USE_CHUNKED_UPLOADS', true);
define('CHUNKED_UPLOAD_SIZE', 100000000); // 100MB

// max allowed upload size & max permitted urls
$maxUploadSize = UserPeer::getMaxUploadFilesize();
$maxPermittedUrls = (int) UserPeer::getMaxRemoteUrls();

// get accepted file types
$acceptedFileTypes = UserPeer::getAcceptedFileTypes();

// whether to allow uploads or not
$showUploads = true;
if (UserPeer::getAllowedToUpload() == false) {
    $showUploads = false;
}

// load folders
$folderArr = array();
if ($Auth->loggedIn())
{
    $folderArr = fileFolder::loadAllForSelect($Auth->id);
}

// load categories
$categoryListing = $db->getRows("SELECT id, label, `key` FROM plugin_filepreviewer_category ORDER BY label");

// uploader javascript
require_once(SITE_TEMPLATES_PATH . '/partial/_uploader_javascript.inc.php');
?>

<div class="preLoadImages hidden">
    <img src="<?php echo SITE_IMAGE_PATH; ?>/delete_small.png" height="1" width="1"/>
    <img src="<?php echo SITE_IMAGE_PATH; ?>/add_small.gif" height="1" width="1"/>
    <img src="<?php echo SITE_IMAGE_PATH; ?>/red_error_small.png" height="1" width="1"/>
    <img src="<?php echo SITE_IMAGE_PATH; ?>/green_tick_small.png" height="1" width="1"/>
    <img src="<?php echo SITE_IMAGE_PATH; ?>/processing_small.gif" height="1" width="1"/>
</div>

<div>
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    <ul class="nav nav-tabs bordered">
        <li class="active"><a href="#fileUpload" data-toggle="tab"><?php echo UCWords(t('file_upload', 'file upload')); ?></a></li>
        <li><a href="#urlUpload" data-toggle="tab"><?php echo UCWords(t('remote_url_upload', 'remote url upload')); ?></a></li>
<?php
// append any plugin includes
pluginHelper::includeAppends('index_tab.inc.php');
?>
    </ul>

    <!-- FILE UPLOAD -->
    <div class="tab-content">
        <div id="fileUpload" class="tab-pane active">
            <div class="fileUploadMain">
                <div <?php if ($showUploads == false) {
            if ((UserPeer::getAllowedToUpload(0) == false) && (UserPeer::getAllowedToUpload(1) == true)) echo 'onClick="window.location=\'register.' . SITE_CONFIG_PAGE_EXTENSION . '\';"';
            else echo 'onClick="alert(\'' . t('index_uploading_disabled', 'Error: Uploading has been disabled.') . '\'); return false;";';
        } ?>>

                    <!-- uploader -->
                    <div id="uploaderContainer" class="uploader-container">
                        <div id="uploader">
                            <form action="<?php //echo crossSiteAction::appendUrl(file::getUploadUrl() . '/core/ajax/file_upload_handler.ajax.php?r=' . htmlspecialchars(_CONFIG_SITE_HOST_URL) . '&p=' . htmlspecialchars(_CONFIG_SITE_PROTOCOL)); ?>" method="POST" enctype="multipart/form-data">
                                <div class="fileupload-buttonbar hiddenAlt">
                                    <label class="fileinput-button">
                                        <span><?php echo t('add_files', 'Add files...'); ?></span>
                                        <?php
                                        if ($showUploads == true) {
                                            if (coreFunctions::ifBrowserAllowsMultipleUploads() == true) {
                                                echo '<input id="add_files_btn" type="file" name="files">';
                                            } else {
                                                echo '<input id="add_files_btn" type="file" name="files[]" multiple>';
                                            }
                                        }
                                        ?>
                                    </label>
                                    <button id="start_upload_btn" type="submit" class="start"><?php echo t('start_upload', 'Start upload'); ?></button>
                                    <button id="cancel_upload_btn" type="reset" class="cancel"><?php echo t('cancel_upload', 'Cancel upload'); ?></button>
                                </div>
                                <div class="fileupload-content">
                                    <label for="add_files_btn" id="initialUploadSectionLabel">
                                        <div id="initialUploadSection" class="initialUploadSection"<?php if (!Stats::currentBrowserIsIE()): ?> onClick="$('#add_files_btn').click();
                                                    return false;"<?php endif; ?>>
                                            <div class="initialUploadText">
                                                <div class="uploadElement">
                                                    <div class="internal">
                                                        <img src="<?php echo SITE_IMAGE_PATH; ?>/modal_icons/upload-computer-icon.png" class="upload-icon-image"/>
                                                        <div class="clear"><!-- --></div>
														<?php if (Stats::currentBrowserIsIE()): ?>
															<?php echo t('click_here_to_browse_your_files', 'Click here to browse your files...'); ?>
														<?php else: ?>
                                                            <?php echo t('drag_and_drop_files_here_or_click_to_browse', 'Drag &amp; drop files here or click to browse...'); ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="uploadFooter">
                                                <div class="baseText">
                                                    <a class="showAdditionalOptionsLink"><?php echo UCFirst(t('options', 'options')); ?></a>&nbsp;&nbsp;|&nbsp;&nbsp;<?php echo t('max_file_size', 'Max file size'); ?>: <?php echo $maxUploadSize > 0 ? coreFunctions::formatSize($maxUploadSize) : t('any', 'Any'); ?>. <?php echo COUNT($acceptedFileTypes) ? ('<span title="'.str_replace(".", "", implode(", ", $acceptedFileTypes)).'">'.t('allowed_file_types', 'Allowed file types') . ': ' . str_replace(".", "", implode(", ", array_slice($acceptedFileTypes, 0, 20))) . ''.(COUNT($acceptedFileTypes) > 20?('... '.(COUNT($acceptedFileTypes)-20).' MORE.'):'.').'</span>') : ''; ?>
                                                </div>
                                            </div>
                                            <div class="clear"><!-- --></div>
                                        </div>
                                    </label>
                                    <div id="fileListingWrapper" class="fileListingWrapper hidden">
                                        <div class="fileSection">
                                            <div id="files" class="files"></div>
                                            <div id="addFileRow" class="addFileRow">
                                                <div class="template-upload template-upload-img">
                                                    <a href="#"<?php if (!Stats::currentBrowserIsIE()): ?> onClick="$('#add_files_btn').click(); return false;"<?php endif; ?>>
                                                        <i class="glyphicon glyphicon-plus"></i>
                                                    </a>
                                                </div>
                                            </div>
                                            <div class="clear"></div>
                                        </div>

                                        <div id="processQueueSection" class="fileSectionFooterText">
                                            <div class="upload-button">
                                                <button onClick="$('#start_upload_btn').click(); return false;" class="btn btn-green btn-lg" type="button"><?php echo t("set_upload_queue", "Upload Queue"); ?> <i class="entypo-upload"></i></button>
                                            </div>
                                            <div class="baseText">
                                                <a class="showAdditionalOptionsLink"><?php echo UCFirst(t('options', 'options')); ?></a>&nbsp;&nbsp;|&nbsp;&nbsp;<?php echo t('max_file_size', 'Max file size'); ?>: <?php echo $maxUploadSize > 0 ? coreFunctions::formatSize($maxUploadSize) : t('any', 'Any'); ?>. <?php echo COUNT($acceptedFileTypes) ? ('<span title="'.str_replace(".", "", implode(", ", $acceptedFileTypes)).'">'.t('allowed_file_types', 'Allowed file types') . ': ' . str_replace(".", "", implode(", ", array_slice($acceptedFileTypes, 0, 20))) . ''.(COUNT($acceptedFileTypes) > 20?('... '.(COUNT($acceptedFileTypes)-20).' MORE.'):'.').'</span>') : ''; ?>
                                            </div>
                                            <div class="clear"><!-- --></div>
                                        </div>

                                        <div id="processingQueueSection" class="fileSectionFooterText hidden">
                                            <div class="globalProgressWrapper">
                                                <div id="progress" class="progress progress-striped active">
                                                    <div style="width: 0%" aria-valuemax="100" aria-valuemin="0" aria-valuenow="0" role="progressbar" class="progress-bar progress-bar-info">
                                                        <span class="sr-only"></span>
                                                    </div>
                                                </div>
                                                <div id="fileupload-progresstext" class="fileupload-progresstext">
                                                    <div id="fileupload-progresstextRight" class="file-upload-progress-right"><!-- --></div>
                                                    <div id="fileupload-progresstextLeft" class="file-upload-progress-left"><!-- --></div>
                                                </div>
                                            </div>
                                            <div class="clear"><!-- --></div>
                                            <div class="upload-button">
                                                <button id="hide_modal_btn" data-dismiss="modal" class="btn btn-default btn-lg" type="button"><?php echo t("set_hide", "Hide"); ?> <i class="entypo-arrows-ccw"></i></button>
                                            </div>
                                            <div class="clear"><!-- --></div>
                                        </div>

                                        <div id="completedSection" class="fileSectionFooterText row hidden">
                                            <div class="col-md-12">
                                                <div class="baseText">
                                                    <?php echo t('file_upload_completed', 'Image uploads completed.'); ?> <?php echo t('index_upload_more_files', '<a href="[[[WEB_ROOT]]]">Click here</a> to upload more files.', array('WEB_ROOT' => $Auth->loggedIn() ? (WEB_ROOT . '/index.' . SITE_CONFIG_PAGE_EXTENSION . '?upload=1') : (WEB_ROOT . '/index.' . SITE_CONFIG_PAGE_EXTENSION . '?upload=1'))); ?>
                                                </div>
                                            </div>
											
											<div class="col-md-12 upload-complete-btns">
                                                <button class="btn btn-info" type="button" onClick="viewFileLinksPopup();
                                                                                                        return false;"><?php echo t("view_all_links", "View All Links"); ?> <i class="entypo-link"></i></button>
                                                <button data-dismiss="modal" class="btn btn-default" type="button"><?php echo t("set_close", "Close"); ?> <i class="entypo-check"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <script id="template-upload" type="text/x-jquery-tmpl">
                            {% for (var i=0, file; file=o.files[i]; i++) { %}
                            <div class="template-upload-img template-upload{% if (file.error) { %} errorText{% } %}" id="fileUploadRow{%=i%}" title="{%=file.name%}">
                            {% if (file.error) { %}
                            <div class="error cancel" title="{%=file.name%}">
<?php echo t('index_error', 'Error'); ?>:
                            {%=file.error%}
                            </div>
                            {% } else { %}
                            <div class="previewOverlay" title="{%=file.name%}">
                            <div class="progressText hidden"></div>
                            <div class="progress hidden">
                            <div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%;">
                            </div>
                            </div>
                            </div>
                            <div class="previewWrapper" title="{%=file.name%}">
                            <div class="cancel">
                            <a href="#" onClick="return false;">
                            <img src="<?php echo SITE_IMAGE_PATH; ?>/delete_small.png" height="10" width="10" alt="<?php echo t('delete', 'delete'); ?>"/>
                            </a>
                            </div>
                            <div class="preview" title="{%=file.name%}&nbsp;&nbsp;{%=o.formatFileSize(file.size)%}"><span class="fade"></span></div>
							<div class="filename" title="{%=file.name%}&nbsp;&nbsp;{%=o.formatFileSize(file.size)%}">{%=file.name%}</div>
                            </div>
                            <div class="start hidden"><button>start</button></div>
                            <div class="cancel hidden"><button>cancel</button></div>
                            {% } %}
                            </div>
                            {% } %}
                        </script>

                        <script id="template-download" type="text/x-jquery-tmpl"><!-- --></script>

                    </div>
                    <!-- end uploader -->

                </div>

                <div class="clear"><!-- --></div>
            </div>
        </div>

        <!-- URL UPLOAD -->
        <div class="tab-pane" id="urlUpload"  <?php if ($showUploads == false) {
    if ((UserPeer::getAllowedToUpload(0) == false) && (UserPeer::getAllowedToUpload(1) == true)) echo 'onClick="window.location=\'register.' . SITE_CONFIG_PAGE_EXTENSION . '\';"';
    else echo 'onClick="alert(\'' . t('index_uploading_disabled', 'Error: Uploading has been disabled.') . '\'); return false;";';
} ?>>
            <div class="urlUploadMain">
                <div>
                    <!-- url uploader -->
                    <div>
                        <div id="urlFileUploader">
                            <div class="urlFileUploaderWrapper">
                                <form action="<?php //echo crossSiteAction::appendUrl(file::getUploadUrl() . "/core/ajax/url_upload_handler.php"); ?>" method="POST" enctype="multipart/form-data">
                                    <div class="initialUploadText">
                                        <div>
                                            <?php echo t('file_upload_remote_url_intro', 'Download files directly from other sites into your account. Note: If the files are on another file download site or password protected, this may not work.'); ?><br/><br/>
                                        </div>
                                        <div class="inputElement">
                                            <textarea name="urlList" id="urlList" class="urlList form-control" placeholder="http://example-site.com/file.jpg"></textarea>
                                            <div class="clear"><!-- --></div>
                                        </div>
                                    </div>
                                    <div class="urlUploadFooter">
                                        <div class="upload-button">
                                            <button id="transferFilesButton" onClick="urlUploadFiles();
                                                return false;" class="btn btn-green btn-lg" type="button"><?php echo t("set_transfer_files", "Transfer Images"); ?> <i class="entypo-upload"></i></button>
                                        </div>
                                        <div class="baseText">
                                            <a class="showAdditionalOptionsLink"><?php echo UCFirst(t('options', 'options')); ?></a>&nbsp;&nbsp;|&nbsp;&nbsp;<?php echo t('enter_up_to_x_file_urls', 'Enter up to [[[MAX_REMOTE_URL_FILES]]] file urls. Separate each url on it\'s own line.', array('MAX_REMOTE_URL_FILES' => $maxPermittedUrls)); ?>
                                        </div>
                                    </div>
                                    <div class="clear"><!-- --></div>
                                </form>
                            </div>
                        </div>

                        <div id="urlFileListingWrapper" class="urlFileListingWrapper hidden">
                            <div class="fileSection">
                                <table id="urls" class="files table table-striped">
                                    <tbody>
                                    </tbody>
                                </table>
                                <div class="clear"><!-- --></div>
                                <div class="upload-button processing-button">
                                    <button onClick="$('#start_upload_btn').click(); return false;" class="btn btn-default disabled btn-lg" type="button"><?php echo t("set_upload_processing", "Processing..."); ?> <i class="entypo-arrows-ccw"></i></button>
                                </div>
                            </div>
                            <div class="clear"><!-- --></div>

                            <div class="fileSectionFooterText row hidden">
                                <div class="col-md-12">
                                    <div class="baseText">
                                        <?php echo t('file_transfers_completed', 'Image transfers completed.'); ?> <?php echo t('index_upload_more_files', '<a href="[[[WEB_ROOT]]]">Click here</a> to upload more files.', array('WEB_ROOT' => $Auth->loggedIn() ? (WEB_ROOT . '/index.' . SITE_CONFIG_PAGE_EXTENSION . '?upload=1') : (WEB_ROOT . '/index.' . SITE_CONFIG_PAGE_EXTENSION . '?upload=1'))); ?>
                                    </div>
                                </div>
                                <div class="col-md-12 upload-complete-btns">
									<button class="btn btn-info" type="button" onClick="viewFileLinksPopup();
																							return false;"><?php echo t("view_all_links", "View All Links"); ?> <i class="entypo-link"></i></button>
									<button data-dismiss="modal" class="btn btn-default" type="button"><?php echo t("set_close", "Close"); ?> <i class="entypo-check"></i></button>
								</div>
                            </div>
                        </div>

                    </div>
                    <!-- end url uploader -->

                </div>

                <div class="clear"><!-- --></div>
            </div>
        </div>

        <?php
        // append any plugin includes
        pluginHelper::includeAppends('index_tab_content.inc.php');
        ?>
    </div>

</div>

<div id="additionalOptionsWrapper" class="additional-options-wrapper" style="display: none;">
    <div class="row">
		<div class="col-md-2"></div>
        <div class="col-md-4">
            <div class="panel minimal">
                <div class="panel-heading">
                    <div class="panel-title">
                        <?php echo UCWords(t('send_via_email', 'send via email:')); ?>
                    </div>
                </div>
                <div class="panel-body">
                    <p>
                        <?php echo t('enter_an_email_address_below_to_send_the_list_of_files', 'Enter an email address below to send the list of files via email once they\'re uploaded.'); ?>
                    </p>
                    <div class="form-group">
                        <label class="control-label" for="send_via_email"><?php echo t("email_address", "Email Address"); ?>:</label>
                        <input id="send_via_email" name="send_via_email" type="text" class="form-control"/>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="panel minimal">
                <div class="panel-heading">
                    <div class="panel-title">
                        <?php echo UCWords(t('store_in_folder', 'store in folder:')); ?>
                    </div>
                </div>
                <div class="panel-body">
                    <p>
                        <?php echo t('select_folder_below_to_store_intro_text_files', 'Select an folder below to store these files in. All current uploads will be available within these folders.'); ?>
                    </p>
                    <div class="form-group">
                        <label class="control-label" for="upload_folder_id"><?php echo t("folder_name", "Folder Name"); ?>:</label>
                        <select id="upload_folder_id" name="upload_folder_id" class="form-control" <?php echo!$Auth->loggedIn() ? 'DISABLED="DISABLED"' : ''; ?>>
                            <option value=""><?php echo!$Auth->loggedIn() ? t("index_login_to_enable", "- login to enable -") : t("index_default", "- default -"); ?></option>
                            <?php
                            if (COUNT($folderArr)) {
                                foreach ($folderArr AS $id => $folderLabel) {
                                    echo '<option value="' . (int) $id . '"';
                                    if ($fid == (int) $id) {
                                        echo ' SELECTED';
                                    }
                                    echo '>' . validation::safeOutputToScreen($folderLabel) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

		<div class="col-md-2"></div>

        <input id="set_password" name="set_password" type="password" type="text" class="form-control" value="" style="display: none;"/>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="footer-buttons">
                <button onClick="showAdditionalOptions(true);
                        return false;" class="btn btn-default" type="button"><?php echo t("set_cancel", "Cancel"); ?></button>
                <button onClick="saveAdditionalOptions();
                        return false;" class="btn btn-info" type="button"><?php echo t("set_save_and_close", "Save Options"); ?> <i class="entypo-check"></i></button>
            </div>
        </div>
    </div>
</div>
