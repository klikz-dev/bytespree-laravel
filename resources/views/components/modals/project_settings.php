<script type="text/x-template" id="project-settings-template">
    <!-- Project Settings Modal -->
    <div class="dmiux_popup" id="modal-project-settings" ref="proj_settings_modal" role="dialog" tabindex="-1">
        <div class="dmiux_popup__window dmiux_popup__window_lg" role="document">
            <div class="dmiux_popup__head">
                <h4 class="dmiux_popup__title">Database Settings For {{ selectedDatabase.database }}</h4>
                <button type="button" class="dmiux_popup__close"></button>
            </div>
            <div class="dmiux_popup__cont dmiux_popup_cont_nav">
                <div class="dmiux_popup__tabs">
                    <a v-if="selectedDatabase.integration_id != 0" :class="(selectedDatabase.integration_id != 0) ? 'active' : ''" data-tab-open="#global-settings" href="#" id="general-tab-heading">General</a>
                    <a v-if="selectedDatabase.integration_id != 0" id="tab-connector" data-tab-open="#integration-settings" @click="checkOptions('settings')" href="#">Connector</a>
                    <a data-tab-open="#table-settings" v-show="selectedDatabase.use_tables" href="#">Tables</a>
                    <a data-tab-open="#table-integration-settings" @click="checkOptions('table-settings')" v-show="selectedDatabase.use_tables && tableSettingsCount > 0" href="#">Table Settings</a>
                    <a v-if="selectedDatabase.integration_id != 0" data-tab-open="#callbacks" href="#">Callbacks</a>
                    <a v-if="selectedDatabase.integration_id != 0" data-tab-open="#schedule-settings" href="#">Schedule</a>
                    <a v-if="selectedDatabase.integration_id != 0" data-tab-open="#hooks" v-show="selectedDatabase.use_hooks && $parent.checkPerms('manage_settings', selectedDatabase.id)" href="#">Hooks</a>
                    <a :class="(selectedDatabase.integration_id == 0) ? 'active' : ''" data-tab-open="#sql-access" v-show="$parent.checkPerms('grant_sql_access', selectedDatabase.id)" href="#">SQL Access</a>
                    <a data-tab-open="#danger-zone" v-show="$parent.checkPerms('delete', selectedDatabase.id)" href="#">Advanced</a>
                </div>
                <div class="dmiux_popup__overflow" v-show="$parent.checkPerms('manage_settings', selectedDatabase.id)">
                    <div class="dmiux_popup__tab" :class="(selectedDatabase.integration_id != 0) ? 'visible' : ''" id="global-settings">
                        <p class="mt-3 alert alert-info">Notificants receive email notifications with details about database health status each time the database in synchronized with the source.</p>
                        <div class="dmiux_input">
                            <label for="input-notifications">Notificants</label>	
                             <textarea type="text" class="dmiux_input__input" v-model="selectedDatabase.notificants" maxlength="500" id="input-notifications"></textarea>
                        </div>
                    </div>
                    <div class="dmiux_popup__tab" id="integration-settings" v-if="selectedDatabase.integration_id != 0">
                        <p class="mt-3 alert alert-info">Changes to connector settings will take effect the next time the database is synchronized.</p>
                        <div v-if="selectedDatabase.settings && selectedDatabase.settings.length === 0">
                            <br>
                            No settings defined for integration.
                        </div>
                        <connector-settings :settings="selectedDatabase.settings" :table="''" :table_index="'-1'"></connector-settings>
                        <div v-if="selectedDatabase.is_oauth">
                            <button class="dmiux_button" type="button" @click="makeOAuthCall(selectedDatabase.oauth_url)">
                                Authorize {{ selectedDatabase.integration_name }}
                            </button>
                        </div>
                        <span v-if="selectedDatabase.instructions != '' && selectedDatabase.instructions != undefined" v-html="selectedDatabase.instructions"></span>
                    </div>
                    <div class="dmiux_popup__tab" id="callbacks">
                        <p class="mt-3 alert alert-info">When you set up a callback, Bytespree will make an HTTP Post request against a third-party URL each time this database is synced with the source. For databases with multiple tables, a callback request will be executed for each table.</p>
                        <div v-if="$parent.checkPerms('manage_settings', selectedDatabase.id) === true" class="float-right mb-4">
                            <button @click="editingStart('new', false)" class="dmiux_button"><i class="fa fa-plus"></i> Add Callback</button>
                        </div>
                        <table class="dmiux_data-table__table">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>URL</th>
                                    <th>Key</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(callback, index) in callbacks">
                                    <!--  Column 1 with edit and delete icons  -->
                                    <td class="align-middle" style="width: 20px !important;">
                                        <div class="dmiux_data-table__actions" v-if="index === callbackToUpsert">
                                            <div v-if="$parent.checkPerms('manage_settings', selectedDatabase.id) === true" class="dmiux_actionswrap dmiux_actionswrap--cancel" @click="editingCancel()" title="Cancel"></div>
                                            <div v-if="$parent.checkPerms('manage_settings', selectedDatabase.id) === true" class="dmiux_actionswrap dmiux_actionswrap--save" @click="editingSave()" title="Save"></div>
                                        </div>
                                        <div class="dmiux_data-table__actions" v-else>
                                            <div v-if="$parent.checkPerms('manage_settings', selectedDatabase.id) === true" class="dmiux_actionswrap dmiux_actionswrap--edit" @click="editingStart('edit', index)" title="Update"></div>
                                            <div v-if="$parent.checkPerms('manage_settings', selectedDatabase.id) === true" class="dmiux_actionswrap dmiux_actionswrap--bin" @click="deleteCallback(callback.id)" title="Delete"></div>
                                        </div>
                                    </td>
                                    <!--  Column 2 with URL  -->
                                    <td v-if="index === callbackToUpsert">
                                        <input class="form-control form-control-sm" v-model:value="callback.callback_url" id="url-edit" placeholder="Callback URL" autocomplete="off" /> 
                                    </td>
                                    <td v-else>
                                        <a :href="callback.callback_url" target="_blank" class="ml-2">{{ callback.callback_url }}</a>
                                    </td>
                                    <!-- Column 3 with Key and Refresh icon -->
                                    <td v-if="index === callbackToUpsert">
                                        <span class="ml-2">
                                            {{ callback.key }}
                                        </span> 
                                    </td>
                                    <td v-else>
                                        <span class="ml-2">
                                            <span class="float-right pl-2" data-toggle="tooltip" title="Refresh Key" @click="editingStart('redo', index)">
                                                <i v-if="$parent.checkPerms('manage_settings', selectedDatabase.id) === true" class="fas fa-redo callback-style"></i>
                                            </span>
                                            {{ callback.key }}
                                        </span> 
                                    </td>
                                </tr>
                            </tbody> 
                        </table>           
                    </div>
                    <div class="dmiux_popup__tab" id="table-settings">
                        <div class="mt-3 alert alert-info">
                            All times are in {{ $root.system_timezone }}. Current {{ $root.system_timezone }} Date/Time is: {{ $root.datetime }}	                        
                        </div>
                        <div class="dmiux_data-table">
                            <div class="dmiux_data-table__cont">                      
                                <table class="dmiux_data-table__table">
                                    <thead>                                                                           
                                        <tr>
                                            <th></th>
                                            <th>Table</th>
                                            <th v-if="! selectedDatabase.fully_replace_tables || selectedDatabase.fully_replace_tables == false || selectedDatabase.fully_replace_tables == null">Last Started</th>
                                        </tr>                                                                        
                                    </thead>
                                    <!-- If there are available tables found, show a drop-down instead of a text box -->
                                    <tbody v-if="has_tables">
                                        <tr v-for="(table,index) in selectedDatabase.tables" v-if="table.deleted != true">
                                            <td>
                                                <div class="dmiux_actionswrap dmiux_actionswrap--bin" @click="removeThisTable(table, index)" data-toggle="tooltip" title="Remove table"></div>
                                            </td>
                                            <td v-if="table.added && table.editing">
                                                <div class="dmiux_select">
                                                    <select v-model="selected_tbl.table_name"
                                                            @change="addThisTable(selected_tbl.table_name, index)"
                                                            class="dmiux_select__select dmiux_input__input">
                                                        <option value="" selected disabled>Choose a table</option>
                                                        <option v-for="(tbl, idx) in available_tables" v-show="tbl.used == false" :value="tbl.table_name">{{ tbl.table_name }}</option>
                                                    </select>
                                                    <div class="dmiux_select__arrow"></div>
                                                </div>
                                            </td>
                                            <td v-else class="dmiux_fw600 w-100">
                                                <input type="text" disabled class="dmiux_input dmiux_input__input" v-model="table.name" />
                                            </td>
                                            <td v-if="! selectedDatabase.fully_replace_tables || selectedDatabase.fully_replace_tables == false || selectedDatabase.fully_replace_tables == null">
                                                <input type="date" @change="tableChanged(table)" class="dmiux_input dmiux_input__input auto-width" v-model="table.date_last_started" />
                                                <input type="time" step="60" @change="tableChanged(table)" class="dmiux_input dmiux_input__input auto-width" v-model="table.time_last_started" />
                                            </td>
                                        </tr>
                                    </tbody>
                                    <!-- Else just show a text box for the user to type in the name of a table -->
                                    <tbody v-else>
                                        <tr v-for="(table,index) in selectedDatabase.tables" v-if="table.deleted == false">
                                            <td>
                                                <div class="dmiux_actionswrap dmiux_actionswrap--bin" @click="deleteTable(table, index)" data-toggle="tooltip" title="Delete table"></div>
                                            </td>
                                            <td class="dmiux_fw600 w-100">
                                                <input type="text" @change="tableChanged(table, index)" class="dmiux_input dmiux_input__input" v-model="table.name" />
                                            </td>
                                            <td v-if="selectedDatabase.fully_replace_tables || selectedDatabase.fully_replace_tables == false || selectedDatabase.fully_replace_tables == null">
                                                <input type="date" @change="tableChanged(table)" class="dmiux_input dmiux_input__input auto-width" v-model="table.date_last_started" />
                                                <input type="time" step="60" @change="tableChanged(table)" class="dmiux_input dmiux_input__input auto-width" v-model="table.time_last_started" />
                                            </td> 
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <button @click="addTable()" class="dmiux_button mt-3"><i class="fa fa-plus"></i> Add Table</button>  
                    </div>
                    <div class="dmiux_popup__tab table-settings-cards" id="table-integration-settings">
                        <div class="dmiux_cards">
                            <div v-for="(table,index) in selectedDatabase.tables" v-if="table.name != '' && table.name != null" 
                                class="dmiux_cards__row dmiux_grid-row">
                                <div class="dmiux_cards__col dmiux_grid-col">
                                    <div class="dmiux_cards__item">
                                        <div class="dmiux_cards__heading">{{ table.name }}</div>
                                        <connector-settings :settings="table.settings" :table="table.name" :table_index="index"></connector-settings>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="dmiux_popup__tab" id="schedule-settings" v-if="selectedDatabase.integration_id != 0">
                        <div class="mt-3 alert alert-info">
                            All times are in {{ $root.system_timezone }}. Current {{ $root.system_timezone }} Date/Time is: {{ $root.datetime }}	
                        </div>	
                        <div v-if="selectedDatabase.use_tables" class="dmiux_grid-row">
                            <div class="dmiux_grid-col dmiux_grid-col_auto">
                                <div class="dmiux_radio dmiux_mb100">
                                    <input type="radio" name="table_schedule_type" value="one" v-model="table_schedule_type" class="dmiux_radio__input">
                                    <div class="dmiux_radio__check"></div>
                                    <div class="dmiux_radio__label">One schedule for all tables</div>
                                </div>
                            </div>
                            
                            <div class="dmiux_grid-col dmiux_grid-col_auto">
                                <div class="dmiux_radio dmiux_mb100">
                                    <input type="radio" name="table_schedule_type" value="multi" v-model="table_schedule_type" class="dmiux_radio__input">
                                    <div class="dmiux_radio__check"></div>
                                    <div class="dmiux_radio__label">Schedule per table</div>
                                </div>
                            </div>
                        </div>
                        <template v-if="! selectedDatabase.use_tables || table_schedule_type == 'one'">
                            <div class="tab-form dmiux_select">
                                <label for="single-select_frequency">Frequency</label>
                                <select v-model:value="schedule_type_id" @change="changeFrequency()" class="dmiux_select__select mb-4" id="single-select_frequency">
                                    <option disabled value="0">Choose a Frequency</option>
                                    <option v-for="type in types" :value="type.id">{{ type.name }}</option>
                                </select>
                                <div class="dmiux_select__arrow"></div>
                            </div>
                            <div v-if="properties.length != 0">
                                <div class="tab-form dmiux_select" v-for="(property,index) in properties">
                                    <label :for="property.name">{{ property.name }}</label>
                                    <select v-model:value="property.value" :id="property.name" class="dmiux_select__select mb-4" @change="changeAllTableScheduleProperties()">
                                        <option value="none" disabled>Choose an Option</option>
                                        <option v-for="option in property.options" :value="option.value">{{ option.label }}</option>
                                    </select>
                                    <div class="dmiux_select__arrow"></div>
                                </div>
                            </div>
                        </template>
                        <template v-else>
                            <div class="table-settings-cards">
                                <div class="dmiux_cards">
                                    <div v-for="(table,index) in selectedDatabase.tables" v-if="table.name != '' && table.name != null" 
                                        class="dmiux_cards__row dmiux_grid-row">
                                        <div class="dmiux_cards__col dmiux_grid-col">
                                            <div class="dmiux_cards__item">
                                                <div class="dmiux_cards__heading">{{ table.name }}</div>
                                                <label :for="'select-' + table.name + '_frequency'">Frequency</label>
                                                <div class="tab-form dmiux_select mb-2">
                                                    <select v-model:value="table.schedule.schedule_type_id" @change="changeTableFrequency(table.schedule)" class="dmiux_select__select" :id="'select-' + table.name + '_frequency'">
                                                        <option disabled value="0">Choose a Frequency</option>
                                                        <option v-for="type in types" :value="type.id">{{ type.name }}</option>
                                                    </select>
                                                    <div class="dmiux_select__arrow"></div>
                                                </div>
                                                <div v-if="table.schedule.properties != undefined && table.schedule.properties.length > 0">
                                                    <template  v-for="(property,index) in table.schedule.properties">
                                                        <label :for="'select-property_' + index + '_' + table.name">{{ property.name }}</label>
                                                        <div class="tab-form dmiux_select mb-2">        
                                                            <select v-model:value="property.value" class="dmiux_select__select" :id="'select-property_' + index + '_' + table.name" @change="changeTableScheduleProperty(table.schedule)">
                                                                <option value="none" disabled>Choose an Option</option>
                                                                <option v-for="option in property.options" :value="option.value">{{ option.label }}</option>
                                                            </select>
                                                            <div class="dmiux_select__arrow"></div>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div class="dmiux_popup__tab" id="hooks" v-if="selectedDatabase.integration_id != 0">
                        <p class="mt-3 alert alert-info">Hooks can be used to create, update, and delete records in the database using webhooks from third-party systems.</p>
                        <table class="dmiux_data-table__table">
                            <thead>
                                <tr>
                                    <th>Action</th>
                                    <th>URL</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Create</td>
                                    <td><?php echo getenv("ENVIRONMENT_URL"); ?>/Inbound/create/{{ selectedDatabase.hook_key }}</td>
                                </tr>
                                <tr>
                                    <td>Update</td>
                                    <td><?php echo getenv("ENVIRONMENT_URL"); ?>/Inbound/update/{{ selectedDatabase.hook_key }}</td>
                                </tr>
                                <tr>
                                    <td>Delete</td>
                                    <td><?php echo getenv("ENVIRONMENT_URL"); ?>/Inbound/delete/{{ selectedDatabase.hook_key }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="dmiux_popup__tab" :class="(selectedDatabase.integration_id == 0) ? 'visible' : ''" id="sql-access">
                        <p class="mt-3 alert alert-info">You cannot use SQL to modify tables or data controlled by the integration, but you can create views and run select queries. If you're planning to connect a tool like Tableau to your database, enable SQL access below.</p>
                        <div class="dmiux_checkbox mb-3">
                            <input @change="manageSqlUser()" :checked="selectedDatabase.isReadOnly" type="checkbox" class="dmiux_checkbox__input">
                            <div class="dmiux_checkbox__check"></div>
                            <div class="dmiux_checkbox__label">Enable SQL Access</div>
                        </div>
                        <template v-if="selectedDatabase.isReadOnly">
                            <table class="dmiux_data-table__table">
                                <thead>
                                    <tr>
                                        <th colspan='3'>PostgreSQL Connection Credentials</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td></td>
                                        <td><strong>Username</strong></td>
                                        <td>{{ selectedDatabase.user.username }}</td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <span title="Update Password" @click="updatePassword();" class="fas fa-sync-alt cursor-p link-hover"></span>
                                        </td>
                                        <td><strong>Password</strong></td>
                                        <td>{{ selectedDatabase.user.password }}</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td><strong>Hostname</strong></td>
                                        <td>{{ selectedDatabase.server.hostname }}</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td><strong>Port</strong></td>
                                        <td>{{ selectedDatabase.server.port }}</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td><strong>Database</strong></td>
                                        <td>{{ selectedDatabase.database }}</td>
                                    </tr>
                                    <tr v-if="selectedDatabase.server.server_provider_configuration_id != null">
                                        <td></td>
                                        <td><strong>CA Certificate</strong></td>
                                        <td><a target="_blank" :href="'/internal-api/v1/data-lakes/' + selectedDatabase.id + '/sql-user/certificate'">Download CA Certificate</a></td>
                                    </tr>
                                   
                                </tbody>
                            </table>
                        </template>
                    </div>
                    <div v-show="$parent.checkPerms('delete', selectedDatabase.id)" class="dmiux_popup__tab" id="danger-zone">
                        <label for="input-retry_syncs" class="dmiux_popup__label">Automatic Retries for Failed Syncs</label>
                        <div class="dmiux_select">
                            <select id="input-retry_syncs" class="dmiux_select__select" v-model:value="selectedDatabase.retry_syncs">
                                <option value="false">Disabled</option>
                                <option value="true">Enabled</option>
                            </select>
                            <div class="dmiux_select__arrow"></div>
                        </div>
                        <template v-if="$root.currentUser.is_admin == true">
                            <label for="input-server_threshold" class="dmiux_popup__label">Server Usage Threshold for Notifications</label>
                            <div class="dmiux_input">
                                <input class="dmiux_input__input" @change="cleanupServerThreshold()" v-model="selectedDatabase.server.alert_threshold" id="input-server_threshold" type="number" step="0.1" />
                            </div>
                            <small class="text-muted pt-1 d-block">Usage threshold cannot go above 100 and is limited to 2 decimal places</small>
                        </template>
                        <template v-if="isUpgradeAvailable(selectedDatabase.tap_version, selectedDatabase.version)">
                            <div class="mt-3 alert alert-info">
                                <h5 class="mt-2">An Upgrade is Available</h5>
                                <hr />
                                <h5>Version</h5>
                                <div class="mb-2">{{ selectedDatabase.tap_version }} -> {{ selectedDatabase.version }}</div>
                                <h5>Release Notes</h5>
                                <div>{{ selectedDatabase.release_notes }}</div>
                            </div>
                            <button class="dmiux_button" type="button" @click="upgradeConnector()">Upgrade to Latest Version</button>
                            <hr />
                        </template>
                        <div v-if="dependencies.projects.length > 0 || dependencies.foreign_projects.length > 0 || dependencies.warehouse_foreign_databases.length > 0" class="mt-3 alert alert-danger">
                            <h5 class="mt-2">The impacts of removing this database are listed below</h5>
                            <div class="ml-3">
                                <p v-if="dependencies.projects.length != 0"><b>The following <span v-if="dependencies.projects.length == 1">project</span><span v-else>projects</span> will be deleted:</b></p>
                                <ul>
                                    <li class="general_overflow" v-for="project in dependencies.projects">{{ project.display_name }}</li>
                                </ul>
                                <p v-if="dependencies.foreign_projects.length != 0"><b>The database will be removed from the following <span v-if="dependencies.foreign_projects.length == 1">project</span><span v-else>projects</span>:</b></p>
                                <ul>
                                    <li class="general_overflow" v-for="project in dependencies.foreign_projects">{{ project.project.display_name }}</li>
                                </ul>
                                <p v-if="dependencies.warehouse_foreign_databases.length != 0"><b>This database's foreign relationship will be removed from the following <span v-if="dependencies.warehouse_foreign_databases.length == 1">database</span><span v-else>databases</span>:</b></p>
                                <ul>
                                    <li class="general_overflow" v-for="foreign_database in dependencies.warehouse_foreign_databases">{{ foreign_database.database.database }}</li>
                                </ul>
                            </div>
                        </div>
                        <div v-else class="mt-3 alert alert-info">
                            Removing this database will not impact Studio projects or Warehouse foreign databases
                        </div>
                        <button class="dmiux_button dmiux_button_danger" type="button" @click="deleteIntegration()">Delete This Database</button>
                        <div v-if="selectedDatabase.integration_id != '0'">
                            <hr />
                            <div class="alert alert-info mt-3">
                                <p>Converting this database to a basic database will:</p>
                                <ul>
                                    <li>remove scheduled syncing from its datasource</li>
                                    <li>remove the functionality of any callbacks triggered by syncing</li>
                                    <li><u>not</u> impact projects or Warehouse foreign databases</li>
                                </ul>
                            </div>
                            <button class="dmiux_button dmiux_button_danger" type="button" @click="convertIntegrationToBasic()">
                                Convert This Database To A Basic Database
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="dmiux_popup__foot">
                <div class="dmiux_grid-row">
                    <div class="dmiux_grid-col"></div>
                    <div class="dmiux_grid-col dmiux_grid-col_auto">
                        <button class="dmiux_button dmiux_button_secondary dmiux_popup__close_popup" type="button">Cancel</button>
                    </div>
                    <div class="dmiux_grid-col dmiux_grid-col_auto">
                        <button class="dmiux_button" type="button" @click="saveChanges()">Save</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>
