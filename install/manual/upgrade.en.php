<?php
define('CRON_PATH', realpath(dirname(__FILE__).'/../../admin/tasks'));
?>
<a name="top"></a>
<h2>Manual Installation: (Upgrade To Existing Code)</h2>  
<table border=0 width="100%">
    <tbody>
        <tr>
            <td>
                <ul>
                    <li><a href="#Step_1"><span>Step 1. Backup Existing Code &amp; Database.</span></a>
                    <li><a href="#Step_2"><span>Step 2. Uncompress Downloaded Zip File.</span></a>
                    <li><a href="#Step_3"><span>Step 3. Import Database Patches.</span></a>
                    <li><a href="#Step_4"><span>Step 4. Upload Files.</span></a>
                    <li><a href="#Step_5"><span>Step 5. Set Folder Permissions.</span></a>
                    <li><a href="#Step_5a"><span>Step 6. Update Version Number.</span></a>
                    <li><a href="#Step_6"><span>Step 7. Setup Cron Tasks.</span></a>
                </ul>
            </td>
        </tr>
    </tbody>
</table>

<br/>

<div style="float:right;">[<a href="#top">top</a>]</div>
<a name="Step_1"></a>
<h3><b>Step 1. Backup Existing Code &amp; Database.</b></h3>
<hr>
<p><strong style="color: red;">VERY IMPORTANT!</strong> - Before you do any changes to the code at all, ensure you've taken a backup copy of the site, files &amp; database.</p>
<p><br /></p>

<div style="float:right;">[<a href="#top">top</a>]</div>
<a name="Step_2"></a>
<h3><b>Step 2. Uncompress Downloaded Zip File.</b></h3>
<hr>
<p>Open the downloaded zip file on your computer and extract the files into a new folder on your desktop. If you can't open zip files you may need to download a zip client such as <a href='http://www.winzip.com' target='_blank'>WinZip</a>.</p>
<p><br /></p>

<div style="float:right;">[<a href="#top">top</a>]</div>
<a name="Step_3"></a>
<h3><b>Step 3. Import Database Patches.</b></h3>
<hr>
<p>Within your hosting control panel, load phpMyAdmin and select your new database. In the right-hand section click on 'import'. Attach the the relevant sql patches from the directory `/install/resources/upgrade_sql_statements/` and submit the form. Choose the patches between your current script version number and the latest, ensuring you do them in version number order.</p>
<p><br /></p>

<div style="float:right;">[<a href="#top">top</a>]</div>
<a name="Step_4"></a>
<h3><b>Step 4. Upload Files.</b></h3>
<hr>
<p>Using an FTP client such as <a href="https://filezilla-project.org/" target="_blank">FileZilla</a>, upload all the files to your webroot (normally public_html folder) apart from the '/install' folder and the '/_config.inc.php' file.</p>
<p><br /></p>

<div style="float:right;">[<a href="#top">top</a>]</div>
<a name="Step_5"></a>
<h3><b>Step 5. Set Folder Permissions.</b></h3>
<hr>
<p>Using your FTP client, set permissions to CHMOD 777 on the following folders: '/files', '/core/logs', '/core/cache' &amp; '/plugins'.</p>
<p><br /></p>

<div style="float:right;">[<a href="#top">top</a>]</div>
<a name="Step_5a"></a>
<h3><b>Step 6. Update Version Number.</b></h3>
<hr>
<p>In _config.inc.php update the _CONFIG_SCRIPT_VERSION value to the latest version number, i.e. 1.1.</p>
<p><br /></p>

<div style="float:right;">[<a href="#top">top</a>]</div>
<a name="Step_6"></a>
<h3><b>Step 7. Setup Cron Tasks.</b></h3>
<hr>
<p><?php echo EI_APPLICATION_NAME; ?> uses a number of cron (background) tasks to ensure redundant files are deleted, accounts are auto downgraded etc. Details of these are below. You can leave these until later if you want to test the upgrade first. See <a href="http://www.cyberciti.biz/faq/how-do-i-add-jobs-to-cron-under-linux-or-unix-oses/" target="_blank">here for more information</a> on cron tasks.</p>
<ul style="font-family: courier,Consolas,monospace;">
    <li>0 0 * * * php <?php echo CRON_PATH; ?>/auto_prune.cron.php >> /dev/null 2>&amp;1</li>
    <li>0 0 * * * php <?php echo CRON_PATH; ?>/create_internal_notifications.cron.php >> /dev/null 2>&amp;1</li>
    <li>0 * * * * php <?php echo CRON_PATH; ?>/delete_redundant_files.cron.php >> /dev/null 2>&amp;1</li>
    <li>0 0 * * * php <?php echo CRON_PATH; ?>/downgrade_accounts.cron.php >> /dev/null 2>&amp;1</li>
    <li>*/5 * * * * php <?php echo CRON_PATH; ?>/process_file_queue.cron.php >> /dev/null 2>&amp;1</li>
	<li>0 1 * * * php <?php echo CRON_PATH; ?>/create_email_notifications.cron.php >> /dev/null 2>&amp;1</li>
</ul>
<p><br /></p>

Congratulations, you now completed the upgrade. Feel free to contact us or post of our <a href="http://forum.mfscripts.com" target="_blank">forum</a> if you have any problems.				
