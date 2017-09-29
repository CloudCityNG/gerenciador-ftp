UPDATE language SET `language_code` = `flag`;

INSERT INTO site_config VALUES (NULL, 'google_translate_api_key', '', 'Google Translate API key. Optional but needed if you use the automatic language translation tool within the admin area.', '', 'string', 'Language');

INSERT INTO `site_config` (`id`, `config_key`, `config_value`, `config_description`, `availableValues`, `config_type`, `config_group`) VALUES (NULL, 'blocked_filename_keywords', 'yetishare|wurlie|reservo', 'Any filenames with the keywords listed here will be blocked from uploading. Keep in mind that this is a partial string search, so blocking the word "exe" will also block the word "exercise". Pipe separated list. i.e. word1|word2|word3', '', 'string', 'File Uploads');

ALTER TABLE  `site_config` CHANGE  `config_value`  `config_value` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
INSERT INTO `site_config` (`id`, `config_key`, `config_value`, `config_description`, `availableValues`, `config_type`, `config_group`) VALUES (NULL, 'system_plugin_config_cache', '', 'Used internally by the system to store a cache of the plugin settings.', '', 'string', 'System');
INSERT INTO `site_config` (`id`, `config_key`, `config_value`, `config_description`, `availableValues`, `config_type`, `config_group`) VALUES (NULL, 'system_theme_config_cache', '', 'Used internally by the system to store a cache of the theme settings.', '', 'string', 'System');

CREATE TABLE  `file_block_hash` (`id` INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,`file_hash` VARCHAR( 32 ) NOT NULL ,`date_created` DATETIME NOT NULL ,UNIQUE (`file_hash`)) ENGINE = MYISAM ;

INSERT INTO `site_config` (`id`, `config_key`, `config_value`, `config_description`, `availableValues`, `config_type`, `config_group`) VALUES(NULL, 'uploads_block_all', 'no', 'Whether to block all uploads on your site, apart from the admin user. Useful as a temporary setting for site maintenance', '["yes", "no"]', 'select', 'File Uploads');
INSERT INTO `site_config` (`id`, `config_key`, `config_value`, `config_description`, `availableValues`, `config_type`, `config_group`) VALUES(NULL, 'downloads_block_all', 'no', 'Whether to block all downloads on your site, apart from the admin user. Useful as a temporary setting for site maintenance', '["yes", "no"]', 'select', 'File Downloads');

ALTER TABLE  `language_content` ADD  `is_locked` INT( 1 ) NOT NULL DEFAULT  '0';
UPDATE `language_content` SET is_locked = 1;

INSERT INTO `plugin` (`id`, `plugin_name`, `folder_name`, `plugin_description`, `is_installed`, `date_installed`, `plugin_settings`, `plugin_enabled`, `load_order`) VALUES(28, 'Social Login', 'sociallogin', 'Login with your Facebook, Twitter or Google+ Account.', 1, '0000-00-00 00:00:00', 0x7b2266616365626f6f6b5f656e61626c6564223a302c2266616365626f6f6b5f6170706c69636174696f6e5f6964223a22222c2266616365626f6f6b5f6170706c69636174696f6e5f736563726574223a22222c22747769747465725f656e61626c6564223a302c22747769747465725f6170706c69636174696f6e5f6b6579223a22222c22747769747465725f6170706c69636174696f6e5f736563726574223a22222c22676f6f676c655f656e61626c6564223a302c22676f6f676c655f6170706c69636174696f6e5f6964223a22222c22676f6f676c655f6170706c69636174696f6e5f736563726574223a22222c22616f6c5f656e61626c6564223a302c22696e7374616772616d5f656e61626c6564223a302c22696e7374616772616d5f6170706c69636174696f6e5f6b6579223a22222c22696e7374616772616d5f6170706c69636174696f6e5f736563726574223a22222c22666f75727371756172655f656e61626c6564223a302c22666f75727371756172655f6170706c69636174696f6e5f6964223a22222c22666f75727371756172655f6170706c69636174696f6e5f736563726574223a22222c226c696e6b6564696e5f656e61626c6564223a302c226c696e6b6564696e5f6170706c69636174696f6e5f6b6579223a22222c226c696e6b6564696e5f6170706c69636174696f6e5f736563726574223a22227d, 1, 999);

INSERT INTO `site_config` VALUES(null, 'security_block_register_email_domain', '', 'Block email address domains from registering. Comma separated list of domains. i.e. exampledomain.com,exampledomain2.com,etc', '', 'textarea', 'Security');

INSERT INTO `site_config` (`id`, `config_key`, `config_value`, `config_description`, `availableValues`, `config_type`, `config_group`) VALUES (NULL, 'reserved_usernames', 'admin|administrator|localhost|support|billing|sales|payments', 'Any usernames listed here will be blocked from the main registration. Pipe separated list.', '', 'string', 'Default');

INSERT INTO `site_config` (`id`, `config_key`, `config_value`, `config_description`, `availableValues`, `config_type`, `config_group`) VALUES (NULL, 'enable_user_registration', 'no', 'Whether to enable user registration on the site.', '["yes","no"]', 'select', 'Site Options');

INSERT INTO `site_config` (`id`, `config_key`, `config_value`, `config_description`, `availableValues`, `config_type`, `config_group`) VALUES (NULL, 'register_form_show_captcha', 'no', 'Whether to display the captcha on the site registration form.', '["yes","no"]', 'select', 'Captcha');


ALTER TABLE  `file_folder_share` ADD  `shared_with_user_id` INT( 11 ) NULL AFTER  `created_by_user_id`;
ALTER TABLE  `file_folder_share` ADD  `share_permission_level` ENUM(  'view',  'upload_download',  'all' ) NOT NULL DEFAULT  'view';