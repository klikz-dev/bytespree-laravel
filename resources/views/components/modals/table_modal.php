<script type="text/x-template" id="table-modal-template">
    <!-- Custom Columns Modal -->
    <div class="dmiux_popup" id="modal-table_controls" role="dialog" tabindex="-1">
        <div class="dmiux_popup__window dmiux_popup__window_xl" role="document">
            <div class="dmiux_popup__head">
                <h4 class="dmiux_popup__title">Manage Columns</h4>
                <button id="x-button" type="button" class="dmiux_popup__close"></button>
            </div>
            <div class="dmiux_popup__cont pt-0 column-preferences">
                <div v-if="$root.explorer.query.columns.length > 400" class="dmiux_grid-col dmiux_grid-col_12">
                    <div class="alert alert-warning">
                        Bytespree Studio is only able to display 400 columns, but this table has {{ $root.explorer.query.columns.length }}.
                    </div>
                </div>
                <div class="dmiux_grid-row column-search">
                    <div class="dmiux_grid-col">
                        <div class="dmiux_input mb-2">
                            <input placeholder="Enter column name to search" class="dmiux_input__input" @input="searchColumns($event)" v-model="column_search">
                            <div class="dmiux_input__icon">
                                <svg height="16" viewbox="0 0 16 16" width="16" xmlns="http://www.w3.org/2000/svg">
                                <path d="M265.7,19.2298137 C266.6,18.0372671 267.1,16.6459627 267.1,15.0559006 C267.1,11.1801242 264,8 260.1,8 C256.2,8 253,11.1801242 253,15.0559006 C253,18.931677 256.2,22.1118012 260.1,22.1118012 C261.7,22.1118012 263.2,21.6149068 264.3,20.7204969 L267.3,23.7018634 C267.5,23.9006211 267.8,24 268,24 C268.2,24 268.5,23.9006211 268.7,23.7018634 C269.1,23.3043478 269.1,22.7080745 268.7,22.310559 L265.7,19.2298137 Z M260.05,20.1 C257.277451,20.1 255,17.9 255,15.1 C255,12.3 257.277451,10 260.05,10 C262.822549,10 265.1,12.3 265.1,15.1 C265.1,17.9 262.822549,20.1 260.05,20.1 Z" fill="currentColor" transform="translate(-253 -8)"></path></svg>
                            </div>
                        </div>
                    </div>
                </div>
                <draggable @end="moved" ref="draggable" v-model="active_columns" :preventOnFilter="false" :filter="'.dmiux_input__input'" class="columns-container" :disabled="column_search != ''">
                    <div v-for="(column, index) in active_columns" :id="column.uuid + '_id'" :key="index" class="dmiux_grid-row preference_row mr-2 ml-2">
                        <div class="dmiux_grid-col dmiux_grid-col_1">
                            <div :title="countTitle(column.checked)" class="dmiux_checkbox">
                                <input type="checkbox" :disabled="column.checked == false && visible_column_count >= 400" :class="column.checked == false && visible_column_count >= 400 ? 'cursor-d' : ''" v-model="column.checked" class="dmiux_checkbox__input" name="custom_boxes">
                                <div class="checkbox-center dmiux_checkbox__check"></div>    
                            </div>
                        </div>
                        <div v-if="column.added == false" class="dmiux_grid-col dmiux_grid-col_9 mt-2 mb-1">
                            <span v-if="column.editing == false"><template v-if="$root.$refs.records.viewing_type == 'Join' && column.prefix != 'aggregate'">{{ column.prefix }}.</template>{{ column.column_name }}<template v-if="column.alias != ''"> as {{ column.alias }}</template><template v-else-if="$root.$refs.records.viewing_type == 'Join'"> as {{ column.target_column_name }}</template></span>
                            <input v-else class="dmiux_input__input" :ref="'column_input_' + index" v-model:value="column.alias" placeholder="Alias" />
                        </div>
                        <div v-else class="dmiux_grid-col dmiux_grid-col_9 mt-2 mb-1">
                            <span v-if="column.editing == false"><prism-editor readonly class="code_editor" :highlight="highlighter" v-model="customColumnText(column)"></prism-editor></span>
                            <div v-else class="dmiux_grid-row mr-1">
                                <input class="dmiux_grid-col dmiux_input__input dmiux_grid-col_25" maxlength="63" :ref="'column_input_' + index" v-model:value="column.column_name" placeholder="Name" />
                                <div class="dmiux_grid-col dmiux_input__input dmiux_grid-col_6 ml-1">
                                    <prism-editor class="code_editor" placeholder="SQL Definition" :highlight="highlighter" v-model="column.sql_definition"></prism-editor>
                                </div>
                                <div class="dmiux_grid-col dmiux_grid-col_3 ml-1 dmiux_checkbox mt-2">
                                    <input type="checkbox" v-model="column.is_aggregate" class="dmiux_checkbox__input">
                                    <div class="dmiux_checkbox__check"></div>
                                    <div class="dmiux_checkbox__label">Is Aggregate Field</div>
                                </div>
                            </div>
                        </div>
                        <div class="dmiux_grid-col dmiux_grid-col_2 mt-2">
                            <button v-if="column.added == true" title="Delete" @click="deleteCustom(index)" type="button" class="dmiux_account__button dmiux_account__button_delete preference_buttons"></button>
                            <button v-if="(column.editing != false && column.editing != undefined) && column.new_column != true" title="Cancel" @click="cancel(index)" type="button" class="dmiux_account__button dmiux_account__button_remove preference_buttons"></button>
                            <button v-if="column.editing == undefined || column.editing == false" title="Edit" @click="edit(index)" type="button" tabindex="-1" class="dmiux_account__button dmiux_account__button_edit preference_buttons"></button>
                            <button v-else @click="save(index)" title="Save" type="button" tabindex="-1" class="dmiux_account__button dmiux_account__button_save preference_buttons"></button>
                            <button v-if="column_search == ''" class="dmiux_account__button dmiux_account__button_move preference_buttons" title="Drag & Drop"></button>
                        </div>
                    </div>
                </draggable>
            </div>
            <div class="dmiux_popup__foot">
                <div class="dmiux_grid-row">
                    <div class="dmiux_grid-col dmiux_grid-col_auto">
                        <button id="button-add_a_column"
                                type="button"
                                class="dmiux_button d-inline-block" 
                                @click="addCustomColumn()">Add a Column&nbsp; <span class="fas fa-plus"></i></span>
                        </button>
                        <button id="button-move_selected_to_top"
                                type="button"
                                :disabled="!isChecked()"
                                class="dmiux_button dmiux_button_secondary d-inline-block"
                                @click="groupSelected()">Move All Visible To Top
                        </button>
                        <div class="dmiux_main-nav__item d-inline-block">  
                            <button id="button-select_columns" class="dmiux_button dmiux_button_secondary">
                                Select Columns&nbsp; <i class="fas fa-angle-down"></i>
                            </button>
                            <div id="dropdown-select_columns" class="dmiux_main-nav__dropdown">
                                <template>
                                    <a class="dmiux_main-nav__sublink" @click="uncheckAll()" href="javascript:void(0)">Select none</a>
                                    <a class="dmiux_main-nav__sublink" @click="checkAll()" href="javascript:void(0)">Select all</a>
                                </template>
                                <template v-if="Array.isArray(joins) && joins.length > 0">
                                    <div class="dmiux_main-nav__hr"></div>
                                    <a class="dmiux_main-nav__sublink" @click="uncheckAll(prefix)" href="javascript:void(0)">Select none with prefix <span class="prefix-none" :title="'Select none with prefix ' + prefix">{{ prefix }}</span></a>
                                    <a class="dmiux_main-nav__sublink" @click="checkAll(prefix)" href="javascript:void(0)">Select all with prefix <span class="prefix-all" :title="'Select all with prefix ' + prefix">{{ prefix }}</span></a>
                                </template>
                                <template v-if="Array.isArray(joins)" v-for="(join, index) in joins">
                                    <div class="dmiux_main-nav__hr"></div>
                                    <a class="dmiux_main-nav__sublink" @click="uncheckAll(join.prefix)" href="javascript:void(0)">Select none with prefix <span class="prefix-none" :title="'Select none with prefix ' + join.prefix">{{ join.prefix }}</span></a>
                                    <a class="dmiux_main-nav__sublink" @click="checkAll(join.prefix)" href="javascript:void(0)">Select all with prefix <span class="prefix-all" :title="'Select all with prefix ' + join.prefix">{{ join.prefix }}</span></a>
                                </template>
                            </div>
                        </div>
                    </div>
                    <div class="dmiux_grid-col"></div>
                    <div class="dmiux_grid-col dmiux_grid-col_auto">
                        <button id="cancel-button-table-modal" class="dmiux_button dmiux_button_secondary dmiux_popup__close_popup dmiux_popup__cancel d-inline-block" type="button">
                            Cancel
                        </button>
                        <button v-if="column_preferences_changed" id="button-reset_preferences" class="dmiux_button dmiux_button_secondary d-inline-block" @click="resetPreferences()" type="button">
                            Reset Preferences
                        </button>
                        <button id="button-apply_preferences" class="dmiux_button d-inline-block" @click="changeColumnPreferences()" type="button">
                            Apply Preferences
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>