<?php

class DBSession
{

    public static function register()
    {
        ini_set('session.save_handler', 'user');
        session_set_save_handler(array('DBSession', 'open'), array('DBSession', 'close'), array('DBSession', 'read'), array('DBSession', 'write'), array('DBSession', 'destroy'), array('DBSession', 'gc'));
        
        // the following prevents unexpected effects when using objects as save handlers
		register_shutdown_function('session_write_close');
    }

    public static function open()
    {
        $db = Database::getDatabase(true);
        return $db->isConnected();
    }

    public static function close()
    {
        return true;
    }

    public static function read($id)
    {
        $db = Database::getDatabase(true);
        $db->query('SELECT `data` FROM `sessions` WHERE `id` = :id', array('id' => $id));

        return $db->hasRows() ? $db->getValue() : '';
    }

    public static function write($id, $data)
    {
        // load user id if the user is logged in
        $user_id = NULL;
        $Auth = Auth::getAuth();
        if($Auth->loggedIn())
        {
            $user_id = $Auth->id;
        }
        
        $db = Database::getDatabase(true);
        $db->query('INSERT INTO `sessions` (`id`, `data`, `updated_on`, `user_id`) values (:id, :data, :updated_on, :user_id) ON DUPLICATE KEY UPDATE data=:data, updated_on=:updated_on, user_id=:user_id', array('id'         => $id, 'data'       => $data, 'updated_on' => time(), 'user_id' => $user_id));

        return ($db->affectedRows() == 1);
    }

    public static function destroy($id)
    {
        $db = Database::getDatabase(true);
        $db->query('DELETE FROM `sessions` WHERE `id` = :id', array('id' => $id));
        return ($db->affectedRows() == 1);
    }

    /*
     * $max set in php.ini with session.gc-maxlifetime
     */
    public static function gc($max)
    {
        // max override due to issues on certain server installs
        $max = 60*60*24*14;

        $db = Database::getDatabase(true);
        $db->query('DELETE FROM `sessions` WHERE `updated_on` < :updated_on', array('updated_on' => time() - $max));
        return true;
    }
}
