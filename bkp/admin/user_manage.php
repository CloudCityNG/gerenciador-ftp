<?php
// initial constants
define('ADMIN_PAGE_TITLE', 'Manage Users');
define('ADMIN_SELECTED_PAGE', 'users');
define('ADMIN_SELECTED_SUB_PAGE', 'user_manage');

// includes and security
include_once('_local_auth.inc.php');

// handle impersonate requests
if(isset($_REQUEST['impersonate']))
{
	$impUserId = (int)$_REQUEST['impersonate'];
	if($impUserId)
	{
		// load user
		$impUser = UserPeer::loadUserById($impUserId);
		if($impUser)
		{
			// make sure they are not an admin user for security purposes
			$userType = UserPeer::getUserLevelValue('level_type', $impUser->level_id);
			if($userType != 'admin')
			{
				// fine to impersonate user
				$_SESSION['_old_user'] = $_SESSION['user'];
				$rs = $Auth->impersonate($impUserId);
				if($rs)
				{
					// redirect to customer file manager
					coreFunctions::redirect(WEB_ROOT.'/account_home.html');
				}
				else
				{
					// failed impersonating user
					unset($_SESSION['_old_user']);
					adminFunctions::setError("Failed impersonating user account, please try again later.");
				}
			}
		}
	}
}

// page header
include_once('_header.inc.php');

// account types
$accountTypeDetails = $db->getRows('SELECT id, level_id, label FROM user_level WHERE id > 0 ORDER BY level_id ASC');

// account status
$accountStatusDetails = array('active','pending','disabled','suspended');

// error/success messages
if (isset($_REQUEST['sa']))
{
    adminFunctions::setSuccess('New user successfully added.');
}
elseif (isset($_REQUEST['se']))
{
    adminFunctions::setSuccess('User successfully updated.');
}
elseif (isset($_REQUEST['error']))
{
    adminFunctions::setError(urldecode($_REQUEST['error']));
}

// get any params
$filterByAccountType = '';
if(isset($_REQUEST['filterByAccountType']))
{
    $filterByAccountType = trim($_REQUEST['filterByAccountType']);
}

$filterByAccountStatus = 'active';
if(isset($_REQUEST['filterByAccountStatus']))
{
    $filterByAccountStatus = trim($_REQUEST['filterByAccountStatus']);
}
?>

<script>
    oTable = null;
    gUserId = null;
    $(document).ready(function(){
        // datatable
        oTable = $('#fileTable').dataTable({
            "sPaginationType": "full_numbers",
            "bServerSide": true,
            "bProcessing": true,
            "sAjaxSource": 'ajax/user_manage.ajax.php',
            "bJQueryUI": true,
            "iDisplayLength": 25,
            "aaSorting": [[ 1, "asc" ]],
            "aoColumns" : [
                { bSortable: false, sWidth: '3%', sName: 'file_icon', sClass: "center adminResponsiveHide" },
                { sName: 'username' },
                { sName: 'email_address', sClass: "adminResponsiveHide" },
                { sName: 'account_type', sWidth: '10%', sClass: "center adminResponsiveHide" },
                { sName: 'last_login', sWidth: '15%', sClass: "center adminResponsiveHide" },
                { sName: 'space_used', sWidth: '9%', sClass: "center adminResponsiveHide" },
                { sName: 'total_files', sWidth: '8%', sClass: "center adminResponsiveHide" },
                { sName: 'status', sWidth: '10%', sClass: "center adminResponsiveHide" },
                { bSortable: false, sWidth: '16%', sClass: "center dataTableFix responsiveTableColumn" }
            ],
            "fnServerData": function ( sSource, aoData, fnCallback ) {
                aoData.push( { "name": "filterText", "value": $('#filterText').val() } );
                aoData.push( { "name": "filterByAccountType", "value": $('#filterByAccountType').val() } );
                aoData.push( { "name": "filterByAccountStatus", "value": $('#filterByAccountStatus').val() } );
                aoData.push( { "name": "filterByAccountId", "value": $('#filterByAccountId').val() } );
                $.ajax({
                    "dataType": 'json',
                    "type": "GET",
                    "url": "ajax/user_manage.ajax.php",
                    "data": aoData,
                    "success": fnCallback
                });
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
                "Delete User": function() {
                    removeUser();
                    $("#confirmDelete").dialog("close");
                },
                "Cancel": function() {
                    $("#confirmDelete").dialog("close");
                }
            },
            open: function() {
                resetOverlays();
            }
        });
    });

    function reloadTable()
    {
        oTable.fnDraw(false);
    }
    
    function confirmRemoveUser(userId)
    {
        $('#confirmDelete').dialog('open');
        gUserId = userId;
    }
    
    function removeFile()
    {
        $.ajax({
            type: "POST",
            url: "ajax/update_user_state.ajax.php",
            data: { fileId: gUserId, statusId: 3 },
            dataType: 'json',
            success: function(json) {
                if(json.error == true)
                {
                    showError(json.msg);
                }
                else
                {
                    showSuccess(json.msg);
                    reloadTable();
                }
                
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                showError(XMLHttpRequest.responseText);
            }
        });
    }
    
    function confirmRemoveUser(userId)
    {
        if(confirm('Are you sure you want to permanently remove this user? All files and data relating to the user will be removed. This can not be undone.'))
        {
            setCurrentUserId(userId);
            removeUser();
        }
        
        return false;
    }
    
    function removeUser()
    {
        bulkDeleteConfirm();
    }
    
    var bulkError = '';
    var bulkSuccess = '';
    var totalDone = 0;
    var currentUserId = 0;
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
    function setCurrentUserId(userId)
    {
        currentUserId = userId;
    }
    function getCurrentUserId(userId)
    {
        return currentUserId;
    }
    function bulkDeleteConfirm(userId)
    {
        // get server list for deleting all files
        $.ajax({
            type: "POST",
            url: "ajax/get_all_file_server_paths.ajax.php",
            data: { userId: getCurrentUserId() },
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
                    
                    // for no files
                    if(affectedServers == 0)
                    {
                        finishBulkProcess();
                    }
                    else
                    {
                        for(filePath in filePathsObj)
                        {
                            //  call server with file ids to delete
                            $.ajax({
                                type: "POST",
                                url: "<?php echo _CONFIG_SITE_PROTOCOL; ?>://"+filePath+"/<?php echo ADMIN_FOLDER_NAME; ?>/ajax/file_manage_bulk_delete.ajax.php",
                                data: { fileIds: filePathsObj[filePath] },
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
                }

            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                showError('Failed connecting to server to get the list of servers, please try again later.');
            }
        });
    }
    
    function finishBulkProcess()
    {
        // delete actual user
        $.ajax({
            type: "POST",
            url: "ajax/user_remove.ajax.php",
            data: { userId: getCurrentUserId() },
            dataType: 'json',
            success: function(json) {
                // compile result
                if(json.error == true)
                {
                    showError(json.msg);
                }
                else
                {
                    showSuccess(json.msg);
                }
                tidyBulkProcess();
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                showError('Failed deleting user, please try again later.');
                tidyBulkProcess();
            }
        });
    }
    
    function tidyBulkProcess()
    {
        reloadTable();
        clearBulkResponses();

        // scroll to the top of the page
        $("html, body").animate({ scrollTop: 0 }, "slow");
        $('#selectAllCB').prop('checked', false);
    }
	
	function confirmImpersonateUser(userId)
    {
        if(confirm("Are you sure you want to login as this user account? You'll have access to their account as they would see it.\n\nWhen you logout of the impersonated user, you'll be reverted to this admin user account again."))
		{
			window.location="user_manage.php?impersonate="+userId;
			return true;
		}
		
		return false;
    }
</script>

<div class="row clearfix">
    <div class="sectionLargeIcon largeUserIcon"></div>
    <div class="widget clearfix">
        <h2>User List</h2>
        <div class="widget_inside responsiveTable">
            <?php echo adminFunctions::compileNotifications(); ?>
            <div class="col_12">
                <table id='fileTable' class='dataTable'>
                    <thead>
                        <tr>
                            <th></th>
                            <th class="align-left"><?php echo adminFunctions::t('username', 'Username'); ?></th>
                            <th><?php echo adminFunctions::t('email_address', 'Email Address'); ?></th>
                            <th><?php echo adminFunctions::t('type', 'Type'); ?></th>
                            <th class="align-left"><?php echo adminFunctions::t('last_login', 'Last Login'); ?></th>
                            <th class="align-left"><?php echo adminFunctions::t('space_used', 'HD Used'); ?></th>
                            <th><?php echo adminFunctions::t('files', 'Files'); ?></th>
                            <th><?php echo adminFunctions::t('status', 'Status'); ?></th>
                            <th class="align-left"><?php echo adminFunctions::t('actions', 'Actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
            <form action="user_add.php" method="GET">
                <input type="submit" value="Add User" class="button blue"/>
            </form>
        </div>
    </div>
</div>

<div class="customFilter" id="customFilter" style="display: none;">
    <label>
        Filter Results:
        <input name="filterText" id="filterText" type="text" value="<?php echo isset($_REQUEST['filterText'])?adminFunctions::makeSafe($_REQUEST['filterText']):''; ?>" onKeyUp="reloadTable(); return false;" style="width: 160px;"/>
    </label>
    <label class="adminResponsiveHide" style="padding-left: 6px;">
        By Type:
        <select name="filterByAccountType" id="filterByAccountType" onChange="reloadTable(); return false;" style="width: 160px;">
            <option value="">- all -</option>
            <?php
            if (COUNT($accountTypeDetails))
            {
                foreach ($accountTypeDetails AS $accountTypeDetail)
                {
                    echo '<option value="' . $accountTypeDetail['id'] . '"';
                    if (($filterByAccountType) && ($filterByAccountType == $accountTypeDetail['id']))
                    {
                        echo ' SELECTED';
                    }
                    echo '>' . UCWords($accountTypeDetail['label']) . '</option>';
                }
            }
            ?>
        </select>
    </label>
    <label class="adminResponsiveHide" style="padding-left: 6px;">
        By Status:
        <select name="filterByAccountStatus" id="filterByAccountStatus" onChange="reloadTable(); return false;" style="width: 120px;">
            <option value="">- all -</option>
            <?php
            if (COUNT($accountStatusDetails))
            {
                foreach ($accountStatusDetails AS $accountStatusDetail)
                {
                    echo '<option value="' . $accountStatusDetail . '"';
                    if (($filterByAccountStatus) && ($filterByAccountStatus == $accountStatusDetail))
                    {
                        echo ' SELECTED';
                    }
                    echo '>' . UCWords($accountStatusDetail) . '</option>';
                }
            }
            ?>
        </select>
    </label>
    <input type="hidden" value="<?php echo isset($_REQUEST['filterByAccountId'])?adminFunctions::makeSafe($_REQUEST['filterByAccountId']):''; ?>" name="filterByAccountId" id="filterByAccountId"/>
</div>

<div id="confirmDelete" title="Confirm Action">
    <p>Are you sure you want to disable this user?</p>
</div>

<?php
include_once('_footer.inc.php');
?>