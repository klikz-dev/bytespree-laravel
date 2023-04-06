<script type="text/x-template" id="join-modal-template">
    <!-- Joins Modal -->
    <div class="dmiux_popup" id="modal-join" role="dialog" tabindex="-1">
        <div class="dmiux_popup__window dmiux_popup__window_lg" role="document">
            <div class="dmiux_popup__head">
                <h4 class="dmiux_popup__title">Manage Joins</h4>
                <button type="button" id="button-close_join_manager" class="dmiux_popup__close" @click="modalCloseJoin($event)"></button>
            </div>
            <div class="dmiux_popup__cont">
                <div class="dmiux_grid-row">
                    <div class="dmiux_grid-col dmiux_grid-col_12">
                        <label for="input-alias_this_table">Alias for current table</label>
                    </div>
                </div>
                <div class="dmiux_grid-row"> 
                    <div class="dmiux_grid-col dmiux_grid-col_8">
                        <div class="dmiux_input mt-2">
                            <input id="input-alias_this_table" type="text" v-model="prefix" class="dmiux_input__input" :disabled="edit_prefix == false">
                         </div>
                    </div>
                    <div v-if="edit_prefix == false" class="dmiux_grid-col dmiux_grid-col_4 pl-1">
                        <button class="dmiux_button dmiux_button_secondary mt-2" @click="editPrefix()" type="button">Change Alias</button>
                    </div>
                    <div v-if="edit_prefix == true" class="dmiux_grid-col dmiux_grid-col_4">
                        <div class="dmiux_grid-row">
                            <div class="dmiux_grid-col dmiux_grid-col_auto pl-1">
                                <button class="dmiux_button dmiux_button_secondary mt-2" @click="cancelPrefixChange()" type="button">Cancel</button>
                            </div>
                            <div class="dmiux_grid-col dmiux_grid-col_auto pl-0">
                                <button v-if="prefix_changed" class="dmiux_button mt-2" @click="applyPrefixChange()" type="button">Apply</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div @scroll="hideShowArrows()"
                     class="row flex-row flex-nowrap join-arrow-div"
                     id="joins_list">
                    <button type="button" class="dmiux_data-table__arrow dmiux_data-table__arrow_left join_left" onclick="scroll_left('joins_list')"><i></i></button>
                    <button type="button" class="dmiux_data-table__arrow dmiux_data-table__arrow_right join_right" onclick="scroll_right('joins_list')"><i></i></button>
                    <div v-for="(join, index) in joins" class="card-group ml-2 mt-2" :id="'join_' + index">
                        <div class="card join-card" >
                            <div class="card-header join-card-hdr">
                                <div class="dmiux_grid-row">
                                    <div class="dmiux_grid-col_7 dmiux_select join-hdr-select">
                                        <select @change="getSelectedTableColumns(index)" 
                                                class="dmiux_select__select dmiux_checkbox__label px-1" 
                                                v-model="join.schema_table"
                                                :disabled="join.editing == false"
                                                :title="join.schema_table">
                                            <option disabled selected value="">Choose table or view</option>
                                            <option v-for="table in tables" :value="table.table_schema + '.' + table.table_name">{{ table.table_catalog }}.{{ table.table_name }}</option>
                                        </select>
                                        <div class="dmiux_select__arrow"></div>
                                    </div>
                                    <div class="dmiux_grid-col_1 text-center px-1"><em> as </em></div>
                                    <div class="dmiux_grid-col_25">
                                        <input v-model="join.prefix" type="text" class="dmiux_input__input dmiux_checkbox__label px-1" placeholder="alias" :disabled="join.editing == false" :title="join.prefix">
                                    </div>
                                    <div class="dmiux_grid-col_15">
                                        <button v-if="join.editing == false" title="Edit join" type="button" tabindex="-1" class="dmiux_account__button dmiux_account__button_edit transformation_buttons transformation_button_save" @click="editJoin(index)"></button>
                                        <button v-else title="Save join" type="button" tabindex="-1" class="dmiux_account__button dmiux_account__button_save transformation_buttons transformation_button_save" @click="manageJoin(index, 'save')"></button>
                                        <button title="Delete join" type="button" tabindex="-1" class="dmiux_account__button dmiux_account__button_remove transformation_buttons transformation_button_remove" @click="manageJoin(index, 'delete')"></button>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="dmiux_grid-row">
                                    <div class="dmiux_grid-col_8">
                                        <h6><label :for="'select-join_on'+index">Join on</label></h6>
                                    </div>
                                </div>
                                <div class="dmiux_grid-row">
                                    <div class="dmiux_grid-col_55">
                                        <div class="dmiux_select">
                                            <select :id="'select-join_on'+index" class="dmiux_select__select dmiux_checkbox__label" v-model="join.source_target_column" @change="setSourceColumnAndPrefix($event, index)" :disabled="join.editing == false">
                                                <option selected disabled value="">Source column</option>
                                                <template v-for="column in join.source_columns" v-if="column.is_aggregate != true && column.prefix != 'aggregate'">
                                                    <option v-if="column.prefix == 'custom'" :data-prefix="column.prefix" :data-column_name="column.column_name" :value="column.old_sql_definition ?? column.sql_definition">{{ column.column_name }}</option>
                                                    <option v-else :data-prefix="column.prefix" :data-column_name="column.column_name" :value="column.prefix + '_' + column.column_name"><span v-if="$root.explorer.viewing_type == 'Join'">{{ column.prefix }}_</span>{{ column.column_name }}</option>
                                                </template>
                                            </select>
                                            <div class="dmiux_select__arrow"></div>
                                        </div>
                                    </div>
                                    <div class="dmiux_grid-col-1 text-center"> &nbsp;<em>=</em>&nbsp; </div>
                                    <div class="dmiux_grid-col_6 pr-3">
                                        <div class="dmiux_select">
                                            <select class="dmiux_select__select dmiux_checkbox__label" v-model="join.target_column" :disabled="join.editing == false">
                                                <option selected disabled value="">Target column</option>
                                                <option v-for="target_column in join.target_columns" :value="target_column.column_name">{{ target_column.column_name }}</option>
                                            </select>
                                            <div class="dmiux_select__arrow"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="dmiux_grid-row">
                                    <div class="dmiux_grid-col_7 mt-4">
                                        <h6><label :for="'select-join_type'+index">Join type</label></h6>
                                    </div>
                                </div>
                                <div class="dmiux_grid-row mr-2">
                                    <div class="dmiux_grid-col_12">
                                        <div class="dmiux_select">
                                            <select :id="'select-join_type'+index" class="dmiux_select__select dmiux_checkbox__label" v-model:value="join.join_type" :disabled="join.editing == false">
                                                <option selected value="INNER">Inner Join</option>
                                                <option value="LEFT">Left Join</option>
                                                <option value="RIGHT">Right Join</option>
                                            </select>
                                            <div class="dmiux_select__arrow"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="dmiux_grid-row">
                                    <div class="dmiux_grid-col_3 my-3">
                                        <div class="dmiux_checkbox">
                                            <input v-model="join.cast" type="checkbox" class="dmiux_checkbox__input" :disabled="join.editing == false"> 
                                            <div class="dmiux_checkbox__check"></div>
                                            <div class="dmiux_checkbox__label">Cast?</div>
                                        </div>
                                    </div>
                                    <div v-if="join.cast == true" class="dmiux_grid-col_85 mt-4 ml-1">
                                        <div class="dmiux_select">
                                            <select class="dmiux_select__select dmiux_checkbox__label" v-model:value="join.cast_type" :disabled="join.editing == false">
                                                <option disabled value="">Cast each column as</option>
                                                <option value="text">TEXT</option>
                                                <option value="varchar">VARCHAR</option>
                                                <option value="bigint">INTEGER</option>
                                                <option value="numeric">NUMERIC</option>
                                                <option value="date">DATE</option>
                                                <option value="timestamp">TIMESTAMP</option>
                                            </select>
                                            <div class="dmiux_select__arrow"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="dmiux_popup__foot">
                <div class="dmiux_grid-row">
                    <div class="dmiux_grid-col dmiux_grid-col_auto">
                        <a href="#" @click="addJoin()">+ Add a join</a>                    
                    </div>
                    <div class="dmiux_grid-col"></div>
                    <div class="dmiux_grid-col dmiux_grid-col_auto">
                        <button id="button-cancel_join_manager" class="dmiux_button dmiux_button_secondary" @click="modalCloseJoin($event)" type="button">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>
