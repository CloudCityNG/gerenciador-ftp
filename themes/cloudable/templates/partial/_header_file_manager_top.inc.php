<!DOCTYPE html>
<html lang="en" dir="<?php echo SITE_LANGUAGE_DIRECTION == 'RTL' ? 'RTL' : 'LTR'; ?>" class="direction<?php echo SITE_LANGUAGE_DIRECTION == 'RTL' ? 'Rtl' : 'Ltr'; ?> <?php echo defined('HTML_ELEMENT_CLASS')?HTML_ELEMENT_CLASS:''; ?>">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <title><?php echo validation::safeOutputToScreen(PAGE_NAME); ?> - <?php echo SITE_CONFIG_SITE_NAME; ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta name="description" content="<?php echo validation::safeOutputToScreen(PAGE_DESCRIPTION); ?>" />
        <meta name="keywords" content="<?php echo validation::safeOutputToScreen(PAGE_KEYWORDS); ?>" />
        <meta name="copyright" content="Copyright &copy; <?php echo date("Y"); ?> - <?php echo SITE_CONFIG_SITE_NAME; ?>" />
        <meta name="robots" content="all" />
        <meta http-equiv="Cache-Control" content="no-cache" />
        <meta http-equiv="Expires" content="-1" />
        <meta http-equiv="Pragma" content="no-cache" />
		
		<!-- og meta tags -->
		<?php if(defined('PAGE_OG_TITLE')): ?>
			<meta property="og:title" content="<?php echo PAGE_OG_TITLE; ?>" />
		<?php endif; ?>
		<?php if(defined('PAGE_OG_IMAGE')): ?>
			<meta property="og:image" content="<?php echo PAGE_OG_IMAGE; ?>" />
		<?php else: ?>
			<meta property="og:image" content="<?php echo SITE_IMAGE_PATH; ?>/favicon/ms-icon-144x144.png" />
		<?php endif; ?>
		<meta property="og:type" content="website" />
		
		<!-- fav and touch icons -->
        <link rel="icon" type="image/x-icon" href="<?php echo SITE_IMAGE_PATH; ?>/favicon/favicon.ico" />
        <link rel="icon" type="image/png" sizes="96x96" href="<?php echo SITE_IMAGE_PATH; ?>/favicon/favicon-96x96.png">
        <link rel="apple-touch-icon" sizes="152x152" href="<?php echo SITE_IMAGE_PATH; ?>/favicon/apple-icon-152x152.png">
        <link rel="manifest" href="<?php echo SITE_IMAGE_PATH; ?>/favicon/manifest.json">
        <meta name="msapplication-TileImage" content="<?php echo SITE_IMAGE_PATH; ?>/favicon/ms-icon-144x144.png">
        <meta name="msapplication-TileColor" content="#ffffff">
        <meta name="theme-color" content="#ffffff">
		
        <?php
        // add css files, use the htmlHelper::addCssFile() function so files can be joined/minified
		pluginHelper::addCssFile(SITE_CSS_PATH . '/fonts.css');
        pluginHelper::addCssFile(SITE_CSS_PATH . '/font-icons/entypo/css/entypo.css');
		pluginHelper::addCssFile(SITE_CSS_PATH . '/font-icons/font-awesome/css/font-awesome.min.css');
        pluginHelper::addCssFile(SITE_CSS_PATH . '/bootstrap.css');
		if ($themeObj->getThemeSkin())
		{
            pluginHelper::addCssFile(SITE_CSS_PATH . '/skins/'.$themeObj->getThemeSkin());
        }
        else
		{
			pluginHelper::addCssFile(SITE_CSS_PATH . '/skins/default.css');
		}
        pluginHelper::addCssFile(SITE_CSS_PATH . '/core.css');
        pluginHelper::addCssFile(SITE_CSS_PATH . '/theme.css');
        pluginHelper::addCssFile(SITE_CSS_PATH . '/forms.css');
        pluginHelper::addCssFile(SITE_CSS_PATH . '/responsive.css');
        if (SITE_LANGUAGE_DIRECTION == 'RTL')
        {
            // include RTL styles
            pluginHelper::addCssFile(SITE_CSS_PATH . '/rtl.css');
        }
        pluginHelper::addCssFile(SITE_CSS_PATH . '/daterangepicker-bs3.css');
        pluginHelper::addCssFile(SITE_CSS_PATH . '/custom.css');
        pluginHelper::addCssFile(SITE_CSS_PATH . '/file-upload.css');
		pluginHelper::addCssFile(SITE_CSS_PATH . '/search_widget.css');

        // output css
        pluginHelper::outputCss();
        ?>
		<?php echo $themeObj->outputCustomCSSCode(); ?>

        <script src="<?php echo SITE_JS_PATH; ?>/jquery-1.11.0.min.js"></script>
        <script type="text/javascript" src="<?php echo SITE_JS_PATH; ?>/jquery.ckie.js"></script>
        <script type="text/javascript" src="<?php echo SITE_JS_PATH; ?>/jquery.jstree.js"></script>
        <script type="text/javascript" src="<?php echo SITE_JS_PATH; ?>/jquery.event.drag-2.2.js"></script>
        <script type="text/javascript" src="<?php echo SITE_JS_PATH; ?>/jquery.event.drag.live-2.2.js"></script>
        <script type="text/javascript" src="<?php echo SITE_JS_PATH; ?>/jquery.event.drop-2.2.js"></script>
        <script type="text/javascript" src="<?php echo SITE_JS_PATH; ?>/jquery.event.drop.live-2.2.js"></script>
		
		<link rel="stylesheet" href="<?php echo SITE_CSS_PATH; ?>/file_browser_sprite_48px.css" type="text/css" charset="utf-8" />
        
        <link rel="stylesheet" type="text/css" href="<?php echo SITE_JS_PATH; ?>/slick/slick.css"/>
       
        <link rel="stylesheet" type="text/css" href="<?php echo SITE_JS_PATH; ?>/slick/slick-theme.css"/>
        <script type="text/javascript" src="<?php echo SITE_JS_PATH; ?>/slick/slick.js"></script>
        
        <link rel="stylesheet" type="text/css" href="<?php echo SITE_JS_PATH; ?>/photo-swipe/photoswipe.css"/>
        <link rel="stylesheet" type="text/css" href="<?php echo SITE_JS_PATH; ?>/photo-swipe/default-skin/default-skin.css"/>
        <script type="text/javascript" src="<?php echo SITE_JS_PATH; ?>/photo-swipe/photoswipe.min.js"></script>
        <script type="text/javascript" src="<?php echo SITE_JS_PATH; ?>/photo-swipe/photoswipe-ui-default.min.js"></script>
		
		<!-- mobile swipe navigation -->
		<script type="text/javascript" src="<?php echo SITE_JS_PATH; ?>/jquery.touchSwipe.min.js"></script>
		
		<script type="text/javascript" src="<?php echo SITE_JS_PATH; ?>/cloudable.js"></script>

        <!--[if lt IE 9]><script src="<?php echo SITE_JS_PATH; ?>/ie8-responsive-file-warning.js"></script><![endif]-->

        <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
        <!--[if lt IE 9]>
                <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
                <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
        <![endif]-->

		<?php
		// create missing translations for javascript
		t('selected_file', 'selected file');
		?>
		
        <script type="text/javascript">
            var WEB_ROOT = "<?php echo WEB_ROOT; ?>";
			var SITE_THEME_WEB_ROOT = "<?php echo SITE_THEME_WEB_ROOT; ?>";
			var SITE_CSS_PATH = "<?php echo SITE_CSS_PATH; ?>";
			var SITE_IMAGE_PATH = "<?php echo SITE_IMAGE_PATH; ?>";
			var _CONFIG_SITE_PROTOCOL = "<?php echo _CONFIG_SITE_PROTOCOL; ?>";
			var CORE_AJAX_WEB_ROOT = "<?php echo CORE_AJAX_WEB_ROOT; ?>";
			var LOGGED_IN = <?php echo $Auth->loggedIn()?'true':'false'; ?>;
<?php echo translate::generateJSLanguageCode(); ?>
        </script>
        <?php
// add js files, use the htmlHelper::addJsFile() function so files can be joined/minified
        pluginHelper::addJsFile(SITE_JS_PATH . '/jquery-ui.js');
        pluginHelper::addJsFile(SITE_JS_PATH . '/jquery.dataTables.min.js');
        pluginHelper::addJsFile(SITE_JS_PATH . '/jquery.tmpl.min.js');
        pluginHelper::addJsFile(SITE_JS_PATH . '/load-image.min.js');
        pluginHelper::addJsFile(SITE_JS_PATH . '/canvas-to-blob.min.js');
        pluginHelper::addJsFile(SITE_JS_PATH . '/jquery.iframe-transport.js');
        pluginHelper::addJsFile(SITE_JS_PATH . '/jquery.fileupload.js');
        pluginHelper::addJsFile(SITE_JS_PATH . '/jquery.fileupload-process.js');
        pluginHelper::addJsFile(SITE_JS_PATH . '/jquery.fileupload-resize.js');
        pluginHelper::addJsFile(SITE_JS_PATH . '/jquery.fileupload-validate.js');
        pluginHelper::addJsFile(SITE_JS_PATH . '/jquery.fileupload-ui.js');
        pluginHelper::addJsFile(SITE_JS_PATH . '/zeroClipboard/ZeroClipboard.js');
        pluginHelper::addJsFile(SITE_JS_PATH . '/daterangepicker/moment.min.js');
        pluginHelper::addJsFile(SITE_JS_PATH . '/daterangepicker/daterangepicker.js');
        pluginHelper::addJsFile(SITE_JS_PATH . '/global.js');

// output js
        pluginHelper::outputJs();
        ?>
        
        
    </head>