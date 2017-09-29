<?php

// includes and security
include_once('_local_auth.inc.php');

// handle deletes
if(isset($_REQUEST['del']))
{
	if (_CONFIG_DEMO_MODE == true)
	{
		adminFunctions::setError(t("no_changes_in_demo_mode"));
	}
	else
	{
		$db->query('DELETE FROM user_level_pricing WHERE id = '.(int)$_REQUEST['del'].' LIMIT 1');
		adminFunctions::setSuccess('The package pricing item has been removed.');
	}
}

$appendTitle = '';
if(isset($_REQUEST['level_id']))
{
	$packageName = $db->getValue('SELECT label FROM user_level WHERE level_id = '.(int)$_REQUEST['level_id'].' LIMIT 1');
	if($packageName)
	{
		$appendTitle = ' for "'.UCWords($packageName).'"';
	}
}

// initial constants
define('ADMIN_PAGE_TITLE', 'Account Type Pricing'.$appendTitle);
define('ADMIN_SELECTED_PAGE', 'account_package_pricing');
define('ADMIN_SELECTED_SUB_PAGE', 'account_package_pricing');

// page header
include_once('_header.inc.php');
?>

<script>
    oTable = null;
    gEditPricingId = null;
    $(document).ready(function() {
        // datatable
        oTable = $('#fileTable').dataTable({
            "sPaginationType": "full_numbers",
            "bServerSide": true,
            "bProcessing": true,
            "sAjaxSource": 'ajax/account_package_pricing_manage.ajax.php<?php echo (isset($_REQUEST['level_id']))?('?level_id='.(int)$_REQUEST['level_id']):''; ?>',
            "bJQueryUI": true,
            "iDisplayLength": 25,
            "aaSorting": [[1, "asc"]],
            "aoColumns": [
                {bSortable: false, sWidth: '3%', sName: 'file_icon', sClass: "center adminResponsiveHide"},
                {bSortable: false, sWidth: '18%', sClass: "adminResponsiveHide"},
				{bSortable: false},
                {bSortable: false, sWidth: '30%', sClass: "adminResponsiveHide"},
                {bSortable: false, sWidth: '11%', sClass: "center"},
                {bSortable: false, sWidth: '11%', sClass: "center adminResponsiveHide"}
            ],
            "fnServerData": function(sSource, aoData, fnCallback) {
                aoData.push({"name": "filterText", "value": $('#filterText').val()});
                $.ajax({
                    "dataType": 'json',
                    "type": "GET",
                    "url": "ajax/account_package_pricing_manage.ajax.php<?php echo (isset($_REQUEST['level_id']))?('?level_id='.(int)$_REQUEST['level_id']):''; ?>",
                    "data": aoData,
                    "success": fnCallback
                });
            }
        });

        // dialog box
        $("#addNewPricingForm").dialog({
            modal: true,
            autoOpen: false,
            width: getDefaultDialogWidth(),
            height: 475,
            buttons: {
                "Add Pricing": function() {
                    processAddPricing();
                },
                "Cancel": function() {
                    $("#addNewPricingForm").dialog("close");
                }
            },
            open: function() {
                gEditPricingId = null;
                setLoader();
                loadAddNewPricingForm();
                resetOverlays();
            }
        });

        $("#editPackagePricingForm").dialog({
            modal: true,
            autoOpen: false,
            width: getDefaultDialogWidth(),
            height: 475,
            buttons: {
                "Update Pricing": function() {
                    processAddPricing();
                },
                "Cancel": function() {
                    $("#editPackagePricingForm").dialog("close");
                }
            },
            open: function() {
                setEditLoader();
                loadEditPackagePricingForm();
                resetOverlays();
            }
        });
    });

    function setLoader()
    {
        $('#addNewPricingForm').html('Loading, please wait...');
    }

    function loadAddNewPricingForm()
    {
        $('#addNewPricingForm').html('');
        $('#editPackagePricingForm').html('');
        $.ajax({
            type: "POST",
            url: "ajax/account_package_pricing_manage_add_form.ajax.php",
            data: {},
            dataType: 'json',
            success: function(json) {
                if (json.error == true)
                {
                    $('#addNewPricingForm').html(json.msg);
                }
                else
                {
                    $('#addNewPricingForm').html(json.html);
                }

            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                $('#addNewPricingForm').html(XMLHttpRequest.responseText);
            }
        });
    }

    function setEditLoader()
    {
        $('#editPackagePricingForm').html('Loading, please wait...');
    }

    function loadEditPackagePricingForm()
    {
        $('#addNewPricingForm').html('');
        $('#editPackagePricingForm').html('');
        $.ajax({
            type: "POST",
            url: "ajax/account_package_pricing_manage_add_form.ajax.php",
            data: {gEditPricingId: gEditPricingId},
            dataType: 'json',
            success: function(json) {
                if (json.error == true)
                {
                    $('#editPackagePricingForm').html(json.msg);
                }
                else
                {
                    $('#editPackagePricingForm').html(json.html);
                }
				updateAddPricingOpt();
            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                $('#editPackagePricingForm').html(XMLHttpRequest.responseText);
            }
        });
    }

    function processAddPricing()
    {
        // get data
        existing_pricing_id = gEditPricingId;
        pricing_label = $('#pricing_label').val();
		package_pricing_type = $('#package_pricing_type').val();
		period = null;
		download_allowance = null;
		if(package_pricing_type == 'period')
		{
			period = $('#period').val();
		}
		else
		{
			download_allowance = $('#download_allowance').val();
		}
        user_level_id = $('#user_level_id').val();
        price = $('#price').val();

        $.ajax({
            type: "POST",
            url: "ajax/account_package_pricing_manage_add_process.ajax.php",
            data: {existing_pricing_id: existing_pricing_id, pricing_label: pricing_label, package_pricing_type: package_pricing_type, period: period, download_allowance: download_allowance, user_level_id: user_level_id, price: price},
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
                    $("#addNewPricingForm").dialog("close");
                    $("#editPackagePricingForm").dialog("close");
                }

            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                showError(XMLHttpRequest.responseText, 'popupMessageContainer');
            }
        });

    }

    function addNewPricingForm()
    {
        $('#addNewPricingForm').dialog('open');
    }

    function editPackagePricingForm(pricingId)
    {
        gEditPricingId = pricingId;
        $('#editPackagePricingForm').dialog('open');
    }

    function reloadTable()
    {
        oTable.fnDraw(false);
    }
	
	function updateAddPricingOpt()
	{
		if($('#package_pricing_type').val() == 'bandwidth')
		{
			$('.period-class').hide();
			$('.bandwidth-class').show();
		}
		else
		{
			$('.bandwidth-class').hide();
			$('.period-class').show();
		}
	}
</script>

<div class="row clearfix">
    <div class="sectionLargeIcon largePackagesIcon"></div>
    <div class="widget clearfix">
        <h2>Package Pricing</h2>
        <div class="widget_inside responsiveTable">
            <?php echo adminFunctions::compileNotifications(); ?>
            <div class="col_12">
                <table id='fileTable' class='dataTable'>
                    <thead>
                        <tr>
                            <th></th>
                            <th class="align-left"><?php echo UCWords(adminFunctions::t("pricing_label", "pricing label")); ?></th>
							<th class="align-left"><?php echo UCWords(adminFunctions::t("account_package", "account package")); ?></th>
                            <th class="align-left"><?php echo UCWords(adminFunctions::t("package_type", "package type")); ?></th>
                            <th class="align-left"><?php echo UCWords(adminFunctions::t("price", "price")); ?></th>
                            <th class="align-left"><?php echo UCWords(adminFunctions::t("action", "action")); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
            <input type="submit" value="Add New Pricing" class="button blue mobileAdminResponsiveHide" onClick="addNewPricingForm(); return false;"/>
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

<div id="addNewPricingForm" title="Add New Package Pricing">
    <span id="addNewPricingForm"></span>
</div>

<div id="editPackagePricingForm" title="Edit Package Pricing">
    <span id="editPackagePricingForm"></span>
</div>

<?php
include_once('_footer.inc.php');
?>