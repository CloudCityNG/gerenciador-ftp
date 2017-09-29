<?php
// initial constants
define('ADMIN_PAGE_TITLE', 'Manage Languages');
define('ADMIN_SELECTED_PAGE', 'configuration');
define('ADMIN_SELECTED_SUB_PAGE', 'translation_manage');

// includes and security
include_once('_local_auth.inc.php');

// action rebuild request
if(isset($_REQUEST['rebuild']))
{
    $rs = translate::rebuildTranslationsFromCode();
    if($rs)
    {
        adminFunctions::setSuccess('Scan complete. Total found: '.$rs['foundTotal'].'. Total added: '.$rs['addedTotal']);
    }
}

// page header
include_once('_header.inc.php');
?>

<script>
    oTable = null;
    gLanguageId = null;
    gDefaultLanguage = '';
    gEditLanguageId = null;
    $(document).ready(function(){
        // datatable
        oTable = $('#fileTable').dataTable({
            "sPaginationType": "full_numbers",
            "bServerSide": true,
            "bProcessing": true,
            "sAjaxSource": 'ajax/translation_manage.ajax.php',
            "bJQueryUI": true,
            "iDisplayLength": 25,
            "aaSorting": [[ 1, "asc" ]],
            "aoColumns" : [   
                { bSortable: false, sWidth: '3%', sName: 'file_icon', sClass: "center adminResponsiveHide" },
                { sName: 'language' },
                { bSortable: false, sWidth: '10%', sClass: "center adminResponsiveHide" },
                { bSortable: false, sWidth: '10%', sClass: "center adminResponsiveHide" },
                { bSortable: false, sWidth: '10%', sClass: "center adminResponsiveHide" },
                { bSortable: false, sWidth: '25%', sClass: "center dataTableFix responsiveTableColumn" }
            ],
            "fnServerData": function ( sSource, aoData, fnCallback ) {
                aoData.push( { "name": "filterText", "value": $('#filterText').val() } );
                $.ajax({
                    "dataType": 'json',
                    "type": "GET",
                    "url": "ajax/translation_manage.ajax.php",
                    "data": aoData,
                    "success": fnCallback
                });
            }
        });
        
        // update custom filter
        $('.dataTables_filter').html($('#customFilter').html());

        // dialog box
        $( "#addLanguageForm" ).dialog({
            modal: true,
            autoOpen: false,
            width: getDefaultDialogWidth(),
            height: 410,
            buttons: {
                "Add Language": function() {
                    processAddLanguage();
                },
                "Cancel": function() {
                    $("#addLanguageForm").dialog("close");
                }
            },
            open: function() {
                setLoader();
                loadAddLanguageForm();
                resetOverlays();
            }
        });
        
        // dialog box
        $( "#editLanguageForm" ).dialog({
            modal: true,
            autoOpen: false,
            width: getDefaultDialogWidth(),
            height: 410,
            buttons: {
                "Update Language": function() {
                    processEditLanguage();
                },
                "Cancel": function() {
                    $("#editLanguageForm").dialog("close");
                }
            },
            open: function() {
                setEditLoader();
                loadEditLanguageForm();
                resetOverlays();
            }
        });
        
        // dialog box
        $( "#confirmDelete" ).dialog({
            modal: true,
            autoOpen: false,
            width: getDefaultDialogWidth(),
            buttons: {
                "Delete Language": function() {
                    removeLanguage();
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
        
        // dialog box
        $( "#confirmSetAsDefault" ).dialog({
            modal: true,
            autoOpen: false,
            width: getDefaultDialogWidth(),
            buttons: {
                "Set As Default Language": function() {
                    setDefaultLanguage();
                    $("#confirmSetAsDefault").dialog("close");
                },
                "Cancel": function() {
                    $("#confirmSetAsDefault").dialog("close");
                }
            },
            open: function() {
                resetOverlays();
            }
        });
    });
    
    function setLoader()
    {
        $('#languageForm').html('Loading, please wait...');
    }
    
    function setEditLoader()
    {
        $('#languageEditForm').html('Loading, please wait...');
    }
    
    function loadAddLanguageForm()
    {
        $.ajax({
            type: "POST",
            url: "ajax/translation_manage_add_form.ajax.php",
            data: { },
            dataType: 'json',
            success: function(json) {
                if(json.error == true)
                {
                    $('#languageForm').html(json.msg);
                }
                else
                {
                    $('#languageForm').html(json.html);
                    $('#translation_flag').ddslick({
                        height: 150,
                        background: '#ffffff',
                        onSelected: function(selectedData){
                            $('#translation_flag_hidden').val(selectedData.selectedData.value);
                        }
                    });
                }
                
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                $('#languageForm').html(XMLHttpRequest.responseText);
            }
        });
    }
    
    function loadEditLanguageForm()
    {
        $.ajax({
            type: "POST",
            url: "ajax/translation_manage_add_form.ajax.php",
            data: { languageId: gEditLanguageId },
            dataType: 'json',
            success: function(json) {
                if(json.error == true)
                {
                    $('#languageEditForm').html(json.msg);
                }
                else
                {
                    $('#languageEditForm').html(json.html);
                    $('#translation_flag').ddslick({
                        height: 150,
                        background: '#ffffff',
                        onSelected: function(selectedData){
                            $('#translation_flag_hidden').val(selectedData.selectedData.value);
                        }
                    });
                }
                
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                $('#languageForm').html(XMLHttpRequest.responseText);
            }
        });
    }
    
    function processAddLanguage()
    {
        // get data
        translation_name = $('#translation_name').val();
        translation_flag = $('#translation_flag_hidden').val();
        direction = $('#direction').val();
		language_code = $('#language_code').val();
        
        $.ajax({
            type: "POST",
            url: "ajax/translation_manage_add_process.ajax.php",
            data: { translation_name: translation_name, translation_flag: translation_flag, direction: direction, language_code: language_code },
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
                    $("#addLanguageForm").dialog("close");
                }
                
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                showError(XMLHttpRequest.responseText, 'popupMessageContainer');
            }
        });

    }
    
    function processEditLanguage()
    {
        // get data
        translation_name = $('#translation_name').val();
        translation_flag = $('#translation_flag_hidden').val();
        direction = $('#direction').val();
		language_code = $('#language_code').val();
        
        $.ajax({
            type: "POST",
            url: "ajax/translation_manage_add_process.ajax.php",
            data: { translation_name: translation_name, translation_flag: translation_flag, languageId: gEditLanguageId, direction: direction, language_code: language_code },
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
                    $("#editLanguageForm").dialog("close");
                }
                
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                showError(XMLHttpRequest.responseText, 'popupMessageContainer');
            }
        });

    }
    
    function addLanguageForm()
    {
        $('#addLanguageForm').dialog('open');
    }
    
    function editLanguageForm(languageId)
    {
        gEditLanguageId = languageId;
        $('#editLanguageForm').dialog('open');
    }

    function reloadTable()
    {
        oTable.fnDraw(false);
    }
    
    function deleteLanguage(languageId)
    {
        $('#confirmDelete').dialog('open');
        gLanguageId = languageId;
    }
    
    function removeLanguage()
    {
        $.ajax({
            type: "POST",
            url: "ajax/translation_manage_remove.ajax.php",
            data: { languageId: gLanguageId },
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
    
    function setDefault(defaultLanguage)
    {
        $('#confirmSetAsDefault').dialog('open');
        gDefaultLanguage = defaultLanguage;
    }
    
    function setDefaultLanguage()
    {
        $.ajax({
            type: "POST",
            url: "ajax/translation_manage_set_default_language.ajax.php",
            data: { defaultLanguage: gDefaultLanguage },
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
    
    function setAvailableState(languageId, state)
    {
        $.ajax({
            type: "POST",
            url: "ajax/translation_manage_set_available_state.ajax.php",
            data: { languageId: languageId, state: state },
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
    
    function confirmRescan()
    {
        if(confirm('Are you sure you want to scan the codebase for missing translations? This will examine each file in the script for translations and automatically add the default text into the database. This process can take some time to complete.'))
        {
            window.location = 'translation_manage.php?rebuild=1';
        }
        
        return false;
    }
</script>

<div class="row clearfix">
    <div class="sectionLargeIcon largeLanguageIcon"></div>
    <div class="widget clearfix">
        <h2>Available Languages</h2>
        <div class="widget_inside responsiveTable">
            <?php echo adminFunctions::compileNotifications(); ?>
            <div class="col_12">
                <table id='fileTable' class='dataTable'>
                    <thead>
                        <tr>
                            <th></th>
                            <th class="align-left"><?php echo UCWords(adminFunctions::t('language_name', 'Language Name')); ?></th>
                            <th class="align-left"><?php echo UCWords(adminFunctions::t('default', 'Default')); ?></th>
                            <th class="align-left"><?php echo UCWords(adminFunctions::t('available', 'Available')); ?></th>
                            <th class="align-left"><?php echo UCWords(adminFunctions::t('direction', 'Direction')); ?></th>
                            <th class="align-left"><?php echo UCWords(adminFunctions::t('actions', 'Actions')); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
            
            <div style="float: right;">
                <input type="submit" value="Scan For Missing Translations" class="button adminResponsiveHide" onClick="confirmRescan(); return false;"/>
                <input type="submit" value="Export Translations" class="button adminResponsiveHide" onClick="window.location='translation_manage_export.php'; return false;"/>
                <input type="submit" value="Import Translations" class="button adminResponsiveHide" onClick="window.location='translation_manage_import.php'; return false;"/>
            </div>
            <div class="buttonFloat">
                <input type="submit" value="Add Language" class="button blue" onClick="addLanguageForm(); return false;"/>
            </div>
            
        </div>
    </div>
</div>

<div class="customFilter" id="customFilter" style="display: none;">
    <label>
        Filter Results:
        <input name="filterText" id="filterText" type="text" onKeyUp="reloadTable(); return false;" style="width: 160px;"/>
    </label>
</div>

<div id="addLanguageForm" title="Add Language">
    <span id="languageForm"></span>
</div>

<div id="editLanguageForm" title="Edit Language">
    <span id="languageEditForm"></span>
</div>

<div id="confirmDelete" title="Confirm Action">
    <p>Are you sure you want to delete this language? Any translations will also be removed.</p>
</div>

<div id="confirmSetAsDefault" title="Confirm Action">
    <p>Are you sure you want to set this language as the default on the site?</p>
</div>

<?php
include_once('_footer.inc.php');
?>