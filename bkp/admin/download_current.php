<?php
// initial constants
define('ADMIN_PAGE_TITLE', 'Downloads');
define('ADMIN_SELECTED_PAGE', 'downloads');
define('AUTO_REFRESH_PERIOD_SECONDS', 15);
define('ADMIN_SELECTED_SUB_PAGE', 'download_current');

// includes and security
include_once('_local_auth.inc.php');

// clear any expired download trackers
downloadTracker::clearTimedOutDownloads();
downloadTracker::purgeDownloadData();

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
            "sAjaxSource": 'ajax/download_current.ajax.php',
            "bJQueryUI": true,
            "iDisplayLength": 100,
            "aaSorting": [[ 1, "desc" ]],
            "bFilter": false,
            "bLengthChange": false,
            "aoColumns" : [   
                { bSortable: false, sWidth: '3%', sName: 'file_icon', sClass: "center adminResponsiveHide" },
                { sName: 'date_started', sWidth: '15%', sClass: "activeDownloadsColumn" },
                { sName: 'ip_address', sWidth: '12%', sClass: "center adminResponsiveHide" },
                { sName: 'file_name' },
                { sName: 'file_size', sWidth: '12%', sClass: "center adminResponsiveHide" },
                { sName: 'total_threads', sWidth: '10%', sClass: "center adminResponsiveHide" },
                { sName: 'status', sWidth: '14%', sClass: "center adminResponsiveHide" }
            ],
            "oLanguage": {
                "sEmptyTable": "There are no active downloads."
            }
        });
        
        oTableRefreshTimer = setInterval('reloadTable()', <?php echo (int)AUTO_REFRESH_PERIOD_SECONDS; ?> * 1000)
    });
    
    function reloadTable()
    {
        oTable.fnDraw();
    }
</script>

<div class="row clearfix">
    <div class="sectionLargeIcon largeDownloadsIcon"></div>
    <div class="widget clearfix">
        <h2>Active Downloads</h2>
        <div class="widget_inside responsiveTable">
            <p style="padding-bottom: 6px;">The table below shows all active downloads on the site. This screen will automatically refresh every <?php echo AUTO_REFRESH_PERIOD_SECONDS; ?> seconds.</p>
            <p>Note: This page will not show any data if you are using xSendFile (Apache) or xAccelRedirect (Nginx) to handle your file downloads.</p>
            <div class="col_12">
                <table id='fileTable' class='dataTable'>
                    <thead>
                        <tr>
                            <th></th>
                            <th class="align-left"><?php echo UCWords(adminFunctions::t("date_started", "date started")); ?></th>
                            <th class="align-left"><?php echo UCWords(adminFunctions::t("downloader", "downloader")); ?></th>
                            <th class="align-left"><?php echo UCWords(adminFunctions::t("file_name", "file name")); ?></th>
                            <th class="align-left"><?php echo UCWords(adminFunctions::t("file_size", "file size")); ?></th>
                            <th class="align-left"><?php echo UCWords(adminFunctions::t("threads", "threads")); ?></th>
                            <th class="align-left"><?php echo UCWords(adminFunctions::t("status", "status")); ?></th>
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