<?php
// initial constants
define('ADMIN_SELECTED_PAGE', 'plugins');
define('ADMIN_SELECTED_SUB_PAGE', 'plugin_manage');

// includes and security
include_once('../../../core/includes/master.inc.php');
include_once(DOC_ROOT . '/' . ADMIN_FOLDER_NAME . '/_local_auth.inc.php');

// load plugin details
$pluginId = (int) $_REQUEST['id'];
$plugin   = $db->getRow("SELECT * FROM plugin WHERE id = " . (int) $pluginId . " LIMIT 1");
if (!$plugin)
{
    adminFunctions::redirect(ADMIN_WEB_ROOT . '/plugin_manage.php?error=' . urlencode('There was a problem loading the plugin details.'));
}
define('ADMIN_PAGE_TITLE', $plugin['plugin_name'] . ' Plugin Settings');

// prepare variables
$plugin_enabled = (int) $plugin['plugin_enabled'];
$ok_receiver   = '';

// load existing settings
if (strlen($plugin['plugin_settings']))
{
    $plugin_settings = json_decode($plugin['plugin_settings'], true);
    if ($plugin_settings)
    {
        $ok_receiver = $plugin_settings['ok_receiver'];
    }
}

// page header
include_once(ADMIN_ROOT . '/_header.inc.php');
?>

<div class="row clearfix">
    <div class="col_12">
        <div class="sectionLargeIcon" style="background: url(../assets/img/icons/128px.png) no-repeat;"></div>
        <div class="widget clearfix">
            <h2>Plugin Settings</h2>
            <div class="widget_inside">
                <?php echo adminFunctions::compileNotifications(); ?>
                    <div class="clearfix col_12">
                        <div class="col_4">
                            <h3>Import Script</h3>
                            <p>Details of how to run the import script.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <div style="margin: 8px;">
                                        The import script enables you to migrate your existing 'offline' files into the script. It can be run on your main server aswell as file servers.<br/><br/>
                                        First download the <a href="download_import.php">import.php script</a>. This can also be found in:<br/><br/>
                                        <code>
                                            <?php echo DOC_ROOT; ?>/plugins/fileimport/admin/import.php.txt (rename to import.php)
                                        </code>
                                        <br/><br/><br/>
                                        Populate the constants in [[[SQUARE_BRACKET]]] at the top. i.e. FILE_IMPORT_ACCOUNT_NAME, FILE_IMPORT_PATH, FILE_IMPORT_ACCOUNT_START_FOLDER<br/><br/>
                                        Save and upload the file, either to the YetiShare root of your main site or the YetiShare root of a file server. The YetiShare root is the same location as ___RELEASE_HISTORY.txt<br/><br/>
                                        Upload or move all your files to a location on that server. This should be outside of the YetiShare installation (FILE_IMPORT_PATH).<br/><br/>
                                        Call the script on the command line (ssh) using PHP. Like this:
                                        <code>
                                            php <?php echo DOC_ROOT; ?>/import.php
                                        </code>
                                        <br/><br/><br/>
                                        The import will complete with progress onscreen. Files will not be moved, they'll be copied into YetiShare so you can delete them after the import.<br/><br/>
                                        Once the import is complete, ensure you remove the import.php script from your YetiShare root.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="clearfix col_12">
                        <div class="col_4 adminResponsiveHide">&nbsp;</div>
                        <div class="col_8 last">
                            <div class="clearfix">
                                <div class="input no-label">
                                    <input type="reset" value="Back" class="button grey" onClick="window.location='<?php echo ADMIN_WEB_ROOT; ?>/plugin_manage.php';"/>
                                </div>
                            </div>
                        </div>
                    </div>

                    <input name="submitted" type="hidden" value="1"/>
                    <input name="id" type="hidden" value="<?php echo $pluginId; ?>"/>
                </form>
            </div>
        </div>   
    </div>
</div>

<?php
include_once(ADMIN_ROOT . '/_footer.inc.php');
?>