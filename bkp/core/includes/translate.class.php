<?php

/**
 * main translate class
 */
class translate
{
    private static $setupTranslations = false;
	public static $translations = array();
    
    // setup the initial translation constants
    static function setUpTranslationConstants()
    {
        if(self::$setupTranslations == true)
        {
            return true;
        }
        
        if (!defined("SITE_CONFIG_SITE_LANGUAGE"))
        {
            define("SITE_CONFIG_SITE_LANGUAGE", "English (en)");
        }

		$db = Database::getDatabase();
        if (isset($_SESSION['_t']))
		{
			$languageId = (int)self::getCurrentLanguageId();
		}
		else
		{
			$languageId = (int)$db->getValue("SELECT id FROM language WHERE languageName = " . $db->quote(SITE_CONFIG_SITE_LANGUAGE));
		}
		
        if (!(int) $languageId)
        {
            return false;
        }

        translate::updateAllLanguageContent($languageId);

        /* load in the content */
        $rows = $db->getRows("SELECT language_key.languageKey, language_content.content FROM language_content LEFT JOIN language_key ON language_content.languageKeyId = language_key.id WHERE language_content.languageId = " . (int) $languageId.' AND language_key.languageKey IS NOT NULL');
        if (COUNT($rows))
        {
            foreach ($rows AS $row)
            {
                $constantName = strtoupper(trim($row['languageKey']));
                if(!isset(self::$translations[$constantName]))
                {
                    self::$translations[$constantName] = $row['content'];
                }
            }
        }

        self::$setupTranslations = true;
    }

    /* translation function for JS */
    static function generateJSLanguageCode()
	{
		self::setUpTranslationConstants();

		/* setup js */
		$js   = array();
		$js[] = "/* translation function */";
		$js[] = "function t(key){ ";
		$js[] = "l = {";

		/* load in the content */
		$ljs = array();
		foreach (self::$translations AS $k=>$row)
		{
			$itemKey = strtolower(str_replace(array("\r", "\n"), "", $k));
			if(strlen($itemKey) == 0)
			{
				continue;
			}
			$ljs[] = "\"" . addslashes($itemKey) . "\":\"" . addslashes(str_replace(array("\r", "\n"), "", $row)) . "\"";
		}
		$js[] = implode(", ", $ljs);
		$js[] = "};";

		$js[] = "return l[key.toLowerCase()];";
		$js[] = "}";
		return implode("\n", $js);
	}

    static function updateAllLanguageContent($languageId)
    {
        $db             = Database::getDatabase();
        /* make sure we have all content records populated */
        $getMissingRows = $db->getRows("SELECT id, languageKey, defaultContent FROM language_key WHERE NOT EXISTS (SELECT 1 FROM language_content WHERE languageId = " . (int) $languageId . ")");
        if (is_array($getMissingRows) && COUNT($getMissingRows))
        {
            foreach ($getMissingRows AS $getMissingRow)
            {
                $dbInsert                = new DBObject("language_content", array("languageKeyId", "languageId", "content"));
                $dbInsert->languageKeyId = $getMissingRow['id'];
                $dbInsert->languageId    = (int) $languageId;
                $dbInsert->content       = $getMissingRow['defaultContent'];
                $dbInsert->insert();
            }
        }
    }

    static function getTranslation($key, $defaultContent = null, $isAdminArea = 0, $replacements = array(), $overwriteDefault = false)
    {
        /* are we in language debug mode */
        if (SITE_CONFIG_LANGUAGE_SHOW_KEY == "key")
        {
            return $key;
        }
        
        // setup translations
        self::setUpTranslationConstants();

        /* return the language translation if we can find it */
        $constantName = strtoupper(trim($key));
        if (!isset(self::$translations[$constantName]) || ($overwriteDefault == true))
        {
            if ($defaultContent !== null)
            {
				$db = Database::getDatabase();
                if (isset($_SESSION['_t']))
                {
					$languageId = (int)self::getCurrentLanguageId();
                }
				else
				{
					$languageId = (int)$db->getValue("SELECT id FROM language WHERE languageName = " . $db->quote(SITE_CONFIG_SITE_LANGUAGE));
				}
				
                if (!(int) $languageId)
                {
                    return false;
                }

				// clear default value
				$db->query('DELETE FROM language_content WHERE languageKeyId IN (SELECT id FROM language_key WHERE languageKey='.$db->quote($key).')');
				$db->query('DELETE FROM language_key WHERE languageKey='.$db->quote($key).' LIMIT 1');
				
                // insert default key value
                $dbInsert                 = new DBObject("language_key", array("languageKey", "defaultContent", "isAdminArea"));
                $dbInsert->languageKey    = $key;
                $dbInsert->defaultContent = $defaultContent;
                $dbInsert->isAdminArea    = (int) $isAdminArea;
                $dbInsert->insert();
				
				$dbInsert2                = new DBObject("language_content", array("languageKeyId", "languageId", "content"));
                $dbInsert2->languageKeyId = $dbInsert->id;
                $dbInsert2->languageId    = (int) $languageId;
                $dbInsert2->content       = $defaultContent;
                $dbInsert2->insert();

                // set constant
                self::$translations[strtoupper($key)] = $defaultContent;

                // do replacements
                if (COUNT($replacements))
                {
                    foreach ($replacements AS $k => $replacement)
                    {
                        $defaultContent = str_replace('[[[' . strtoupper($k) . ']]]', $replacement, $defaultContent);
                    }
                }

                if (SITE_CONFIG_LANGUAGE_SHOW_KEY == "key title text")
                {
                    $defaultContent = self::addTitleText($defaultContent, strtoupper($key));
                }
                
                return $defaultContent;
            }
            
            return "<font style='color:red;'>SITE ERROR: MISSING TRANSLATION *** <strong>" . $key . "</strong> ***</font>";
        }

        // do replacements
        $text = self::$translations[$constantName];
        if (COUNT($replacements))
        {
            foreach ($replacements AS $k => $replacement)
            {
                $text = str_replace('[[[' . strtoupper($k) . ']]]', $replacement, $text);
            }
        }

        if (SITE_CONFIG_LANGUAGE_SHOW_KEY == "key title text")
        {
            $text = self::addTitleText($text, strtoupper($key));
        }

        return $text;
    }
    
    static function addTitleText($baseText, $titleText)
    {
        return '<span title="'.validation::safeOutputToScreen($titleText).'">'.$baseText.'</span>';
    }
    
    static function extractTranslationsFromText($str)
    {
        $rs = array();
        $patterns = array('/\.\s*t\s*\(\s*\'(.*?)\'\s*\s*\)/i', "/\.\s*t\s*\(\s*\\\"(.*?)\\\"\s*\s*\)/i", '/\.\s*t\s*\(\s*\'(.*?)\'\s*\s*\,/i', "/\.\s*t\s*\(\s*\\\"(.*?)\\\"\s*\s*\,/i", '/\(\s*t\s*\(\s*\'(.*?)\'\s*\s*\)/i', "/\(\s*t\s*\(\s*\\\"(.*?)\\\"\s*\s*\)/i", '/\(\s*t\s*\(\s*\'(.*?)\'\s*\s*\,/i', "/\(\s*t\s*\(\s*\\\"(.*?)\\\"\s*\s*\,/i", '/ t\s*\(\s*\'(.*?)\'\s*\s*\)/i', "/ t\s*\(\s*\\\"(.*?)\\\"\s*\s*\)/i", '/ t\s*\(\s*\'(.*?)\'\s*\s*\,/i', "/ t\s*\(\s*\\\"(.*?)\\\"\s*\s*\,/i", '/\:\:t\s*\(\s*\'(.*?)\'\s*\s*\)/i', "/\:\:t\s*\(\s*\\\"(.*?)\\\"\s*\s*\)/i", '/\:\:t\s*\(\s*\'(.*?)\'\s*\s*\,/i', "/\:\:t\s*\(\s*\\\"(.*?)\\\"\s*\s*\,/i");
        foreach($patterns AS $pattern)
        {
            preg_match_all($pattern, $str, $matches);
            if(COUNT($matches))
            {
                $funcCalls = $matches[1];
                if(COUNT($funcCalls))
                {
                    foreach($funcCalls AS $funcCall)
                    {
                        $funcCall = str_replace(array('\', \'', '","', '\',\'', '\',"', '",\'', '\', "', '", \''), '", "', $funcCall);
                        $funcCall = str_replace(array('\', $', '", $', '\',$', '",$'), '", "$', $funcCall);
                        $funcCall = str_replace(array('\', array', '", array'), '", "array', $funcCall);

                        $tmpExp = explode('", "', $funcCall);
                        
                        $defaultContent = $tmpExp[1];
                        if(substr($defaultContent, 0, 1) == '$')
                        {
                            continue;
                        }
                        if(strpos($tmpExp{0}, '$') !== false)
                        {
                            continue;
                        }
                        
                        // if not set by previous pattern
                        if((!isset($rs[$tmpExp{0}])) && (strlen($defaultContent) > 0))
                        {
                            $rs[$tmpExp{0}] = (string)$defaultContent;
                        }
                    }
                }
            }
        }

        return $rs;
    }
    
    static function rebuildTranslationsFromCode()
    {
        // get database
        $db         = Database::getDatabase();
        
        // prepare total file list
        $filesToCheck = array();

        // get all files with translations, start with the base
        $dirFiles = adminFunctions::getDirectoryList(SITE_TEMPLATES_PATH, null, true);
        if($dirFiles)
        {
            foreach($dirFiles AS $dirFile)
            {
                if(is_file($dirFile))
                {
                    $filesToCheck[] = $dirFile;
                }
            }
        }

        // add sub folders
        $subFolders = array('api', 'core/includes', 'core/page', PLUGIN_DIRECTORY_NAME);
        foreach($subFolders AS $subFolder)
        {
            $dirFiles = adminFunctions::getDirectoryList(DOC_ROOT.'/'.$subFolder, null, true);
            $filesToCheck = array_merge($filesToCheck, $dirFiles);
        }

        // only keep .php files
        $total = COUNT($filesToCheck);
        for($i=0; $i<$total; $i++)
        {
            $fileName = str_replace(DOC_ROOT, '', $filesToCheck[$i]);
            $fileName = strtolower($fileName);
            if((strpos($fileName, '.php') == false) && (strpos($fileName, '.html') == false))
            {
                unset($filesToCheck[$i]);
            }
        }

        // loop files and pick up any translations
        $translations = array();
        foreach($filesToCheck AS $filePath)
        {
            // get contents of file
            $fileContents = file_get_contents($filePath);
            $rs = translate::extractTranslationsFromText($fileContents);
            if(COUNT($rs))
            {
                $shortFileName = str_replace(DOC_ROOT.'/', '', $filePath);
                foreach($rs AS $k=>$item)
                {
                    if(!isset($translations[$k]))
                    {
                        $translations[$k] = array('default_content'=>$item, 'file_source'=>array($shortFileName));
                    }
                    else
                    {
                        $translations[$k]['file_source'] = $shortFileName;
                    }
                }
            }
        }

        // loop translations and populate default translation content
        translate::setUpTranslationConstants();
        $foundTotal = 0;
        $addedTotal = 0;
        $db->query('UPDATE language_key SET foundOnScan = 0');
        foreach($translations AS $translationKey => $translation)
        {
            /* return the language translation if we can find it */
            $constantName = strtoupper($translationKey);
            if (!isset(self::$translations[$constantName]))
            {
                if (strlen($translation['default_content']))
                {
                    // figure out if admin
                    $isAdminArea = 1;
                    foreach($translation['file_source'] AS $fileSource)
                    {
                        if(strpos($fileSource, ADMIN_FOLDER_NAME) === false)
                        {
                            $isAdminArea = 0;
                        }
                    }

                    // insert default key value
                    $dbInsert                 = new DBObject("language_key", array("languageKey", "defaultContent", "isAdminArea", "foundOnScan"));
                    $dbInsert->languageKey    = $translationKey;
                    $dbInsert->defaultContent = $translation['default_content'];
                    $dbInsert->isAdminArea    = (int) $isAdminArea;
                    $dbInsert->foundOnScan    = 1;
                    $dbInsert->insert();

                    $addedTotal++;

                    // set constant
                    self::$translations[strtoupper($translationKey)] = $translation['default_content'];
                }
            }
            else
            {
                $db->query('UPDATE language_key SET foundOnScan = 1 WHERE languageKey='.$db->quote($translationKey).' LIMIT 1');
            }

            $foundTotal++;
        }

        return array('foundTotal' => $foundTotal, 'addedTotal' => $addedTotal);
    }
	
	static function getCurrentLanguageId()
    {
		if(!defined('SITE_CURRENT_LANGUAGE_ID'))
		{
			// get database
			$db = Database::getDatabase();
			$languageId = (int)$db->getValue("SELECT id FROM language WHERE isActive = 1 AND languageName = " . $db->quote($_SESSION['_t']) . " LIMIT 1");
			define('SITE_CURRENT_LANGUAGE_ID', $languageId);
		}
		
		return (int)SITE_CURRENT_LANGUAGE_ID;
	}
}
