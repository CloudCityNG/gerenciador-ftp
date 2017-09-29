<?php

class pluginHelper
{

    private static $cssFiles = array();
    private static $jsFiles = array();
	public static $pluginConfigCache = null;

    static function pluginEnabled($pluginKey = '')
    {
        if ((_CONFIG_DEMO_MODE == true) && (pluginHelper::currentlyInPluginDemoMode() == false))
        {
            return false;
        }

        if (strlen($pluginKey) == 0)
        {
            return false;
        }

        if (self::$pluginConfigCache == null)
        {
            self::$pluginConfigCache = self::loadPluginConfigurationFiles();
        }

        if (!isset(self::$pluginConfigCache[$pluginKey]))
        {
            return false;
        }

        if (((int) self::$pluginConfigCache[$pluginKey]['data']['plugin_enabled'] == 0) || ((int) self::$pluginConfigCache[$pluginKey]['data']['is_installed'] == 0))
        {
            return false;
        }

        return true;
    }
	
	static function reloadSessionPluginConfig()
	{
		self::loadPluginConfigurationFiles(true);
	}

    static function pluginSpecificConfiguration($pluginKey = '')
    {
        if (self::pluginEnabled($pluginKey) == false)
        {
            return false;
        }

        return self::$pluginConfigCache[$pluginKey];
    }

    static function getPluginConfiguration()
    {
        return self::$pluginConfigCache;
    }

    static function loadPluginConfigurationFiles($updateCache = false)
    {
		// try to load from cache
		if($updateCache == false)
		{
			if(strlen(SITE_CONFIG_SYSTEM_PLUGIN_CONFIG_CACHE))
			{
				self::$pluginConfigCache = @json_decode(SITE_CONFIG_SYSTEM_PLUGIN_CONFIG_CACHE, true);
				if(COUNT(self::$pluginConfigCache))
				{
					return self::$pluginConfigCache;
				}
			}
			else
			{
				$updateCache = true;
			}
		}
		
        $rs = array();

        // get active plugins from the db
        $db = Database::getDatabase(true);
        $plugins = $db->getRows('SELECT * FROM plugin WHERE is_installed = 1 ORDER BY load_order ASC');
        if ($plugins)
        {
            // use output buffering to ensure no random white space is added to the core
            ob_start();
            foreach ($plugins AS $plugin)
            {
                $settingsPath = PLUGIN_DIRECTORY_ROOT . $plugin['folder_name'] . '/_plugin_config.inc.php';
                if (file_exists($settingsPath))
                {
                    include($settingsPath);
                    $rs[$plugin{'folder_name'}] = array();
                    $rs[$plugin{'folder_name'}]['data'] = $plugin;
                    $rs[$plugin{'folder_name'}]['config'] = $pluginConfig;
                }
            }
            // delete output buffer
            ob_end_clean();
        }
		
		// save cache
		if($updateCache == true)
		{
			self::updatePluginConfigCache($rs);
		}

        return $rs;
    }
	
	static function updatePluginConfigCache($dataArr)
	{
		// setup database
		$db = Database::getDatabase();
		
		// update cache
		$db->query('UPDATE site_config SET config_value='.$db->quote(json_encode($dataArr)).' WHERE config_key = \'system_plugin_config_cache\' LIMIT 1');
		self::$pluginConfigCache = $dataArr;
	}

    static function getInstance($pluginKey)
    {
		// if no data in session try to reload it
        if(!isset(self::$pluginConfigCache[$pluginKey]))
		{
			self::reloadSessionPluginConfig();
		}

		// get plugin data
        $plugin = self::$pluginConfigCache[$pluginKey];

        // make sure we have a folder
        if(strlen($plugin['data']['folder_name']) == 0)
        {
            return false;
        }

        // create plugin instance
        $classPath = PLUGIN_DIRECTORY_ROOT . $plugin['data']['folder_name'] . '/plugin' . UCFirst($plugin['data']['folder_name']) . '.class.php';
        $pluginClassName = 'Plugin' . UCFirst($plugin['data']['folder_name']);
        include_once($classPath);

        return new $pluginClassName();
    }

    static function getPluginAdminNav()
    {
        // add any plugin navigation
        $html = '';
        $pluginConfigs = self::getPluginConfiguration();
		$totalItems = 0;
        if (COUNT($pluginConfigs))
        {
            foreach ($pluginConfigs AS $pluginConfig)
            {
                if ((self::pluginEnabled($pluginConfig['data']['folder_name']) == 1) && (isset($pluginConfig['config']['admin_settings']['top_nav'])))
                {
                    foreach ($pluginConfig['config']['admin_settings']['top_nav'] AS $navItem)
                    {
                        $html .= '<li';
                        if (ADMIN_SELECTED_PAGE == $navItem[0]['link_key'])
                            $html .= ' class="active"';
                        $html .= '><a href="' . ($navItem[0]['link_url'] != '#' ? (PLUGIN_WEB_ROOT . '/' . $pluginConfig['config']['folder_name'] . '/' . $navItem[0]['link_url']) : ($navItem[0]['link_url'])) . '"><span>' . adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t($navItem[0]['link_text'], $navItem[0]['link_text'])))) . '</span></a>';

                        if (COUNT($navItem > 1))
                        {
                            $html .= '<ul>';
                            unset($navItem[0]);
                            foreach ($navItem AS $navSubItem)
                            {
                                $html .= '<li><a href="' . PLUGIN_WEB_ROOT . '/' . $pluginConfig['config']['folder_name'] . '/' . $navSubItem['link_url'] . '"><span>' . adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t($navSubItem['link_text'], $navSubItem['link_text'])))) . '</span></a>';
                                
                                // add any sub items
    							if(isset($navSubItem['sub_nav']))
    							{
    								$html .= '<ul>';
    								foreach ($navSubItem['sub_nav'] AS $subNavItem)
    								{
    									$html .= '<li><a href="' . PLUGIN_WEB_ROOT . '/' . $pluginConfig['config']['folder_name'] . '/' . $subNavItem['link_url'] . '"><span>' . adminFunctions::makeSafe(UCWords(strtolower(adminFunctions::t($subNavItem['link_text'], $subNavItem['link_text'])))) . '</span></a></li>';
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
            }
        }
		
		if($totalItems >= 4)
		{
			$html = '<li><a href="#"><span>More...</span><ul>'.$html.'</ul></li>';
		}

        return $html;
    }

    static function getPluginAdminNavDropdown()
    {
        // add any plugin navigation
        $html = '';
        $pluginConfigs = self::getPluginConfiguration();
        if (COUNT($pluginConfigs))
        {
            foreach ($pluginConfigs AS $pluginConfig)
            {
                if ((self::pluginEnabled($pluginConfig['data']['folder_name']) == 1) && (isset($pluginConfig['config']['admin_settings']['top_nav'])))
                {
                    foreach ($pluginConfig['config']['admin_settings']['top_nav'] AS $navItem)
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
                                $html .= ' value="' . PLUGIN_WEB_ROOT . '/' . $pluginConfig['config']['folder_name'] . '/' . $navSubItem['link_url'] . '">' . htmlentities(UCWords(strtolower(adminFunctions::t($navSubItem['link_text'], $navSubItem['link_text'])))) . '</option>';
                            }
                        }
                        $html .= '</optgroup>';
                    }
                }
            }
        }

        return $html;
    }

    static function addCssFile($filePath)
    {
        self::$cssFiles[] = $filePath;
    }

    static function outputCss()
    {
        if (!isset(self::$pluginConfigCache))
        {
            self::$pluginConfigCache = self::loadPluginConfigurationFiles();
        }

        $cssFiles = array();
        foreach (self::$pluginConfigCache AS $pluginConfig)
        {
            if (self::pluginEnabled($pluginConfig['data']['folder_name']))
            {
                $cssFilePath = PLUGIN_DIRECTORY_ROOT . $pluginConfig['data']['folder_name'] . '/assets/css/styles.css';
                if (file_exists($cssFilePath))
                {
                    self::addCssFile(PLUGIN_WEB_ROOT . '/' . $pluginConfig['data']['folder_name'] . '/assets/css/styles.css');
                }
            }
        }

        if (COUNT(self::$cssFiles))
        {
            // merge and minify css
            //$cachedFilePath = self::mergeCssFiles(self::$cssFiles);
            //if($cachedFilePath !== false)
            //{
            //    self::$cssFiles = array($cachedFilePath);
            //}
            // output css
            foreach (self::$cssFiles AS $cssFile)
            {
                echo "<link rel=\"stylesheet\" href=\"" . $cssFile . "\" type=\"text/css\" charset=\"utf-8\" />\n";
            }
        }
    }

    static function getMergedBaseFilename($fileListing)
    {
        return MD5(implode('|', $fileListing));
    }

    static function mergeCssFiles($fileListing = array())
    {
        // calculate filename
        $newFileName = self::getMergedBaseFilename($fileListing);

        // get contents
        $fileContentStr = '';
        if (COUNT($fileListing))
        {
            foreach ($fileListing AS $filePath)
            {
                // get contents
                $fileContent = file_get_contents($filePath);
                if (strlen($fileContent))
                {
                    $fileContentStr .= "/* " . $filePath . " */\n";
                    $fileContentStr .= $fileContent;
                    $fileContentStr .= "\n";
                }
            }
        }

        // save to file
        $rs = cache::saveToFileCache($newFileName.'.css', $fileContentStr);
        if ($rs == false)
        {
            return false;
        }

        return CACHE_WEB_ROOT . '/' . $newFileName . '.css';
    }

    static function outputAdminCss()
    {
        if (!isset(self::$pluginConfigCache))
        {
            self::$pluginConfigCache = self::loadPluginConfigurationFiles();
        }

        $cssFiles = array();
        foreach (self::$pluginConfigCache AS $pluginConfig)
        {
            if (self::pluginEnabled($pluginConfig['data']['folder_name']))
            {
                $cssFilePath = PLUGIN_DIRECTORY_ROOT . $pluginConfig['data']['folder_name'] . '/admin/styles.css';
                if (file_exists($cssFilePath))
                {
                    $cssFiles[] = PLUGIN_WEB_ROOT . '/' . $pluginConfig['data']['folder_name'] . '/admin/styles.css';
                }
            }
        }

        if (COUNT($cssFiles))
        {
            foreach ($cssFiles AS $cssFile)
            {
                echo "<link rel=\"stylesheet\" href=\"" . $cssFile . "\" type=\"text/css\" charset=\"utf-8\" />\n";
            }
        }
    }

    static function addJsFile($filePath)
    {
        self::$jsFiles[] = $filePath;
    }

    static function outputJs()
    {
        if (!isset(self::$pluginConfigCache))
        {
            self::$pluginConfigCache = self::loadPluginConfigurationFiles();
        }

        $jsFiles = array();
        foreach (self::$pluginConfigCache AS $pluginConfig)
        {
            if (self::pluginEnabled($pluginConfig['data']['folder_name']))
            {
                $jsFilePath = PLUGIN_DIRECTORY_ROOT . $pluginConfig['data']['folder_name'] . '/assets/js/plugin.js';
                if (file_exists($jsFilePath))
                {
                    self::addJsFile(PLUGIN_WEB_ROOT . '/' . $pluginConfig['data']['folder_name'] . '/assets/js/plugin.js');
                }
            }
        }

        if (COUNT(self::$jsFiles))
        {
			// disabled as it can cause issues with paths. Recommend using a service like CloudFlaire instead
            //if (SITE_CONFIG_PERFORMANCE_JS_FILE_MINIFY == 'yes')
            //{
            //    // merge and minify js
            //    $cachedFilePath = self::mergeJsFiles(self::$jsFiles);
            //    if ($cachedFilePath !== false)
            //    {
            //        self::$jsFiles = array($cachedFilePath);
            //    }
            ///}

            foreach (self::$jsFiles AS $jsFile)
            {
                echo "<script type=\"text/javascript\" src=\"" . $jsFile . "\"></script>\n";
            }
        }
    }

    static function mergeJsFiles($fileListing = array())
    {
        // calculate filename
        $newFileName = self::getMergedBaseFilename($fileListing);
        $jsSubFilePath = CACHE_DIRECTORY_ROOT . '/js';
        $fullCacheFilePath = $jsSubFilePath . '/' . $newFileName . '.js';
        $fullCacheWebPath = CACHE_WEB_ROOT . '/js/' . $newFileName . '.js';
        if (file_exists($fullCacheFilePath))
        {
            return $fullCacheWebPath;
        }

        // get contents
        $fileContentStr = '';
        if (COUNT($fileListing))
        {
            foreach ($fileListing AS $filePath)
            {
                // get contents
                $fileContent = file_get_contents($filePath);
                if (strlen($fileContent))
                {
                    $fileContentStr .= "// " . $filePath . "\n";
                    $fileContentStr .= $fileContent;
                    $fileContentStr .= "\n";
                }
            }
        }

        // minify js before saving
        $rs = self::minifyJS($fileContentStr);
        if ($rs)
        {
            $fileContentStr = $rs;
        }

        // make sure sub folder exists
        if (!is_dir($jsSubPath))
        {
            @mkdir($jsSubFilePath);
        }

        // save to file
        $rs = file_put_contents($fullCacheFilePath, $fileContentStr);
        if ($rs == false)
        {
            return false;
        }

        return $fullCacheWebPath;
    }

    static function minifyJS($fileContent)
    {
        include_once(CORE_ROOT . '/includes/jsmin/JSMin.php');

        return JSMin::minify($fileContent);
    }

    static function outputAdminJs()
    {
        if (!isset(self::$pluginConfigCache))
        {
            self::$pluginConfigCache = self::loadPluginConfigurationFiles();
        }

        $jsFiles = array();
        foreach (self::$pluginConfigCache AS $pluginConfig)
        {
            if (self::pluginEnabled($pluginConfig['data']['folder_name']))
            {
                $jsFilePath = PLUGIN_DIRECTORY_ROOT . $pluginConfig['data']['folder_name'] . '/admin/plugin.js';
                if (file_exists($jsFilePath))
                {
                    $jsFiles[] = PLUGIN_WEB_ROOT . '/' . $pluginConfig['data']['folder_name'] . '/admin/plugin.js';
                }
            }
        }

        if (COUNT($jsFiles))
        {
            foreach ($jsFiles AS $jsFile)
            {
                echo "<script type=\"text/javascript\" src=\"" . $jsFile . "\"></script>\n";
            }
        }
    }

    static function includeAppends($fileName, $params = null)
    {
        $originalParams = $params;
        if (!isset(self::$pluginConfigCache))
        {
            self::$pluginConfigCache = self::loadPluginConfigurationFiles();
        }

        $includesFiles = array();
        foreach (self::$pluginConfigCache AS $pluginConfig)
        {
            $includesFilePath = PLUGIN_DIRECTORY_ROOT . $pluginConfig['data']['folder_name'] . '/includes/_append_' . $fileName;
            if ((file_exists($includesFilePath)) && (self::pluginEnabled($pluginConfig['data']['folder_name']) == true))
            {
                $includesFiles[] = $includesFilePath;
            }
        }
        if (COUNT($includesFiles))
        {
            foreach ($includesFiles AS $includesFile)
            {
                include($includesFile);
            }
        }

        return $params;
    }

    static function outputPaymentLinks($params = null)
    {
        if (!isset(self::$pluginConfigCache))
        {
            self::$pluginConfigCache = self::loadPluginConfigurationFiles();
        }

        $includesFiles = array();
        foreach (self::$pluginConfigCache AS $pluginConfig)
        {
            $includesFilePath = PLUGIN_DIRECTORY_ROOT . $pluginConfig['data']['folder_name'] . '/includes/_append_upgradeBoxes.inc.php';
            if ((file_exists($includesFilePath)) && (self::pluginEnabled($pluginConfig['data']['folder_name']) == true))
            {
                $includesFiles[] = $includesFilePath;
            }
        }

        if (COUNT($includesFiles))
        {
			$days = $params['days'];
			$price = $params['price'];
            foreach ($includesFiles AS $includesFile)
            {
                include($includesFile);
            }
        }
    }

    static function generateHeaderNavStructure($baseNavigation, $userLevelId = 0)
    {
        // add any plugin navigation
        $pluginConfigs = self::getPluginConfiguration();
        if (COUNT($pluginConfigs))
        {
            foreach ($pluginConfigs AS $pluginConfig)
            {
                if ((self::pluginEnabled($pluginConfig['data']['folder_name']) == 1) && (isset($pluginConfig['config']['site_settings']['top_nav'])))
                {
                    foreach ($pluginConfig['config']['site_settings']['top_nav'] AS $k => $navItem)
                    {
                        $baseNavigation[$k] = $navItem;
                    }
                }
            }
        }

        // filter any not valid for this user level id
        $navItems = array();
        foreach ($baseNavigation AS $k => $baseNavigationItem)
        {
            if (in_array($userLevelId, $baseNavigationItem['user_level_id']))
            {
                $navItems[$k] = $baseNavigationItem;
            }
        }

        // sort according to 'position'
        usort($navItems, array('pluginHelper', 'sortByOrder'));

        return $navItems;
    }

    static function sortByOrder($a, $b)
    {
        return $a['position'] - $b['position'];
    }
	
	static function currentlyInPluginDemoMode()
	{
		if (self::demoPluginsEnabled())
		{
			return false;
		}

		if (_CONFIG_DEMO_MODE == false)
		{
			return false;
		}

		return true;
	}
    
    static function demoPluginsEnabled()
	{
		if (!isset($_SESSION['_plugins']))
		{
			return false;
		}

		if ($_SESSION['_plugins'] == false)
		{
			return false;
		}

		return true;
	}
	
	static function getPluginAdminPackageSettingsForm($packageId = null)
    {
		// setup database
		$db = Database::getDatabase();
		
        // add any plugin navigation
        $html = '';
        $pluginConfigs = self::getPluginConfiguration();
        if (COUNT($pluginConfigs))
        {
            foreach ($pluginConfigs AS $pluginConfig)
            {
                if ((self::pluginEnabled($pluginConfig['data']['folder_name']) == 1) && (isset($pluginConfig['config']['package_options'])))
                {
					$html .= '<strong>'.$pluginConfig['config']['plugin_name'].' Plugin:<br/><br/></strong>';
					$html .= '<div class="form">';
					$class = 'alt-highlight';
                    foreach ($pluginConfig['config']['package_options'] AS $formItem)
                    {
						$value = $formItem['default_value'];
						
						// try to load any existing values
						if((int)$packageId)
						{
							$existingData = $db->getValue('SELECT plugin_settings FROM plugin WHERE id = '.(int)$pluginConfig['data']['id'].' LIMIT 1');
							if($existingData)
							{
								$existingDataArr = json_decode($existingData, true);
								if(is_array($existingDataArr))
								{
									if(isset($existingDataArr['account_packages']))
									{
										if(isset($existingDataArr['account_packages']['package_'.$packageId]))
										{
											if(isset($existingDataArr['account_packages']['package_'.$packageId][$formItem{'field_name'}]))
											{
												$value = $existingDataArr['account_packages']['package_'.$packageId][$formItem{'field_name'}];
											}
										}
									}
								}
							}
						}
						
                        $html .= '<div class="clearfix '.$class.'">
										<label>' . adminFunctions::makeSafe(UCWords($formItem['field_label'])) . ':</label>
										<div class="input">';
						switch($formItem['field_type'])
						{
							case 'integer':
								$element = '<input type="text" value="'.adminFunctions::makeSafe($value).'" class="custom[integer]" name="plugin_'.adminFunctions::makeSafe($pluginConfig['config']['folder_name']).'_'.adminFunctions::makeSafe($formItem['field_name']) . '" id="' . adminFunctions::makeSafe($formItem['field_name']) . '"/>';
								break;
							case 'select':
								$selectItems = array();
								$availableValues = $formItem['available_values'];
								if(substr($availableValues, 0, 6) == 'SELECT')
								{
									$items = $db->getRows($availableValues);
									if($items)
									{
										foreach($items AS $item)
										{
											$selectItems[] = $item['itemValue'];
										}
									}
								}
								else
								{
									$selectItems = json_decode($availableValues, true);
									if(COUNT($selectItems) == 0)
									{
										$selectItems = array('Error: Failed loading options');
									}
								}
						 
								$element = '<select name="plugin_'.adminFunctions::makeSafe($pluginConfig['config']['folder_name']).'_' . adminFunctions::makeSafe($formItem['field_name']) . '" id="' . adminFunctions::makeSafe($formItem['field_name']) . '">';
								foreach($selectItems AS $selectItem)
								{
									$element .= '<option value="'.adminFunctions::makeSafe($selectItem).'"';
									if($selectItem == $value)
									{
										$element .= ' SELECTED';
									}
									$element .= '>'.adminFunctions::makeSafe($selectItem).'</option>';
								}
								$element .= '</select>';
								break;
							case 'string':
								$element = '<input type="text" value="'.adminFunctions::makeSafe($value).'" class="large" name="plugin_'.adminFunctions::makeSafe($pluginConfig['config']['folder_name']).'_' . adminFunctions::makeSafe($formItem['field_name']) . '" id="' . adminFunctions::makeSafe($formItem['field_name']) . '"/>';
								break;
							case 'textarea':
							default:
								$element = '<textarea class="xxlarge" name="plugin_'.adminFunctions::makeSafe($pluginConfig['config']['folder_name']).'_' . adminFunctions::makeSafe($formItem['field_name']) . '" id="' . adminFunctions::makeSafe($formItem['field_name']) . '">'.adminFunctions::makeSafe($value).'</textarea>';
								break;
						}
						
						$html .= $element;
						if(strlen($formItem['field_description']))
						{
							$html .= '&nbsp;&nbsp;'.adminFunctions::makeSafe($formItem['field_description']);
						}
						$html .= '		</div>
									</div>';

						// row formatting
						if($class == 'alt-highlight')
						{
							$class = '';
						}
						else
						{
							$class = 'alt-highlight';
						}
                    }
					$html .= '</div><br/>';
                }
            }
        }

        return $html;
    }
	
	static function updatePluginPackageSettings($data, $packageId)
    {
		// setup database
		$db = Database::getDatabase();
		
        // add any plugin navigation
        $html = '';
        $pluginConfigs = self::getPluginConfiguration();
        if (COUNT($pluginConfigs))
        {
            foreach ($pluginConfigs AS $pluginConfig)
            {
                if ((self::pluginEnabled($pluginConfig['data']['folder_name']) == 1) && (isset($pluginConfig['config']['package_options'])))
                {
					$newSettingsData = array();
					foreach($data AS $k=>$dataItem)
					{
						if(substr($k, 0, strlen('plugin_'.$pluginConfig['data']['folder_name'].'_')) == 'plugin_'.$pluginConfig['data']['folder_name'].'_')
						{
							$actualName = substr($k, strlen('plugin_'.$pluginConfig['data']['folder_name'].'_'));
							$newSettingsData[$actualName] = $dataItem;
						}
					}
					
					// if we have new data, update the db
					if(COUNT($newSettingsData))
					{
						$existingData = $db->getValue('SELECT plugin_settings FROM plugin WHERE id = '.(int)$pluginConfig['data']['id'].' LIMIT 1');
						if($existingData)
						{
							$existingDataArr = json_decode($existingData, true);
							if(is_array($existingDataArr))
							{
								// add new data
								$existingDataArr['account_packages']['package_'.$packageId] = $newSettingsData;
								
								// update the db
								$db->query('UPDATE plugin SET plugin_settings = '.$db->quote(json_encode($existingDataArr)).' WHERE id = '.(int)$pluginConfig['data']['id'].' LIMIT 1');
							}
						}
					}
                }
            }
        }
		
		// reload plugin settings
		self::reloadSessionPluginConfig();

        return true;
    }
}
