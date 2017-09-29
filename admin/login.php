<?php
define('ADMIN_IGNORE_LOGIN', true);
include_once('_local_auth.inc.php');
include_once('_header_login.inc.php');

// check for mcrypt, required for login
if(!function_exists('mcrypt_create_iv'))
{
    adminFunctions::setError(t("mcrypt_not_found", "Mcypt functions not found within PHP, please ask support to install and try again."));
}
?>

<div class="row loginWrapper">
    <div class="col_6 pre_3">
        <div class="widget clearfix">
            <h2><?php echo UCWords(adminFunctions::t("admin_login", "admin login")); ?></h2>
            <div class="widget_inside responsiveTable">
                <div class="responsiveLoginWrapper">
                <p class="margin_bottom_15"><?php echo adminFunctions::t("login_to_the_admin_area_below", "Login to the admin area below:"); ?></p>
                <?php
                if ($_REQUEST['error'])
                {
                    adminFunctions::setError("Incorrect login details, please try again.");
                }
                echo adminFunctions::compileErrorHtml();
                ?>
                <form method="POST" action="index.php">
                    <div class="form">
                        <div class="clearfix">
                            <label><?php echo adminFunctions::t("username", "username"); ?></label>
                            <div class="input">
                                <input id="username" name="username" type="text" value="<?php echo htmlentities($_REQUEST['username']); ?>" class="xlarge"/>
                            </div>
                        </div>
                        <div class="clearfix">
                            <label><?php echo adminFunctions::t("password", "password"); ?></label>
                            <div class="input">
                                <input id="password" name="password" type="password" value="" class="xlarge"/>
                            </div>
                        </div>
                        <div class="clearfix">
                            <div class="input no-label">
                                <input type="submit" class="button blue" value="<?php echo adminFunctions::t("login", "login"); ?>"></input>
                            </div>
                        </div>
                    </div>
                    <input id="submitme" name="submitme" value="1" type="hidden"/>
                    <input id="version" name="version" value="<?php echo adminFunctions::makeSafe(_CONFIG_SCRIPT_VERSION); ?>" type="hidden"/>
                </form>
            </div>
        </div>
    </div>
   </div>
</div>


<?php
include_once('_footer_login.inc.php');
?>