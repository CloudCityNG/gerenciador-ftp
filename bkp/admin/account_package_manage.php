<?php
// initial constants
define('ADMIN_PAGE_TITLE', 'Account Packages');
define('ADMIN_SELECTED_PAGE', 'account_level');
define('ADMIN_SELECTED_SUB_PAGE', 'account_level');

// includes and security
include_once('_local_auth.inc.php');

// update to sync id & level_id
$db->query('UPDATE user_level SET level_id = id');

// page header
include_once('_header.inc.php');
?>

<script>
    oTable = null;
    gFileServerId = null;
    gEditUserLevelId = null;
    gTestFileServerId = null;
    gDeleteFileServerId = null;
    $(document).ready(function() {
        // datatable
        oTable = $('#fileTable').dataTable({
            "sPaginationType": "full_numbers",
            "bServerSide": true,
            "bProcessing": true,
            "sAjaxSource": 'ajax/account_package_manage.ajax.php',
            "bJQueryUI": true,
            "iDisplayLength": 25,
            "aaSorting": [[1, "asc"]],
            "aoColumns": [
                {bSortable: false, sWidth: '3%', sName: 'file_icon', sClass: "center adminResponsiveHide"},
                {bSortable: false},
                {bSortable: false, sWidth: '13%', sClass: "center adminResponsiveHide"},
                {bSortable: false, sWidth: '13%', sClass: "center adminResponsiveHide"},
                {bSortable: false, sWidth: '13%', sClass: "center adminResponsiveHide"},
                {bSortable: false, sWidth: '13%', sClass: "center adminResponsiveHide"},
                {bSortable: false, sWidth: '12%', sClass: "center adminResponsiveHide"},
                {bSortable: false, sWidth: '20%', sClass: "center"}
            ],
            "fnServerData": function(sSource, aoData, fnCallback) {
                aoData.push({"name": "filterText", "value": $('#filterText').val()});
                $.ajax({
                    "dataType": 'json',
                    "type": "GET",
                    "url": "ajax/account_package_manage.ajax.php",
                    "data": aoData,
                    "success": fnCallback
                });
            }
        });

        // dialog box
        $("#addPackageForm").dialog({
            modal: true,
            autoOpen: false,
            width: getDefaultDialogWidth(),
            height: 594,
            buttons: {
                "Add Account Package": function() {
                    processAddUserPackage();
                },
                "Cancel": function() {
                    $("#addPackageForm").dialog("close");
                }
            },
            open: function() {
                gEditUserLevelId = null;
                setLoader();
                loadAddPackageForm();
                resetOverlays();
            }
        });

        $("#editPackageForm").dialog({
            modal: true,
            autoOpen: false,
            width: getDefaultDialogWidth(),
            height: 574,
            buttons: {
                "Update Package": function() {
                    processAddUserPackage();
                },
                "Cancel": function() {
                    $("#editPackageForm").dialog("close");
                }
            },
            open: function() {
                setEditLoader();
                loadEditUserPackageForm();
                resetOverlays();
            }
        });
    });

    function setLoader()
    {
        $('#addUserPackageForm').html('Loading, please wait...');
    }

    function loadAddPackageForm()
    {
        $('#addUserPackageForm').html('');
        $('#editUserPackageForm').html('');
        $.ajax({
            type: "POST",
            url: "ajax/account_package_manage_add_form.ajax.php",
            data: {},
            dataType: 'json',
            success: function(json) {
                if (json.error == true)
                {
                    $('#addUserPackageForm').html(json.msg);
                }
                else
                {
                    $('#addUserPackageForm').html(json.html);
                }

            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                $('#addUserPackageForm').html(XMLHttpRequest.responseText);
            }
        });
    }

    function setEditLoader()
    {
        $('#editUserPackageForm').html('Loading, please wait...');
    }

    function loadEditUserPackageForm()
    {
        $('#addUserPackageForm').html('');
        $('#editUserPackageForm').html('');
        $.ajax({
            type: "POST",
            url: "ajax/account_package_manage_add_form.ajax.php",
            data: {gEditUserLevelId: gEditUserLevelId},
            dataType: 'json',
            success: function(json) {
                if (json.error == true)
                {
                    $('#editUserPackageForm').html(json.msg);
                }
                else
                {
                    $('#editUserPackageForm').html(json.html);
                }

            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                $('#editUserPackageForm').html(XMLHttpRequest.responseText);
            }
        });
    }

    function processAddUserPackage()
    {
        // get data
		if(gEditUserLevelId !== null)
		{
			formData = $('#editUserPackageForm form').serialize();
		}
		else
		{
			formData = $('#addUserPackageForm form').serialize();
		}
		formData += "&existing_user_level_id=" + encodeURIComponent(gEditUserLevelId);

        $.ajax({
            type: "POST",
            url: "ajax/account_package_manage_add_process.ajax.php",
            data: formData,
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
                    $("#addPackageForm").dialog("close");
                    $("#editPackageForm").dialog("close");
                }

            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                showError(XMLHttpRequest.responseText, 'popupMessageContainer');
            }
        });

    }

    function addPackageForm()
    {
        $('#addPackageForm').dialog('open');
    }

    function editPackageForm(userLevelId)
    {
        gEditUserLevelId = userLevelId;
        $('#editPackageForm').dialog('open');
    }

    function reloadTable()
    {
        oTable.fnDraw(false);
    }
</script>

<div class="row clearfix">
    <div class="sectionLargeIcon largePackagesIcon"></div>
    <div class="widget clearfix">
        <h2>Existing Packages</h2>
        <div class="widget_inside responsiveTable">
            <?php echo adminFunctions::compileNotifications(); ?>
            <div class="col_12">
                <table id='fileTable' class='dataTable'>
                    <thead>
                        <tr>
                            <th></th>
                            <th class="align-left"><?php echo UCWords(adminFunctions::t("package_label", "package label")); ?></th>
							<th class="align-left"><?php echo UCWords(adminFunctions::t("users", "users")); ?></th>
                            <th class="align-left"><?php echo UCWords(adminFunctions::t("allow_upload", "allow upload")); ?></th>
                            <th class="align-left"><?php echo UCWords(adminFunctions::t("max_upload_size", "max upload size")); ?></th>
                            <th class="align-left"><?php echo UCWords(adminFunctions::t("storage", "storage")); ?></th>
                            <th class="align-left"><?php echo UCWords(adminFunctions::t("on_upgrade_page", "upgrade page")); ?></th>
                            <th class="align-left"><?php echo UCWords(adminFunctions::t("action", "action")); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
				<br/>
				Note: Only packages which have a "Package Type" of "Paid" have the option to set pricing.
            </div>
            <input type="submit" value="New Account Package" class="button blue mobileAdminResponsiveHide" onClick="addPackageForm();
        return false;"/>
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

<div id="addPackageForm" title="Add Package">
    <span id="addUserPackageForm"></span>
</div>

<div id="editPackageForm" title="Edit Package">
    <span id="editUserPackageForm"></span>
</div>

<?php
include_once('_footer.inc.php');
?>