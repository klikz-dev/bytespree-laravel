<script type="text/x-template" id="csv-export-modal-template">
    <!-- Project Modal -->
    <div class="dmiux_popup" id="modal-csv_export" role="dialog" tabindex="-1">
        <div class="dmiux_popup__window dmiux_popup__window_lg" role="document">
            <div class="dmiux_popup__head">
                <h4 class="dmiux_popup__title">Export CSV</h4>
                <button id="x-button" type="button" class="dmiux_popup__close"></button>
            </div>
            <div class="dmiux_popup__cont">
                <div class="form-group">
                    <label class="dmiux_popup__label" for="input-send_users">What users do you want to send this export to?</label>
                    <multiselect v-model="users"
                                id="input-send_users"
                                :multiple="true" 
                                :options="$root.users" 
                                :show-labels="false" 
                                :close-on-select="false"
                                :clear-on-select="false"
                                label="name"
                                track-by="id"
                                :preselect-first="true">
                    </multiselect>
                </div>
                <div class="dmiux_input">
                    <label class="dmiux_popup__label" for="input-message">Message for users</label>
                    <textarea type="text" class="dmiux_input__input" id="input-message" v-model="message" maxlength="300"></textarea>
                </div>
                <div class="dmiux_input">
                    <label class="dmiux_popup__label" for="input-record-limit">Limit number of records</label>
                    <input type="number" class="dmiux_input__input" id="input-record-limit" v-model="limit" />
                </div>
            </div>
            <div class="dmiux_popup__foot">
                <div class="dmiux_grid-row">
                    <div class="dmiux_grid-col"></div>
                    <div class="dmiux_grid-col dmiux_grid-col_auto">
                        <button id="cancel-button-csv-export" class="dmiux_button dmiux_button_secondary dmiux_popup__close_popup dmiux_popup__cancel" type="button">Cancel</button>
                    </div>
                    <div class="dmiux_grid-col dmiux_grid-col_auto pl-0">
                        <button class="dmiux_button" type="button" @click="sendCSV();">Submit</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>
