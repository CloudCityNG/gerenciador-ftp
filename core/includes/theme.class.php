<?php

abstract class Theme
{

    abstract function getThemeDetails();

    public function install()
    {
        // get theme details
        $themeDetails = $this->getThemeDetails();

        // update reference in database
        $db = Database::getDatabase();
        $db->query('UPDATE theme SET is_installed = 1 WHERE folder_name = :folder_name', array('folder_name' => $themeDetails['folder_name']));

        return true;
    }

    public function uninstall()
    {
        // get theme details
        $themeDetails = $this->getThemeDetails();

        // update reference in database
        $db = Database::getDatabase();
        $db->query('UPDATE theme SET is_installed = 0 WHERE folder_name = :folder_name', array('folder_name' => $themeDetails['folder_name']));

        return true;
    }

}