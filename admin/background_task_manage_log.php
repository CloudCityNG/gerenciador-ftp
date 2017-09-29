<?php
// initial constants
define('ADMIN_SELECTED_PAGE', 'configuration');

// includes and security
define('MIN_ACCESS_LEVEL', 20);
include_once('_local_auth.inc.php');

// pickup params
$taskId = null;
if(isset($_REQUEST['task_id']))
{
	$taskId = (int)$_REQUEST['task_id'];
}
if(!$taskId)
{
	coreFunctions::redirect('background_task_manage.php');
}

// load task
$task = $db->getRow('SELECT * FROM background_task WHERE id = '.(int)$taskId.' LIMIT 1');
if(!$task)
{
	coreFunctions::redirect('background_task_manage.php');
}

define('ADMIN_PAGE_TITLE', 'Background Task Logs: "'.$task['task'].'"');

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
            "sAjaxSource": 'ajax/background_task_manage_log.ajax.php?task_id=<?php echo (int)$task['id']; ?>',
            "bJQueryUI": true,
            "iDisplayLength": 25,
			"bFilter": false,
            "aaSorting": [[1, "desc"]],
            "aoColumns": [
                {bSortable: false, sWidth: '3%', sName: 'file_icon', sClass: "center"},
                {sName: 'server', bSortable: false},
                {sName: 'start_time', sWidth: '15%', sClass: "center", bSortable: false},
				{sName: 'end_time', sWidth: '15%', sClass: "center", bSortable: false},
                {sName: 'status', sClass: "center", sWidth: '15%', bSortable: false}
            ]
        });
    });
</script>

<div class="row clearfix">
    <div class="sectionLargeIcon largeLogIcon"></div>
    <div class="widget clearfix">
        <h2>Recent Logs: <?php echo $task['task']; ?></h2>
        <div class="widget_inside">
            <p>All recent runs of this task are listed below, these include any external servers which may also be running this task.</p>
            <?php echo adminFunctions::compileNotifications(); ?>
            <div class="col_12">
                <table id='fileTable' class='dataTable'>
                    <thead>
                        <tr>
                            <th></th>
							<th><?php echo adminFunctions::t('server', 'Server'); ?></th>
                            <th><?php echo adminFunctions::t('start_time', 'Start Time'); ?></th>
                            <th><?php echo adminFunctions::t('end_time', 'End Time'); ?></th>
                            <th><?php echo adminFunctions::t('status', 'Status'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
            <div class="clear"></div>

            <div style="float: right;">
                <a href="https://support.mfscripts.com/public/kb_view/26/" class="button blue" target="_blank">More Information On Background Tasks/Crons</a>
            </div>
			<div style="float: left;">
                <a href="background_task_manage.php" class="button blue">< Back to Background Task Logs</a>
            </div>
            <div class="clear"></div>

        </div>
    </div>
</div>

<?php
include_once('_footer.inc.php');
?>