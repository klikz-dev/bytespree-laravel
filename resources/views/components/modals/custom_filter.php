<script type="text/x-template" id="filter-modal-template">
    <!-- Custom Filter Modal -->
    <div class="dmiux_popup" id="modal-custom_filter" ref="filter_modal" role="dialog" tabindex="-1">
        <div class="dmiux_popup__window dmiux_popup__window_lg" role="document">
            <div class="dmiux_popup__head">
                <h4 class="dmiux_popup__title"><span v-if="edit_mode == true">Edit </span>Custom Filter for <mark v-if="$root.explorer.selected_alias == ''"><template v-if="viewing_type=='Join'">{{ selected_prefix }}_</template>{{ selected_column }}</mark><mark v-else>{{ $root.explorer.selected_alias }}</mark></h4>
                <button type="button" id="x-button" class="dmiux_popup__close" @click="modalCloseCustom"></button>
            </div>
            <form id="form-custom_filter" autocomplete="off" onSubmit="event.preventDefault()">
                <div class="dmiux_popup__cont">
                    <label for="ddl-search_type">Where <strong v-if="$root.explorer.selected_alias == ''"><template v-if="viewing_type=='Join'">{{ selected_prefix }}_</template>{{ selected_column }}</strong><strong v-else>{{ $root.explorer.selected_alias }}</strong></label>
                        <div class="dmiux_select mb-2">
                            <select id="ddl-search_type" class="dmiux_select__select" @change="setOperationChange()" v-model:value="operator"> {{ operator }}
                                <option v-for="op in operators" v-if="!op.disabled" :value="op.operator">{{ op.name }}</option>
                            </select>
                            <div class="dmiux_select__arrow"></div>
                        </div>

                        <!-- Beginning of multiple values -->
                        <div v-if="operator == 'in' || operator == 'not in'" class="form-group dmiux_input">
                            <div class="dmiux_grid-row mb-2">
                                <div class="dmiux_grid-col dmiux_grid-col_6 dmiux_radio"> 
                                    <input v-model="in_type" value="string" type="radio" class="dmiux_radio__input">
                                    <div class="dmiux_radio__check"></div>
                                    <div class="dmiux_radio__label">Manual Input</div>
                                </div>
                                <div class="dmiux_grid-col dmiux_grid-col_6 dmiux_radio">
                                    <input v-model="in_type" value="column" type="radio" class="dmiux_radio__input">
                                    <div class="dmiux_radio__check"></div>
                                    <div class="dmiux_radio__label">Choose a Column</div>
                                </div>
                            </div>
                            <div v-if="in_type == 'string'">
                                <div class="dmiux_grid-row">
                                    <div class="dmiux_grid-col dmiux_grid-col_10 dmiux_input mt-1">
                                        <input type="text"
                                            placeholder="Enter a value"
                                            v-model="value"
                                            v-on:keyup.enter="addIn()"
                                            id="input-custom_filter_value"
                                            class="dmiux_input__input" />
                                    </div>
                                    <div class="dmiux_grid-col dmiux_grid-col_auto mt-1">
                                        <button class="dmiux_button mt-0" type="button" @click="addIn();">Add</button>
                                    </div>
                                </div>
                                <div class="dmiux_grid-row mt-3">
                                    <div v-if="in_array.length > 0" class="dmiux_grid-col dmiux_grid-col_10">
                                        <div class="dmiux_query-flags dmiux_query-flags_included">
                                            <button type="button"
                                                    @click="clearIn()" 
                                                    class="dmiux_query-flags__remove-all dmiux_clear-all"
                                                    :class="in_array.length === 0 ? 'dmiux_removed' : ''">Remove All<i class="dmiux_clear-all__icon dmiux_clear-all__icon_remove"></i></button>
                                            <div class="dmiux_query-flags__title">Values</div>
                                            <div class="dmiux_grid-row dmiux_query-flags__overflow dmiux_scrollbar white-bg mx-0 my-2 auto-height">
                                                <div v-for="(item, idx2) in in_array" id="idx2" class="dmiux_flagbox dmiux_flagbox_included mr-4 mb-2">
                                                    <button type="button" @click="removeValue(idx2)" data-tooltip-pos="bottom" data-tooltip-content="<div>Remove</div>" class="dmiux_query-summary__clear"></button>
                                                    <div class="mono-font dmiux_flagbox__label ml-4">{{ item }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else>
                                <div class="dmiux_grid-row">
                                    <div class="dmiux_grid-col dmiux_grid-col_6">
                                        <label for="filter_table">Choose a Table</label>
                                        <div class="dmiux_select">
                                            <select id="filter_table" class="dmiux_select__select" @change="getInColumns()" v-model:value="in_column.schema_table">
                                                <option v-for="table in tables" :value="table.table_schema + '.' + table.table_name">{{ table.table_catalog }}.{{ table.table_name }}</option>
                                            </select>
                                            <div class="dmiux_select__arrow"></div>
                                        </div>
                                    </div>

                                    <div class="dmiux_grid-col dmiux_grid-col_6">
                                        <label for="filter_column">Choose a Column</label>
                                        <div class="dmiux_select">
                                            <select id="filter_column" :disabled="columns.length == 0" class="dmiux_select__select" v-model:value="in_column.column">
                                                <option v-for="column in columns" :value="column.column_name">{{ column.column_name}}</option>
                                            </select>
                                            <div class="dmiux_select__arrow"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- End of multiple values -->

                        <!-- Beginning of "between 2 values"(used only for date, datetime-local, and number) -->
                        <div v-else-if="operator == 'between'" class="form-group dmiux_input">
                            <template v-if="selected_column_data_type == 'timestamp without time zone' || selected_column_data_type == 'date'">
                                <div class="dmiux_grid-row mb-2">
                                    <div class="dmiux_grid-col"></div>
                                    <div class="dmiux_grid-col dmiux_grid-col_3 dmiux_radio"> 
                                        <input v-model="date_type" value="manual" type="radio" class="dmiux_radio__input">
                                        <div class="dmiux_radio__check"></div>
                                        <div class="dmiux_radio__label">Exact Date</div>
                                    </div>
                                    <div class="dmiux_grid-col dmiux_grid-col_6 dmiux_radio">
                                        <input v-model="date_type" value="interval" type="radio" class="dmiux_radio__input">
                                        <div class="dmiux_radio__check"></div>
                                        <div class="dmiux_radio__label">Date Interval</div>
                                    </div>
                                </div>
                                <div class="dmiux_grid-row">
                                    <div class="dmiux_grid-col dmiux_grid-col_6">
                                        <label for="input-custom_filter_value_low">From</label>
                                        <template v-if="date_type == 'manual'">
                                            <input :type="selected_column_data_type == 'date' ? 'date' : 'datetime-local'" 
                                                @input="cleanupDate('low')"
                                                id="input-custom_filter_value_low" 
                                                v-model:low_value="low_value"
                                                class="dmiux_input__input" />
                                        </template>
                                        <template v-else>
                                            <div class="dmiux_grid-row">
                                                <div class="dmiux_grid-col dmiux_grid-col_12 pb-1">
                                                    <input :placeholder="'Number of ' + date_interval.low.type" id="ddl-interval_time" v-model="date_interval.low.time" type="number" class="dmiux_input__input">
                                                </div>
                                            </div>
                                            <div class="dmiux_grid-row">
                                                <div class="dmiux_grid-col dmiux_grid-col_6">
                                                    <div class="dmiux_select">
                                                        <select id="ddl-interval_type" class="dmiux_select__select" v-model:value="date_interval.low.type">
                                                            <option value="minutes">Minutes</option>
                                                            <option value="hours">Hours</option>
                                                            <option value="days">Days</option>
                                                            <option value="months">Months</option>
                                                            <option value="years">Years</option>
                                                        </select>
                                                        <div class="dmiux_select__arrow"></div>
                                                    </div>
                                                </div>
                                                <div class="dmiux_grid-col dmiux_grid-col_6">
                                                    <div class="dmiux_select">
                                                        <select id="ddl-interval_direction" class="dmiux_select__select" v-model:value="date_interval.low.direction">
                                                            <option value="+">from now</option>
                                                            <option value="-">ago</option>
                                                        </select>
                                                        <div class="dmiux_select__arrow"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                    <div class="dmiux_grid-col dmiux_grid-col_6"> 
                                        <label for="input-custom_filter_value_high">To</label>
                                        <template v-if="date_type == 'manual'">
                                            <input :type="selected_column_data_type == 'date' ? 'date' : 'datetime-local'" 
                                                @input="cleanupDate('high')"
                                                id="input-custom_filter_value_high" 
                                                v-model:high_value="high_value"
                                                class="dmiux_input__input" />
                                        </template>
                                        <template v-else>
                                            <div class="dmiux_grid-row">
                                                <div class="dmiux_grid-col dmiux_grid-col_12 pb-1">
                                                    <input :placeholder="'Number of ' + date_interval.high.type" id="ddl-interval_time" v-model="date_interval.high.time" type="number" class="dmiux_input__input">
                                                </div>
                                            </div>
                                            <div class="dmiux_grid-row">
                                                <div class="dmiux_grid-col dmiux_grid-col_6">
                                                    <div class="dmiux_select">
                                                        <select id="ddl-interval_type" class="dmiux_select__select" v-model:value="date_interval.high.type">
                                                            <option value="minutes">Minutes</option>
                                                            <option value="hours">Hours</option>
                                                            <option value="days">Days</option>
                                                            <option value="months">Months</option>
                                                            <option value="years">Years</option>
                                                        </select>
                                                        <div class="dmiux_select__arrow"></div>
                                                    </div>
                                                </div>
                                                <div class="dmiux_grid-col dmiux_grid-col_6">
                                                    <div class="dmiux_select">
                                                        <select id="ddl-interval_direction" class="dmiux_select__select" v-model:value="date_interval.high.direction">
                                                            <option value="+">from now</option>
                                                            <option value="-">ago</option>
                                                        </select>
                                                        <div class="dmiux_select__arrow"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                            <template v-else>
                                <div class="dmiux_grid-row">
                                    <div class="dmiux_grid-col dmiux_grid-col_6">
                                        <label for="input-custom_filter_value_low">Low Value</label>
                                        <input type="number" 
                                            @input="cleanupNumber('low')"
                                            id="input-custom_filter_value_low" 
                                            class="dmiux_input__input" 
                                            v-model:low_value="low_value" />
                                    </div>
                                    <div class="dmiux_grid-col dmiux_grid-col_6"> 
                                        <label for="input-custom_filter_value_low">High Value</label>
                                        <input type="number" 
                                            @input="cleanupNumber('high')"
                                            id="input-custom_filter_value_high" 
                                            class="dmiux_input__input" 
                                            v-model:high_value="high_value" />
                                    </div>
                                </div>
                            </template>
                        </div>
                        <!-- End of "between 2 values" -->

                        <!-- no value -->
                        <div v-else-if="operator == 'empty' || operator =='not empty'"></div>
                        <!-- End of "no value" -->

                        <!-- Single Value -->
                        <div v-else>
                            <div v-if="selected_column_data_type == 'boolean'" class="form-group dmiux_input">
                                <div class="dmiux_grid-row">
                                    <div class="dmiux_grid-col dmiux_grid-col_12">
                                        <select v-model="value" id="input-custom_filter_value" class="dmiux_select__select">
                                            <option value="true">True</option>
                                            <option value="false">False</option>
                                        </select>
                                        <div class="dmiux_select__arrow"></div>
                                    </div>
                                </div>
                            </div>
                            <div v-else-if="selected_column_data_type == 'date' || selected_column_data_type == 'timestamp without time zone'">
                                <div class="dmiux_grid-row mb-2">
                                    <div class="dmiux_grid-col"></div>
                                    <div class="dmiux_grid-col dmiux_grid-col_3 dmiux_radio"> 
                                        <input v-model="date_type" value="manual" type="radio" class="dmiux_radio__input">
                                        <div class="dmiux_radio__check"></div>
                                        <div class="dmiux_radio__label">Exact Date</div>
                                    </div>
                                    <div class="dmiux_grid-col dmiux_grid-col_6 dmiux_radio">
                                        <input v-model="date_type" value="interval" type="radio" class="dmiux_radio__input">
                                        <div class="dmiux_radio__check"></div>
                                        <div class="dmiux_radio__label">Date Interval</div>
                                    </div>
                                </div>
                                <template v-if="date_type == 'manual'">
                                    <input :type="selected_column_data_type == 'date' ? 'date' : 'datetime-local'" 
                                        @input="cleanupDate('single')"
                                        id="input-custom_filter_value" 
                                        v-model:value="value"
                                        class="dmiux_input__input" />
                                </template>
                                <template v-else>
                                    <div class="dmiux_grid-row">
                                        <div class="dmiux_grid-col dmiux_grid-col_4">
                                            <input :placeholder="'Number of ' + date_interval.type" id="ddl-interval_time" v-model="date_interval.time" type="number" class="dmiux_input__input">
                                        </div>
                                        <div class="dmiux_grid-col dmiux_grid-col_4">
                                            <div class="dmiux_select">
                                                <select id="ddl-interval_type" class="dmiux_select__select" v-model:value="date_interval.type">
                                                    <option value="minutes">Minutes</option>
                                                    <option value="hours">Hours</option>
                                                    <option value="days">Days</option>
                                                    <option value="months">Months</option>
                                                    <option value="years">Years</option>
                                                </select>
                                                <div class="dmiux_select__arrow"></div>
                                            </div>
                                        </div>
                                        <div class="dmiux_grid-col dmiux_grid-col_4">
                                            <div class="dmiux_select">
                                                <select id="ddl-interval_direction" class="dmiux_select__select" v-model:value="date_interval.direction">
                                                    <option value="+">from now</option>
                                                    <option value="-">ago</option>
                                                </select>
                                                <div class="dmiux_select__arrow"></div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <input v-else-if="selected_column_data_type == 'integer' || selected_column_data_type == 'bigint' || selected_column_data_type == 'numeric' || selected_column_data_type == 'decimal' || selected_column_data_type == 'float'" 
                                type="number" 
                                @input="cleanupNumber('single')"
                                id="input-custom_filter_value" 
                                class="dmiux_input__input" 
                                v-model:value="value" />
                            <input v-else 
                                type="text"
                                placeholder="Enter a value"
                                id="input-custom_filter_value"
                                class="dmiux_input__input"
                                v-model:value="value">
                        </div>
                        <!-- End of Single Value -->
                </div>
                <div class="dmiux_popup__foot">
                    <div class="dmiux_grid-row">
                        <div class="dmiux_grid-col"></div>
                        <div class="dmiux_grid-col dmiux_grid-col_auto">
                            <button id="cancel-button-custom-filter" class="dmiux_button dmiux_button_secondary dmiux_popup__close_popup dmiux_popup__cancel" @click="modalCloseCustom" type="button">Cancel</button>
                        </div>
                        <div class="dmiux_grid-col dmiux_grid-col_auto pl-0">
                            <button class="dmiux_button" type="button" @click="addFilter();">Filter</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</script>
