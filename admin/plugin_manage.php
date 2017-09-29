<?php
// initial constants
define('ADMIN_PAGE_TITLE', 'Plugins');
define('ADMIN_SELECTED_PAGE', 'plugins');
define('ADMIN_SELECTED_SUB_PAGE', 'plugin_manage');

// includes and security
include_once('_local_auth.inc.php');

// error/success messages
if (isset($_REQUEST['sa']))
{
    adminFunctions::setSuccess('Plugin successfully added. To enable the plugin, install it below and configure any plugin specific settings.');
}
elseif (isset($_REQUEST['se']))
{
    adminFunctions::setSuccess('Plugin settings updated.');
}
elseif (isset($_REQUEST['sm']))
{
    // redirect to plugin settings
    if(strlen(trim($_REQUEST['plugin'])))
    {
        adminFunctions::redirect(PLUGIN_WEB_ROOT.'/'.urlencode(trim($_REQUEST['plugin'])).'/admin/settings.php?id='.(int)$_REQUEST['id'].'&sm='.urlencode($_REQUEST['sm']));
    }
    else
    {
        adminFunctions::setSuccess(urldecode($_REQUEST['sm']));
    }
}
elseif (isset($_REQUEST['d']))
{
    adminFunctions::setSuccess(urldecode($_REQUEST['d']));
}
elseif (isset($_REQUEST['error']))
{
    adminFunctions::setError(urldecode($_REQUEST['error']));
}

// update plugin config cache
pluginHelper::loadPluginConfigurationFiles(true);

// page header
include_once('_header.inc.php');
?>

<script>
    oTable = null;
    gPluginId = null;
    $(document).ready(function(){
        // datatable
        oTable = $('#fileTable').dataTable({
            "sPaginationType": "full_numbers",
            "bServerSide": true,
            "bProcessing": true,
            "sAjaxSource": 'ajax/plugin_manage.ajax.php',
            "bJQueryUI": true,
            "iDisplayLength": 100,
            "bLengthChange": false,
            "bFilter": false,
            "aoColumns" : [   
                { bSortable: false, sWidth: '3%', sName: 'file_icon', sClass: "center adminResponsiveHide"},
                { bSortable: false, sName: 'plugin_title'},
                { bSortable: false, sName: 'directory_name', sWidth: '14%', sClass: "adminResponsiveHide"},
                { bSortable: false, sName: 'installed', sWidth: '10%', sClass: "center adminResponsiveHide" },
				{ bSortable: false, sName: 'version', sWidth: '10%', sClass: "center adminResponsiveHide" },
                { bSortable: false, sWidth: '20%', sClass: "center", sClass: "center"}
            ],
            "oLanguage": {
                "sEmptyTable": "You have no plugins configured within your site. Go to <a href='<?php echo themeHelper::getCurrentProductUrl(); ?>' target='_blank'><?php echo themeHelper::getCurrentProductName(); ?></a> to see a list of available plugins."
            }
        });

        // dialog box
        $( "#confirmInstall" ).dialog({
            modal: true,
            autoOpen: false,
            width: getDefaultDialogWidth(),
            buttons: {
                "Install": function() {
                    installPlugin();
                    $("#confirmInstall").dialog("close");
                },
                "Cancel": function() {
                    $("#confirmInstall").dialog("close");
                }
            },
            open: function() {
                resetOverlays();
            }
        });
        
        $( "#confirmUninstall" ).dialog({
            modal: true,
            autoOpen: false,
            width: getDefaultDialogWidth(),
            buttons: {
                "Uninstall": function() {
                    uninstallPlugin();
                    $("#confirmUninstall").dialog("close");
                },
                "Cancel": function() {
                    $("#confirmUninstall").dialog("close");
                }
            },
            open: function() {
                resetOverlays();
            }
        });
        
        $( "#confirmDelete" ).dialog({
            modal: true,
            autoOpen: false,
            width: getDefaultDialogWidth(),
            buttons: {
                "Delete": function() {
                    deletePlugin();
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
    
    function confirmInstallPlugin(plugin_id)
    {
        $('#confirmInstall').dialog('open');
        gPluginId = plugin_id;
    }
    
    function confirmUninstallPlugin(plugin_id)
    {
        $('#confirmUninstall').dialog('open');
        gPluginId = plugin_id;
    }
    
    function confirmDeletePlugin(plugin_id)
    {
        $('#confirmDelete').dialog('open');
        gPluginId = plugin_id;
    }
    
    function installPlugin()
    {
        $.ajax({
            type: "POST",
            url: "ajax/plugin_manage_install.ajax.php",
            data: { plugin_id: gPluginId },
            dataType: 'json',
            success: function(json) {
                if(json.error == true)
                {
                    showError(json.msg, 'messageContainer');
                }
                else
                {
                    //showSuccess(json.msg, 'messageContainer');
                    //reloadTable();
                    window.location='plugin_manage.php?id='+encodeURIComponent(json.id)+'&plugin='+encodeURIComponent(json.plugin)+'&sm='+encodeURIComponent(json.msg);
                }
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                showError(XMLHttpRequest.responseText, 'messageContainer');
            }
        });
    }
    
    function uninstallPlugin()
    {
        $.ajax({
            type: "POST",
            url: "ajax/plugin_manage_uninstall.ajax.php",
            data: { plugin_id: gPluginId },
            dataType: 'json',
            success: function(json) {
                if(json.error == true)
                {
                    showError(json.msg, 'messageContainer');
                }
                else
                {
                    //showSuccess(json.msg, 'messageContainer');
                    //reloadTable();
                    window.location='plugin_manage.php?sm='+encodeURIComponent(json.msg);
                }
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                showError(XMLHttpRequest.responseText, 'messageContainer');
            }
        });
    }
    
    function deletePlugin()
    {
        $.ajax({
            type: "POST",
            url: "ajax/plugin_manage_delete.ajax.php",
            data: { plugin_id: gPluginId },
            dataType: 'json',
            success: function(json) {
                if(json.error == true)
                {
                    showError(json.msg, 'messageContainer');
                }
                else
                {
                    //showSuccess(json.msg, 'messageContainer');
                    //reloadTable();
                    window.location='plugin_manage.php?d='+encodeURIComponent(json.msg);
                }
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                showError(XMLHttpRequest.responseText, 'messageContainer');
            }
        });
    }
</script>

<div class="row clearfix">
    <div class="sectionLargeIcon largeServerIcon"></div>
    <div class="widget clearfix">
        <h2>Manage Plugins</h2>
        <div class="widget_inside responsiveTable">
            <?php echo adminFunctions::compileNotifications(); ?>
            <span id="messageContainer"></span>
            <div class="col_12">
                <table id='fileTable' class='dataTable'>
                    <thead>
                        <tr>
                            <th></th>
                            <th class="align-left"><?php echo UCWords(adminFunctions::t("plugin", "plugin")); ?></th>
                            <th class="align-left"><?php echo UCWords(adminFunctions::t("directory_name", "directory name")); ?></th>
                            <th class="align-left"><?php echo UCWords(adminFunctions::t("installed", "installed?")); ?></th>
							<th class="align-left"><?php echo UCWords(adminFunctions::t("version", "version")); ?></th>
                            <th class="align-left"><?php echo UCWords(adminFunctions::t("action", "action")); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
            <input type="submit" value="Add Plugin" class="button blue" onClick="window.location='plugin_manage_add.php';"/>
			<input type="submit" value="Get Plugins" class="button blue" onClick="window.open('<?php echo themeHelper::getCurrentProductUrl(); ?>/plugins.html','_blank');"/>
        </div>
    </div>
</div>

<div id="confirmInstall" title="Confirm Action">
    <p>Are you sure you want to install this plugin?</p>
</div>

<div id="confirmUninstall" title="Confirm Action">
    <p>Are you sure you want to uninstall this plugin? All data associated with the plugin will be deleted and unrecoverable.</p>
</div>
<div id="confirmDelete" title="Confirm Action">
    <p>Are you sure you want to delete this plugin? All data associated with the plugin will be deleted and unrecoverable.</p>
</div>

<?php
include_once('_footer.inc.php');
?>