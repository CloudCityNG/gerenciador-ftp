<?php
define('ADMIN_PAGE_TITLE', 'Support Information');
define('ADMIN_SELECTED_PAGE', 'configuration');
define('ADMIN_SELECTED_SUB_PAGE', 'support_info');

include_once('_local_auth.inc.php');

// page header
include_once(ADMIN_ROOT . '/_header.inc.php');
$dt     = new DateTime();
$phparr = adminFunctions::phpinfoArray();
?>
<div class="row clearfix">
    <div class="col_12">
        <div class="sectionLargeIcon" style="background: url(../assets/img/icons/128px.png) no-repeat;"></div>
        <div class="widget clearfix">
            <h2>Support Information</h2>
            <div class="widget_inside">
            <?php
            if (_CONFIG_DEMO_MODE == true)
            {
                adminFunctions::setError("Viewing the support information is not permitted in demo mode.");
                echo adminFunctions::compileErrorHtml();
            }
            else
            {            
            ?>
                <?php echo adminFunctions::compileNotifications(); ?>
                <div class="clearfix col_12">
                    <div class="col_4">
                        <h3>Support Information</h3>
                    </div>
                    <div class="col_8 last">
                        <div class="form">
                            <div class="clearfix alt-highlight">
                                <div style="padding-left:40px;">When requiring support, please click the "Download File" button below and attach it to your support ticket.</div>
                            </div>
                            <div class="clearfix">
                                <label>Download File:</label>
                                <div class="input"><button name="supportInfo" id="supportInfo" class="button blue" onClick="window.open('support_info_download.php'); return false;">Download File</button></div>
                            </div>                            
                        </div>
                    </div>                       
                </div> 

                <div class="clearfix col_12">
                    <div class="col_4">
                        <h3>Server Information</h3>
                    </div>
                    <div class="col_8 last">
                        <div class="form">
                            <div class="clearfix alt-highlight">
                                <label>Operating System:</label>
                                <div class="input" style="padding-top:6px;"><?php echo php_uname(); ?></div>
                            </div>
                            <div class="clearfix">
                                <label>Web Server:</label>
                                <div class="input" style="padding-top:6px;"><?php echo $_SERVER['SERVER_SIGNATURE']?$_SERVER['SERVER_SIGNATURE']:$_SERVER['SERVER_SOFTWARE']; ?>
                                </div>
                            </div>
                            <div class="clearfix alt-highlight">
                                <label>Server Hostname:</label>
                                <div class="input" style="padding-top:6px;"><?php echo $_SERVER['HTTP_HOST']; ?></div>
                            </div>
                            <div class="clearfix">
                                <label>Server IP Address:</label>
                                <div class="input" style="padding-top:6px;"><?php echo $_SERVER['SERVER_ADDR']; ?></div>
                            </div>
                            <div class="clearfix alt-highlight">
                                <label>Server Time:</label>
                                <div class="input" style="padding-top:6px;"><?php echo $dt->format('d-m-Y H:i:s'); ?></div>
                            </div>
                            <div class="clearfix">
                                <label>Document Root:</label>
                                <div class="input" style="padding-top:6px;"><?php echo $_SERVER['DOCUMENT_ROOT']; ?></div>
                            </div>
                        </div>
                    </div>                        
                </div>
                
                <div class="clearfix col_12">
                    <div class="col_4">
                        <h3>MySQL Information</h3>
                    </div>
                    <div class="col_8 last">
                        <div class="form">
                            <div class="clearfix alt-highlight">
                                <label>MySQL Client Version:</label>
                                <div class="input" style="padding-top:6px;"><?php echo $phparr['mysqli']['Client API library version']; ?></div>
                            </div>
                            <div class="clearfix">
                                <label>MySQL Server Version:</label>
                                <div class="input" style="padding-top:6px;"><?php echo $db->getValue("SELECT version();"); ?></div>
                            </div>
                            <div class="clearfix alt-highlight">
                                <label>MySQL Server Time:</label>
                                <div class="input" style="padding-top:6px;"><?php echo $db->getValue('SELECT NOW();'); ?></div>
                            </div>
                            <div class="clearfix">
                                <label>PDO Installed:</label>
                                <div class="input" style="padding-top:6px;"><?php echo $phparr['PDO']['PDO drivers']; ?></div>
                            </div>
                            <div class="clearfix alt-highlight">
                                <label>PDO Version:</label>
                                <div class="input" style="padding-top:6px;"><?php echo $phparr['pdo_mysql']['Client API version']; ?></div>
                            </div>
                        </div>
                    </div>                        
                </div>  
                            
                <div class="clearfix col_12">
                    <div class="col_4">
                        <h3>PHP Information</h3>
                    </div>
                    <div class="col_8 last">
                        <div class="form">
                            <div class="clearfix alt-highlight">
                                <label>PHP Version:</label>
                                <div class="input" style="padding-top:6px;"><?php echo phpversion(); ?></div>
                            </div>
                            <div class="clearfix">
                                <label>php.ini Location:</label>
                                <div class="input" style="padding-top:6px;"><?php echo php_ini_loaded_file(); ?></div>
                            </div>
                            <div class="clearfix alt-highlight">
                                <label>Current PHP Time:</label>
                                <div class="input" style="padding-top:6px;"><?php echo date('Y-m-d H:i:s'); ?></div>
                            </div>
                            <div class="clearfix">
                                <label>Max Execution Time:</label>
                                <div class="input" style="padding-top:6px;"><?php echo $phparr['Core']['max_execution_time']; ?></div>
                            </div>
                            <div class="clearfix alt-highlight">
                                <label>Max Input Time:</label>
                                <div class="input" style="padding-top:6px;"><?php echo $phparr['Core']['max_input_time']; ?></div>
                            </div>
                            <div class="clearfix">
                                <label>Memory Limit:</label>
                                <div class="input" style="padding-top:6px;"><?php echo $phparr['Core']['memory_limit']; ?></div>
                            </div>
                            <div class="clearfix alt-highlight">
                                <label>Post Max Size:</label>
                                <div class="input" style="padding-top:6px;"><?php echo $phparr['Core']['post_max_size']; ?></div>
                            </div>
                            <div class="clearfix">
                                <label>Upload Max Filesize:</label>
                                <div class="input" style="padding-top:6px;"><?php echo $phparr['Core']['upload_max_filesize']; ?></div>
                            </div>
                            <div class="clearfix alt-highlight">
                                <label>cURL Enabled:</label>
                                <div class="input" style="padding-top:6px;"><?php echo ucfirst($phparr['curl']['cURL support']); ?></div>
                            </div>
                            <div class="clearfix">
                                <label>cURL Version:</label>
                                <div class="input" style="padding-top:6px;"><?php echo $phparr['curl']['cURL Information']; ?></div>
                            </div>
                            <div class="clearfix alt-highlight">
                                <label>Default Timezone:</label>
                                <div class="input" style="padding-top:6px;"><?php echo $phparr['date']['Default timezone']; ?></div>
                            </div>
                            <div class="clearfix">
                                <label>GD Enabled:</label>
                                <div class="input" style="padding-top:6px;"><?php echo ucfirst($phparr['gd']['GD Support']); ?></div>
                            </div>
                            <div class="clearfix alt-highlight">
                                <label>GD Version:</label>
                                <div class="input" style="padding-top:6px;"><?php echo $phparr['gd']['GD Version']; ?></div>
                            </div>
                            <div class="clearfix">
                                <label>Mcrypt Version:</label>
                                <div class="input" style="padding-top:6px;"><?php echo $phparr['mcrypt']['Version']; ?></div>
                            </div>
                            <div class="clearfix alt-highlight">
                                <label>Default Timezone:</label>
                                <div class="input" style="padding-top:6px;"><?php echo $phparr['date']['Default timezone']; ?></div>
                            </div>
                            
                            <div class="clearfix">
                                <label>Loaded Extensions:</label>
                                <div class="input" style="padding-top:6px;">
                                <select name="" id="" class="xxlarge" MULTIPLE>
                                        <?php
                                        
                                        foreach(get_loaded_extensions() AS $e)
                                        {
                                            echo '<option value="">'. $e .'</option>';
                                        }
                                        ?>
                                    </select>                                    
                                </div>
                            </div>                                
                        </div>
                    </div> 
                </div>
                <?php
                }
                ?>
            </div>
        </div>   
    </div>
</div>
<?php
include_once(ADMIN_ROOT . '/_footer.inc.php');
?>