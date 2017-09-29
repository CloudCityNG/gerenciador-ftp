<?php
// initial constants
define('ADMIN_PAGE_TITLE', 'Background Task Logs');
define('ADMIN_SELECTED_PAGE', 'configuration');
define('ADMIN_SELECTED_SUB_PAGE', 'background_task');

// includes and security
define('MIN_ACCESS_LEVEL', 20);
include_once('_local_auth.inc.php');

// page header
include_once('_header.inc.php');

?>

<script>
    oTable = null;
    $(document).ready(function() {
        // datatable
        oTable = $('#fileTable').dataTable({
            "sPaginationType": "full_numbers",
            "bServerSide": true,
            "bProcessing": true,
            "sAjaxSource": 'ajax/background_task_manage.ajax.php',
            "bJQueryUI": true,
            "iDisplayLength": 25,
			"bFilter": false,
            "aaSorting": [[1, "asc"]],
            "aoColumns": [
                {bSortable: false, sWidth: '3%', sName: 'file_icon', sClass: "center adminResponsiveHide"},
                {sName: 'task_name', bSortable: false},
                {sName: 'last_update', sWidth: '15%', sClass: "center", bSortable: false},
                {sName: 'status', sClass: "center", sWidth: '15%', bSortable: false},
                {bSortable: false, sClass: "center", sWidth: '15%', sClass: "adminResponsiveHide"}
            ]
        });
    });
</script>

<div class="row clearfix">
    <div class="sectionLargeIcon largeLogIcon"></div>
    <div class="widget clearfix">
        <h2>List Of Background/Cron Tasks</h2>
        <div class="widget_inside responsiveTable">
            <p>Below is the background (cron) tasks set to run on the system. Use this page to ensure they're running and see the last run time. For more information on setting up the crons, <a href="https://support.mfscripts.com/public/kb_view/26/" target="_blank">see here</a>.</p>
            <?php echo adminFunctions::compileNotifications(); ?>
            <div class="col_12">
                <table id='fileTable' class='dataTable'>
                    <thead>
                        <tr>
                            <th></th>
                            <th class="align-left"><?php echo adminFunctions::t('task_name', 'Task Name'); ?></th>
                            <th class="align-left"><?php echo adminFunctions::t('last_run', 'Last Run'); ?></th>
                            <th><?php echo adminFunctions::t('status', 'Status'); ?></th>
                            <th class="align-left"><?php echo adminFunctions::t('actions', 'Actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
            <div class="clear"></div>

            <div class="adminResponsiveHide">
                <a href="https://support.mfscripts.com/public/kb_view/26/" class="button blue" target="_blank">More Information On Background Tasks/Crons</a>
            </div>
            <div class="clear"></div>

        </div>
    </div>
</div>

<?php
include_once('_footer.inc.php');
?>