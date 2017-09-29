<?php

// setup includes
require_once ('../../../core/includes/master.inc.php');

// require login
$Auth->requireUser(WEB_ROOT . '/login.' . SITE_CONFIG_PAGE_EXTENSION);

// get existing url downloads and any recent completed
$downloads = $db->getRows('SELECT *, TIMESTAMPDIFF(SECOND, remote_url_download_queue.started, NOW()) AS startedAgo FROM remote_url_download_queue WHERE ((job_status = \'downloading\' OR job_status = \'pending\' OR job_status = \'processing\') AND user_id=' .
    (int)$Auth->id . ') OR (finished IS NOT NULL AND finished >= DATE_SUB(NOW(), INTERVAL 2 day) AND user_id=' .
    (int)$Auth->id . ') ORDER BY created ASC');

?>

<?php if (COUNT($downloads)): ?>

<div class="urlUploadMainInternal contentPageWrapper" style="width: auto;">
    <div>
        <div class="initialUploadText">
            <div class="uploadText">
                <h2><?php

    echo t('plugin_torrentdownload_pending_transfers', 'Torrent Transfers');

?>:</h2>
            </div>
            <div class="clearLeft"><!-- --></div>

            <div class="dataTables_wrapper">
                <table cellspacing="0" cellpadding="0" width="100%" id="existingBackgroundUrlDownloadTable" class="table table-striped files">
                    <thead>
                        <th style="width: 16px;"></th>
                        <th><?php

    echo UCWords(t('url', 'url'));

?></th>
                        <th style="width: 100px; text-align:center;"><?php

    echo UCWords(t('progress', 'progress'));

?></th>
                        <th style="width: 120px; text-align:center;"><?php

    echo UCWords(t('status', 'status'));

?></th>
                    </thead>
                    <tbody>
                    <?php

                    foreach ($downloads as $i => $download)
                    {
						$downloadSpeed = 0;
						if($download['job_status'] == 'downloading')
                        {
							if((int)$download['startedAgo'] > 0)
							{
								$downloadSpeed = number_format($download['downloaded_size']/$download['startedAgo'], 0, '.', '');
							}
						}
						
                        echo '<tr ' . ($i % 2 == 0 ? 'class="odd"' : '') . ' title="'.validation::safeOutputToScreen(str_replace("\"", "'", $download['notes'])).'">';
                        $icon = 'processing_small.gif';
						
                        if ($download['job_status'] == 'complete')
                        {
                            $icon = 'green_tick_small.png';
                        }
                        elseif ($download['job_status'] == 'cancelled')
                        {
                            $icon = 'red_error_small.png';
                        }
						elseif ($download['job_status'] == 'failed')
                        {
                            $icon = 'red_error_small.png';
                        }
                        echo '<td><img src="' . SITE_IMAGE_PATH . '/'.$icon.'" width="16" height="16" alt="' . validation::safeOutputToScreen(UCWords($download['job_status'])) .
                            '" title="' . validation::safeOutputToScreen(UCWords($download['job_status'])) .
                            '"/></td>';
                        echo '<td class="name">';
                        echo validation::safeOutputToScreen($download['url']).(($download['total_size'] > 0)?(' ('.validation::safeOutputToScreen(coreFunctions::formatSize($download['downloaded_size'])).')'):'');
						if(strlen($download['notes']))
						{
							echo '<br/>'.validation::safeOutputToScreen($download['notes']);
						}
						else
						{
							if((int)$download['new_file_id'])
							{
								$file = file::loadById($download['new_file_id']);
								if($file)
								{
									echo '<br/>'.UCWords(t('download', 'Download')).': <a href="'.validation::safeOutputToScreen($file->getFullShortUrl()).'" target="_blank">'.validation::safeOutputToScreen($file->originalFilename).'</a>';
								}
							}
						}
                        echo '</td>';
                        echo '<td>';
                        if(($download['job_status'] == 'cancelled') || ($download['job_status'] == 'failed'))
                        {
                            echo '-';
                        }
                        else
                        {
                            // % progress
                            echo validation::safeOutputToScreen(number_format($download['download_percent'], 2)) . ' %';
							if($downloadSpeed > 0)
							{
								echo '<br/>'.validation::safeOutputToScreen(coreFunctions::formatSize($downloadSpeed)).'s';
							}
                        }
                        echo '</td>';
                        echo '<td class="name" style="text-align: center">';
                        echo validation::safeOutputToScreen(UCWords($download['job_status']));
                        if($download['job_status'] == 'pending')
                        {
                            echo '<br/><a href="#" onClick="confirmRemoveBackgroundUrl('.(int)$download['id'].');">('.t('cancel', 'cancel').')</a>';
                        }
						elseif($download['job_status'] == 'downloading')
                        {
                            echo '<br/><a href="#" onClick="confirmRemoveBackgroundUrl('.(int)$download['id'].');">('.t('cancel', 'cancel').')</a>';
                        }
                        elseif($download['job_status'] == 'cancelled')
                        {
                            echo '<br/><a href="#" onClick="removeBackgroundUrl('.(int)$download['id'].');">('.t('remove', 'remove').')</a>';
                        }
                        elseif(($download['job_status'] == 'complete') || ($download['job_status'] == 'failed'))
                        {
                            echo '<br/><a href="#" onClick="removeBackgroundUrl('.(int)$download['id'].');">('.t('clear', 'clear').')</a>';
                        }
                        echo '</td>';
                        echo '</tr>';
                    }
                
                ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="clear"><!-- --></div>
    </div>

    <div class="clear"><!-- --></div>
</div>

<?php endif; ?>