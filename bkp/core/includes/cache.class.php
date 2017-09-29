<?php

/**
 * Cache class for managing local cache in memory and on disk
 */
class cache
{
    static public $cacheArr = array();
    
    static function cacheExists($key)
    {
        if(isset(self::$cacheArr[$key]))
        {
            return true;
        }
        
        return false;
    }
    
    static function getCache($key)
    {
        if(self::cacheExists($key))
        {
            $value = self::$cacheArr[$key]['value'];
            $type = self::$cacheArr[$key]['type'];
            if($type == 'object' || $type == 'array')
            {
                return unserialize($value);
            }
            
            return $value;
        }
        
        return false;
    }
    
    static function setCache($key, $value)
    {
        self::$cacheArr[$key] = array();
        self::$cacheArr[$key]['type'] = gettype($value);
        
        if(is_array($value) || is_object($value))
        {
            $value = serialize($value);
        }
        
        self::$cacheArr[$key]['value'] = $value;

        return true;
    }
	
	static function clearCache($key)
    {
        self::$cacheArr[$key] = array();
		unset(self::$cacheArr[$key]);
    }
    
    static function clearAllCache()
    {
        unset(self::$cacheArr);
        self::$cacheArr = array();
    }
    
    static function saveCacheToFile($newFileName, $fileContentStr)
    {
        // save to file
        $fullCacheFilePath = CACHE_DIRECTORY_ROOT . '/' . $newFileName;
        
        // make sure the folder path exists
        if(!file_exists(dirname($fullCacheFilePath)))
        {
            $rs = mkdir(dirname($fullCacheFilePath), 0777, true);
            if(!$rs)
			{
                log::error('Failed creating cache storage folder, possibly a permissions problem. ('.dirname($fullCacheFilePath).')');
				die('Failed creating cache storage folder, possibly a permissions problem. ('.dirname($fullCacheFilePath).')');
			}
        }
        
        // save cache
        $rs = file_put_contents($fullCacheFilePath, $fileContentStr);
        if ($rs == false)
        {
            return false;
        }
        
        return $fullCacheFilePath;
    }
    
    /**
     * Cache file in the format js/filename.js. i.e. relative to the CACHE_DIRECTORY_ROOT root
     * 
     * @param type $cacheFile
     * @return boolean|string
     */
    static function getCacheFromFile($cacheFile)
    {
        // full path to file
        $fullCacheFilePath = CACHE_DIRECTORY_ROOT . '/' . $cacheFile;
        
        // make sure the folder path exists
        if(!file_exists($fullCacheFilePath))
        {
            return false;
        }
        
        return file_get_contents($fullCacheFilePath);
    }
    
    static function checkCacheFileExists($cacheFile)
    {
        // full path to file
        $fullCacheFilePath = CACHE_DIRECTORY_ROOT . '/' . $cacheFile;
        
        // make sure the folder path exists
        if(!file_exists($fullCacheFilePath))
        {
            return false;
        }
        
        return true;
    }
	
	static function removeCacheFile($cacheFile)
	{
		if(self::checkCacheFileExists($cacheFile))
		{
			// full path to file
			$fullCacheFilePath = CACHE_DIRECTORY_ROOT . '/' . $cacheFile;
		
			// remove cache file
			@unlink($fullCacheFilePath);
		}
	}
	
	static function removeCacheSubFolder($cacheFolder)
	{
		// failsafe
		if(!strlen($cacheFolder))
		{
			return false;
		}
		
		// full path to folder
		$fullCacheFilePath = CACHE_DIRECTORY_ROOT . '/' . $cacheFolder;
		$files = array_diff(scandir($fullCacheFilePath), array('.', '..')); 
		foreach ($files as $file)
		{
			// failsafe
			if(!strlen($file))
			{
				continue;
			}
			(is_dir($fullCacheFilePath.'/'.$file)) ? self::removeCacheSubFolder($cacheFolder.'/'.$file) : unlink($fullCacheFilePath.'/'.$file); 
		}
		
		return rmdir($cacheFolder); 
	}
}
