<?php
// local template functions
require_once(SITE_TEMPLATES_PATH . '/partial/_template_functions.inc.php');

// get database connection
$db = Database::getDatabase();

// load theme functions
$themeObj = themeHelper::getLoadedInstance();
$totalActiveImages = (int)file::getTotalActivePublicFiles();
$totalActiveFolders = (int)fileFolder::getTotalActivePublicFolders();

// top navigation
require_once(SITE_TEMPLATES_PATH . '/partial/_navigation_header.inc.php');

// load all files
$sQL         = "SELECT COUNT(id) AS total, SUM(fileSize) AS totalFilesize, IF(statusId=1,'active','inactive') AS status FROM file WHERE userId = " . (int) $Auth->id . " GROUP BY IF(statusId=1,'active','inactive')";
$totalData   = $db->getRows($sQL);
$totalActiveFileSize = 0;
foreach($totalData AS $totalDataItem)
{
	if($totalDataItem['status'] == 'active')
	{
		$totalActive = (int)$totalDataItem['total'];
		$totalActiveFileSize = (int)$totalDataItem['totalFilesize'];
	}
	else
	{
		$totalTrash = (int)$totalDataItem['total'];
	}
}

// account stats
$totalFileStorage = UserPeer::getMaxFileStorage($Auth->id);
$storagePercentage = 0;
if ($totalActiveFileSize > 0) {
    $storagePercentage = ($totalActiveFileSize / $totalFileStorage) * 100;
    if ($storagePercentage < 1) {
        $storagePercentage = 1;
    } else {
        $storagePercentage = floor($storagePercentage);
    }
}

// load user object
$user = null;
if ($Auth->loggedIn()) {
    $user = UserPeer::loadUserById($Auth->id);
}

// header top
require_once(SITE_TEMPLATES_PATH . '/partial/_header_file_manager_top.inc.php');
?>
<body class="page-body">
    <div class="page-container horizontal-menu with-sidebar fit-logo-with-sidebar <?php echo($Auth->loggedIn() == true)?'logged-in':'logged-out'; ?>">	

        <div class="sidebar-menu fixed">
			<div class="sidebar-mobile-menu visible-xs"> <a href="#" class="with-animation"><i class="entypo-menu"></i> </a> </div>
			<?php if (UserPeer::getAllowedToUpload() == true): ?>
				<div class="sidebar-mobile-upload visible-xs"><a href="#" onClick="uploadFiles(); return false;"><?php echo t('upload_account', 'Upload'); ?>&nbsp;&nbsp;<span class="glyphicon glyphicon-cloud-upload"></span></a> </div>
			<?php endif; ?>
			
            <!-- logo -->
            <div class="siderbar-logo">
                <a href="<?php echo coreFunctions::getCoreSitePath(); ?>/index.<?php echo SITE_CONFIG_PAGE_EXTENSION; ?>">
                    <img src="<?php echo $themeObj->getMainLogoUrl(); ?>" alt="<?php echo SITE_CONFIG_SITE_NAME; ?>" />
                </a>
            </div>
			<div id="folderTreeview"></div>
            <div class="clear"></div>
        </div>

        <header class="navbar navbar-fixed-top"><!-- set fixed position by adding class "navbar-fixed-top" -->
            <div class="navbar-inner">
				<?php if($Auth->loggedIn()): ?>
                <div class="navbar-form navbar-form-sm navbar-left shift" ui-shift="prependTo" data-target=".navbar-collapse" role="search">
                    <div class="form-group">
                        <div class="input-group" id="top-search">
                            <input type="text" value="<?php echo isset($_REQUEST['t'])?validation::safeOutputToScreen($_REQUEST['t']):''; ?>" class="form-control input-sm bg-light no-border rounded padder typeahead" placeholder="<?php echo addslashes(t('account_header_search_your_files', 'Search your files...')); ?>" onKeyUp="handleTopSearch(event, this); return false;" id="searchInput">
                            <span class="input-group-btn">
                                <button type="submit" class="btn btn-sm bg-light rounded" onClick="handleTopSearch(null, $('#searchInput')); return false;" title="" data-original-title="<?php echo t('filter', 'Filter'); ?>" data-placement="bottom" data-toggle="tooltip"><i class="entypo-search"></i></button>
                                <button type="submit" class="btn btn-sm bg-light rounded" onClick="showFilterModal(); return false;" title="" data-original-title="<?php echo t('advanced_search', 'Advanced Search'); ?>" data-placement="bottom" data-toggle="tooltip"><i class="entypo-cog"></i></button>
                            </span>
                        </div>
                    </div>
                </div>
				<?php else: ?>
				<div class="navbar-form navbar-form-sm navbar-left shift non-logged-in-logo">
					<a href="<?php echo coreFunctions::getCoreSitePath(); ?>/index.<?php echo SITE_CONFIG_PAGE_EXTENSION; ?>">
						<img src="<?php echo $themeObj->getInverseLogoUrl(); ?>" alt="<?php echo SITE_CONFIG_SITE_NAME; ?>" />
					</a>
				</div>
				<?php endif; ?>

				<?php if (UserPeer::getAllowedToUpload() == true): ?>
					<div class="upload-button-wrapper pull-left">
						<button class="btn btn-green" type="button" onClick="uploadFiles(); return false;"><?php echo t('upload_account', 'Upload'); ?>&nbsp;&nbsp;<span class="glyphicon glyphicon-cloud-upload"></span></button>
					</div>
				<?php endif; ?>
                
                <div class="header-home-button pull-left">
                    <a href="<?php echo !$Auth->loggedIn()?(validation::safeOutputToScreen($_SESSION['sharekeyOriginalUrl'])):"#"; ?>" onClick="<?php echo $Auth->loggedIn()?"loadImages(-1, 1); return false;":""; ?>">
                    	<i class="glyphicon glyphicon-home"></i>
                    </a>
                </div>

                <ul class="mobile-account-toolbar-wrapper nav navbar-right pull-right">
                
                <?php if ($Auth->loggedIn()): ?>

                        
						<?php
						// reload user level from database encase they've just upgraded
						$packageId = $db->getValue('SELECT level_id FROM users WHERE id = ' . (int) $Auth->id . ' LIMIT 1');
						if($packageId == 20)
						{
						?>
						<li class="root-level responsive-Hide">
                            <a href="<?php echo ADMIN_WEB_ROOT; ?>/" target="_blank">
                                <span class="badge badge-danger badge-roundless"><?php echo strtoupper(t('admin_user', 'Admin User')); ?></span>
                            </a>
                        </li>
						<?php
						}
						?>
                        
                        <li class="dropdown account-nav-icon">
                            <a href="#" data-toggle="dropdown" class="dropdown-toggle clear">
                                <span class="thumb-sm avatar pull-right">
                                    <img width="40" height="40" class="img-circle" alt="<?php echo validation::safeOutputToScreen($Auth->getAccountScreenName()); ?>" src="<?php echo WEB_ROOT; ?>/page/view_avatar.php?id=<?php echo $Auth->id; ?>&width=40&height=40"/>
                                </span>
                                <span class="user-screen-name hidden-sm hidden-md"><?php echo validation::safeOutputToScreen($Auth->getAccountScreenName()); ?></span> <b class="caret"></b>
                            </a>
                            <!-- dropdown -->
                            <ul class="dropdown-menu">
                                <?php
                                $label = UCWords(t('unlimited', 'unlimited'));
                                if ($totalFileStorage > 0)
								{
                                    $label = $storagePercentage . '%';
                                }
                                ?>

                                <li class="account-menu bg-light" title="<?php echo $label; ?>" onClick="window.location='<?php echo coreFunctions::getCoreSitePath() . '/account_edit.' . SITE_CONFIG_PAGE_EXTENSION; ?>';" style="cursor: pointer;">
                                    <div>
                                        <p>
                                <?php if ($totalFileStorage > 0): ?>
                                                <span><span id="totalActiveFileSize"><?php echo validation::safeOutputToScreen(coreFunctions::formatSize($totalActiveFileSize)); ?></span> <?php echo t("of", "of"); ?> <?php echo validation::safeOutputToScreen(coreFunctions::formatSize($totalFileStorage)); ?> <?php echo t("used", "used"); ?></span>
                                <?php else: ?>
                                                <span><span id="totalActiveFileSize"><?php echo validation::safeOutputToScreen(coreFunctions::formatSize($totalActiveFileSize)); ?></span> <?php echo t("of", "of"); ?> <?php echo UCWords(t('unlimited', 'unlimited')); ?></span>
                                <?php endif; ?>
                                        </p>
                                    </div>
                                    <div class="progress progress-xs m-b-none dker">
                                        <div style="width: <?php echo $storagePercentage; ?>%" aria-valuemax="100" aria-valuemin="0" aria-valuenow="<?php echo $storagePercentage; ?>" role="progressbar" class="progress-bar progress-bar-success"></div>
                                    </div>
                                </li>
                                <li>
                                    <a href="<?php echo coreFunctions::getCoreSitePath() . '/account_edit.' . SITE_CONFIG_PAGE_EXTENSION; ?>"> <i class="entypo-cog"></i><?php echo t('file_manager_account_settings', 'Account Settings'); ?></a>
                                </li>
                                <li class="divider"></li>
								<?php if($packageId == 20): ?>
								<li>
                                    <a href="<?php echo ADMIN_WEB_ROOT; ?>/" target="_blank"></i> <i class="entypo-users"></i><?php echo t('admin_area_link', 'Admin Area'); ?></a>
                                </li>
                                <li class="divider"></li>
								<?php endif; ?>
                                <li>
                                    <a href="<?php echo coreFunctions::getCoreSitePath() . '/logout.' . SITE_CONFIG_PAGE_EXTENSION; ?>"> <i class="entypo-logout"></i><?php echo t('file_manager_logout', 'Logout'); ?></a>
                                </li>
                            </ul>
                            <!-- / dropdown -->
                        </li>
<?php else: ?>
                        <li>
                            <a href="<?php echo coreFunctions::getCoreSitePath() . '/login.' . SITE_CONFIG_PAGE_EXTENSION; ?>">
                                <i class="entypo-lock"></i>
    <?php echo t('login', 'login'); ?>
                            </a>
                        </li>

<?php endif; ?>
                </ul>
            </div>
        </header>

        <div id="main-ajax-container" class="layer"></div>

<?php
// file manager javascript
require_once(SITE_TEMPLATES_PATH . '/partial/_account_home_javascript.inc.php');
?>