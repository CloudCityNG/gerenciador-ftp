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
define('ADMIN_PAGE_TITLE', 'Edit User: \'' . $user['username'] . '\'');

// account types
$accountTypeDetails = $db->getRows('SELECT id, level_id, label FROM user_level WHERE id > 0 ORDER BY level_id ASC');

// account status
$accountStatusDetails = array('active', 'pending', 'disabled', 'suspended');

// user titles
$titleItems = array('Mr', 'Ms', 'Mrs', 'Miss', 'Miss', 'Dr');

// load all file servers
$sQL           = "SELECT id, serverLabel FROM file_server ORDER BY serverLabel";
$serverDetails = $db->getRows($sQL);

// prepare variables
$username         = $user['username'];
$password         = '';
$confirm_password = '';
$account_status   = $user['status'];
$account_type     = $user['level_id'];
$expiry_date      = (strlen($user['paidExpiryDate']) && ($user['paidExpiryDate'] != '0000-00-00 00:00:00')) ? date('d/m/Y', strtotime($user['paidExpiryDate'])) : '';
$title            = $user['title'];
$first_name       = $user['firstname'];
$last_name        = $user['lastname'];
$email_address    = $user['email'];
$storage_limit    = $user['storageLimitOverride'];
$remainingBWDownload = $user['remainingBWDownload'];
$upload_server_override = $user['uploadServerOverride'];

// handle page submissions
if (isset($_REQUEST['submitted']))
{
    // get variables
    $password         = trim($_REQUEST['password']);
    $confirm_password = trim($_REQUEST['confirm_password']);
    $account_status   = trim($_REQUEST['account_status']);
    $account_type     = trim($_REQUEST['account_type']);
    $expiry_date      = trim($_REQUEST['expiry_date']);
    $title            = trim($_REQUEST['title']);
    $first_name       = trim($_REQUEST['first_name']);
    $last_name        = trim($_REQUEST['last_name']);
    $email_address    = trim(strtolower($_REQUEST['email_address']));
    $storage_limit    = trim($_REQUEST['storage_limit']);
    $storage_limit = str_replace(array(',', ' ', '.', '(', ')', '-'), '', $storage_limit);
	$remainingBWDownload = trim($_REQUEST['remainingBWDownload']);
    $remainingBWDownload = str_replace(array(',', ' ', '.', '(', ')', '-'), '', $remainingBWDownload);
	if((int)$remainingBWDownload == 0)
	{
		$remainingBWDownload = null;
	}
    $dbExpiryDate     = '';
    $upload_server_override = trim($_REQUEST['upload_server_override']);

    // validate submission
    if (_CONFIG_DEMO_MODE == true)
    {
        adminFunctions::setError(adminFunctions::t("no_changes_in_demo_mode"));
    }
    elseif (strlen($first_name) == 0)
    {
        adminFunctions::setError(adminFunctions::t("enter_first_name"));
    }
    elseif (strlen($last_name) == 0)
    {
        adminFunctions::setError(adminFunctions::t("enter_last_name"));
    }
    elseif (strlen($email_address) == 0)
    {
        adminFunctions::setError(adminFunctions::t("enter_email_address"));
    }
    elseif (validation::validEmail($email_address) == false)
    {
        adminFunctions::setError(adminFunctions::t("entered_email_address_invalid"));
    }
    elseif (strlen($expiry_date))
    {
        // turn into db format
        $exp1 = explode(" ", $expiry_date);
        $exp  = explode("/", $exp1[0]);
        if (COUNT($exp) != 3)
        {
            adminFunctions::setError(adminFunctions::t("account_expiry_invalid_dd_mm_yy", "Account expiry date invalid, it should be in the format dd/mm/yyyy"));
        }
        else
        {
            $dbExpiryDate = $exp[2] . '-' . $exp[1] . '-' . $exp[0] . ' 00:00:00';

            // check format
            if (strtotime($dbExpiryDate) == false)
            {
                adminFunctions::setError(adminFunctions::t("account_expiry_invalid_dd_mm_yy", "Account expiry date invalid, it should be in the format dd/mm/yyyy"));
            }
        }
    }

    // check for password
    if (adminFunctions::isErrors() == false)
    {
        if (strlen($password))
        {
            if ($password != $confirm_password)
            {
                adminFunctions::setError(adminFunctions::t("confirmation_password_does_not_match", "Your confirmation password does not match"));
            }
			else
			{
				// check password structure
				$passValid = passwordPolicy::validatePassword($password);
				if(is_array($passValid))
				{
					adminFunctions::setError(implode('<br/>', $passValid));
				}
			}
        }
    }

    // add the account
    if (adminFunctions::isErrors() == false)
    {
        // update the user
        $dbUpdate = new DBObject("users", array("level_id", "email", "status", "title", "firstname", "lastname", "paidExpiryDate", "storageLimitOverride", "uploadServerOverride", "remainingBWDownload"), 'id');
        if (strlen($password))
        {
            $dbUpdate = new DBObject("users", array("password", "level_id", "email", "status", "title", "firstname", "lastname", "paidExpiryDate", "storageLimitOverride", "uploadServerOverride", "remainingBWDownload"), 'id');
            $dbUpdate->password = Password::createHash($password);
        }
        $dbUpdate->level_id = $account_type;
        $dbUpdate->email = $email_address;
        $dbUpdate->status = $account_status;
        $dbUpdate->title = $title;
        $dbUpdate->firstname = $first_name;
        $dbUpdate->lastname = $last_name;
        $dbUpdate->paidExpiryDate = $dbExpiryDate;
        $dbUpdate->storageLimitOverride = strlen($storage_limit)?$storage_limit:NULL;
        $dbUpdate->uploadServerOverride = (int)$upload_server_override?(int)$upload_server_override:NULL;
		$dbUpdate->remainingBWDownload = (int)$remainingBWDownload?(int)$remainingBWDownload:NULL;
        $dbUpdate->id = $userId;
        $dbUpdate->update();
        
        // append any plugin includes
        pluginHelper::includeAppends('admin_user_edit.inc.php');

        // redirect
        adminFunctions::redirect('user_manage.php?se=1');
    }
}

// page header
include_once('_header.inc.php');
?>

<script>
    $(function() {
        // formvalidator
        $("#userForm").validationEngine();
        
        // date picker
        $( "#expiry_date" ).datepicker({
            "dateFormat": "dd/mm/yy"
        });
    });
    
    function checkExpiryDate()
    {
        userType = $('#account_type option:selected').val();
        if(userType > 1)
        {
            // default to 1 year
            $('#expiry_date').val('<?php echo date('d/m/Y', strtotime('+1 year')); ?>');
        }
    }
</script>

<div class="row clearfix">
    <div class="col_12">
        <div class="sectionLargeIcon largeUserAddIcon"></div>
        <div class="widget clearfix">
            <h2>User Details</h2>
            <div class="widget_inside">
                <?php echo adminFunctions::compileNotifications(); ?>
                <form method="POST" action="user_edit.php" name="userForm" id="userForm" autocomplete="off">
                    <div class="clearfix col_12">
                        <div class="col_4">
                            <h3>Account Details</h3>
                            <p>Information about the account.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>Account Status:</label>
                                    <div class="input">
                                        <select name="account_status" id="account_status" class="medium validate[required]">
                                            <?php
                                            foreach ($accountStatusDetails AS $accountStatusDetail)
                                            {
                                                echo '<option value="' . $accountStatusDetail . '"';
                                                if (($account_status) && ($account_status == $accountStatusDetail))
                                                {
                                                    echo ' SELECTED';
                                                }
                                                echo '>' . UCWords($accountStatusDetail) . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="clearfix">
                                    <label>Account Type:</label>
                                    <div class="input">
                                        <select name="account_type" id="account_type" class="large validate[required]" onChange="checkExpiryDate();">
                                            <?php
                                            foreach ($accountTypeDetails AS $accountTypeDetail)
                                            {
                                                echo '<option value="' . $accountTypeDetail['id'] . '"';
                                                if (($account_type) && ($account_type == $accountTypeDetail['id']))
                                                {
                                                    echo ' SELECTED';
                                                }
                                                echo '>' . UCWords($accountTypeDetail['label']) . '</option>';
                                            }
                                            ?>
                                        </select><span class="paid-account-option">&nbsp;&nbsp;If paid user, ensure you set the expiry date below</span>
                                    </div>
                                </div>
                                <div class="clearfix alt-highlight paid-account-option">
                                    <label>Paid Expiry:</label>
                                    <div class="input">
                                        <input id="expiry_date" name="expiry_date" type="text" class="medium" value="<?php echo adminFunctions::makeSafe($expiry_date); ?>"/>&nbsp;&nbsp;<span class="dateField">(dd/mm/yyyy, maximum 19th January 2038)</span>
                                    </div>
                                </div>
                                <div class="clearfix">
                                    <label>Storage Limit:</label>
                                    <div class="input"><input id="storage_limit" name="storage_limit" placeholder="1073741824 = 1GB" type="text" class="medium" value="<?php echo adminFunctions::makeSafe($storage_limit); ?>"/>&nbsp;bytes. Overrides account type limits. Use zero for unlimited.</div>
                                </div>
								<div class="clearfix alt-highlight">
                                    <label>Download Allowance:</label>
                                    <div class="input"><input id="remainingBWDownload" name="remainingBWDownload" placeholder="1073741824 = 1GB" type="text" class="medium" value="<?php echo adminFunctions::makeSafe($remainingBWDownload); ?>"/>&nbsp;bytes. Optional. Generally left blank. Use zero for unlimited.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="clearfix col_12">
                        <div class="col_4">
                            <h3>User Details</h3>
                            <p>Details about the user.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>Title:</label>
                                    <div class="input">
                                        <select name="title" id="title">
                                            <?php
                                            foreach ($titleItems AS $titleItem)
                                            {
                                                echo '<option value="' . $titleItem . '"';
                                                if (($title) && ($title == $titleItem))
                                                {
                                                    echo ' SELECTED';
                                                }
                                                echo '>' . UCWords($titleItem) . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="clearfix">
                                    <label>First Name:</label>
                                    <div class="input"><input id="first_name" name="first_name" type="text" class="large validate[required]" value="<?php echo adminFunctions::makeSafe($first_name); ?>"/></div>
                                </div>
                                <div class="clearfix alt-highlight">
                                    <label>Last Name:</label>
                                    <div class="input"><input id="last_name" name="last_name" type="text" class="large validate[required]" value="<?php echo adminFunctions::makeSafe($last_name); ?>"/></div>
                                </div>
                                <div class="clearfix">
                                    <label>Email Address:</label>
                                    <div class="input"><input id="email_address" name="email_address" type="text" class="large validate[required,custom[email]]" value="<?php echo adminFunctions::makeSafe($email_address); ?>"/></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="clearfix col_12">
                        <div class="col_4">
                            <h3>Reset Password</h3>
                            <p>Reset the user password. Leave blank to keep the existing.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix">
                                    <label>Password:</label>
                                    <div class="input"><input id="password" name="password" type="password" class="large" autocomplete="off"/></div>
                                </div>
                                <div class="clearfix alt-highlight">
                                    <label>Confirm Password:</label>
                                    <div class="input"><input id="confirm_password" name="confirm_password" type="password" class="large validate[equals[password]]]" value="<?php echo adminFunctions::makeSafe($confirm_password); ?>" autocomplete="off"/></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="clearfix col_12">
                        <div class="col_4">
                            <h3>Other Options</h3>
                            <p>Server upload override.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>Test Upload Server:</label>
                                    <div class="input">
                                        <select name="upload_server_override" id="upload_server_override" class="xlarge">
                                            <option value="">- none - (default)</option>
                                            <?php
                                            foreach ($serverDetails AS $serverDetail)
                                            {
                                                echo '<option value="' . $serverDetail['id'] . '"';
                                                if (($upload_server_override) && ($upload_server_override == $serverDetail['id']))
                                                {
                                                    echo ' SELECTED';
                                                }
                                                echo '>' . $serverDetail['serverLabel'] . '</option>';
                                            }
                                            ?>
                                        </select><br/>
                                        <div style="color: #777; padding-top: 2px; max-width: 400px;">Useful for testing new servers for a specific user. Leave as 'none' to use the global settings. Note: This user will need to logout and back in to see these changes.</div>
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
                                    <input type="submit" value="Submit" class="button blue">
                                    <input type="reset" value="Reset" class="button grey">
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