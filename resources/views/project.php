<?php echo view("components/head"); ?>
<?php echo view("components/project/component-toolbar"); ?>

<?php echo view("components/project/component-widget-tables"); ?>
<?php echo view("components/project/component-widget-activity"); ?>
<?php echo view("components/project/component-widget-flags"); ?>
<?php echo view("components/project/component-widget-links"); ?>
<?php echo view("components/project/component-widget-snapshots"); ?>
<?php echo view("components/project/component-widget-queries"); ?>
<?php echo view("components/project/component-widget-publishers"); ?>
<?php echo view("components/project/component-widget-users"); ?>
<?php echo view("components/project/component-widget-settings"); ?>

<?php echo view("components/modals/link_hyperlink"); ?>
<?php echo view("components/modals/column_search"); ?>

<div id="app">
    <toolbar :buttons="toolbar.buttons"
             :breadcrumbs="toolbar.breadcrumbs"
             :tables="tables"
             :current_user="currentUser">
    </toolbar>
    <div class="dmiux_content">
        <div class="dmiux_grid-row">
            <div class="dmiux_grid-col dmiux_grid-col_25 dmiux_grid-col_lg-3 dmiux_grid-col_md-12">
                <div class="dmiux_block m-2">
                    <div class="dmiux_vtabs">
                        <div class="dmiux_vtabs__head">
                            <div class="dmiux_vtabs__title">Menu</div>
                        </div>
                        <div class="dmiux_vtabs__cont dmiux_noscrollbar">

                            <router-link class="text-decoration-none" :to="{ name: 'tables', params: { } }">                  
                                <button type="button"
                                        :class="$route.name == 'tables' ? 'dmiux_active' : ''"
                                        class="dmiux_vtabs__item">Tables &amp; Views
                                </button>
                            </router-link>
                            <router-link class="text-decoration-none" :to="{ name: 'activity', params: { } }">                  
                                <button type="button"
                                        :class="$route.name == 'activity' ? 'dmiux_active' : ''"
                                        class="dmiux_vtabs__item">Activity
                                </button>
                            </router-link>
                            <router-link class="text-decoration-none" :to="{ name: 'flags', params: { } }">                  
                                <button type="button"
                                        :class="$route.name == 'flags' ? 'dmiux_active' : ''"
                                        class="dmiux_vtabs__item">Flags
                                </button>
                            </router-link>
                            <router-link class="text-decoration-none" :to="{ name: 'attachments', params: { } }">                  
                                <button type="button"
                                        :class="$route.name == 'attachments' ? 'dmiux_active' : ''"
                                        class="dmiux_vtabs__item">Attachments
                                </button>
                            </router-link>
                            <router-link class="text-decoration-none" :to="{ name: 'snapshots', params: { } }">                  
                                <button type="button"
                                        :class="$route.name == 'snapshots' ? 'dmiux_active' : ''"
                                        class="dmiux_vtabs__item">Snapshots
                                </button>
                            </router-link>
                            <router-link class="text-decoration-none" :to="{ name: 'queries', params: { } }">                  
                                <button type="button"
                                        :class="$route.name == 'queries' ? 'dmiux_active' : ''"
                                        class="dmiux_vtabs__item">Saved Queries
                                </button>
                            </router-link>
                            <router-link class="text-decoration-none" :to="{ name: 'publishers', params: { } }">                  
                                <button type="button"
                                        :class="$route.name == 'publishers' || $route.name == 'logs' ? 'dmiux_active' : ''"
                                        class="dmiux_vtabs__item">Publishers
                                </button>
                            </router-link>
                            <router-link class="text-decoration-none" :to="{ name: 'users', params: { } }">                  
                                <button type="button"
                                        :class="$route.name == 'users' ? 'dmiux_active' : ''"
                                        class="dmiux_vtabs__item">Users
                                </button>
                            </router-link>
                            <router-link class="text-decoration-none" :to="{ name: 'settings', params: { } }">                  
                                <button type="button"
                                        :class="$route.name == 'settings' ? 'dmiux_active' : ''"
                                        class="dmiux_vtabs__item">Settings
                                </button>
                            </router-link>
                        </div>
                    </div>
                </div>
            </div>
            <div v-show="ready" class="dmiux_grid-col dmiux_grid-col_95 dmiux_grid-col_lg-9 dmiux_grid-col_md-12">
                <div class="dmiux_block m-2">
                    <router-view></router-view>
                </div>
            </div>
        </div>
    </div>
    
    <column-search
        :not_found="not_found"
        :columns="columns">
    </column-search>
</div>

<script>
    var project_id = <?php echo $project_id; ?>;
    var completed = '<?php echo $completed; ?>';
    var max_size = '<?php echo $max_size; ?>';
    var destination_schema_id = '<?php echo $destination_schema_id; ?>';
    var from_download_link = '<?php echo $from_download_link; ?>';
    var file_upload_url = '<?php echo $file_upload_url; ?>';
</script>
<script defer src="/assets/js/project.js?#{release}#"></script>
<?php echo view("components/foot"); ?>