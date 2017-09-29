<?php
// initial constants
define('ADMIN_PAGE_TITLE', 'Abuse Reports');
define('ADMIN_SELECTED_PAGE', 'files');
define('MIN_ACCESS_LEVEL', 10); // allow moderators
define('ADMIN_SELECTED_SUB_PAGE', 'file_report_manage');

// includes and security
include_once('_local_auth.inc.php');

// page header
include_once('_header.inc.php');

// status list
$statusDetails = array('pending', 'cancelled', 'accepted');

$filterByReportStatus = 'pending';
if (isset($_REQUEST['filterByReportStatus']))
{
    $filterByReportStatus = trim($_REQUEST['filterByReportStatus']);
}
?>
        
<script>
    oTable = null;
    gFileId = null;
    gAbuseId = null;
    gNotesText = '';
    $(document).ready(function(){
        // datatable
        oTable = $('#fileTable').dataTable({
            "sPaginationType": "full_numbers",
            "bServerSide": true,
            "bProcessing": true,
            "sAjaxSource": 'ajax/file_report_manage.ajax.php',
            "bJQueryUI": true,
            "iDisplayLength": 25,
            "aaSorting": [[ 2, "desc" ]],
            "aoColumns" : [
                { bSortable: false, sWidth: '3%', sName: 'file_icon', sClass: "center adminResponsiveHide" },
                { sName: 'report_date', sWidth: '12%', sClass: "center" },
                { sName: 'file_name', sClass: "adminResponsiveHide" },
                { sName: 'reported_by_name', sWidth: '15%', sClass: "center adminResponsiveHide" },
                { sName: 'reported_by_ip', sWidth: '15%', sClass: "center adminResponsiveHide" },
                { sName: 'status', sWidth: '12%', sClass: "center adminResponsiveHide" },
                { bSortable: false, sWidth: '20%', sClass: "center" }
            ],
            "fnServerData": function ( sSource, aoData, fnCallback ) {
                aoData.push( { "name": "filterText", "value": $('#filterText').val() } );
                aoData.push( { "name": "filterByReportStatus", "value": $('#filterByReportStatus').val() } );
                $.ajax({
                    "dataType": 'json',
                    "type": "GET",
                    "url": "ajax/file_report_manage.ajax.php",
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
                "Delete & Block File": function() {
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
        
        // dialog box
        $( "#viewReport" ).dialog({
            modal: true,
            autoOpen: false,
            width: getDefaultDialogWidth(),
            height: 500,
            buttons: {
                "Remove File": function() {
                    $("#viewReport").dialog("close");
                    confirmRemoveFile(gAbuseId, gNotesText, gFileId);
                },
                "Decline Request": function() {
                    $("#viewReport").dialog("close");
                    declineReport(gAbuseId);
                },
                "Close": function() {
                    $("#viewReport").dialog("close");
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
    
    function confirmRemoveFile(abuseId, notesText, fileId)
    {
        $('#admin_notes').val(notesText);
        $('#confirmDelete').dialog('open');
        gFileId = fileId;
        gAbuseId = abuseId;
    }
    
    function viewReport(abuseId, notesText, fileId, reportStatus)
    {
        $('#viewReport').dialog('open');
        gFileId = fileId;
        gAbuseId = abuseId;
        gNotesText = notesText;

        // show or hide action buttons
        if(reportStatus == 'pending')
        {
            $(":button:contains('Remove File')").prop("disabled", false).removeClass("ui-state-disabled");
            $(":button:contains('Decline Request')").prop("disabled", false).removeClass("ui-state-disabled");
        }
        else
        {
            $(":button:contains('Remove File')").prop("disabled", true).addClass("ui-state-disabled");
            $(":button:contains('Decline Request')").prop("disabled", true).addClass("ui-state-disabled");
            $('.ui-dialog :button').blur();
        }

        $('#viewReport').html('Loading, please wait...');
        $.ajax({
            type: "POST",
            url: "ajax/file_report_detail.ajax.php",
            data: { abuseId: abuseId },
            dataType: 'json',
            success: function(json) {
                if(json.error == true)
                {
                    $('#viewReport').html(json.msg);
                }
                else
                {
                    $('#viewReport').html(json.html);
                }
                
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                $('#viewReport').html(XMLHttpRequest.responseText);
            }
        });
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
                    //  delete file
                    $.ajax({
                        type: "POST",
                        url: "<?php echo _CONFIG_SITE_PROTOCOL; ?>://"+jsonOuter.filePath+"/<?php echo ADMIN_FOLDER_NAME; ?>/ajax/update_file_state.ajax.php",
                        data: { fileId: gFileId, statusId: $('#removal_type').val(), adminNotes: $('#admin_notes').val(), blockUploads: 1, csaKey1: jsonOuter.csaKey1, csaKey2: jsonOuter.csaKey2 },
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
                                acceptReport(gAbuseId);
                                return true;
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
    
    function acceptReport(abuseId)
    {
        gAbuseId = abuseId;
        //  accept report
        $.ajax({
            type: "POST",
            url: "ajax/file_report_accept.ajax.php",
            data: { abuseId: gAbuseId },
            dataType: 'json',
            success: function(json) {
                if(json.error == true)
                {
                    $('#confirmDelete').dialog('close');
                    showError(json.msg);
                }
                else
                {
                    $('#confirmDelete').dialog('close');
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
    
    function declineReport(reportId)
    {
        if(confirm('Are you sure you want to decline this abuse report?'))
        {
            //  decline report
            $.ajax({
                type: "POST",
                url: "ajax/file_report_decline.ajax.php",
                data: { reportId: reportId },
                dataType: 'json',
                success: function(json) {
                    if(json.error == true)
                    {
                        showError(json.msg);
                    }
                    else
                    {
                        reloadTable();
                    }

                },
                error: function (XMLHttpRequest, textStatus, errorThrown) {
                    showError(XMLHttpRequest.responseText);
                }
            });
        }
        
        return false;
    }
</script>

<div class="row clearfix">
    <div class="sectionLargeIcon largeBannedIpIcon"></div>
    <div class="widget clearfix">
        <h2>Abuse Reports</h2>
        <div class="widget_inside responsiveTable">
            <?php echo adminFunctions::compileNotifications(); ?>
            <div class="col_12">
                <table id='fileTable' class='dataTable'>
                    <thead>
                        <tr>
                            <th></th>
                            <th class="align-left"><?php echo adminFunctions::t('report_date', 'Report Date'); ?></th>
                            <th><?php echo adminFunctions::t('file', 'File'); ?></th>
                            <th class="align-left"><?php echo adminFunctions::t('reported_by_name', 'Reported Name'); ?></th>
                            <th style="width: 10%;"><?php echo adminFunctions::t('reported_by_ip', 'Reported By IP'); ?></th>
                            <th style="width: 10%;"><?php echo adminFunctions::t('status', 'Status'); ?></th>
                            <th class="align-left" style="width: 15%;"><?php echo adminFunctions::t('actions', 'Actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="customFilter" id="customFilter" style="display: none;">
    <label>
        Filter Results:
        <input name="filterText" id="filterText" type="text" value="<?php echo adminFunctions::makeSafe($filterText); ?>" onKeyUp="reloadTable(); return false;" style="width: 160px;"/>
    </label>
    <label class="adminResponsiveHide" style="padding-left: 6px;">
        By Status:
        <select name="filterByReportStatus" id="filterByReportStatus" onChange="reloadTable(); return false;" style="width: 120px;">
            <option value="">- all -</option>
            <?php
            foreach ($statusDetails AS $statusDetail)
            {
                echo '<option value="' . $statusDetail . '"';
                if (($filterByReportStatus) && ($filterByReportStatus == $statusDetail))
                {
                    echo ' SELECTED';
                }
                echo '>' . UCWords($statusDetail) . '</option>';
            }
            ?>
        </select>
    </label>
</div>

<div id="confirmDelete" title="Confirm Action">
    <p>Select the type of removal below. You can also add removal notes such as a copy of the original removal request. The notes are only visible by an admin user.</p>
    <form id="removeFileForm" class="form">
        <div class="clearfix">
            <label>Removal Type:</label>
            <div class="input">
                <select name="removal_type" id="removal_type" class="large">
                    <option value="3">General</option>
                    <option value="4" SELECTED>Copyright Breach (DMCA)</option>
                </select>
            </div>
        </div>
        <div class="clearfix alt-highlight">
            <label>Notes:</label>
            <div class="input">
                <textarea name="admin_notes" id="admin_notes" class="xxlarge"></textarea>
            </div>
        </div>
    </form>
</div>

<div id="viewReport" title="View Report"></div>

<?php
include_once('_footer.inc.php');
?>