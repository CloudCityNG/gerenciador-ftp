<?php

// setup includes
require_once('../_local_auth.inc.php');

echo "The script sees your IP address as: ".Stats::getIP()."<br/>";
echo "Your country is: ".Stats::getCountry(Stats::getIP())."<br/><br/>";

echo "Using core PHP functions, your IP is being reported as: ".$_SERVER['REMOTE_ADDR'];