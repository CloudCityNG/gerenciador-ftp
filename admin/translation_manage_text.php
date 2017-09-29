<?php
define('ADMIN_SELECTED_SUB_PAGE', 'translation_manage');

// includes and security
include_once('_local_auth.inc.php');

// redirect if we don't know the languageId
if(!isset($_REQUEST['languageId']))
{
    adminFunctions::redirect('translation_manage.php');
}

// try to load the language
$sQL           = "SELECT * FROM language WHERE id = ".(int)$_REQUEST['languageId']." LIMIT 1";
$languageDetail = $db->getRow($sQL);
if(!$languageDetail)
{
    adminFunctions::redirect('translation_manage.php');
}

// delete text item
if(isset($_REQUEST['d']))
{
    $textItem = (int)$_REQUEST['d'];
    $db->query('DELETE FROM language_content WHERE languageKeyId = '.$textItem);
    $db->query('DELETE FROM language_key WHERE id = '.$textItem.' LIMIT 1');
    adminFunctions::setSuccess('Translation removed.');
}

// error/success messages
if (isset($_REQUEST['sa']))
{
    adminFunctions::setSuccess('Translations successully imported.');
}

// initial constants
define('ADMIN_PAGE_TITLE', 'Manage Translations For \''.$languageDetail['languageName'].'\'');
define('ADMIN_SELECTED_PAGE', 'configuration');

// page header
include_once('_header.inc.php');

// defaults
$filterByGroup = null;
if (isset($_REQUEST['filterByGroup']))
{
    $filterByGroup = trim($_REQUEST['filterByGroup']);
}
?>

<script>
    oTable = null;
    gTranslationId = null;
    $(document).ready(function(){
        // datatable
        oTable = $('#fileTable').dataTable({
            "sPaginationType": "full_numbers",
            "bServerSide": true,
            "bProcessing": true,
            "sAjaxSource": 'ajax/translation_manage_text.ajax.php?languageId=<?php echo $languageDetail['id']; ?>',
            "bJQueryUI": true,
            "iDisplayLength": 50,
            "aaSorting": [[ 1, "asc" ]],
            "aoColumns" : [   
                { bSortable: false, sWidth: '3%', sName: 'file_icon', sClass: "center adminResponsiveHide"},
                { sName: 'language_key', sWidth: '17%', sClass: "adminResponsiveHide"},
                { sName: 'english_content', sWidth: '25%', sClass: "adminResponsiveHide"},
                { sName: 'translated_content' },
				{ bSortable: false, sWidth: '3%', sName: 'file_icon', sClass: "center adminResponsiveHide"},
                { bSortable: false, sWidth: '10%', sClass: "center" }
            ],
            "fnServerData": function ( sSource, aoData, fnCallback ) {
                aoData.push( { "name": "filterText", "value": $('#filterText').val() } );
                $.ajax({
                    "dataType": 'json',
                    "type": "GET",
                    "url": "ajax/translation_manage_text.ajax.php?languageId=<?php echo $languageDetail['id']; ?>",
                    "data": aoData,
                    "success": fnCallback
                });
            }
        });
        
        // update custom filter
        $('.dataTables_filter').html($('#customFilter').html());

        // dialog box
        $( "#editTranslationForm" ).dialog({
            modal: true,
            autoOpen: false,
            width: getDefaultDialogWidth(),
            height: 420,
            buttons: {
                "Update": function() {
                    updateTranslationValue();
                },
                "Cancel": function() {
                    $("#editTranslationForm").dialog("close");
                }
            },
            open: function() {
                setLoader();
                loadEditTranslationForm();
                resetOverlays();
            }
        });
		
		// dialog box
        $( "#autoTranslationForm" ).dialog({
            modal: true,
            autoOpen: false,
            width: getDefaultDialogWidth(),
            height: 420,
            buttons: {
                "Close": function() {
                    $("#autoTranslationForm").dialog("close");
                }
            },
            open: function() {
                setLoader();
				startAutoTranslation();
                resetOverlays();
            }
        });
    });
    
    function setLoader()
    {
        $('#translationForm').html('Loading, please wait...');
    }
    
    function loadEditTranslationForm()
    {
        $.ajax({
            type: "POST",
            url: "ajax/translation_manage_text_edit_form.ajax.php",
            data: { gTranslationId: gTranslationId, languageId: <?php echo $languageDetail['id']; ?> },
            dataType: 'json',
            success: function(json) {
                if(json.error == true)
                {
                    $('#translationForm').html(json.msg);
                }
                else
                {
                    $('#translationForm').html(json.html);
                }
                
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                $('#translationForm').html(XMLHttpRequest.responseText);
            }
        });
    }
    
    function updateTranslationValue()
    {
        // get data
        translation_item_id = $('#translation_item_id').val();
        translated_content = $('#translated_content').val();
        
        $.ajax({
            type: "POST",
            url: "ajax/translation_manage_text_edit_process.ajax.php",
            data: { translation_item_id: translation_item_id, translated_content: translated_content, languageId: <?php echo $languageDetail['id']; ?> },
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
                    $("#editTranslationForm").dialog("close");
                }
                
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                showError(XMLHttpRequest.responseText, 'popupMessageContainer');
            }
        });
    }
    
    function editTranslationForm(translationId)
    {
        gTranslationId = translationId;
        $('#editTranslationForm').dialog('open');
    }
	
	function startAutoTranslation()
	{
		$('#autoTranslationFormWrapper').html('Loading, please wait...');
		$('#autoTranslationFormWrapper').html('<iframe src="<?php echo ADMIN_WEB_ROOT; ?>/translation_manage_text_auto_convert.php?languageId=<?php echo $languageDetail['id']; ?>" style="background: url(\'assets/images/spinner.gif\') no-repeat center center;" height="100%" width="100%" frameborder="0" scrolling="auto">Loading...</iframe>');
	}

    function reloadTable()
    {
        oTable.fnDraw(false);
    }
    
    function deleteTranslation(textId)
    {
        if(confirm("Are you sure you want to delete this translation text? It will be removed from ALL languages you have, not just this one. It'll be repopulated with the default translation text when it's requested by the script, or after a translation re-scan."))
        {
            window.location="<?php echo ADMIN_WEB_ROOT; ?>/translation_manage_text.php?languageId=<?php echo $languageDetail['id']; ?>&d="+textId;
        }
        
        return false;
    }
	
	function processAutoTranslate()
	{
		<?php if(strlen(SITE_CONFIG_GOOGLE_TRANSLATE_API_KEY) == 0): ?>
		alert('This process requires a valid Google Translation API key within the site settings. Please add this and try again. Note: There may be a fee from Google for using the auto translation.');
		return false;
		<?php endif; ?>
		
		var enText = $('#enTranslationText').val();
		var toLangCode = $('#enTranslationCode').val();
		$.ajax({
            type: "POST",
            url: "ajax/translation_manage_text_auto_process.ajax.php",
            data: { enText: enText, toLangCode: toLangCode },
            dataType: 'json',
            success: function(json) {
                if(json.error == true)
                {
                    showError(json.msg, 'popupMessageContainer');
                }
                else
                {
                    showSuccess(json.msg);
                    $('#translated_content').val(json.translation);
                }
                
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                showError(XMLHttpRequest.responseText, 'popupMessageContainer');
            }
        });
	}
	
	function confirmAutomaticTranslate()
	{
		<?php if(strlen(SITE_CONFIG_GOOGLE_TRANSLATE_API_KEY) == 0): ?>
		alert('This process requires a valid Google Translation API key within the site settings. Please add this and try again. Note: There may be a fee from Google for using the auto translation.');
		return false;
		<?php endif; ?>
		
		if(confirm("Are you sure you want to automatically translate the above 'en' text into '<?php echo $languageDetail['language_code']; ?>'? This will be done via the Google Translation API and may take some time to complete.\n\nIMPORTANT: This process will OVERWRITE any translations which are not locked ('<?php echo $languageDetail['language_code']; ?>'). If you're unsure, cancel and 'export' a copy of the language so you have a backup.\n\nIf this process timesout, you can re-run it to pickup where it failed. Each new translation is marked as 'locked' so it'll only be translated once."))
		{
			$('#autoTranslationForm').dialog('open');
		}
		
		return false;
	}
	
	function toggleLock(contentId)
    {
        $.ajax({
            type: "POST",
            url: "ajax/translation_manage_text_set_is_locked.ajax.php",
            data: { contentId: contentId },
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
    <div class="sectionLargeIcon largeLanguageIcon"></div>
    <div class="widget clearfix">
        <h2>Translations</h2>
        <div class="widget_inside">
            <?php echo adminFunctions::compileNotifications(); ?>
            <div class="col_12">
                <table id='fileTable' class='dataTable'>
                    <thead>
                        <tr>
                            <th></th>
                            <th class="align-left"><?php echo adminFunctions::t('language_key', 'Language Key'); ?></th>
                            <th class="align-left"><?php echo adminFunctions::t('english_content', 'English Content'); ?></th>
                            <th class="align-left"><?php echo adminFunctions::t('translated_content', 'Translated Content'); ?></th>
							<th></th>
                            <th class="align-left"><?php echo adminFunctions::t('actions', 'Actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
			<div style="float: right;">
                <input type="submit" value="Automatic Translate (via Google Translate)" class="button adminResponsiveHide" onClick="confirmAutomaticTranslate(); return false;"/>
            </div>
            <div class="buttonFloat">
                <input type="submit" value="Back to Manage Languages" class="button blue" onClick="window.location='translation_manage.php';"/>
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

<div id="editTranslationForm" title="Edit Translation">
    <span id="translationForm"></span>
</div>

<div id="autoTranslationForm" title="Auto Translation Progress... (This might take some time!)">
    <span id="autoTranslationFormWrapper"></span>
</div>

<?php
include_once('_footer.inc.php');
?>