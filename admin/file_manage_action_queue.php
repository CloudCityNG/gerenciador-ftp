<?php
// initial constants
define('ADMIN_PAGE_TITLE', 'Manage File Action Queue');
define('ADMIN_SELECTED_PAGE', 'files');
define('ADMIN_SELECTED_SUB_PAGE', 'file_manage_queue');

// includes and security
define('MIN_ACCESS_LEVEL', 20);
include_once('_local_auth.inc.php');

// process cancels
if (isset($_REQUEST['cancel']))
{
    if (_CONFIG_DEMO_MODE == true)
    {
        adminFunctions::setError(adminFunctions::t("no_changes_in_demo_mode"));
    }
    else
    {
        $queueItem = (int) $_REQUEST['cancel'];
        $db->query('UPDATE file_action SET status = \'cancelled\', last_updated=NOW(), status_msg=\'Cancelled\' WHERE id = ' . (int)$queueItem . ' AND status = \'pending\' LIMIT 1');
    }
}
// process restorations
if (isset($_REQUEST['restore']))
{
    if (_CONFIG_DEMO_MODE == true)
    {
        adminFunctions::setError(adminFunctions::t("no_changes_in_demo_mode"));
    }
    else
    {
        $queueItem = (int) $_REQUEST['restore'];
        $db->query('UPDATE file_action SET status = \'pending\', file_action = \'restore\', last_updated=NOW(), status_msg=\'Processing\' WHERE id = ' . (int)$queueItem . ' AND status = \'pending\' LIMIT 1');
    }
}

// page header
include_once('_header.inc.php');

// load all servers
$sQL           = "SELECT id, serverLabel FROM file_server ORDER BY serverLabel";
$serverDetails = $db->getRows($sQL);

// prepare status
$statusDetails = array('pending', 'processing', 'failed', 'complete', 'cancelled');

// defaults
$filterText = '';
if (isset($_REQUEST['filterText']))
{
    $filterText = trim($_REQUEST['filterText']);
}

$filterByStatus = '';
if (isset($_REQUEST['filterByStatus']))
{
    $filterByStatus = $_REQUEST['filterByStatus'];
}

$filterByServer = null;
if (isset($_REQUEST['filterByServer']))
{
    $filterByServer = (int) $_REQUEST['filterByServer'];
}
?>

<script>
    oTable = null;
    checkboxIds = {};
    gTableLoaded = false;
    $(document).ready(function() {
        // datatable
        oTable = $('#fileTable').dataTable({
            "sPaginationType": "full_numbers",
            "bServerSide": true,
            "bProcessing": true,
            "sAjaxSource": 'ajax/file_manage_action_queue.ajax.php',
            "bJQueryUI": true,
            "iDisplayLength": 25,
            "aaSorting": [[1, "desc"]],
            "aoColumns": [
                {bSortable: false, sWidth: '3%', sName: 'file_icon', sClass: "center adminResponsiveHide"},
                {sName: 'date_added', sWidth: '15%', sClass: "center adminResponsiveHide"},
                {sName: 'server', sWidth: '14%', sClass: "center adminResponsiveHide"},
                {sName: 'file_path'},
                {sName: 'file_action', sWidth: '10%', sClass: "center adminResponsiveHide"},
                {sName: 'status', sWidth: '17%', sClass: "center"},
                {bSortable: false, sWidth: '90px', sClass: "center"}
            ],
            "fnServerData": function(sSource, aoData, fnCallback) {
                aoData.push({"name": "filterText", "value": $('#filterText').val()});
                aoData.push({"name": "filterByServer", "value": $('#filterByServer').val()});
                aoData.push({"name": "filterByStatus", "value": $('#filterByStatus').val()});
                $.ajax({
                    "dataType": 'json',
                    "type": "GET",
                    "url": "ajax/file_manage_action_queue.ajax.php",
                    "data": aoData,
                    "success": fnCallback
                });
                gTableLoaded = true;
            }
        });

        // update custom filter
        $('.dataTables_filter').html($('#customFilter').html());

        // refresh every 10 seconds
        window.setInterval(function() {
            if (gTableLoaded == false)
            {
                return true;
            }
            gTableLoaded = false;
            reloadTable();
        }, 20000);
    });

    function reloadTable()
    {
        oTable.fnDraw(false);
    }

    function cancelItem(itemId)
    {
        if (confirm('Are you sure you want to cancel this item? This can not be reversed.'))
        {
            window.location = "file_manage_action_queue.php?cancel=" + itemId;
        }

        return false;
    }
	
	function restoreItem(itemId)
	{
		if(confirm('Are you sure you want to restore this file?'))
		{
			window.location = "file_manage_action_queue.php?restore=" + itemId;
		}
	}
</script>

<div class="row clearfix">
    <div class="sectionLargeIcon largeFileIcon"></div>
    <div class="widget clearfix">
        <h2>List Of File Actions</h2>
        <div class="widget_inside responsiveTable">
            <p>Below is listed any actions on core files. So queued deletes, moves etc. This page will automatically update every 20 seconds.</p>
            <p style="padding-bottom: 8px;"><strong>Note:</strong> Some deleted files are purposely scheduled for removal in 24 hours - 7 days time, this is to allow time for them to be reverted if needed.</p>
            <?php echo adminFunctions::compileNotifications(); ?>
            <div class="col_12">
                <table id='fileTable' class='dataTable'>
                    <thead>
                        <tr>
                            <th></th>
                            <th><?php echo adminFunctions::t('date_added', 'Date Added'); ?></th>
                            <th class="align-left"><?php echo adminFunctions::t('server', 'Server'); ?></th>
                            <th class="align-left"><?php echo adminFunctions::t('file_path', 'File Path'); ?></th>
                            <th><?php echo adminFunctions::t('file_action', 'File Action'); ?></th>
                            <th><?php echo adminFunctions::t('status', 'Status'); ?></th>
                            <th class="align-left" style="width: 165px;"><?php echo adminFunctions::t('actions', 'Actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
            <div class="clear"></div>

        </div>
    </div>
</div>

<div class="customFilter" id="customFilter" style="display: none;">
    <label>
        Filter By Filename:
        <input name="filterText" id="filterText" type="text" value="<?php echo adminFunctions::makeSafe($filterText); ?>" onKeyUp="reloadTable();
        return false;" style="width: 160px;"/>
    </label>
    <label class="adminResponsiveHide" style="padding-left: 6px;">
        By Server:
        <select name="filterByServer" id="filterByServer" onChange="reloadTable();
        return false;" style="width: 120px;">
            <option value="">- all -</option>
            <?php
            if (COUNT($serverDetails))
            {
                foreach ($serverDetails AS $serverDetail)
                {
                    echo '<option value="' . $serverDetail['id'] . '"';
                    if (($filterByServer) && ($filterByServer == $serverDetail['id']))
                    {
                        echo ' SELECTED';
                    }
                    echo '>' . $serverDetail['serverLabel'] . '</option>';
                }
            }
            ?>
        </select>
    </label>
    <label class="adminResponsiveHide" style="padding-left: 6px;">
        By Status:
        <select name="filterByStatus" id="filterByStatus" onChange="reloadTable();
        return false;" style="width: 120px;">
            <option value="">- all -</option>
            <?php
            if (COUNT($statusDetails))
            {
                foreach ($statusDetails AS $statusDetail)
                {
                    echo '<option value="' . $statusDetail . '"';
                    if (($filterByStatus) && ($filterByStatus == $statusDetail))
                    {
                        echo ' SELECTED';
                    }
                    echo '>' . UCWords($statusDetail) . '</option>';
                }
            }
            ?>
        </select>
    </label>
</div>

<?php
include_once('_footer.inc.php');
?>