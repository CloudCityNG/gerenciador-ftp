<?php

/**
 * crossSiteAction class
 */
class crossSiteAction
{

    public static function setData($dataArr = array())
    {
        // get database
        $db = Database::getDatabase(true);

        // get auth
        $Auth = Auth::getAuth();

        // unique keys
		$key1 = hash('sha256', microtime() . rand(100000, 999999));
		$key2 = hash('sha256', microtime() . rand(100000, 999999) . $key1);

        // get user id
        $userId = '';
        if ($Auth->loggedIn())
        {
            $userId = $Auth->id;
        }

        // add session id to overall data
        $dataArr['_session_id'] = session_id();
		
		// add user id to overall data
        $dataArr['_logged_in_user_id'] = $userId;

        // insert into database
		$setData = $db->query('INSERT IGNORE INTO cross_site_action (key1, key2, data, date_added) VALUES (' . $db->quote($key1) . ',  ' . $db->quote($key2) . ', '.$db->quote(json_encode($dataArr)).', NOW())');

        return array('key1' => $key1, 'key2' => $key2);
    }

    public static function getData($key1, $key2)
    {
        // get database
        $db = Database::getDatabase(true);

        // load data from database
        $getData = $db->getValue('SELECT data FROM cross_site_action WHERE key1=' . $db->quote($key1) . ' AND key2=' . $db->quote($key2) . ' LIMIT 1');
        if (($getData !== false) && strlen($getData))
        {
            return json_decode($getData, true);
        }

        return false;
    }
    
    public static function deleteData($key1, $key2)
    {
        // get database
        $db = Database::getDatabase(true);

        // delete data from database
        $db->query('DELETE FROM cross_site_action WHERE key1=' . $db->quote($key1) . ' AND key2=' . $db->quote($key2) . ' LIMIT 1');
    }
    
    public static function purgeOldData()
    {
        // get database
        $db = Database::getDatabase(true);
        
        // delete anything older than 7 days
        $db->query('DELETE FROM cross_site_action WHERE date_added < DATE_SUB(NOW(), INTERVAL 7 DAY)');
    }

    public static function setAuthFromKeys($key1 = '', $key2 = '', $deleteCrossSiteAction = true)
    {
        // make sure we have some keys
        if((strlen($key1) == 0) && (strlen($key2) == 0))
        {
            return false;
        }
		
		// ignore this process if we're on the main server
		if(_CONFIG_SITE_HOST_URL == _CONFIG_CORE_SITE_HOST_URL)
		{
			return false;
		}
        
        // get auth
        $Auth = Auth::getAuth();

        // check for existing Auth, disabled, not needed as covered previously
        //if ($Auth->loggedIn() == true)
        //{
        //    return $Auth;
        //}
        
        // check referer
        $refDomain = strtolower(coreFunctions::getReffererDomainOnly());
        if (!$refDomain)
        {
            return false;
        }
        else
        {
            // make sure host is what we expect
            if($refDomain != strtolower(_CONFIG_CORE_SITE_HOST_URL))
            {
                return false;
            }
        }

        // try to load the data
        $dataArr = self::getData($key1, $key2);
        if (!$dataArr)
        {
            return false;
        }
        
        // remove database data
        if($deleteCrossSiteAction == true)
        {
            self::deleteData($key1, $key2);
        }
        
        // remove any old database data
        self::purgeOldData();

        // try to load the Auth object from the session id
		if (isset($dataArr['_session_id']) && (strlen($dataArr['_session_id']) > 0))
		{
			// if different to current session id and different server
			if(($dataArr['_session_id'] != session_id()) && (_CONFIG_SITE_HOST_URL != _CONFIG_CORE_SITE_HOST_URL))
			{
				// get database
				$db = Database::getDatabase(true);

				// get session from db
				$sessionData = $db->getValue('SELECT data FROM sessions WHERE id='.$db->quote($dataArr['_session_id']).' LIMIT 1');
				if ($sessionData)
				{
					// clear the existing session
					$Auth->logout();
					
					// link to existing session
					session_id($dataArr['_session_id']);
				
					// load our config settings
					$config = Config::getConfig();
					
					// initialize our session
					session_name($config->sessionName);

					// how long to keep sessions active before expiring
					session_set_cookie_params((int) SITE_CONFIG_SESSION_EXPIRY);

					session_start();

					// reapply session
					session_decode($sessionData);
					
					return $Auth->impersonate((int) $dataArr['_logged_in_user_id']);
				}
            }
		}

		// fall back on user id
        if (!isset($dataArr['_logged_in_user_id']) || ((int)$dataArr['_logged_in_user_id'] == 0))
        {
			if ($Auth->loggedIn() == true)
			{
			    $Auth->logout();
			}
			
            return false;
        }

        return $Auth->impersonate((int) $dataArr['_logged_in_user_id']);
    }
    
    public static function appendUrl($url, $dataArr = array())
    {
        // create tracker key for session
        $sessionKeys = self::setData($dataArr);
        if($sessionKeys == false)
        {
            return $url;
        }

        // prepare url
        if(strpos($url, '?') !== false)
        {
            $url .= '&';
        }
        else
        {
            $url .= '?';
        }
        
        // add keys to url
        return $url . 'csaKey1=' . $sessionKeys['key1'] . '&csaKey2=' . $sessionKeys['key2'];
    }

}
