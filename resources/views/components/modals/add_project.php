<script type="text/x-template" id="add-project-modal-template">
    <!-- Project Modal -->
    <div v-click-outside="clearProject" class="dmiux_popup" id="modal-add_project" role="dialog" tabindex="-1">
        <div class="dmiux_popup__window dmiux_popup__window_md" role="document">
            <div class="dmiux_popup__head">
                <h4 class="dmiux_popup__title display-inline_flex">
                    <template v-if="editing == false">Add </template>
                    <template v-else>Edit <span class="modal-title-overflow_text pl-1 pr-1">{{ project.display_name }}</span></template>Project
                </h4>
                <button @click="clearProject()" type="button" class="dmiux_popup__close"></button>
            </div>
            <form id="form-add_project" autocomplete="off">
                <div class="dmiux_popup__cont">
                    <div class="dmiux_input">
                        <label class="dmiux_popup__label" for="input-add_project_display_name">Name</label>
                        <input type="text" maxlength="200" @input="resetSchema()" @blur="generateSchemaName()" class="dmiux_input__input" id="input-add_project_display_name" v-model="project.display_name">
                        <small class="text-muted pt-1 d-block">
                            <span class="tooltip-pretty fas fa-info-circle text-primary cursor-p" title="This is an auto-generated key to be used for your Studio project."></span>
                            Project Key: {{ project.name }}</small>
                    </div>
                    <div class="dmiux_input">
                        <label class="dmiux_popup__label" for="input-add_project_desc">Description</label>
                        <input type="text" maxlength="200" @input="cleanupDescription()" class="dmiux_input__input" id="input-add_project_desc" v-model="project.description">
                    </div>
                    <div class="dmiux_input" v-if="editing == true">
                        <label class="dmiux_popup__label" for="input-add_project_primary_database">Primary Database</label>
                        <input type="text" class="dmiux_input__input" id="input-add_project_primary_database" v-model="project.primary_database.database" disabled>
                    </div>
                    <div>
                        <label class="dmiux_popup__label" for="input-foreign_databases">Which databases should be included?</label>
                        <div class="dmiux_multiselect">
                            <input readonly id="input-foreign_databases" type="text" :value="project.foreign_databases.map((f_db) => { return f_db.database }).join(', ')" class="dmiux_multiselect__input">
                            <div class="dmiux_multiselect__arrow"></div>
                            <div class="dmiux_multiselect__dropdown">
                                <div class="dmiux_multiselect__overflow">
                                    <div v-for="database in databases" :key="database.id" v-if="inDatabase(database) || $root.checkPerms('manage_settings', database.id, 'warehouse')" class="dmiux_multiselect__checkbox dmiux_checkbox mt-1">
                                        <input v-model="project.foreign_databases" :disabled="!$root.checkPerms('manage_settings', database.id, 'warehouse')" @change="foreignDatabaseHandling()" :value="database" type="checkbox" class="dmiux_checkbox__input">
                                        <div class="dmiux_checkbox__check"></div>
                                        <div class="dmiux_checkbox__label">{{ database.database }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="project.foreign_databases.length > 0 && editing == false" class="dmiux_input">
                            <label class="dmiux_popup__label" for="input-add_project_database">Which database should be primary?</label>
                            <div class="dmiux_select">
                                <select class="dmiux_select__select" id="input-add_project_database" v-model="project.partner_integration_id">
                                    <option selected disabled value="">Select a database</option>
                                    <option v-for="database in project.foreign_databases" :value="database.id">{{ database.database }}</option>
                                </select>
                                <div class="dmiux_select__arrow"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="dmiux_popup__foot">
                    <div class="dmiux_grid-row">
                        <div class="dmiux_grid-col"></div>
                        <div class="dmiux_grid-col dmiux_grid-col_auto">
                            <button @click="clearProject()" class="dmiux_button dmiux_button_secondary dmiux_popup__close_popup" type="button">Cancel</button>
                        </div>
                        <div class="dmiux_grid-col dmiux_grid-col_auto pl-0">
                            <button v-if="editing == false" class="dmiux_button" type="button" @click="addProject();" :disabled="! canSubmitProject">Submit</button>
                            <button v-else class="dmiux_button" type="button" @click="editProject();">Submit</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</script>