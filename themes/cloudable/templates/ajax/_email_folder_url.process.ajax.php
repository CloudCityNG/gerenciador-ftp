<?php

//setup database
$db = Database::getDatabase(true);

// handle submission
if ((int) $_REQUEST['submitme'])
{
    // validation
    $folderId = (int) $_REQUEST['folderId'];
    $shareEmailAddress = substr(strtolower(trim($_REQUEST['shareEmailAddress'])), 0, 255);
    $shareExtraMessage = trim($_REQUEST['shareExtraMessage']);
	$shareEmailFolderUrl = trim($_REQUEST['shareEmailFolderUrl']);
    if (strlen($shareEmailAddress) == 0)
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
        $folder = fileFolder::loadById($folderId);
        if (!$folder)
        {
            notification::setError(t("could_not_load_folder", "There was a problem loading the folder."));
        }
    }
	
	// make sure we have a url
	if((!strlen($shareEmailFolderUrl)) && ($folder))
	{
		$shareEmailFolderUrl = $folder->createUniqueSharingUrl();
	}

    // send the email
    if (!notification::isErrors())
    {
        // prepare variables
        $shareEmailAddress = strip_tags($shareEmailAddress);
        $shareExtraMessage = strip_tags($shareExtraMessage);
        $shareExtraMessage = substr($shareExtraMessage, 0, 2000);
		
		// setup shared by names
		$sharedBy = t('guest', 'Guest');
		$sharedByEmail = '';
		if($Auth->loggedIn())
		{
			$sharedBy = $Auth->getAccountScreenName();
			$sharedByEmail = $Auth->email;
		}
        
        // send the email
        $subject = t('email_folder_url_process_subject', 'Folder shared by [[[SHARED_BY_NAME]]] on [[[SITE_NAME]]]', array('SITE_NAME' => SITE_CONFIG_SITE_NAME, 'SHARED_BY_NAME' => $sharedBy));

        $replacements = array(
            'SITE_NAME' => SITE_CONFIG_SITE_NAME,
            'WEB_ROOT' => WEB_ROOT,
            'SHARED_BY_NAME' => $sharedBy,
            'SHARED_EMAIL_ADDRESS' => $sharedByEmail,
            'EXTRA_MESSAGE' => strlen($shareExtraMessage)?nl2br($shareExtraMessage):t('not_applicable_short', 'n/a'),
            'FOLDER_NAME' => $folder->folderName,
            'FOLDER_URL' => $shareEmailFolderUrl,
        );
        $defaultContent = "[[[SHARED_BY_NAME]]] has shared the following folder with you via <a href='[[[WEB_ROOT]]]'>[[[SITE_NAME]]]</a>:<br/><br/>";
        $defaultContent .= "<strong>Folder Name:</strong> [[[FOLDER_NAME]]]<br/>";
        $defaultContent .= "<strong>View:</strong> [[[FOLDER_URL]]]<br/>";
        $defaultContent .= "<strong>From:</strong> [[[SHARED_BY_NAME]]] [[[SHARED_EMAIL_ADDRESS]]]<br/>";
        $defaultContent .= "<strong>Message:</strong><br/>[[[EXTRA_MESSAGE]]]<br/><br/>";
        $defaultContent .= "Feel free to contact us if you have any difficulties viewing the folder.<br/><br/>";
        $defaultContent .= "Regards,<br/>";
        $defaultContent .= "[[[SITE_NAME]]] Admin";
        $htmlMsg = t('email_folder_url_process_content', $defaultContent, $replacements);

        coreFunctions::sendHtmlEmail($shareEmailAddress, $subject, $htmlMsg, SITE_CONFIG_DEFAULT_EMAIL_ADDRESS_FROM, strip_tags(str_replace("<br/>", "\n", $htmlMsg)));
        notification::setSuccess(t("email_folder_url_process_folder_send_via_email_to", "Folder shared via email to [[[RECIPIENT_EMAIL_ADDRESS]]]", array('RECIPIENT_EMAIL_ADDRESS' => $shareEmailAddress)));
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