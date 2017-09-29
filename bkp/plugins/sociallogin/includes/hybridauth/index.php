<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2012, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html 
*/

// ------------------------------------------------------------------------
//	HybridAuth End Point
// ------------------------------------------------------------------------

require_once('../../../../core/includes/master.inc.php');

$config = PLUGIN_DIRECTORY_ROOT . 'sociallogin/includes/hybridauth/config.php';
require_once(PLUGIN_DIRECTORY_ROOT . 'sociallogin/includes/hybridauth/Hybrid/Auth.php');

require_once( "Hybrid/Auth.php" );
require_once( "Hybrid/Endpoint.php" ); 

Hybrid_Endpoint::process();