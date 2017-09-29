<?php
// initial constants
define('ADMIN_PAGE_TITLE', 'File Servers');
define('ADMIN_SELECTED_PAGE', 'file_servers');
define('ADMIN_SELECTED_SUB_PAGE', 'server_manage');

// includes and security
include_once('_local_auth.inc.php');

// update storage stats
if(isset($_REQUEST['r']))
{
	file::updateFileServerStorageStats(null, true);
}

// handle upload/download toggles
if(isset($_REQUEST['toggle_uploads']))
{
	$configValue = 'yes';
	if(SITE_CONFIG_UPLOADS_BLOCK_ALL == 'yes')
	{
		$configValue = 'no';
	}
	$db->query('UPDATE site_config SET config_value = :configValue WHERE config_key = \'uploads_block_all\' LIMIT 1', array('configValue' => $configValue));
	
	// redirect to self
	coreFunctions::redirect(ADMIN_WEB_ROOT.'/server_manage.php?toggle_uploadss=1');
}
elseif(isset($_REQUEST['toggle_uploadss']))
{
	adminFunctions::setSuccess("Uploads disabled.");
}
elseif(isset($_REQUEST['toggle_downloads']))
{
	$configValue = 'yes';
	if(SITE_CONFIG_DOWNLOADS_BLOCK_ALL == 'yes')
	{
		$configValue = 'no';
	}
	$db->query('UPDATE site_config SET config_value = :configValue WHERE config_key = \'downloads_block_all\' LIMIT 1', array('configValue' => $configValue));
	
	// redirect to self
	coreFunctions::redirect(ADMIN_WEB_ROOT.'/server_manage.php?toggle_downloadss=1');
}
elseif(isset($_REQUEST['toggle_downloadss']))
{
	adminFunctions::setSuccess("Downloads disabled.");
}

// page header
include_once('_header.inc.php');
?>

<script>
    oTable = null;
    gFileServerId = null;
    gEditFileServerId = null;
    gTestFileServerId = null;
    gDeleteFileServerId = null;
    $(document).ready(function() {
        // datatable
        oTable = $('#fileTable').dataTable({
            "sPaginationType": "full_numbers",
            "bServerSide": true,
            "bProcessing": true,
            "sAjaxSource": 'ajax/server_manage.ajax.php',
            "bJQueryUI": true,
            "iDisplayLength": 25,
            "aaSorting": [[1, "asc"]],
            "aoColumns": [
                {bSortable: false, sWidth: '3%', sName: 'file_icon', sClass: "center adminResponsiveHide"},
                {sName: 'server_label', sWidth: '25%'},
                {sName: 'server_type', sWidth: '10%', sClass: "center adminResponsiveHide"},
                {sName: 'storage_path', sClass: "adminResponsiveHide"},
                {sName: 'total_space_used', sWidth: '10%', sClass: "center"},
                {sName: 'total_files', sWidth: '10%', sClass: "center"},
                {sName: 'status', sWidth: '10%', sClass: "center adminResponsiveHide"},
                {bSortable: false, sWidth: '20%', sClass: "center adminResponsiveHide"}
            ],
            "fnServerData": function(sSource, aoData, fnCallback) {
                aoData.push({"name": "filterText", "value": $('#filterText').val()});
                $.ajax({
                    "dataType": 'json',
                    "type": "GET",
                    "url": "ajax/server_manage.ajax.php",
                    "data": aoData,
                    "success": fnCallback
                });
            }
        });

        // update custom filter
        $('.dataTables_filter').html($('#customFilter').html());

        // dialog box
        $("#addServerForm").dialog({
            modal: true,
            autoOpen: false,
            width: getDefaultDialogWidth(),
            height: 634,
            buttons: {
                "Add File Server": function() {
                    processAddFileServer();
                },
                "Cancel": function() {
                    $("#addServerForm").dialog("close");
                }
            },
            open: function() {
                gEditFileServerId = null;
                setLoader();
                loadAddServerForm();
                resetOverlays();
            }
        });

        $("#editServerForm").dialog({
            modal: true,
            autoOpen: false,
            width: getDefaultDialogWidth(),
            height: 634,
            buttons: {
                "Update File Server": function() {
                    processAddFileServer();
                },
                "Cancel": function() {
                    $("#editServerForm").dialog("close");
                }
            },
            open: function() {
                setEditLoader();
                loadEditServerForm();
                resetOverlays();
            }
        });

        $("#testServerForm").dialog({
            modal: true,
            autoOpen: false,
            width: getDefaultDialogWidth(),
            height: 300,
            buttons: {
                "OK": function() {
                    $("#testServerForm").dialog("close");
                }
            },
            open: function() {
                setTestLoader();
                resetOverlays();
            }
        });

        // dialog box
        $("#confirmDelete").dialog({
            modal: true,
            autoOpen: false,
            width: getDefaultDialogWidth(),
            buttons: {
                "Confirm File Removal": function() {
                    removeFileServer();
                },
                "Cancel": function() {
                    $("#confirmDelete").dialog("close");
                }
            }
        });
    });

    function setLoader()
    {
        $('#addFileServerForm').html('Loading, please wait...');
    }

    function confirmRemoveFileServer(serverId, serverName, activeFiles)
    {
        $('#pleaseWait').hide();
        $('#confirmText').show();
        $('#serverNameLabel').html(serverName);
        $('#serverActiveFilesLabel').html(activeFiles);
        $('#confirmDelete').dialog('open');
        gDeleteFileServerId = serverId;
    }

    function removeFileServer()
    {
        $('#confirmText').hide();
        $('#pleaseWait').show();
        $.ajax({
            type: "POST",
            url: "ajax/server_manage_remove.ajax.php",
            data: {serverId: gDeleteFileServerId},
            dataType: 'json',
            success: function(json) {
                if (json.error == true)
                {
                    $('#pleaseWait').hide();
                    $('#confirmText').show();
                    showError(json.msg);
                }
                else
                {
                    showSuccess(json.msg);
                    reloadTable();
                    $("#confirmDelete").dialog("close");
                }

            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                $('#pleaseWait').hide();
                $('#confirmText').show();
                showError(XMLHttpRequest.responseText);
            }
        });
    }

    function loadAddServerForm()
    {
        $('#addFileServerForm').html('');
        $('#editFileServerForm').html('');
        $.ajax({
            type: "POST",
            url: "ajax/server_manage_add_form.ajax.php",
            data: {},
            dataType: 'json',
            success: function(json) {
                if (json.error == true)
                {
                    $('#addFileServerForm').html(json.msg);
                }
                else
                {
                    $('#addFileServerForm').html(json.html);
                    showHideFTPElements();
                    updateUrlParams();
                }

            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                $('#addFileServerForm').html(XMLHttpRequest.responseText);
            }
        });
    }

    function setEditLoader()
    {
        $('#editFileServerForm').html('Loading, please wait...');
    }

    function loadEditServerForm()
    {
        $('#addFileServerForm').html('');
        $('#editFileServerForm').html('');
        $.ajax({
            type: "POST",
            url: "ajax/server_manage_add_form.ajax.php",
            data: {gEditFileServerId: gEditFileServerId},
            dataType: 'json',
            success: function(json) {
                if (json.error == true)
                {
                    $('#editFileServerForm').html(json.msg);
                }
                else
                {
                    $('#editFileServerForm').html(json.html);
                    showHideFTPElements();
                    updateUrlParams();
                }

            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                $('#editFileServerForm').html(XMLHttpRequest.responseText);
            }
        });
    }

    function loadFtpTestServerForm()
    {
        $('#testFileServerForm').html('<iframe src="server_manage_test_ftp.php?serverId=' + gTestFileServerId + '" style="background: url(\'assets/images/spinner.gif\') no-repeat center center;" height="100%" width="100%" frameborder="0" scrolling="auto">Loading...</iframe>');
    }

    function setTestLoader()
    {
        $('#testFileServerForm').html('Loading, please wait...');
    }

    function loadDirectTestServerForm()
    {
        $('#testFileServerForm').html('<iframe src="server_manage_test_direct.php?serverId=' + gTestFileServerId + '" style="background: url(\'assets/images/spinner.gif\') no-repeat center center;" height="100%" width="100%" frameborder="0" scrolling="auto">Loading...</iframe>');
    }

    function processAddFileServer()
    {
	
        // get data
        ftp_host = '';
        ftp_port = '';
        ftp_username = '';
        ftp_password = '';
        ftp_server_type = '';
        ftp_passive_mode = '';
        file_server_domain_name = '';
        script_path = '';
        server_label = $('#server_label').val();
        status_id = $('#status_id').val();
        server_type = $('#server_type').val();
        max_storage_space = $('#max_storage_space').val();
        server_priority = $('#server_priority').val();
        storage_path = '';
        route_via_main_site = 0;
		dlAccelerator = 0;
		
		file_server_direct_ip_address = '';
		file_server_direct_ssh_port = 22;
		file_server_direct_ssh_username = '';
		file_server_direct_ssh_password = '';
		file_server_direct_server_path_to_storage = '';
		
        if (server_type == 'ftp')
        {
            ftp_host = $('#ftp_host').val();
            ftp_port = $('#ftp_port').val();
            ftp_username = $('#ftp_username').val();
            ftp_password = $('#ftp_password').val();
            storage_path = $('#ftp_storage_path').val();
            ftp_server_type = $('#ftp_server_type').val();
            ftp_passive_mode = $('#ftp_passive_mode').val();
        }
        else if (server_type == 'direct')
        {
            file_server_domain_name = $('#file_server_domain_name').val();
            script_path = $('#script_path').val();
            storage_path = $('#direct_storage_path').val();
            route_via_main_site = $('#route_via_main_site').val();
            dlAccelerator = $('#dlAccelerator2').val();
			
			file_server_direct_ip_address = $('#file_server_direct_ip_address').val();
			file_server_direct_ssh_port = $('#file_server_direct_ssh_port').val();
			file_server_direct_ssh_username = $('#file_server_direct_ssh_username').val();
			file_server_direct_ssh_password = $('#file_server_direct_ssh_password').val();
			file_server_direct_server_path_to_storage = $('#file_server_direct_server_path_to_storage').val();
        }
        else if (server_type == 'local')
        {
            storage_path = $('#local_storage_path').val();
			dlAccelerator = $('#dlAccelerator1').val();
			
			file_server_direct_ip_address = $('#file_server_direct_ip_address_2').val();
			file_server_direct_ssh_port = $('#file_server_direct_ssh_port_2').val();
			file_server_direct_ssh_username = $('#file_server_direct_ssh_username_2').val();
			file_server_direct_ssh_password = $('#file_server_direct_ssh_password_2').val();
			file_server_direct_server_path_to_storage = $('#file_server_direct_server_path_to_storage_2').val();
        }
        existing_file_server_id = gEditFileServerId;
        $.ajax({
            type: "POST",
            url: "ajax/server_manage_add_process.ajax.php",
            data: {existing_file_server_id: existing_file_server_id, route_via_main_site: route_via_main_site, file_server_domain_name: file_server_domain_name, script_path: script_path, server_label: server_label, status_id: status_id, server_type: server_type, storage_path: storage_path, ftp_host: ftp_host, ftp_port: ftp_port, ftp_username: ftp_username, ftp_password: ftp_password, ftp_server_type: ftp_server_type, ftp_passive_mode: ftp_passive_mode, max_storage_space: max_storage_space, server_priority: server_priority, dlAccelerator: dlAccelerator, file_server_direct_ip_address: file_server_direct_ip_address, file_server_direct_ssh_port: file_server_direct_ssh_port, file_server_direct_ssh_username: file_server_direct_ssh_username, file_server_direct_ssh_password: file_server_direct_ssh_password, file_server_direct_server_path_to_storage: file_server_direct_server_path_to_storage},
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
                    $("#addServerForm").dialog("close");
                    $("#editServerForm").dialog("close");
                }

            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                showError(XMLHttpRequest.responseText, 'popupMessageContainer');
            }
        });

    }

    function addServerForm()
    {
        $('#addServerForm').dialog('open');
    }

    function editServerForm(fileServerId)
    {
        gEditFileServerId = fileServerId;
        $('#editServerForm').dialog('open');
    }

    function reloadTable()
    {
        oTable.fnDraw(false);
    }

    function showHideFTPElements()
    {
        hideAllElements();
        serverType = $('#server_type').val();
        if (serverType == 'ftp')
        {
            $('.ftpElements').show();
        }
        else if (serverType == 'direct')
        {
            $('.directElements').show();
        }
        else if (serverType == 'local')
        {
            $('.localElements').show();
        }
    }

    function hideAllElements()
    {
        $('.directElements').hide();
        $('.ftpElements').hide();
        $('.localElements').hide();
    }

    function testFtpFileServer(serverId)
    {
        gTestFileServerId = serverId;
        $('#testServerForm').dialog('open');
        loadFtpTestServerForm();
    }

    function testDirectFileServer(serverId)
    {
        gTestFileServerId = serverId;
        $('#testServerForm').dialog('open');
        loadDirectTestServerForm();
    }

    function updateUrlParams()
    {
        // file server domain name
        SITE_HOST = $('#file_server_domain_name').val();
        SITE_HOST = SITE_HOST.replace(/\s/g, "");
        $('#file_server_domain_name').val(SITE_HOST);
        if (SITE_HOST.length == 0)
        {
            SITE_HOST = '';
        }

        // rewrite base
        REWRITE_BASE = $('#script_path').val();
        REWRITE_BASE = REWRITE_BASE.replace(/\s/g, "");
        $('#script_path').val(REWRITE_BASE);
        if (REWRITE_BASE.length == 0)
        {
            REWRITE_BASE = '/';
        }

        $('#configLink').attr('href', 'server_manage_direct_get_config_file.php?fileName=_config.inc.php&REWRITE_BASE=' + REWRITE_BASE + '&SITE_HOST=' + SITE_HOST);
        $('#htaccessLink').attr('href', 'server_manage_direct_get_config_file.php?fileName=.htaccess&REWRITE_BASE=' + REWRITE_BASE);
    }
	
	function toggleAllUploads()
	{
		if(confirm("Are you sure you want to disable all uploads on your site? This will block any new uploads from starting for non-admin users."))
		{
			window.location='server_manage.php?toggle_uploads=1';
		}
		
		return false;
	}
	
	function toggleAllDownloads()
	{
		if(confirm("Are you sure you want to disable all downloads on your site? This will block any new downloads from starting for non-admin users."))
		{
			window.location='server_manage.php?toggle_downloads=1';
		}
		
		return false;
	}
</script>

<div class="row clearfix">
    <div class="sectionLargeIcon largeServerIcon"></div>
    <div class="widget clearfix">
        <h2>File Servers</h2>
        <div class="widget_inside responsiveTable">
            <?php echo adminFunctions::compileNotifications(); ?>
            <div class="col_12">
                <table id='fileTable' class='dataTable'>
                    <thead>
                        <tr>
                            <th></th>
                            <th class="align-left"><?php echo UCWords(adminFunctions::t("server_label", "server label")); ?></th>
                            <th class="align-left"><?php echo UCWords(adminFunctions::t("server_type", "server type")); ?></th>
                            <th class="align-left"><?php echo UCWords(adminFunctions::t("storage_path", "storage path")); ?></th>
                            <th class="align-left"><?php echo UCWords(adminFunctions::t("space_used", "space used")); ?> *</th>
                            <th class="align-left"><?php echo UCWords(adminFunctions::t("total_files", "total files")); ?> *</th>
                            <th class="align-left"><?php echo UCWords(adminFunctions::t("status", "status")); ?></th>
                            <th class="align-left"><?php echo UCWords(adminFunctions::t("action", "action")); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
                <div style="padding-top: 6px;">
                    Note: Active servers above might not necessarily be used for new uploads. You can set which specific server is used within the <a href="setting_manage.php?filterByGroup=File+Uploads">site configuration</a> section.<br/><br/>* The above storage and total file data is refreshed every 5 minutes, so the above values may be slightly out of date. You can force a refresh by <a href="server_manage.php?r=1">click here</a>. (this can take some time!)
                </div>
            </div>

			<div style="float: right;">
                <a href="#" onclick="toggleAllUploads(); return false;" class="button <?php echo (SITE_CONFIG_UPLOADS_BLOCK_ALL == 'yes')?'red ':''; ?>mobileAdminResponsiveHide"><?php echo (SITE_CONFIG_UPLOADS_BLOCK_ALL == 'yes')?'Enable':'Disable'; ?> All Site Uploads</a>&nbsp;
                <a href="#" onclick="toggleAllDownloads(); return false;" class="button <?php echo (SITE_CONFIG_DOWNLOADS_BLOCK_ALL == 'yes')?'red ':''; ?>mobileAdminResponsiveHide"><?php echo (SITE_CONFIG_DOWNLOADS_BLOCK_ALL == 'yes')?'Enable':'Disable'; ?> All Site Downloads</a>
			</div>
			<div style="float: left;">
				<a href="#" class="button blue mobileAdminResponsiveHide" onClick="addServerForm(); return false;">Add File Server</a>
			</div>
			<div class="clear"></div>
        </div>
    </div>
</div>

<div class="customFilter" id="customFilter" style="display: none;">
    <label>
        Filter Results:
        <input name="filterText" id="filterText" type="text" onKeyUp="reloadTable();
        return false;" style="width: 160px;"/>
    </label>
</div>

<div id="addServerForm" title="Add File Server">
    <span id="addFileServerForm"></span>
</div>

<div id="editServerForm" title="Edit File Server">
    <span id="editFileServerForm"></span>
</div>

<div id="testServerForm" title="Test File Server">
    <span id="testFileServerForm"></span>
</div>

<div id="confirmDelete" title="Confirm Action">
    <div id="confirmText">
        <p>Are you sure you want to remove the file server called '<span id="serverNameLabel" style="font-weight: bold;"></span>'?</p>
        <p>There are <span id="serverActiveFilesLabel"></span> file(s) on this server. Any active files will be removed and any historic data will be lost. This includes the statistics on these and previously expired files.</p>
        <p>Once confirmed, this action can not be reversed.</p>
        <p>Note: If there are a lot of files on this file server, this process may take a long time to complete.</p>
    </div>
    <div id="pleaseWait">
        Removing, please wait...
    </div>
</div>

<?php
include_once('_footer.inc.php');
?>