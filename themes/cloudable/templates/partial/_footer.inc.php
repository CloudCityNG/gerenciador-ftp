</div>

<?php
// page html structure for popups, uploader etc
include_once(SITE_TEMPLATES_PATH . '/partial/_site_js_html_containers.inc.php');
?>

<!-- Bottom Scripts -->
<script type="text/javascript" src="<?php echo SITE_JS_PATH; ?>/gsap/main-gsap.js"></script>
<script type="text/javascript" src="<?php echo SITE_JS_PATH; ?>/bootstrap.js"></script>
<script type="text/javascript" src="<?php echo SITE_JS_PATH; ?>/joinable.js"></script>
<script type="text/javascript" src="<?php echo SITE_JS_PATH; ?>/resizeable.js"></script>
<script type="text/javascript" src="<?php echo SITE_JS_PATH; ?>/cloudable-api.js"></script>
<script type="text/javascript" src="<?php echo SITE_JS_PATH; ?>/toastr.js"></script>
<script type="text/javascript" src="<?php echo SITE_JS_PATH; ?>/custom.js"></script>
<script type="text/javascript" src="<?php echo SITE_JS_PATH; ?>/handlebars.js"></script>
<script type="text/javascript" src="<?php echo SITE_JS_PATH; ?>/typeahead.bundle.js"></script>
<script type="text/javascript" src="<?php echo SITE_JS_PATH; ?>/search_widget.js"></script>
<script type="text/javascript" src="<?php echo SITE_JS_PATH; ?>/clipboardjs/clipboard.min.js"></script>

<script src="<?php echo SITE_JS_PATH; ?>/file-manager-gallery/jquery.wookmark.js" type="text/javascript"></script>

<div class="clipboard-placeholder-wrapper">
	<button id="clipboard-placeholder-btn" type="button" data-clipboard-action="copy" data-clipboard-target="#clipboard-placeholder"></button>
	<div id="clipboard-placeholder"></div>
</div>

<?php echo (defined('SITE_CONFIG_GOOGLE_ANALYTICS_CODE') && strlen(SITE_CONFIG_GOOGLE_ANALYTICS_CODE)) ? SITE_CONFIG_GOOGLE_ANALYTICS_CODE : ''; ?>

</body>
</html>