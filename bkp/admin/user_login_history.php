<?php
// initial constants
define('ADMIN_SELECTED_PAGE', 'users');
define('ADMIN_SELECTED_SUB_PAGE', 'user_manage');

// includes and security
include_once('_local_auth.inc.php');

// load user details
$userId = (int) $_REQUEST['id'];
$user   = $db->getRow("SELECT * FROM users WHERE id = " . (int) $userId . " LIMIT 1");
if (!$user)
{
    adminFunctions::redirect('user_manage.php?error=' . urlencode('There was a problem loading the user details.'));
}
define('ADMIN_PAGE_TITLE', '30 Day Login History for \'' . $user['username'] . '\'');

// get all login data
$loginData = $db->getRows('SELECT login_success.*, country_info.name AS country_name FROM login_success LEFT JOIN country_info ON login_success.country_code = country_info.iso_alpha2 WHERE login_success.user_id = '.(int)$userId.' ORDER BY date_added DESC');

// get data for stats
$totalDifferentIps = (int)$db->getValue('SELECT COUNT(DISTINCT ip_address) FROM login_success WHERE login_success.user_id = '.(int)$userId);
$totalDifferentCountries = (int)$db->getValue('SELECT COUNT(DISTINCT country_code) FROM login_success WHERE login_success.user_id = '.(int)$userId);

// page header
include_once('_header.inc.php');
?>

<script>
    oTable = null;
    gPluginId = null;
    $(document).ready(function(){
        // datatable
        oTable = $('#userLoginsTable').dataTable({
            "sPaginationType": "full_numbers",
            "bServerSide": false,
            "bJQueryUI": true,
            "iDisplayLength": 100,
            "bLengthChange": false,
			"aaSorting": [[ 0, "desc" ]]
        });
    });
</script>

<div class="row clearfix">
    <div class="col_12">
        <div class="sectionLargeIcon largeUserAddIcon"></div>
        <div class="widget clearfix">
            <h2>User Details</h2>
            <div class="widget_inside">
                <?php echo adminFunctions::compileNotifications(); ?>
                    <div class="clearfix col_12">
                        <div class="col_4">
                            <h3>Account Logins</h3>
                            <p>Details of any successful account logins for the past 30 days, the IP address and which country it was from.</p>
                            <p style='padding-top: 6px;'>
                                <ul class='square' style='margin-left: 12px;'>
                                    <li>Total Logins: <strong><?php echo COUNT($loginData); ?></strong></li>
                                    <li>Different IPs Used: <strong><?php echo $totalDifferentIps; ?></strong></li>
                                    <li>Different Countries Used: <strong><?php echo $totalDifferentCountries; ?></strong></li>
                                </ul>
                            </p>
                        </div>
                        <div class="col_8 last">
                            <table id="userLoginsTable" class="zebra">
                                <thead>
                                    <tr>
                                        <th class='align-left'>Login Date</th>
                                        <th class='align-left'>IP Address</th>
                                        <th class='align-left'>Country</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if(COUNT($loginData) == 0)
                                    {
                                        echo '<tr><td>No logins in the past 30 days.</td><td></td><td></td></tr>';
                                    }
                                    else
                                    {
                                        foreach($loginData AS $loginDataItem): ?>
                                        <tr>
                                            <td><?php echo adminFunctions::makeSafe(coreFunctions::formatDate($loginDataItem['date_added'], SITE_CONFIG_DATE_TIME_FORMAT)); ?></td>
                                            <td><?php echo adminFunctions::makeSafe($loginDataItem['ip_address']); ?></td>
                                            <td><?php echo adminFunctions::makeSafe(strlen($loginDataItem['country_name'])?$loginDataItem['country_name']:''); ?></td>
                                        </tr>
                                        <?php endforeach;
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="clearfix col_12">
                        <div class="col_4 adminResponsiveHide">&nbsp;</div>
                        <div class="col_8 last">
                            <div class="clearfix">
                                <div class="input">
                                    <input type="submit" value="Done" class="button blue" onClick="window.location='user_manage.php'; return false;">
                                </div>
                            </div>
                        </div>
                    </div>

                    <input name="submitted" type="hidden" value="1"/>
                    <input name="id" type="hidden" value="<?php echo $userId; ?>"/>
                </form>
            </div>
        </div>   
    </div>
</div>

<?php
include_once('_footer.inc.php');
?>