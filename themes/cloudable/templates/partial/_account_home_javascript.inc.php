<link rel="stylesheet" href="<?php echo SITE_CSS_PATH; ?>/file_browser_sprite_48px.css" type="text/css" charset="utf-8" />

<script type="text/javascript">
    var cur = -1, prv = -1;
    var pageStart = 0;
    var perPage = 30;
    var fileId = 0;
    var intialLoad = true;
    var uploaderShown = false;
    var fromFilterModal = false;
    var doubleClickTimeout = null;
    var backgroundFolderLoading = false;
	var clipboard = null;
	var triggerTreeviewLoad = true;
    $(function () {
        // initial button state
        updateFileActionButtons();

<?php if (defined('_INT_FILE_ID')): ?>
            showFileInformation(<?php echo (int) _INT_FILE_ID; ?>);
            backgroundFolderLoading = true;
<?php endif; ?>

		<?php if($Auth->loggedIn() == true): ?>
        // load folder listing
        $("#folderTreeview").jstree({
            "plugins": [
                "themes", "json_data", "ui", "types", "crrm", "contextmenu", "cookies"
            ],
            "themes": {
                "theme": "default",
                "dots": false,
                "icons": true
            },
            "core": {"animation": 150},
            "json_data": {
                "data": [
                    {
                        "data": "<?php echo t('file_manager', 'File Manager'); ?>",
                        "state": "closed",
                        "attr": {"id": "-1", "rel": "home", "original-text": "<?php echo str_replace("\"", "'", t('file_manager', 'File Manager')); ?>"}
                    },
                    {
                        "data": "<?php echo t('recent_files', 'Recent Files'); ?>",
                        "attr": {"id": "recent", "rel": "recent", "original-text": "<?php echo str_replace("\"", "'", t('recent_files', 'Recent Files')); ?>"}
                    },
                    {
                        "data": "<?php echo t('all_files', 'All Files'); ?><?php echo ($totalActive > 0) ? (' (' . $totalActive . ')') : ''; ?>",
                        "attr": {"id": "all", "rel": "all", "original-text": "<?php echo str_replace("\"", "'", t('all_files', 'All Files')); ?>"}
                    },
                    {
                        "data": "<?php echo t('trash_can', 'Trash Can'); ?><?php echo ($totalTrash > 0) ? (' (' . $totalTrash . ')') : ''; ?>",
                        "attr": {"id": "trash", "rel": "bin", "original-text": "<?php echo str_replace("\"", "'", t('trash_can', 'Trash Can')); ?>"}
                    }
                ],
                "ajax": {
                    "url": function (node) {
                        var nodeId = "";
                        var url = ""
                        if (node == -1)
                        {
                            url = "<?php echo CORE_AJAX_WEB_ROOT; ?>/_account_home_v2_folder_listing.ajax.php";
                        }
                        else
                        {
                            nodeId = node.attr('id');
                            url = "<?php echo CORE_AJAX_WEB_ROOT; ?>/_account_home_v2_folder_listing.ajax.php?folder=" + nodeId;
                        }

                        return url;
                    }
                }
            },
            "contextmenu": {
                "items": buildTreeViewContextMenu
            },
            'progressive_render': true
        }).bind("dblclick.jstree", function (event, data) {
            var node = $(event.target).closest("li");
            if ($(node).hasClass('jstree-leaf') == true)
            {
                return false;
            }

            //$("#folderTreeview").jstree("toggle_node", node.data("jstree"));
        }).bind("select_node.jstree", function (event, data) {
			// use this to stop the treeview from triggering a reload of the file manager
			if(triggerTreeviewLoad == false)
			{
				triggerTreeviewLoad = true;
				return false;
			}
            // add a slight delay encase this is a double click
            if (intialLoad == false)
            {
                // wait before loading the files, just encase this is a double click
                clickTreeviewNode(event, data);

                return false;
            }

            clickTreeviewNode(event, data);
        }).bind("load_node.jstree", function (event, data) {
            // assign click to icon
            assignNodeExpandClick();
			reSelectFolder();
        }).bind("open_node.jstree", function (event, data) {
            // reassign drag crop for sub-folder
            setupTreeviewDropTarget();
        }).delegate("a", "click", function (event, data) {
            event.preventDefault();
        }).bind('loaded.jstree', function (e, data) {
            // load default view if not stored in cookie
            var doIntial = true;
            if (typeof ($.cookie("jstree_open")) != "undefined")
            {
                if ($.cookie("jstree_open").length > 0)
                {
                    doIntial = false;
                }
            }

            if (doIntial == true)
            {
                $("#folderTreeview").jstree("open_node", $("#-1"));
            }

            // reload stats
            updateStatsViaAjax();
        });

        var doIntial = true;
        if (typeof ($.cookie("jstree_select")) != "undefined")
        {
            if ($.cookie("jstree_select").length > 0)
            {
                doIntial = false;
            }
        }
        if (doIntial == true)
        {
            // load file listing
            $('#nodeId').val('-1');
        }

        $('.layer').bind('drop', function (e) {
            // blocks upload popup on internal moves / folder icons
			if($(e.target).hasClass('folderIconLi') == false)
			{
				uploadFiles();
			}
        });

        $("#fileManager").click(function (event) {
            if (ctrlPressed == false)
            {
                if ($(event.target).is('ul') || $(event.target).hasClass('fileManager')) {
                    clearSelected();
                }
            }
        });

        setupFileDragSelect();
		<?php endif; ?>
    });

    function assignNodeExpandClick()
    {
        $('.jstree-icon').off('click');
        $('.jstree-icon').on('click', function (event) {
            var node = $(event.target).parent().parent();
            if ($(node).hasClass('jstree-leaf') != true)
            {
                // expand
                $("#folderTreeview").jstree("toggle_node", $(node));

                // stop the node from being selected
                event.stopPropagation();
                event.preventDefault();
            }
        });
    }

    function clickTreeviewNode(event, data)
    {
        clearSelected();
        clearSearchFilters(false);
		cancelPendingNetworkRequests();

        // load via ajax
        if (intialLoad == true)
        {
            intialLoad = false;
        }
        else
        {
            $('#nodeId').val(data.rslt.obj.attr("id"));
            $('#folderIdDropdown').val($('#nodeId').val());
            if (typeof (setUploadFolderId) === 'function')
            {
                setUploadFolderId($('#nodeId').val());
            }
            loadImages(data.rslt.obj.attr("id"));
        }
    }
	
	function cancelPendingNetworkRequests()
	{
		// disabled due to adverse side effects on refresh
		return false;
		
		// don't cancel if we're uploading files
		if(uploadComplete == false)
		{
			return false;
		}
		
		if(window.stop !== undefined)
		{
			 window.stop();
		}
		else if(document.execCommand !== undefined)
		{
			 document.execCommand("Stop", false);
		}
	}

    function updateFolderDropdownMenuItems()
    {
        // not a sub folder
        if (isPositiveInteger($('#nodeId').val()) == false)
        {
            $('#subFolderOptions').hide();
            $('#topFolderOptions').show();
        }
        // all sub folders / menu options
        else
        {
            $('#topFolderOptions').hide();
            $('#subFolderOptions').show();
        }
    }

    function reloadDragItems()
    {
        $('.fileIconLi')
			.drop("start", function () {
				$(this).removeClass("active");
				if ($(this).hasClass("selected") == false)
				{
					$(this).addClass("active");
				}
			})
			.drop(function (ev, dd) {
				selectFile($(this).attr('fileId'), true);
			})
			.drop("end", function () {
				$(this).removeClass("active");
			});
        $.drop({multi: true});
    }

    function refreshFolderListing(triggerLoad)
    {
		if(typeof(triggerLoad) != "undefined")
		{
			triggerTreeviewLoad = triggerLoad;
		}
		
        $("#folderTreeview").jstree("refresh");
    }

    function buildTreeViewContextMenu(node)
    {
        var items = {};
        if ($(node).attr('id') == 'trash')
        {
            var items = {
                "Empty": {
                    "label": "<?php echo t('empty_trash', 'Empty Trash'); ?>",
					"icon": "glyphicon glyphicon-trash",
                    "action": function (obj) {
                        confirmEmptyTrash();
                    }
                }
            };
        }
        else if ($(node).attr('id') == '-1')
        {
            var items = {
                "Upload": {
                    "label": "<?php echo t('upload_files', 'Upload Files'); ?>",
					"icon": "glyphicon glyphicon-cloud-upload",
                    "separator_after": true,
                    "action": function (obj) {
                        uploadFiles('');
                    }
                },
                "Add": {
                    "label": "<?php echo t('add_folder', 'Add Folder'); ?>",
					"icon": "glyphicon glyphicon-plus",
                    "action": function (obj) {
                        showAddFolderForm(obj.attr("id"));
                    }
                }
            };
        }
        else if ($.isNumeric($(node).attr('id')))
        {
            var items = {
                "Upload": {
                    "label": "<?php echo t('upload_files', 'Upload Files'); ?>",
					"icon": "glyphicon glyphicon-cloud-upload",
                    "separator_after": true,
                    "action": function (obj) {
                        uploadFiles(obj.attr("id"));
                    }
                },
				"Add": {
                    "label": "<?php echo t('add_sub_folder', 'Add Sub Folder'); ?>",
					"icon": "glyphicon glyphicon-plus",
                    "action": function (obj) {
                        showAddFolderForm(obj.attr("id"));
                    }
                },
                "Edit": {
                    "label": "<?php echo t('edit_folder', 'Edit'); ?>",
					"icon": "glyphicon glyphicon-pencil",
                    "action": function (obj) {
                        showAddFolderForm(null, obj.attr("id"));
                    }
                },
                "Delete": {
                    "label": "<?php echo t('delete_folder', 'Delete'); ?>",
					"icon": "glyphicon glyphicon-remove",
                    "action": function (obj) {
                        confirmRemoveFolder(obj.attr("id"));
                    }
                },
                "Download": {
                    "label": "<?php echo t('download_all_files', 'Download All Files (Zip)'); ?>",
					"icon": "glyphicon glyphicon-floppy-save",
                    "separator_before": true,
                    "action": function (obj) {
                        downloadAllFilesFromFolder(obj.attr("id"));
                    }
                },
				"Share": {
                    "label": "<?php echo t('share_folder', 'Share Folder'); ?>",
					"icon": "glyphicon glyphicon-share",
                    "action": function (obj) {
						showFolderSharingForm(obj.attr("id"));
                    }
                }
            };
        }

        return items;
    }

    function confirmRemoveFolder(folderId)
    {
        // only allow actual sub folders
        if (isPositiveInteger(folderId) == false)
        {
            return false;
        }

        if (confirm('<?php echo str_replace('\'', '', t('are_you_sure_you_want_to_remove_this_folder', 'Are you sure you want to remove this folder? Any files within the folder will be moved into your default root folder and remain active.')); ?>'))
        {
            removeFolder(folderId);
        }

        return false;
    }

    function removeFolder(folderId)
    {
        $.ajax({
            dataType: "json",
            url: "<?php echo CORE_AJAX_WEB_ROOT; ?>/_account_delete_folder.ajax.php",
            data: {folderId: folderId},
            success: function (data) {
                if (data.error == true)
                {
                    showErrorNotification('Error', data.msg);
                }
                else
                {
                    // refresh treeview
                    showSuccessNotification('Success', data.msg);
                    refreshFolderListing();
                }
            }
        });
    }

    function confirmEmptyTrash()
    {
        if (confirm('<?php echo str_replace('\'', '', t('are_you_sure_you_want_to_empty_the_trash', 'Are you sure you want to empty the trash can? Any statistics and other file information will be permanently deleted.')); ?>'))
        {
            emptyTrash();
        }

        return false;
    }

    function emptyTrash()
    {
        $.ajax({
            dataType: "json",
            url: "<?php echo CORE_AJAX_WEB_ROOT; ?>/_account_empty_trash.ajax.php",
            success: function (data) {
                if (data.error == true)
                {
                    alert(data.msg);
                }
                else
                {
                    // reload file listing
                    loadFiles();

                    // reload stats
                    updateStatsViaAjax();
                }
            }
        });
    }

    var hideLoader = false;
    function loadFiles(folderId)
    {
        // get variables
        if (typeof (folderId) == 'undefined')
        {
            folderId = $('#nodeId').val();
        }

        loadImages(folderId);
    }

    function dblClickFile(fileId)
    {

    }
	
	function clearExistingHoverFileItem()
	{
		$('.hoverItem').removeClass('hoverItem');
	}

    function showFileMenu(liEle, clickEvent)
    {
        clickEvent.stopPropagation();
		hideOpenContextMenus();
		
        fileId = $(liEle).attr('fileId');
        downloadUrl = $(liEle).attr('dtfullurl');
        statsUrl = $(liEle).attr('dtstatsurl');
        isDeleted = $(liEle).hasClass('fileDeletedLi');
        fileName = $(liEle).attr('dtfilename');
        extraMenuItems = $(liEle).attr('dtextramenuitems');
        var items = {
            "Stats": {
                "label": "<?php echo UCWords(t('account_file_details_stats', 'Stats')); ?>",
				"icon": "glyphicon glyphicon-stats",
                "action": function (obj) {
                    showStatsPopup(fileId);
                }
            }
        };

        if (isDeleted == false)
        {
            var items = {};

            // replace any items for overwriting (plugins)
            if (extraMenuItems.length > 0)
            {
                items = JSON.parse(extraMenuItems);
                for (i in items)
                {
                    // setup click action on menu item
                    eval("items['" + i + "']['action'] = " + items[i]['action']);
                }
            }
			
			// default menu items
            items["View"] = {
                "label": "<?php echo UCWords(t('account_file_details_view', 'View')); ?>",
				"icon": "glyphicon glyphicon-zoom-in",
                "action": function (obj) {
                    showImage(fileId);
                }
            };

            items["Download"] = {
                "label": "<?php echo UCWords(t('account_file_details_download', 'Download')); ?> " + fileName,
				"icon": "glyphicon glyphicon-download-alt",
                "separator_after": true,
                "action": function (obj) {
                    openUrl('<?php echo CORE_PAGE_WEB_ROOT; ?>/account_home_v2_direct_download.php?fileId=' + fileId);
                }
            };            

            items["Edit"] = {
                "label": "<?php echo UCWords(t('account_file_details_edit_file_info', 'Edit File Info')); ?>",
				"icon": "glyphicon glyphicon-pencil",
                "action": function (obj) {
                    showEditFileForm(fileId);
                }
            };
			
			items["Duplicate"] = {
                "label": "<?php echo UCWords(t('account_file_details_create_copy', 'Create Copy')); ?>",
				"icon": "glyphicon glyphicon-plus-sign",
                "action": function (obj) {
					selectFile(fileId, true);
                    duplicateFiles();
                }
            };

            items["Delete"] = {
                "label": "<?php echo UCWords(t('account_file_details_delete', 'Delete')); ?>",
				"icon": "glyphicon glyphicon-remove",
                "separator_after": true,
                "action": function (obj) {
                    selectFile(fileId, true);
                    deleteFiles();
                }
            };
			
			items["Copy"] = {
                "label": "<?php echo t('copy_url_to_clipboard', 'Copy Url to Clipboard'); ?>",
				"icon": "entypo entypo-clipboard",
				"classname": "fileMenuItem"+fileId,
                "separator_after": true,
                "action": function (obj) {
					selectFile(fileId, true);
					fileUrlText = '';
					for (i in selectedItems)
					{
						fileUrlText += selectedItems[i][3] + "<br/>";
					}
                    $('#clipboard-placeholder').html(fileUrlText);
					copyToClipboard('.fileMenuItem'+fileId);
                }
            };

			items["Select"] = {
                "label": "<?php echo UCWords(t('account_file_details_select_file', 'Select File')); ?> ",
				"icon": "glyphicon glyphicon-check",
                "action": function (obj) {
                    selectFile(fileId, true);
                }
            };

            items["Links"] = {
                "label": "<?php echo UCWords(t('file_manager_links', 'Links')); ?>",
				"icon": "glyphicon glyphicon-link",
                "action": function (obj) {
                    selectFile(fileId, true);
                    viewFileLinks();
                    // clear selected if only 1
                    if (countSelected() == 1)
                    {
                        clearSelected();
                    }
                }
            };

            items["Stats"] = {
                "label": "<?php echo UCWords(t('account_file_details_stats', 'Stats')); ?>",
				"icon": "glyphicon glyphicon-stats",
                "action": function (obj) {
                    showStatsPopup(fileId);
                }
            };

            // replace any items for overwriting
            for (i in extraMenuItems)
            {
                if (typeof (items[i]) != 'undefined')
                {
                    items[i] = extraMenuItems[i];
                }
            }
        }
        $.vakata.context.show(items, $(liEle), clickEvent.pageX - 15, clickEvent.pageY - 8, liEle);
        return false;
    }
	
	function showFolderMenu(liEle, clickEvent)
    {
        clickEvent.stopPropagation();
        folderId = $(liEle).attr('folderId');
		var items = {
                "Upload": {
                    "label": "<?php echo t('upload_files', 'Upload Files'); ?>",
					"icon": "glyphicon glyphicon-cloud-upload",
                    "separator_after": true,
                    "action": function (obj) {
                        uploadFiles(folderId);
                    }
                },
				"Add": {
                    "label": "<?php echo t('add_sub_folder', 'Add Sub Folder'); ?>",
					"icon": "glyphicon glyphicon-plus",
                    "action": function (obj) {
                        showAddFolderForm(folderId);
                    }
                },
                "Edit": {
                    "label": "<?php echo t('edit_folder', 'Edit'); ?>",
					"icon": "glyphicon glyphicon-pencil",
                    "action": function (obj) {
                        showAddFolderForm(null, folderId);
                    }
                },
                "Delete": {
                    "label": "<?php echo t('delete_folder', 'Delete'); ?>",
					"icon": "glyphicon glyphicon-remove",
                    "action": function (obj) {
                        confirmRemoveFolder(folderId);
                    }
                },
                "Download": {
                    "label": "<?php echo t('download_all_files', 'Download All Files (Zip)'); ?>",
					"icon": "glyphicon glyphicon-floppy-save",
                    "separator_before": true,
                    "action": function (obj) {
                        downloadAllFilesFromFolder(folderId);
                    }
                },
				"Copy": {
                    "label": "<?php echo t('copy_url_to_clipboard', 'Copy Url to Clipboard'); ?>",
					"icon": "entypo entypo-clipboard",
					"classname": "folderMenuItem"+folderId,
                    "separator_before": true,
                    "action": function (obj) {
						$('#clipboard-placeholder').html($('#folderItem'+folderId).attr('sharing-url'));
						copyToClipboard('.folderMenuItem'+folderId);
                    }
                },
                "Share": {
                    "label": "<?php echo t('share_folder', 'Share Folder'); ?>",
					"icon": "glyphicon glyphicon-share",
                    "action": function (obj) {
						showFolderSharingForm(folderId);
                    }
                }
            };

        $.vakata.context.show(items, $(liEle), clickEvent.pageX - 15, clickEvent.pageY - 8, liEle);
        return false;
    }

    function selectFile(fileId, onlySelectOn)
    {
        if (typeof (onlySelectOn) == "undefined")
        {
            onlySelectOn = false;
        }

        // clear any selected if ctrl key not pressed
        if ((ctrlPressed == false) && (onlySelectOn == false))
        {
            showFileInformation(fileId);

            return false;
        }

        elementId = 'fileItem' + fileId;
        if (($('.' + elementId).hasClass('selected')) && (onlySelectOn == false))
        {
            $('.' + elementId).removeClass('selected');
            if (typeof (selectedItems['k' + fileId]) != 'undefined')
            {
                delete selectedItems['k' + fileId];
            }
        }
        else
        {
            $('.' + elementId + '.owned-image:not(.fileDeletedLi)').addClass('selected');
            if ($('.' + elementId).hasClass('selected'))
            {
                selectedItems['k' + fileId] = [fileId, $('.' + elementId).attr('dttitle'), $('.' + elementId).attr('dtsizeraw'), $('.' + elementId).attr('dtfullurl'), $('.' + elementId).attr('dturlhtmlcode'), $('.' + elementId).attr('dturlbbcode')];
            }
        }

        updateSelectedFilesStatusText();
        updateFileActionButtons();
    }

    var ctrlPressed = false;
    $(window).keydown(function (evt) {
        if (evt.which == 17) {
            ctrlPressed = true;
        }
    }).keyup(function (evt) {
        if (evt.which == 17) {
            ctrlPressed = false;
        }
    });

    $(window).keydown(function (evt) {
        if (evt.which == 65) {
            if (ctrlPressed == true)
            {
                selectAllFiles();
                return false;
            }
        }
    })

    function updateFileActionButtons()
    {
        totalSelected = countSelected();
        if (totalSelected > 0)
        {
            $('.fileActionLinks').removeClass('disabled');

        }
        else
        {
            $('.fileActionLinks').addClass('disabled');
        }
    }

    function viewFileLinks()
    {
        count = countSelected();
        if (count > 0)
        {
            fileUrlText = '';
            htmlUrlText = '';
            bbCodeUrlText = '';
            for (i in selectedItems)
            {
                fileUrlText += selectedItems[i][3] + "<br/>";
                htmlUrlText += selectedItems[i][4] + "&lt;br/&gt;<br/>";
                bbCodeUrlText += '[URL='+selectedItems[i][3]+']'+selectedItems[i][3] + "[/URL]<br/>";
            }

            $('#popupContentUrls').html(fileUrlText);
            $('#popupContentHTMLCode').html(htmlUrlText);
            $('#popupContentBBCode').html(bbCodeUrlText);

            jQuery('#fileLinksModal').modal('show', {backdrop: 'static'}).on('shown.bs.modal');
        }
    }

    function showLightboxNotice()
    {
        jQuery('#generalModal').modal('show', {backdrop: 'static'}).on('shown.bs.modal', function () {
            $('.general-modal .modal-body').html($('#filePopupContentWrapperNotice').html());
        });
    }

    function showFileInformation(fileId)
    {
        // hide any context menus
        hideOpenContextMenus();

        // load overlay
        showFileInline(fileId);
    }

    function loadPage(startPos)
    {
		cancelPendingNetworkRequests();
        $('html, body').animate({
            scrollTop: $(".page-body").offset().top
        }, 700);
        pageStart = startPos;
        refreshFileListing();
    }

    function downloadAllFilesFromFolder(folderId)
    {
        // only allow actual sub folders
        if (isPositiveInteger(folderId) == false)
        {
            return false;
        }

        if (confirm("<?php echo t('account_home_are_you_sure_download_all_files', 'Are you sure you want to download all the files in this folder? This may take some time to complete.'); ?>"))
        {
            downloadAllFilesFromFolderConfirm(folderId);
        }

        return false;
    }

    function downloadAllFilesFromFolderConfirm(folderId)
    {
		$('.download-folder-modal .modal-body .col-md-9').html('');
        jQuery('#downloadFolderModal').modal('show', {backdrop: 'static'}).on('shown.bs.modal', function () {
            $('.download-folder-modal .modal-body .col-md-9').html('<iframe src="<?php echo CORE_AJAX_WEB_ROOT; ?>/_account_home_v2_download_all_folder_files.ajax.php?folderId=' + folderId + '" style="zoom:0.60" width="99.6%" height="440" frameborder="0"></iframe>');
        });
    }
	
	function downloadAllFilesFromFolderShared(folderId)
    {
        // only allow actual sub folders
        if (isPositiveInteger(folderId) == false)
        {
            return false;
        }

        if (confirm("<?php echo t('account_home_are_you_sure_download_all_files', 'Are you sure you want to download all the files in this folder? This may take some time to complete.'); ?>"))
        {
            downloadAllFilesFromFolderSharedConfirm(folderId);
        }

        return false;
    }
	
	function downloadAllFilesFromFolderSharedConfirm(folderId)
    {
		$('.download-folder-modal .modal-body .col-md-9').html('');
        jQuery('#downloadFolderModal').modal('show', {backdrop: 'static'}).on('shown.bs.modal', function () {
            $('.download-folder-modal .modal-body .col-md-9').html('<iframe src="<?php echo WEB_ROOT; ?>/ajax/_download_all_folder_files_shared.ajax.php?folderId=' + folderId + '" style="zoom:0.60" width="99.6%" height="440" frameborder="0"></iframe>');
        });
    }
</script>


<script>
    function showAddFolderForm(parentId, editFolderId)
    {
        // only allow actual sub folders on edit
        if ((typeof (editFolderId) != 'undefined') && (isPositiveInteger(editFolderId) == false))
        {
            return false;
        }

        showLoaderModal();
        if (typeof (parentId) == 'undefined')
        {
            parentId = $('#nodeId').val();
        }

        if (typeof (editFolderId) == 'undefined')
        {
            editFolderId = 0;
        }

        jQuery('#addEditFolderModal .modal-content').load("<?php echo WEB_ROOT; ?>/ajax/_account_add_edit_folder.ajax.php", {parentId: parentId, editFolderId: editFolderId}, function () {
            hideLoaderModal();
            jQuery('#addEditFolderModal').modal('show', {backdrop: 'static'}).on('shown.bs.modal', function () {
                $('#addEditFolderModal input').first().focus();
            });
        });
    }

<?php
// load folder structure as array
$folderListing = fileFolder::loadAllForSelect($Auth->id, '|||');
$folderListingArr = array();
foreach ($folderListing AS $k => $folderListingItem) {
    $folderListingArr[$k] = validation::safeOutputToScreen($folderListingItem);
}
$jsArray = json_encode($folderListing);
echo "var folderArray = " . $jsArray . ";\n";
?>
    function markInternalNotificationsRead()
    {
        $.ajax({
            dataType: "json",
            url: "<?php echo CORE_AJAX_WEB_ROOT; ?>/_account_internal_notification_mark_all_read.ajax.php",
            success: function (data) {
                $('.internal-notification .unread').addClass('read').removeClass('unread');
                $('.internal-notification .text-bold').removeClass('text-bold');
                $('.internal-notification .badge').hide();
                $('.internal-notification .unread-count').html('You have 0 new notifications.');
                $('.internal-notification .mark-read-link').hide();
            }
        });
    }

    progressWidget = null;
    function showProgressWidget(intialText, title, complete, timeout)
    {
		if(typeof(timeout) == "undefined")
		{
			timeout = 0;
		}
		
        if (progressWidget != null)
        {
            progressWidget.hide();
        }

        var opts = {
            "closeButton": false,
            "debug": false,
            "positionClass": "toast-bottom-right",
            "onclick": null,
            "showDuration": "300",
            "hideDuration": "1000",
            "timeOut": timeout,
            "extendedTimeOut": "0",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut",
            "onclick": function () {
                showUploaderPopup();
            }
        };

        if (complete == true)
        {
            progressWidget = toastr.success(intialText, title, opts);
        }
        else
        {
            progressWidget = toastr.info(intialText, title, opts);
        }
    }

    function updateProgressWidgetText(text)
    {
        if (progressWidget == null)
        {
            return false;
        }

        $(progressWidget).find('.toast-message').html(text);
    }

    function checkShowUploadProgressWidget()
    {
        if (uploadComplete == false)
        {
            showProgressWidget('<?php echo str_replace("'", "", t('file_manager_uploading', 'Uploading...')); ?>', '<?php echo str_replace("'", "", t('file_manager_upload_progress', 'Upload Progress:')); ?>', false);
        }
    }

    function checkShowUploadFinishedWidget()
    {
        showProgressWidget('<?php echo str_replace("'", "", t('file_manager_upload_complete', 'Upload complete.')); ?>', '<?php echo str_replace("'", "", t('file_manager_upload_progress', 'Upload Progress:')); ?>', true, 6000);
    }

    function updateStatsViaAjax()
    {
        // first request stats via ajax
        $.ajax({
            dataType: "json",
            url: "<?php echo CORE_AJAX_WEB_ROOT; ?>/_account_get_account_file_stats.ajax.php",
            success: function (data) {
                updateOnScreenStats(data);
            }
        });
    }

    function updateOnScreenStats(data)
    {
        // update list of folders for breadcrumbs
        folderArray = jQuery.parseJSON(data.folderArray);

        // update folder drop-down list in the popup uploader
        $("#folder_id").html(data.folderSelectForUploader);

        // update root folder stats
        if (data.totalRootFiles > 0)
        {
            $("#folderTreeview").jstree('set_text', '#-1', $('#-1').attr('original-text') + ' (' + data.totalRootFiles + ')');
        }
        else
        {
            $("#folderTreeview").jstree('set_text', '#-1', $('#-1').attr('original-text'));
        }

        // update trash folder stats
        if (data.totalTrashFiles > 0)
        {
            $("#folderTreeview").jstree('set_text', '#trash', $('#trash').attr('original-text') + ' (' + data.totalTrashFiles + ')');
        }
        else
        {
            $("#folderTreeview").jstree('set_text', '#trash', $('#trash').attr('original-text'));
        }

        // update all folder stats
        $("#folderTreeview").jstree('set_text', '#all', $('#all').attr('original-text') + ' (' + data.totalActiveFiles + ')');

        // update total storage stats
        $(".remaining-storage .progress .progress-bar").attr('aria-valuenow', data.totalStoragePercentage);
        $(".remaining-storage .progress .progress-bar").width(data.totalStoragePercentage + '%');
        $("#totalActiveFileSize").html(data.totalActiveFileSizeFormatted);
    }

    function isDesktopUser()
    {
        if ((getBrowserWidth() <= 1024) && (getBrowserWidth() > 0))
        {
            return false;
        }

        return true;
    }

    function getBrowserWidth()
    {
        return $(window).width();
    }

    function duplicateFiles()
    {
        if (countSelected() > 0)
        {
			duplicateFilesConfirm();
        }

        return false;
    }

    function duplicateFilesConfirm()
    {
        // show loader
        showLoaderModal(0);

        // prepare file ids
        fileIds = [];
        for (i in selectedItems)
        {
            fileIds.push(i.replace('k', ''));
        }

        // duplicate files
        $.ajax({
            type: "POST",
            url: "<?php echo CORE_AJAX_WEB_ROOT; ?>/_file_manage_bulk_duplicate.ajax.php",
            data: {fileIds: fileIds},
            dataType: 'json',
            success: function (json) {
                if (json.error == true)
                {
                    // hide loader
                    hideLoaderModal();
                    $('#filePopupContentNotice').html(json.msg);
                    showLightboxNotice();
                }
                else
                {
                    // done
                    addBulkSuccess(json.msg);
                    finishBulkProcess();
                }

            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                $('#popupContentNotice').html('Failed connecting to server, please try again later.');
                showLightboxNotice();
            }
        });
    }
</script>

<script type="text/javascript">
    function showFileInline(fileId)
    {
        showImage(fileId);
    }

    function showImageBrowseSlide(folderId)
    {
        $('#imageBrowseWrapper').show();
        $('#albumBrowseWrapper').hide();
        loadFiles(folderId);
    }

    function handleTopSearch(event, ele, isAdvSearch)
    {
		// make sure we have a default setting for advance search
		if(typeof(isAdvSearch) == 'undefined')
		{
			isAdvSearch = false;
		}
		
		searchText = $(ele).val();
        $('#filterText').val(searchText);

        // check for enter key
		doSearch = false;
		if(event == null)
		{
			doSearch = true;
		}
		else
		{
			var charCode = (typeof event.which === "number") ? event.which : event.keyCode;
			if (charCode == 13)
			{
				doSearch = true;
			}
		}
		
		// do search
		if(doSearch == true)
		{
			// make sure we have something to search
			if(searchText.length == 0)
			{
				showErrorNotification('Error', 'Please enter something to search for.');
				return false;
			}
			
			filterAllFolders = false;
			filterUploadedDateRange = '';
			if(isAdvSearch == true)
			{
				if($('#filterAllFolders').is(':checked'))
				{
					filterAllFolders = true;
				}
				filterUploadedDateRange = $('#filterUploadedDateRange').val();
			}
			
			url = WEB_ROOT+'/search/?s=image&filterAllFolders='+filterAllFolders+'&filterUploadedDateRange='+filterUploadedDateRange+'&t='+encodeURIComponent(searchText);
			window.location = url;
		}

        return false;
    }
	
	function showFolderSharingForm(folderId)
    {
		showLoaderModal();
        jQuery('#shareFolderModal .modal-content').load("<?php echo WEB_ROOT; ?>/ajax/_account_share_folder.ajax.php", {folderId: folderId}, function () {
            hideLoaderModal();
            jQuery('#shareFolderModal').modal('show', {backdrop: 'static'});
			createdUrl = false;
			
			setupPostPopup();
        });
    }
	
	function setupPostPopup()
	{
		// hover over tooptips
		setupToolTips();
		
		// radios
		replaceCheckboxes();
		
		// block enter key from being pressed
		$('#registeredEmailAddress').keypress(function (e) {
			if (e.which == 13)
			{
				return false;
			}
		});
	}
	
	function shareFolderInternally(folderId)
	{
		setShareFolderButtonLoading();
		$.ajax({
            dataType: "json",
            url: "<?php echo CORE_AJAX_WEB_ROOT; ?>/_share_folder_internally.ajax.php",
            data: {folderId: folderId, registeredEmailAddress: $('#registeredEmailAddress').val(), permissionType: $('input[name=permission_radio]:checked').val()},
            success: function (data) {
                if (data.error == true)
                {
                    showErrorNotification('Error', data.msg);
					clearShareFolderButtonLoading();
                }
                else
                {
					$('#registeredEmailAddress').val('');
					loadExistingInternalShareTable(data.folderId);
					clearShareFolderButtonLoading();
					showSuccessNotification('Success', data.msg);
                }
            }
        });
	}
	
	function loadExistingInternalShareTable(folderId)
	{
		$('#existingInternalShareTable').load("<?php echo WEB_ROOT; ?>/ajax/_account_existing_internal_share.ajax.php", {folderId: folderId}).hide().fadeIn();
	}
	
	function shareFolderInternallyRemove(folderShareId)
	{
		$.ajax({
            dataType: "json",
            url: "<?php echo CORE_AJAX_WEB_ROOT; ?>/_share_folder_internally_remove.ajax.php",
            data: {folderShareId: folderShareId},
            success: function (data) {
                if (data.error == true)
                {
                    showErrorNotification('Error', data.msg);
                }
                else
                {
					loadExistingInternalShareTable(data.folderId);
					showSuccessNotification('Success', data.msg);
                }
            }
        });
	}
	
	function setShareFolderButtonLoading()
	{
		$('#shareFolderInternallyBtn').removeClass('btn-info');
		$('#shareFolderInternallyBtn').addClass('btn-default disabled');
		$('#shareFolderInternallyBtn').html("<?php echo UCWords(t("processing", "processing")); ?> <i class=\"entypo-arrows-cw\"></i>");
	}
	
	function clearShareFolderButtonLoading()
	{
		$('#shareFolderInternallyBtn').removeClass('btn-default disabled');
		$('#shareFolderInternallyBtn').addClass('btn-info');
		$('#shareFolderInternallyBtn').html("<?php echo UCWords(t("grant_access", "grant access")); ?> <i class=\"entypo-lock\"></i>");
	}
	
	function copyToClipboard(ele)
	{
		destroyClipboard();
		clipboard = new Clipboard(ele);
		clipboard.on('success', function(e) {
			showSuccessNotification('Success', 'Copied to clipboard.');
			$('#clipboard-placeholder').html('');
		});

		clipboard.on('error', function(e) {
			showErrorNotification('Error', 'Failed copying to clipboard.');
		});
	}
	
	function destroyClipboard()
	{
		if(clipboard != null)
		{
			clipboard.destroy();
		}
	}
	
	callbackcheck = false;
	function showStatsPopup(fileId)
    {
		showLoaderModal();
        jQuery('#statsModal .modal-content').load("<?php echo WEB_ROOT; ?>/ajax/_file_stats.ajax.php", {fileId: fileId}, function () {
            hideLoaderModal();
            jQuery('#statsModal').modal('show', {backdrop: 'static'}).on('show', function() {
				callbackcheck = setTimeout(function(){
					redrawCharts();
					clearTimeout(callbackcheck);
				}, 100);
			});
        });
    }
	
	var createdUrl = false;
	function generateFolderSharingUrl(folderId)
	{
		$.ajax({
            dataType: "json",
            url: "<?php echo CORE_AJAX_WEB_ROOT; ?>/_generate_folder_sharing_url.ajax.php",
            data: {folderId: folderId},
            success: function (data) {
                if (data.error == true)
                {
                    showErrorNotification('Error', data.msg);
                }
                else
                {
                    $('#sharingUrlInput').html(data.msg);
					$('#shareEmailFolderUrl').html(data.msg);
					$('#nonPublicSharingUrls').fadeIn();
					$('#nonPublicSharingUrls').html($('.social-wrapper-template').html().replace(/SHARE_LINK/g, data.msg));
					createdUrl = true;
                }
            }
        });
	}
</script>

<?php
// output any extra account home javascript
pluginHelper::includeAppends('account_home_javascript.php');
?>