<?php

/* main configuration file for script */
define("_CONFIG_SITE_HOST_URL", "localhost");  /* site url host without the http:// and no trailing forward slash - i.e. www.mydomain.com or links.mydomain.com */
define("_CONFIG_SITE_FULL_URL", "localhost/sistema_ftp");  /* full site url without the http:// and no trailing forward slash - i.e. www.mydomain.com/links or the same as the _CONFIG_SITE_HOST_URL */

/* database connection details */
define("_CONFIG_DB_HOST", "localhost");  /* database host name */
define("_CONFIG_DB_NAME", "sistema_ftp");    /* database name */
define("_CONFIG_DB_USER", "root");    /* database username */
define("_CONFIG_DB_PASS", "b40th34infoadx");    /* database password */

/* set these to the main site host if you're using direct web server uploads/downloads to remote servers */
define("_CONFIG_CORE_SITE_HOST_URL", "localhost");  /* site url host without the http:// and no trailing forward slash - i.e. www.mydomain.com or links.mydomain.com */
define("_CONFIG_CORE_SITE_FULL_URL", "localhost/sistema_ftp");  /* full site url without the http:// and no trailing forward slash - i.e. www.mydomain.com/links or the same as the _CONFIG_SITE_HOST_URL */

define("_CONFIG_SCRIPT_VERSION", "1.2.1");    /* script version */

/* show database degug information on fail */
define("_CONFIG_DB_DEBUG", true);    /* this will display debug information when something fails in the DB - leave this as true if you're not sure */

/* server paths */
define("_CONFIG_SCRIPT_ROOT",           dirname(__FILE__));
define("_CONFIG_FILE_STORAGE_PATH",     _CONFIG_SCRIPT_ROOT . '/files/');     /* location on your server to store file uploads */

/* the url of the domain to download files from, only change if you plan on using a different domain to link to your files */
define("_CONFIG_SITE_FILE_DOMAIN",      _CONFIG_SITE_FULL_URL);  /* url without the http:// and no trailing forward slash */

/* which protcol to use, default is http */
define("_CONFIG_SITE_PROTOCOL", "http");

/* key used for encoding data within the site */
define("_CONFIG_UNIQUE_ENCRYPTION_KEY", "FMmYSyVNEafhf76OC4IfnSmtFP5DyTj5fLTyApBEFhlkdgzfa9BDQnxV3rYrB9lhK5VKkWOp41QXWF4xE51UhnqaVEHXDt5NoPYOmm5gdK4zQwnUqeoOIVodaw1dgvrL");

/* toggle demo mode */
define("_CONFIG_DEMO_MODE", false);    /* always leave this as false */