<script type="text/x-template" id="component-records">
    <div class="container-fluid studio-records-content mt-md-4 mt-0" id="content">
        <div class="table-responsive dmiux_table_container dataTables_wrapper dmiux_grid-row m-0 flex-column-reverse flex-lg-row" style="overflow-x: initial !important" id="explorer">
            <div v-if="$root.explorer.view_mode == 'save'" class="dmiux_grid-col dmiux_grid-col_12 pb-4 py-sm-0 order-lg-0 order-md-2" >
                <div class="alert alert-warning mb-0">
                    <strong>Heads up!</strong> You are currently editing view "{{$root.explorer.view.view_name}}". <a href="javascript:void(0);" @click="$root.returnToView()">Cancel Editing</a>
                </div>
            </div>
            <div v-else-if="Object.keys($root.explorer.origin_query).length > 0" class="dmiux_grid-col dmiux_grid-col_12">
                <div class="alert alert-warning">
                    <strong>Heads up!</strong> You are currently looking at a previous version of a view. <a href="javascript:void(0);" @click="$root.endPreview()">Cancel Preview</a>
                </div>
            </div>

            <div v-if="($root.ready == true) && (records.length == 0) && (view_mode == 'save')" class="dmiux_grid-col dmiux_grid-col_12">
                <div class="alert alert-warning">
                    <strong>Aw snap!</strong> Looks like this table is empty.
                </div>
            </div>
            <div v-if="$root.explorer.query.columns.length > 400" class="dmiux_grid-col dmiux_grid-col_12 order-lg-0 order-2 px-0 px-lg-2">
                <div class="alert alert-warning alert-dismissible">
                    Bytespree Studio is only able to display 400 columns, but this table has {{ $root.explorer.query.columns.length }}.
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            </div>
            <div :class="$refs.table_summary != undefined && $refs.table_summary.view_history.shown ? 'dmiux_grid-col_lg-7 dmiux_grid-col_75' : 'dmiux_grid-col_lg-9 dmiux_grid-col_95'" class="dmiux_grid-col dmiux_grid-col_md-12 px-0 px-lg-2">
                <div class="dmiux_data-table dmiux_data-table__cont">
                    <button v-if="!mobile && this.record_counts > 0" type="button" class="dmiux_data-table__arrow dmiux_data-table__arrow_left records-left" onclick="scroll_left('datatable_scroll', 'records-')"><i></i></button>
                    <button v-if="!mobile && this.record_counts > 0" id="arrow_fix_right" type="button" class="dmiux_data-table__arrow dmiux_data-table__arrow_right records-right" onclick="scroll_right('datatable_scroll', 'records-')"><i></i></button>
                    <div v-if="records.length > 0 || view_mode == 'save'" @scroll="hideShowArrows()" id="datatable_scroll">
                        <table v-if="pivoted == false" class="dmiux_data-table__table table-bordered dt-jqueryui">
                            <thead>
                                <tr>
                                    <th style="background-color: white !important; padding-top: 0px !important; padding-bottom: 0px !important;"></th>
                                    <th v-for="column in columns" :id="'table_' +  column.prefix + '_' + column.column_name" v-if="column.checked" style="background-color: white !important; padding-top: 0px !important; padding-bottom: 0px !important;">
                                        <center style="height: 30px !important; padding-top: 5px;">
                                            <span v-if="flags[column.table_name + '_' + column.column_name] != null"
                                                class="flag text-lg text-danger"
                                                data-toggle="tooltip"
                                                data-placement="bottom"
                                                :title="flags[column.table_name + '_' + column.column_name].assigned_user" >
                                                <span class="fas fa-flag"></span>
                                            </span>

                                            <span v-if="comments[column.table_name + '_' + column.column_name] != null"
                                                class="comment text-lg"
                                                data-toggle="tooltip"
                                                data-placement="bottom"
                                                :title="comments[column.table_name + '_' + column.column_name][0].comment_text">
                                                <span class="fas fa-comment" style="color: #374C68;"></span>
                                            </span>

                                            <span v-if="mappings[column.table_name + '_' + column.column_name] != null"
                                                class="mapping text-lg"
                                                data-toggle="tooltip"
                                                data-placement="bottom"
                                                :title="getColumnMappingTitle(column.table_name, column.column_name)">
                                                <span class="fas fa-map-marker-alt" style="color: #7F8FA5;"></span>
                                            </span>

                                            <span v-if="attachments[column.table_name + '_' + column.column_name] != null"
                                                class="files text-lg"
                                                data-toggle="tooltip"
                                                data-placement="bottom"
                                                :title="attachments[column.table_name + '_' + column.column_name][0].file_name">
                                                <span class="fas fa-file" style="color: #acb0c5;"></span>
                                            </span>

                                            <span v-if="transformations[column.prefix + '_' + column.column_name] != null && transformations[column.prefix + '_' + column.column_name].length > 0"
                                                class="transformations text-lg"
                                                data-toggle="tooltip"
                                                data-placement="bottom"
                                                :title="transformations[column.prefix + '_' + column.column_name].length == 1 ? '1 Transformation Applied' : transformations[column.prefix + '_' + column.column_name].length + ' Transformations Applied'">
                                                <span class="fas fa-exchange-alt" style="color: #acb0c5;"></span>
                                            </span>

                                            <span v-if="checkWhere(column.target_column_name, true)"
                                                class="text-lg"
                                                data-toggle="tooltip"
                                                data-placement="bottom"
                                                title="Filtered Column">
                                                <span class="fas fa-filter" style="color: #611d8f;"></span>
                                            </span>

                                        </center>
                                    </th>
                                </tr>
                                <tr>
                                    <th style="background-color: white !important; padding-top: 0px !important; padding-bottom: 0px !important;"></th>
                                    <th v-for="(bcolumn, index) in columns" v-if="bcolumn.checked" class="fill-circle" style="background-color: white !important; padding-top: 0px !important; padding-bottom: 0px !important;">
                                        <center v-if="(bcolumn.data_type != 'jsonb' || (bcolumn.data_type == 'jsonb' && (transformations[bcolumn.prefix + '_' + bcolumn.column_name] != null && transformations[bcolumn.prefix + '_' + bcolumn.column_name].length != 0)))" style="margin-bottom: 10px; margin-top: 10px;">
                                            <div class="dmiux_radio" style="display: block;">
                                                <input type="radio" @change="changeSelectedColumnValues(bcolumn.alias, bcolumn.data_type, bcolumn.sql_definition, bcolumn.prefix, bcolumn.column_name, index, bcolumn.is_aggregate, false)" v-model="column" :value="bcolumn.prefix + '_' + bcolumn.column_name" class="dmiux_radio__input">
                                                <div class="dmiux_radio__check" style="margin-left: 0px;"></div>
                                            </div>
                                        </center>
                                    </th>
                                </tr>
                                <tr>
                                    <th @click="$root.modals.change_column_preference = true"
                                        data-toggle="tooltip"
                                        title="Manage columns"
                                        class="dmiux_data-pivot-table-headings cog-wheel"
                                        style="width: 20px; cursor:pointer; padding-top: 3px !important; padding-bottom: 3px !important;">
                                        <img src="<?php echo getenv('DMIUX_URL'); ?>/img/icons/config.svg"
                                            class="text-center" />
                                    </th>
                                    <th v-for="acolumn in columns"
                                        v-if="acolumn.checked" 
                                        style="padding-top: 3px !important; padding-bottom: 3px !important;"
                                        class="text-center dmiux_data-pivot-table-headings">
                                        <div class="sort_column"
                                            @click="sortData(acolumn.column_name, acolumn.prefix, acolumn.alias, acolumn.sql_definition)"
                                            data-toggle="tooltip"
                                            data-placement="top"
                                            @mouseover="setHoverCol(acolumn)"
                                            :title="tooltip_title">
                                            <span :class="checkWhere(acolumn.target_column_name) ? 'dmiux_column-filtered' : ''">
                                                <div class="DataTables_sort_wrapper">
                                                    <template v-if="acolumn.alias == ''"><template v-if="viewing_type == 'Join' && acolumn.prefix != 'aggregate' && acolumn.prefix != 'custom'">{{ acolumn.prefix }}_</template>{{ acolumn.column_name }}</template>
                                                    <template v-else>{{ acolumn.alias }}</template>
                                                    <img v-if="isSortColumn(acolumn)" 
                                                        src="<?php echo getenv('DMIUX_URL'); ?>/img/icons/arrow-downsort.svg" 
                                                        :class="$root.explorer.query.order.order_type == 'asc' ? 'flip_sort' : ''"
                                                        class="dmiux_sorticon" />
                                                </div>
                                            </span>
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="tbody-explorer">
                                <tr v-for="record in records">
                                    <td></td>
                                    <td v-for="(bcolumn, index) in columns" v-if="bcolumn.checked">
                                        <template v-for="(val, key) in record" v-if="key == bcolumn.target_column_name || key == bcolumn.alias">
                                            <template v-if="isJson(val, bcolumn)">
                                                <button class="dmiux_button"
                                                        style="color: white;"
                                                        onclick="toggle_unstructured(this);">
                                                    Show unstructured data
                                                </button>
                                                <unstructured-stage :val="val"
                                                                    :flags="flags"
                                                                    :comments="comments"
                                                                    :mappings="mappings"
                                                                    :attachments="attachments"
                                                                    :prefix="bcolumn.prefix"
                                                                    :sql_definition="bcolumn.sql_definition"
                                                                    :table_name="bcolumn.table_name"
                                                                    :column_name="bcolumn.column_name"
                                                                    :selected_column_index="index">
                                                </unstructured-stage>
                                            </template>
                                            <template v-else-if="bcolumn.data_type == 'boolean'">
                                                <pre v-if="val === true" :class="checkWhere(key) ? 'dmiux_column-filtered' : ''">true</pre>
                                                <pre v-else-if="val == false" :class="checkWhere(key) ? 'dmiux_column-filtered' : ''">false</pre>
                                            </template>
                                            <div v-else :class="isNumberColumn(key) ? 'text-right' : ''">
                                                <pre :class="checkWhere(key) ? 'dmiux_column-filtered' : ''">{{ val }}</pre>
                                            </div>
                                        </template>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <table v-else class="dmiux_data-table__table table-bordered dt-jqueryui">
                            <thead>
                            </thead>
                            <tbody id="tbody-explorer">
                                <tr>
                                    <th class="dmiux_data-table-headings"></th>
                                    <th class="dmiux_data-table-headings"></th>
                                    <th @click="$root.modals.change_column_preference = true"
                                        data-toggle="tooltip"
                                        title="Manage columns"
                                        class="dmiux_data-pivot-table-headings dmiux_cog-wheel">
                                        <img src="<?php echo getenv('DMIUX_URL'); ?>/img/icons/config.svg"
                                            class="text-center" />
                                    </th>
                                    <td v-for="record in records"></td>
                                </tr>
                                <tr v-for="(bcolumn, index) in columns">
                                    <th v-if="bcolumn.checked" class="dmiux_data-table-headings">
                                        <center>
                                            <span v-if="flags[bcolumn.table_name + '_' + bcolumn.column_name] != null"
                                                class="flag text-lg text-danger"
                                                data-toggle="tooltip"
                                                data-placement="bottom"
                                                :title="flags[bcolumn.table_name + '_' + bcolumn.column_name].assigned_user" >
                                                <span class="fas fa-flag"></span>
                                            </span>

                                            <span v-if="comments[bcolumn.table_name + '_' + bcolumn.column_name] != null"
                                                class="comment text-lg"
                                                data-toggle="tooltip"
                                                data-placement="bottom"
                                                :title="comments[bcolumn.table_name + '_' + bcolumn.column_name][0].comment_text">
                                                <span class="fas fa-comment" style="color: #374C68;"></span>
                                            </span>

                                            <span v-if="mappings[bcolumn.table_name + '_' + bcolumn.column_name] != null"
                                                class="mapping text-lg"
                                                data-toggle="tooltip"
                                                data-placement="bottom"
                                                :title="getColumnMappingTitle(bcolumn.table_name, bcolumn.column_name)">
                                                <span class="fas fa-map-marker-alt" style="color: #7F8FA5;"></span>
                                            </span>

                                            <span v-if="attachments[bcolumn.table_name + '_' + bcolumn.column_name] != null"
                                                class="files text-lg"
                                                data-toggle="tooltip"
                                                data-placement="bottom"
                                                :title="attachments[bcolumn.table_name + '_' + bcolumn.column_name][0].file_name">
                                                <span class="fas fa-file" style="color: #acb0c5;"></span>
                                            </span>

                                            <span v-if="transformations[bcolumn.prefix + '_' + bcolumn.column_name] != null && transformations[bcolumn.prefix + '_' + bcolumn.column_name].length > 0"
                                                class="transformations text-lg"
                                                data-toggle="tooltip"
                                                data-placement="bottom"
                                                :title="transformations[bcolumn.prefix + '_' + bcolumn.column_name].length == 1 ? '1 Transformation Applied' : transformations[bcolumn.prefix + '_' + bcolumn.column_name].length + ' Transformations Applied'">
                                                <span class="fas fa-exchange-alt" style="color: #acb0c5;"></span>
                                            </span>
                                        </center>
                                    </th>
                                    <th v-if="bcolumn.checked" class="fill-circle dmiux_data-table-headings">
                                        <center v-if="(bcolumn.data_type != 'jsonb' || (bcolumn.data_type == 'jsonb' && (transformations[bcolumn.prefix + '_' + bcolumn.column_name] != null && transformations[bcolumn.prefix + '_' + bcolumn.column_name].length != 0)))" class="mb-2 mt-2">
                                            <div class="dmiux_radio d-block">
                                                <input type="radio" @change="changeSelectedColumnValues(bcolumn.alias, bcolumn.data_type, bcolumn.sql_definition, bcolumn.prefix, bcolumn.column_name, index, bcolumn.is_aggregate, false)" v-model="column" :value="bcolumn.prefix + '_' + bcolumn.column_name" class="dmiux_radio__input">
                                                <div class="dmiux_radio__check ml-0"></div>
                                            </div>
                                        </center>
                                    </th>
                                    <th v-if="bcolumn.checked" class="text-right dmiux_data-pivot-table-headings pt-1 pb-1">
                                        <div class="sort_column"
                                            @click="sortData(bcolumn.column_name, bcolumn.prefix, bcolumn.alias, bcolumn.sql_definition)"
                                            data-toggle="tooltip"
                                            data-placement="top"
                                            @mouseover="setHoverCol(bcolumn)"
                                            :title="tooltip_title">
                                            <span :class="(checkWhere(bcolumn.target_column_name)) ? 'dmiux_column-filtered' : ''">
                                                <div class="DataTables_sort_wrapper">
                                                    <template v-if="bcolumn.alias == ''"><template v-if="viewing_type == 'Join' && bcolumn.prefix != 'aggregate'">{{ bcolumn.prefix }}_</template>{{ bcolumn.column_name }}</template><template v-else>{{ bcolumn.alias }}</template>
                                                    <img v-if="bcolumn.prefix + '.' + bcolumn.column_name == $root.explorer.query.order.prefix + '.' + $root.explorer.query.order.order_column" 
                                                        src="<?php echo getenv('DMIUX_URL'); ?>/img/icons/arrow-downsort.svg" 
                                                        :class="$root.explorer.query.order.order_type == 'asc' ? 'flip_sort' : ''"
                                                        class="dmiux_sorticon" />
                                                </div>
                                            </span>
                                        </div>
                                    </th>
                                    <template v-if="bcolumn.checked" v-for="record in records">
                                        <td v-for="(val, key) in record" v-if="key == bcolumn.target_column_name || key == bcolumn.alias">
                                            <template v-if="isJson(val, bcolumn)">
                                                <button class="dmiux_button text-white"
                                                        onclick="toggle_unstructured(this);">
                                                    Show unstructured data
                                                </button>
                                                <unstructured-stage :val="val"
                                                                    :flags="flags"
                                                                    :comments="comments"
                                                                    :mappings="mappings"
                                                                    :attachments="attachments"
                                                                    :prefix="bcolumn.prefix"
                                                                    :sql_definition="bcolumn.sql_definition"
                                                                    :table_name="bcolumn.table_name"
                                                                    :column_name="bcolumn.column_name"
                                                                    :selected_column_index="index">
                                                </unstructured-stage>
                                            </template>
                                            <template v-else-if="bcolumn.data_type == 'boolean'">
                                                <pre v-if="val == true" :class="checkWhere(key) ? 'dmiux_column-filtered' : ''">true</pre>
                                                <pre v-else-if="val == false" :class="checkWhere(key) ? 'dmiux_column-filtered' : ''">false</pre>
                                            </template>
                                            <div v-else :class="isNumberColumn(key) ? 'text-right' : ''">
                                                <pre :class="(checkWhere(key)) ? 'dmiux_column-filtered' : ''">{{ val }}</pre>
                                            </div>
                                        </td>
                                    </template>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    </div>
                <template v-if="records.length > 0">
                    <div class="dmiux_actions__row dmiux_grid-row">
                        <div class="dmiux_actions__col dmiux_grid-col dmiux_grid-col_auto">     
                            <div class="dmiux_actions__row dmiux_grid-row">
                                <div class="dmiux_actions__col dmiux_grid-col dmiux_grid-col_auto"> 
                                    <div class="dmiux_input dataTables_paginate fg-buttonset ui-buttonset fg-buttonset-multi ui-buttonset-multi paging_simple_numbers"
                                         style="float: left; padding-top: 0px; margin-right: 0px;">
                                        <a @click="pageBack()"
                                           class="fg-button ui-button ui-state-default previous"
                                           :class="[$root.explorer.page_num == 1 ? 'ui-state-disabled' : '', '']">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 7 11">
                                                <path fill="currentColor" d="M0 5.5L5.5588235 0 7 1.425926 2.8823529 5.5 7 9.574074 5.5588235 11z"></path>
                                            </svg>
                                            Back
                                        </a>
                                        <input @change="searchPageNum($event)" v-model="$root.explorer.page_num" type="number" class="dmiux_input__input" style="width: 75px;" />
                                        <a @click="pageNext()"
                                           class="fg-button ui-button ui-state-default next"
                                           :class="[$root.explorer.page_num == $root.explorer.page_amt ? 'ui-state-disabled' : '', '']">
                                            Next
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 7 11">
                                                <path fill="currentColor" d="M7 5.5L1.4411765 11 0 9.574074 4.1176471 5.5 0 1.425926 1.4411765 0z"></path>
                                            </svg> 
                                        </a>
                                    </div>
                                </div>
                                <div class="dmiux_actions__col dmiux_grid-col dmiux_grid-col_auto"> 
                                    <div class="dataTables_info">{{ $root.explorer.page_num }} of {{ $root.explorer.page_amt > 0 ? $root.explorer.page_amt.toLocaleString() : $root.explorer.page_amt }} Pages</div>
                                </div>
                            </div>
                        </div>
                        <div class="dmiux_grid-col dmiux_grid-col_md-12">
                            <div class="dmiux_mt100"></div>
                        </div>
                        <div class="dmiux_actions__col dmiux_grid-col dmiux_grid-col_auto">   
                            <div class="dmiux_select">
                                <select class="dmiux_select__select" @change="$root.getRecords()" v-model="$root.explorer.limit">
                                    <option value="10">10 Results</option>
                                    <option value="25">25 Results</option>
                                    <option value="50">50 Results</option>
                                    <option value="100">100 Results</option>
                                </select>
                                <div class="dmiux_select__arrow"></div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <template v-if="$root.ready == true && records.length == 0">
                <div v-if="! $root.table_exists" class="dmiux_grid-col_95 dmiux_grid-col_md-12 dmiux_grid-col_lg-9 px-0 px-lg-2">
                    <div class="alert alert-danger">
                        <strong>Oh no!</strong> It looks like the table or view '{{ $root.explorer.query.table }}' has been removed or renamed.
                    </div>
                </div>

                <div v-else-if="hasNoRecords()" class="dmiux_grid-col_95 dmiux_grid-col_md-12 dmiux_grid-col_lg-9 px-0 px-lg-2">
                    <div class="alert alert-warning">
                        <strong>Aw snap!</strong> Looks like this table is empty.
                    </div>
                </div>
            </template>

            <table-summary ref="table_summary"
                           :control_id="control_id"
                           :records="records"
                           :record_counts="record_counts"
                           :type="type"
                           :active_users="active_users"
                           :table="table"
                           :schema="schema"
                           :filters="filters"
                           :viewing_type="viewing_type"
                           :view_mode="view_mode"
                           :view="view"
                           :publishing_destinations="$root.publishing_destinations"
                           :pending_count="pending_count"
                           :mobile="mobile"
                           :notes="notes">
            </table-summary>
        </div>
    </div>
</script>