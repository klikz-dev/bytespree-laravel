<script type="text/x-template" id="compare-template">
    <div class="container-fluid" id="content">
        <div class="dmiux_grid-row" style="margin-top: 20px;">
            <div class="dmiux_grid-col dmiux_grid-col-3">
                <div class="dmiux_select">
                    <label for="input-left_database">Left Database</label>
                    <select class="dmiux_select__select" id="input-left_database" v-model="database_left" @change="get_tables($event, 'left');" required>
                        <option value="">Choose a database</option>
                        <option v-for="database in databases" :value="database.id">{{ database.database }}</option>
                    </select>
                    <div class="dmiux_select__arrow" style="top: 72% !important"></div>
                </div>
            </div>
            <div class="dmiux_grid-col dmiux_grid-col-3">
                <div class="form-group dmiux_select">
                    <label for="input-left_table">Left Table</label>
                    <select class="dmiux_select__select" id="input-left_table" v-model="table_left_selected" @change="enable_right_database">
                        <option value="">Choose a table</option>
                        <option v-for="table in tables_left" :value="table">{{ table.table_schema }}.{{ table.table_name }}</option>
                    </select>
                    <div class="dmiux_select__arrow" style="top: 72% !important"></div>
                </div>
            </div>
            <div class="dmiux_grid-col dmiux_grid-col-3">
                <div class="form-group dmiux_select">
                    <label for="input-right_database">Right Database</label>
                    <select class="dmiux_select__select" id="input-right_database" v-model="database_right" @change="get_tables($event, 'right');" required>
                        <option value="">Choose a database</option>
                        <option v-for="database in databases" :value="database.id">{{ database.database }}</option>
                    </select>
                    <div class="dmiux_select__arrow" style="top: 72% !important"></div>
                </div>
            </div>
            <div class="dmiux_grid-col dmiux_grid-col-3">
                <div class="form-group dmiux_select">
                    <label for="input-right_table">Right Table</label>
                    <select class="dmiux_select__select" v-model="table_right_selected" id="input-right_table" @change="checkToEnableDisplay">
                        <option value="">Choose a table</option>
                        <option v-for="table in tables_right" :value="table">{{ table.table_schema }}.{{ table.table_name }}</option>
                    </select>
                    <div class="dmiux_select__arrow" style="top: 72% !important"></div>
                </div>
            </div>
        </div>
        <div  v-if="!show_table" class="dmiux_grid-row">
            <div class="dmiux_grid-col">
                <button @click="compare()" type="button" class="dmiux_button dmiux_button--primary">Compare&nbsp;&nbsp;<i class="fas fa-eye"></i></button>
            </div>
        </div>
        <div v-else class="dmiux_grid-row">
            <div class="dmiux_grid-col">
                <button @click="compare()" type="button" class="dmiux_button dmiux_button--primary">Compare&nbsp;&nbsp;<i class="fas fa-eye"></i></button>
            </div>
            <div class="dmiux_grid-col dmiux_grid-col_auto mt-2">
                <div class="dmiux_checkbox mr-3">
                    <input id="ignore-position-differences" type="checkbox" v-model="ignore_position_differences" class="dmiux_checkbox__input">
                    <div class="dmiux_checkbox__check"></div>
                    <div class="dmiux_checkbox__label">Ignore Column Position</div>
                </div>
            </div>
            <div class="dmiux_grid-col dmiux_grid-col_auto mt-2">
                <div class="dmiux_checkbox mr-3">
                    <input id="ignore-case-differences" type="checkbox" v-model="ignore_case_differences" class="dmiux_checkbox__input">
                    <div class="dmiux_checkbox__check"></div>
                    <div class="dmiux_checkbox__label">Ignore Column Name Case</div>
                </div>
            </div>
            <div class="dmiux_grid-col dmiux_grid-col_auto mt-2">
                <div class="dmiux_checkbox">
                    <input id="show-col-definitions" type="checkbox" v-model="show_all_col_definitions" class="dmiux_checkbox__input">
                    <div class="dmiux_checkbox__check"></div>
                    <div class="dmiux_checkbox__label">Show Column Definitions</div>
                </div>
            </div>
        </div>
        <div  v-if="show_table" class="dmiux_grid-row mt-4">
            <div class="dmiux_grid-col">
                <h4>Table Comparison</h4>
            </div>
        </div>
        <div v-if="show_table">
            <div class="dmiux_data-table compare-table">
                <div class="dmiux_data-table__cont">
                    <table class="dmiux_data-table__table table-bordered">
                        <thead>
                            <tr>
                                <th v-if="show_all_col_definitions"> 
                                    {{ database_left_name }}.{{ table_left.table_name }}.data_type</th>
                                <th v-if="show_all_col_definitions"> 
                                    {{ database_left_name }}.{{ table_left.table_name }}.length</th>
                                <th v-if="show_all_col_definitions"> 
                                    {{ database_left_name }}.{{ table_left.table_name }}.precision</th>
                                <th> {{ database_left_name }}.{{ table_left.table_name }}</th>
                                <th> {{ database_right_name }}.{{ table_right.table_name }}</th>
                                <th v-if="show_all_col_definitions"> 
                                    {{ database_right_name }}.{{ table_right.table_name }}.precision</th>
                                <th v-if="show_all_col_definitions"> 
                                    {{ database_right_name }}.{{ table_right.table_name }}.length</th>
                                <th v-if="show_all_col_definitions"> 
                                    {{ database_right_name }}.{{ table_right.table_name }}.data_type</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(value, key) in all_columns" :class="all_columns[key]['class']">
                                <td v-if="show_all_col_definitions">
                                    <template v-if="value['left']">
                                        {{ value.left_data_type }}
                                    </template>
                                </td>
                                <td v-if="show_all_col_definitions">
                                    <template v-if="value['left']">
                                        {{ value.left_character_maximum_length }}
                                    </template>
                                </td>
                                <td v-if="show_all_col_definitions">
                                    <template v-if="value['left']">
                                        {{ value.left_numeric_precision}}
                                    </template>
                                </td>
                                <td>
                                    <div v-if="value['left']">
                                        <i v-if="!show_all_col_definitions"
                                           class="tooltip-pretty" 
                                           :title="dbHelper.getColumnDefinition(value.left_data_type, value.left_character_maximum_length, value.left_numeric_precision)" 
                                           :class="dbHelper.getColumnIconClasses(value.left_data_type)"></i>
                                        &nbsp;&nbsp;{{ value.left_column_name }}
                                    </div>
                                    <div class="compare-not-found" v-else>[ Column not found ]</div>
                                </td>
                                <td>
                                    <div v-if="value['right']">
                                        <i v-if="!show_all_col_definitions"
                                           class="tooltip-pretty" 
                                           :title="dbHelper.getColumnDefinition(value.right_data_type, value.right_character_maximum_length, value.right_numeric_precision)" 
                                           :class="dbHelper.getColumnIconClasses(value.right_data_type)"></i>
                                        &nbsp;&nbsp;{{ value.right_column_name }}
                                    </div>
                                    <div class="compare-not-found" v-else>[ Column not found ]</div>
                                </td>
                                <td v-if="show_all_col_definitions">
                                    <template v-if="value['right']">
                                        {{ value.right_numeric_precision }}
                                    </template>
                                </td>
                                <td v-if="show_all_col_definitions">
                                    <template v-if="value['right']">
                                        {{ value.right_character_maximum_length }}
                                    </template>
                                </td>
                                <td v-if="show_all_col_definitions">
                                    <template v-if="value['right']">
                                        {{ value.right_data_type}}
                                    </template>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</script>