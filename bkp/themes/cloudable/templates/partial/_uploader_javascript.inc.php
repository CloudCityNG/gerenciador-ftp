<?php
// for js translations
t('uploader_hour', 'hour');
t('uploader_hours', 'hours');
t('uploader_minute', 'minute');
t('uploader_minutes', 'minutes');
t('uploader_second', 'second');
t('uploader_seconds', 'seconds');
t('selected', 'selected');
t('selected_image_clear', 'clear');
t('account_file_details_clear_selected', 'Clear Selected');

define('MAX_CONCURRENT_THUMBNAIL_REQUESTS', 5);

$fid = null;
if (isset($_REQUEST['fid'])) {
    $fid = (int) $_REQUEST['fid'];
}
?>
<script>
    var fileUrls = [];
    var fileUrlsHtml = [];
    var fileUrlsBBCode = [];
    var fileDeleteHashes = [];
    var fileShortUrls = [];
	var fileNames = [];
	var uploadPreviewQueuePending = [];
	var uploadPreviewQueueProcessing = [];
	var statusFlag = 'pending';
    var lastEle = null;
    var startTime = null;
    var fileToEmail = '';
    var filePassword = '';
    var fileCategory = '';
    var fileFolder = '';
    var uploadComplete = true;
    $(document).ready(function () {
        document.domain = '<?php echo coreFunctions::removeSubDomain(_CONFIG_CORE_SITE_HOST_URL); ?>';
<?php
if ($showUploads == true) {
    // figure out max files
    $maxFiles = UserPeer::getMaxUploadsAtOnce($Auth->package_id);

    // failsafe
    if ((int) $maxFiles == 0) {
        $maxFiles = 50;
    }

    // if php restrictions are lower than permitted, override
    $phpMaxSize = coreFunctions::getPHPMaxUpload();
    $maxUploadSizeNonChunking = 0;
    if ($phpMaxSize < $maxUploadSize) {
        $maxUploadSizeNonChunking = $phpMaxSize;
    }
    ?>
            // figure out if we should use 'chunking'
            var maxChunkSize = 0;
            var uploaderMaxSize = <?php echo (int) $maxUploadSizeNonChunking; ?>;
    <?php if (USE_CHUNKED_UPLOADS == true): ?>
                if (browserXHR2Support() == true)
                {
                    maxChunkSize = <?php echo (coreFunctions::getPHPMaxUpload() > CHUNKED_UPLOAD_SIZE ? CHUNKED_UPLOAD_SIZE : coreFunctions::getPHPMaxUpload() - 5000); // in bytes, allow for smaller PHP upload limits  ?>;
                    var uploaderMaxSize = <?php echo $maxUploadSize; ?>;
                }
    <?php endif; ?>

            // Initialize the jQuery File Upload widget:
            $('#fileUpload #uploader').fileupload({
                sequentialUploads: true,
                url: '<?php echo crossSiteAction::appendUrl(file::getUploadUrl() . '/core/page/ajax/file_upload_handler.ajax.php?r=' . htmlspecialchars(_CONFIG_SITE_HOST_URL) . '&p=' . htmlspecialchars(_CONFIG_SITE_PROTOCOL)); ?>',
                maxFileSize: uploaderMaxSize,
                formData: {},
				autoUpload: false,
                xhrFields: {
                    withCredentials: true
                },
                getNumberOfFiles: function () {
                    return getTotalRows();
                },
                previewMaxWidth: 160,
                previewMaxHeight: 134,
                previewCrop: true,
                messages: {
                    maxNumberOfFiles: '<?php echo str_replace("'", "\'", t('file_upload_maximum_number_of_files_exceeded', 'Maximum number of files exceeded')); ?>',
                    acceptFileTypes: '<?php echo str_replace("'", "\'", t('file_upload_file_type_not_allowed', 'File type not allowed')); ?>',
                    maxFileSize: '<?php echo str_replace("'", "\'", t('file_upload_file_is_too_large', 'File is too large')); ?>',
                    minFileSize: '<?php echo str_replace("'", "\'", t('file_upload_file_is_too_small', 'File is too small')); ?>'
                },
                maxChunkSize: maxChunkSize,
    <?php echo COUNT($acceptedFileTypes) ? ('acceptFileTypes: /(\\.|\\/)(' . str_replace(".", "", implode("|", $acceptedFileTypes) . ')$/i,')) : ''; ?> maxNumberOfFiles: <?php echo (int) $maxFiles; ?>
            })
                    .on('fileuploadadd', function (e, data) {
						<?php if(COUNT($acceptedFileTypes)): ?>
							var acceptFileTypes = /^(<?php echo str_replace(".", "", implode("|", $acceptedFileTypes)); ?>)$/i;
							for(i in data.originalFiles)
							{
								fileExtension = data.originalFiles[i]['name'].substr(data.originalFiles[i]['name'].lastIndexOf('.')+1);
								if(!acceptFileTypes.test(fileExtension)) {
									alert("<?php echo str_replace("'", "\'", t('file_upload_file_type_not_allowed', 'File type not allowed')); ?> (\""+data.originalFiles[i]['name']+"\")");
									return false;
								}
							}
						<?php endif; ?>
						
                        $('#fileUpload #uploader #fileListingWrapper').removeClass('hidden');
                        $('#fileUpload #uploader #initialUploadSection').addClass('hidden');
                        $('#fileUpload #uploader #initialUploadSectionLabel').addClass('hidden');

                        // fix for safari
                        getTotalRows();
                        // end safari fix

                        totalRows = getTotalRows() + 1;
                        updateTotalFilesText(totalRows);
                    })
                    .on('fileuploadstart', function (e, data) {
                        uploadComplete = false;

                        // hide/show sections
                        $('#fileUpload #addFileRow').addClass('hidden');
                        $('#fileUpload #processQueueSection').addClass('hidden');
                        $('#fileUpload #processingQueueSection').removeClass('hidden');

                        // hide cancel icons
                        $('#fileUpload .cancel').hide();
                        $('#fileUpload .cancel').click(function () {
                            return false;
                        });

                        // show faded overlay on images
                        $('#fileUpload .previewOverlay').addClass('faded');

                        // set timer
                        startTime = (new Date()).getTime();
                    })
                    .on('fileuploadstop', function (e, data) {
                        // finished uploading
                        updateTitleWithProgress(100);
                        updateProgessText(100, data.total, data.total);
                        $('#fileUpload #processQueueSection').addClass('hidden');
                        $('#fileUpload #processingQueueSection').addClass('hidden');
                        $('#fileUpload #completedSection').removeClass('hidden');

                        // set all remainging pending icons to failed
                        $('#fileUpload .processingIcon').parent().html('<img src="<?php echo SITE_IMAGE_PATH; ?>/red_error_small.png" width="16" height="16"/>');

                        uploadComplete = true;
                        sendAdditionalOptions();

                        // setup copy link
                        setupCopyAllLink();

                        // flag as finished for later on
						statusFlag = 'finished';
						
                        if (typeof (checkShowUploadFinishedWidget) === 'function')
                        {
                            checkShowUploadFinishedWidget();
                        }

						delay(function() {
							$('#hide_modal_btn').click();
						}, 1500);
                    })
                    .on('fileuploadprogressall', function (e, data) {
                        // progress bar
                        var progress = parseInt(data.loaded / data.total * 100, 10);
                        $('#progress .progress-bar').css(
                                'width',
                                progress + '%'
                                );

                        // update page title with progress
                        updateTitleWithProgress(progress);
                        updateProgessText(progress, data.loaded, data.total);
                    })
                    .on('fileuploadsend', function (e, data) {
                        // show progress ui elements
                        $(data['context']).find('.previewOverlay .progressText').removeClass('hidden');
                        $(data['context']).find('.previewOverlay .progress').removeClass('hidden');
                    })
                    .on('fileuploadprogress', function (e, data) {
                        // progress bar
                        var progress = parseInt(data.loaded / data.total * 100, 10);

                        // update item progress
                        $(data['context']).find('.previewOverlay .progressText').html(progress + '%');
                        $(data['context']).find('.previewOverlay .progress .progress-bar').css('width', progress + '%');
                    })
                    .on('fileuploaddone', function (e, data) {

                        // hide faded overlay on images
                        $(data['context']).find('.previewOverlay').removeClass('faded');

                        // keep a copy of the urls globally
                        fileUrls.push(data['result'][0]['url']);
                        fileUrlsHtml.push(data['result'][0]['url_html']);
                        fileUrlsBBCode.push(data['result'][0]['url_bbcode']);
                        fileDeleteHashes.push(data['result'][0]['delete_hash']);
                        fileShortUrls.push(data['result'][0]['short_url']);
						fileNames.push(data['result'][0]['name']);

                        var isSuccess = true;
                        if (data['result'][0]['error'] != null)
                        {
                            isSuccess = false;
                        }

                        var html = '';
                        html += '<div class="template-download-img';
                        if (isSuccess == false)
                        {
                            html += ' errorText';
                        }
                        html += '" ';
                        if (isSuccess == true)
                        {
                            html += 'onClick="window.open(\'' + data['result'][0]['url'] + '\'); return false;"';
                        }
						html += ' title="'+data['result'][0]['name']+'"';
                        html += '>';

                        if (isSuccess == true)
                        {
							previewUrl = WEB_ROOT+'/themes/cloudable/images/trans_1x1.gif';
							if(data['result'][0]['success_result_html'].length > 0)
							{
								previewUrl = data['result'][0]['success_result_html'];
							}
							
							html += "<div id='finalThumbWrapper"+data['result'][0]['file_id']+"'></div>";
							queueUploaderPreview('finalThumbWrapper'+data['result'][0]['file_id'], previewUrl, data['result'][0]['file_id']);
                        }
                        else
                        {
                            // @TODO - replace this with an error icon
                            html += 'Error uploading: ' + data['result'][0]['name'];
                        }
                        html += '</div>';

                        // update screen with success content
                        $(data['context']).replaceWith(html);
						processUploaderPreviewQueue();
                    })
                    .on('fileuploadfail', function (e, data) {
                        // hand deletes
                        if (data.errorThrown == 'abort')
                        {
                            $(data['context']).remove();
                            return true;
                        }

                        // update screen with error content, ajax issues
                        var html = '';
                        html += '<div class="template-download-img errorText">';
                        html += '<?php echo t('indexjs_error_server_problem_reservo', 'ERROR: There was a server problem when attempting the upload.'); ?>';
                        html += '</div>';
                        $(data['context'])
                                .replaceWith(html);

                        totalRows = getTotalRows();
                        if (totalRows > 0)
                        {
                            totalRows = totalRows - 1;
                        }

                        updateTotalFilesText(totalRows);
                    });

            // Open download dialogs via iframes,
            // to prevent aborting current uploads:
            $('#fileUpload #uploader #files a:not([target^=_blank])').on('click', function (e) {
                e.preventDefault();
                $('<iframe style="display:none;"></iframe>')
                        .prop('src', this.href)
                        .appendTo('body');
            });

            $('#fileUpload #uploader').bind('fileuploadsubmit', function (e, data) {
                // The example input, doesn't have to be part of the upload form:
                data.formData = {_sessionid: '<?php echo session_id(); ?>', cTracker: '<?php echo MD5(microtime()); ?>', maxChunkSize: maxChunkSize, folderId: fileFolder};
            });
    <?php
}
?>

        $('.showAdditionalOptionsLink').click(function (e) {
            // show panel
            showAdditionalOptions();

            // prevent background clicks
            e.preventDefault();

            return false;
        });

<?php if ($fid != null): ?>
            saveAdditionalOptions(true);
<?php endif; ?>
    });
	
	function queueUploaderPreview(thumbWrapperId, previewImageUrl, previewImageId)
	{
		uploadPreviewQueuePending[thumbWrapperId] = [previewImageUrl, previewImageId];
	}
	
	function processUploaderPreviewQueue()
	{
		// allow only 4 at once
		if(getTotalProcessing() >= <?php echo (int)MAX_CONCURRENT_THUMBNAIL_REQUESTS; ?>)
		{
			return false;
		}
		
		for(i in uploadPreviewQueuePending)
		{
			var filename = $('#'+i).parent().attr('title');
			$('#'+i).html("<img src='"+uploadPreviewQueuePending[i][0]+"' id='finalThumb"+uploadPreviewQueuePending[i][1]+"' onLoad=\"showUploadThumbCheck('finalThumb"+uploadPreviewQueuePending[i][1]+"', "+uploadPreviewQueuePending[i][1]+");\"/><div class='filename'>"+filename+"</div>");
			uploadPreviewQueueProcessing[i] = uploadPreviewQueuePending[i];
			delete uploadPreviewQueuePending[i];
			return false;
		}
	}
	
	function getTotalPending()
	{
		total = 0;
		for(i in uploadPreviewQueuePending)
		{
			total++;
		}
		
		return total;
	}
	
	function getTotalProcessing()
	{
		total = 0;
		for(i in uploadPreviewQueueProcessing)
		{
			total++;
		}
		
		return total;
	}

	function showUploadThumbCheck(thumbId, itemId)
	{
		$('#'+thumbId).after("<div class='image-upload-thumb-check' style='display: none;'><i class='glyphicon glyphicon-ok'></i></div>");
		$('#'+thumbId).parent().find('.image-upload-thumb-check').fadeIn().delay(1000).fadeOut();
		
		// finish uploading
		if(getTotalPending() == 0 && getTotalProcessing() == 0)
		{
			// refresh treeview
			if (typeof (checkShowUploadFinishedWidget) === 'function')
			{
				refreshFolderListing();
			}
		}

		// trigger the next
		delete uploadPreviewQueueProcessing['finalThumbWrapper'+itemId];
		processUploaderPreviewQueue();
	}
	
	function getPreviewExtension(filename)
	{
		fileExtension = filename.substr(filename.lastIndexOf('.')+1);
		if((fileExtension == 'gif') || (fileExtension == 'mng'))
		{
			return 'gif';
		}
		
		return 'jpg';
	}
	
    function setUploadFolderId(folderId)
    {
        if (typeof (folderId != "undefined") && ($.isNumeric(folderId)))
        {
            $('#upload_folder_id').val(folderId);
        }
        else if ($('#nodeId').val() == '-1')
        {
            $('#upload_folder_id').val('');
        }
        else if ($.isNumeric($('#nodeId').val()))
        {
            $('#upload_folder_id').val($('#nodeId').val());
        }
        else
        {
            $('#upload_folder_id').val('');
        }
        saveAdditionalOptions(true);
    }

    function getSelectedFolderId()
    {
        return $('#upload_folder_id').val();
    }

    function setupCopyAllLink()
    {

    }

    function updateProgessText(progress, uploadedBytes, totalBytes)
    {
        // calculate speed & time left
        nowTime = (new Date()).getTime();
        loadTime = (nowTime - startTime);
        if (loadTime == 0)
        {
            loadTime = 1;
        }
        loadTimeInSec = loadTime / 1000;
        bytesPerSec = uploadedBytes / loadTimeInSec;

        textContent = '';
        textContent += '<?php echo t('indexjs_progress', 'Progress'); ?>: ' + progress + '%';
        textContent += ' ';
        textContent += '(' + bytesToSize(uploadedBytes, 2) + ' / ' + bytesToSize(totalBytes, 2) + ')';

        $("#fileupload-progresstextLeft").html(textContent);

        rightTextContent = '';
        rightTextContent += '<?php echo t('indexjs_speed', 'Speed'); ?>: ' + bytesToSize(bytesPerSec, 2) + '<?php echo t('indexjs_speed_ps', 'ps'); ?>. ';
        rightTextContent += '<?php echo t('indexjs_remaining', 'Remaining'); ?>: ' + humanReadableTime((totalBytes / bytesPerSec) - (uploadedBytes / bytesPerSec));

        $("#fileupload-progresstextRight").html(rightTextContent);

        // progress widget for file manager
        if (typeof (updateProgressWidgetText) === 'function')
        {
            updateProgressWidgetText(textContent);
        }
    }

    function getUrlsAsText()
    {
        urlStr = '';
        for (var i = 0; i < fileUrls.length; i++)
        {
            urlStr += fileUrls[i] + "\n";
        }

        return urlStr;
    }

    function viewFileLinksPopup()
    {
        fileUrlText = '';
        htmlUrlText = '';
        bbCodeUrlText = '';
        if (fileUrls.length > 0)
        {
            for (var i = 0; i < fileUrls.length; i++)
            {
                fileUrlText += fileUrls[i] + "<br/>";
                htmlUrlText += fileUrlsHtml[i] + "&lt;br/&gt;<br/>";
				bbCodeUrlText += '[URL='+fileUrls[i]+'][IMG]'+WEB_ROOT+'/plugins/filepreviewer/site/thumb.php?s='+fileShortUrls[i] + "&/"+fileNames[i]+"[/IMG][/URL]<br/>";
            }
        }

        $('#popupContentUrls').html(fileUrlText);
        $('#popupContentHTMLCode').html(htmlUrlText);
        $('#popupContentBBCode').html(bbCodeUrlText);

        jQuery('#fileLinksModal').modal('show', {backdrop: 'static'}).on('shown.bs.modal');
    }

    function showLinkSection(sectionId, ele)
    {
        $('.link-section').hide();
        $('#' + sectionId).show();
        $(ele).parent().children('.active').removeClass('active');
        $(ele).addClass('active');
        $('.file-links-wrapper .modal-header .modal-title').html($(ele).html());
    }

    function selectAllText(el)
    {
        if (typeof window.getSelection != "undefined" && typeof document.createRange != "undefined")
        {
            var range = document.createRange();
            range.selectNodeContents(el);
            var sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(range);
        }
        else if (typeof document.selection != "undefined" && typeof document.body.createTextRange != "undefined")
        {
            var textRange = document.body.createTextRange();
            textRange.moveToElementText(el);
            textRange.select();
        }
    }

    function updateTitleWithProgress(progress)
    {
        if (typeof (progress) == "undefined")
        {
            var progress = 0;
        }
        if (progress == 0)
        {
            $(document).attr("title", "<?php echo PAGE_NAME; ?> - <?php echo SITE_CONFIG_SITE_NAME; ?>");
		}
		else
		{
			$(document).attr("title", progress + "% <?php echo t('indexjs_uploaded', 'Uploaded'); ?> - <?php echo PAGE_NAME; ?> - <?php echo SITE_CONFIG_SITE_NAME; ?>");
		}
	}

	function getTotalRows()
	{
		totalRows = $('#files .template-upload').length;
		if (typeof (totalRows) == "undefined")
		{
			return 0;
		}

		return totalRows;
	}

	function updateTotalFilesText(total)
	{
		// removed for now, might be useful in some form in the future
		//$('#uploadButton').html('upload '+total+' files');
	}

	function setRowClasses()
	{
		// removed for now, might be useful in some form in the future
		//$('#files tr').removeClass('even');
		//$('#files tr').removeClass('odd');
		//$('#files tr:even').addClass('odd');
		//$('#files tr:odd').addClass('even');
	}

	function showAdditionalInformation(ele)
	{
		// block parent clicks from being processed on additional information
		$('.sliderContent table').unbind();
		$('.sliderContent table').click(function (e) {
			e.stopPropagation();
		});

		// make sure we've clicked on a new element
		if (lastEle == ele)
		{
			// close any open sliders
			$('.sliderContent').slideUp('fast');
			// remove row highlighting
			$('.sliderContent').parent().parent().removeClass('rowSelected');
			lastEle = null;
			return false;
		}
		lastEle = ele;

		// close any open sliders
		$('.sliderContent').slideUp('fast');

		// remove row highlighting
		$('.sliderContent').parent().parent().removeClass('rowSelected');

		// select row and popup content
		$(ele).addClass('rowSelected');

		// set the position of the sliderContent div
		$(ele).find('.sliderContent').css('left', 0);
		$(ele).find('.sliderContent').css('top', ($(ele).offset().top + $(ele).height()) - $('.file-upload-wrapper .modal-content').offset().top);
		$(ele).find('.sliderContent').slideDown(400, function () {
		});

		return false;
	}

	function saveFileToFolder(ele)
	{
		// get variables
		shortUrl = $(ele).closest('.sliderContent').children('.shortUrlHidden').val();
		folderId = $(ele).val();

		// send ajax request
		var request = $.ajax({
			url: "<?php echo CORE_AJAX_WEB_ROOT; ?>/_update_folder.ajax.php",
			type: "POST",
			data: {shortUrl: shortUrl, folderId: folderId},
			dataType: "html"
		});
	}

	function showAdditionalOptions(hide)
	{
		if (typeof (hide) == "undefined")
		{
			hide = false;
		}

		if (($('#additionalOptionsWrapper').is(":visible")) || (hide == true))
		{
			$('#additionalOptionsWrapper').slideUp();
		}
		else
		{
			$('#additionalOptionsWrapper').slideDown();
		}
	}

	function saveAdditionalOptions(hide)
	{
		if (typeof (hide) == "undefined")
		{
			hide = false;
		}

		// save values globally
		fileToEmail = $('#send_via_email').val();
		filePassword = $('#set_password').val();
		fileCategory = $('#set_category').val();
		fileFolder = $('#upload_folder_id').val();

		// attempt ajax to save
		processAddtionalOptions();

		// hide
		showAdditionalOptions(hide);
	}

	function processAddtionalOptions()
	{
		// make sure the uploads have completed
		if (uploadComplete == false)
		{
			return false;
		}

		return sendAdditionalOptions();
	}

	function sendAdditionalOptions()
	{
		// make sure we have some urls
		if (fileDeleteHashes.length == 0)
		{
			return false;
		}

		$.ajax({
			type: "POST",
			url: "<?php echo WEB_ROOT; ?>/ajax/_update_file_options.ajax.php",
			data: {fileToEmail: fileToEmail, filePassword: filePassword, fileCategory: fileCategory, fileDeleteHashes: fileDeleteHashes, fileShortUrls: fileShortUrls, fileFolder: fileFolder}
		}).done(function (msg) {
			originalFolder = fileFolder;
			if(originalFolder == '')
			{
				originalFolder = '-1';
			}
			fileToEmail = '';
			filePassword = '';
			fileCategory = '';
			fileFolder = '';
			fileDeleteHashes = [];
			if (typeof updateStatsViaAjax === "function")
			{
				//updateStatsViaAjax();
			}
			if (typeof refreshFileListing === "function")
			{
				//refreshFileListing();
				loadImages(originalFolder);
			}

		});
	}
</script>

<?php
if ($showUploads == true) {
    ?>
    <script>
        function findUrls(text)
		{
			var source = (text || '').toString();
			var urlArray = [];
			var url;
			var matchArray;
			
			// standardise
			source = source.replace("\n\r", "\n");
			source = source.replace("\r", "\n");
			source = source.replace("\n\n", "\n");
			
			// split apart urls
			source = source.split("\n");

			// find urls
			var regexToken = /(\b(https?|ftp):\/\/[-A-Z0-9+&@#\/%?=~()_|\s!:,.;'\[\]]*[-A-Z0-9+&@#\/%=~()_'|\s])/ig;
			
			// validate urls
			for(i in source)
			{
				url = source[i];
				if(url.match(regexToken))
				{
					urlArray.push(url);
				}
			}

			return urlArray;
		}

        var currentUrlItem = 0;
        var totalUrlItems = 0;
        function urlUploadFiles()
        {
            // get textarea contents
            urlList = $('#urlList').val();
            if (urlList.length == 0)
            {
                alert('<?php echo str_replace("'", "\'", t('please_enter_the_urls_to_start', 'Please enter the urls to start.')); ?>');
                return false;
            }

            // get file list as array
            urlList = findUrls(urlList);
            totalUrlItems = urlList.length;

            // first check to make sure we have some urls
            if (urlList.length == 0)
            {
                alert('<?php echo str_replace("'", "\'", t('no_valid_urls_found_please_make_sure_any_start_with_http_or_https', 'No valid urls found, please make sure any start with http or https and try again.')); ?>');
                return false;
            }

            // make sure the user hasn't entered more than is permitted
            if (urlList.length > <?php echo (int) $maxPermittedUrls; ?>)
            {
                alert('<?php echo str_replace("'", "\'", t('you_can_not_add_more_than_x_urls_at_once', 'You can not add more than [[[MAX_URLS]]] urls at once.', array('MAX_URLS' => (int) $maxPermittedUrls))); ?>');
                return false;
            }

            // create table listing
            html = '';
            for (i in urlList)
            {
                html += '<tr id="rowId' + i + '"><td class="cancel"><a href="#" onClick="return false;"><img src="<?php echo SITE_IMAGE_PATH; ?>/processing_small.gif" class="processingIcon" height="16" width="16" alt="<?php echo str_replace("\"", "\\\"", t('processing', 'processing')); ?>"/>';
                html += '</a></td><td class="name" colspan="3">' + urlList[i] + '&nbsp;&nbsp;<span class="progressWrapper"><span class="progressText"></span></span></td></tr>';
            }
            $('#urlUpload #urls').html(html);

            // show file uploader screen
            $('#urlUpload #urlFileListingWrapper').removeClass('hidden');
            $('#urlUpload #urlFileUploader').addClass('hidden');

            // loop over urls and try to retrieve the file
            startRemoteUrlDownload(currentUrlItem);

        }

        function updateUrlProgress(data)
        {
            $.each(data, function (key, value) {
                switch (key)
                {
                    case 'progress':
                        percentageDone = parseInt(value.loaded / value.total * 100, 10);

                        textContent = '';
                        textContent += 'Progress: ' + percentageDone + '%';
                        textContent += ' ';
                        textContent += '(' + bytesToSize(value.loaded, 2) + ' / ' + bytesToSize(value.total, 2) + ')';

                        progressText = textContent;
                        $('#rowId' + value.rowId + ' .progressText').html(progressText);
                        break;
                    case 'done':
                        handleUrlUploadSuccess(value);

                        if ((currentUrlItem + 1) < totalUrlItems)
                        {
                            currentUrlItem = currentUrlItem + 1;
                            startRemoteUrlDownload(currentUrlItem);
                        }
                        break;
                }
            });
        }

        function startRemoteUrlDownload(index)
        {
            // show progress
            $('#urlUpload .urlFileListingWrapper .processing-button').removeClass('hidden');

            // get file list as array
            urlList = $('#urlList').val();
            urlList = findUrls(urlList);

            // create iframe to track progress
            var iframe = $('<iframe src="javascript:false;" style="display:none;"></iframe>');
            iframe
                    .prop('src', '<?php echo crossSiteAction::appendUrl(file::getUploadUrl() . "/core/page/ajax/url_upload_handler.ajax.php"); ?>&rowId=' + index + '&url=' + encodeURIComponent(urlList[index]) + '&folderId=' + fileFolder)
                    .appendTo(document.body);
        }

        function handleUrlUploadSuccess(data)
        {
            isSuccess = true;
            if (data.error != null)
            {
                isSuccess = false;
            }

            html = '';
            html += '<tr class="template-download';
            if (isSuccess == false)
            {
                html += ' errorText';
            }
            html += '" onClick="return showAdditionalInformation(this);">'
            if (isSuccess == false)
            {
                // add result html
                html += data.error_result_html;
            }
            else
            {
                // add result html
                html += data.success_result_html;

                // keep a copy of the urls globally
                fileUrls.push(data.url);
                fileUrlsHtml.push(data.url_html);
                fileUrlsBBCode.push(data.url_bbcode);
                fileDeleteHashes.push(data.delete_hash);
                fileShortUrls.push(data.short_url);
            }

            html += '</tr>';

            $('#rowId' + data.rowId).replaceWith(html);

            if (data.rowId == urlList.length - 1)
            {
                // show footer
                $('#urlUpload .urlFileListingWrapper .processing-button').addClass('hidden');
                $('#urlUpload .fileSectionFooterText').removeClass('hidden');

                // set additional options
                sendAdditionalOptions();

                // setup copy link
                setupCopyAllLink();
				
				delay(function() {
					$('#hide_modal_btn').click();
				}, 1500);
            }
        }
    </script>
    <?php
}
?>
