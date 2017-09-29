<?php

class UserPeer
{

    // Singleton object. Leave $me alone.
    private static $me;

    static function create($username, $password, $email, $title, $firstname, $lastname, $accType = 'user')
    {
		// connect db
        $db = Database::getDatabase();
		
		// default free user level id
		$levelId = 1;
		$levelIdRs = (int)$db->getValue('SELECT id FROM user_level WHERE level_type = \'free\' AND id > 0 ORDER BY id ASC LIMIT 1');
		if($levelIdRs)
		{
			$levelId = (int)$levelIdRs;
		}
		
        $dbInsert                 = new DBObject("users", array("username", "password", "email",
            "title", "firstname", "lastname", "datecreated",
            "createdip", "status", "level_id", "paymentTracker", "identifier")
        );
        $dbInsert->username       = $username;
        $dbInsert->password       = Password::createHash($password);
        $dbInsert->email          = $email;
        $dbInsert->title          = $title;
        $dbInsert->firstname      = $firstname;
        $dbInsert->lastname       = $lastname;
        $dbInsert->datecreated    = coreFunctions::sqlDateTime();
        $dbInsert->createdip      = coreFunctions::getUsersIPAddress();
        $dbInsert->status         = 'active';
        $dbInsert->level_id       = $levelId;
        $dbInsert->paymentTracker = MD5(time() . $username);
        $dbInsert->identifier     = MD5(time() . $username . $password);
        if ($dbInsert->insert())
        {
            return $dbInsert;
        }

        return false;
    }

    static function createPasswordResetHash($userId)
    {
        $user = true;

        // make sure it doesn't already exist on an account
        while ($user != false)
        {
            // create hash
            $hash = MD5(microtime() . $userId);

            // lookup by hash
            $user = self::loadUserByPasswordResetHash($hash);
        }

        // update user with hash
        $db = Database::getDatabase(true);
        $db->query('UPDATE users SET passwordResetHash = :passwordResetHash WHERE id = :id', array('passwordResetHash' => $hash, 'id'                => $userId));

        return $hash;
    }

    static function loadUserById($id)
    {
		// first check for user in cache and load it if found
		if(cache::cacheExists('USER_OBJECT_'.(int)$id) == false)
		{
			$userObj = new User();
			$userObj->select($id, 'id');
			if (!$userObj->ok())
			{
				return false;
			}
			
			// store cache
			cache::setCache('USER_OBJECT_'.(int)$id, $userObj);
		}

		// get from cache
        return cache::getCache('USER_OBJECT_'.(int)$id);
    }

    static function loadUserByUsername($username)
    {
        $userObj = new User();
        $userObj->select($username, 'username');
        if (!$userObj->ok())
        {
            return false;
        }

        return $userObj;
    }

    static function loadUserByPaymentTracker($paymentTracker)
    {
        $userObj = new User();
        $userObj->select($paymentTracker, 'paymentTracker');
        if (!$userObj->ok())
        {
            return false;
        }

        return $userObj;
    }

    static function loadUserByEmailAddress($email)
    {
        $userObj = new User();
        $userObj->select($email, 'email');
        if (!$userObj->ok())
        {
            return false;
        }

        return $userObj;
    }

    static function loadUserByPasswordResetHash($hash)
    {
        $userObj = new User();
        $userObj->select($hash, 'passwordResetHash');
        if (!$userObj->ok())
        {
            return false;
        }

        return $userObj;
    }

    static function loadUserByIdentifier($identifier)
    {
        $userObj = new User();
        $userObj->select($identifier, 'identifier');
        if (!$userObj->ok())
        {
            return false;
        }

        return $userObj;
    }

	// *************************************************
	// deprecated, use upgradeUserByPackageId() instead
	// *************************************************
    static function upgradeUser($userId, $days, $packageId = null)
    {
        // load user
        $user = UserPeer::loadUserById($userId);

        // upgrade user
        $newExpiryDate = strtotime('+' . $days . ' days');

        // extend user
        if ($user->level_id >= 2)
        {
            // add onto existing period
            $existingExpiryDate = strtotime($user->paidExpiryDate);

            // if less than today just revert to now
            if ($existingExpiryDate < time())
            {
                $existingExpiryDate = time();
            }

            $newExpiryDate = (int) $existingExpiryDate + (int) ($days * (60 * 60 * 24));
        }

        // figure out new account type
        $newUserType = 2;
        if ($user->level_id > 2)
        {
            $newUserType = $user->level_id;
        }

        // update user account to premium
        $dbUpdate                 = new DBObject("users", array("level_id", "lastPayment", "paidExpiryDate"), 'id');
        $dbUpdate->level_id       = $newUserType;
        $dbUpdate->lastPayment    = date("Y-m-d H:i:s", time());
        $dbUpdate->paidExpiryDate = date("Y-m-d H:i:s", $newExpiryDate);
        $dbUpdate->id             = $userId;
        $effectedRows             = $dbUpdate->update();
        if ($effectedRows === false)
        {
            // failed to update user
            return false;
        }

        return true;
    }
	
	static function upgradeUserByPackageId($userId, $order)
    {
		// connect db
        $db = Database::getDatabase();
		
        // load user
        $user = UserPeer::loadUserById($userId);

		// load pricing info
		$price = $db->getRow('SELECT id, pricing_label, period, price, user_level_id FROM user_level_pricing WHERE id = '.(int)$order->user_level_pricing_id.' LIMIT 1');
		if(!$price)
		{
			return false;
		}
		$priceStr = $price['price'];
		$days = (int)coreFunctions::convertStringDatePeriodToDays($price['period']);
		$newUserPackageId = $price['user_level_id'];
		
        // upgrade user
		$newExpiryDate = strtotime('+' . $days . ' days');
		
        // extend user if they are not a 'free' account
		$levelName = self::getUserLevelValue('level_type', $user->level_id);
		if ($levelName != 'free')
		{
			// add onto existing period
			$existingExpiryDate = strtotime($user->paidExpiryDate);

			// if less than today just revert to now
			if ($existingExpiryDate < time())
			{
				$existingExpiryDate = time();
			}

			$newExpiryDate = (int) $existingExpiryDate + (int) ($days * (60 * 60 * 24));
			
			// if they are an admin or moderator keep the same account type, avoid downgrades resulting in non admin based accounts
			if($levelName != 'paid')
			{
				$newUserPackageId = $user->level_id;
			}
		}

        // update user account to premium
        $dbUpdate                 = new DBObject("users", array("level_id", "lastPayment", "paidExpiryDate"), 'id');
        $dbUpdate->level_id       = $newUserPackageId;
        $dbUpdate->lastPayment    = date("Y-m-d H:i:s", time());
        $dbUpdate->paidExpiryDate = date("Y-m-d H:i:s", $newExpiryDate);
        $dbUpdate->id             = $userId;
        $effectedRows             = $dbUpdate->update();
        if ($effectedRows === false)
        {
            // failed to update user
            return false;
        }

        return true;
    }
	
	static function getDefaultFreeAccountTypeId()
	{
		// connect db
        $db = Database::getDatabase(true);
		
		// load account id for free accounts
		$levelIdRs = (int)$db->getValue('SELECT id FROM user_level WHERE level_type = \'free\' AND id > 0 ORDER BY id ASC LIMIT 1');
		if($levelIdRs)
		{
			return $levelIdRs;
		}
		
		// fallback
		return 1;
	}
	
	static function getAllAccountTypePackageIds($typeLabel = 'paid')
	{
		// connect db
        $db = Database::getDatabase();
		
		// preload all paid account types
		$paidAccountTypes = $db->getRows('SELECT id FROM user_level WHERE level_type = '.$db->quote($typeLabel));
		if(!$paidAccountTypes)
		{
			return false;
		}
		
		// prepare in an array for the query below
		$paidAccountTypesArr = array();
		foreach($paidAccountTypes AS $paidAccountType)
		{
			$paidAccountTypesArr[] = (int)$paidAccountType['id'];
		}
		
		return $paidAccountTypesArr;
	}

    static function downgradeExpiredAccounts()
    {
        // connect db
        $db = Database::getDatabase();

		// prepare in an array for the query below
		$paidAccountTypesArr = self::getAllAccountTypePackageIds();
		if(COUNT($paidAccountTypesArr) == 0)
		{
			return false;
		}

        // downgrade paid accounts
		$freeAccountTypeId = self::getDefaultFreeAccountTypeId();
        $sQL = 'UPDATE users SET level_id = '.(int)$freeAccountTypeId.' WHERE level_id IN ('.implode(',', $paidAccountTypesArr).') AND UNIX_TIMESTAMP(paidExpiryDate) < ' . time();
        $rs  = $db->query($sQL);
    }

    static function getLevelLabel($packageId)
    {
        return self::getUserLevelValue('label', $packageId);
    }
	
	// used for old level id types
	static function getLevelIdFromPackageId($packageId)
    {
		$levelName = self::getUserLevelValue('level_type', $packageId);
		switch($levelName)
		{
			case 'free':
				return 1;
				break;
			case 'paid':
				return 2;
				break;
			case 'moderator':
				return 10;
				break;
			case 'admin':
				return 20;
				break;
			default:
				return 0;
				break;
		}
    }
	
	static function getLevelTypeFromPackageId($packageId)
    {
        return self::getUserLevelValue('level_type', $packageId);
    }

    static function getAcceptedFileTypes()
    {
        $rs = array();
        if (strlen(trim(SITE_CONFIG_ACCEPTED_UPLOAD_FILE_TYPES)) > 0)
        {
            $fileTypes = explode(";", trim(SITE_CONFIG_ACCEPTED_UPLOAD_FILE_TYPES));
            foreach ($fileTypes AS $fileType)
            {
                if (strlen(trim($fileType)))
                {
                    $rs[] = strtolower(trim($fileType));
                }
            }
        }
        sort($rs);

        return $rs;
    }
    
    static function getBlockedFileTypes()
    {
        $rs = array();
        if (strlen(trim(SITE_CONFIG_BLOCKED_UPLOAD_FILE_TYPES)) > 0)
        {
            $fileTypes = explode(";", trim(SITE_CONFIG_BLOCKED_UPLOAD_FILE_TYPES));
            foreach ($fileTypes AS $fileType)
            {
                if (strlen(trim($fileType)))
                {
                    $rs[] = strtolower(trim($fileType));
                }
            }
        }
        sort($rs);
            
		return $rs;      
    }
	
	static function getBlockedFilenameKeywords()
    {
        $rs = array();
        if (strlen(trim(SITE_CONFIG_BLOCKED_FILENAME_KEYWORDS)) > 0)
        {
            $keywords = explode("|", trim(SITE_CONFIG_BLOCKED_FILENAME_KEYWORDS));
            foreach ($keywords AS $keyword)
            {
                if (strlen(trim($keyword)))
                {
                    $rs[] = strtolower(trim($keyword));
                }
            }
        }
            
		return $rs;      
    }
    
    static function getRemainingFilesToday()
    {
        if ((int) SITE_CONFIG_MAX_FILES_PER_DAY == 0)
        {
            return 10000;
        }

        $userIP         = coreFunctions::getUsersIPAddress();
        $db             = Database::getDatabase(true);
        $totalUploads   = (int) $db->getValue('SELECT COUNT(id) AS total FROM file WHERE DATE(uploadedDate) = DATE(NOW()) AND uploadedIP = ' . $db->quote($userIP));
        $totalRemaining = (int) SITE_CONFIG_MAX_FILES_PER_DAY - $totalUploads;

        return $totalRemaining >= 0 ? $totalRemaining : 0;
    }
	
	// note: $levelId is the account package id (user_level.id)
	static function getUserLevelValue($column, $levelId)
    {
		// run this through cache for better performance and less db queries
		$userLevelCacheArr = null;
		if(cache::cacheExists('DATA_USER_LEVEL') == false)
		{
			// load in cache from the database
			$db = Database::getDatabase();
			$userLevelCache = $db->getRows('SELECT * FROM user_level');
			$userLevelCacheArr = array();
			foreach($userLevelCache AS $userLevelCacheItem)
			{
				$userLevelCacheArr[$userLevelCacheItem{'id'}] = $userLevelCacheItem;
			}
			cache::setCache('DATA_USER_LEVEL', $userLevelCacheArr);
		}
		
		// check value in cache
		if($userLevelCacheArr == null)
		{
			$userLevelCacheArr = cache::getCache('DATA_USER_LEVEL');
		}

		if(!isset($userLevelCacheArr[$levelId][$column]))
		{
			return null;
		}
		
		return $userLevelCacheArr[$levelId][$column];
    }

	// note: $levelId is the account package id (user_level.id)
    static function showSiteAdverts($levelId = null)
    {
        if ($levelId === null)
        {
            $Auth    = Auth::getAuth();
            $levelId = $Auth->package_id;
        }
        
        return self::getUserLevelValue('show_site_adverts', $levelId) == 1 ? true : false;
    }

	// note: $levelId is the account package id (user_level.id)
    static function getAllowedToUpload($levelId = null)
    {
        if ($levelId === null)
        {
            $Auth    = Auth::getAuth();
            $levelId = $Auth->package_id;
        }
        
        return self::getUserLevelValue('can_upload', $levelId) == 1 ? true : false;
    }

	// note: $levelId is the account package id (user_level.id)
    static function getMaxDailyDownloads($levelId = null)
    {
        if ($levelId === null)
        {
            $Auth    = Auth::getAuth();
            $levelId = $Auth->package_id;
        }
        
        $val = self::getUserLevelValue('downloads_per_24_hours', $levelId);
        return $val==null?0:(int)$val;
    }

	// note: $levelId is the account package id (user_level.id)
    static function getMaxDownloadSize($levelId = null)
    {
        if ($levelId === null)
        {
            $Auth    = Auth::getAuth();
            $levelId = $Auth->package_id;
        }
        
        $val = self::getUserLevelValue('max_download_filesize_allowed', $levelId);
        return $val==null?0:(int)$val;
    }

	// note: $levelId is the account package id (user_level.id)
    static function getMaxDownloadSpeed($levelId = null)
    {
        if ($levelId === null)
        {
            $Auth    = Auth::getAuth();
            $levelId = $Auth->package_id;
        }
        
        $val = self::getUserLevelValue('download_speed', $levelId);
        return $val==null?0:(int)$val;
    }

	// note: $levelId is the account package id (user_level.id)
    static function getMaxRemoteUrls($levelId = null)
    {
        if ($levelId === null)
        {
            $Auth    = Auth::getAuth();
            $levelId = $Auth->package_id;
        }
        
        $val = self::getUserLevelValue('max_remote_download_urls', $levelId);
        return $val==null?0:(int)$val;
    }

	// note: $levelId is the account package id (user_level.id)
    static function getMaxUploadFilesize($levelId = null)
    {
        if ($levelId === null)
        {
            $Auth    = Auth::getAuth();
            $levelId = $Auth->package_id;
        }
        
        $val = self::getUserLevelValue('max_upload_size', $levelId);
        return strlen($val)?$val:0;
    }

	// note: $levelId is the account package id (user_level.id)
    static function showDownloadCaptcha($levelId = null)
    {
        if ($levelId === null)
        {
            $Auth    = Auth::getAuth();
            $levelId = $Auth->package_id;
        }

        switch ($levelId)
        {
            // free user
            case 1:
                return (SITE_CONFIG_FREE_USER_SHOW_CAPTCHA == 'yes') ? true : false;
            // non user
            case 0:
                return (SITE_CONFIG_NON_USER_SHOW_CAPTCHA == 'yes') ? true : false;
            // paid & admin users
            default:
                return false;
        }
    }

	// note: $levelId is the account package id (user_level.id)
    static function getWaitTimeBetweenDownloads($levelId = null)
    {
        if ($levelId === null)
        {
            $Auth    = Auth::getAuth();
            $levelId = $Auth->package_id;
        }
        
        return self::getUserLevelValue('wait_between_downloads', $levelId);
    }
	
	// note: $levelId is the account package id (user_level.id)
	static function getDaysToKeepInactiveFiles($levelId = null)
    {
        if ($levelId === null)
        {
            $Auth    = Auth::getAuth();
            $levelId = $Auth->package_id;
        }
        
        return self::getUserLevelValue('days_to_keep_inactive_files', $levelId);
    }

	// note: $levelId is the account package id (user_level.id)
    static function enableUpgradePage($levelId = null)
    {
        if ($levelId === null)
        {
            $Auth    = Auth::getAuth();
            $levelId = $Auth->package_id;
        }

        return self::getUserLevelValue('show_upgrade_screen', $levelId)==1?'yes':'no';
    }

	// note: $levelId is the account package id (user_level.id)
    static function getMaxDownloadThreads($levelId = null)
    {
        if ($levelId === null)
        {
            $Auth    = Auth::getAuth();
            $levelId = $Auth->package_id;
        }
        
        return (int)self::getUserLevelValue('concurrent_downloads', $levelId);
    }
	
	// note: $levelId is the account package id (user_level.id)
    static function getMaxUploadsAtOnce($levelId = null)
    {
        if ($levelId === null)
        {
            $Auth    = Auth::getAuth();
            $levelId = $Auth->package_id;
        }
        
        return (int)self::getUserLevelValue('concurrent_uploads', $levelId);
    }

    static function getMaxFileStorage($userId = null)
    {
        $fallback = 1024 * 1024 * 1024 * 1024 * 5; // 5TB fallback
        $limit    = $fallback;
        $Auth     = null;
        $userLevel = 0;
        if ($userId === null)
        {
            $Auth   = Auth::getAuth();
            $userId = $Auth->id;
        }

        if ($userId !== null)
        {
            if ($Auth)
            {
                $userLevel            = $Auth->package_id;
                $storageLimitOverride = $Auth->user->storageLimitOverride;
            }
            else
            {
                $user                 = UserPeer::loadUserById($userId);
                $userLevel            = $user->level_id;
                $storageLimitOverride = $user->storageLimitOverride;
            }
        }

        // limit based on account type
        $str = self::getUserLevelValue('max_storage_bytes', $userLevel);
        $limit = ((strlen($str) == 0) || ($str == 0)) ? null : $str;

        // check for limit override
        if ((strlen($storageLimitOverride)))
        {
            $limit = $storageLimitOverride;
        }

        // for unlimited
        if ($limit == 0)
        {
            $limit = null;
        }

        return $limit;
    }

	// note: $levelId is the account package id (user_level.id)
    static function getTotalWaitingTime($levelId = null)
    {
        if ($levelId === null)
        {
            $Auth    = Auth::getAuth();
            $levelId = $Auth->package_id;
        }

        // lookup total waiting time
        $db        = Database::getDatabase();
        $sQL       = 'SELECT additional_settings FROM download_page WHERE user_level_id = ' . (int) $levelId;
        $rows      = $db->getRows($sQL);
        $totalTime = 0;
        if ($rows)
        {
            foreach ($rows AS $row)
            {
                $additionalSettings = $row['additional_settings'];
                if (strlen($additionalSettings))
                {
                    $additionalSettingsArr = json_decode($additionalSettings, true);
                    if (isset($additionalSettingsArr['download_wait']))
                    {
                        $totalTime = $totalTime + (int) $additionalSettingsArr['download_wait'];
                    }
                }
            }
        }

        return $totalTime;
    }

    static function getAvailableFileStorage($userId = null)
    {
        $fallback = 1024 * 1024 * 1024 * 1024 * 5; // 5TB fallback
        if ($userId === null)
        {
            $Auth    = Auth::getAuth();
            if ($Auth->loggedIn() == true)
            {
                $Auth   = Auth::getAuth();
                $userId = $Auth->id;
            }
            else
            {
                return $fallback;
            }
        }
        $maxFileStorage = self::getMaxFileStorage($userId);
        if (($maxFileStorage === null) || ($maxFileStorage === $fallback)) // unlimited users
        {
            return null;
        }

        // calculate total user
        $totalUsed = file::getTotalActiveFileSizeByUser($userId);
        if ($totalUsed > $maxFileStorage)
        {
            return 0;
        }

        return $maxFileStorage - $totalUsed;
    }
    
    static function getAdminApiDetails()
    {
        $db = Database::getDatabase(true);
        $rs = $db->getRow('SELECT users.id, username, apikey FROM users LEFT JOIN user_level ON users.level_id = user_level.id WHERE user_level.level_type = \'admin\' AND status=\'active\' LIMIT 1');
        if(!$rs)
        {
            return false;
        }
        
        // create key if we don't have one
        if(strlen($rs['apikey']) == 0)
        {
            $newKey = MD5(microtime().$rs['id'].microtime());
            $db->query('UPDATE users SET apikey = '.$db->quote($newKey).' WHERE id = '.(int)$rs['id'].' LIMIT 1');
            $rs = $db->getRow('SELECT id, username, apikey FROM users LEFT JOIN user_level ON users.level_id = user_level.id WHERE user_level.level_type = \'admin\' AND status=\'active\' LIMIT 1');
        }
        
        return $rs;
    }
	
	static function getAvailableFileStoragePercentage($userId = null)
    {
        $fallback = 1024 * 1024 * 1024 * 1024 * 5; // 5TB fallback
        if ($userId === null)
        {
            $Auth    = Auth::getAuth();
            if ($Auth->loggedIn() == true)
            {
                $Auth   = Auth::getAuth();
                $userId = $Auth->id;
            }
            else
            {
                return 0;
            }
        }
        $maxFileStorage = self::getMaxFileStorage($userId);
        if (($maxFileStorage === null) || ($maxFileStorage === $fallback)) // unlimited users
        {
            return 100;
        }

        // calculate total user
        $totalUsed = file::getTotalActiveFileSizeByUser($userId);
        if ($totalUsed > $maxFileStorage)
        {
            return 100;
        }

        return 100-(ceil(($totalUsed/$maxFileStorage)*100));
    }
	
	static function hydrate($userDataArr)
    {
        $userObj = new User();
        foreach ($userDataArr AS $k => $v)
        {
            $userObj->$k = $v;
        }

        return $userObj;
    }
	
	static function buildProfileUrl($username)
	{
		return coreFunctions::getCoreSitePath().'/profile/'.$username.'/';
	}
}
