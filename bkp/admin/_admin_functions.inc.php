<?php

class adminFunctions
{

    public static $errors  = array();
    public static $success = array();

    public static function setError($error)
    {
        self::$errors[] = $error;
    }

    public static function isErrors()
    {
        return self::getErrors() > 0;
    }

    public static function getErrors()
    {
        if (COUNT(self::$errors) == 0)
        {
            return false;
        }

        return self::$errors;
    }

    public static function compileErrorHtml()
    {
        $html = '';
        if (self::getErrors())
        {
            $html .= '<span class="notification undone">';
            foreach (self::getErrors() AS $error)
            {
                $html .= $error . '<br/>';
            }
            $html .= '</span>';
        }

        return $html;
    }

    public static function setSuccess($success)
    {
        self::$success[] = $success;
    }

    public static function isSuccess()
    {
        return self::getSuccess() > 0;
    }

    public static function getSuccess()
    {
        if (COUNT(self::$success) == 0)
        {
            return false;
        }

        return self::$success;
    }

    public static function compileSuccessHtml()
    {
        $html = '';
        if (self::getSuccess())
        {
            $html .= '<span class="notification done">';
            foreach (self::getSuccess() AS $success)
            {
                $html .= $success . '<br/>';
            }
            $html .= '</span>';
        }

        return $html;
    }

    public static function compileNotifications()
    {
        $html = self::compileErrorHtml();
        $html .= self::compileSuccessHtml();

        return $html;
    }

    public static function makeSafe($str)
    {
        return htmlspecialchars($str, ENT_QUOTES, "UTF-8");
    }

    public static function redirect($path)
    {
        // if no headers sent
        if (!headers_sent())
        {
            header('location: ' . $path);
            exit;
        }

        // fallback to meta/javascript redirect
        echo '<script type="text/javascript">';
        echo 'window.location.href="' . $path . '";';
        echo '</script>';
        echo '<noscript>';
        echo '<meta http-equiv="refresh" content="0;url=' . $path . '" />';
        echo '</noscript>';
        exit;
    }

    public static function getDirectoryList($directory, $extFilter = null, $recurr = false)
    {
        return coreFunctions::getDirectoryList($directory, $extFilter, $recurr);
    }

    public static function formatSize($bytes, $decimals = 0)
    {
        $size = $bytes / 1024;
        if ($size < 1024)
        {
            $size = number_format($size, $decimals);
            $size .= ' KB';
        }
        else
        {
            if ($size / 1024 < 1024)
            {
                $size = number_format($size / 1024, $decimals);
                $size .= ' MB';
            }
            else if ($size / 1024 / 1024 < 1024)
            {
                $size = number_format($size / 1024 / 1024, $decimals);
                $size .= ' GB';
            }
            else if ($size / 1024 / 1024 / 1024 < 1024)
            {
                $size = number_format($size / 1024 / 1024 / 1024, $decimals);
                $size .= ' TB';
            }
        }
        // remove unneccessary zeros
        $size = str_replace(".00 ", " ", $size);

        return $size;
    }

    public static function t($key, $defaultContent = '', $replacements = array())
    {
        return translate::getTranslation($key, $defaultContent, 1, $replacements);
    }

    public static function registerPlugins()
    {
        // get database connection
        $db = Database::getDatabase();

        // scan plugin directory and make sure they are all listed within the database
        $pluginDirectory = PLUGIN_DIRECTORY_ROOT;
        $directories     = adminFunctions::getDirectoryList($pluginDirectory);
        if (COUNT($directories))
        {
            foreach ($directories AS $directory)
            {
                // check the database to see if it already exists
                $found = $db->getValue("SELECT id FROM plugin WHERE folder_name = " . $db->quote($directory));
                if ($found)
                {
                    continue;
                }

                // not found in the db, we probably need to add it
                $pluginPath      = $pluginDirectory . $directory . '/';
                $pluginClassFile = $pluginPath . 'plugin' . UCFirst(strtolower($directory)) . '.class.php';
                $pluginClassName = 'Plugin' . UCFirst(strtolower($directory));

                // make sure we have the main class file
                if (!file_exists($pluginClassFile))
                {
                    continue;
                }

                try
                {
                    // try to create an instance of the class
                    include_once($pluginClassFile);
                    if (!class_exists($pluginClassName))
                    {
                        continue;
                    }

                    $instance = new $pluginClassName();
                    if (!$instance)
                    {
                        continue;
                    }

                    // get plugin details
                    $pluginDetails = $instance->getPluginDetails();

                    // insert new plugin into db
                    if ($pluginDetails)
                    {
                        $dbInsert                     = new DBObject("plugin", array("plugin_name", "folder_name", "plugin_description", "is_installed"));
                        $dbInsert->plugin_name        = $pluginDetails['plugin_name'];
                        $dbInsert->folder_name        = $pluginDetails['folder_name'];
                        $dbInsert->plugin_description = $pluginDetails['plugin_description'];
                        $dbInsert->is_installed       = 0;
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

    public static function recursiveDelete($str)
    {
        // failsafe, make sure it's only in the plugin directory
        if (substr($str, 0, strlen(PLUGIN_DIRECTORY_ROOT)) != PLUGIN_DIRECTORY_ROOT)
        {
            return false;
        }

        if (is_file($str))
        {
            return @unlink($str);
        }
        elseif (is_dir($str))
        {
            // look for .htaccess files
            $scan = glob(rtrim($str, '/') . '/.*');
            foreach ($scan as $index => $path)
            {
                @unlink($path);
            }

            // handle directories
            $scan = glob(rtrim($str, '/') . '/*');
            foreach ($scan as $index => $path)
            {
                self::recursiveDelete($path);
            }

            return @rmdir($str);
        }
    }

    public static function recursiveThemeDelete($str)
    {
        // failsafe, make sure it's only in the plugin directory
        if (substr($str, 0, strlen(SITE_THEME_DIRECTORY_ROOT)) != SITE_THEME_DIRECTORY_ROOT)
        {
            return false;
        }

        if (is_file($str))
        {
            return @unlink($str);
        }
        elseif (is_dir($str))
        {
            // look for .htaccess files
            $scan = glob(rtrim($str, '/') . '/.*');
            foreach ($scan as $index => $path)
            {
                @unlink($path);
            }

            // handle directories
            $scan = glob(rtrim($str, '/') . '/*');
            foreach ($scan as $index => $path)
            {
                self::recursiveThemeDelete($path);
            }

            return @rmdir($str);
        }
    }

    public static function limitStringLength($string, $length = 100)
    {
        if (strlen($string) < $length)
        {
            return $string;
        }

        return substr($string, 0, $length) . '...';
    }

    public static function registerThemes()
    {
        return themeHelper::registerThemes();
    }
	
	public function phpinfoArray($return=false)
	{
		ob_start(); 
		phpinfo(-1);    
		$pi = preg_replace(array('#^.*<body>(.*)</body>.*$#ms', '#<h2>PHP License</h2>.*$#ms',
		'#<h1>Configuration</h1>#',  "#\r?\n#", "#</(h1|h2|h3|tr)>#", '# +<#',
		"#[ \t]+#", '#&nbsp;#', '#  +#', '# class=".*?"#', '%&#039;%',
		  '#<tr>(?:.*?)" src="(?:.*?)=(.*?)" alt="PHP Logo" /></a>'
		  .'<h1>PHP Version (.*?)</h1>(?:\n+?)</td></tr>#',
		  '#<h1><a href="(?:.*?)\?=(.*?)">PHP Credits</a></h1>#',
		  '#<tr>(?:.*?)" src="(?:.*?)=(.*?)"(?:.*?)Zend Engine (.*?),(?:.*?)</tr>#',
		  "# +#", '#<tr>#', '#</tr>#'), array('$1', '', '', '', '</$1>' . "\n", '<', ' ', ' ', ' ', '', ' ',
		  '<h2>PHP Configuration</h2>'."\n".'<tr><td>PHP Version</td><td>$2</td></tr>'.
		  "\n".'<tr><td>PHP Egg</td><td>$1</td></tr>',
		  '<tr><td>PHP Credits Egg</td><td>$1</td></tr>',
		  '<tr><td>Zend Engine</td><td>$2</td></tr>' . "\n" .
		  '<tr><td>Zend Egg</td><td>$1</td></tr>', ' ', '%S%', '%E%'),
		ob_get_clean());
		
		$sections = explode('<h2>', strip_tags($pi, '<h2><th><td>'));
		unset($sections[0]);
		
		$pi = array();
		foreach($sections as $section)
		{
		   $n = substr($section, 0, strpos($section, '</h2>'));
		   preg_match_all('#%S%(?:<td>(.*?)</td>)?(?:<td>(.*?)</td>)?(?:<td>(.*?)</td>)?%E%#', $section, $askapache, PREG_SET_ORDER);
		   foreach($askapache as $m)
			   $pi[$n][$m[1]] = (!isset($m[3])||$m[2]==$m[3]) ? $m[2] : array_slice($m,2);
		}
		
		return ($return === false) ? ($pi) : $pi;
	}

}