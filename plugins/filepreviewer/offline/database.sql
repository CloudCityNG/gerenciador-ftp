CREATE TABLE `plugin_filepreviewer_watermark` (`id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `file_name` VARCHAR(255) NOT NULL, `image_content` BLOB NOT NULL) ENGINE = MyISAM;

CREATE TABLE IF NOT EXISTS `plugin_filepreviewer_meta` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_id` int(11) NOT NULL,
  `width` int(8) NOT NULL,
  `height` int(8) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `file_id` (`file_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;

ALTER TABLE `plugin_filepreviewer_embed_token` ADD `ip_address` VARCHAR( 15 ) NULL;
ALTER TABLE `plugin_filepreviewer_meta` ADD `raw_data` TEXT NULL;

ALTER TABLE  `plugin_filepreviewer_meta` ADD  `date_taken` DATETIME NULL;

ALTER TABLE `plugin_filepreviewer_meta`  ADD `image_colors` VARCHAR(100) NULL DEFAULT NULL,  ADD `image_bg_color` VARCHAR(7) NULL DEFAULT NULL,  ADD INDEX (`image_bg_color`) ,  ADD FULLTEXT (`image_colors`);
