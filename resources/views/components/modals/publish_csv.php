<script type="text/x-template" id="publish-csv-template">
    <!-- Publish CSV Modal -->
    <div class="dmiux_popup" id="modal-publish_csv" role="dialog" tabindex="-1">
        <div class="dmiux_popup__window dmiux_popup__window_md" role="document">
            <div class="dmiux_popup__head">
                <h4 class="dmiux_popup__title"><template v-if="$root.explorer.publisher.id != -1">Edit </template>Publish to CSV</h4>
                <button id="x-button" type="button" class="dmiux_popup__close" @click="modalClose($event)"></button>
            </div>
            <form id="form-publish_csv" onSubmit="event.preventDefault()" autocomplete="off">
                <div class="dmiux_popup__cont hide-scroll">
                    <div class="form-group">
                        <label class="dmiux_popup__label" for="input-csv-send_users">Which users do you want to send this export to? <span class="text-danger">*</span></label>
                        <multiselect v-model="options.users"
                                    id="input-csv-send_users"
                                    :multiple="true" 
                                    :options="actualUsers" 
                                    :show-labels="false" 
                                    :close-on-select="false"
                                    :clear-on-select="false"
                                    label="name"
                                    track-by="id"
                                    :preselect-first="true">
                        </multiselect>
                    </div>
                    <div class="dmiux_input">
                        <label class="dmiux_popup__label" for="input-csv-message">Message for users</label>
                        <textarea type="text" class="dmiux_input__input" id="input-csv-message" v-model="options.message" maxlength="300"></textarea>
                    </div>
                    <div class="dmiux_input">
                        <label class="dmiux_popup__label" for="input-csv-record-limit">Limit number of records</label>
                        <input type="number" class="dmiux_input__input" id="input-csv-record-limit" v-model="options.limit" />
                    </div>
                    
                    <div class="dmiux_checkbox">
                        <input type="checkbox" v-model="options.append_timestamp" class="dmiux_checkbox__input">
                        <div class="dmiux_checkbox__check"></div>
                        <div class="dmiux_checkbox__label">
                            Append a timestamp to the filename
                        </div>
                    </div>
                    <div v-if="! options.append_timestamp" class="alert alert-info mt-3">
                        Without timestamps, files with the same name will be overwritten.
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
                    <div class="dmiux_grid-row">
                        <div class="dmiux_grid-col"></div>
                        <div class="dmiux_grid-col dmiux_grid-col_auto">
                            <button id="cancel-button-publish-csv" class="dmiux_button dmiux_button_secondary dmiux_popup__cancel" @click="modalClose($event)" type="button">Cancel</button>
                        </div>
                        <div class="dmiux_grid-col dmiux_grid-col_auto pl-0">
                            <button class="dmiux_button dmiux_button_primary" type="button" @click="publish()">Publish</button>
                        </div>
                    </div>	
                </div>
            </form>
        </div>
    </div>
</script>