<?php
echo view("components/head");
echo view("components/studio/component-card-projects");
echo view("components/studio/component-toolbar");
echo view("components/modals/add_project");
echo view("components/modals/project_users");
?>
    <div id="app">
        <toolbar ref="toolbar"
                 :buttons="toolbar.buttons"
                 :breadcrumbs="toolbar.breadcrumbs"
                 :current_user="currentUser"
                 :tags="tags">
        </toolbar>
        <template v-if="loaded">
            <template v-if="projects.length == 0 && tag == ''">
                <center>
                    <img class="welcome-image" src="assets/images/first-project.png" />
                </center>
                <center>
                    <button v-if="servers.length == 0 && currentUser.is_admin" @click="movePage('/admin/servers')" class="dmiux_button dmiux_button_primary-outline mx-auto" type="button">+ Create your first server</button>
                    <button v-else-if="databases.user_databases.length == 0 && checkUserPerms('datalake_create')" @click="movePage('/data-lake?show_database_options')" class="dmiux_button dmiux_button_primary-outline" type="button">+ Create your first database</button>
                    <template v-else-if="databases.user_databases.length == 0">
                        <h3>No databases exist yet</h3> 
                        <p>You do not have permissions to create one</p>
                    </template>
                    <button v-else-if="checkUserPerms('studio_create')" @click="$refs.toolbar.createProject()" class="dmiux_button dmiux_button_primary-outline" type="button">+ Create your first project</button>
                    <template v-else>
                        <h3>No projects exist yet</h3> 
                        <p>You do not have permissions to create one</p>
                    </template>
                </center>
            </template>
            <div v-else class="dmiux_content warehouse-margin">
                <div v-if="flash_error && flash_error !== ''" class="dmiux_grid-cont dmiux_grid-cont_fw">
                    <div class="alert alert-danger" role="alert">{{ flash_error }}</div>
                </div>
                <card-projects :projects="projects"></card-projects>
            </div>
        </template>

        <add-project :editing="editing"
                     :selected_project="selected_project">
        </add-project>
        <project-users :selected_project="selected_project"></project-users>
    </div>
    <script>
        var flash_error = '<?php echo $flash_error ?>';
    </script>
    <script defer src="/assets/js/studio.js?#{release}#"></script>
<?php echo view("components/foot");?>
