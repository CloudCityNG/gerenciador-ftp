<div class="file-browse-container-wrapper">
    <div class="file-browse-container" <?php if (defined('_INT_FILE_ID')): ?>style="display: none;"<?php endif; ?>>
        <div class="toolbar-container">

            <!-- toolbar -->
            <div class="col-md-6 col-sm-8 clearfix">
                <!-- breadcrumbs -->
                <div class="row breadcrumbs-container">
                    <!-- Collect the nav links, forms, and other content for toggling -->
                    <div class="col-md-12 col-sm-12 clearfix">
                        <ol id="folderBreadcrumbs" class="breadcrumb bc-3 pull-left">
                            <li>
                                <a href="#">
                                    <i class="entypo-folder"></i><?php echo t('your_uploads', 'Your Uploads'); ?>
                                </a>
                            </li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-sm-4 clearfix hidden-xs">
                <div class="list-inline pull-right responsiveHide">
                    <div class="btn-toolbar pull-right" role="toolbar">
                        <div class="btn-group">
                            <button class="btn btn-white disabled fileActionLinks" type="button" title="" data-original-title="<?php echo t('file_manager_links', 'Links'); ?>" data-placement="bottom" data-toggle="tooltip" onClick="viewFileLinks();
                                    return false;"><i class="entypo-link"></i></button>
                            <button class="btn btn-white disabled fileActionLinks" type="button" title="" data-original-title="<?php echo t('file_manager_delete', 'Delete'); ?>" data-placement="bottom" data-toggle="tooltip" onClick="deleteFiles();
                                    return false;"><i class="entypo-cancel"></i></button>
                            <button class="btn btn-white" type="button" title="" data-original-title="<?php echo t('list_view', 'List View'); ?>" data-placement="bottom" data-toggle="tooltip" onClick="toggleViewType();
                                    return false;" id="viewTypeText"><i class="entypo-list"></i></button>
                            <button class="btn btn-white" type="button" title="" data-original-title="<?php echo t('fullscreen', 'Fullscreen'); ?>" data-placement="bottom" data-toggle="tooltip" onClick="toggleFullScreenMode();
                                    return false;"><i class="entypo-resize-full"></i></button>
                        </div>

                        <div class="btn-group">
                            <div class="btn-group">
                                <button id="filterButton" data-toggle="dropdown" class="btn btn-white dropdown-toggle" type="button">
                                    <?php echo t('account_home_sort_by', 'Sort By'); ?> <i class="entypo-arrow-combo"></i>
                                </button>
                                <ul role="menu" class="dropdown-menu dropdown-white pull-right">
                                    <li class="disabled"><a href="#"><?php echo t('account_home_sort_by', 'Sort By'); ?></a></li>
                                    <?php
                                    foreach ($orderByOptions AS $k => $orderByOption) {
                                        echo '<li><a href="#" onClick="updateSorting(\'' . validation::safeOutputToScreen($k) . '\', \'' . validation::safeOutputToScreen(t($k, $orderByOption)) . '\', this); return false;">' . validation::safeOutputToScreen(t($k, $orderByOption)) . '</a></li>';
                                    }
                                    ?>
                                </ul>
                                <input name="filterOrderBy" id="filterOrderBy" value="order_by_filename_asc" type="hidden"/>
                            </div>

                            <div class="btn-group">
                                <button id="perPageButton" data-toggle="dropdown" class="btn btn-white dropdown-toggle" type="button">
                                    <?php echo $defaultPerPage; ?> <i class="entypo-arrow-combo"></i>
                                </button>
                                <ul role="menu" class="dropdown-menu dropdown-white pull-right per-page-menu">
                                    <li class="disabled"><a href="#"><?php echo UCWords(t('account_home_per_page', 'Per Page')); ?></a></li>
                                    <?php
                                    foreach ($perPageOptions AS $perPageOption) {
                                        //if ($perPageOption == $defaultPerPage)
                                        echo '<li><a href="#" onClick="updatePerPage(\'' . validation::safeOutputToScreen($perPageOption) . '\', \'' . validation::safeOutputToScreen($perPageOption) . '\', this); return false;">' . validation::safeOutputToScreen($perPageOption) . '</a></li>';
                                    }
                                    ?>
                                </ul>
                                <input name="perPageElement" id="perPageElement" value="100" type="hidden"/>
                            </div>
                        </div>
                    </div>
                    <ol id="folderBreadcrumbs2" class="breadcrumb bc-3 pull-right">
                        <li class="active">
                            <span id="statusText"></span>
                        </li>
                    </ol>
                </div>
            </div>
            <!-- /.navbar-collapse -->
        </div>

        <div class="panel panel-primary file-manager-container">
            <div id="fileManagerContainer" class="panel-body">

                <img src="<?php echo SITE_IMAGE_PATH; ?>/file_icons/sprite_48px.png" style="width: 1px; height:1px; position: absolute; top: -99999px;"/>
                <div class="file-listing-wrapper">
                    <!-- main file listing section -->
                    <div id="fileManagerWrapper" class="fileManagerWrapper">
                        <div id="fileManager" class="fileManager fileManagerIcon"><span class=""><?php echo t('file_manager_loading', 'Loading...'); ?></a></div>
                        <div class="clear"></div>
                        <input id="nodeId" type="hidden" value="-1"/>
                    </div>
                </div>

                <div class="row paginationRow">
                    <div id="pagination" class="paginationWrapper col-md-12 responsiveAlign"></div>
                </div>
            </div>

        </div>
    </div>
</div>
