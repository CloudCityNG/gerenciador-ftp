<?php
// initial constants
define('ADMIN_PAGE_TITLE', 'Received Payments');
define('ADMIN_SELECTED_PAGE', 'users');
define('ADMIN_SELECTED_SUB_PAGE', 'payment_manage');

// includes and security
include_once('_local_auth.inc.php');

// page header
include_once('_header.inc.php');
?>

<script>
    oTable = null;
    gPaymentId = null;
    $(document).ready(function(){
        // datatable
        oTable = $('#fileTable').dataTable({
            "sPaginationType": "full_numbers",
            "bServerSide": true,
            "bProcessing": true,
            "sAjaxSource": 'ajax/payment_manage.ajax.php',
            "bJQueryUI": true,
            "iDisplayLength": 25,
            "aaSorting": [[ 1, "desc" ]],
            "aoColumns" : [   
                { bSortable: false, sWidth: '3%', sName: 'file_icon', sClass: "center adminResponsiveHide" },
                { sName: 'payment_date', sWidth: '15%' , sClass: "center" },
                { sName: 'user_name', sWidth: '18%' , sClass: "center adminResponsiveHide" },
                { sName: 'description' , sClass: "center adminResponsiveHide" },
                { sName: 'amount', sWidth: '12%', sClass: "center" },
                { bSortable: false, sWidth: '10%', sClass: "center adminResponsiveHide" }
            ],
            "fnServerData": function ( sSource, aoData, fnCallback ) {
                aoData.push( { "name": "filterText", "value": $('#filterText').val() } );
                $.ajax({
                    "dataType": 'json',
                    "type": "GET",
                    "url": "ajax/payment_manage.ajax.php",
                    "data": aoData,
                    "success": fnCallback
                });
            }
        });
        
        // update custom filter
        $('.dataTables_filter').html($('#customFilter').html());

        // dialog box
        $( "#paymentDetailForm" ).dialog({
            modal: true,
            autoOpen: false,
            width: getDefaultDialogWidth(),
            height: 450,
            buttons: {
                "Close": function() {
                    $("#paymentDetailForm").dialog("close");
                }
            },
            open: function() {
                loadPaymentDetail();
                resetOverlays();
            }
        });
        
        // dialog box
        $( "#addPaymentForm" ).dialog({
            modal: true,
            autoOpen: false,
            width: getDefaultDialogWidth(),
            height: 550,
            buttons: {
                "Add Payment": function() {
                    processAddPayment();
                },
                "Cancel": function() {
                    $("#addPaymentForm").dialog("close");
                }
            },
            open: function() {
                loadAddPaymentForm();
                resetOverlays();
            }
        });
    });
    
    function viewPaymentDetail(paymentId)
    {
        gPaymentId = paymentId;
        $('#paymentDetailForm').dialog('open');
    }

    function reloadTable()
    {
        oTable.fnDraw(false);
    }
    
    function loadPaymentDetail()
    {
        $('#paymentDetailInnerWrapper').html('Loading, please wait...');
        $.ajax({
            type: "POST",
            url: "ajax/payment_manage_detail.ajax.php",
            data: { paymentId: gPaymentId },
            dataType: 'json',
            success: function(json) {
                if(json.error == true)
                {
                    $('#paymentDetailInnerWrapper').html(json.msg);
                }
                else
                {
                    $('#paymentDetailInnerWrapper').html(json.html);
                }
                
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                $('#paymentDetailInnerWrapper').html(XMLHttpRequest.responseText);
            }
        });
    }
    
    function loadAddPaymentForm()
    {
        $.ajax({
            type: "POST",
            url: "ajax/payment_manage_add_form.ajax.php",
            data: { },
            dataType: 'json',
            success: function(json) {
                if(json.error == true)
                {
                    $('#paymentForm').html(json.msg);
                }
                else
                {
                    $('#paymentForm').html(json.html);
                }
                
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                $('#paymentForm').html(XMLHttpRequest.responseText);
            }
        });
    }
    
    function processAddPayment()
    {
        // get data
        user_id = $('#user_id').val();
        payment_date = $('#payment_date').val();
        payment_amount = $('#payment_amount').val();
        description = $('#description').val();
        payment_method = $('#payment_method').val();
        notes = $('#notes').val();
        
        $.ajax({
            type: "POST",
            url: "ajax/payment_manage_add_process.ajax.php",
            data: { user_id: user_id, payment_date: payment_date, payment_amount: payment_amount, description: description, payment_method: payment_method, notes: notes },
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
                    $("#addPaymentForm").dialog("close");
                }
                
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                showError(XMLHttpRequest.responseText, 'popupMessageContainer');
            }
        });
    }
    
    function addPaymentForm()
    {
        $('#addPaymentForm').dialog('open');
    }
</script>

<div class="row clearfix">
    <div class="sectionLargeIcon largeServerIcon"></div>
    <div class="widget clearfix">
        <h2>Received Payments</h2>
        <div class="widget_inside responsiveTable">
            <?php echo adminFunctions::compileNotifications(); ?>
            <div class="col_12">
                <table id='fileTable' class='dataTable'>
                    <thead>
                        <tr>
                            <th></th>
                            <th class="align-left"><?php echo UCWords(adminFunctions::t("payment_date", "payment date")); ?></th>
                            <th class="align-left"><?php echo UCWords(adminFunctions::t("user_name", "user name")); ?></th>
                            <th class="align-left"><?php echo UCWords(adminFunctions::t("description", "description")); ?></th>
                            <th class="align-left"><?php echo UCWords(adminFunctions::t("amount", "amount")); ?></th>
                            <th class="align-left"><?php echo UCWords(adminFunctions::t("action", "action")); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
                <div style="padding-top: 6px;">
                    Note: Payments will only show above after the charge is successful and the payment gateway calls back to the site. Any users which have been manually upgraded will not be shown above.
                </div>
            </div>
            <input type="submit" value="Manually Log Payment" class="button blue" onClick="addPaymentForm(); return false;"/>
        </div>
    </div>
</div>

<div class="customFilter" id="customFilter" style="display: none;">
    <label>
        Filter Results:
        <input name="filterText" id="filterText" type="text" onKeyUp="reloadTable(); return false;" style="width: 160px;"/>
    </label>
</div>

<div id="paymentDetailForm" title="Payment Details">
    <span id="paymentDetailInnerWrapper"></span>
</div>

<div id="addPaymentForm" title="Add Payment">
    <span id="paymentForm"></span>
</div>

<?php
include_once('_footer.inc.php');
?>