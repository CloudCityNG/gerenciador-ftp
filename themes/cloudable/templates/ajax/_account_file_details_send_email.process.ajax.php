<?php

//setup database
$db = Database::getDatabase(true);

// handle submission
if ((int) $_REQUEST['submitme'])
{
    // validation
    $fileId            = (int) $_REQUEST['fileId'];
    $shareRecipientName = substr(trim($_REQUEST['shareRecipientName']), 0, 255);
    $shareEmailAddress = substr(strtolower(trim($_REQUEST['shareEmailAddress'])), 0, 255);
    $shareExtraMessage = trim($_REQUEST['shareExtraMessage']);
    if (strlen($shareRecipientName) == 0)
    {
        notification::setError(t("please_enter_the_recipient_name", "Please enter the recipient name."));
    }
    elseif (strlen($shareEmailAddress) == 0)
    {
        notification::setError(t("please_enter_the_recipient_email_address", "Please enter the recipient email address."));
    }
    elseif (validation::validEmail($shareEmailAddress) == false)
    {
        notification::setError(t("please_enter_a_valid_recipient_email_address", "Please enter a valid recipient email address."));
    }
    else
    {
        // make sure this user owns the file
		// @TODO - or file is public if publicly sharing
        $file = file::loadById($fileId);
        if (!$file)
        {
            notification::setError(t("could_not_load_file", "There was a problem loading the file."));
        }
        //elseif ($file->userId != $Auth->id)
        //{
        //    notification::setError(t("could_not_load_file", "There was a problem loading the file."));
        //}
    }

    // send the email
    if (!notification::isErrors())
    {
        // prepare variables
        $shareRecipientName = strip_tags($shareRecipientName);
        $shareEmailAddress = strip_tags($shareEmailAddress);
        $shareExtraMessage = strip_tags($shareExtraMessage);
        $shareExtraMessage = substr($shareExtraMessage, 0, 2000);
		
		// blank out extra message for non logged in user
		if($Auth->loggedIn() == false)
		{
			$shareExtraMessage = '';
		}
		
		// setup shared by names
		$sharedBy = t('guest', 'Guest');
		$sharedByEmail = '';
		if($Auth->loggedIn())
		{
			$sharedBy = $Auth->getAccountScreenName();
			$sharedByEmail = $Auth->email;
		}
        
        // send the email
        $subject = t('account_file_details_share_via_email_subject', 'Image shared by [[[SHARED_BY_NAME]]] on [[[SITE_NAME]]]', array('SITE_NAME' => SITE_CONFIG_SITE_NAME, 'SHARED_BY_NAME' => $sharedBy));

        $replacements = array(
            'SITE_NAME' => SITE_CONFIG_SITE_NAME,
            'WEB_ROOT' => WEB_ROOT,
            'RECIPIENT_NAME' => $shareRecipientName,
            'SHARED_BY_NAME' => $sharedBy,
            'SHARED_EMAIL_ADDRESS' => $sharedByEmail,
            'EXTRA_MESSAGE' => strlen($shareExtraMessage)?nl2br($shareExtraMessage):t('not_applicable_short', 'n/a'),
            'FILE_NAME' => $file->originalFilename,
            'FILE_URL' => $file->getFullShortUrl()
        );
        $defaultContent = "Dear [[[RECIPIENT_NAME]]],<br/><br/>";
        $defaultContent .= "[[[SHARED_BY_NAME]]] has shared the following image with you via <a href='[[[WEB_ROOT]]]'>[[[SITE_NAME]]]</a>:<br/><br/>";
        $defaultContent .= "<strong>Image:</strong> [[[FILE_NAME]]]<br/>";
        $defaultContent .= "<strong>View:</strong> [[[FILE_URL]]]<br/>";
        $defaultContent .= "<strong>From:</strong> [[[SHARED_BY_NAME]]] [[[SHARED_EMAIL_ADDRESS]]]<br/>";
        $defaultContent .= "<strong>Message:</strong><br/>[[[EXTRA_MESSAGE]]]<br/><br/>";
        $defaultContent .= "Feel free to contact us if you have any difficulties viewing the image.<br/><br/>";
        $defaultContent .= "Regards,<br/>";
        $defaultContent .= "[[[SITE_NAME]]] Admin";
        $htmlMsg = t('account_file_details_share_via_email_content', $defaultContent, $replacements);

        coreFunctions::sendHtmlEmail($shareEmailAddress, $subject, $htmlMsg, SITE_CONFIG_DEFAULT_EMAIL_ADDRESS_FROM, strip_tags(str_replace("<br/>", "\n", $htmlMsg)));
        notification::setSuccess(t("image_sent_via_email_to_x", "Image sent via email to [[[RECIPIENT_EMAIL_ADDRESS]]]", array('RECIPIENT_EMAIL_ADDRESS' => $shareEmailAddress)));
    }
}

// prepare result
$returnJson            = array();
$returnJson['success'] = false;
$returnJson['msg']     = t("problem_updating_item", "There was a problem sending the email, please try again later.");
if (notification::isErrors())
{
    // error
    $returnJson['success'] = false;
    $returnJson['msg']     = implode('<br/>', notification::getErrors());
}
else
{
    // success
    $returnJson['success'] = true;
    $returnJson['msg']     = implode('<br/>', notification::getSuccess());
}

echo json_encode($returnJson);