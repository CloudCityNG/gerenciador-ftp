<?php

// setup includes
require_once('../_local_auth.inc.php');

$backup = new backup();
echo $backup->backupDatabase();