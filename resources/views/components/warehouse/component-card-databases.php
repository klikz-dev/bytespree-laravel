<script type="text/x-template" id="component-card-databases">
    <div class="dmiux_db-card--container">
        <div class="dmiux_grid-row">
            <div v-if="databases.other_databases.length > 0" class="dmiux_grid-col_12 dmiux_db-card--section-title">
                My <span v-if="databases.user_databases.length > 1">Databases</span><span v-else>Database</span>
            </div>
            <div v-for="(database, index) in databases.user_databases" v-if="!database.hidden" class="dmiux_grid-col_3 dmiux_grid-col_lg-3 dmiux_grid-col_md-4 dmiux_grid-col_sm-6">
                <div class="dmiux_db-card--item m-2 text-left">
                    <div :class="headerClass(database.status_color)" class="dmiux_db-card--header">
                        <span :title="database.database" class="dmiux_db-card--title tooltip-pretty">{{ database.database }}</span>
                        <span v-if="database.status_color != 'blue' && database.use_tables" class="float-right tooltip-pretty" :title="headerTooltip(database.status_color, database.failed_jobs)">
                            <i class="fas fa-info-circle"></i>
                        </span>
                        <?php if (! app()->isProduction()): ?>
                        <span class="badge badge-default float-right">{{ database.id }}</span>
                        <?php endif; ?>
                    </div>
                    <div class="dmiux_db-card--body">
                        <div v-if="database.integration_id != 0 && database.integration_id != null" class="vertical-center database-card-image-container">
                            <img  class="img-fluid" draggable="false" 
                                :src="`/connectors/` + database.integration_id + `/logo?v3.0.1`">
                        </div>
                        <div v-else class="vertical-center dmiux_simple-db-style">
                            <img src="/assets/images/applications-database.png"  class="img-fluid" draggable="false"  /><br> 
                        </div>
                        <div :class="$parent.checkPerms('tag_write', database.id) && (tags != null && tags.length != 0) ? 'dmiux_grid-row' : ''">
                            <div :class="$parent.checkPerms('tag_write', database.id) && (tags != null && tags.length != 0) ? 'dmiux_grid-col dmiux_grid-col_10' : ''" class="dmiux_select">
                                <select class="dmiux_select__select dmiux_select__select_pholder" @change="get_action(database, $event)">
                                    <option selected disabled value="">Select an option</option>
                                    <option v-if="$parent.checkPerms('view_logs', database.id) === true && database.integration_id != 0" value="il">View Integration Logs</option>
                                    <option v-if="$parent.checkPerms('manage_schema', database.id) === true" value="mt">Manage Tables &amp; Views</option>
                                    <option v-if="$parent.checkPerms('manage_settings', database.id) === true" value="is">Manage Database Settings</option>
                                    <option v-if="$parent.checkPerms('run', database.id) === true && database.integration_id != 0" :disabled="! database.use_tables && database.is_running" :value="'ri' +(database.use_tables ? 't' : 'f')">Run Integration Now</option>
                                </select>
                                <div :style="$parent.checkPerms('tag_write', database.id) ? 'right: 20px' : ''" class="dmiux_select__arrow"></div>
                            </div>
                            <div v-if="$parent.checkPerms('tag_write', database.id) === true && (tags != null && tags.length != 0)" class="dmiux_grid-col dmiux_grid-col_2 pl-0 pr-0">
                                <nav class="dmiux_app-nav pl-2 pr-1 tooltip_dmi">
                                    <div data-tooltip="Manage Tags"><img @click="toggle_dropdown(database.id)" style="border-right: 0px; height: 30px; width: 40px; cursor: pointer;" class="dmiux_app-nav__toggle pl-0 pr-2 pb-0" src="<?php echo getenv('DMIUX_URL') ?>/img/icons/tag.svg"></div>
                                    <div :id="'warehouse_tags_' + database.id" class="dmiux_app-nav__dropdown_warehouse mt-1" style="right: -12px; left: initial; opacity: 0;" @mouseleave="toggle_dropdown(database.id)">  
                                        <div style="max-height: 100px; overflow-y: auto">
                                            <div class="row hide-x-scroll">
                                                <div v-for="tag in tags" class="col-md-12 mb-1 ml-2 dmiux_tag-box" :style="'background-color:' + tag.color" 
                                                @click="addDatabaseTag(tag, database, index)">
                                                    {{ tag.name }} <span v-if="inDatabase(tag.id, index)" class="fas fa-check"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </nav>
                            </div>
                        </div>
                        <div class="row ml-1 tooltip_dmi">
                            <div v-for="tag in database.tags" :data-tooltip="tag.name" class="col-md-2 col-sm-1 mt-1 pb-2 mr-1" :style="'border-radius: 4px; background-color:' + tag.color" ></div>
                        </div>
                    </div>                         
                </div>
            </div>
        </div>
        <div id="title-other-databases" v-if="databases.other_databases.length > 0" class="dmiux_grid-row">
            <div class="dmiux_grid-col_12 dmiux_db-card--section-title dmiux_mt100">
                <a href="javascript:void(0)" @click="toggleUnauthorized"><i style="width:15px" id="icon-other-databases" class="fas fa-sm" :class="show_unauthorized ? 'fa-chevron-down' : 'fa-chevron-right'"></i> 
                    Other <span v-if="databases.other_databases.length > 1">Databases ({{ databases.other_databases.length }})</span><span v-else>Database</span></a>
            </div>
        </div>
        <div id="section-other-databases" class="dmiux_grid-row dmiux_db-card--section">
            <div v-for="(database, index) in databases.other_databases" v-if="!database.hidden" class="dmiux_grid-col_3 dmiux_grid-col_lg-3 dmiux_grid-col_md-4 dmiux_grid-col_sm-6">
                <div class="dmiux_db-card--item m-2 text-left">
                    <div class="dmiux_db-card--header dmiux_db-card--header-disabled">
                        <span class="dmiux_db-card--title">{{ database.database }}</span>
                    </div>
                    <div class="dmiux_db-card--body">
                        <div v-if="database.integration_id != 0" class="vertical-center database-card-image-container">
                            <img class="img-fluid dmiux_db-card--logo-disabled" draggable="false"
                                :src="'/connectors/' + database.integration_id + '/logo?v3.0.1'">
                        </div>
                        <div v-else class="vertical-center dmiux_simple-db-style">
                            <img class="img-fluid dmiux_db-card--logo-disabled" draggable="false" 
                                src="/assets/images/applications-database.png">
                        </div>
                        <div class="dmiux_grid-row">
                            <div class="dmiux_grid-col dmiux_grid-col_12">
                                <button @click="requestAccess(database)" type="button" class="dmiux_button dmiux_button_secondary dmiux_w100">Request Access<i class="fas fa-lock dmiux_ml100"></i></button>
                            </div> 
                        </div>
                    </div>                         
                </div>
            </div>
        </div>
    </div>
</script>
