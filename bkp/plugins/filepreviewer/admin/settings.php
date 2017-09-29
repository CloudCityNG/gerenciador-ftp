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
    adminFunctions::redirect(ADMIN_WEB_ROOT . '/theme_manage.php?error=' . urlencode('There was a problem loading the plugin details.'));
}
define('ADMIN_PAGE_TITLE', $plugin['plugin_name'] . ' Settings');

// load plugin details
$pluginDetails = pluginHelper::pluginSpecificConfiguration('filepreviewer');
$pluginConfig  = $pluginDetails['config'];

// prepare variables
$plugin_enabled = (int) $plugin['plugin_enabled'];
$non_show_viewer     = 1;
$free_show_viewer    = 1;
$paid_show_viewer    = 1;
$enable_preview_image = 1;
$preview_image_show_thumb = 1;
$auto_rotate = 1;
$enable_preview_document = 1;
$preview_document_pdf_thumbs = 1;
$preview_document_ext = 'doc,docx,xls,xlsx,ppt,pptx,pdf,pages,ai,psd,tiff,dxf,svg,eps,ps,ttf,otf,xps';
$enable_preview_video = 1;
$preview_video_ext = 'mp4,flv,ogg';
$preview_video_autoplay = 1;
$enable_preview_audio = 1;
$preview_audio_ext = 'mp3';
$preview_audio_autoplay = 1;

// load existing settings
if (strlen($plugin['plugin_settings']))
{
    $plugin_settings = json_decode($plugin['plugin_settings'], true);
    if ($plugin_settings)
    {
        $enable_preview_image = (int) $plugin_settings['enable_preview_image'];
		$preview_image_show_thumb = (int) $plugin_settings['preview_image_show_thumb'];
		$auto_rotate = (int) $plugin_settings['auto_rotate'];
		$enable_preview_document = (int) $plugin_settings['enable_preview_document'];
		$preview_document_pdf_thumbs = (int) $plugin_settings['preview_document_pdf_thumbs'];
		$preview_document_ext = $plugin_settings['preview_document_ext'];
		$enable_preview_video = (int) $plugin_settings['enable_preview_video'];
		$preview_video_ext = $plugin_settings['preview_video_ext'];
		$preview_video_autoplay = (int) $plugin_settings['preview_video_autoplay'];
		$enable_preview_audio = (int) $plugin_settings['enable_preview_audio'];
		$preview_audio_ext = $plugin_settings['preview_audio_ext'];
		$preview_audio_autoplay = (int) $plugin_settings['preview_audio_autoplay'];
    }
}

// handle page submissions
if (isset($_REQUEST['submitted']))
{
    // get variables
	$plugin_enabled = (int) $_REQUEST['plugin_enabled'];
    $plugin_enabled = $plugin_enabled != 1 ? 0 : 1;
    $non_show_viewer     = 1;
    $free_show_viewer    = 1;
    $paid_show_viewer    = 1;
    $enable_preview_image = (int) $_REQUEST['enable_preview_image'];
	$preview_image_show_thumb = (int) $_REQUEST['preview_image_show_thumb'];
	$auto_rotate = (int) $_REQUEST['auto_rotate'];
	$enable_preview_document = (int) $_REQUEST['enable_preview_document'];
	$preview_document_pdf_thumbs = (int) $_REQUEST['preview_document_pdf_thumbs'];
	$preview_document_ext = trim(strtolower($_REQUEST['preview_document_ext']));
	$enable_preview_video = (int) $_REQUEST['enable_preview_video'];
	$preview_video_ext = trim(strtolower($_REQUEST['preview_video_ext']));
	$preview_video_autoplay = (int) $_REQUEST['preview_video_autoplay'];
	$enable_preview_audio = (int) $_REQUEST['enable_preview_audio'];
	$preview_audio_ext = trim(strtolower($_REQUEST['preview_audio_ext']));
	$preview_audio_autoplay = (int) $_REQUEST['preview_audio_autoplay'];

    // validate submission
    if (_CONFIG_DEMO_MODE == true)
    {
        adminFunctions::setError(adminFunctions::t("no_changes_in_demo_mode"));
    }

    // update the settings
    if (adminFunctions::isErrors() == false)
    {
        // compile new settings
        $settingsArr                        = array();
        $settingsArr['non_show_viewer']     = (int) $non_show_viewer;
        $settingsArr['free_show_viewer']    = (int) $free_show_viewer;
        $settingsArr['paid_show_viewer']    = (int) $paid_show_viewer;
        $settingsArr['enable_preview_image'] = (int) $enable_preview_image;
		$settingsArr['preview_image_show_thumb'] = (int) $preview_image_show_thumb;
		$settingsArr['auto_rotate'] = (int) $auto_rotate;
		$settingsArr['supported_image_types'] = 'jpg,jpeg,png,gif,wbmp';
		$settingsArr['enable_preview_document'] = (int) $enable_preview_document;
		$settingsArr['preview_document_pdf_thumbs'] = (int) $preview_document_pdf_thumbs;
		$settingsArr['preview_document_ext'] = $preview_document_ext;
		$settingsArr['enable_preview_video'] = (int) $enable_preview_video;
		$settingsArr['preview_video_ext'] = $preview_video_ext;
		$settingsArr['preview_video_autoplay'] = (int) $preview_video_autoplay;
		$settingsArr['enable_preview_audio'] = (int) $enable_preview_audio;
		$settingsArr['preview_audio_ext'] = $preview_audio_ext;
		$settingsArr['preview_audio_autoplay'] = (int) $preview_audio_autoplay;
		$settingsArr['caching'] = 1;
		$settingsArr['image_quality'] = 90;	
        $settings                           = json_encode($settingsArr);

        // update the settings
        $dbUpdate                  = new DBObject("plugin", array("plugin_enabled", "plugin_settings"), 'id');
        $dbUpdate->plugin_enabled  = $plugin_enabled;
        $dbUpdate->plugin_settings = $settings;
        $dbUpdate->id              = $pluginId;
        $dbUpdate->update();

        adminFunctions::redirect(ADMIN_WEB_ROOT . '/theme_manage.php');
    }
}

// page header
include_once(ADMIN_ROOT . '/_header.inc.php');

?>

<div class="row clearfix">
    <div class="col_12">
        <div class="sectionLargeIcon" style="background: url(../assets/img/icons/128px.png) no-repeat;"></div>
        <div class="widget clearfix">
            <h2>Settings</h2>
            <div class="widget_inside">
                <?php echo adminFunctions::compileNotifications(); ?>
                <form method="POST" action="settings.php" name="pluginForm" id="pluginForm" autocomplete="off" enctype="multipart/form-data">
					<div class="clearfix col_12">
                        <div class="col_4">
                            <h3>Plugin State</h3>
                            <p>Whether this plugin is enabled.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>Plugin Enabled:</label>
                                    <div class="input">
                                        <select name="plugin_enabled" id="plugin_enabled" class="medium validate[required]">
                                            <?php
                                            $enabledOptions = array(0 => 'No', 1 => 'Yes');
                                            foreach ($enabledOptions AS $k => $enabledOption)
                                            {
                                                echo '<option value="' . $k . '"';
                                                if ($plugin_enabled == $k)
                                                {
                                                    echo ' SELECTED';
                                                }
                                                echo '>' . $enabledOption . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
					
					<div class="clearfix col_12">
                        <div class="col_4">
                            <h3>Image Previews</h3>
                            <p>Whether to enable image previews and thumbnails. If enabled, thumbnails will be shown while browsing files aswell as when you click to view a file.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>Preview Images:</label>
                                    <div class="input">
                                        <select name="enable_preview_image" class="medium">
                                            <?php
                                            $options = array('1' => 'Yes', '0' => 'No');
                                            foreach ($options AS $k => $option)
                                            {
                                                echo '<option value="' . $k . '"';
                                                if ($enable_preview_image == $k)
                                                {
                                                    echo ' SELECTED';
                                                }
                                                echo '>' . $option . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
								
								<div class="clearfix">
                                    <label>Show Thumbnails:</label>
                                    <div class="input">
                                        <select name="preview_image_show_thumb" class="medium">
                                            <?php
                                            $options = array('1' => 'Yes', '0' => 'No');
                                            foreach ($options AS $k => $option)
                                            {
                                                echo '<option value="' . $k . '"';
                                                if ($preview_image_show_thumb == $k)
                                                {
                                                    echo ' SELECTED';
                                                }
                                                echo '>' . $option . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
								
								<div class="clearfix alt-highlight">
                                    <label>Auto Rotate Images:</label>
                                    <div class="input">
                                        <select name="auto_rotate" id="auto_rotate" class="medium">
                                            <?php
                                            $enabledOptions = array(0 => 'No', 1 => 'Yes');
                                            foreach ($enabledOptions AS $k => $enabledOption)
                                            {
                                                echo '<option value="' . $k . '"';
                                                if ($auto_rotate == $k)
                                                {
                                                    echo ' SELECTED';
                                                }
                                                echo '>' . $enabledOption . '</option>';
                                            }
                                            ?>
                                        </select>
                                        <br/>
                                        <div style="padding-top: 7px; color: #777; font-size: 11px;">
                                            If set to 'yes', image previews will be automatically rotated the correct way up (based on EXIF data).
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
					
					<div class="clearfix col_12">
                        <div class="col_4">
                            <h3>Document Previews</h3>
                            <p>Whether to enable document previews. If enabled, the document will be shown using Google Docs after you click on the file.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>Preview Documents:</label>
                                    <div class="input">
                                        <select name="enable_preview_document" class="medium">
                                            <?php
                                            $options = array('1' => 'Yes', '0' => 'No');
                                            foreach ($options AS $k => $option)
                                            {
                                                echo '<option value="' . $k . '"';
                                                if ($enable_preview_document == $k)
                                                {
                                                    echo ' SELECTED';
                                                }
                                                echo '>' . $option . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
								
								<div class="clearfix">
                                    <label>Show PDF Thumbnails:</label>
                                    <div class="input">
                                        <select name="preview_document_pdf_thumbs" class="medium">
                                            <?php
                                            $options = array('1' => 'Yes', '0' => 'No');
                                            foreach ($options AS $k => $option)
                                            {
                                                echo '<option value="' . $k . '"';
                                                if ($preview_document_pdf_thumbs == $k)
                                                {
                                                    echo ' SELECTED';
                                                }
                                                echo '>' . $option . '</option>';
                                            }
                                            ?>
                                        </select>
										<br/>
                                        <div style="padding-top: 7px; color: #777; font-size: 11px;">
                                            Requires ImageMagick within PHP. Please contact your host to enable this.
                                        </div>
                                    </div>
                                </div>
								
								<div class="clearfix alt-highlight">
                                    <label>File Extensions:</label>
                                    <div class="input">
                                        <input type="text" name="preview_document_ext" class="xxlarge" value="<?php echo adminFunctions::makeSafe($preview_document_ext); ?>"/>
										<br/>
                                        <div style="padding-top: 7px; color: #777; font-size: 11px;">
                                            Default: doc,docx,xls,xlsx,ppt,pptx,pdf,pages,ai,psd,tiff,dxf,svg,eps,ps,ttf,otf,xps
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
					
					<div class="clearfix col_12">
                        <div class="col_4">
                            <h3>Video Previews</h3>
                            <p>Whether to enable video previews. If enabled, the video will be shown using JWPlayer after you click on the file.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>Preview Videos:</label>
                                    <div class="input">
                                        <select name="enable_preview_video" class="medium">
                                            <?php
                                            $options = array('1' => 'Yes', '0' => 'No');
                                            foreach ($options AS $k => $option)
                                            {
                                                echo '<option value="' . $k . '"';
                                                if ($enable_preview_video == $k)
                                                {
                                                    echo ' SELECTED';
                                                }
                                                echo '>' . $option . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
								
								<div class="clearfix">
                                    <label>File Extensions:</label>
                                    <div class="input">
                                        <input type="text" name="preview_video_ext" class="xxlarge" value="<?php echo adminFunctions::makeSafe($preview_video_ext); ?>"/>
										<br/>
                                        <div style="padding-top: 7px; color: #777; font-size: 11px;">
                                            Default: mp4,flv,ogg
                                        </div>
                                    </div>
                                </div>
								
								<div class="clearfix alt-highlight">
                                    <label>Autoplay:</label>
                                    <div class="input">
                                        <select name="preview_video_autoplay" class="medium">
                                            <?php
                                            $options = array('1' => 'Yes', '0' => 'No');
                                            foreach ($options AS $k => $option)
                                            {
                                                echo '<option value="' . $k . '"';
                                                if ($preview_video_autoplay == $k)
                                                {
                                                    echo ' SELECTED';
                                                }
                                                echo '>' . $option . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
					
					<div class="clearfix col_12">
                        <div class="col_4">
                            <h3>Audio Previews</h3>
                            <p>Whether to enable audi previews. If enabled, the audio will be played using JWPlayer after you click on the file.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>Preview Audio:</label>
                                    <div class="input">
                                        <select name="enable_preview_audio" class="medium">
                                            <?php
                                            $options = array('1' => 'Yes', '0' => 'No');
                                            foreach ($options AS $k => $option)
                                            {
                                                echo '<option value="' . $k . '"';
                                                if ($enable_preview_audio == $k)
                                                {
                                                    echo ' SELECTED';
                                                }
                                                echo '>' . $option . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
								
								<div class="clearfix">
                                    <label>File Extensions:</label>
                                    <div class="input">
                                        <input type="text" name="preview_audio_ext" class="xxlarge" value="<?php echo adminFunctions::makeSafe($preview_audio_ext); ?>"/>
										<br/>
                                        <div style="padding-top: 7px; color: #777; font-size: 11px;">
                                            Default: mp3
                                        </div>
                                    </div>
                                </div>
								
								<div class="clearfix alt-highlight">
                                    <label>Autoplay:</label>
                                    <div class="input">
                                        <select name="preview_audio_autoplay" class="medium">
                                            <?php
                                            $options = array('1' => 'Yes', '0' => 'No');
                                            foreach ($options AS $k => $option)
                                            {
                                                echo '<option value="' . $k . '"';
                                                if ($preview_audio_autoplay == $k)
                                                {
                                                    echo ' SELECTED';
                                                }
                                                echo '>' . $option . '</option>';
                                            }
                                            ?>
                                        </select>
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
									<input name="thumb_resize_method" type="hidden" value="cropped"/>
									<input name="thumb_size_w" type="hidden" value="180"/>
									<input name="thumb_size_h" type="hidden" value="150"/>
									<input name="caching" type="hidden" value="1"/>
									<input name="show_direct_link" type="hidden" value="0"/>
									<input name="show_embedding" type="hidden" value="0"/>
									<input name="image_quality" type="hidden" value="90"/>
									<input name="watermark_enabled" type="hidden" value="0"/>
									
                                    <input type="submit" value="Submit" class="button blue">
                                    <input type="reset" value="Reset" class="button grey">
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