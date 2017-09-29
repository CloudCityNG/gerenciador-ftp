<?php
// initial constants
define('ADMIN_PAGE_TITLE', 'Manage Banned IP Addresses');
define('ADMIN_SELECTED_PAGE', 'configuration');

// includes and security
include_once('_local_auth.inc.php');

// clear any expired IPs
bannedIP::clearExpiredBannedIps();

// page header
include_once('_header.inc.php');
?>

<script>
    oTable = null;
    gBannedIpId = null;
    $(document).ready(function(){
        // datatable
        oTable = $('#fileTable').dataTable({
            "sPaginationType": "full_numbers",
            "bServerSide": true,
            "bProcessing": true,
            "sAjaxSource": 'ajax/banned_ip_manage.ajax.php',
            "bJQueryUI": true,
            "iDisplayLength": 25,
            "aaSorting": [[ 1, "asc" ]],
            "aoColumns" : [   
                { bSortable: false, sWidth: '3%', sName: 'file_icon', sClass: "center adminResponsiveHide" },
                { sName: 'ip_address', sWidth: '12%' },
                { sName: 'date_banned', sWidth: '12%', sClass: "adminResponsiveHide" },
                { sName: 'ban_type', sWidth: '10%', sClass: "adminResponsiveHide" },
                { sName: 'ban_expiry', sWidth: '15%', sClass: "adminResponsiveHide" },
                { sName: 'ban_notes' , sClass: "adminResponsiveHide"},
                { bSortable: false, sWidth: '10%', sClass: "center" }
            ],
            "fnServerData": function ( sSource, aoData, fnCallback ) {
                aoData.push( { "name": "filterText", "value": $('#filterText').val() } );
                $.ajax({
                    "dataType": 'json',
                    "type": "GET",
                    "url": "ajax/banned_ip_manage.ajax.php",
                    "data": aoData,
                    "success": fnCallback
                });
            }
        });
        
        // update custom filter
        $('.dataTables_filter').html($('#customFilter').html());

        // dialog box
        $( "#addIPForm" ).dialog({
            modal: true,
            autoOpen: false,
            width: getDefaultDialogWidth(),
            height: 496,
            buttons: {
                "Ban IP Address": function() {
                    processBanIPAddress();
                },
                "Cancel": function() {
                    $("#addIPForm").dialog("close");
                }
            },
            open: function() {
                setLoader();
                loadAddIPForm();
                resetOverlays();
            }
        });
        
        // dialog box
        $( "#confirmDelete" ).dialog({
            modal: true,
            autoOpen: false,
            width: getDefaultDialogWidth(),
            buttons: {
                "Delete Banned IP": function() {
                    removeBannedIp();
                    $("#confirmDelete").dialog("close");
                },
                "Cancel": function() {
                    $("#confirmDelete").dialog("close");
                }
            }
        });
    });
    
    function setLoader()
    {
        $('#banIPForm').html('Loading, please wait...');
    }
    
    function loadAddIPForm()
    {
        $.ajax({
            type: "POST",
            url: "ajax/banned_ip_manage_add_form.ajax.php",
            data: { },
            dataType: 'json',
            success: function(json) {
                if(json.error == true)
                {
                    $('#banIPForm').html(json.msg);
                }
                else
                {
                    $('#banIPForm').html(json.html);
                    
                    // date picker
                    $( "#ban_expiry_date" ).datepicker({
                        "dateFormat": "dd/mm/yy",
                        "minDate": 0
                    });
                }
                
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                $('#banIPForm').html(XMLHttpRequest.responseText);
            }
        });
    }
    
    function processBanIPAddress()
    {
        // get data
        ip_address = $('#ip_address').val();
        ban_type = $('#ban_type').val();
        ban_expiry_date = $('#ban_expiry_date').val();
        ban_expiry_hour = $('#ban_expiry_hour').val();
        ban_expiry_minute = $('#ban_expiry_minute').val();
        ban_notes = $('#ban_notes').val();
        
        $.ajax({
            type: "POST",
            url: "ajax/banned_ip_manage_add_process.ajax.php",
            data: { ip_address: ip_address, ban_type: ban_type, ban_expiry_date: ban_expiry_date, ban_expiry_hour: ban_expiry_hour, ban_expiry_minute: ban_expiry_minute, ban_notes: ban_notes },
            dataType: 'json',
            success: function(json) {
                if(json.error == true)
                {
                    showError(json.msg, 'popupMessageContainer');
                }
                else
                {
                    showSuccess(json.msg);
                    reloadTable();
                    $("#addIPForm").dialog("close");
                }
                
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                showError(XMLHttpRequest.responseText, 'popupMessageContainer');
            }
        });

    }
    
    function addIPForm()
    {
        $('#addIPForm').dialog('open');
    }

    function reloadTable()
    {
        oTable.fnDraw(false);
    }
    
    function deleteBannedIp(bannedIpId)
    {
        $('#confirmDelete').dialog('open');
        gBannedIpId = bannedIpId;
    }
    
    function removeBannedIp()
    {
        $.ajax({
            type: "POST",
            url: "ajax/banned_ip_manage_remove.ajax.php",
            data: { bannedIpId: gBannedIpId },
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
</script>

<div class="row clearfix">
    <div class="sectionLargeIcon largeBannedIpIcon"></div>
    <div class="widget clearfix">
        <h2>Banned IP Addresses</h2>
        <div class="widget_inside responsiveTable">
            <?php echo adminFunctions::compileNotifications(); ?>
            <div class="col_12">
                <table id='fileTable' class='dataTable'>
                    <thead>
                        <tr>
                            <th></th>
                            <th class="align-left"><?php echo adminFunctions::t('ip_address', 'IP Address'); ?></th>
                            <th class="align-left"><?php echo adminFunctions::t('date_banned', 'Date Banned'); ?></th>
                            <th class="align-left"><?php echo adminFunctions::t('ban_type', 'Ban Type'); ?></th>
                            <th class="align-left"><?php echo adminFunctions::t('ban_expiry', 'Ban Expiry'); ?></th>
                            <th class="align-left"><?php echo adminFunctions::t('ban_notes', 'Ban Notes'); ?></th>
                            <th class="align-left"><?php echo adminFunctions::t('actions', 'Actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
            <input type="submit" value="Ban IP Address" class="button blue" onClick="addIPForm(); return false;"/>
        </div>
    </div>
</div>

<div class="customFilter" id="customFilter" style="display: none;">
    <label>
        Filter Results:
        <input name="filterText" id="filterText" type="text" onKeyUp="reloadTable(); return false;" style="width: 160px;"/>
    </label>
</div>

<div id="addIPForm" title="Ban IP Address">
    <span id="banIPForm"></span>
</div>

<div id="confirmDelete" title="Confirm Action">
    <p>Are you sure you want to delete this banned ip?</p>
</div>

<?php
include_once('_footer.inc.php');
?>