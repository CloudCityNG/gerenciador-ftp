<?php

class themecloudable extends Theme
{

    public $config = null;

    public function __construct()
    {
        // get the plugin config
        include('_theme_config.inc.php');

        // load config into the object
        $this->config = $themeConfig;
    }

    public function getThemeDetails()
    {
        return $this->config;
    }
	
	public function getThemeSkin()
	{
		$skin = themeHelper::getConfigValue('site_skin');
		if(strlen($skin))
		{
			return $skin;
		}
		
		return false;
	}
	
	public function getMainLogoUrl()
	{
		// get database
		$db = Database::getDatabase();

		// see if the replaced logo exists
		$localCachePath = CACHE_DIRECTORY_ROOT . '/themes/'.$this->config['folder_name'].'/logo.png';
		if(file_exists($localCachePath))
		{
			return CACHE_WEB_ROOT . '/themes/'.$this->config['folder_name'].'/logo.png';
		}
		
		return $this->getFallbackMainLogoUrl();
	}
	
	public function getFallbackMainLogoUrl()
	{
		return coreFunctions::getCoreSitePath().'/themes/'.$this->config['folder_name'].'/images/logo/logo.png';
	}
	
	public function getInverseLogoUrl()
	{
		// get database
		$db = Database::getDatabase();

		// see if the replaced logo exists
		$localCachePath = CACHE_DIRECTORY_ROOT . '/themes/'.$this->config['folder_name'].'/logo_inverse.png';
		if(file_exists($localCachePath))
		{
			return CACHE_WEB_ROOT . '/themes/'.$this->config['folder_name'].'/logo_inverse.png';
		}
		
		return $this->getInverseFallbackLogoUrl();
	}

	public function getInverseFallbackLogoUrl()
	{
		return coreFunctions::getCoreSitePath().'/themes/'.$this->config['folder_name'].'/images/logo/logo-whitebg.png';
	}
	
	public function outputCustomCSSCode()
	{
		// see if the replaced logo exists
		$localCachePath = CACHE_DIRECTORY_ROOT . '/themes/'.$this->config['folder_name'].'/custom_css.css';
		if(file_exists($localCachePath))
		{
			return "<link href=\"".CACHE_WEB_ROOT . "/themes/".$this->config['folder_name']."/custom_css.css?r=".md5(microtime())."\" rel=\"stylesheet\">\n";
		}
	}
	
	public function getCustomCSSCode()
	{
		return themeHelper::getConfigValue('css_code');
	}
    
    public function getSimilarFiles(file $file)
    {
        $similarFiles = array();
		
		// load database
		$db = Database::getDatabase(true);
		
		// load orderby from session
		$orderBy = 'originalFilename';
		if(isset($_SESSION['search']['filterOrderBy']))
		{
			$orderBy = $_SESSION['search']['filterOrderBy'];
		}
        
        // get all other files in the same folder/album, only if this file is in an actual folder
        if((int)$file->folderId)
        {
			$similarFiles = $db->getRows('SELECT * FROM file WHERE folderId = ' . (int)$file->folderId . ' AND statusId = 1 ORDER BY '.$db->escape($this->convertSortOption($orderBy)));
        }
		else if((int)$file->userId)
		{
			$similarFiles = $db->getRows('SELECT * FROM file WHERE userId = ' . (int)$file->userId . ' AND folderId IS NULL AND statusId = 1 ORDER BY '.$db->escape($this->convertSortOption($orderBy)));
		}
		
		if (!is_array($similarFiles))
		{
			return array();
		}

        if(!COUNT($similarFiles))
        {
            return array();            
        }
        
        // set the currently selected on as the first
        $startArr = array();
        $endArr = array();
        $found = false;        
        $rsArr = array();
        foreach($similarFiles AS $similarFile)
        {
			// make sure it's an image
			$file = file::hydrate($similarFile);
			$rsArr[] = $file;
        }

        return $rsArr;
    }
	
	public function convertSortOption($filterOrderBy)
	{
		$sortColName = 'originalFilename asc';
		switch($filterOrderBy)
		{
			case 'order_by_filename_asc':
				$sortColName = 'originalFilename asc';
				break;
			case 'order_by_filename_desc':
				$sortColName = 'originalFilename desc';
				break;
			case 'order_by_uploaded_date_asc':
			case '':
				$sortColName = 'uploadedDate asc';
				break;
			case 'order_by_uploaded_date_desc':
				$sortColName = 'uploadedDate desc';
				break;
			case 'order_by_downloads_asc':
				$sortColName = 'visits asc';
				break;
			case 'order_by_downloads_desc':
				$sortColName = 'visits desc';
				break;
			case 'order_by_filesize_asc':
				$sortColName = 'fileSize asc';
				break;
			case 'order_by_filesize_desc':
				$sortColName = 'fileSize desc';
				break;
			case 'order_by_last_access_date_asc':
				$sortColName = 'lastAccessed asc';
				break;
			case 'order_by_last_access_date_desc':
				$sortColName = 'lastAccessed desc';
				break;
		}
		
		return $sortColName;
	}
}
