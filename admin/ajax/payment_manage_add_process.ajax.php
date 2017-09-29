<?php

// includes and security
include_once('../_local_auth.inc.php');

$user_id = trim($_REQUEST['user_id']);
$payment_date = trim($_REQUEST['payment_date']);
$payment_amount = (float)trim($_REQUEST['payment_amount']);
$description = trim($_REQUEST['description']);
$payment_method = trim($_REQUEST['payment_method']);
$notes = trim($_REQUEST['notes']);

// prepare result
$result = array();
$result['error'] = false;
$result['msg']   = '';

if (strlen($user_id) == 0)
{
    $result['error'] = true;
    $result['msg']   = adminFunctions::t("please_select_the_username", "Please select the username.");
}
elseif (strlen($payment_date) == 0)
{
    $result['error'] = true;
    $result['msg']   = adminFunctions::t("please_enter_the_payment_date", "Please enter the payment date.");
}
elseif ((float)$payment_amount == 0)
{
    $result['error'] = true;
    $result['msg']   = adminFunctions::t("please_enter_the_payment_amount", "Please enter the payment amount.");
}
elseif (_CONFIG_DEMO_MODE == true)
{
    $result['error'] = true;
    $result['msg']   = adminFunctions::t("no_changes_in_demo_mode");
}
else
{
    // default description
    if (strlen($description) == 0)
    {
        $description = 'Payment of '.SITE_CONFIG_COST_CURRENCY_SYMBOL.$payment_amount;
    }
    
    // reformat date for database
    $dbDate = date_create_from_format(SITE_CONFIG_DATE_TIME_FORMAT, $payment_date);
  
    // add the payment record
    $dbInsert = new DBObject("payment_log", array("user_id", "date_created", "amount", "currency_code", "description", "request_log", "payment_method"));
    $dbInsert->user_id = $user_id;
    $dbInsert->date_created = coreFunctions::formatDate($dbDate->getTimestamp(), 'Y-m-d H:i:s');
    $dbInsert->amount = $payment_amount;
    $dbInsert->currency_code = SITE_CONFIG_COST_CURRENCY_CODE;
    $dbInsert->description = $description;
    $dbInsert->request_log = $notes;
    $dbInsert->payment_method = $payment_method;
    $rs = $dbInsert->insert();
    if (!$rs)
    {
        $result['error'] = true;
        $result['msg']   = adminFunctions::t("error_problem_payment_log_record", "There was a problem adding the payment log, please try again.");
    }
    else
    {
        $result['error'] = false;
        $result['msg']   = 'Payment has been logged.';
    }
}

echo json_encode($result);
exit;
