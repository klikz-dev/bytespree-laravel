<script type="text/x-template" id="union-modal-template">
    <!-- Union Modal -->
    <div class="dmiux_popup" id="modal-union" role="dialog" tabindex="-1">
        <div class="dmiux_popup__window dmiux_popup__window_lg" role="document">
            <div class="dmiux_popup__head">
                <h4 class="dmiux_popup__title">Manage Unions</h4>
                <button type="button" id="button-close_union_manager" class="dmiux_popup__close" @click="modalClose($event)"></button>
            </div>
            <div class="dmiux_popup__cont">
                <template v-if="unions.length === 0">
                    <div class="alert alert-info mb-0">
                        <p class="mb-1">Unions allow you to combine the results of two or more select statements on views or tables.</p>
                        <p class="mb-1"><strong>Unions are only supported if:</strong></p>
                        <ul class="mb-2">
                            <li>All tables or views have the same number of visible columns</li>
                            <li>All visible columns are in the same order</li>
                            <li>All visible columns are of similar data types</li>
                        </ul>
                        <small><a href="https://app.intercom.com/a/apps/ubbjplne/articles/articles/6932579/show" target="_blank">Read more about unions in Studio</a></small>
                    </div>
                </template>
                <template v-else>
                    <div class="dmiux_grid-row dmiux_mb100" v-for="union in unions">
                        <div class="dmiux_grid-col dmiux_grid-col_10 dmiux_grid-col_sm-8">
                            <div class="dmiux_select">
                                <select :disabled="! union.is_editing" class="dmiux_select__select" v-model="union.schema_table">
                                    <option disabled selected value="">Choose table or view</option>
                                    <option v-for="table in tables" :value="table.table_schema + '.' + table.table_name">{{ table.table_catalog }}.{{ table.table_name }}</option>
                                </select>
                                <div class="dmiux_select__arrow"></div>
                            </div>
                        </div>
                        <div class="dmiux_grid-col dmiux_grid-col_2 dmiux_grid-col_sm-4">
                            <button @click="remove(union)" title="Delete" type="button" class="dmiux_account__button dmiux_account__button_delete preference_buttons"></button>
                            <button v-if="union.is_editing && union.schema_table != ''" @click="save(union)" title="Save" type="button" tabindex="-1" class="dmiux_account__button dmiux_account__button_save preference_buttons"></button>
                            <button v-if="! union.is_editing" @click="edit(union)" title="Edit" type="button" tabindex="-1" class="dmiux_account__button dmiux_account__button_edit preference_buttons"></button>
                        </div>
                    </div>
                    <div class="dmiux_grid-row dmiux_mb100">
                        <div class="dmiux_grid-col dmiux_grid-col_12">
                            <div class="dmiux_checkbox">
                                <input  v-model="union_all" type="checkbox" class="dmiux_checkbox__input">
                                <div class="dmiux_checkbox__check"></div>
                                <div class="dmiux_checkbox__label">Allow duplicates (apply union all)</div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
            <div class="dmiux_popup__foot">
                <div class="dmiux_grid-row">
                    <div class="dmiux_grid-col dmiux_grid-col_auto">
                        <a href="#" @click="add()">+ Add a union</a>                    
                    </div>
                    <div class="dmiux_grid-col"></div>
                    <div class="dmiux_grid-col dmiux_grid-col_auto">
                        <button id="button-cancel_union_manager" class="dmiux_button dmiux_button_secondary" @click="modalClose($event)" type="button">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>
