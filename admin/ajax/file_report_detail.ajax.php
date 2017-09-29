<?php

// includes and security
define('MIN_ACCESS_LEVEL', 10); // allow moderators
include_once('../_local_auth.inc.php');

// prepare result
$result          = array();
$result['error'] = false;
$result['msg']   = '';

if (!isset($_REQUEST['abuseId']))
{
    $result['error'] = true;
    $result['msg']   = 'Failed finding the report.';
}
else
{
    $abuseId = (int) $_REQUEST['abuseId'];

    // load all server statuses
    $sQL           = "SELECT file_report.file_id, file_report.report_date, file_report.reported_by_name, file_report.reported_by_email, file_report.reported_by_address, file_report.reported_by_telephone_number, file_report.digital_signature, file_report.report_status, file_report.reported_by_ip, file_report.other_information FROM file_report LEFT JOIN file ON file_report.file_id = file.id WHERE file_report.id=" . $abuseId . " LIMIT 1";
    $reportDetail = $db->getRow($sQL);
    if (!$reportDetail)
    {
        $result['error'] = true;
        $result['msg']   = 'Failed finding the report.';
    }
    else
    {
        $file = file::loadById($reportDetail['file_id']);
        
        $result['html'] = '<p style="padding-bottom: 4px;">Full details of the abuse report are below:</p>';
        $result['html'] .= '<span id="popupMessageContainer"></span>';
        $result['html'] .= '<table class="dataTable dataTableStatic">';
        
        $result['html'] .= '<tr>';
        $result['html'] .= '<td style="width: 110px;">File Url:</td>';
        $result['html'] .= '<td><a href="'.$file->getFullShortUrl().'" target="_blank">'.adminFunctions::makeSafe($file->getFullShortUrl()).'</a></td>';
        $result['html'] .= '</tr>';
        
        $result['html'] .= '<tr>';
        $result['html'] .= '<td>Reported Date:</td>';
        $result['html'] .= '<td>'.adminFunctions::makeSafe(coreFunctions::formatDate($reportDetail['report_date'], SITE_CONFIG_DATE_TIME_FORMAT)).'</td>';
        $result['html'] .= '</tr>';
        
        $result['html'] .= '<tr>';
        $result['html'] .= '<td>Status:</td>';
        $statusRow = '<span class="statusText' . str_replace(" ", "", adminFunctions::makeSafe(UCWords($reportDetail['report_status']))) . '"';
        $statusRow .= '>' . $reportDetail['report_status'] . '</span>';
        $result['html'] .= '<td>'.$statusRow.'</td>';
        $result['html'] .= '</tr>';
        
        $result['html'] .= '<tr>';
        $result['html'] .= '<td>Name:</td>';
        $result['html'] .= '<td>'.adminFunctions::makeSafe($reportDetail['reported_by_name']).'</td>';
        $result['html'] .= '</tr>';

        $result['html'] .= '<tr>';
        $result['html'] .= '<td>Email:</td>';
        $result['html'] .= '<td>'.adminFunctions::makeSafe($reportDetail['reported_by_email']).'</td>';
        $result['html'] .= '</tr>';
        
        $result['html'] .= '<tr>';
        $result['html'] .= '<td>Address:</td>';
        $result['html'] .= '<td>'.adminFunctions::makeSafe($reportDetail['reported_by_address']).'</td>';
        $result['html'] .= '</tr>';
        
        $result['html'] .= '<tr>';
        $result['html'] .= '<td>Telephone:</td>';
        $result['html'] .= '<td>'.adminFunctions::makeSafe($reportDetail['reported_by_telephone_number']).'</td>';
        $result['html'] .= '</tr>';
        
        $result['html'] .= '<tr>';
        $result['html'] .= '<td>Digital Signature:</td>';
        $result['html'] .= '<td>'.adminFunctions::makeSafe($reportDetail['digital_signature']).'</td>';
        $result['html'] .= '</tr>';
        
        $result['html'] .= '<tr>';
        $result['html'] .= '<td>Reported By IP:</td>';
        $result['html'] .= '<td>'.adminFunctions::makeSafe($reportDetail['reported_by_ip']).'</td>';
        $result['html'] .= '</tr>';
        
        $result['html'] .= '<tr>';
        $result['html'] .= '<td>Other Information:</td>';
        $result['html'] .= '<td>'.adminFunctions::makeSafe($reportDetail['other_information']).'</td>';
        $result['html'] .= '</tr>';
        
        $result['html'] .= '</table>';
    }
}

echo json_encode($result);
exit;
