</div><!--container -->
</div>
</div>

<footer>
    <div class="container">
        <div class="row clearfix">
            <div class="col_12">
                <div class="responsiveFooterWrapper">
                <span class="left"><?php echo t("copyright"); ?> &copy; <?php echo date("Y"); ?> <?php echo SITE_CONFIG_SITE_NAME; ?></span>
                <div class="adminResponsiveClear"></div>
                <span class="right">
                    Created by <a href="<?php echo themeHelper::getCurrentProductUrl(); ?>" target="_blank"><?php echo themeHelper::getCurrentProductName(); ?></a>, a <a href="https://mfscripts.com" target="_blank">MFScripts</a> company&nbsp;&nbsp;|&nbsp;&nbsp;v<?php echo _CONFIG_SCRIPT_VERSION; ?>&nbsp;&nbsp;|&nbsp;&nbsp;<a href="http://forum.mfscripts.com" target="_blank"><?php echo t("support"); ?></a>
                </span>
                </div>
            </div>
        </div>
    </div>
</footer>

<?php
// output error
if (strlen($systemAlertErrorStr))
{
    ?>
    <div id="alertMessage" title="System Notice">
        <p><?php echo $systemAlertErrorStr; ?></p>
    </div>
    <script>
        $(document).ready(function(){
            // dialog box
            $( "#alertMessage" ).dialog({
                modal: true,
                autoOpen: false,
                width: getDefaultDialogWidth(),
                buttons: {
                    "OK": function() {
                        $("#alertMessage").dialog("close");
                    }
                }
            });
        });
    </script>
    <?php
}
?>

</body>
</html>
