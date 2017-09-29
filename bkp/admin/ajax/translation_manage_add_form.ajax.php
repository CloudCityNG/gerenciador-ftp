<?php

// includes and security
include_once('../_local_auth.inc.php');

if(isset($_REQUEST['languageId']))
{
    $languageId = (int)$_REQUEST['languageId'];
}

// defaults
$translation_name = '';
$translation_flag = '';
$direction = 'LTR';
$language_code = '';

// is this an edit?
if($languageId)
{
    $language = $db->getRow("SELECT * FROM language WHERE id = ".(int)$languageId);
    if($language)
    {
        $translation_name = $language['languageName'];
        $translation_flag = $language['flag'];
        $direction = $language['direction'];
		$language_code = $language['language_code'];
    }
}

// load all flag icons
$flags = adminFunctions::getDirectoryList(ADMIN_ROOT.'/assets/images/icons/flags/', 'png');
sort($flags);

// load all language codes
$languageCodes = googleTranslate::getAvailableLanguages();

// prepare result
$result = array();
$result['error'] = false;
$result['msg'] = '';
$result['html'] = 'Could not load the form, please try again later.';

$result['html']  = '<p style="padding-bottom: 4px;">Use the form below to add a new language. Once it\'s created, you can edit any of the text items into your preferred language.</p>';
$result['html'] .= '<span id="popupMessageContainer"></span>';
$result['html'] .= '<form id="addTranslationForm" class="form">';
$result['html'] .= '<div class="clearfix alt-highlight">
                        <label>Language Name:</label>
                        <div class="input">
                            <input name="translation_name" id="translation_name" type="text" value="'.adminFunctions::makeSafe($translation_name).'" class="xlarge"/>
                        </div>
                    </div>';
$result['html'] .= '<div class="clearfix">
                        <label>Text Direction:</label>
                        <div class="input">
                            <select name="direction" id="direction">
                                <option value="LTR"'.($direction=='LTR'?' SELECTED':'').'>Left To Right (LTR)</option>
                                <option value="RTL"'.($direction=='RTL'?' SELECTED':'').'>Right To Left (RTL)</option>
                            </select><br/><br/>
                            Note: This is entirely dependant on the theme used, this setting just provides the theme<br/>
                            with a request to show text in this direction. If the theme doesn\'t support this setting,<br/>
                            it will be ignored.
                        </div>
                    </div>';
$result['html'] .= '<div class="clearfix alt-highlight">
                        <label>Language Flag:</label>
                        <div class="input">
                            <select name="translation_flag" id="translation_flag">
                                ';
                                foreach($flags AS $flag)
                                {
                                    $result['html'] .= '<option value="'.$flag.'" data-imagesrc="assets/images/icons/flags/'.$flag.'"';
                                    if($translation_flag.'.png' == $flag)
                                    {
                                        $result['html'] .= ' SELECTED';
                                    }
                                    $result['html'] .= '>'.$flag.'</option>';
                                }
                                $result['html'] .= '
                            </select>
                        </div>
                    </div>';
$result['html'] .= '<div class="clearfix">
                        <label>Language Code:</label>
                        <div class="input">
                            <select name="language_code" id="language_code">
								<option value="">- select -</option>
                                ';
                                foreach($languageCodes AS $k=>$languageCode)
                                {
                                    $result['html'] .= '<option value="'.$k.'""';
                                    if($language_code == $k)
                                    {
                                        $result['html'] .= ' SELECTED';
                                    }
                                    $result['html'] .= '>'.strtoupper(adminFunctions::makeSafe($k)).' ('.adminFunctions::makeSafe($languageCode).')</option>';
                                }
                                $result['html'] .= '
                            </select>
                        </div>
                    </div>';
$result['html'] .= '<input name="translation_flag_hidden" id="translation_flag_hidden" type="hidden" value="'.adminFunctions::makeSafe($flag).'"/>';
$result['html'] .= '</form>';

echo json_encode($result);
exit;
