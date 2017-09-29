<?php

class OrderPeer
{

    // Singleton object. Leave $me alone.
    private static $me;

	// *************************************************
	// deprecated, use createByPackage() instead
	// *************************************************
    static function create($user_id, $payment_hash, $days, $amount, $upgradeFileId)
    {
        $upgradeUserId = null;
        if ((isset($_SESSION['plugin_rewards_aff_user_id'])) && ($user_id != (int) $_SESSION['plugin_rewards_aff_user_id']))
        {
            $upgradeUserId = (int)$_SESSION['plugin_rewards_aff_user_id'];
        }
		
		// check for cookie
		if(isset($_COOKIE['source_aff_id']))
		{
			if(((int)$_COOKIE['source_aff_id']) && ((int)$_COOKIE['source_aff_id'] != (int)$_SESSION['plugin_rewards_aff_user_id']))
			{
				$upgradeUserId = (int)$_COOKIE['source_aff_id'];
			}

			// remove cookie
			unset($_COOKIE['source_aff_id']);
		}

        $dbInsert = new DBObject("premium_order",
                        array("user_id", "payment_hash", "days",
                            "amount", "order_status", "date_created", "upgrade_file_id", "upgrade_user_id"));
        $dbInsert->user_id = $user_id;
        $dbInsert->payment_hash = $payment_hash;
        $dbInsert->days = $days;
        $dbInsert->amount = $amount;
        $dbInsert->order_status = 'pending';
        $dbInsert->date_created = date("Y-m-d H:i:s", time());
        $dbInsert->upgrade_file_id = $upgradeFileId;
        if((int)$upgradeFileId)
        {
            // lookup user
            $db   = Database::getDatabase();
            $upgradeUserId = (int)$db->getValue('SELECT userId FROM file WHERE id='.(int)$upgradeFileId.' LIMIT 1');
        }
        
        $dbInsert->upgrade_user_id = $upgradeUserId;
        if ($dbInsert->insert())
        {
            return $dbInsert;
        }

        return false;
    }
	
	static function createByPackageId($user_id, $user_level_pricing_id, $upgradeFileId)
    {
        $upgradeUserId = null;
        if ((isset($_SESSION['plugin_rewards_aff_user_id'])) && ($user_id != (int) $_SESSION['plugin_rewards_aff_user_id']))
        {
            $upgradeUserId = (int)$_SESSION['plugin_rewards_aff_user_id'];
        }
		
		// check for cookie
		if(isset($_COOKIE['source_aff_id']))
		{
			if((int)$_COOKIE['source_aff_id'])
			{
				$upgradeUserId = (int)$_COOKIE['source_aff_id'];
			}
		}
		
		// setup database
		$db = Database::getDatabase();
		
		// lookup days and amount based on $user_level_pricing_id
		$price = $db->getRow('SELECT id, pricing_label, period, price FROM user_level_pricing WHERE id = '.(int)$user_level_pricing_id.' LIMIT 1');
		if(!$price)
		{
			return false;
		}
		$amount = $price['price'];
		$days = (int)coreFunctions::convertStringDatePeriodToDays($price['period']);
		
		// load username for later
		$username = $db->getValue('SELECT username FROM users WHERE id = '.$user_id.' LIMIT 1');
		if(!$username)
		{
			return false;
		}
		
		// create order hash for tracking
		$payment_hash = MD5(microtime() . $user_id);

		// add order to the database
        $dbInsert = new DBObject("premium_order",
                        array("user_id", "payment_hash", "user_level_pricing_id", "days",
                            "amount", "description", "order_status", "date_created", "upgrade_file_id", "upgrade_user_id"));
        $dbInsert->user_id = $user_id;
        $dbInsert->payment_hash = $payment_hash;
		$dbInsert->user_level_pricing_id = $user_level_pricing_id;
        $dbInsert->days = $days;
        $dbInsert->amount = $amount;
		$dbInsert->description = substr($price['pricing_label'] . ' '.t('premium_for', 'Premium for').' ' . $username, 0, 100);
        $dbInsert->order_status = 'pending';
        $dbInsert->date_created = date("Y-m-d H:i:s", time());
        $dbInsert->upgrade_file_id = $upgradeFileId;
        if((int)$upgradeFileId)
        {
            // lookup user
            $db   = Database::getDatabase();
            $upgradeUserId = (int)$db->getValue('SELECT userId FROM file WHERE id='.(int)$upgradeFileId.' LIMIT 1');
        }
        
        $dbInsert->upgrade_user_id = $upgradeUserId;
        if ($dbInsert->insert())
        {
            return $dbInsert;
        }

        return false;
    }

    static function loadByPaymentTracker($paymentHash)
    {
        $orderObj = new Order();
        $orderObj->select($paymentHash, 'payment_hash');
        if (!$orderObj->ok())
        {
            return false;
        }

        return $orderObj;
    }

}
