<?php

session_start();
if(isset($_SESSION['tracker']))
{
	$_SESSION['tracker']++;
}
else
{
	$_SESSION['tracker'] = 1;
}
?>

This file tests session support on your server. The following value should increment as this page is refreshed: <?php echo $_SESSION['tracker']; ?><br/>
<br/>
If the value is always 1, there is a problem storing sessions on your server.<br/>
<br/>
<?php if($_SESSION['tracker'] > 1): ?>
<span style="color: green; font-weight: bold;">It looks like sessions are being stored ok.</span>
<?php endif; ?>

<?php
echo 'Session save path: "'.session_save_path().'"'; 
?>