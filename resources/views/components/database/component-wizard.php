<script type="text/x-template" id="component-wizard">
    <div>

        <!--  Beginning of Information Row  -->
        <form v-if="selected_tab != 'database-integration'" class="dmiux_grid-row dmiux_grid-row_aic pb-2" id="form-database_integration_base_settings" autocomplete="off" onsubmit="event.preventDefault()">
            <!-- Display Chosen Integration -->
            <div class="dmiux_grid-col dmiux_grid-col_xs-12 dmiux_grid-col_sm-4 dmiux_grid-col_md-3 pb-2">
                <label class="dmiux_query-parameters__title" for="input-connectors_dropdown">Chosen Connector</label>
                <div class="dmiux_select">
                    <select v-model="$root.selected_integration.id"
                            @change="chooseIntegration($root.selected_integration)"
                            class="dmiux_select__select dmiux_input__input_lg"
                            id="input-connectors_dropdown">
                        <option selected disabled>Choose a connector</option>
                        <option v-for="integration in integrations" :value="integration.id">{{ integration.name }}</option>
                    </select>
                    <div class="dmiux_select__arrow"></div>
                </div>
            </div>

            <!--  Input for Database Name  -->
            <div class="dmiux_grid-col dmiux_grid-col_xs-12 dmiux_grid-col_sm-4 dmiux_grid-col_md-3 pb-2">
                <div class="dmiux_input">
                    <label class="dmiux_query-parameters__title" for="database">Database Name</label>
                    <input type="text"
                            class="dmiux_input__input dmiux_input__input_lg"
                            name="database"
                            id="database"
                            maxlength="59"
                            @input="cleanupDatabaseName()"
                            v-model="integration_details.database"
                            placeholder="Name of database"
                            pattern="[0-9a-z$_]+"
                            required />
                </div>
            </div>

            <!--  Select for Server  -->
            <div class="dmiux_grid-col dmiux_grid-col_xs-12 dmiux_grid-col_sm-4 dmiux_grid-col_md-3 pb-2">
                <label class="dmiux_query-parameters__title" for="server">Server</label>
                <div class="dmiux_select">
                    <select v-model="integration_details.server_id" class="dmiux_select__select dmiux_input__input_lg" id="server" required>
                        <option value="" disabled>Choose a Server</option>
                        <option v-for="server in servers" v-if="server.status == '' || server.status == 'online'" :value="server.id">{{ server.name }}</option>
                    </select>
                    <div class="dmiux_select__arrow"></div>
                </div>
            </div>

            <!--  Button to Create Database  -->
            <div class="dmiux_grid-col mt-4 pb-2">
                <button class="dmiux_button dmiux_button_lg float-sm-right w-100" 
                    type="button" 
                    @click="$root.selected_integration.name == 'Basic' ? addIntegration() : checkSchedule()"
                    :class="databaseComplete == false ? 'hidden' : ''"> Create Database 
                </button>
            </div>
        </form>
        <!--  End of Information Row  -->

        <div>
            <!--  Beginning of Select Integration  -->
            <form v-show="selected_tab == 'database-integration'" id="database-integration">
                <div class="dmiux_grid-row">
                    <div v-for="(integration, index) in integrations" 
                            :key="integration.id" 
                            class="text-center dmiux_grid-col_xs-12 dmiux_grid-col_sm-6 dmiux_grid-col_md-4 dmiux_grid-col_3"
                            @hover="showShadow()">

                        <div class="card dmiux_card-with-hdr__container mx-2" @click="chooseIntegration(integration);">
                            <div class="dmiux_db-card--header">{{ integration.name }}</div>
                            <div class="card-body vertical-center database-card-image-container">
                                <img draggable="false"
                                        class="img-fluid"
                                        :src="`/connectors/` + integration.id + `/logo?v3.0.1`" />
                                <input name="$root.selected_integration" 
                                        type="radio" 
                                        class="dmiux_query-summary__group"
                                        v-model="is_selected">
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            <!--  End of Select Integration  -->

            <!--  Beginning of Enter Settings  -->
            <form v-show="selected_tab == 'database-settings' && (integration_details.settings.length > 0 || $root.selected_integration.use_tables == true)" id="database-settings">

                <!--  Beginning of Settings Tabs  --> 
                <div class="dmiux_query-tabs border-0 dmiux_noscrollbar mt-1">
                    <button type="button"
                        v-show="has_instructions == true || has_known_limitations == true"
                        @click="settings_tab = 'instructions'"
                        class="dmiux_query-tabs__item"
                        :class="settings_tab == 'instructions' ? 'dmiux_active' : ''">
                        Instructions
                    </button>
                    <button type="button"
                        v-show="integration_details.settings.length !== 0"
                        @click="changeSettingsTab('integration-settings')"
                        class="dmiux_query-tabs__item"
                        :class="settings_tab == 'integration-settings' ? 'dmiux_active' : ''">
                        Connector Settings
                    </button>
                    <button type="button"
                        @mouseover="settingsReady()"
                        v-show="integration_details.use_tables === true"
                        @click="showTables(integration_details.id, integration_details.settings)"
                        :title="settings_ready ? '' : 'Please enter all the settings before entering tables.'"
                        :class="[settings_tab == 'tables' ? 'dmiux_active' : '', settings_ready == true ? 'dmiux_query-tabs__item' : 'dmiux_query-tabs__item_disabled']">
                        Tables
                    </button>
                    <button type="button"
                        v-show="integration_details.use_tables === true && tableSettingsCount > 0 && integration_details.tables.length > 0"
                        @click="settings_tab = 'table-settings'"
                        class="dmiux_query-tabs__item"
                        :class="settings_tab == 'table-settings' ? 'dmiux_active' : ''">
                        Table Settings
                    </button>
                </div>
                <!--  End of Settings Tabs  --> 
                
                <div>
                    <!--  Beginning of Instructions  -->
                    <div v-if="settings_tab == 'instructions'">
                        <div id="dmiux_query-tab_instruct" class="dmiux_block">
                            <div v-if="has_known_limitations == true">
                                <h2 class="mt-2">Known Limitations</h2>
                                <ul>
                                    <li v-for="limitation in integration_details.known_limitations">{{ limitation }}</li>
                                </ul>
                            </div>
                            <div v-if="has_known_limitations == true" class="dmiux_checkbox mb-4">
                                <input v-model="agree_with_kl" type="checkbox" class="dmiux_checkbox__input">
                                <div class="dmiux_checkbox__check"></div>
                                <div class="dmiux_checkbox__label">I understand the known limitations and wish to proceed</div>
                            </div>              
                            <div v-if="has_instructions">
                                <h2 class="mt-2">Getting Started</h2>
                                <p class="mt-2" v-html="integration_details.instructions"></p>
                            </div>
                        </div>
                    </div>
                    <!--  End of Instructions  -->

                    <!--  Beginning of Connector Settings  -->
                    <div v-if="settings_tab == 'integration-settings'">
                        <div id="dmiux_query-tab_integ" class="dmiux_block">
                            <connector-settings :settings.sync="integration_details.settings" :table="''" :table_index="'-1'"></connector-settings>
                            <div v-if="integration_details.is_oauth" class="pt-2">
                                <button :disabled="$root.oauth_complete" class="dmiux_button" type="button" @click="makeOAuthCall(integration_details.oauth_url)">
                                    Authorize {{ integration_details.name }}
                                </button>
                            </div>
                            <!-- Custom component for connector settings, if necessary -->
                            <component
                                :is="settingsComponent"
                                :oauth_complete="$root.oauth_complete"
                                @settingChanged="settingChangedFromChildComponent"
                                :data_store="integration_data_store"
                                @store="storeIntegrationData">
                            </component>
                            <!-- End of Custom component -->
                        </div>
                    </div>
                    <!--  End of Connector Settings  -->
                    
                    <!--  Beginning of Add Tables  --> 
                    <div v-else-if="settings_tab == 'tables'">
                        <div id="dmiux_query-tab_table" class="dmiux_block">
                            <div v-if="has_tables == true"> 
                                <div class="dmiux_grid-row">
                                    <div class="dmiux_grid-col dmiux_grid-col_lg-12">
                                        <div class="dmiux_query-flags dmiux_query-flags_all">
                                            <button type="button" class="dmiux_query-flags__include-all dmiux_clear-all" @click="addAllTables()">Include All<i class="dmiux_clear-all__icon dmiux_clear-all__icon_plus"></i></button>
                                            <div class="dmiux_query-flags__title">Select Tables</div>
                                            <div class="dmiux_query-flags__overflow dmiux_scrollbar" style="height: 40vh; background-color: #fff;">
                                                <div v-for="(tbl1, idx1) in connector_tables">
                                                    <div v-if="tbl1.used == false" id="idx1" class="dmiux_flagbox">
                                                        <button type="button" @click="addThisTable(idx1)" data-tooltip-pos="bottom" data-tooltip-content="<div>Include</div>" class="dmiux_flagbox__icon dmiux_flagbox__icon_plus"><i></i></button>
                                                        <div class="dmiux_flagbox__label">{{ tbl1.table_name }}</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="dmiux_grid-col dmiux_grid-col_xs-12">
                                        <div class="dmiux_query-flags dmiux_query-flags_included">
                                            <button type="button"
                                                    @click="removeAllTables()"
                                                    class="dmiux_query-flags__remove-all dmiux_clear-all"
                                                    :class="integration_details.tables.length === 0 ? 'dmiux_removed' : ''">Remove All<i class="dmiux_clear-all__icon dmiux_clear-all__icon_remove"></i></button>
                                            <div class="dmiux_query-flags__title">Included Tables</div>
                                            <div class="dmiux_scrollbar">
                                                <div v-for="(tbl2, idx2) in integration_details.tables">
                                                    <div id="idx2" class="dmiux_flagbox dmiux_flagbox_included dmiux_included_container dmiux_flagbox_mobile database-table_height">
                                                        <button type="button" @click="removeThisTable(idx2)" data-tooltip-pos="bottom" data-tooltip-content="<div>Remove</div>" class="dmiux_flagbox__icon dmiux_flagbox__icon_remove mt-1"><i></i></button>
                                                        <div class="dmiux_flagbox__label mt-1">
                                                            <span v-if="tbl2.name.length < 10">{{ tbl2.name }}</span><span v-else>{{ tbl2.name.substring(0,10)+"..." }}</span>
                                                        </div>
                                                        <div v-if="integration_details.fully_replace_tables != true" class="dmiux_date_checkbox">
                                                            <input type="date" v-model="integration_details.tables[idx2].date" class="dmiux_input dmiux_input__input dmiux_input__input_checkbox auto-width" :class="integration_details.tables[idx2].checkbox !== true ? 'dmiux_invisible' : ''" :max="sync_max_date"/>
                                                            <div class="dmiux_checkbox dmiux_checkbox_wrapper">
                                                                <input type="checkbox" class="dmiux_checkbox__input" v-model="integration_details.tables[idx2].checkbox">
                                                                <div class="dmiux_checkbox__check">
                                                                    <div class="dmiux_checkbox_tooltip">Only synchronize records created or modified after a certain date</div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else>
                                <template v-for="(table,index) in integration_details.tables">
                                    <div v-if="table.deleted === false" class="form-inline mt-2 mb-1">
                                        <input @change="updateTable(index)" class="dmiux_input dmiux_input__input auto-width table-class" type="text" v-model="table.name" />
                                        <button @click="deleteTable(table, index)" type="button" class="dmiux_button red-background ml-2">Delete</button>
                                    </div>
                                </template>
                                <button @click="addTable()" type="button" class="dmiux_button mt-3"><i class="fa fa-plus"></i> Add Table</button>
                                <div class="dmiux_grid-col dmiux_grid-col_auto dmiux_grid-col_sm-12 ml-4 mt-4 pt-3">
                                    <div v-if="integration_details.tables && integration_details.tables.length === 0" class="dmiux_query-parameters__title italicized text-danger"> * You must add at least one table</div>
                                </div>
                            </div>
                            <component
                                :is="tablesComponent">
                            </component>
                        </div>
                    </div>
                    <!--  End of Add Tables  -->
                    
                    <!--  Beginning of Table Settings  -->
                    <div v-else-if="settings_tab == 'table-settings'">
                        <div id="dmiux_query-tab_table_settings">
                            <template v-for="(table,index) in integration_details.tables">
                                <div class="dmiux_block pt-4 px-4">
                                    <h4 class="text-muted">{{ table.name }}</h4>
                                    <hr />
                                    <connector-settings :settings.sync="table.settings" :table="table.name" :table_index="index"></connector-settings>
                                </div>
                            </template>
                        </div>
                    </div>
                    <!--  End of Table Settings  -->
                </div>
            </form>
            <!--  End of Enter Settings  -->
       
            <!--  Beginning of Set Schedule  -->
            <form v-show="selected_tab == 'database-schedule' && $root.selected_integration.name != 'Basic'" id="database-schedule">
                <div class="dmiux_grid-row my-3 alert alert-info">
                    All times are in {{system_timezone}}. Current {{ system_timezone }} Date/Time is: {{ $root.datetime }}
                </div>
                <div v-if="integration_details.use_tables == true" class="dmiux_grid-row">
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
                <template v-if="integration_details.use_tables == false || table_schedule_type == 'one'">
                    <div class="tab-form dmiux_select text-left">
                        <label for="select-frequency">Frequency</label>
                        <select id="select-frequency" v-model:value="schedule_type_id" @change="changeFrequency()" class="dmiux_select__select mb-4">
                            <option disabled value="0">Choose a Frequency</option>
                            <option v-for="type in schedule_types" :value="type.id">{{ type.name }}</option>
                        </select>
                        <div class="dmiux_select__arrow"></div>
                    </div>
                    <div v-if="properties.length != 0" class="tab-form">
                        <span v-for="(property,index) in properties">
                            <div class="dmiux_select">
                                <label :for="'select-property_' + index">{{ property.name }}</label>
                                <select v-model:value="property.value" class="dmiux_select__select mb-4" @change="changeAllTableScheduleProperties()" :id="'select-property_' + index">
                                    <option :value="undefined" disabled>Choose an Option</option>
                                    <option v-for="option in property.options" :value="option.value">{{ option.label }}</option>
                                </select>
                                <div class="dmiux_select__arrow mt-0"></div>
                            </div>
                        </span>
                    </div>
                </template>
                <template v-else>
                    <div class="mb-4 text-left" v-for="(table,index) in integration_details.tables">
                        <h4 class="text-muted">{{ table.name }}</h4>
                        <hr />
                        <div class="tab-form dmiux_select">
                            <label :for="'select-' + table.name + '_frequency'">Frequency</label>
                            <select v-model:value="table.schedule.schedule_type_id" @change="changeTableFrequency(table.schedule)" class="dmiux_select__select mb-4" :id="'select-' + table.name + '_frequency'">
                                <option disabled value="0">Choose a Frequency</option>
                                <option v-for="type in schedule_types" :value="type.id">{{ type.name }}</option>
                            </select>
                            <div class="dmiux_select__arrow"></div>
                        </div>
                        <div v-if="table.schedule.properties.length > 0" class="tab-form">
                            <span v-for="(property,index) in table.schedule.properties">
                                <div class="dmiux_select">
                                    <label :for="'select-property_' + index + '_' + table.name">{{ property.name }}</label>
                                    <select v-model:value="property.value" class="dmiux_select__select mb-4" :id="'select-property_' + index + '_' + table.name">
                                        <option :value="null" disabled>Choose an Option</option>
                                        <option v-for="option in property.options" :value="option.value">{{ option.label }}</option>
                                    </select>
                                    <div class="dmiux_select__arrow mt-0"></div>
                                </div>
                            </span>
                        </div>
                    </div>
                </template>
            </form>
            <!--  End of Set Schedule  -->
        </div>
    </div>
</script>
