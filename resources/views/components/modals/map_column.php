<script type="text/x-template" id="map-modal-template">
    <!-- Map Column Modal -->
    <div class="dmiux_popup" id="modal-map_column" ref="map_modal" role="dialog" tabindex="-1">
        <div class="dmiux_popup__window dmiux_popup__window_lg" id="map-modal-width" role="document">
            <div class="dmiux_popup__head">
                <h4 class="dmiux_popup__title">Add Mapping for <mark v-if="$root.explorer.selected_alias == ''">{{ selected_column }}</mark><mark v-else>{{ $root.explorer.selected_alias }}</mark></h4>
                <button type="button" class="dmiux_popup__close"></button>
            </div>
            <div class="dmiux_popup__cont dmiux_popup_cont_nav">
                <div class="dmiux_popup__tabs dmiux_noscrollbar">
                    <a @click="changeModule(-1)"
                       :class="mapping_module_id == -1 ? 'active' : ''"
                       href="#">Table &amp; Column
                    </a>
                    <template v-for="module in modules">
                        <a @click="changeModule(module.id)"
                           :class="mapping_module_id == module.id ? 'active' : ''"
                           href="#">{{ module.name }}
                        </a>
                    </template>
                    <a @click="changeModule(0)"
                       :class="mapping_module_id == 0 ? 'active' : ''"
                       href="#">All Mappings
                    </a>
                </div>
                <div class="dmiux_popup__tabs" v-if="mapping.id" style="background-color: #F4A405; color: white; padding-left: 5px;">
                    You are editing an existing mapping to {{ mapping.module_name }}
                </div>

                <div v-if="mapping_module_id == -1">
                    <div class="form-group">
                        <label for="input-mapping_destination_table">Table</label>
                        <v-select @input="getDestinationTableColumns()"
                                  id="input-mapping_destination_table"
                                  :options="destination_tables"
                                  v-model="mapping.destination_table_name">
                        </v-select>
                    </div>
                    <div class="form-group">
                        <label for="input-mapping_destination_column">Column</label>
                        <v-select id="input-mapping_destination_column"
                                  :options="destination_columns"
                                  :reduce="c => c.value"
                                  v-model="mapping.destination_column_name">
                        </v-select>
                    </div>
                </div>
                
                <div v-for="module in modules"
                     v-if="mapping_module_id == module.id">
                    <div class="form-group" v-for="field in module.fields">
                        <label :for="'input-mapping_' + module.id + '_' + field.id">{{ field.name }}</label>
                        <div v-if="field.type == 'select'">
                            <v-select :id="'input-mapping_' + module.id + '_' + field.id"
                                      :options="field.options"
                                      :reduce="c => c.value"
                                      v-model="mapping.module_fields[field.id]">
                            </v-select>
                        </div>
                        <div class="dmiux_input" v-if="field.type == 'text'">
                            <input type="text"
                                   class="dmiux_input__input"
                                   v-model="mapping.module_fields[field.id]" />
                        </div>
                    </div>
                </div>

                <div v-if="mapping_module_id == 0"
                     @scroll="hideShowArrows()"
                     class="row flex-row flex-nowrap"
                     style="overflow-x: scroll; max-width: 770px; margin-left: 0px; margin-right: 0px;"
                     id="all_mappings">
                     <div v-if="columnMappings.length == 0" style="width: 100%;" class="alert alert-info" role="alert">
                    This column has no mappings yet.
                    </div>
                    <button style="z-index: 110" type="button" class="dmiux_data-table__arrow dmiux_data-table__arrow_left mapping_left" onclick="scroll_left('all_mappings', 'mapping_')"><i></i></button>
                    <button style="z-index: 110" type="button" class="dmiux_data-table__arrow dmiux_data-table__arrow_right mapping_right" onclick="scroll_right('all_mappings', 'mapping_')"><i></i></button>
                    <div class="card col-6 mapping-card" v-for="mapping in columnMappings">
                        <div class="card-header mapping-card-header">
                            <span v-if="mapping.module == null">Table to Column</span>
                            <span v-else>{{ mapping.module.name }}</span>
                            <span style="cursor: pointer;" class="fas fa-trash float-right" data-toggle="tooltip" title="Delete" @click="deleteMapping(mapping.id)"></span>
                            <span class="fas fa-edit float-right mr-2" style="cursor: pointer;" data-toggle="tooltip" title="Update" @click="editMapping(mapping)"></span>
                        </div>

                        <div class="card-body">
                            <p v-if="mapping.module == null">
                                <b>Table & Column</b>: {{ mapping.destination_table_name }} -> {{ mapping.destination_column_name }}
                            </p>
                            <p v-else v-for="field_data in mapping.module_fields">
                                <b>{{ field_data.name }}</b>: {{ field_data.value }}
                            </p>

                            <div v-if="mapping.notes != '' && mapping.notes != null" style="margin-bottom: 10px; height: 100px; overflow-y: scroll;">
                                <b>Notes</b>
                                <hr>
                                {{ mapping.notes }}
                            </div>

                            <div v-if="mapping.condition != '' && mapping.condition != null" style="height: 100px; overflow-y: scroll;">
                                <b>Conditions</b>
                                <hr>
                                {{ mapping.condition }}
                            </div>
                        </div>
                    </div>
                </div>

                <template v-if="mapping_module_id != 0">
                    <div id="accordion" ref="accordion">
                        <div class="card">
                            <div class="card-header accordian-header"
                                id="heading-conditions">
                                <h5 class="mb-0">
                                    <button class="btn btn-block btn-link accordian-text"
                                            data-toggle="collapse"
                                            data-target="#collapse-condition"
                                            aria-expanded="true"
                                            aria-controls="collapse-condition">
                                        Conditions
                                    </button>
                                </h5>
                            </div>

                            <div id="collapse-condition"
                                class="collapse"
                                aria-labelledby="heading-conditions"
                                data-parent="#accordion">
                                <div class="card-body">
                                    <div class="dmiux_input" id="map-condition-div">
                                        <label for="textarea-input_column_condition">Condition</label>
                                        <textarea v-model="mapping.condition"
                                                class="dmiux_input__input"
                                                id="textarea-input_column_condition"
                                                placeholder="If..." rows="2"
                                        ></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header accordian-header" id="heading-notes">
                                <h5 class="mb-0">
                                    <button class="btn btn-block btn-link accordian-text"
                                            data-toggle="collapse"
                                            data-target="#collapse-notes"
                                            aria-expanded="true"
                                            aria-controls="collapse-notes">
                                        Notes
                                    </button>
                                </h5>
                            </div>

                            <div id="collapse-notes"
                                class="collapse"
                                aria-labelledby="heading-notes"
                                data-parent="#accordion">
                                <div class="card-body">
                                    <div class="form-group" id="map-notes-div">
                                        <label for="input-notes">Notes</label>
                                        <textarea class="dmiux_input__input"
                                                id="input-notes"
                                                v-model:value="mapping.notes"
                                                rows="2">
                                        </textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <div class="dmiux_popup__foot">
                <div class="dmiux_grid-row">
                    <div class="dmiux_grid-col"></div>
                    <div class="dmiux_grid-col dmiux_grid-col_auto">
                        <button v-if="!mapping.id" @click="closeMapModal()" class="dmiux_button dmiux_button_secondary" type="button">Close</button>
                        <button v-else @click="cancelEdit()" class="dmiux_button dmiux_button_secondary" type="button">Cancel Edit</button>
                    </div>
                    <div class="dmiux_grid-col dmiux_grid-col_auto" v-if="mapping_module_id != 0">
                        <button v-if="!mapping.id" @click="addMapping()" class="dmiux_button" type="button">Add mapping</button>
                        <button v-else @click="submitEdit()" class="dmiux_button" type="button">Save changes</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>