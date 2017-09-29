<?php

// Script to update the IP location files to the latest data.
// You can grab the database from http://software77.net/geo-ip/ (IPV4 CSV (zip))

// To update the class files (in ip_to_country) do the following:
// - Download the latest database from the url above
// - Extract it into /admin/_test_scripts/IpToCountry.csv
// - Make sure /core/includes/ip_to_country/ (and all files) has write permissions. CHMOD 777 or 755 depending on your host.
// - Delete the existing files from within /core/includes/ip_to_country/
// - Within your browser open: yoursite.com/admin/_test_scripts/test_update_ip_class_files.php
// - You should see the new files generated within /core/includes/ip_to_country/
// - Remove the file /admin/_test_scripts/IpToCountry.csv

// setup includes
require_once('../_local_auth.inc.php');

// try to create the files
$i = new ip2Country();

// check for write permissions
if(!is_writable($i->cache_dir))
{
	die("Error: The ip2Country cache folder isn't writable. Also ensure you delete existing files within this folder. Please change and try again. ".$i->cache_dir);
}

// create the files
$i->parseCSV2('IpToCountry.csv');

echo "File creation complete. ".$i->cache_dir;