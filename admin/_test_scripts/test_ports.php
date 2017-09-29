<?php

// setup includes
require_once('../_local_auth.inc.php');

// test access to ports
$host = 'host_or_ip_to_test';
$ports = array(21, 22, 80, 443);

foreach ($ports as $port)
{
    $connection = @fsockopen($host, $port, $errno, $errstr, 15);
    if (is_resource($connection))
    {
        echo '<h2>' . $host . ':' . $port . ' is open.</h2>' . "\n";

        fclose($connection);
    }

    else
    {
        echo '<h2>' . $host . ':' . $port . ' is not responding.</h2>' . "\n";
    }
}