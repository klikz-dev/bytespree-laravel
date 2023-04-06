<script type="text/x-template" id="component-widget-settings">
    <div class="dmiux_grid-cont dmiux_grid-cont_fw" id="settings">
        <template v-if="$root.checkPerms('project_manage', true) == false">
            <div class="alert alert-info"><b>Hiya!</b> You do not have access to this project's settings.</div>
        </template>
        <template v-else>
            <div class="dmiux_block grid_margin">
                <div class="dmiux_block__head">Settings</div>
                <div class="dmiux_block__cont form-group">
                    <label for="input-destination_schema">Destination schema</label>
                    <div class="dmiux_select">
                        <select id="input-destination_schema"
                                class="dmiux_select__select"
                                v-model="$parent.destination_schema_id" required>
                            <option disabled>Choose a destination schema</option>
                            <option v-for="s in destination_schemas" :value="s.id">{{ s.name }}</option>
                        </select>
                        <div class="dmiux_select__arrow"></div>
                    </div>
                </div>
                <div class="dmiux_block__cont">
                    <div v-for="setting in settings">
                        <div v-if="setting.type == 'checkbox'" class="form-group dmiux_checkbox">
                            <input type="checkbox" class="dmiux_checkbox__input" :id="'setting_' + setting.name" v-model="setting.value">
                            <div class="dmiux_checkbox__check"></div>
                            <label :for="'setting_' + setting.name">{{ setting.label }}</label>
                        </div>
                        <div v-else class="form-group dmiux_input">
                            <label :for="'setting_' + setting.name">{{ setting.label }}</label>
                            <span>
                                <input type="text" class="dmiux_input__input" :id="'setting_' + setting.name" v-model="setting.value">
                            </span>
                        </div>
                    </div>

                    <button type="button" class="dmiux_button" @click="updateSettingValue()" id="studio-project-save-setting">Save Setting</button>  
                </div>
            </div>

            <div class="dmiux_block grid_margin">
                <div class="dmiux_block__head">
                    SQL Access <span class="tooltip-pretty fas fa-info-circle fa-lg mr-1 text-primary cursor-p"
                        title="<p>Enabling SQL access will generate a user to be used externally.</p><p>This user will be given permission to:</p> <ul><li>read all schemas in the primary database</li><li>write to the studio schema</li><li>destroy from the studio schema</li></ul>">
                    </span>
                </div>
                <div class="dmiux_block__cont w-100">
                    <div class="dmiux_checkbox mb-3">
                        <input @change="manageSqlUser()" :checked="sql_user_enabled"  type="checkbox" class="dmiux_checkbox__input">
                        <div class="dmiux_checkbox__check"></div>
                        <div class="dmiux_checkbox__label">Enable SQL Access</div>
                    </div>
                    <template v-if="this.sql_user_enabled">
                        <table class="dmiux_data-table__table">
                            <tbody>
                                <tr>
                                    <td></td>
                                    <td><strong>Username</strong></td>
                                    <td>{{ sql_user.username }}</td>
                                </tr>
                                <tr>
                                    <td>
                                        <span title="Update Password" @click="updatePassword();" class="fas fa-sync-alt cursor-p link-hover"></span>
                                    </td>
                                    <td><strong>Password</strong></td>
                                    <td>{{ sql_user.password }}</td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td><strong>Hostname</strong></td>
                                    <td>{{ connectivity_info.host }}</td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td><strong>Port</strong></td>
                                    <td>{{ connectivity_info.port }}</td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td><strong>Database</strong></td>
                                    <td>{{ connectivity_info.database }}</td>
                                </tr>
                                <tr v-if="server_has_certificate">
                                    <td></td>
                                    <td><strong>CA Certificate</strong></td>
                                    <td><a target="_blank" :href="'/internal-api/v1/data-lakes/' + connectivity_info.database_id + '/sql-user/certificate'">Download CA Certificate</a></td>
                                </tr>
                                
                            </tbody>
                        </table>
                    </template>
                </div>
            </div>
            <div class="dmiux_block dmiux_grid-row">
                <div class="alert alert-info w-100">
                    <div class="dmiux_grid-row">
                        <button @click="updateCompleted()"
                                type="button"
                                id="studio-project-update-completed-status"
                                class="dmiux_button">
                                <template v-if="$root.completed == 'false'">
                                    Mark Project as Complete&nbsp;&nbsp;
                                    <span class="fas fa-check"></span>
                                </template>
                                <template v-else>
                                    Mark Project as Incomplete&nbsp;&nbsp;
                                    <span class="fas fa-times"></span>
                                </template>
                        </button> 
                        <label v-if="$root.completed == 'false'" class="mt-1 ml-1">Marking as complete will notify users when changes are made to tables</label>
                        <label v-else class="mt-1 ml-1">Marking as incomplete will stop notifying users about changes made to tables</label>
                    </div>
                </div>
                <div class="alert alert-danger w-100 mb-0">
                    <div class="dmiux_grid-row">
                        <button class="dmiux_button dmiux_button_danger" type="button" @click="deleteStudioProject()" id="studio-project-delete">Delete This Studio Project&nbsp;&nbsp;
                            <span class="fas fa-times"></span></button>
                        <label class="mt-1 ml-1">Deleting this project will irreversibly remove it from your team's Studio environment</label>
                    </div>
                </div>
            </div>
        </template>
    </div>
</script>

<script>
    var widget_settings = Vue.component('widget-settings', {
        template: '#component-widget-settings',
        data: function() {
            return {
                settings: [],
                destination_schemas: [],
                server_has_certificate: false,
                sql_user_enabled: false,
                sql_user: {
                    username: null,
                    password: null,
                },
                connectivity_info: null,
                connectivity_info_template: {
                    host: null,
                    port: null,
                    database: null,
                    database_id: null,
                }
            };
        },
        methods: {
            getSettings: function() {
                this.$root.loading(true);
                fetch(`/internal-api/v1/studio/projects/${this.$root.project_id}/settings`)
                    .then(response => response.json())
                    .then(json => {
                        this.settings = json.data; 
                        if(this.settings){
                            for(i = 0; i <= this.settings.length - 1; i++)
                            {
                                if(this.settings[i].value == "false"){
                                    this.settings[i].value = false;
                                } else if(this.settings[i].value == "true"){
                                    this.settings[i].value = true;
                                }
                            }
                        }       
                        this.$root.loading(false);
                    });
            },
            getDestinationSchemas() {
                fetch(`/internal-api/v1/admin/schemas`)
                    .then(response => response.json())
                    .then(json => {
                        this.destination_schemas = json.data;
                        this.$root.loading(false);
                    })
            },
            deleteStudioProject: function () {
                var check = prompt("By deleting this project, any flags, comments, mappings, SQL views, and dependent SQL views will also be deleted. Are you sure you want to delete this project?. Type 'DELETE' to continue.");
                if(check != null && check.toLowerCase() == "delete")
                {
                    this.$root.loading(true);
                    fetch(`/internal-api/v1/studio/projects/${this.$root.project_id}`, { method: 'delete' })
                        .then(FetchHelper.handleJsonResponse)
                        .then(json => {
                            this.$root.loading(false);
                            window.location.href = `/studio?message=${encodeURIComponent("Studio project deleted successfully!")}&message_type=success`
                        })
                        .catch((error) => {
                            this.$root.loading(false);
                            ResponseHelper.handleErrorMessage(error, "Failed to delete project");
                        });
                }
            },
            updateSettingValue: function () {
                let options = FetchHelper.buildJsonRequest({
                    "destination_schema_id": this.$root.destination_schema_id
                }, 'put');

                this.$root.loading(true);
                fetch(`/internal-api/v1/studio/projects/${this.$root.project_id}/destination-schema`, options)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        if (this.settings.length == 0) {
                            this.$root.getProjectDetails();
                            notify.success("Setting saved successfully!");
                            this.$root.loading(false);
                            return;
                        }

                        let options = FetchHelper.buildJsonRequest({
                            "settings": this.settings
                        }, 'put');

                        fetch(`/internal-api/v1/studio/projects/${this.$root.project_id}/settings`, options)
                            .then(FetchHelper.handleJsonResponse)
                            .then(json => {
                                this.$root.loading(false);
                                this.getSettings();
                                this.getProjectDetails();
                                notify.success("Setting saved successfully!");
                            })
                            .catch((error) => {
                                this.$root.loading(false);
                                ResponseHelper.handleErrorMessage(error, "Settings failed to save.");
                            });
                })
                .catch((error) => {
                    ResponseHelper.handleErrorMessage(error, "Settings failed to save.");
                    this.$root.loading(false);
                });
            },
            openRoleSelect: function () {      
                this.$root.user_roles = [];     
                for (i = 0; i <= this.$root.users.length - 1; i++) {
                    if (this.$root.users[i]['role_id']!= null){
                        this.$root.user_roles.push({
                            "user_id": this.$root.users[i]['id'],
                            "role_id": this.$root.users[i]['role_id']
                        });
                    }
                }
                openModal("#modal-roles");
            },
            updateCompleted: function () {
                if(!confirm("Are you sure you want to change this project's status?")) {
                    return false;
                }

                this.$root.loading(true);
                fetch(`/internal-api/v1/studio/projects/${this.$root.project_id}/completed`, { method: 'put' })
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.$root.loading(false);
                        if(json.data.updated == false) {
                            this.$root.completed = 'false';
                        } else if(json.data.updated == true) {
                            this.$root.completed = 'true';
                        } else {
                            notify.danger("There was a problem changing the status of this project.");
                        }
                    })
                    .catch((error) => {
                        this.$root.loading(false);
                        ResponseHelper.handleErrorMessage(error, "There was a problem changing the status of this project.");
                    });

            },
            manageSqlUser () {
                if (this.sql_user_enabled == true) {
                    this.removeSqlUser();
                    return;
                }

                this.createSqlUser();
            },
            createSqlUser () {
                let db_password_length = 10;
                this.$root.loading(true);

                let password = this.generatePassword(db_password_length);
                let options = FetchHelper.buildJsonRequest({
                    "password": password
                }, 'post');

                fetch(baseUrl + `/internal-api/v1/studio/projects/${this.$root.project_id}/sql-user`, options)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.sql_user.username = json.data.user.username;
                        this.sql_user.password = json.data.user.password;
                        this.connectivity_info = json.data.connectivity_info;
                        this.sql_user_enabled = true;
                        this.$root.loading(false);
                        notify.success(json.message);
                    })
                    .catch((error) => {
                        ResponseHelper.handleErrorMessage(error, "Could not generate a username");
                        this.$root.loading(false);
                    });
            },
            removeSqlUser () {
                this.$root.loading(true);

                fetch(baseUrl + `/internal-api/v1/studio/projects/${this.$root.project_id}/sql-user`, {method: 'delete'})
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.sql_user.username = null;
                        this.sql_user.password = null;
                        this.sql_user_enabled = false;
                        this.connectivity_info = this.connectivity_info_template;
                        this.$root.loading(false);
                        notify.success("SQL user has been removed successfully");
                    })
                    .catch((error) => {
                        ResponseHelper.handleErrorMessage(error, "SQL user could not be removed");
                        this.$root.loading(false);
                    });
            },
            updatePassword () {
                this.$root.loading(true);

                let password = this.generatePassword(10);
                let options = FetchHelper.buildJsonRequest({
                    "password": password
                }, 'put');

                fetch(baseUrl + `/internal-api/v1/studio/projects/${this.$root.project_id}/sql-user`, options)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.sql_user.password = json.data.user.password;
                        this.$root.loading(false);
                        notify.success("Password updated successfully");
                    })
                    .catch((error) => {
                        ResponseHelper.handleErrorMessage(error, "There was a problem updating the SQL user's password");
                        this.$root.loading(false);
                    });
            },
            generatePassword (length) {
                let db_password_charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                var password = "";
                for (var i = 0, n = db_password_charset.length; i < length; ++i) {
                    password += db_password_charset.charAt(Math.floor(Math.random() * n));
                }
                return password;
            },
            getSqlUser ()
            {
                fetch(`/internal-api/v1/studio/projects/${this.$root.project_id}/sql-user`)
                    .then(response => response.json())
                    .then(json => {
                        this.sql_user = json.data.user;
                        this.connectivity_info = json.data.connectivity_info;
                        this.sql_user_enabled = this.sql_user.username != null;
                        this.server_has_certificate = json.data.server_has_certificate;
                        this.$root.loading(false);
                    })
                    .catch((error) => {
                        this.$root.loading(false);
                        ResponseHelper.handleErrorMessage(error, "Current SQL user could not be retrieved");
                    });
            }
        },
        mounted () {
            this.connectivity_info = this.connectivity_info_template;
            this.$root.pageLoad();
            this.getSettings();
            this.getDestinationSchemas();
            this.getSqlUser();
        },
        updated () {
            this.$nextTick(function () {
                $('.tooltip-pretty').tooltipster({
                    contentAsHTML: true
                });
            })
        }
    });
</script>
