<script type="text/x-template" id="publish-sftp-template">
    <!-- Publish Sftp Modal -->
    <div class="dmiux_popup" id="modal-publish_sftp" role="dialog" tabindex="-1">
        <div class="dmiux_popup__window dmiux_popup__window_md" role="document">
            <div class="dmiux_popup__head">
                <h4 class="dmiux_popup__title"><template v-if="$root.explorer.publisher.id != -1">Edit </template>Publish to SFTP Site</h4>
                <button id="x-button" type="button" class="dmiux_popup__close" @click="modalClosePublishSFTP($event)"></button>
            </div>
            <form id="form-publish_sftp" autocomplete="off" onSubmit="event.preventDefault()">
                <div class="dmiux_popup__cont hide-scroll">
                    <label class="dmiux_popup__label">File name preview: {{ table }}<template v-if="options.append_timestamp">_{yyyymmddhhiiss}</template>.csv</label>
                    <label class="dmiux_popup__label" for="select-site_id">SFTP Site</label>
                    <div class="dmiux_select">
                        <select v-model="options.site_id" id="select-site_id" class="dmiux_select__select">
                            <option v-for="site in sftp_sites" :value="site.id">{{ site.hostname }}</option>
                        </select>
                        <div class="dmiux_select__arrow"></div>
                    </div>
                    <div class="dmiux_input">
                        <label class="dmiux_popup__label" for="input-path">Path</label>
                        <input @input="cleanPath()" type="text" class="dmiux_input__input" id="input-path" v-model="options.path">
                    </div>
                    <div class="dmiux_checkbox">
                        <input type="checkbox" v-model="options.append_timestamp" class="dmiux_checkbox__input">
                        <div class="dmiux_checkbox__check"></div>
                        <div class="dmiux_checkbox__label">Append Timestamp?</div>
                    </div>
                    <div v-if="options.append_timestamp == false" class="dmiux_checkbox">
                        <input type="checkbox" v-model="options.overwrite_existing" class="dmiux_checkbox__input">
                        <div class="dmiux_checkbox__check"></div>
                        <div class="dmiux_checkbox__label">Overwrite Existing?</div>
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
                            <button id="cancel-button-publish-sftp" class="dmiux_button dmiux_button_secondary dmiux_popup__cancel" @click="modalClosePublishSFTP($event)" type="button">Cancel</button>
                        </div>
                        <div class="dmiux_grid-col dmiux_grid-col_auto pl-0">
                            <button class="dmiux_button dmiux_button_primary" type="button" @click="publish()" :disabled="! canSubmit">Publish</button>
                        </div>
                    </div>	
                </div>
            </form>
        </div>
    </div>
</script>