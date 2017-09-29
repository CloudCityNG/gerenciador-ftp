<?php

// includes and security
include_once('_local_auth.inc.php');

// load file
$file = file::loadById((int)$_REQUEST['fileId']);

// initial constants
define('ADMIN_PAGE_TITLE', 'Downloads for '.substr($file->originalFilename, 0, 50));
define('ADMIN_SELECTED_PAGE', 'downloads');
define('ADMIN_SELECTED_SUB_PAGE', 'download_previous');

// page header
include_once('_header.inc.php');
?>

<script>
    oTable = null;
    oTableRefreshTimer = null;
    $(document).ready(function(){
        // datatable
        oTable = $('#fileTable').dataTable({
            "sPaginationType": "full_numbers",
            "bServerSide": true,
            "bProcessing": true,
            "sAjaxSource": 'ajax/download_previous.ajax.php?fileId=<?php echo (int)$file->id; ?>',
            "bJQueryUI": true,
            "iDisplayLength": 100,
            "aaSorting": [[ 1, "desc" ]],
            "bFilter": false,
            "bLengthChange": false,
            "aoColumns" : [   
                { bSortable: false, sWidth: '3%', sName: 'file_icon', sClass: "center adminResponsiveHide" },
                { sName: 'date_started', sWidth: '15%', sClass: "activeDownloadsColumn" },
                { sName: 'ip_address', sWidth: '12%', sClass: "center adminResponsiveHide" },
                { sName: 'username' },
            ],
            "oLanguage": {
                "sEmptyTable": "There are no active downloads."
            }
        });
    });
    
    function reloadTable()
    {
        oTable.fnDraw();
    }
</script>

<div class="row clearfix">
    <div class="sectionLargeIcon largeDownloadsIcon"></div>
    <div class="widget clearfix">
        <h2>File Downloads</h2>
        <div class="widget_inside responsiveTable">
            <div class="col_12">
                <table id='fileTable' class='dataTable'>
                    <thead>
                        <tr>
                            <th></th>
                            <th class="align-left"><?php echo UCWords(adminFunctions::t("download_date", "download date")); ?></th>
                            <th class="align-left"><?php echo UCWords(adminFunctions::t("ip_address", "ip address")); ?></th>
                            <th class="align-left"><?php echo UCWords(adminFunctions::t("username", "username")); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
include_once('_footer.inc.php');
?>