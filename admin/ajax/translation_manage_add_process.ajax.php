<?php

// includes and security
include_once('../_local_auth.inc.php');

$translation_name = trim($_REQUEST['translation_name']);
$translation_flag = trim($_REQUEST['translation_flag']);
$language_code 	  = trim($_REQUEST['language_code']);
$translation_flag = str_replace(array(".png", ".jpg", ".gif"), "", $translation_flag);
if (isset($_REQUEST['languageId']))
{
    $languageId = (int) $_REQUEST['languageId'];
}
$direction = trim($_REQUEST['direction']);

// prepare result
$result          = array();
$result['error'] = false;
$result['msg']   = '';

if (strlen($translation_name) == 0)
{
    $result['error'] = true;
    $result['msg']   = adminFunctions::t("enter_the_language_name", "Please enter the language name.");
}
elseif (strlen($language_code) == 0)
{
    $result['error'] = true;
    $result['msg']   = adminFunctions::t("select_the_language_code", "Please select the language code.");
}
elseif (_CONFIG_DEMO_MODE == true)
{
    $result['error'] = true;
    $result['msg']   = adminFunctions::t("no_changes_in_demo_mode");
}
else
{
    if ($languageId)
    {
        $row = $db->getRow('SELECT id FROM language WHERE languageName = ' . $db->quote($translation_name) . ' AND id!=' . $languageId);
    }
    else
    {
        $row = $db->getRow('SELECT id FROM language WHERE languageName = ' . $db->quote($translation_name));
    }
    if (is_array($row))
    {
        $result['error'] = true;
        $result['msg']   = adminFunctions::t("language_already_exists", "A language with that name already exists in the database.");
    }
    else
    {
        if ($languageId)
        {
            $rs = $db->query('UPDATE language SET languageName = :languageName, flag = :flag, direction = :direction, language_code = :language_code WHERE id = :id', array('languageName' => $translation_name, 'flag'         => $translation_flag, 'direction'    => $direction, 'language_code'    => $language_code, 'id'           => $languageId));
            if (!$rs)
            {
                $result['error'] = true;
                $result['msg']   = adminFunctions::t("error_problem_language_record_update", "There was a problem updating the language, please try again.");
            }
            else
            {
                $result['error'] = false;
                $result['msg']   = 'Language \'' . $translation_name . '\' has been updated.';
            }
        }
        else
        {
            // add the new language
            $dbInsert               	= new DBObject("language", array("languageName", "isLocked", "flag", "direction", "language_code"));
            $dbInsert->languageName 	= $translation_name;
            $dbInsert->isLocked     	= 0;
            $dbInsert->flag         	= $translation_flag;
            $dbInsert->direction    	= $direction;
			$dbInsert->language_code    = $language_code;
            $rs                     	= $dbInsert->insert();
            if (!$rs)
            {
                $result['error'] = true;
                $result['msg']   = adminFunctions::t("error_problem_language_record", "There was a problem adding the language, please try again.");
            }
            else
            {
                // make sure we have all content records populated
                $getMissingRows = $db->getRows("SELECT id, languageKey, defaultContent FROM language_key WHERE id NOT IN (SELECT languageKeyId FROM language_content WHERE languageId = " . (int) $rs . ")");
                if (COUNT($getMissingRows))
                {
                    foreach ($getMissingRows AS $getMissingRow)
                    {
                        $dbInsert                = new DBObject("language_content", array("languageKeyId", "languageId", "content"));
                        $dbInsert->languageKeyId = $getMissingRow['id'];
                        $dbInsert->languageId    = (int) $rs;
                        $dbInsert->content       = $getMissingRow['defaultContent'];
                        $dbInsert->insert();
                    }
                }

                $result['error'] = false;
                $result['msg']   = 'Language \'' . $translation_name . '\' has been added.';
            }
        }
    }
}

echo json_encode($result);
exit;
