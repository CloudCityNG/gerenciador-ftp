<?php
// mod security may also cause this to fail
// php.ini - output_buffering = Off zlib.output_compression = Off

@ini_set('zlib.output_compression',0);
@ini_set('implicit_flush',1);
@ob_end_clean();
ob_implicit_flush(1);
header('Content-type: text/html; charset=utf-8');
// 1KB of initial data, required by Webkit browsers
echo "<span>" . str_repeat(" ", 1000) . "</span>";
echo 'Begin ...<br />';
for( $i = 0 ; $i < 10 ; $i++ )
{
    echo $i . '<br />';
    ob_end_flush(); 
    ob_flush(); 
    flush(); 
    ob_start();
    sleep(1);
}
echo 'End ...<br />';
?>