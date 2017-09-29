<?php

// includes and security
include_once('../_local_auth.inc.php');

if(isset($_REQUEST['pageId']))
{
    $pageId = (int)$_REQUEST['pageId'];
}

// preload user levels
$userLevels = $db->getRows('SELECT id, label FROM user_level WHERE level_type != \'admin\' ORDER BY id');
$userLevelsArr = array();
$userLevelsArr[0] = 'Guest';
foreach($userLevels AS $userLevel)
{
    $userLevelsArr[$userLevel{'id'}] = $userLevel['label'];
}

// get all download pages
$downloadPages = array();
$phpFiles = adminFunctions::getDirectoryList(SITE_TEMPLATES_PATH.'/partial', 'php');
foreach($phpFiles AS $phpFile)
{
    if((substr($phpFile, 0, 15) == '_download_page_') && ($phpFile != '_download_page_captcha.inc.php'))
    {
        $downloadPages[] = $phpFile;
    }
}
sort($downloadPages);

// defaults
$download_page = '';
$user_level_id = 0;
$page_order = 1;
$additional_javascript_code = '';
$additional_settings = '';
$optional_timer = 0;

// is this an edit?
if($pageId)
{
    $language = $db->getRow("SELECT * FROM download_page WHERE id = ".(int)$pageId);
    if($language)
    {
        $download_page = $language['download_page'];
        $user_level_id = (int)$language['user_level_id'];
        $page_order = (int)$language['page_order'];
        $additional_javascript_code = $language['additional_javascript_code'];
        $additional_settings = $language['additional_settings'];
        if(strlen($additional_settings))
        {
            $additional_settings_arr = json_decode($additional_settings, true);
            if(isset($additional_settings_arr['download_wait']))
            {
                $optional_timer = (int)$additional_settings_arr['download_wait'];
            }
        }
    }
}

// prepare result
$result = array();
$result['error'] = false;
$result['msg'] = '';
$result['html'] = 'Could not load the form, please try again later.';

$result['html']  = '<p style="padding-bottom: 4px;">Use the form below to add a new page to the download process for an account type.</p>';
$result['html'] .= '<span id="popupMessageContainer"></span>';
$result['html'] .= '<form id="addTranslationForm" class="form">';
$result['html'] .= '<div class="clearfix alt-highlight">
                        <label>Download Page:</label>
                        <div class="input">
                            <select name="download_page" id="download_page">
                                ';
                                foreach($downloadPages AS $downloadPage)
                                {
                                    $result['html'] .= '<option value="'.$downloadPage.'" ';
                                    if($download_page == $downloadPage)
                                    {
                                        $result['html'] .= ' SELECTED';
                                    }
                                    $result['html'] .= '>'.$downloadPage.'</option>';
                                }
                                $result['html'] .= '
                            </select>
                        </div>
                    </div>';
$result['html'] .= '<div class="clearfix">
                        <label>User Type:</label>
                        <div class="input">
                            <select name="user_level_id" id="user_level_id">
                                ';
                                foreach($userLevelsArr AS $userLevelId => $userLevelLabel)
                                {
                                    $result['html'] .= '<option value="'.$userLevelId.'" ';
                                    if($user_level_id == $userLevelId)
                                    {
                                        $result['html'] .= ' SELECTED';
                                    }
                                    $result['html'] .= '>'.UCWords($userLevelLabel).'</option>';
                                }
                                $result['html'] .= '
                            </select>
                        </div>
                    </div>';
$result['html'] .= '<div class="clearfix alt-highlight">
                        <label>Page Order:</label>
                        <div class="input">
                            <input name="page_order" id="page_order" value="'.$page_order.'" type="text" class="small"/>&nbsp;&nbsp;If lower than existing pages, these will be automatically increased.
                        </div>
                    </div>';
$result['html'] .= '<div class="clearfix">
                        <label>Optional Timer:</label>
                        <div class="input">
                            <input name="optional_timer" id="optional_timer" value="'.$optional_timer.'" type="text" class="small"/>&nbsp;&nbsp;Ensure page supports countdowns. If your page is refreshing try clearing this.
                        </div>
                    </div>';
$result['html'] .= '<div class="clearfix alt-highlight">
                        <label>Optional JS Code:</label>
                        <div class="input">
                            <textarea name="additional_javascript_code" id="additional_javascript_code" class="xxlarge">'.$additional_javascript_code.'</textarea><br/>Can be used for pop-unders or other javascript code.
                        </div>
                    </div>';
$result['html'] .= '<input name="translation_flag_hidden" id="translation_flag_hidden" type="hidden" value="'.adminFunctions::makeSafe($flag).'"/>';
$result['html'] .= '</form>';

echo json_encode($result);
exit;
