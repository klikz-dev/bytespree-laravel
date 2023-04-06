<script type="text/x-template" id="create-empty-database-template">
    <!-- Project Settings Modal -->
    <div class="dmiux_popup" id="modal-create-empty-database" ref="create_empty_database_modal" role="dialog" tabindex="-1">
        <div class="dmiux_popup__window dmiux_popup__window_md" role="document">
            <div class="dmiux_popup__head">
                <h4 class="dmiux_popup__title">Create an empty database</h4>
                <button type="button" class="dmiux_popup__close" id="x-button"></button>
            </div>
            <form id="form-create_empty_database" autocomplete="off" onSubmit="event.preventDefault()">
                <div class="dmiux_popup__cont">
                    <div class="dmiux_input">
                        <label class="dmiux_popup__label" for="empty-database">Database Name</label>
                        <input type="text"
                                class="dmiux_input__input"
                                name="database"
                                id="empty-database"
                                maxlength="59"
                                @input="cleanupDatabaseName()"
                                v-model="database.name"
                                placeholder="Name of database"
                                pattern="[0-9a-z$_]+"
                                required />
                        <small>Database names must start with a letter and can only contain letters, numbers and underscores.</small>
                    </div>
                    <label class="dmiux_popup__label" for="empty-database-server">Server</label>
                    <div class="dmiux_select">
                        <select v-model="database.server_id" class="dmiux_select__select" id="empty-database-server" required>
                            <option value="" disabled>Choose a Server</option>
                            <option v-for="server in servers" v-if="server.status === undefined || server.status == '' || server.status == 'online'" :value="server.id">{{ server.name }}</option>
                        </select>
                        <div class="dmiux_select__arrow"></div>
                    </div>
                    <div class="dmiux_checkbox">
                        <input type="checkbox" class="dmiux_checkbox__input" id="empty-database-checkbox" v-model="database.checkbox_value"> 
                        <div class="dmiux_checkbox__check dmiux_empty_database-checkbox"></div>
                        <label for="empty-database-checkbox">Take me to the CSV importer after creating the database</label>
                    </div>
                </div>
                <div class="dmiux_popup__foot">
                    <div class="dmiux_grid-row">
                        <div class="dmiux_grid-col"></div>
                        <div class="dmiux_grid-col dmiux_grid-col_auto">
                            <button class="dmiux_button dmiux_button_secondary dmiux_popup__close_popup dmiux_popup__cancel" id="cancel-button-create-empty-database" type="button">Cancel</button>
                        </div>
                        <div class="dmiux_grid-col dmiux_grid-col_auto">
                            <button class="dmiux_button" type="button" @click="saveEmptyDatabase()">Create</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</script>
