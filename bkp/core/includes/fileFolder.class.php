<?php

class fileFolder
{
	public function getFolderUrl()
	{
		return WEB_ROOT.'/folder/'.(int)$this->id.'/'.$this->getSafeFoldernameForUrl();
	}
	
	public function getAlbumUrl()
	{
		return WEB_ROOT.'/album/'.(int)$this->id.'/'.$this->getSafeFoldernameForUrl();
	}
	
	public function getSafeFoldernameForUrl()
    {
        return str_replace(array(" ", "\"", "'", ";", "#", "%"), "_", strip_tags($this->folderName));
    }
	
	public function getCoverData()
	{
		$db   = Database::getDatabase();
		
		// get convert id
		$coverImageId = $this->coverImageId;
		if($coverImageId == null)
		{
			// load new and set in the db
			$coverImageData = $db->getRow('SELECT id, unique_hash FROM file WHERE folderId = '.(int)$this->id.' AND statusId = 1 AND extension IN('.file::getImageExtStringForSql().') LIMIT 1');
			if($coverImageData)
			{
				$this->setCoverId($coverImageData['id']);
			}
			
			// make sure we have the file hash
			$uniqueHash = $coverImageData['unique_hash'];
			if(strlen($uniqueHash) == 0)
			{
				$uniqueHash = file::createUniqueFileHash($coverImageData['id']);
			}
			
			return array('file_id'=>$coverImageData['id'], 'unique_hash'=>$uniqueHash);
		}

		// make sure cover image exists, update to new if not
		$coverImageData = $db->getRow('SELECT id, unique_hash FROM file WHERE id = '.(int)$coverImageId.' AND statusId = 1 AND extension IN('.file::getImageExtStringForSql().') LIMIT 1');
		if(!$coverImageData)
		{
			$coverImageData = $db->getRow('SELECT id, unique_hash FROM file WHERE folderId = '.(int)$this->id.' AND statusId = 1 AND extension IN('.file::getImageExtStringForSql().') LIMIT 1');
			if($coverImageData)
			{
				$this->setCoverId($coverImageData['id']);
			}
		}

		// make sure we have the file hash
		$uniqueHash = $coverImageData['unique_hash'];
		if(strlen($uniqueHash) == 0)
		{
			$uniqueHash = file::createUniqueFileHash($coverImageData['id']);
		}

		return array('file_id'=>$coverImageData['id'], 'unique_hash'=>$uniqueHash);
	}
	
	public function setCoverId($coverId)
	{
		$db   = Database::getDatabase();
		return $db->query('UPDATE file_folder SET coverImageId = '.(int)$coverId.' WHERE id = '.(int)$this->id.' LIMIT 1');
	}
	
	public function isPublic($publicId = 1)
	{
		return (($this->isPublic) >= (int)$publicId);
	}
	
	public function getOwner()
	{
		return UserPeer::loadUserById($this->userId);
	}
	
	public function getTotalViews()
	{
		$db   = Database::getDatabase();
        return (int)$db->getValue('SELECT SUM(visits) AS total FROM file WHERE folderId = ' . (int)$this->id);
	}
	
	public function getTotalLikes()
	{
		$db   = Database::getDatabase();
        return (int)$db->getValue('SELECT SUM(total_likes) AS total FROM file WHERE folderId = ' . (int)$this->id);
	}
	
    static function getFoldersByUser($userId)
    {
		// first check for folders in cache and load it if found
		if(cache::cacheExists('FOLDER_OBJECTS_BY_USERID_'.(int)$userId) == false)
		{
			$db   = Database::getDatabase(true);
			// SHARE CODE - DISABLED UNTIL THE NEXT RELEASE
			//$rows = $db->getRows('SELECT file_folder.*, file_folder_share.shared_with_user_id, file_folder_share.share_permission_level FROM file_folder LEFT JOIN file_folder_share ON file_folder.id = file_folder_share.folder_id WHERE file_folder.userId = ' . (int)$userId . ' OR file_folder_share.shared_with_user_id = '.(int)$userId.' ORDER BY folderName ASC');
			$rows = $db->getRows('SELECT file_folder.* FROM file_folder WHERE file_folder.userId = ' . (int)$userId . ' ORDER BY folderName ASC');
			
			// store cache
			cache::setCache('FOLDER_OBJECTS_BY_USERID_'.(int)$userId, $rows);
		}

		// get from cache
        return cache::getCache('FOLDER_OBJECTS_BY_USERID_'.(int)$userId);
    }

    static function loadById($id)
    {
        $db  = Database::getDatabase(true);
        $row = $db->getRow('SELECT * FROM file_folder WHERE id = ' . (int) $id);
        if (!is_array($row))
        {
            return false;
        }

        $folderObj = new fileFolder();
        foreach ($row AS $k => $v)
        {
            $folderObj->$k = $v;
        }

        return $folderObj;
    }

    /**
     * Remove by user
     */
    public function removeByUser()
    {
        // get db
        $db = Database::getDatabase(true);
        
        // get owner
        $accountId = $db->getValue('SELECT userId FROM file_folder WHERE id = '.(int)$this->id);
        if(!(int)$accountId)
        {
            return false;
        }

        // get all child ids
        return fileFolder::deleteFolder($this->id, $accountId);
    }
	
	/**
     * Create unique sharing url. Allow 'private' folders to be accessed without an account login.
     */
	public function createUniqueSharingUrl($userId = null, $permissionType = 'view')
	{
		// get db
        $db = Database::getDatabase();
		
		// check for existing
		if($userId)
		{
			$accessKey = $db->getValue('SELECT access_key FROM file_folder_share WHERE folder_id = ' . (int)$this->id . ' AND created_by_user_id = '.(int)$this->userId.' AND shared_with_user_id = '.(int)$userId.' LIMIT 1');
			if($accessKey)
			{
				return $this->getFolderUrl().'?sharekey='.$accessKey;
			}
		}
		
		// generate random accessKey
		$accessKey = coreFunctions::generateRandomString(64);
		
		// add to the database
		$db->query('INSERT INTO file_folder_share (folder_id, access_key, date_created, created_by_user_id, shared_with_user_id, share_permission_level) VALUES (' . (int)$this->id . ',  ' . $db->quote($accessKey) . ', NOW(), '.(int)$this->userId.', '.((int)$userId?(int)$userId:'null').', ' . $db->quote($permissionType) . ')');
		
		// return url
		return $this->getFolderUrl().'?sharekey='.$accessKey;
	}
	
	public function getAllSharedUsers()
	{
		// get db
        $db = Database::getDatabase();
		
		// get list of shares
		return $db->getRows('SELECT users.email, users.id AS user_id, file_folder_share.id, file_folder_share.share_permission_level FROM file_folder_share LEFT JOIN users ON file_folder_share.shared_with_user_id = users.id WHERE file_folder_share.shared_with_user_id IS NOT NULL AND file_folder_share.folder_id = ' . (int)$this->id);
	}
	
	public function removeUniqueSharingUrl($shareId)
	{
		// get db
        $db = Database::getDatabase();
		
		// remove the share
		return $db->query('DELETE FROM file_folder_share WHERE folder_id = ' . (int)$this->id . ' AND id = '.(int)$shareId.' LIMIT 1');
	}
    
    static function deleteFolder($folderId, $accountId)
    {
        // get db
        $db = Database::getDatabase(true);

        // load children
        $subFolders = $db->getRows('SELECT id FROM file_folder WHERE parentId = '.(int)$folderId.' AND userId = '.(int)$accountId);
        if($subFolders)
        {
            foreach($subFolders AS $subFolder)
            {
                self::deleteFolder($subFolder['id'], $accountId);
            }
        }
        
        $db->query('UPDATE file SET folderId = NULL WHERE folderId = '.(int)$folderId);
        $db->query('DELETE FROM file_folder WHERE id = '.(int)$folderId);
        
        return true;
    }

    static function loadAllByAccount($accountId)
    {
        return self::getFoldersByUser($accountId);
    }
    
    static function loadAllForSelect($accountId, $delimiter = '/')
    {
        $rs = array();
        $folders = self::loadAllByAccount($accountId);
        if($folders)
        {
            // first prepare local array for easy lookups
            $lookupArr = array();
            foreach($folders AS $folder)
            {
                $lookupArr[$folder{'id'}] = array('l'=>$folder['folderName'], 'p'=>$folder['parentId']);
            }
            
            // populate data
            foreach($folders AS $folder)
            {
                $folderLabelArr = array();
                $folderLabelArr[] = $folder['folderName'];
                $failSafe = 0;
                $parentId = $folder['parentId'];
                while(($parentId != NULL) && ($failSafe < 30))
                {
                    $failSafe++;
                    if(isset($lookupArr[$parentId]))
                    {
                        $folderLabelArr[] = $lookupArr[$parentId]['l'];
                        $parentId = $lookupArr[$parentId]['p'];
                    }
                }
                
                $folderLabelArr = array_reverse($folderLabelArr);
                $rs[$folder{'id'}] = implode($delimiter, $folderLabelArr);
            }
        }
        
        // make pretty
        natcasesort($rs);
        
        return $rs;
    }
	
	static function loadAllChildren($parentFolderId = null)
    {
        $db  = Database::getDatabase(true);
        $row = $db->getRows('SELECT * FROM file_folder WHERE parentId = ' . (int) $parentFolderId .' ORDER BY folderName');
        if (!is_array($row))
        {
            return false;
        }

        return $row;
    }
    
    static function loadAllPublicChildren($parentFolderId = null)
    {
        $db  = Database::getDatabase(true);
        $row = $db->getRows('SELECT * FROM file_folder WHERE parentId = ' . (int) $parentFolderId .' AND isPublic >= 1 ORDER BY folderName');
        if (!is_array($row))
        {
            return false;
        }

        return $row;
    }
    
    static function convertFolderPathToId($pathStr, $accountId)
    {
        $folderListing = self::loadAllForSelect($accountId, '/');
        if(COUNT($folderListing))
        {
            foreach($folderListing AS $k=>$folderListingItem)
            {
                if($folderListingItem == $pathStr)
                {
                    return $k;
                }
            }
        }
        
        return NULL;
    }

	static function getFolderCoverData($folderId)
	{
		$folder = fileFolder::loadById($folderId);
		if(!$folder)
		{
			return false;
		}

		return $folder->getCoverData();
	}
	
	/**
     * Hydrate folder data into a Folder object, save reloading from database is we already have the data
     * 
     * @param type $folderDataArr
     * @return Folder
     */
    static function hydrate($folderDataArr)
    {
        $folderObj = new fileFolder();
        foreach ($folderDataArr AS $k => $v)
        {
            $folderObj->$k = $v;
        }

        return $folderObj;
    }
	
	static function getTotalActivePublicFolders()
	{
		$db  = Database::getDatabase();
		
		return $db->getValue('SELECT COUNT(DISTINCT file_folder.id) FROM file_folder LEFT JOIN file ON file_folder.id = file.folderId WHERE file_folder.isPublic = 2 AND file_folder.accessPassword IS NULL AND file.isPublic != 0');
	}
}
