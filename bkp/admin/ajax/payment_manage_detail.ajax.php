<?php

// includes and security
include_once('../_local_auth.inc.php');

// prepare result
$result          = array();
$result['error'] = false;
$result['msg']   = '';

if (!isset($_REQUEST['paymentId']))
{
    $result['error'] = true;
    $result['msg']   = 'Failed finding payment information.';
}
else
{
    $paymentId = (int) $_REQUEST['paymentId'];

    // load all server statuses
    $sQL           = "SELECT payment_log.id, payment_log.date_created, payment_log.description, payment_log.amount, payment_log.currency_code, payment_log.request_log, payment_log.payment_method, users.username, users.id AS user_id FROM payment_log LEFT JOIN users ON payment_log.user_id = users.id WHERE payment_log.id=" . $paymentId . " LIMIT 1";
    $paymentDetail = $db->getRow($sQL);
    if (!$paymentDetail)
    {
        $result['error'] = true;
        $result['msg']   = 'Failed finding payment information.';
    }
    else
    {
        $result['html'] = '<p style="padding-bottom: 4px;">Full details of the payment are below:</p>';
        $result['html'] .= '<span id="popupMessageContainer"></span>';
        $result['html'] .= '<table class="dataTable dataTableStatic">';
        
        $result['html'] .= '<tr>';
        $result['html'] .= '<td style="width: 110px;">User:</td>';
        $result['html'] .= '<td><a href="user_manage.php?filterByAccountId='.urlencode($paymentDetail['user_id']).'">'.adminFunctions::makeSafe($paymentDetail['username']).'</a></td>';
        $result['html'] .= '</tr>';
        
        $result['html'] .= '<tr>';
        $result['html'] .= '<td>Amount:</td>';
        $result['html'] .= '<td>'.adminFunctions::makeSafe($paymentDetail['amount']).' '.adminFunctions::makeSafe($paymentDetail['currency_code']).' '.(strlen($paymentDetail['payment_method'])?('&nbsp;('.$paymentDetail['payment_method'].')'):'').'</td>';
        $result['html'] .= '</tr>';

        
        $result['html'] .= '<tr>';
        $result['html'] .= '<td>Payment Date:</td>';
        $result['html'] .= '<td>'.adminFunctions::makeSafe(coreFunctions::formatDate($paymentDetail['date_created'], SITE_CONFIG_DATE_TIME_FORMAT)).'</td>';
        $result['html'] .= '</tr>';

        $result['html'] .= '<tr>';
        $result['html'] .= '<td>Description:</td>';
        $result['html'] .= '<td>'.adminFunctions::makeSafe($paymentDetail['description']).'</td>';
        $result['html'] .= '</tr>';
        
        $result['html'] .= '<tr>';
        $result['html'] .= '<td>Payment Log:</td>';
        $result['html'] .= '<td>'.nl2br(adminFunctions::makeSafe($paymentDetail['request_log'])).'</td>';
        $result['html'] .= '</tr>';
        
        $result['html'] .= '</table>';
    }
}

echo json_encode($result);
exit;
