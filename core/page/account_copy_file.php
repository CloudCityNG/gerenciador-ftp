<?php
/* setup includes */
require_once('../../core/includes/master.inc.php');

// require login
$Auth->requireUser(WEB_ROOT.'/login.'.SITE_CONFIG_PAGE_EXTENSION);

/* load file */
if (isset($_REQUEST['f']))
{
    $file = file::loadByShortUrl($_REQUEST['f']);
    if (!$file)
    {
        // failed lookup of file
        coreFunctions::redirect(WEB_ROOT . '/account_home.' . SITE_CONFIG_PAGE_EXTENSION);
    }

    // make sure the file is active
    if ($file->statusId != 1)
    {
        coreFunctions::redirect(WEB_ROOT . '/error.' . SITE_CONFIG_PAGE_EXTENSION . '?e=' . urlencode(t('failed_to_copy_file',
                                                                                         'There was a problem copying the file, please try again later.')));
    }

    // make sure the file doesn't have a password
    if (strlen($file->accessPassword))
    {
        coreFunctions::redirect(WEB_ROOT . '/error.' . SITE_CONFIG_PAGE_EXTENSION . '?e=' . urlencode(t('failed_to_copy_file',
                                                                                         'There was a problem copying the file, please try again later.')));
    }
    
    // if this user already owns the file, don't copy
    if ($file->userId == $Auth->id)
    {
        coreFunctions::redirect(WEB_ROOT . '/error.' . SITE_CONFIG_PAGE_EXTENSION . '?e=' . urlencode(t('failed_to_copy_file',
                                                                                         'There was a problem copying the file, please try again later.')));
    }

    // attempt to copy the file
    $newFile = $file->duplicateFile();

    // on failure
    if ($newFile == false)
    {
        coreFunctions::redirect(WEB_ROOT . '/error.' . SITE_CONFIG_PAGE_EXTENSION . '?e=' . urlencode(t('failed_to_copy_file',
                                                                                         'There was a problem copying the file, please try again later.')));
    }
    else
    {
        coreFunctions::redirect(WEB_ROOT . '/account_home.' . SITE_CONFIG_PAGE_EXTENSION . '?s=' . t('file_copied',
                                                                                      'File copied into your account - [[[FILE_LINK]]]',
                                                                                      array('FILE_LINK' => $newFile->originalFilename)));
    }
}
else
{
    coreFunctions::redirect(WEB_ROOT . '/account_home.' . SITE_CONFIG_PAGE_EXTENSION);
}
