<?php

class Auth
{

    // Singleton object. Leave $me alone.
    private static $me;
    public $id;
    public $username;
    public $level_id;
    public $user; // DBObject User object (if available)

    // Call with no arguments to attempt to restore a previous logged in session
    // which then falls back to a guest user (which can then be logged in using
    // $this->login($username, $rawPassword). Or pass a user_id to simply login that user. The
    // $seriously is just a safeguard to be certain you really do want to blindly
    // login a user. Set it to true.

    private function __construct($userToImpersonate = null)
    {
        $this->id       = null;
        $this->username = null;
        $this->level_id = 0;
		$this->package_id = 0;
        $this->level    = UserPeer::getLevelLabel($this->level_id);
        $this->user     = null;

        if (class_exists('User') && (is_subclass_of('User', 'DBObject')))
        {
            $this->user = new User();
        }

        if (!is_null($userToImpersonate))
        {
            return $this->impersonate($userToImpersonate);
        }

        if ($this->attemptSessionLogin())
        {
            return;
        }
    }

    /**
     * Standard singleton
     * @return Auth
     */
    public static function getAuth($userToImpersonate = null)
    {
        if (is_null(self::$me))
        {
            self::$me = new Auth($userToImpersonate);
        }

        return self::$me;
    }

    // You'll typically call this function when a user logs in using
    // a form. Pass in their username and password.
    // Takes a username and a *plain text* password
    public function login($username, $rawPassword, $fromLoginForm = false)
    {
        $rs = $this->convertPassword($username, $rawPassword);
        if ($rs == false)
        {
            return false;
        }

        return $this->attemptLogin($username, $rawPassword, false, $fromLoginForm);
    }

    // manage convertions to sha256, this code is only for migration
    public function convertPassword($username, $rawPassword)
    {
        // get database
        $db = Database::getDatabase();

        // check for existing user
        $user = $db->getRow('SELECT id, password FROM users WHERE username = ' . $db->quote($username));
        if ($user === false)
        {
            return false;
        }

        // see if it matches the one entered
        if ($user['password'] == md5($rawPassword))
        {
            // create new password
            $sha256Password = Password::createHash($rawPassword);

            // update user with new
            $db->query('UPDATE users SET password = :password WHERE id = :id', array('password' => $sha256Password, 'id'       => $user['id']));
        }

        return true;
    }

    public function logout()
    {
        $Config = Config::getConfig();

        $this->id       	= null;
        $this->username 	= null;
        $this->level_id 	= 0;
		$this->package_id 	= 0;
        $this->level    	= 'guest';
        $this->user     	= null;

        if (class_exists('User') && (is_subclass_of('User', 'DBObject')))
        {
            $this->user = new User();
        }

        $_SESSION['user'] = '';
        unset($_SESSION['user']);
		if(isset($_SESSION['_old_user']))
		{
			// revert old session if this is a logout of impersonate user
			$_SESSION['user'] = $_SESSION['_old_user'];
			$_SESSION['_old_user'] = '';
			unset($_SESSION['_old_user']);
			
			// redirect to file manager as old user
			coreFunctions::redirect(WEB_ROOT.'/account_home.html');
		}
		else
		{
			// clear session
			session_destroy();
			setcookie('spf', '.', time() - 3600, '/', $Config->authDomain);
		}
    }

    // Is a user logged in? This was broken out into its own function
    // in case extra logic is ever required beyond a simple bool value.
    public function loggedIn()
    {
        return $this->level_id > 0;
    }

    // Helper function that redirects away from 'admin only' pages
    public function requireAdmin()
    {
        // check for login attempts
        if (isset($_REQUEST['username']) && isset($_REQUEST['password']))
        {
            $this->login($_REQUEST['username'], $_REQUEST['password']);
        }

        // ensure it's an admin user
        $this->requireAccessLevel(20, ADMIN_WEB_ROOT . "/login.php?error=1");
    }

    // Helper function that redirects away from 'member only' pages
    public function requireUser($url)
    {
        $this->requireAccessLevel(1, $url);
    }

    /*
     * Function to handle access rights and minimum permission levels for access.
     * The higher the number the greater the permission requirement. See the
     * database table called 'user_level' for the permission level_ids.
     * 
     * @param type $level
     */

    public function requireAccessLevel($minRequiredLevel = 0, $redirectOnFailure = 'login.php')
    {
        // check for login attempts
        if (isset($_REQUEST['username']) && isset($_REQUEST['password']))
        {
            $this->login($_REQUEST['username'], $_REQUEST['password']);
        }
		
		$userType = UserPeer::getLevelTypeFromPackageId($this->package_id);
		switch($minRequiredLevel)
		{
			case 0:
				if(in_array($userType, array('moderator', 'free', 'paid', 'admin', 'guest', 'nonuser')))
				{
					return true;
				}
				break;
			case 1:
				if(in_array($userType, array('moderator', 'free', 'paid', 'admin')))
				{
					return true;
				}
				break;
			case 20:
				if(in_array($userType, array('admin')))
				{
					return true;
				}
				break;
			case 10:
				if(in_array($userType, array('moderator', 'admin')))
				{
					return true;
				}
				break;
			case 2:
				if(in_array($userType, array('moderator', 'paid', 'admin')))
				{
					return true;
				}
				break;
		}

        if (strlen($redirectOnFailure))
        {
            coreFunctions::redirect($redirectOnFailure);
        }

        return false;
    }

    public function hasAccessLevel($minRequiredLevel = 0)
    {
        return $this->requireAccessLevel($minRequiredLevel, null);
    }

    // Login a user simply by passing in their username or id. Does
    // not check against a password. Useful for allowing an admin user
    // to temporarily login as a standard user for troubleshooting.
    // Takes an id or username
    public function impersonate($userToImpersonate)
    {
        $db     = Database::getDatabase();
        if (is_int($userToImpersonate))
        {
            $row = $db->getRow('SELECT * FROM users WHERE id = ' . (int)$userToImpersonate.' LIMIT 1');
        }
        else
        {
            $row = $db->getRow('SELECT * FROM users WHERE username = ' . $db->quote($userToImpersonate).' LIMIT 1');
        }

        if (is_array($row))
        {
            $this->id             = $row['id'];
            $this->username       = $row['username'];
            $this->email          = $row['email'];
			$this->package_id     = $row['level_id'];
            $this->level_id       = UserPeer::getLevelIdFromPackageId($this->package_id);
            $this->level          = UserPeer::getLevelLabel($this->level_id);
            $this->paidExpiryDate = $row['paidExpiryDate'];
            $this->paymentTracker = $row['paymentTracker'];

            // load any additional user info if DBObject and User are available
            $this->user     	  = new User();
            $this->user->id       = $row['id'];
            $this->user->load($row);
			
			// store in session
            $this->storeSessionData();

            return true;
        }

        return false;
    }

    // Attempt to login using data stored in the current session
    private function attemptSessionLogin()
    {
        if (isset($_SESSION['user']))
        {
            $sessionAuth = unserialize($_SESSION['user']);
            if (is_object($sessionAuth))
            {
                foreach ($sessionAuth AS $k => $v)
                {
                    $this->$k = $v;
                }
            }

            return true;
        }

        return false;
    }

    // The function that actually verifies an attempted login and
    // processes it if successful.
    // Takes a username and a raw password
    public function attemptLogin($username, $rawPassword, $sessionLogin = false, $fromLoginForm = false)
    {
        $db     = Database::getDatabase();
        $Config = Config::getConfig();

        // We SELECT * so we can load the full user record into the user DBObject later
        $row = $db->getRow('SELECT * FROM users WHERE username = ' . $db->quote($username).' LIMIT 1');
        if ($row === false)
        {
            // log failure
            Auth::logFailedLoginAttempt(coreFunctions::getUsersIPAddress(), $loginUsername);

            return false;
        }

        // validate password
        if ($sessionLogin == false)
        {
            if (Password::validatePassword($rawPassword, $row['password']) === false)
            {
                // log failure
                Auth::logFailedLoginAttempt(coreFunctions::getUsersIPAddress(), $loginUsername);

                return false;
            }
        }
        
        // check for mcrypt, required for login
        if(!function_exists('mcrypt_create_iv'))
        {
            return false;
        }

        // make sure account is active
        if ($row['status'] != "active")
        {
            return false;
        }
        else
        {
            // check user isn't banned from logging in
            $bannedIp = bannedIP::getBannedIPData();
            if ($bannedIp)
            {
                if ($bannedIp['banType'] == 'Login')
                {
                    return false;
                }
            }
        }
        
        // stop account sharing
        if($fromLoginForm == true)
        {
            Auth::clearSessionByUserId($row['id']);
        }

        $this->id             = $row['id'];
        $this->username       = $row['username'];
        $this->email          = $row['email'];
        $this->package_id     = $row['level_id'];
		$this->level_id       = UserPeer::getLevelIdFromPackageId($this->package_id);
        $this->level          = UserPeer::getLevelLabel($this->level_id);
        $this->paidExpiryDate = $row['paidExpiryDate'];
        $this->paymentTracker = $row['paymentTracker'];

        // load any additional user info if DBObject and User are available
        $this->user     = new User();
        $this->user->id = $row['id'];
        $this->user->load($row);

        // update lastlogindate
        $iPAddress = coreFunctions::getUsersIPAddress();
        if ($sessionLogin == false)
        {
            $db->query('UPDATE users SET lastlogindate = NOW(), lastloginip = :ip WHERE id = :id LIMIT 1', array('ip' => $iPAddress, 'id' => $this->id));
        }

        // remove any failed logins for the IP address
        Auth::clearAllLoginAttemptsForIp($iPAddress);

        // log IP address for login
        if($fromLoginForm == true)
        {
            Auth::logSuccessfulLogin($this->id, $iPAddress);
        }
		
		// delete old session data
		$this->purgeOldSessionData();

        // setup session
        $this->storeSessionData();
        if (($sessionLogin == false) && (SITE_CONFIG_LANGUAGE_USER_SELECT_LANGUAGE == 'yes'))
        {
            $this->setSessionLanguage();
        }

        return true;
    }
	
	public function purgeOldSessionData()
	{
		// get database
		$db = Database::getDatabase();
		
		// delete old session data
		$db->query('DELETE FROM `sessions` WHERE `updated_on` < :updated_on', array('updated_on' => time() - (60*60*24*2))); // 2 days
	}
	
	// reload session, used for account upgrades
	public function reloadSession()
	{
		if($this->id == 0)
		{
			return false;
		}
		
		// reload the user object
		$this->user = UserPeer::loadUserById($this->id);
		
		// update the auth object
		$this->id             = $this->user->id;
        $this->username       = $this->user->username;
        $this->email          = $this->user->email;
        $this->package_id     = $this->user->level_id;
		$this->level_id       = UserPeer::getLevelIdFromPackageId($this->package_id);
        $this->level          = UserPeer::getLevelLabel($this->level_id);
		
		// setup session
        $this->storeSessionData();
	}

    public function getAccountScreenName()
    {
        return $this->user->getAccountScreenName();
    }

    private function setSessionLanguage()
    {
        $db           = Database::getDatabase();
        $languageName = $db->getValue("SELECT languageName FROM language WHERE isActive = 1 AND id = " . $db->escape($this->user->languageId) . " LIMIT 1");
        if ($languageName)
        {
            $_SESSION['_t'] = $languageName;
        }
    }

    // stores current object in session
    private function storeSessionData()
    {
        $_SESSION['user'] = serialize($this);
    }

    private function createHashedPassword($rawPassword)
    {
        return MD5($rawPassword);
    }

    public static function logFailedLoginAttempt($ipAddress, $loginUsername = '')
    {
        // clear anything older than 24 hours
        self::clearOldLoginAttempts();

        // get database
        $db = Database::getDatabase();

        // add failed login attempt
        $dbInsert             = new DBObject("login_failure", array("ip_address", "date_added", "username"));
        $dbInsert->ip_address = $ipAddress;
        $dbInsert->date_added = coreFunctions::sqlDateTime();
        $dbInsert->username   = $loginUsername;
        $dbInsert->insert();

        // block IP address if greater than x failed logins
        if ((int) SITE_CONFIG_SECURITY_BLOCK_IP_LOGIN_ATTEMPTS > 0)
        {
            $failedAttempts = (int) $db->getValue('SELECT COUNT(id) AS total FROM login_failure WHERE ip_address = ' . $db->quote($ipAddress));
            if ($failedAttempts >= SITE_CONFIG_SECURITY_BLOCK_IP_LOGIN_ATTEMPTS)
            {
                // add IP address to block list
                $dbInsert             = new DBObject("banned_ips", array("ipAddress", "banType", "banNotes", "dateBanned", "banExpiry"));
                $dbInsert->ipAddress  = $ipAddress;
                $dbInsert->banType    = 'Login';
                $dbInsert->banNotes   = 'Banned after too many failed logins.';
                $dbInsert->dateBanned = coreFunctions::sqlDateTime();
                $dbInsert->banExpiry  = date('Y-m-d H:i:s', strtotime('+24 hours'));
                $dbInsert->insert();
            }
        }
    }

    public static function clearOldLoginAttempts()
    {
        // get database
        $db = Database::getDatabase();

        // clear anything older than 24 hours
        $db->query('DELETE FROM login_failure WHERE date_added < DATE_SUB(NOW(), INTERVAL 24 HOUR)');
    }

    public static function clearAllLoginAttemptsForIp($ipAddress)
    {
        // get database
        $db = Database::getDatabase();

        // clear anything older than 24 hours
        $db->query('DELETE FROM login_failure WHERE ip_address = ' . $db->quote($ipAddress));
    }

    public static function logSuccessfulLogin($userId, $ipAddress)
    {
        // clear anything older than 1 month
        self::clearOldSuccessfulLogins();

        // get database
        $db = Database::getDatabase();

        // try to find country code based on IP address
        $countryCode = Stats::getCountry($ipAddress);
        if (($countryCode == 'unknown') || ($countryCode == 'ZZ') || (!$countryCode))
        {
            $countryCode = '';
        }
        $countryCode = substr($countryCode, 0, 2);

        // add failed login attempt
        $dbInsert               = new DBObject("login_success", array("ip_address", "date_added", "user_id", "country_code"));
        $dbInsert->ip_address   = $ipAddress;
        $dbInsert->date_added   = coreFunctions::sqlDateTime();
        $dbInsert->user_id      = $userId;
        $dbInsert->country_code = $countryCode;
        $dbInsert->insert();
    }

    public static function clearOldSuccessfulLogins()
    {
        // get database
        $db = Database::getDatabase();

        // clear anything older than 1 month
        $db->query('DELETE FROM login_success WHERE date_added < DATE_SUB(NOW(), INTERVAL 1 MONTH)');
    }
    
    public static function clearSessionByUserId($userId)
    {
        // deletes any existing sessions for the same user id
        if ((SITE_CONFIG_PREMIUM_USER_BLOCK_ACCOUNT_SHARING == 'yes') && coreFunctions::currentIsMainSite())
        {
            $db = Database::getDatabase();
            $db->query('DELETE FROM sessions WHERE user_id = ' . (int) $userId);
        }
    }
	
	public static function paymentCompleteReLogin()
	{
		
	}

}
