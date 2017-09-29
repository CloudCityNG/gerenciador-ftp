<?php

// includes and security
define('MIN_ACCESS_LEVEL', 10); // allow moderators
include_once('../_local_auth.inc.php');

$reportId = (int) $_REQUEST['abuseId'];

// prepare result
$result = array();
$result['error'] = false;
$result['msg']   = '';

if (_CONFIG_DEMO_MODE == true)
{
    $result['error'] = true;
    $result['msg']   = adminFunctions::t("no_changes_in_demo_mode");
}
else
{
    // update to accepted
    $db->query('UPDATE file_report SET report_status = \'accepted\' WHERE id = :reportId LIMIT 1', array('reportId' => $reportId));
    if ($db->affectedRows() == 1)
    {
		// send a confirmation email of removal
		$sQL           = "SELECT file_report.file_id, file_report.report_date, file_report.reported_by_name, file_report.reported_by_email, file_report.reported_by_address, file_report.reported_by_telephone_number, file_report.digital_signature, file_report.report_status, file_report.reported_by_ip, file_report.other_information FROM file_report LEFT JOIN file ON file_report.file_id = file.id WHERE file_report.id=" . (int)$reportId . " LIMIT 1";
		$reportDetail = $db->getRow($sQL);
		if($reportDetail)
		{
			// load file
			$file = file::loadById($reportDetail['file_id']);

			// send email
            $subject = t('report_file_accept_email_subject', 'Update on file removal request for [[[SITE_NAME]]]', array('SITE_NAME' => SITE_CONFIG_SITE_NAME));
            $replacements = array(
                'FILE_DETAILS' => $file->getFullShortUrl(),
                'SITE_NAME' => SITE_CONFIG_SITE_NAME,
                'WEB_ROOT' => WEB_ROOT,
				'REPORTER_NAME' => $reportDetail['reported_by_name'],
            );
			$defaultContent  = "Dear [[[REPORTER_NAME]]]<br/><br/>";
            $defaultContent .= "This is confirmation that we have removed the following file you reported on our site:<br/><br/>";
            $defaultContent .= "- [[[FILE_DETAILS]]]<br/><br/>";
            $defaultContent .= "If you have any further questions, feel free to contact us via [[[WEB_ROOT]]].<br/><br/>";
			$defaultContent .= "Kind Regards,<br/>";
			$defaultContent .= "[[[SITE_NAME]]]";
            $htmlMsg = t('report_file_accept_email_content', $defaultContent, $replacements);

            coreFunctions::sendHtmlEmail($reportDetail['reported_by_email'], $subject, $htmlMsg, null, strip_tags(str_replace("<br/>", "\n", $htmlMsg)));
		}

        $result['error'] = false;
        $result['msg']   = 'File removed and report accepted.';
    }
    else
    {
        $result['error'] = true;
        $result['msg']   = 'Could not accept report, please try again later.';
    }
}

echo json_encode($result);
exit;
