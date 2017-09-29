<?php

// man navigation items
$headerNavigation = array();

$headerNavigation['login'] = array(
    'link_url' => coreFunctions::getCoreSitePath() . '/login.' . SITE_CONFIG_PAGE_EXTENSION,
    'link_text' => t('login', 'login'),
    'link_key' => 'login',
    'user_level_id' => array(0),
    'position' => 400
);

// logged in users
$headerNavigation['your_files'] = array(
    'link_url' => coreFunctions::getCoreSitePath() . '/index.' . SITE_CONFIG_PAGE_EXTENSION,
    'link_text' => t('your_folders', 'your folders'),
    'link_key' => 'your_files',
    'user_level_id' => range(1, 20),
    'position' => 30
);

// logged in users
$headerNavigation['settings'] = array(
    'link_url' => coreFunctions::getCoreSitePath() . '/account_edit.' . SITE_CONFIG_PAGE_EXTENSION,
    'link_text' => t('file_manager_account_settings', 'Account Settings'),
    'link_key' => 'settings',
    'user_level_id' => range(1, 20),
    'position' => 999
);
