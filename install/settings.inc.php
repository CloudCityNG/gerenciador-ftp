<?php

error_reporting(0);

// for version number
include_once('config.tpl');

// *** check for PHP minimum version number  -
define("EI_CHECK_PHP_MINIMUM_VERSION", true);
define("EI_PHP_MINIMUM_VERSION", "5.3.0");

// *** language: en - English
define("EI_DEFAULT_LANGUAGE", "en");

// *** array of available languages
$arr_active_languages = array("en" => "English");

// *** force database creation
define("EI_DATABASE_CREATE", false);

// *** define database type
define("EI_DATABASE_TYPE", "mysql");

// *** check for database engine minimum version number -
define("EI_CHECK_DB_MINIMUM_VERSION", true);
define("EI_DB_MINIMUM_VERSION", "4.0.0");

// *** config file name - output file with config parameters (database, username etc.)
define("EI_CONFIG_FILE_NAME", "../_config.inc.php");

// *** according to directory hierarchy (you may add/remove "../" before EI_CONFIG_FILE_DIRECTORY)
define("EI_CONFIG_FILE_PATH", EI_CONFIG_FILE_NAME);

// *** sql dump file - file that includes SQL statements for instalation
define("EI_SQL_DUMP_FILE_CREATE", "resources/database.sql");

// *** defines using of utf-8 encoding and collation for SQL dump file
define("EI_USE_ENCODING", true);
define("EI_DUMP_FILE_ENCODING", "utf8");
define("EI_DUMP_FILE_COLLATION", "utf8_unicode_ci");

// *** allow manual installation
define("EI_ALLOW_MANUAL_INSTALLATION", true);

// *** manual installation text file
define("EI_MANUAL_INSTALLATION_DIR", "manual/");
$arr_manual_installations  = array("en" => "manual.en.php");
$arr_upgrade_installations = array("en" => "upgrade.en.php");
$arr_upgradev4_installations = array("en" => "upgradev4.en.php");

// *** config file name - config template file name
define("EI_CONFIG_FILE_TEMPLATE", "config.tpl");

// *** application name
define("EI_APPLICATION_NAME", "uCloud");

// *** default start file name - application start file
define("EI_APPLICATION_START_FILE", "");

// *** additional text after successful installation
define("EI_POST_INSTALLATION_TEXT", "");
