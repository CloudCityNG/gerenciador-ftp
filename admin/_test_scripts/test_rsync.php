<?php
die('DISABLED FOR NOW, EXPECT SOMETHING IN v4.4!');

// allow for up to 30 minutes to this to run, it 'should' be quicker!
set_time_limit(60 * 30);

// setup includes
require_once('../_local_auth.inc.php');

set_include_path(get_include_path() . PATH_SEPARATOR . CORE_ROOT . '/includes/phpseclib');
include_once('Net/SFTP.php');

?>
<style>
body {
    font-family: "Courier New", Courier, monospace;
	font-size: 12px;
	padding: 6px;
}
</style>
<?php

// make sure shell_exec is available
if(!function_exists('shell_exec'))
{
    exitError("Error: The PHP function shell_exec() is not available and may be blocked within your php.ini file. Please check and try again.\n");
}

// setup params for later
$localSSHHost = '';
$localSSHUser = '';
$localSSHPass = '';
$localScriptPath = _CONFIG_SCRIPT_ROOT;

$remoteSSHHost = '';
$remoteSSHUser = '';
$remoteSSHPass = '';
$remoteScriptPath = '';

// connect to 'local' storage via SSH
$localSSH = new Net_SSH2($localSSHHost);
if (!$localSSH->login($localSSHUser, $localSSHPass))
{
    exitError('Failed login to local server via SSH ('.$localSSHHost.'). Ensure the correct SSH details are set on the main local file server within admin, manage servers.');
}

// test for rsync
$rs = $localSSH->exec('rsync');
if(stripos($rs, 'command not found') !== false)
{
	exitError('Failed finding rsync on local server ('.$localSSHHost.'). Please install via SSH and try this process again.');
}

// connect to 'remote' storage via SSH
$remoteSSH = new Net_SSH2($remoteSSHHost);
if (!$remoteSSH->login($remoteSSHUser, $remoteSSHPass))
{
    exitError('Failed login to remote server via SSH ('.$remoteSSHHost.'). Ensure the correct SSH details are set on the main remote file server within admin, manage servers.');
}

// test for rsync
$rs = $remoteSSH->exec('rsync');
if(stripos($rs, 'command not found') !== false)
{
	exitError('Failed finding rsync on remote server ('.$remoteSSHHost.'). Please install via SSH and try this process again.');
}

// assume rsync exists if we've got this far, try to sync the files remotely
$rSyncCmd = "rsync -avhoe \"ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null\" --exclude 'files/*' --exclude '.svn/*' --exclude 'core/logs/*' --exclude '/core/cache/*' --exclude '/___OLD_SITE/*' --exclude '/_config.inc.php' ".$localScriptPath."/ ".$remoteSSHUser."@".$remoteSSHHost.":".$remoteScriptPath."\n";
echo outputMessage("Calling rsync command:<br/><br/>".$rSyncCmd);

$syncRs = $localSSH->write($rSyncCmd);
$localSSH->setTimeout(5);
$content = formatForBrowser($localSSH->read());
echo $content;

// check for errors
if(stripos($rs, 'Permission denied, please try again') !== false)
{
	exitError('Failed logging into remote file server server ('.$remoteSSHHost.') via rsync on local server ('.$localSSHHost.'). Please check the login credits via admin, manage file servers and try again.');
}

coreFunctions::flushOutput();

// expected response:
// root@1.1.1.1's password:

$localSSH->write($remoteSSHPass."\n");
$content = formatForBrowser($localSSH->read());
echo $content;
coreFunctions::flushOutput();

$tracker = 0;
$rsyncError = false;
while($tracker < 200)
{
	$localSSH->setTimeout(5);
	$content = formatForBrowser($localSSH->read());
	echo $content;
	coreFunctions::flushOutput();
	if(strlen(trim($content)) == 0)
	{
		$tracker = 200;
	}
	$tracker++;
	
	// check for errors
	if(stripos($content, 'rsync error') !== false)
	{
		$rsyncError = true;
	}
}

// wrap up
if($rsyncError == true)
{
	exitError('Failed fully transferring the files via rsync. Please check the output above and try again.');
}

outputSuccess('<br/>Successfully rsynced the files from your local server ('.$localSSHHost.') to the file server ('.$remoteSSHHost.').');
exit;

// or
// rsync error

// local functions
function formatForBrowser($str)
{
	return nl2br($str);
}

function outputSuccess($str)
{
	echo "<span style='color: green; font-weight: bold;'>- ".$str."</span><br/><br/>";
}

function outputMessage($str)
{
	echo "<br/><span style='font-weight: bold;'>- ".$str."</span><br/><br/>";
}

function exitError($str)
{
	echo "<br/><span style='color: red; font-weight: bold;'>- Error: ".$str."</span>";
	exit;
}