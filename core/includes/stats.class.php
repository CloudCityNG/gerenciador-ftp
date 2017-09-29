<?php

// Track your page with...
// Stats::track($some_file);

class Stats
{

    private static $me;

    private function __construct()
    {
        
    }

    public function getStats()
    {
        if (is_null(self::$me))
        {
            self::$me = new Stats();
        }

        return self::$me;
    }

    public static function track($file, $rs = '')
    {
        $db = Database::getDatabase();

        if (SITE_CONFIG_STATS_ONLY_COUNT_UNIQUE == 'yes')
        {
            // check whether the user has already visited today
            $sql = "SELECT * FROM stats WHERE ip = " . $db->quote(self::getIP()) . " AND file_id = " . $file->id . " AND DATE(download_date) = " . $db->quote(date('Y-m-d'));
            $row = $db->getRows($sql);
            if (COUNT($row))
            {
                return false;
            }
        }

        $file->updateVisitors();

        $dt               = date("Y-m-d H:i:s");
        $referer          = isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : '';
        $referer_is_local = self::refererIsLocal($referer);
        $ip               = self::getIP();
        $info             = self::browserInfo();
        $browser_family   = $info['browser'];
        $os               = $info['platform'];
        $user_agent       = $info['useragent'];
        $country          = self::getCountry($ip);
        $base_url         = self::getBaseUrl($referer);
		$user_id		  = null;
		$Auth = Auth::getAuth();
		if($Auth->loggedIn() === true)
		{
			$user_id = $Auth->id;
		}

        $sql  = "INSERT INTO stats (download_date, referer, referer_is_local, file_id, country, browser_family, os, ip, user_agent, base_url, user_id)
                    VALUES (:download_date, :referer, :referer_is_local, :file_id, :country, :browser_family, :os, :ip, :user_agent, :base_url, :user_id)";
        $vals = array(
            'download_date'    => $dt,
            'referer_is_local' => $referer_is_local,
            'referer'          => $referer,
            'file_id'          => $file->id,
            'country'          => $country,
            'ip'               => $ip,
            'browser_family'   => $browser_family,
            'os'               => $os,
            'user_agent'       => $user_agent,
            'base_url'         => $base_url,
			'user_id'          => $user_id);
        $db->query($sql, $vals);

        return true;
    }

    public static function refererIsLocal($referer = null)
    {
        if (is_null($referer))
        {
            $referer = isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : '';
        }

        if (!strlen($referer))
        {
            return 0;
        }

        $regex_host = (preg_quote($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : '');
        return (preg_match("!^https?://$regex_host!i", $referer) !== false) ? 1 : 0;
    }

    public static function getIP()
    {
        return coreFunctions::getUsersIPAddress();
    }

    // from http://us3.php.net/get_browser comments
    public static function browserInfo($a_browser = false, $a_version = false, $name = false)
    {
        $browser_list = 'msie firefox konqueror chrome safari netscape navigator opera mosaic lynx amaya omniweb avant camino flock seamonkey aol mozilla gecko';
        $user_browser = strtolower($_SERVER["HTTP_USER_AGENT"]);
        $this_version = $this_browser = '';

        $browser_limit = strlen($user_browser);
        foreach (explode(' ', $browser_list) as $row)
        {
            $row = ($a_browser !== false) ? $a_browser : $row;
            $n   = stristr($user_browser, $row);
            if (!$n || !empty($this_browser))
                continue;

            $this_browser = $row;
            $j            = strpos($user_browser, $row) + strlen($row) + 1;
            for (; $j <= $browser_limit; $j++)
            {
                $s = trim(substr($user_browser, $j, 1));
                $this_version .= $s;

                if ($s === '')
                    break;
            }
        }

        if ($a_browser !== false)
        {
            $ret = false;
            if (strtolower($a_browser) == $this_browser)
            {
                $ret = true;

                if ($a_version !== false && !empty($this_version))
                {
                    $a_sign = explode(' ', $a_version);
                    if (version_compare($this_version, $a_sign[1], $a_sign[0]) === false)
                    {
                        $ret = false;
                    }
                }
            }

            return $ret;
        }

        $this_platform = '';
        if (strpos($user_browser, 'linux'))
        {
            $this_platform = 'linux';
        }
        elseif (strpos($user_browser, 'macintosh') || strpos($user_browser, 'mac platform x'))
        {
            $this_platform = 'mac';
        }
        elseif (strpos($user_browser, 'windows') || strpos($user_browser, 'win32'))
        {
            $this_platform = 'windows';
        }

        if ($name !== false)
        {
            return $this_browser . ' ' . $this_version;
        }

        return array("browser"   => $this_browser,
            "version"   => $this_version,
            "platform"  => $this_platform,
            "useragent" => $user_browser
        );
    }

    public static function getCountry($ip)
    {
		// use new faster class
		$i = new ip2Country();
		$i->load($ip);
		$country = $i->countryCode;

        // fallback
        if ((strlen($country) == 0) || ($country == '?'))
        {
            $country = "ZZ";
        }

        return $country;
    }

    public static function getBaseUrl($url)
    {
        $url        = preg_replace("/^http:\/\//", "", $url);
        $url        = preg_replace("/^https:\/\//", "", $url);
        $url        = preg_replace("/^ftp:\/\//", "", $url);
        $url_tokens = explode("/", $url);

        return $url_tokens[0];
    }
	
	static function currentBrowserIsIE()
	{
		if (isset($_SERVER['HTTP_USER_AGENT']) &&
			(strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false))
		{
			return true;
		}
		
		return false;
	}
	
	static function currentDeviceIsAndroid()
	{
		$ua = $_SERVER['HTTP_USER_AGENT'];
		if (eregi('Android', $ua))
		{
			return true;
		}

		return false;
	}
    
    static function currentDeviceIsIos()
	{
		$ua = $_SERVER['HTTP_USER_AGENT'];
		if (eregi('iPhone', $ua))
		{
			return true;
		}
        
        if (eregi('iPad', $ua))
		{
			return true;
		}
        
        if (eregi('iPod', $ua))
		{
			return true;
		}

		return false;
	}
	
	static function isDownloadManager($userAgent)
	{
		$userAgent = trim($userAgent);
		if (strlen($userAgent) == 0)
		{
			return false;
		}

		$dlUserAgents    = 'Charon|DAP |DA |DC-Sakura|Download Demon|Download Druid|Download Express|';
		$dlUserAgents .= 'Download Master|Download Ninja|Download Wonder|DownloadDirect|FDM |FileHound|';
		$dlUserAgents .= 'FlashGet|FreshDownload|Gamespy_Arcade|GetRight|GetRightPro|Go!Zilla|HiDownload|';
		$dlUserAgents .= 'HTTPResume|ICOO Loader|iGetter|Iria/|JetCar|JDownloader|Kontiki Client|LeechGet|';
		$dlUserAgents .= 'LightningDownload|Mass Downloader|MetaProducts Download Express|MyGetRight|NetAnts|';
		$dlUserAgents .= 'NetPumper|Nitro Downloader|Octopus|PuxaRapido|RealDownload|SmartDownload|SpeedDownload|';
		$dlUserAgents .= 'SQ Webscanner|Stamina|Star Downloader|StarDownloader|WebReaper|WebStripper|';
		$dlUserAgents .= 'WinGet|WWWOFFLE|wxDownload Fast';
		$dlUserAgentsArr = explode('|', $dlUserAgents);
		foreach ($dlUserAgentsArr AS $dlUserAgent)
		{
			if (substr($userAgent, 0, strlen($dlUserAgent)) == $dlUserAgent)
			{
				return true;
			}
		}

		return false;
	}

}
