<?php
// initial constants
define('ADMIN_PAGE_TITLE', 'Manage Files');
define('ADMIN_SELECTED_PAGE', 'files');
define('ADMIN_SELECTED_SUB_PAGE', 'file_manage');

// includes and security
define('MIN_ACCESS_LEVEL', 10); // allow moderators
include_once('_local_auth.inc.php');

// page header
include_once('_header.inc.php');

// load all users
$sQL         = "SELECT id, username AS selectValue FROM users ORDER BY username";
$userDetails = $db->getRows($sQL);

// load all file servers
$sQL           = "SELECT id, serverLabel FROM file_server ORDER BY serverLabel";
$serverDetails = $db->getRows($sQL);

// load all file status
$sQL           = "SELECT id, label FROM file_status ORDER BY label";
$statusDetails = $db->getRows($sQL);

// defaults
$filterText = '';
if (isset($_REQUEST['filterText']))
{
    $filterText = trim($_REQUEST['filterText']);
}

$filterByStatus = 1;
if (isset($_REQUEST['filterByStatus']))
{
    $filterByStatus = (int) $_REQUEST['filterByStatus'];
}

$filterByServer = null;
if (isset($_REQUEST['filterByServer']))
{
    $filterByServer = (int) $_REQUEST['filterByServer'];
}

$filterByUser = null;
$filterByUserLabel = '';
if (isset($_REQUEST['filterByUser']))
{
    $filterByUser = (int) $_REQUEST['filterByUser'];
	$filterByUserLabel = $db->getValue('SELECT username FROM users WHERE id = '.(int)$filterByUser.' LIMIT 1');
}

// UPLOAD SOURCE
$filterBySource = null;
if (isset($_REQUEST['filterBySource']))
{
    $filterBySource = $_REQUEST['filterBySource'];
}
// UPLOAD SOURCE
?>

<script>
    oTable = null;
    gFileId = null;
    gEditFileId = null;
    checkboxIds = {};
    $(document).ready(function(){
        // datatable
        oTable = $('#fileTable').dataTable({
            "sPaginationType": "full_numbers",
            "bServerSide": true,
            "bProcessing": true,
            "sAjaxSource": 'ajax/file_manage.ajax.php',
            "bJQueryUI": true,
            "iDisplayLength": 25,
            "aaSorting": [[ 2, "desc" ]],
            "aoColumns" : [   
                { bSortable: false, sWidth: '3%', sName: 'file_icon', sClass: "center adminResponsiveHide" },
                { sName: 'filename' },
                { sName: 'date_uploaded', sWidth: '12%', sClass: "center adminResponsiveHide" },
                { sName: 'filesize', sWidth: '10%', sClass: "center adminResponsiveHide" },
                { sName: 'downloads', sWidth: '10%', sClass: "center adminResponsiveHide" },
                { sName: 'owner', sWidth: '11%', sClass: "center adminResponsiveHide" },
                { sName: 'status', sWidth: '11%', sClass: "center adminResponsiveHide" },
                { bSortable: false, sWidth: '195px', sClass: "center removeMultiFilesButton" }
            ],
            "fnServerData": function ( sSource, aoData, fnCallback ) {
                aoData.push( { "name": "filterText", "value": $('#filterText').val() } );
                aoData.push( { "name": "filterByUser", "value": $('#filterByUser').val() } );
                aoData.push( { "name": "filterByServer", "value": $('#filterByServer').val() } );
                aoData.push( { "name": "filterByStatus", "value": $('#filterByStatus').val() } );
                aoData.push( { "name": "filterBySource", "value": $('#filterBySource').val() } );
                $.ajax({
                    "dataType": 'json',
                    "type": "GET",
                    "url": "ajax/file_manage.ajax.php",
                    "data": aoData,
                    "success": fnCallback
                });
            },
            "fnDrawCallback": function (oSettings) {
                reloadCheckedItems();
				updateView();
            }
        });
        
        // update custom filter
        $('.dataTables_filter').html($('#customFilter').html());
        
        // dialog box
        $( "#confirmDelete" ).dialog({
            modal: true,
            autoOpen: false,
            width: getDefaultDialogWidth(),
            buttons: {
                "Delete File": function() {
                    removeFile(function() {
                        $("#confirmDelete").dialog("close");
                    });
                },
                "Cancel": function() {
                    $("#confirmDelete").dialog("close");
                }
            },
            open: function() {
                resetOverlays();
            }
        });
        
        $( "#showNotes" ).dialog({
            modal: true,
            autoOpen: false,
            width: getDefaultDialogWidth(),
            buttons: {
                "OK": function() {
                    $("#showNotes").dialog("close");
                }
            },
            open: function() {
                resetOverlays();
            }
        });
        
        $("#editFileForm").dialog({
            modal: true,
            autoOpen: false,
            width: getDefaultDialogWidth(),
            height: 602,
            buttons: {
                "Update File": function() {
                    processEditFile();
                },
                "Cancel": function() {
                    $("#editFileForm").dialog("close");
                }
            },
            open: function() {
                loadEditFileForm();
                resetOverlays();
            }
        });
        
        $("#moveFilesForm").dialog({
            modal: true,
            autoOpen: false,
            width: getDefaultDialogWidth(),
            height: 280,
            buttons: {
                "Move File(s)": function() {
                    processMoveFileForm();
                },
                "Cancel": function() {
                    $("#moveFilesForm").dialog("close");
                }
            },
            open: function() {
                loadMoveFileForm();
                resetOverlays();
            }
        });
		$('#filterByUser').autocomplete({			
			source: function( request, response ) {
				$.ajax({
					url : 'ajax/file_manage_auto_complete.ajax.php',
					dataType: "json",
					data: {
					   filterByUser: $("#filterByUser").val()
					},
					 success: function( data ) {
						 response( data );
					}
				});
			},
			autoFocus: true,
			minLength: 3,
			select: function( event, ui ) { 
				$('#filterByUser').val(ui.item.value);
				reloadTable();
			}
		  });
    });
	
	function sleep(milliseconds) {
  var start = new Date().getTime();
  for (var i = 0; i < 1e7; i++) {
    if ((new Date().getTime() - start) > milliseconds){
      break;
    }
  }
}
    
    function bulkMoveFiles()
    {
        if(countCheckboxFiles() == 0)
        {
            alert('Please select some files to move.');
            return false;
        }
        
        // show popup
        $('#moveFilesForm').dialog('open');
    }
    
    function loadMoveFileForm()
    {
        $('#moveFilesRawFileForm').html('');
        $.ajax({
            type: "POST",
            url: "ajax/file_manage_move_form.ajax.php",
            dataType: 'json',
            success: function(json) {
                if (json.error == true)
                {
                    $('#moveFilesRawFileForm').html(json.msg);
                }
                else
                {
                    $('#moveFilesRawFileForm').html(json.html);
                }

            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                $('#moveFilesRawFileForm').html(XMLHttpRequest.responseText);
            }
        });
    }
    
    function processMoveFileForm()
    {
        // get data
        serverIds = $('#server_ids').val();
        $.ajax({
            type: "POST",
            url: "ajax/file_manage_move_form_process.ajax.php",
            data: {serverIds: serverIds, gFileIds: getCheckboxFiles()},
            dataType: 'json',
            success: function(json) {
                if (json.error == true)
                {
                    showError(json.msg, 'popupMessageContainer');
                }
                else
                {
                    showSuccess(json.msg);
                    reloadTable();
                    clearBulkResponses();
                    checkboxIds = {};
                    updateButtonText();
                    $("#moveFilesForm").dialog("close");
                }

            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                showError(XMLHttpRequest.responseText, 'popupMessageContainer');
            }
        });

    }

    function reloadTable()
    {
        oTable.fnDraw(false);
    }
    
    function confirmRemoveFile(fileId)
    {
        $('#confirmDelete').dialog('open');
        gFileId = fileId;
    }
    
    function showNotes(notes)
    {
        $('#showNotes').html(notes);
        $('#showNotes').dialog('open');
    }
    
    function removeFile(callback)
    {
        // find out file server first
        $.ajax({
            type: "POST",
            url: "ajax/get_file_server_path.ajax.php",
            data: { fileId: gFileId },
            dataType: 'json',
            success: function(jsonOuter) {
                if(jsonOuter.error == true)
                {
                    showError(jsonOuter.msg);
                }
                else
                {
                    // delete file
                    $.ajax({
                        type: "POST",
                        url: "<?php echo _CONFIG_SITE_PROTOCOL; ?>://"+jsonOuter.filePath+"/<?php echo ADMIN_FOLDER_NAME; ?>/ajax/update_file_state.ajax.php",
                        data: { fileId: gFileId, statusId: $('#removal_type').val(), adminNotes: $('#admin_notes').val(), blockUploads: $('#block_uploads').val(), csaKey1: jsonOuter.csaKey1, csaKey2: jsonOuter.csaKey2 },
                        dataType: 'json',
                        xhrFields: {
                            withCredentials: true
                        },
                        success: function(json) {
                            if(json.error == true)
                            {
                                showError(json.msg);
                            }
                            else
                            {
                                showSuccess(json.msg);
                                $('#removal_type').val(3);
                                $('#admin_notes').val('');
                                reloadTable();
                                callback();
                            }

                        },
                        error: function (XMLHttpRequest, textStatus, errorThrown) {
                            showError(XMLHttpRequest.responseText);
                        }
                    });
                }
                
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                showError(XMLHttpRequest.responseText);
            }
        });
    }
    
    function toggleFileIds(ele)
    {
        if($(ele).is(':checked'))
        {
            checkboxIds['cb'+$(ele).val()] = $(ele).val();
        }
        else
        {
            if(typeof(checkboxIds['cb'+$(ele).val()]) != 'undefined')
            {
                delete checkboxIds['cb'+$(ele).val()];
            }
        }
        
        updateButtonText();
    }
    
    function reloadCheckedItems()
    {
        for(i in checkboxIds)
        {
            $elementId = 'cbElement'+checkboxIds[i];
            if(typeof($('#'+$elementId)) != 'undefined')
            {
                $('#'+$elementId).prop('checked', true);
            }
        }
    }
    
    function updateButtonText()
    {
        totalFiles = countCheckboxFiles();
        if(totalFiles == 0)
        {
            totalFiles = '';
            $('#removeMultiFilesButton').removeClass('blue');
            $('#deleteMultiFilesButton').removeClass('blue');
            $('#moveMultiFilesButton').removeClass('blue');
        }
        else
        {
            totalFiles = ' ('+totalFiles+')';
            $('#removeMultiFilesButton').addClass('blue');
            $('#deleteMultiFilesButton').addClass('blue');
            $('#moveMultiFilesButton').addClass('blue');
        }
        
        baseRemoveText = "<?php echo adminFunctions::t('remove_files_total', 'Remove Files[[[FILE_COUNT]]]'); ?>";
        baseRemoveText = baseRemoveText.replace('[[[FILE_COUNT]]]', totalFiles);
        $('#removeMultiFilesButton').html(baseRemoveText);
        
        baseDeleteText = "<?php echo adminFunctions::t('delete_files_and_data_total', 'Delete Files And Stats Data[[[FILE_COUNT]]]'); ?>";
        baseDeleteText = baseDeleteText.replace('[[[FILE_COUNT]]]', totalFiles);
        $('#deleteMultiFilesButton').html(baseDeleteText);
        
        baseMoveText = "<?php echo adminFunctions::t('move_files_total', 'Move Files[[[FILE_COUNT]]]'); ?>";
        baseMoveText = baseMoveText.replace('[[[FILE_COUNT]]]', totalFiles);
        $('#moveMultiFilesButton').html(baseMoveText);
    }
    
    function countCheckboxFiles()
    {
        count = 0;
        for(i in checkboxIds)
        {
            count++;
        }
        
        return count;
    }
    
    function getCheckboxFiles()
    {
        count = 0;
        for(i in checkboxIds)
        {
            count++;
        }
        
        return checkboxIds;
    }
    
    function bulkDeleteFiles(deleteData)
    {
        if(typeof(deleteData) == 'undefined')
        {
            deleteData = false;
        }

        if(countCheckboxFiles() == 0)
        {
            alert('Please select some files to remove.');
            return false;
        }
        
        msg = 'Are you sure you want to remove '+countCheckboxFiles()+' files? This can not be undone once confirmed.';
        if(deleteData == true)
        {
            msg += '\n\nAll file data and associated data such as the stats, will also be deleted from the database. This will entirely clear any record of the upload. (exc logs)';
        }
        else
        {
            msg += '\n\nThe original file record will be retained along with the file stats.';
        }
        
        if(confirm(msg))
        {
            bulkDeleteConfirm(deleteData);
        }
    }
    
    var bulkError = '';
    var bulkSuccess = '';
    var totalDone = 0;
    function addBulkError(x)
    {
        bulkError += x;
    }
    function getBulkError(x)
    {
        return bulkError;
    }
    function addBulkSuccess(x)
    {
        bulkSuccess += x;
    }
    function getBulkSuccess(x)
    {
        return bulkSuccess;
    }
    function clearBulkResponses()
    {
        bulkError = '';
        bulkSuccess = '';
    }
    function bulkDeleteConfirm(deleteData)
    {
        // get server list first
        $.ajax({
            type: "POST",
            url: "ajax/get_all_file_server_paths.ajax.php",
            data: { fileIds: checkboxIds },
            dataType: 'json',
            success: function(jsonOuter) {
                if(jsonOuter.error == true)
                {
                    showError(jsonOuter.msg);
                }
                else
                {
                    // loop file servers and attempt to remove files
                    totalDone = 0;
                    filePathsObj = jsonOuter.filePaths;
                    affectedServers = 0;
                    for(filePath in filePathsObj)
                    {
                        affectedServers++;
                    }
                    for(filePath in filePathsObj)
                    {
                        //  call server with file ids to delete
                        $.ajax({
                            type: "POST",
                            url: "<?php echo _CONFIG_SITE_PROTOCOL; ?>://"+filePath+"/<?php echo ADMIN_FOLDER_NAME; ?>/ajax/file_manage_bulk_delete.ajax.php",
                            data: { fileIds: filePathsObj[filePath]['fileIds'], deleteData: deleteData, csaKey1: filePathsObj[filePath]['csaKey1'], csaKey2: filePathsObj[filePath]['csaKey2'] },
                            dataType: 'json',
                            xhrFields: {
                                withCredentials: true
                            },
                            success: function(json) {
                                if(json.error == true)
                                {
                                    addBulkError(filePath+': '+json.msg+'<br/>');
                                }
                                else
                                {
                                    addBulkSuccess(filePath+': '+json.msg+'<br/>');
                                }
                                
                                totalDone++;
                                if(totalDone == affectedServers)
                                {
                                    finishBulkProcess();
                                }
                            },
                            error: function (XMLHttpRequest, textStatus, errorThrown) {
                                addBulkError(filePath+": Failed connecting to server to remove files.<br/>");
                                totalDone++;
                                if(totalDone == affectedServers)
                                {
                                    finishBulkProcess();
                                }
                            }
                        });
                    }
                }
                
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                showError('Failed connecting to server to get the list of servers, please try again later.');
            }
        });
    }
    
    function finishBulkProcess()
    {
        // get final response
        bulkError = getBulkError();
        bulkSuccess = getBulkSuccess();

        // compile result
        if(bulkError.length > 0)
        {
            showError(bulkError+bulkSuccess);
        }
        else
        {
            showSuccess(bulkSuccess);
        }
        reloadTable();
        clearBulkResponses();
        checkboxIds = {};
        updateButtonText();
        
        // scroll to the top of the page
        $("html, body").animate({ scrollTop: 0 }, "slow");
        $('#selectAllCB').prop('checked', false);
    }
    
    function toggleSelectAll()
    {
        if($('#selectAllCB').is(':checked'))
        {
            selectAllFiles();
        }
        else
        {
            deselectAllFiles();
        }
    }
    
    function selectAllFiles()
    {
        $("#fileTable .checkbox").each(function(index, ele) {
            checkboxIds['cb'+$(ele).val()] = $(ele).val();
        });
        reloadCheckedItems();
        updateButtonText();
    }
    
    function deselectAllFiles()
    {
        $("#fileTable .checkbox").each(function(index, ele) {
            if(typeof(checkboxIds['cb'+$(ele).val()]) != 'undefined')
            {
                delete checkboxIds['cb'+$(ele).val()];
                $('#cbElement'+$(ele).val()).prop('checked', false);
            }
        });
        reloadCheckedItems();
        updateButtonText();
        $('#selectAllCB').prop('checked', false);
    }
    
    function editFile(fileId)
    {
        gEditFileId = fileId;
        $('#editFileForm').dialog('open');
    }
    
    function loadEditFileForm()
    {
        $('#editFileFormInner').html('');
        $.ajax({
            type: "POST",
            url: "ajax/file_manage_edit_form.ajax.php",
            data: {gEditFileId: gEditFileId},
            dataType: 'json',
            success: function(json) {
                if (json.error == true)
                {
                    $('#editFileFormInner').html(json.msg);
                }
                else
                {
                    $('#editFileFormInner').html(json.html);
                    toggleFilePasswordField();
                }

            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                $('#editFileFormInner').html(textStatus+': '+errorThrown);
            }
        });
    }
    
    function toggleFilePasswordField()
    {
        if($('#editFileForm #enablePassword').is(':checked'))
        {
            $('#editFileForm #password').attr('READONLY', false);
        }
        else
        {
            $('#editFileForm #password').attr('READONLY', true);
        }
    }
    
    function processEditFile()
    {
        // get data
        filename = $('#filename').val();
        file_owner = $('#file_owner').val();
        short_url = $('#short_url').val();
        enablePassword = $('#enablePassword').prop('checked');
        password = $('#password').val();
        mime_type = $('#mime_type').val();
        min_user_level = $('#min_user_level').val();
        admin_notes = $('#edit_admin_notes').val();
        existing_file_id = gEditFileId;

        $.ajax({
            type: "POST",
            url: "ajax/file_manage_edit_process.ajax.php",
            data: {existing_file_id: existing_file_id, filename: filename, file_owner: file_owner, short_url: short_url, mime_type: mime_type, min_user_level: min_user_level, admin_notes: admin_notes, enablePassword: enablePassword, password: password},
            dataType: 'json',
            success: function(json) {
                if (json.error == true)
                {
                    showError(json.msg, 'popupMessageContainer');
                }
                else
                {
                    showSuccess(json.msg);
                    reloadTable();
                    $("#editFileForm").dialog("close");
                }

            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                showError(textStatus+': '+errorThrown, 'popupMessageContainer');
            }
        });

    }
	
	function updateView()
	{
		var newView = $('#updateView').val();
		if(newView == 'list')
		{
			$('.file-thumbnail-view').hide();
			$('.file-listing-view').show();
		}
		else
		{
			$('.file-listing-view').hide();
			$('.file-thumbnail-view').show();
		}
	}
	
	function updateSelectedRemoveFileSelect()
	{
		if($('#removal_type').val() == '4')
		{
			$('#block_uploads').val('1');
		}
		else
		{
			$('#block_uploads').val('0');
		}
	}
</script>

<div class="row clearfix">
    <div class="sectionLargeIcon largeFileIcon"></div>
    <div class="widget clearfix">
        <h2>File List</h2>
        <div class="widget_inside responsiveTable">
            <?php echo adminFunctions::compileNotifications(); ?>
            <div class="col_12">
                <table id='fileTable' class='dataTable'>
                    <thead>
                        <tr>
                            <th ><div style="padding-top: 5px;"><input type="checkbox" id="selectAllCB" onClick="toggleSelectAll();"/></div></th>
                            <th class="align-left fileManageFileName"><?php echo UCWords(adminFunctions::t('filename', 'Filename')); ?></th>
							<th class="align-left"><?php echo UCWords(adminFunctions::t('date_uploaded', 'Date Uploaded')); ?></th>
                            <th ><?php echo UCWords(adminFunctions::t('filesize', 'Filesize')); ?></th>
                            <th style="width: 10%;"><?php echo UCWords(adminFunctions::t('downloads', 'Downloads')); ?></th>
                            <th style="width: 10%;"><?php echo UCWords(adminFunctions::t('owner', 'Owner')); ?></th>
                            <th style="width: 10%;"><?php echo UCWords(adminFunctions::t('status', 'Status')); ?></th>
                            <th class="align-left fileManageActions" style="width: 10%;"><?php echo UCWords(adminFunctions::t('actions', 'Actions')); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
            
            <?php if($Auth->hasAccessLevel(20)): ?>
            <div style="float: right;">
                <a href="export_csv.php?type=files" class="button blue mobileAdminResponsiveHide">Export File Data (csv)</a>
            </div>
            <?php endif; ?>
            
            <div style="float: left;">
                <a href="#" onClick="bulkDeleteFiles(false); return false;" id="removeMultiFilesButton" class="button mobileAdminResponsiveHide"><?php echo adminFunctions::t('remove_files_total', 'Remove Files[[[FILE_COUNT]]]', array('FILE_COUNT'=>'')); ?></a>&nbsp;
                <a href="#" onClick="bulkDeleteFiles(true); return false;" id="deleteMultiFilesButton" class="button mobileAdminResponsiveHide"><?php echo adminFunctions::t('delete_files_and_data_total', 'Delete Files And Stats Data[[[FILE_COUNT]]]', array('FILE_COUNT'=>'')); ?></a>&nbsp;
                <a href="#" onClick="bulkMoveFiles(); return false;" id="moveMultiFilesButton" class="button mobileAdminResponsiveHide"><?php echo adminFunctions::t('move_files_total', 'Move Files[[[FILE_COUNT]]]', array('FILE_COUNT'=>'')); ?></a>            </div>
            <div class="clear"></div>
            
        </div>
    </div>
</div>
<div class="customFilter" id="customFilter" style="display: none;">
    <label>
        Filter:
        <input name="filterText" id="filterText" type="text" value="<?php echo adminFunctions::makeSafe($filterText); ?>" onKeyUp="reloadTable(); return false;" style="width: 120px;"/>
    </label>
    <div class="responsiveDisplay"></div>
	<label id="username" style="padding-left: 6px;">
        User:
        <input name="filterByUser" id="filterByUser" type="text" class="filterByUser form-control txt-auto" style="width: 120px;" value="<?php echo adminFunctions::makeSafe($filterByUserLabel); ?>"/>
    </label>
	<label class="adminResponsiveHide filterByServerWrapper" style="padding-left: 6px;">
        Server:
        <select name="filterByServer" id="filterByServer" onChange="reloadTable(); return false;" style="width: 120px;">
            <option value="">- all -</option>
            <?php
            if (COUNT($serverDetails))
            {
                foreach ($serverDetails AS $serverDetail)
                {
                    echo '<option value="' . $serverDetail['id'] . '"';
                    if (($filterByServer) && ($filterByServer == $serverDetail['id']))
                    {
                        echo ' SELECTED';
                    }
                    echo '>' . $serverDetail['serverLabel'] . '</option>';
                }
            }
            ?>
        </select>
    </label>
    <label class="adminResponsiveHide filterByStatusWrapper" style="padding-left: 6px;">
        Status:
        <select name="filterByStatus" id="filterByStatus" onChange="reloadTable(); return false;" style="width: 120px;">
            <option value="">- all -</option>
            <?php
            if (COUNT($statusDetails))
            {
                foreach ($statusDetails AS $statusDetail)
                {
                    echo '<option value="' . $statusDetail['id'] . '"';
                    if (($filterByStatus) && ($filterByStatus == $statusDetail['id']))
                    {
                        echo ' SELECTED';
                    }
                    echo '>' . $statusDetail['label'] . '</option>';
                }
            }
            ?>
        </select>
    </label>
	<label class="adminResponsiveHide filterBySourceWrapper" style="padding-left: 6px; display: none;">
        Src:
        <select name="filterBySource" id="filterBySource" onChange="reloadTable(); return false;" style="width: 80px;">
            <option value="">- all -</option>
            <option value="direct">Direct</option>
            <option value="ftp">FTP</option>
            <option value="remote">Remote</option>
            <option value="torrent">Torrent</option>
            <option value="leech">Leech</option>
            <option value="webdav">Webdav</option>
            <option value="api">API</option>
            <option value="other">Other</option>
        </select>
    </label>

	<label class="adminResponsiveHide updateViewWrapper" style="padding-left: 6px;">
        View:
        <select name="updateView" id="updateView" onChange="updateView(); return false;" style="width: 80px;">
            <option value="list" <?php echo SITE_CONFIG_DEFAULT_ADMIN_FILE_MANAGER_VIEW=='list'?'SELECTED':''; ?>>List</option>
			<option value="thumb" <?php echo SITE_CONFIG_DEFAULT_ADMIN_FILE_MANAGER_VIEW=='thumb'?'SELECTED':''; ?>>Thumbnails</option>
        </select>
    </label>
</div>
<div id="confirmDelete" title="Confirm Action">
    <p>Select the type of removal below. You can also add removal notes such as a copy of the original removal request. The notes are only visible by an admin user.</p>
    <form id="removeFileForm" class="form">
        <div class="clearfix">
            <label>Removal Type:</label>
            <div class="input">
                <select name="removal_type" id="removal_type" class="large" onChange="updateSelectedRemoveFileSelect(); return false;">
                    <option value="3">General</option>
                    <option value="4">Copyright Breach (DMCA)</option>
                </select>
            </div>
        </div>
        <div class="clearfix alt-highlight">
            <label>Notes:</label>
            <div class="input">
                <textarea name="admin_notes" id="admin_notes" class="xxlarge"></textarea>
            </div>
        </div>
		<div class="clearfix">
            <label>Block Future Uploads:</label>
            <div class="input">
                <select name="block_uploads" id="block_uploads" class="xxlarge">
                    <option value="0">No (allow the same file to be uploaded again)</option>
                    <option value="1">Yes (this file will be blocked from uploading again)</option>
                </select>
            </div>
        </div>
    </form>
</div>

<div id="showNotes" title="File Notes"></div>

<div id="editFileForm" title="Edit File">
    <span id="editFileFormInner"></span>
</div>

<div id="moveFilesForm" title="Move Files Between Servers">
    <span id="moveFilesRawFileForm"></span>
</div>

<?php
include_once('_footer.inc.php');
?>