<?php echo view("components/head"); ?>

<?php echo view("components/warehouse/component-card-databases"); ?>
<?php echo view("components/warehouse/component-toolbar"); ?>
<?php echo view("components/general/component-connector-settings"); ?>
<?php echo view("components/modals/integration_logs"); ?>
<?php echo view("components/modals/integration_manager"); ?>
<?php echo view("components/modals/project_settings"); ?>
<?php echo view("components/modals/create_empty_database"); ?>
<?php echo view("components/modals/request_access"); ?>

    <div id="app">
        <toolbar ref="toolbar"
                 :buttons="toolbar.buttons"
                 :breadcrumbs="toolbar.breadcrumbs"
                 :tags="tags"
                 :servers="servers"
                 :current_user="currentUser">
        </toolbar>
        <template v-if="ready.databases">
            <template v-if="database_count == 0">
                <Transition name="slide" mode="out-in">
                    <div key="one" v-if="! show_database_options || servers.length == 0">
                        <img class="welcome-image" src="assets/images/first-database.png" />
                        <div>
                            <button v-if="servers.length == 0 && currentUser.is_admin" @click="movePage('/admin/servers')" class="dmiux_button dmiux_button_primary-outline mx-auto" type="button">+ Create your first server</button>
                            <div v-else-if="servers.length == 0">
                                <h5 class="text-center">No servers exist</h5> 
                                <p class="text-center">A team administrator must add a server before you can create a database</p>
                            </div>
                            <button v-else-if="checkUserPerms('datalake_create')" @click="show_database_options = !show_database_options;" class="dmiux_button dmiux_button_primary-outline mx-auto" type="button">+ Create your first database</button>
                            <div v-else>
                                <h5 class="text-center">No databases available</h5> 
                                <p class="text-center">A team administrator must give you access to a database before you can continue</p>
                            </div>
                        </div>
                    </div>
                    <div key="two" v-else>
                        <div class="dmiux_grid-row">
                            <div class="dmiux_grid-col dmiux_grid-col_12 text-center mb-2">
                                <h2>Choose an Option:</h2>
                            </div>
                        </div>
                        <div class="dmiux_cards__row dmiux_grid-row">
                            <div class="dmiux_grid-col dmiux_grid-col_3 dmiux_grid-col_lg-2 dmiux_grid-col_md-1"></div>
                            <div class="dmiux_grid-col dmiux_grid-col_3 dmiux_grid-col_lg-4 dmiux_grid-col_md-5 dmiux_grid-col_sm-12 dmiux_cards__col database-choice cursor-p" @click="$root.$refs.toolbar.create_empty_database()">
                                <div class="dmiux_cards__item text-center">
                                    <img src="<?php echo config('services.dmiux.url') ?>/img/empty-db.svg" class="database_creation-icons mb-2" />
                                    <h5 class="mt-4">Create an empty database</h5> 
                                    <p>You’ll be able to upload CSV files and other data</p>  
                                </div>
                            </div>
                            <div class="dmiux_grid-col dmiux_grid-col_3 dmiux_grid-col_lg-4 dmiux_grid-col_md-5 dmiux_grid-col_sm-12 dmiux_cards__col database-choice cursor-p" @click="movePage('/data-lake/create')">
                                <div class="dmiux_cards__item text-center">
                                    <img src="<?php echo config('services.dmiux.url') ?>/img/connector-db.svg" class="database_creation-icons mb-2" />
                                    <h5 class="mt-4">Use a data connector</h5> 
                                    <p>Download data from cloud APIs and other database systems</p>  
                                </div>
                            </div>
                            <div class="dmiux_grid-col dmiux_grid-col_3 dmiux_grid-col_lg-2 dmiux_grid-col_md-1"></div>
                        </div>
                    </div>
                </Transition>
            </template>
            <div v-else class="dmiux_content">
                <div v-if="flashError && flashError !== ''" class="alert alert-danger" role="alert">{{ flashError }}</div>
                <div>
                    <card-warehouse :databases="databases" :callbacks="callbacks" :tags="tags"></card-warehouse>
                </div>
            </div>
        </template>

        <integration-logs ref="integration_logs" :open="modals.integration_logs"></integration-logs>
        <integration-manager :jobs="database_jobs"></integration-manager>
        <project-settings ref="project_settings" :settings="settings" :selected-database="selectedDatabase" :callbacks="callbacks" :dependencies="databaseDependencies"></project-settings>
        <create-empty-database :open="add_model_open" :servers="servers"></create-empty-database>
        <request-access :open="modals.request_access" :selected-database="requestedDatabase"></request-access>
    </div>
    <script>
        var flashError = '<?php echo $flashError ?? '' ?>';
        var system_timezone = '<?php echo $system_timezone ?? '' ?>';
        var system_time_offset = <?php echo $system_time_offset ?? 0 ?>;
        var default_server_id = <?php echo is_null($default_server_id ?? null) ?  'null' : $default_server_id; ?>;
    </script>
    <script defer src="/assets/js/warehouse.js?#{release}#"></script>
<?php echo view("components/foot");?>
