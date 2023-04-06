<script type="text/x-template" id="publish-mssql-template">
    <!-- Publish to Microsoft Server Modal -->
    <div class="dmiux_popup" id="modal-publish_mssql" role="dialog" tabindex="-1">
        <div class="dmiux_popup__window dmiux_popup__window_md" role="document">
            <div class="dmiux_popup__head">
                <h4 class="dmiux_popup__title"><template v-if="$root.explorer.publisher.id != -1">Edit </template>Publish to Microsoft SQL Server</h4>
                <button id="x-button" type="button" class="dmiux_popup__close" @click="modalClose($event)"></button>
            </div>
            <form id="form-publish_mssql" autocomplete="off" onSubmit="event.preventDefault()">
                <div class="dmiux_popup__cont hide-scroll">
                    <label class="dmiux_popup__label" for="select-server_id">Server</label>
                    <div class="dmiux_select">
                        <select v-model="options.server_id" id="select-server_id" class="dmiux_select__select" @change="serverSelected">
                            <option value="0" selected>Select a Server</option>
                            <option v-for="server in servers" :value="server.id">{{ server.data.username }}@{{ server.data.hostname }}</option>
                        </select>
                        <div class="dmiux_select__arrow"></div>
                    </div>
                    <label class="dmiux_popup__label" for="select-database">Database</label>
                    <div class="dmiux_select">
                        <select v-model="options.target_database" id="select-database" class="dmiux_select__select" @change="databaseSelected" :disabled="options.server_id == 0">
                            <option value="">Create a New Database</option>
                            <option v-for="database in databases" :value="database">{{ database }}</option>
                        </select>
                        <div class="dmiux_select__arrow"></div>
                    </div>
                    <div class="dmiux_input" v-if="options.using_new_database">
                        <label class="dmiux_popup__label" for="input-new_database">New Database Name</label>
                        <input type="text" maxlength="124" class="dmiux_input__input" id="input-new_database" v-model="options.target_create_database" :disabled="options.server_id == 0">
                    </div>
                    <div v-if="! options.using_new_database">
                        <label class="dmiux_popup__label" for="select-table">Table</label>
                        <div class="dmiux_select">
                            <select v-model="options.target_table" id="select-table" class="dmiux_select__select" @change="tableSelected" :disabled="options.target_database == ''">
                                <option value="">Create a New Table</option>
                                <option v-for="table in tables" :value="table">{{ table }}</option>
                            </select>
                            <div class="dmiux_select__arrow"></div>
                        </div>
                    </div>
                    <div class="dmiux_input" v-if="options.using_new_table">
                        <label class="dmiux_popup__label" for="input-new_table">New Table Name</label>
                        <input type="text" maxlength="124" class="dmiux_input__input" id="input-new_table" v-model="options.target_create_table">
                    </div>
                    <div class="dmiux_checkbox" v-if="! options.using_new_table">
                        <input type="checkbox" v-model="options.truncate_on_publish" class="dmiux_checkbox__input">
                        <div class="dmiux_checkbox__check"></div>
                        <div class="dmiux_checkbox__label">Truncate existing data on publish?</div>
                    </div>
                    <div class="dmiux_checkbox" v-if="options.using_new_table && publish_type == 'one_time'">
                        <input type="checkbox" v-model="options.append_timestamp" class="dmiux_checkbox__input">
                        <div class="dmiux_checkbox__check"></div>
                        <div class="dmiux_checkbox__label">Append timestamp to table name?</div>
                    </div>
                    <publisher-scheduling limit="500"
                        element_prefix="csv"
                        :can_change_publish_type="this.id == -1"
                        :schedule="this.schedule"
                        :publish_type="publish_type"
                        @update-schedule="schedule_changed"
                        @update-publish-type="publish_type_changed">
                    </publisher-scheduling>
                </div>
                <div class="dmiux_popup__foot">
                    <div v-if="options.using_custom_columns" class="dmiux_grid-row">
                        <div class="dmiux_grid-col dmiux_grid-col_auto pb-2">
                            <label class="dmiux_popup__label text-muted m-0">
                                <i class="fa fa-info"></i> Custom columns will be published as nvarchar(MAX)
                            </label>
                        </div>
                    </div>
                    <div class="dmiux_grid-row">
                        <div class="dmiux_grid-col"></div>
                        <div class="dmiux_grid-col dmiux_grid-col_auto">
                            <button id="cancel-button-publish-mssql" class="dmiux_button dmiux_button_secondary dmiux_popup__cancel" @click="modalClose($event)" type="button">Cancel</button>
                        </div>
                        <div class="dmiux_grid-col dmiux_grid-col_auto">
                            <button class="dmiux_button dmiux_button_primary" type="button" @click="publish()" :disabled="! canSubmit">Publish</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</script>