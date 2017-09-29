<?php

// setup includes
require_once('../_local_auth.inc.php');

echo "Checking if xsendfile module is enabled... ";
if(fileServer::apacheXSendFileEnabled() == false)
{
	echo "Not Found!<br/><br/>";
	echo "Enable xsendfile within your Apache config.<br/><br/>";
	echo "Install on Ubuntu with the following the restart Apache:<br/><br/>";
	echo "apt-get install libapache2-mod-xsendfile<br/><br/>";
	echo "Some resources:<br/>";
	echo "<ul>";
	echo "<li><a href='https://tn123.org/mod_xsendfile/'>https://tn123.org/mod_xsendfile/</a></li>";
	echo "</ul>";
	die();
}

// module found
echo "Found!<br/><br/>";

echo "Ensure you have set the following path in your servers Apache config file, then restarted Apache:<br/><br/>";

echo "XSendFilePath ".DOC_ROOT."/";

echo "<br/><br/>";

echo "So your Apache config file will look similar to this:<br/><br/>";

echo validation::safeOutputToScreen("<VirtualHost *:80>")."<br/>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;".validation::safeOutputToScreen("XSendFilePath ".DOC_ROOT)."/<br/>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;...<br/>";
echo validation::safeOutputToScreen("</VirtualHost>")."<br/><br/>";

echo "If the above is set, your server should be using Apache to download files rather than PHP. Note this will only work for non speed restricted files.";