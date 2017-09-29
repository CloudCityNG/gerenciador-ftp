ALTER TABLE  `file_folder` ADD  `watermarkPreviews` TINYINT( 1 ) NOT NULL AFTER  `coverImageId` ,
ADD  `showDownloadLinks` TINYINT( 1 ) NOT NULL AFTER  `watermarkPreviews`;

ALTER TABLE  `language` ADD  `language_code` VARCHAR( 5 ) NULL DEFAULT NULL;
UPDATE language SET `language_code` = `flag`;

INSERT INTO site_config VALUES (NULL, 'google_translate_api_key', '', 'Google Translate API key. Optional but needed if you use the automatic language translation tool within the admin area.', '', 'string', 'Language');

ALTER TABLE  `file_server` ADD  `serverAccess` TEXT NULL;

