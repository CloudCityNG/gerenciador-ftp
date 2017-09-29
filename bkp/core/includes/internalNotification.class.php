<?php

class internalNotification
{
	static function add($userId, $content, $type = 'entypo-info', $href_url = null, $onclick = null)
	{
		// get database
		$db = Database::getDatabase();
		
		// insert data
		$sql  = "INSERT INTO internal_notification (to_user_id, date_added, content, href_url, onclick, notification_icon)
                    VALUES (:to_user_id, NOW(), :content, :href_url, :onclick, :notification_icon)";
        $vals = array(
            'to_user_id' => $userId,
            'content' => substr($content, 0, 255),
			'href_url' => substr($href_url, 0, 255),
			'onclick' => substr($onclick, 0, 255),
            'notification_icon' => $type);
        $db->query($sql, $vals);
		
		return true;
	}
	
	static function loadRecentByUser($userId)
	{
		// get database
		$db = Database::getDatabase();
		
		// load the past 14 days
		$rows = $db->getRows('SELECT * FROM internal_notification WHERE to_user_id = '.(int)$userId.' AND date_added >= DATE_SUB(NOW(), INTERVAL 14 DAY) ORDER BY date_added DESC');
		
		return $rows;
	}
	
	static function markAllReadByUserId($userId)
	{
		// get database
		$db = Database::getDatabase();
		
		// load the past 14 days
		$db->query('UPDATE internal_notification SET is_read = 1 WHERE to_user_id = '.(int)$userId);
		
		return true;
	}
}
