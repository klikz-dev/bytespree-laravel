<script type="text/x-template" id="component-database-manager">
    <div class="dmiux_grid-cont dmiux_grid-cont_fw">
        <div class="dmiux_block">
            <div class="dmiux_htabs dmiux_noscrollbar">
                <button @click="$parent.switchToTables()" :class="$parent.tab == 'tables' ? 'dmiux_active' : ''" type="button" class="dmiux_htabs__item">Tables</button>
                <button @click="$parent.switchToViews()" :class="$parent.tab == 'views' ? 'dmiux_active' : ''"  type="button" class="dmiux_htabs__item">Views</button>
                <button @click="$parent.switchToForeignTables()" :class="$parent.tab == 'foreign_tables' ? 'dmiux_active' : ''"  type="button" class="dmiux_htabs__item">Foreign Tables</button>
            </div>
            <div class="dmiux_grid-row">
                <div v-show="$parent.tab == 'tables'" class="dmiux_grid-col dmiux_grid-col_25 dmiux_grid-col_lg-3 dmiux_grid-col_md-12">
                    <div class="dmiux_vtabs mb-2">
                        <div class="dmiux_vtabs__head">
                            <div class="dmiux_vtabs__title">Tables</div>
                        </div>
                        <div class="dmiux_vtabs__cont dmiux_noscrollbar side-nav-container-warehouse">
                            <p v-if="$root.loadedItems.getTables == false">Loading...</p>
                            <p v-else-if="tables.length == 0">No tables are in the database</p>
                            <nav class="side-nav warehouse-side-nav">
                                <template v-for="schema in table_schemas">
                                    <span :data-tab-open="'#' + schema" class="cursor-p side-nav__link dmiux_active dmiux_vtabs__item pr-4 font-weight-bold database-manager-overflow_text" :class="active_schema_table == schema ? 'dmiux_active' : ''"  @click="setActiveSchema(schema,  'table')" >
                                      <span :title="schema" class="tooltip-pretty">{{ schema }}</span>  
                                    </span>
                                    <div class="side-nav__dropdown contents-scroll">
                                        <template v-for="(table, index) in tables" v-if="(table.table_type == 'Table' || table.table_type == 'Custom Table') && table.table_schema == schema">
                                            <router-link class="text-decoration-none" :to="{ name: 'tables', params: { tableName: table.table_name, tableSchema: table.table_schema, index: index, selectedTable: selectedTable } }">                  
                                                <button type="button"
                                                        :class="$route.params.tableName == table.table_name ? 'dmiux_active-sub' : ''"
                                                        class="side-nav__link dmiux_vtabs__item database-manager-overflow_text"
                                                        >
                                                        <span class="tooltip-pretty" :title="table.table_name">{{ table.table_name }}</span>
                                                    </button>
                                            </router-link>
                                        </template>
                                    </div>
                                </template>
                            </nav>
                        </div>
                    </div>
                </div>
                <div v-show="$parent.tab == 'views'" class="dmiux_grid-col dmiux_grid-col_25 dmiux_grid-col_lg-3 dmiux_grid-col_md-12">
                    <div class="dmiux_vtabs mb-2">
                        <div class="dmiux_vtabs__head">
                            <div class="dmiux_vtabs__title">Views</div>
                        </div>
                        <div class="dmiux_vtabs__cont dmiux_noscrollbar side-nav-container-warehouse">
                            <p v-if="$root.loadedItems.getViews == false">Loading...</p>
                            <p v-else-if="views.length == 0">No views are in the database</p>
                            <nav class="side-nav warehouse-side-nav">
                                <template v-for="schema in view_schemas">
                                    <span :data-tab-open="'#' + schema" class="cursor-p side-nav__link dmiux_vtabs__item pr-4 font-weight-bold database-manager-overflow_text " :class="active_schema_view == schema ? 'dmiux_active' : ''"  @click="setActiveSchema(schema, 'view')" >
                                        <span class="tooltip-pretty" :title="schema">
                                            {{ schema }}
                                        </span>
                                    </span>
                                    <div class="side-nav__dropdown contents-scroll">
                                        <template v-for="(view, index) in views" v-if="view.view_schema == schema">
                                            <router-link class="text-decoration-none" :to="{ name: 'views', params: { viewName: view.table_name, index: index, viewIndex: viewIndex, selectedView: selectedView } }">  
                                                <button type="button"
                                                        :class="$route.params.viewName == view.table_name ? 'dmiux_active-sub' : ''" 
                                                        class="side-nav__link dmiux_vtabs__item database-manager-overflow_text"
                                                        :data-index="viewName"
                                                        ><span class="tooltip-pretty" :title="getViewName(view)" :class="view.exists == false || view.synchronized == false ? 'database-manager-missing' : ''">{{ getViewName(view) }}</span></button>
                                            </router-link>
                                        </template>
                                    </div>
                                </template>
                            </nav>
                        </div>
                    </div>
                </div>
                <div v-show="$parent.tab == 'foreign_tables'" class="dmiux_grid-col dmiux_grid-col_25 dmiux_grid-col_lg-3 dmiux_grid-col_md-12">
                    <div class="dmiux_vtabs mb-2">
                        <div class="dmiux_vtabs__head">
                            <div class="dmiux_vtabs__title">Foreign Tables</div>
                        </div>
                        <div class="dmiux_vtabs__cont dmiux_noscrollbar side-nav-container-warehouse">
                            <p v-if="$root.loadedItems.getForeignTables == false">Loading...</p>
                            <p v-else-if="foreign_table_schemas.length == 0">No foreign tables are in the database</p>
                            <nav class="side-nav warehouse-side-nav">
                                <template v-for="schema in foreign_table_schemas">
                                    <span :data-tab-open="'#' + schema" class="cursor-p side-nav__link dmiux_vtabs__item pr-4 font-weight-bold database-manager-overflow_text" :class="active_schema_foreign == schema ? 'dmiux_active' : ''"  @click="setActiveSchema(schema, 'foreign')" >
                                        <span class="tooltip-pretty" :title="schema">
                                            {{ schema }}
                                        </span>
                                    </span>
                                    <div v-if="! tableless_foreign_schemas.includes(schema)" class="side-nav__dropdown contents-scroll">
                                        <template v-for="(table, index) in foreign_tables" v-if="(table.table_type == 'FOREIGN' || table.table_type == 'Foreign Table') && table.table_schema == schema">
                                            <router-link class="text-decoration-none" :to="{ name: 'foreign_tables', params: { tableName: table.table_name, tableSchema: table.table_schema, index: index, selectedTable: selectedTable } }">                             
                                                <button type="button"
                                                        :class="$route.params.tableName == table.table_name ? 'dmiux_active-sub' : ''"
                                                        class="side-nav__link dmiux_vtabs__item database-manager-overflow_text tooltip-pretty"
                                                        >
                                                        <span class="tooltip-pretty" :title="table.table_name">
                                                        {{ table.table_name }}
                                                        </span>
                                                </button>
                                            </router-link>
                                        </template>
                                    </div>
                                    <div v-else class="side-nav__dropdown contents-scroll ml-2">
                                        <p>No Tables Found</p>
                                    </div>
                                </template>
                            </nav>
                        </div>
                    </div>
                </div>
                <router-view></router-view>
            </div>
        </div>
    </div>
</script>

<script type="text/x-template" id="component-database-manager_tables">
    <router-view></router-view>
</script>

<script type="text/x-template" id="component-database-manager_views">
    <div v-if="selectedView != undefined && viewName == selectedView.schema + '.' + selectedView.name" class="dmiux_grid-col dmiux_grid-col_95 dmiux_grid-col_lg-9 dmiux_grid-col_md-12">
        <div class="dmiux_grid-col dmiux_grid-col_auto pr-0 pl-0">
            <div v-if="selectedView.schema != 'public'"
                class="alert alert-info">This view is managed by a Studio project and can only be modified in Studio.
            </div>
            <!--
            TODO: Finish implementing https://datamanagementinc.atlassian.net/browse/BYT-1049
            
            <div v-else-if="selectedView.exists == true && selectedView.synchronized == false" class="alert alert-warning">
                <div class="dmiux_grid-row">
                    <div class="dmiux_grid-col dmiux_grid-col_9 dmiux_grid-col_sm-12">
                        This view's current definition does not match the latest version of the definition saved in Bytespree.
                    </div>
                    <div class="dmiux_grid-col dmiux_grid-col_3 dmiux_grid-col_sm-12">
                        <button type="button"
                                class="dmiux_button dmiux_button_warning dmiux_float-right"
                                @click="rebuildView(selectedView.id)"
                                title="Rebuild">Rebuild</button>
                        <button type="button"
                                class="dmiux_button dmiux_float-right mr-1"
                                @click="dropView(false)"
                                data-toggle="tooltip"
                                title="Delete">Delete</button>
                    </div>
                </div>
            </div>
            -->
            <template v-else>
                <div class="dmiux_htabs pl-0">
                    <a v-if="selectedView.user_sql != null" @click="tab = 'user'" href="javascript:void(0)" class="dmiux_htabs__item" :class="tab == 'user' ? 'dmiux_active' : ''">User SQL</a>
                    <a @click="tab = 'postgres'" href="javascript:void(0)" class="dmiux_htabs__item" :class="tab == 'postgres' || selectedView.user_sql == null ? 'dmiux_active' : ''">Database Generated SQL</a>
                </div>

                <button type="button"
                        v-if="selectedView.schema == 'public'"
                        class="dmiux_button float-right m-1"
                        @click="updateView()"
                        data-toggle="tooltip"
                        :title="selectedView.exists && selectedView.synchronized ? 'Update' : 'Recreate'">
                        <template v-if="selectedView.exists && selectedView.synchronized">Update</template>
                        <template v-else>Recreate</template>
                </button>
                <button type="button"
                        v-if="selectedView.type == 'materialized' && selectedView.schema == 'public' && selectedView.exists == true && selectedView.synchronized == true"
                        class="dmiux_button float-right m-1"
                        @click="refreshView(selectedView.name, selectedView.schema)"
                        data-toggle="tooltip"
                        title="Refresh">Refresh</button>
                <button type="button"
                        v-if="selectedView.sql != '' && selectedView.schema == 'public' && selectedView.exists == true && selectedView.synchronized == true"
                        class="dmiux_button float-right m-1"
                        @click="dropView(false)"
                        data-toggle="tooltip"
                        title="Delete">Delete</button>
            </template>
        </div>
        <div class="dmiux_report__heading database-manager-overflow_text">{{ this.viewName }}</div>
        <prism-editor v-if="tab == 'user' && this.selectedView.user_sql != null" class="dmiux_input__input sql-editor" readonly line-numbers placeholder="SQL Definition" :highlight="highlighter" v-model="selectedView.user_sql"></prism-editor>
        <prism-editor v-else class="dmiux_input__input sql-editor" readonly line-numbers placeholder="SQL Definition" :highlight="highlighter" v-model="selectedView.sql"></prism-editor>
    </div>
</script>

<script type="text/x-template" id="component-database-manager_foreign_tables">
    <div v-if="selectedTable != undefined && tableSchema + '.' + tableName == selectedTable.schema + '.' + selectedTable.name" class="dmiux_grid-col dmiux_grid-col_95 dmiux_grid-col_lg-9 dmiux_grid-col_md-12">
        <div class="dmiux_grid-row">
            <div class="dmiux_grid-col">
                <template v-if="selectedTable.type == 'FOREIGN'">
                    <button type="button"
                            class="dmiux_button float-right m-1 tooltip-pretty"
                            @click="refreshForeignDatabase(selectedTable.schema, selectedTable.name)"
                            data-toggle="tooltip"
                            title="Refresh the current foreign table's definition from its source.">Refresh Definition</button>
                </template>
                <div class="dmiux_report__heading">{{ this.tableName }}</div>
            </div>
        </div>

        <div class="dmiux_grid-row">
            <div class="dmiux_grid-col">
                <div class="dmiux_cards">
                    <div id="dmiux_cards__row" class="dmiux_cards__row dmiux_cards__row_flex dmiux_grid-row">
                        <div class="dmiux_cards__col dmiux_grid-col">
                            <div class="dmiux_cards__item dmiux_cards__item_auto_height">
                                <div class="dmiux_cards__heading">Estimated Records</div>
                                <table class="dmiux_cards__table">
                                    <tbody>
                                        <tr>
                                            <td>Not available for foreign tables</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="dmiux_cards__col dmiux_grid-col">
                            <div class="dmiux_cards__item dmiux_cards__item_auto_height">
                                <div class="dmiux_cards__heading">Views</div>
                                <table class="dmiux_cards__table">
                                    <tbody>
                                        <tr>
                                            <td>{{ formatCount(selectedTable.views.length) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="dmiux_cards__col dmiux_grid-col">
                            <div class="dmiux_cards__item dmiux_cards__item_auto_height">
                                <div class="dmiux_cards__heading">Size</div>
                                <table class="dmiux_cards__table">
                                    <tbody>
                                        <tr>
                                            <td>{{ selectedTable.size }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="dmiux_grid-row">
            <div class="dmiux_grid-col dmiux_grid-col_12">
                <div class="dmiux_htabs dmiux_noscrollbar">
                    <button type="button" @click="tab = 'columns'" :class="tab == 'columns' ? 'dmiux_active' : ''" class="dmiux_htabs__item">Columns</button>
                    <button type="button" @click="tab = 'views'" :class="tab == 'views' ? 'dmiux_active' : ''" class="dmiux_htabs__item">Views</button>
                </div>
                <div>
                    <div v-show="tab == 'columns'" class="dmiux_grid-cont_fw dmiux_data-table dmiux_data-table__cont">
                        <table class="manage-tables dmiux_data-table__table">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Position</th>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Length</th>
                                    <th>Default</th>
                                    <th>Required</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="column in selectedTable.columns">
                                    <td></td>
                                    <td>{{ column.ordinal_position }}</td>
                                    <td>{{ column.column_name }}</td>
                                    <td>{{ column.udt_name }}</td>
                                    <td v-if="column.character_maximum_length != ''">{{ column.character_maximum_length }}</td>
                                    <td v-else-if="column.numeric_precision != ''">{{ column.numeric_precision }}<span v-if="column.numeric_scale != 0">, {{ column.numeric_scale }}</span></td>
                                    <td v-else></td>
                                    <td>{{ column.column_default }}</td>
                                    <td>{{ column.is_nullable }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-show="tab == 'views'" class="dmiux_grid-cont_fw dmiux_data-table dmiux_data-table__cont">
                        <table class="manage-tables dmiux_data-table__table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Schema</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="view in selectedTable.views">
                                    <td>{{ view.view_name }}</td>
                                    <td>{{ view.view_schema }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>

<script type="text/x-template" id="component-table_details">
    <div v-if="selectedTable != undefined && tableSchema + '.' + tableName == selectedTable.schema + '.' + selectedTable.name" class="dmiux_grid-col dmiux_grid-col_95 dmiux_grid-col_lg-9 dmiux_grid-col_md-12">
        <div class="dmiux_grid-row">
            <div class="dmiux_grid-col">
                <template v-if="selectedTable.type == 'Custom Table'">
                    <router-link class="text-decoration-none" :to="{ name: 'logs', params: { table_id: selectedTable.table_id } }">                  
                        <button id="view-table-logs" type="button" class="dmiux_button float-right m-1">Import Logs</button>
                    </router-link>
                    <button type="button"
                            class="dmiux_button float-right m-1"
                            data-toggle="tooltip"
                            @click="appendDataToTable(selectedTable)"
                            title="Append Data">Append Data</button>
                    <button type="button"
                            class="dmiux_button float-right m-1"
                            @click="replaceTable(selectedTable)"
                            data-toggle="tooltip"
                            title="Replace">Replace</button>
                    <button type="button"
                            class="dmiux_button float-right m-1"
                            @click="submitDrop(selectedTable)"
                            data-toggle="tooltip"
                            title="Delete">Delete</button>
                </template>
                <div class="dmiux_report__heading">{{ tableName }}</div>
            </div>
        </div>
        <div class="dmiux_grid-row">
            <div class="dmiux_grid-col">
                <div class="dmiux_cards">
                    <div id="dmiux_cards__row" class="dmiux_cards__row dmiux_cards__row_flex dmiux_grid-row">
                        <div class="dmiux_cards__col dmiux_grid-col">
                            <div class="dmiux_cards__item dmiux_cards__item_auto_height">
                                <div class="dmiux_cards__heading">Estimated Records</div>
                                <table class="dmiux_cards__table">
                                    <tbody>
                                        <tr>
                                            <td>{{ formatCount(selectedTable.record_count) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="dmiux_cards__col dmiux_grid-col">
                            <div class="dmiux_cards__item dmiux_cards__item_auto_height">
                                <div class="dmiux_cards__heading">Views</div>
                                <table class="dmiux_cards__table">
                                    <tbody>
                                        <tr>
                                            <td>{{ formatCount(selectedTable.views.length) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="dmiux_cards__col dmiux_grid-col">
                            <div class="dmiux_cards__item dmiux_cards__item_auto_height">
                                <div class="dmiux_cards__heading">Size</div>
                                <table class="dmiux_cards__table">
                                    <tbody>
                                        <tr>
                                            <td>{{ selectedTable.size }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="dmiux_grid-row">
            <div class="dmiux_grid-col dmiux_grid-col_12">
                <div class="dmiux_htabs dmiux_noscrollbar">
                    <button type="button" @click="tab = 'columns'" :class="tab == 'columns' ? 'dmiux_active' : ''" class="dmiux_htabs__item">Columns</button>
                    <button type="button" @click="tab = 'indexes'" :class="tab == 'indexes' ? 'dmiux_active' : ''" class="dmiux_htabs__item">Indexes</button>
                    <button type="button" @click="tab = 'relationships'" :class="tab == 'relationships' ? 'dmiux_active' : ''" class="dmiux_htabs__item">Relationships</button>
                    <button type="button" @click="tab = 'views'" :class="tab == 'views' ? 'dmiux_active' : ''" class="dmiux_htabs__item">Views</button>
                </div>
                <div>
                    <div v-show="tab == 'columns'" class="dmiux_grid-cont_fw dmiux_data-table dmiux_data-table__cont">
                        <table class="manage-tables dmiux_data-table__table">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Position</th>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Length</th>
                                    <th>Default</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="column in selectedTable.columns">
                                    <td></td>
                                    <td>{{ column.ordinal_position }}</td>
                                    <td>{{ column.column_name }}</td>
                                    <td>{{ column.udt_name }}</td>
                                    <td v-if="column.character_maximum_length != ''">{{ column.character_maximum_length }}</td>
                                    <td v-else-if="column.numeric_precision != ''">{{ column.numeric_precision }}<span v-if="column.numeric_scale != 0">, {{ column.numeric_scale }}</span></td>
                                    <td v-else></td>
                                    <td>{{ column.column_default }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-show="tab == 'indexes'" class="dmiux_grid-cont_fw dmiux_data-table dmiux_data-table__cont">
                        <button @click="openIndexModal()" class="dmiux_button float-right">+ Add Index</button>
                        <br>
                        <br>
                        <table class="manage-tables dmiux_data-table__table">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Name</th>
                                    <th>Definition</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="index in selectedTable.indexes">
                                    <td>
                                        <div class="dmiux_data-table__actions">
                                            <div v-if="index.index_name.includes('bytespree_')" class="dmiux_actionswrap--bin cursor-p" @click="removeIndex(index.index_name)" data-toggle="tooltip" title="Delete"></div>
                                        </div>
                                    </td>
                                    <td>{{ index.index_name }}</td>
                                    <td>{{ index.indexdef }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-show="tab == 'relationships'" class="dmiux_grid-cont_fw dmiux_data-table dmiux_data-table__cont">
                        <table class="manage-tables dmiux_data-table__table">
                            <thead>
                                <tr>
                                    <th>Relationship Name</th>
                                    <th>Column</th>
                                    <th>Foreign Table</th>
                                    <th>Foreign Column</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="relationship in selectedTable.relationships">
                                    <td>{{ relationship.constraint_name }}</td>
                                    <td>{{ relationship.column_name }}</td>
                                    <td>{{ relationship.foreign_table_name }}</td>
                                    <td>{{ relationship.foreign_column_name }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-show="tab == 'views'" class="dmiux_grid-cont_fw dmiux_data-table dmiux_data-table__cont">
                        <table class="manage-tables dmiux_data-table__table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Schema</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="view in selectedTable.views">
                                    <td>{{ view.view_name }}</td>
                                    <td>{{ view.view_schema }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>

<script type="text/x-template" id="component-table_logs">
    <div class="dmiux_grid-col dmiux_grid-col_95 dmiux_grid-col_lg-9 dmiux_grid-col_md-12">
        <div class="dmiux_grid-row">
            <div class="dmiux_grid-col">
                <router-link class="text-decoration-none" :to="{ name: 'tables', params: { } }">                  
                    <button id="back-from-table-logs" type="button" class="dmiux_button float-right m-1">Back</button>
                </router-link>
                <div class="dmiux_report__heading">{{ tableName }}</div>
            </div>
        </div>
        <div class="dmiux_grid-cont_fw dmiux_data-table dmiux_data-table__cont">
            <table id="table-import-logs" class="dmiux_data-table__table">
                <thead>
                    <tr>
                        <th></th>
                        <th>File Name</th>
                        <th>Author</th>
                        <th>Upload Date</th>
                        <th>Table Name</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="log in logs">
                        <td><router-link :to="{ name: 'details', params: { table_id: table_id, log_id: log.id } }">details</router-link></td>
                        <td>{{ log.file_name }}</td>
                        <td>{{ log.author }}</td>
                        <td>{{ log.formatted_date }}</td>
                        <td>{{ log.table_name }}</td>
                        <td>{{ log.status }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</script>

<script type="text/x-template" id="component-log_details">
    <div class="dmiux_grid-col dmiux_grid-col_95 dmiux_grid-col_lg-9 dmiux_grid-col_md-12">
        <div class="dmiux_grid-row">
            <div class="dmiux_grid-col">
                <router-link class="text-decoration-none" :to="{ name: 'logs', params: { table_id: table_id } }">                  
                    <button id="back-from-log-details" type="button" class="dmiux_button float-right m-1">Back</button>
                </router-link>
                <div class="dmiux_report__heading">{{ tableName }}</div>
            </div>
        </div>
        <div v-if="log.length != []" class="dmiux_grid-row">
            <div class="dmiux_grid-col">
                <div class="dmiux_cards">
                    <div id="dmiux_cards__row" class="dmiux_cards__row dmiux_cards__row_flex dmiux_grid-row">
                        <div class="dmiux_cards__col dmiux_grid-col_4 dmiux_grid-col_md-12 mb-1">
                            <div class="dmiux_cards__item log-details-card_height">
                                <div class="dmiux_cards__heading">File Summary</div>
                                <table class="dmiux_cards__table">
                                    <tbody>
                                        <tr>
                                            <td>File Name:</td>
                                            <td :title="log.file_name" class="log-details-overflow_name">{{ log.file_name }}</td>
                                        </tr>
                                        <tr>
                                            <td>File Size:</td>
                                            <td>{{ formatBytes(log.file_size) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="dmiux_cards__col dmiux_grid-col_4 dmiux_grid-col_md-12 mb-1">
                            <div class="dmiux_cards__item log-details-card_height">
                                <div class="dmiux_cards__heading">Upload Settings</div>
                                <table class="dmiux_cards__table">
                                    <tbody>
                                        <tr>
                                            <td>First row has column names:</td>
                                            <td>{{ log.settings.has_columns }}</td>
                                        </tr>
                                        <tr>
                                            <td>Ignore errors on import:</td>
                                            <td>{{ log.settings.ignore_errors }}</td>
                                        </tr>
                                        <tr>
                                            <td>Enclosed Character:</td>
                                            <td>{{ log.settings.enclosed }}</td>
                                        </tr>
                                        <tr>
                                            <td>Escape Character:</td>
                                            <td>{{ log.settings.escape }}</td>
                                        </tr>
                                        <tr>
                                            <td>Encoding:</td>
                                            <td>{{ log.settings.encoding }}</td>
                                        </tr>
                                        <tr>
                                            <td>Delimiter:</td>
                                            <td>{{ log.settings.delimiter }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="dmiux_cards__col dmiux_grid-col_4 dmiux_grid-col_md-12 mb-1">
                            <div class="dmiux_cards__item log-details-card_height">
                                <div class="dmiux_cards__heading">Import Summary</div>
                                <table class="dmiux_cards__table">
                                    <tbody>
                                        <tr>
                                            <td>Table Name:</td>
                                            <td :title="log.table_name" class="log-details-overflow_name">{{ log.table_name }}</td>
                                        </tr>
                                        <tr>
                                            <td>Type:</td>
                                            <td>{{ log.type }}</td>
                                        </tr>
                                        <tr>
                                            <td>Records Imported:</td>
                                            <td>{{ log.records_imported }}</td>
                                        </tr>
                                        <tr>
                                            <td>Records In Error:</td>
                                            <td>{{ log.records_in_error }}</td>
                                        </tr>
                                        <tr>
                                            <td>Status:</td>
                                            <td>{{ log.status }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="log.length != []" class="dmiux_grid-row pl-3 pr-3">
            <div class="dmiux_report__heading text-muted mt-0">Columns</div>
            <div class="dmiux_grid-cont_fw dmiux_data-table dmiux_data-table__cont w-100">
                <table id="log-details-columns" class="dmiux_data-table__table">
                    <thead>
                        <tr>
                            <th><span v-if="log.mappings != null">From </span>Column</th>
                            <th v-if="log.mappings != null">To Column</th>
                            <th>Type</th>
                            <th>Length</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="column in log.columns">
                            <td>{{ column.column_name }}</td>
                            <td v-if="log.mappings != null">{{ column.column }}</td>
                            <td>{{ getType(column) }}</td>
                            <td>{{ getLength(column) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</script>

<script>
    var tables = {
        template: '#component-database-manager_tables'
    }

    var table_details = {
        template: '#component-table_details',
        props: [ 'tableName', 'tableSchema' ],
        watch: {
            tableName() {
                this.updateData();
            }
        },
        data: function () {
            return {
                tab: "columns",
                selectedTable: []
            }
        },
        methods: {
            formatCount(count) {
                return parseFloat(count).toFixed().toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            },
            submitDrop(table, ignore_warning = false){
                var table_name = table.name;
                var table_schema = table.schema;
                if(ignore_warning == false) {
                    if (! confirm(`Are you sure you want to delete table ${table_name}?`)) {
                        return false;
                    }
                }
                
                this.$root.loading(true);
                fetch(`/internal-api/v1/data-lakes/${control_id}/tables/${table_schema}/${table_name}/${ignore_warning}`, {method: 'delete'})
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.$root.loading(false);
                        if(ignore_warning == false) {
                            if(json.data == "warning") {
                                if(!confirm(json.message)) {
                                    return;
                                } else {
                                    this.submitDrop(table, true);
                                    return;
                                }
                            }
                        }

                        this.$root.getTables();
                        this.$router.push("/");
                        notify.success(`${table_name} table has been dropped.`);
                    })
                    .catch(error => {
                        this.$root.loading(false);
                        ResponseHelper.handleErrorMessage(error, 'There was an issue deleting the table.');
                    });
            },
            replaceTable(replacing_table, ignore_warning = false){                
                if(! confirm(`Are you sure you want to replace table ${replacing_table.name}?`)) {
                    return;
                }

                this.$root.loading(true);
                fetch(`/internal-api/v1/data-lakes/${control_id}/tables/${replacing_table.schema}/${replacing_table.name}/dependencies`)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.$root.loading(false);

                        app.$refs["addTable"].reset();
                        app.$refs["addTable"].dependant_views = json.data;
                        app.$refs["addTable"].table_id = replacing_table.table_id;
                        app.$refs["addTable"].is_replacing = true;
                        app.$refs["addTable"].is_appending = false;
                        app.$refs["addTable"].file_format_changed = false;
                        app.$refs["addTable"].has_mismatched_column_names = false;
                        app.$refs["addTable"].has_mismatched_column_count = false;
                        app.$refs["addTable"].file_format_matches = true;

                        openModal('#modal-add_table');
                    })
                    .catch(error => {
                        this.$root.loading(false);
                        ResponseHelper.handleErrorMessage(error, 'There was an issue appending the data to the table.');
                    });
            },
            openIndexModal() {
                app.$refs.manageIndex.selected_column = "";

                if(app.$refs.manageIndex.columns.length == app.$refs.manageIndex.indexes.length) {
                    notify.info("All columns have an index over them");
                    return;
                }

                openModal('#modal-add_index');
            },
            appendDataToTable(selectedTable) {
                this.$root.$emit('appendDataToTable', selectedTable);
            },
            removeIndex(index_name) {
                if(! confirm("Are you sure you want to delete this index?")) {
                    return;
                }

                this.$root.loading(true);
                fetch(`/internal-api/v1/data-lakes/${this.$root.control_id}/tables/index/${index_name}`, { method: 'delete' })
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.$root.loading(false);
                        this.$root.getTables(true);
                    })
                    .catch((error) => {
                        this.$root.loading(false);
                        ResponseHelper.handleErrorMessage(error, "Failed to delete index.");
                    });
            },
            updateData() {
                this.$root.$refs.databaseManager.clearSelectedTable();
                this.selectedTable = this.$root.$refs.databaseManager.selectedTable;
                this.$root.$refs.databaseManager.getTableDetails(this.tableName, this.tableSchema, this.$route.params.index, 'normal'); 
            },
            loadPage() {
                if(this.$root.allItemsLoaded()) {
                    if(this.$root.tables[this.$route.params.index] == undefined || this.$root.tables[this.$route.params.index].table_name != this.$route.params.tableName) {
                        notify.info("Table does not exist it may have been deleted or renamed");
                        this.clearPath();
                    } else {
                        this.$root.$refs.databaseManager.active_schema_table = this.tableSchema;
                        this.$root.switchToTables(true); 
                        this.updateData();
                    }
                } else {
                    setTimeout(() => this.loadPage(), 500);
                }
            }
        },
        mounted() {
            this.loadPage();
        }
    }

    var table_logs = {
        template: '#component-table_logs',
        props: [ 'tableName', 'table_id' ],
        data: function () {
            return {
                logs: []
            }
        },
        methods: {
            getTableLogs() {
                this.$root.loading(true);
                fetch(`/internal-api/v1/data-lakes/${control_id}/tables/${this.table_id}/logs`)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.$root.loading(false);
                        this.logs = json.data
                    })
                    .then(() => {
                        this.$nextTick(() => {
                            $('#table-import-logs').DataTable({
                                "order": [[ 3, "desc" ]]
                            });
                        });
                    })
                    .catch((error) => {
                        this.$root.loading(false);
                        ResponseHelper.handleErrorMessage(error, "Unable to get table logs at the moment.");
                    });
            }
        },
        mounted() {
            this.getTableLogs();
            this.$root.switchToTables(true);
        }
    }

    var log_details = {
        template: '#component-log_details',
        props: [ 'table_id', 'log_id', 'tableName' ],
        data: function () {
            return {
                log: []
            }
        },
        methods: {
            getLog() {
                this.$root.loading(true);
                fetch(`/internal-api/v1/data-lakes/${control_id}/tables/logs/${this.log_id}`)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.$root.loading(false);
                        this.log = json.data
                    })
                    .then(() => {
                        this.$nextTick(() => {
                            $('#log-details-columns').DataTable({
                                "ordering": false
                            });
                        });
                    })
                    .catch((error) => {
                        this.$root.loading(false);
                        ResponseHelper.handleErrorMessage(error, "Unable to get table log at the moment.");
                    });
            },
            formatBytes(size) {
                return BytespreeUiHelper.formatBytes(size);
            },
            getType(column) {
                if(column.udt_name != undefined) {
                    return column.udt_name;
                } else {
                    return column.type;
                }
            },
            getLength(column) {
                var type = this.getType(column);

                if(column.character_maximum_length != undefined) {
                    var length = column.character_maximum_length;
                } else {
                    var length = column.value;
                }

                if (type == "numeric") {
                    return `${column.numeric_precision}, ${column.numeric_scale}`
                } else if (type == "decimal") {
                    return `${length}, ${column.precision}`
                } else if (type == "varchar") {
                    return length
                } else {
                    return '';
                }
            }
        },
        mounted() {
            this.getLog();
            this.$root.switchToTables(true);
        }
    }

    var views = {
        template: '#component-database-manager_views',
        props: [ "selectedView", "viewName", "viewIndex", "editor" ],
        data: function () {
            return {
                tab: "user"
            }
        },
        methods: {
            highlighter(code) {
                // js highlight example
                return Prism.highlight(code, Prism.languages.sql, "sql");
            },
            dropView(ignore_warning) {
                if(ignore_warning == false) {
                    console.log(this.selectedView);
                    if (this.selectedView.dependent_views.length > 0) {
                        var dependencies = this.selectedView.dependent_views.map((v) => { return v.schema + '.' + v.name; });
                        notify.danger("This view cannot be deleted because it has dependent views: \n\n" + dependencies.join(', '));
                        return;
                    }
                    
                    if(this.selectedView.foreign_dependent_views.length > 0) {
                        var dependencies = this.selectedView.foreign_dependent_views.map((v) => { return v.schema + '.' + v.name; });
                        notify.danger("This view cannot be deleted because it has dependent views in foreign databases: \n\n" + dependencies.join(', '));
                        return;
                    }

                    var message = "Are you sure you want to delete this view? This cannot be undone.";

                    if (this.selectedView.downstreamBuilds.length > 0) {
                        message  += "\n\nDeleting this view will affect the builds of the following views:\n\n" + this.selectedView.downstreamBuilds.join(', ');
                    }

                    if(!confirm(message)) {
                        return;
                    }
                }

                let options = FetchHelper.buildJsonRequest({
                    "schedule_id": this.selectedView.schedule_id
                }, 'delete');

                this.$root.loading(true);
                fetch(`/internal-api/v1/data-lakes/${control_id}/views/${this.selectedView.id}`, options)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.$root.loading(false);
                        if(ignore_warning == false) {
                            if(json.data == "warning") {
                                if(!confirm(json.message)) {
                                    return;
                                } else {
                                    this.dropView(true);
                                    return;
                                }
                            }
                        }

                        this.$router.push("/");

                        this.$root.getViews(function() {
                            notify.success("View dropped");
                            this.viewIndex = -1;
                            this.viewName = "";
                            app.loading(false);
                        });
                    })
                    .catch((error) => {
                        this.$root.loading(false);
                        ResponseHelper.handleErrorMessage(error, "The view cannot be deleted right now.");
                    });
            },
            refreshView(view_name, schema) {
                this.$root.loading(true);

                let options = FetchHelper.buildJsonRequest({
                    'name': view_name,
                    'schema': schema
                }, 'put');

                fetch(`/internal-api/v1/data-lakes/${control_id}/views/refresh`, options)
                    .then(response => response.json())
                    .then(json => {
                        this.$root.loading(false);
                        this.$root.getViews(function() {
                            notify.success("View refresh has been queued");
                        });
                    })
                    .catch((error) => {
                        this.$root.loading(false);
                        ResponseHelper.handleErrorMessage(error, "The view cannot be refeshed right now.");
                    });
            },
            updateView() {
                if(this.selectedView.user_sql == null) {
                    app.$refs.manageView.sql = this.selectedView.sql;
                } else {
                    let all_check = this.selectedView.user_sql.replace(/(\r\n|\n|\r)/gm, "").toLowerCase();
                    
                    if (all_check.includes('select *')) {
                        notify.info("This query selects all columns. So if new columns were added since it's last publish they will now be included.");
                    }

                    app.$refs.manageView.sql = this.selectedView.user_sql;
                }

                
                app.$refs.manageView.name = this.selectedView.name;
                app.$refs.manageView.type = this.selectedView.type;
                app.$refs.manageView.orig_name = this.selectedView.name;
                app.$refs.manageView.orig_type = this.selectedView.type;
                app.$refs.manageView.frequency = this.selectedView.frequency;
                app.$refs.manageView.schedule = this.selectedView.schedule;
                app.$refs.manageView.schedule_id = this.selectedView.schedule_id;
                app.$refs.manageView.history_guid = this.selectedView.history_guid;
                app.$refs.manageView.build_on = this.selectedView.build_on;
                app.$refs.manageView.upstream_build_id = this.selectedView.upstream_build_id ?? "";
                app.$refs.manageView.downstream_views = this.selectedView.downstreamBuilds;
                app.$refs.manageView.dependent_views = this.selectedView.dependent_views;
                app.$refs.manageView.foreign_dependent_views = this.selectedView.foreign_dependent_views;

                if (this.selectedView.exists) {
                    app.$refs.manageView.editing = true;
                } else {
                    app.$refs.manageView.editing = false;
                    app.$refs.manageView.recreate = true;
                }
                $(document).on("mousedown", "#dmiux_body", app.$refs.manageView.modalClose);
                $(document).on("keydown", app.$refs.manageView.modalClose);
                
                openModal('#modal-add_view');
                return;
            },
            rebuildView(view_id) {
                this.$root.loading(true);
                let options = FetchHelper.buildJsonRequest({
                    view_id: view_id
                });

                fetch(`/internal-api/v1/data-lakes/${control_id}/views/rebuild`, options)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.$root.loading(false);
                        if (json.status == 'ok') {
                            notify.success("View has been rebuilt");
                            this.$root.getViews();
                        } else {
                            notify.danger(json.message);
                        }
                    })
                    .catch((error) => {
                        this.$root.loading(false);
                        ResponseHelper.handleErrorMessage(error, "The view cannot be rebuilt right now.");
                    });
            }
        }
    }
    
    var foreign_tables = {
        template: '#component-database-manager_foreign_tables',
        props: [ 'tableName', 'tableSchema', 'selectedTable' ],
        data: function () {
            return {
                tab: "columns"
            }
        },
        methods: {
            formatCount(count) {
                return parseFloat(count).toFixed().toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            },
            refreshForeignDatabase(schema, table){
                var options = FetchHelper.buildJsonRequest({
                    schema: schema,
                    table: table
                }, 'put');

                this.$root.loading(true);

                fetch(`/internal-api/v1/data-lakes/${control_id}/foreign-databases/refresh`, options)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.$root.getForeignTables(() => {
                            notify.success("Foreign table definition has been refreshed.");
                        });
                    })
                    .catch((error) => {
                        try {
                            if (error.json.data.deleted == true) {
                                this.$root.getForeignTables(() => {
                                    ResponseHelper.handleErrorMessage(error, "Foreign table appears to have been deleted in its source schema.");
                                });
                                this.$root.switchToForeignTables();
                                return;
                            }
                        } catch(e) {
                            // ... fail silently
                        }

                        this.$root.loading(false);
                        ResponseHelper.handleErrorMessage(error, "The table definition could not be refreshed.");
                    });
            }
        }
    }

    var databaseManager = {
        template: '#component-database-manager',
        props: [ 'tables', 'views', 'foreign_tables' ],
        data: function() {
            return {
                hover: -1,
                tableName: "",
                tableSchema: "",
                index: 0,
                viewName: "",
                viewIndex: -1,
                active_schema_table: '', 
                active_schema_view: '', 
                active_schema_foreign:'',
                selectedTable: {
                    "record_count": 0,
                    "size": 0,
                    "columns": [],
                    "indexes": [],
                    "type": '',
                    "name": '',
                    "schema" : '',
                    "relationships": [],
                    "views": []
                },
                selectedView: {
                    "sql": "",
                    "name": "",
                    "type": "",
                    "frequency": "",
                    "schema": "",
                    "schedule": {},
                    "schedule_id" : 0,
                    "history_guid": "",
                    "exists" : false,
                    "synchronized" : false,
                    "build_on": "",
                    "upstream_build_id": "",
                    "downstreamBuilds": [],
                    "dependent_views": []
                }
            }
        },
        watch: {
            views() {
                if (this.views.length > 0 && this.viewIndex >= 0) {
                    if (this.views[this.viewIndex] !== undefined) {
                        this.getViewDetails(this.views[this.viewIndex].table_name, this.viewIndex);
                    }
                }
            }
        },
        methods: {
            getViewName(view) {
                if (view.exists && view.synchronized)
                    return view.view_name;
                else
                    return `! ${view.view_name} (missing)`;
            },
            getViewDetails(viewName, index) {
                if (this.views[index] === undefined) {
                    return;
                }
                this.$root.loading(true);
                this.selectedView.id = this.views[index].id;
                this.selectedView.sql = this.views[index].view_definition;
                this.selectedView.user_sql = this.views[index].user_definition;
                this.selectedView.schema = this.views[index].view_schema;
                this.selectedView.name = this.views[index].view_name;
                this.selectedView.type = this.views[index].view_type;
                this.selectedView.frequency = this.views[index].frequency;
                this.selectedView.schedule = this.views[index].schedule;
                this.selectedView.schedule_id = this.views[index].schedule_id;
                this.selectedView.history_guid = this.views[index].view_history_guid;
                this.selectedView.exists = this.views[index].exists;
                this.selectedView.synchronized = this.views[index].synchronized;
                this.selectedView.build_on = this.views[index].build_on;
                this.selectedView.upstream_build_id = this.views[index].upstream_build_id ?? "";
                this.selectedView.downstreamBuilds = this.views[index].downstream_views;
                this.selectedView.dependent_views = this.views[index].dependent_views;
                this.selectedView.foreign_dependent_views = this.views[index].foreign_dependent_views;
                this.viewName = viewName;
                this.viewIndex = index;
                this.$root.loading(false);
            },
            getTableDetails(tableName, tableSchema, index, type) {
                this.$root.loading(true);
                this.tableName = tableName;
                this.tableSchema = tableSchema;
                this.index = index;
                fetch(`/internal-api/v1/data-lakes/${this.$root.control_id}/tables/${this.tableSchema}/${this.tableName}/details`)
                    .then(response => {
                        app.loading(false);
                        return response;
                    })
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        $('.manage-tables').DataTable().destroy();
                        if(type == 'normal') {
                            this.selectedTable.record_count = this.tables[index].num_records;
                            app.$refs.manageIndex.indexes = json.data.index_columns;
                            this.selectedTable.relationships = json.data.relationships;
                            this.selectedTable.size = this.tables[index].total_size;
                            this.selectedTable.type = this.tables[index].table_type;
                            this.selectedTable.table_id = this.tables[index].table_id;
                        }
                        else {
                            this.selectedTable.size = this.foreign_tables[index].total_size;
                            this.selectedTable.type = this.foreign_tables[index].table_type;
                        }
                        this.selectedTable.columns = json.data.columns;
                        this.selectedTable.name = tableName;
                        this.selectedTable.schema = tableSchema;
                        this.selectedTable.indexes = json.data.indexes;
                        app.$refs.manageIndex.columns = json.data.columns;
                        this.selectedTable.views = json.data.views;
                    })
                    .then(() => {
                        this.$nextTick(() => {
                            $('.manage-tables').DataTable();
                        });
                    })
                    .catch((error) => {
                        this.clearSelectedTable();
                        ResponseHelper.handleErrorMessage(error, "Unable to get table details at the moment.");
                    });
            },
            clearSelectedTable() {
                $('.manage-tables').DataTable().destroy();
                this.tableName = "";
                this.tableSchema = "";
                this.selectedTable = {
                    "table_id": 0,
                    "record_count": 0,
                    "size": 0,
                    "columns": [],
                    "indexes": [],
                    "type": '',
                    "name": '',
                    "schema" : '',
                    "relationships": [],
                    "views": []
                };
            },
            setActiveSchema(schema,type){
                if(type == 'table') {
                    this.active_schema_table = schema;
                } else if(type == 'view') {
                    this.active_schema_view = schema;
                } else {
                    this.active_schema_foreign = schema;
                }
            }
        },
        computed: {
            table_schemas() {
                var schemas = [];
                for(let i = 0; i < this.tables.length; i++) {
                    if(!schemas.includes(this.tables[i].table_schema)) {
                        schemas.push(this.tables[i].table_schema);
                    }
                }
                if(schemas.length > 0){
                    this.active_schema_table = schemas[0];
                }
                return schemas;
            },
            view_schemas() {
                var schemas = [];
                for(let i = 0; i < this.views.length; i++) {
                    if(!schemas.includes(this.views[i].view_schema)) {
                        schemas.push(this.views[i].view_schema);
                    }
                }
                if(this.active_schema_view == ""){
                    if(schemas.length > 0){
                        this.active_schema_view = schemas[0];
                    }
                }

                return schemas;
            },
            foreign_table_schemas() {
                if(this.$root.foreign_schemas.length > 0){
                    this.active_schema_foreign = this.$root.foreign_schemas[0];
                }
                return this.$root.foreign_schemas;
            },
            tableless_foreign_schemas() {
                var schemas = [];
                for(let i = 0; i < this.$root.foreign_schemas.length; i++) {
                    if(! this.$root.foreign_tables.map(function(value, index) { return value['table_schema']; }).includes(this.$root.foreign_schemas[i])) {
                        schemas.push(this.$root.foreign_schemas[i]);
                    }
                }
                return schemas;
            }
        }
    }
</script>