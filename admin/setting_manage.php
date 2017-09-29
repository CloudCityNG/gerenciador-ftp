<?php
// initial constants
define('ADMIN_PAGE_TITLE', 'Site Settings');
define('ADMIN_SELECTED_PAGE', 'configuration');
define('ADMIN_SELECTED_SUB_PAGE', 'setting_manage');

// includes and security
include_once('_local_auth.inc.php');

// page header
include_once('_header.inc.php');

// load all file status
$sQL           = "SELECT config_group FROM site_config WHERE config_group != 'system' GROUP BY config_group ORDER BY config_group";
$groupDetails = $db->getRows($sQL);

// defaults
$filterByGroup = null;
if (isset($_REQUEST['filterByGroup']))
{
    $filterByGroup = trim($_REQUEST['filterByGroup']);
}
?>

<script>
    oTable = null;
    gConfigId = null;
    $(document).ready(function(){
        // datatable
        oTable = $('#fileTable').dataTable({
            "sPaginationType": "full_numbers",
            "bServerSide": true,
            "bProcessing": true,
            "sAjaxSource": 'ajax/setting_manage.ajax.php',
            "bJQueryUI": true,
            "iDisplayLength": 50,
            "aaSorting": [[ 1, "asc" ]],
            "aoColumns" : [   
                { bSortable: false, sWidth: '4%', sName: 'file_icon', sClass: "center adminResponsiveHide" },
                { sName: 'config_description', sWidth: '48%'},
                { sName: 'config_value' , sClass: "adminResponsiveHide"},
                { bSortable: false, sWidth: '10%', sClass: "center" }
            ],
            "fnServerData": function ( sSource, aoData, fnCallback ) {
                aoData.push( { "name": "filterByGroup", "value": $('#filterByGroup').val() } );
                aoData.push( { "name": "filterText", "value": $('#filterText').val() } );
                $.ajax({
                    "dataType": 'json',
                    "type": "GET",
                    "url": "ajax/setting_manage.ajax.php",
                    "data": aoData,
                    "success": fnCallback
                });
            }
        });
        
        // update custom filter
        $('.dataTables_filter').html($('#customFilter').html());

        // dialog box
        $( "#editConfigurationForm" ).dialog({
            modal: true,
            autoOpen: false,
            width: getDefaultDialogWidth(),
            height: '300',
            buttons: {
                "Update Value": function() {
                    updateConfigurationValue();
                },
                "Cancel": function() {
                    $("#editConfigurationForm").dialog("close");
                }
            },
            open: function() {
                setLoader();
                loadEditConfigurationForm();
                resetOverlays();
            }
        });
    });

    function setLoader()
    {
        $('#configurationForm').html('Loading, please wait...');
    }
    
    function loadEditConfigurationForm()
    {
        $.ajax({
            type: "POST",
            url: "ajax/setting_manage_edit_form.ajax.php",
            data: { gConfigId: gConfigId },
            dataType: 'json',
            success: function(json) {
                if(json.error == true)
                {
                    $('#configurationForm').html(json.msg);
                }
                else
                {
                    $('#configurationForm').html(json.html);
                }
                
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                $('#configurationForm').html(XMLHttpRequest.responseText);
            }
        });
    }
    
    function updateConfigurationValue()
    {
        // get data
        configId = $('#configIdElement').val();
        configValue = $('#configValueElement').val();
        
        $.ajax({
            type: "POST",
            url: "ajax/setting_manage_edit_process.ajax.php",
            data: { configId: configId, configValue: configValue },
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
                    $("#editConfigurationForm").dialog("close");
                }
                
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                showError(XMLHttpRequest.responseText, 'popupMessageContainer');
            }
        });

    }
    
    function editConfigurationForm(configId)
    {
        gConfigId = configId;
        $('#editConfigurationForm').dialog('open');
    }

    function reloadTable()
    {
        oTable.fnDraw(false);
    }
</script>

<div class="row clearfix configurationTable">
    <div class="sectionLargeIcon largeConfigIcon"></div>
    <div class="widget clearfix">
        <h2>Update Configuration</h2>
        <div class="widget_inside responsiveTable">
            <?php echo adminFunctions::compileNotifications(); ?>
			<div class="col_2 clearfix">
                <?php
                // check for theme settings file
				$settingsPath = '';
                $currentTheme = themeHelper::getCurrentThemeKey();
				if(file_exists(SITE_THEME_DIRECTORY_ROOT.$currentTheme.'/admin/settings.php'))
				{
					$settingsPath = SITE_THEME_WEB_ROOT.$currentTheme.'/admin/settings.php';
				}
                
                if(strlen($settingsPath))
                {
                    $currentConfig = themeHelper::themeSpecificConfiguration($currentTheme);
                    ?>
                    <div class="settingsSubHeader">
                        <h4><?php echo adminFunctions::makeSafe($currentConfig['data']['theme_name']); ?></h4>
                    </div>
                    <ul class="square adminResponsiveHide pluginSettingsList">
                    <li><a title="" href="<?php echo $settingsPath; ?>"><span>Theme Settings</span></a></li>
                    </ul>
                    <?php
                }
                ?>
                
                <div class="settingsSubHeader">
                    <h4>Core Settings</h4>
                </div>
				<ul class="square adminResponsiveHide coreSettingsList">
				<?php
				if (COUNT($groupDetails))
				{
					foreach ($groupDetails AS $groupDetail)
					{
						echo '<li>';
						if (($filterByGroup) && ($filterByGroup == $groupDetail['config_group']))
						{
							echo '<strong>';
						}
						echo '<a title="'.adminFunctions::makeSafe($groupDetail['config_group']).'" href="setting_manage.php?filterByGroup='.urlencode($groupDetail['config_group']).'">'.adminFunctions::makeSafe($groupDetail['config_group']).'</a>';
						if (($filterByGroup) && ($filterByGroup == $groupDetail['config_group']))
						{
							echo '</strong>';
						}
						echo '</li>';
					}
				}
				?>
				</ul>
				
				<div class="settingsSubHeader">
                    <h4>Account Packages</h4>
                </div>
				<ul class="square adminResponsiveHide pluginSettingsList">
                <?php
                // add links to manage plugins
                $sQL        = "SELECT * FROM user_level WHERE level_type NOT IN ('admin','moderator') ORDER BY id ASC";
                $packageList = $db->getRows($sQL);
                foreach ($packageList AS $packageItem)
                {
                    ?>
                    <li><a title="<?php echo adminFunctions::makeSafe(UCWords($packageItem['label'])); ?>" href="<?php echo ADMIN_WEB_ROOT; ?>/account_package_manage.php"><span><?php echo adminFunctions::makeSafe(UCWords($packageItem['label'])); ?></span></a></li>
                    <?php
                }
                ?>
                </ul>
                
                <div class="settingsSubHeader">
                    <h4>Plugin Settings</h4>
                </div>
                <ul class="square adminResponsiveHide pluginSettingsList">
                <?php
                // add links to manage plugins
                $sQL        = "SELECT * FROM plugin WHERE is_installed = 1 ORDER BY plugin_name";
                $pluginList = $db->getRows($sQL);
                foreach ($pluginList AS $k => $pluginItem)
                {
                    ?>
                    <li><a title="<?php echo adminFunctions::makeSafe($pluginItem['plugin_name']); ?>" href="<?php echo PLUGIN_WEB_ROOT; ?>/<?php echo adminFunctions::makeSafe($pluginItem['folder_name']); ?>/admin/settings.php?id=<?php echo (int) $pluginItem['id']; ?>"><span><?php echo adminFunctions::makeSafe($pluginItem['plugin_name']); ?></span></a></li>
                    <?php
                }
                ?>
                </ul>
			</div>
            <div class="col_10 last">
                <table id='fileTable' class='dataTable'>
                    <thead>
                        <tr>
                            <th></th>
                            <th class="align-left"><?php echo adminFunctions::t('description', 'Description'); ?></th>
                            <th class="align-left"><?php echo adminFunctions::t('value', 'Value'); ?></th>
                            <th class="align-left"><?php echo adminFunctions::t('actions', 'Actions'); ?></th>
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
        <input name="filterText" id="filterText" type="text" onKeyUp="reloadTable(); return false;" style="width: 160px;"/>
    </label>
    <label class="adminResponsiveHide" style="padding-left: 6px;">
        By Group:
        <select name="filterByGroup" id="filterByGroup" onChange="reloadTable(); return false;" style="width: 220px;">
            <option value="">- all -</option>
            <?php
            if (COUNT($groupDetails))
            {
                foreach ($groupDetails AS $groupDetail)
                {
                    echo '<option value="' . $groupDetail['config_group'] . '"';
                    if (($filterByGroup) && ($filterByGroup == $groupDetail['config_group']))
                    {
                        echo ' SELECTED';
                    }
                    echo '>' . $groupDetail['config_group'] . '</option>';
                }
            }
            ?>
        </select>
    </label>
</div>



<?php
include_once('_footer.inc.php');
?>

<div id="editConfigurationForm" title="Edit Configuration">
    <span id="configurationForm"></span>
</div>