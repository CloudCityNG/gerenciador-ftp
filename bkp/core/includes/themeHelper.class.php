<?php

class themeHelper
{
	public static $themeConfigCache = null;
	
    static function themeEnabled($themeKey = '')
    {
        if (strlen($themeKey) == 0)
        {
            return false;
        }

        if (self::$themeConfigCache == null)
        {
            self::$themeConfigCache = self::loadThemeConfigurationFiles();
        }

        if (!isset(self::$themeConfigCache[$themeKey]))
        {
            return false;
        }

		$siteTheme = SITE_CONFIG_SITE_THEME;
		if((isset($_SESSION['_current_theme'])) && (strlen($_SESSION['_current_theme'])))
		{
			$siteTheme = $_SESSION['_current_theme'];
		}

        if ($siteTheme != $themeKey)
        {
            return false;
        }

        return true;
    }
	
	static function clearCachedThemeSettings()
	{
		self::$themeConfigCache = null;
	}
	
	static function getCurrentThemeKey()
	{
		$siteTheme = SITE_CONFIG_SITE_THEME;
		if((isset($_SESSION['_current_theme'])) && (strlen($_SESSION['_current_theme'])))
		{
			$siteTheme = $_SESSION['_current_theme'];
		}
		
		return $siteTheme;
	}

    static function themeSpecificConfiguration($themeKey = '')
    {
        if (self::themeEnabled($themeKey) == false)
        {
            return false;
        }

        return self::$themeConfigCache[$themeKey];
    }

    static function loadThemeConfigurationFiles($updateCache = false)
    {
		// try to load from cache
		if($updateCache == false)
		{
			if(strlen(SITE_CONFIG_SYSTEM_THEME_CONFIG_CACHE))
			{
				self::$themeConfigCache = @json_decode(SITE_CONFIG_SYSTEM_THEME_CONFIG_CACHE, true);
				if(COUNT(self::$themeConfigCache))
				{
					return self::$themeConfigCache;
				}
			}
			else
			{
				$updateCache = true;
			}
		}
		
        $rs = array();

        // get active themes from the db
        $db = Database::getDatabase(true);
        $themes = $db->getRows('SELECT * FROM theme');
		
		// allow for first run
		if(!COUNT($themes))
		{
			self::registerThemes();
			$themes = $db->getRows('SELECT * FROM theme');
		}
		
        if ($themes)
        {
            // use output buffering to ensure no random white space is added to the core
            ob_start();
            foreach ($themes AS $theme)
            {
                $settingsPath = SITE_THEME_DIRECTORY_ROOT . $theme['folder_name'] . '/_theme_config.inc.php';
                if (file_exists($settingsPath))
                {
                    include($settingsPath);
                    $rs[$theme{'folder_name'}] = array();
                    $rs[$theme{'folder_name'}]['data'] = $theme;
                    $rs[$theme{'folder_name'}]['config'] = $themeConfig;
                }
            }
            // delete output buffer
            ob_end_clean();
        }
		
		// save cache
		if($updateCache == true)
		{
			self::updateThemeConfigCache($rs);
		}

        return $rs;
    }
	
	static function updateThemeConfigCache($dataArr)
	{
		// setup database
		$db = Database::getDatabase();
		
		// update cache
		$db->query('UPDATE site_config SET config_value='.$db->quote(json_encode($dataArr)).' WHERE config_key = \'system_theme_config_cache\' LIMIT 1');
		self::$themeConfigCache = $dataArr;
	}

    static function getInstance($themeKey = null)
    {
		if($themeKey == null)
		{
			$themeKey = self::getCurrentThemeKey();
		}
		
		if (!isset(self::$themeConfigCache))
        {
            self::$themeConfigCache = self::loadThemeConfigurationFiles();
        }

        $theme = self::$themeConfigCache[$themeKey];

        // create theme instance
        $classPath = SITE_THEME_DIRECTORY_ROOT . $theme['data']['folder_name'] . '/theme' . UCFirst($theme['data']['folder_name']) . '.class.php';
        $themeClassName = 'Theme' . UCFirst($theme['data']['folder_name']);
        include_once($classPath);

        return new $themeClassName();
    }
    
    static function getLoadedInstance()
    {
        return self::getInstance(self::getCurrentThemeKey());
    }

    static function getThemeAdminNav()
    {
        // add any theme navigation
        $html = '';
		$currentThemeKey = self::getCurrentThemeKey();
        $themeConfig = self::themeSpecificConfiguration($currentThemeKey);
		$totalItems = 0;
		if ((self::themeEnabled($themeConfig['data']['folder_name']) == 1) && (isset($themeConfig['config']['admin_settings']['top_nav'])))
		{
			foreach ($themeConfig['config']['admin_settings']['top_nav'] AS $navItem)
			{
				$html .= '<li';
				if (ADMIN_SELECTED_PAGE == $navItem[0]['link_key'])
					$html .= ' class="active"';
				$html .= '><a href="' . ($navItem[0]['link_url'] != '#' ? (SITE_THEME_WEB_ROOT . $themeConfig['config']['folder_name'] . '/' . $navItem[0]['link_url']) : ($navItem[0]['link_url'])) . '"><span>' . adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t($navItem[0]['link_text'], $navItem[0]['link_text'])))) . '</span></a>';

				if (COUNT($navItem > 1))
				{
					$html .= '<ul>';
					unset($navItem[0]);
					foreach ($navItem AS $navSubItem)
					{
						$html .= '<li><a href="' . SITE_THEME_WEB_ROOT . $themeConfig['config']['folder_name'] . '/' . $navSubItem['link_url'] . '"><span>' . adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t($navSubItem['link_text'], $navSubItem['link_text'])))) . '</span></a>';
						
						// add any sub items
						if(isset($navSubItem['sub_nav']))
						{
							$html .= '<ul>';
							foreach ($navSubItem['sub_nav'] AS $subNavItem)
							{
								$html .= '<li><a href="' . SITE_THEME_WEB_ROOT . $themeConfig['config']['folder_name'] . '/' . $subNavItem['link_url'] . '"><span>' . adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t($subNavItem['link_text'], $subNavItem['link_text'])))) . '</span></a></li>';
							}
							$html .= '</ul>';
						}
						
						$html .= '</li>';
					}
					$html .= '</ul>';
				}
				$html .= '</li>';
				
				$totalItems++;
			}
		}
		
		if($totalItems >= 4)
		{
			$html = '<li><a href="#"><span>More...</span><ul>'.$html.'</ul></li>';
		}

        return $html;
    }

    static function getThemeAdminNavDropdown()
    {
        // add any theme navigation
        $html = '';
        $currentThemeKey = self::getCurrentThemeKey();
        $themeConfig = self::themeSpecificConfiguration($currentThemeKey);
		if ((self::themeEnabled($themeConfig['data']['folder_name']) == 1) && (isset($themeConfig['config']['admin_settings']['top_nav'])))
		{
			foreach ($themeConfig['config']['admin_settings']['top_nav'] AS $navItem)
			{
				$html .= '<optgroup label="' . adminFunctions::makeSafe($navItem[0]['link_text']) . '">';
				if (COUNT($navItem > 1))
				{
					unset($navItem[0]);
					foreach ($navItem AS $navSubItem)
					{
						$html .= '<option';
						if((isset($navSubItem['link_key'])) && defined('ADMIN_SELECTED_SUB_PAGE'))
						{
							if (ADMIN_SELECTED_SUB_PAGE == $navSubItem['link_key'])
							{
								$html .= ' selected';
							}
						}
						$html .= ' value="' . SITE_THEME_WEB_ROOT . $themeConfig['config']['folder_name'] . '/' . $navSubItem['link_url'] . '">' . htmlentities(UCWords(strtolower(adminFunctions::t($navSubItem['link_text'], $navSubItem['link_text'])))) . '</option>';
					}
				}
				$html .= '</optgroup>';
			}
		}

        return $html;
    }
	
	static function getConfigValue($configName)
	{
		$currentTheme = self::getCurrentThemeKey();
		$currentThemeConfig = self::themeSpecificConfiguration($currentTheme);
		if($currentThemeConfig)
		{
			if(strlen($currentThemeConfig['data']['theme_settings']))
			{
				$themeSettingsArr = json_decode($currentThemeConfig['data']['theme_settings'], true);
				if(is_array($themeSettingsArr))
				{
					return $themeSettingsArr[$configName];
				}
			}
		}
		
		return false;
	}
	
	static function setConfigValue($configName, $configValue)
	{
		$currentTheme = self::getCurrentThemeKey();
		$currentThemeConfig = self::themeSpecificConfiguration($currentTheme);
		if($currentThemeConfig)
		{
			if(strlen($currentThemeConfig['data']['theme_settings']))
			{
				$themeSettingsArr = json_decode($currentThemeConfig['data']['theme_settings'], true);
				if(is_array($themeSettingsArr))
				{
					$themeSettingsArr[$configName] = $configValue;
				}
				
				// save
				$db = Database::getDatabase();
				$db->query('UPDATE theme SET theme_settings = '.$db->quote(json_encode($themeSettingsArr)).' WHERE folder_name = '.$db->quote($currentTheme).' LIMIT 1');

				// clear cache
				themeHelper::clearCachedThemeSettings();
			}
		}
		
		return false;
	}
    
    static function getCurrentProductType()
    {
        // get produce type based on current theme
        $thisTheme = self::getCurrentThemeKey();
        $themeData = self::themeSpecificConfiguration($thisTheme);
        if($themeData)
        {
            if((isset($themeData['config'])) && (isset($themeData['config']['product'])))
            {
                if(strlen($themeData['config']['product']))
                {
                    return $themeData['config']['product'];
                }
            }
        }
        
        return 'file_hosting';
    }
	
	static function getCurrentProductName()
    {
        // get produce type based on current theme
        $thisTheme = self::getCurrentThemeKey();
        $themeData = self::themeSpecificConfiguration($thisTheme);
        if($themeData)
        {
            if((isset($themeData['config'])) && (isset($themeData['config']['product_name'])))
            {
                if(strlen($themeData['config']['product_name']))
                {
                    return $themeData['config']['product_name'];
                }
            }
        }
        
        return 'YetiShare';
    }
	
	static function getCurrentProductUrl()
    {
        // get produce type based on current theme
        $thisTheme = self::getCurrentThemeKey();
        $themeData = self::themeSpecificConfiguration($thisTheme);
        if($themeData)
        {
            if((isset($themeData['config'])) && (isset($themeData['config']['product_url'])))
            {
                if(strlen($themeData['config']['product_url']))
                {
                    return $themeData['config']['product_url'];
                }
            }
        }
        
        return 'https://yetishare.com';
    }
	
	static function getAdminThemeCss()
	{
		$thisTheme = self::getCurrentThemeKey();
        $cssPath = SITE_THEME_DIRECTORY_ROOT.$thisTheme.'/admin/assets/css/styles.css';
        if(file_exists($cssPath))
        {
            return SITE_THEME_WEB_ROOT.$thisTheme.'/admin/assets/css/styles.css';
        }
		
		return false;
	}
	
	public static function registerThemes()
    {
        // get database connection
        $db = Database::getDatabase();

        // scan plugin directory and make sure they are all listed within the database
        $themeDirectory = SITE_THEME_DIRECTORY_ROOT;
        $directories    = coreFunctions::getDirectoryList($themeDirectory);
        if (COUNT($directories))
        {
            foreach ($directories AS $directory)
            {
                // check the database to see if it already exists
                $found = $db->getValue("SELECT id FROM theme WHERE folder_name = " . $db->quote($directory));
                if ($found)
                {
                    continue;
                }

                // not found in the db, we probably need to add it
                $themePath = $themeDirectory . $directory . '/';

                // tidy directory for class
                $directory = strtolower($directory);
                $directory = validation::removeInvalidCharacters($directory);

                $themeClassFile = $themePath . 'theme' . UCFirst(strtolower($directory)) . '.class.php';
                $themeClassName = 'Theme' . UCFirst($directory);

                // make sure we have the main class file
                if (!file_exists($themeClassFile))
                {
                    continue;
                }

                try
                {
                    // try to create an instance of the class
                    include_once($themeClassFile);
                    if (!class_exists($themeClassName))
                    {
                        continue;
                    }

                    $instance = new $themeClassName();
                    if (!$instance)
                    {
                        continue;
                    }

                    // get plugin details
                    $themeDetails = $instance->getThemeDetails();

                    // insert new plugin into db
                    if ($themeDetails)
                    {
                        // make sure we have the http at the start of the website
                        $website = $themeDetails['author_website'];
                        if (strlen($website))
                        {
                            if (substr($website, 0, 4) != 'http')
                            {
                                $website = 'http://' . $website;
                            }
                        }

                        // if current theme set as active
                        $isActive = 0;
                        if ($themeDetails['folder_name'] == SITE_CONFIG_SITE_THEME)
                        {
                            $isActive = 1;
                        }

                        // add to the database
                        $dbInsert                    = new DBObject("theme", array("theme_name", "folder_name", "theme_description", "author_name", "author_website", "is_installed"));
                        $dbInsert->theme_name        = $themeDetails['theme_name'];
                        $dbInsert->folder_name       = $themeDetails['folder_name'];
                        $dbInsert->theme_description = $themeDetails['theme_description'];
                        $dbInsert->author_name       = $themeDetails['author_name'];
                        $dbInsert->author_website    = $website;
                        $dbInsert->is_installed      = $isActive;
                        $dbInsert->insert();
                    }
                }
                catch (Exception $e)
                {
                    continue;
                }
            }
        }

        return true;
    }
}
