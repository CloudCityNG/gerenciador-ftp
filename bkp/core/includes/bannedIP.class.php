<?php

class bannedIP
{

    static function getBannedType()
    {
        $userIP = coreFunctions::getUsersIPAddress();
        $db     = Database::getDatabase(true);
        $row    = $db->getRow('SELECT banType FROM banned_ips WHERE ipAddress = ' . $db->quote($userIP).' LIMIT 1');
        if (!is_array($row))
        {
            return false;
        }
        
        return $row['banType'];
    }
    
    static function getBannedIPData($userIP = null)
    {
        if($userIP == null)
        {
            $userIP = coreFunctions::getUsersIPAddress();
        }
        
        $db     = Database::getDatabase(true);
        $row    = $db->getRow('SELECT * FROM banned_ips WHERE ipAddress = ' . $db->quote($userIP).' LIMIT 1');
        if (!is_array($row))
        {
            return false;
        }
        
        return $row;
    }
    
    static function clearExpiredBannedIps()
    {
        // get database
        $db     = Database::getDatabase(true);
        
        // load all expired
        $expired = $db->getRows('SELECT id, ipAddress, dateBanned, banType, banNotes, banExpiry FROM banned_ips WHERE banExpiry IS NOT NULL AND banExpiry < NOW()');
        if($expired)
        {
            // set to different log file
            log::setContext('banned_ips');
            
            foreach($expired AS $expiredIp)
            {
                // log the removal
                log::info('Expired banned ip: '.$expiredIp['ipAddress'].'. Date Banned: '.coreFunctions::formatDate($expiredIp['dateBanned']).'. Type: '.$expiredIp['banType'].'. Notes: '.(strlen($expiredIp['banNotes'])?$expiredIp['banNotes']:'-').'. Expiry: '.coreFunctions::formatDate($expiredIp['banExpiry']));
                
                // remove
                $db->query('DELETE FROM banned_ips WHERE id = '.(int)$expiredIp['id'].' LIMIT 1');
            }
            
            // revert logging
            log::revertContext();
        }
    }

}
