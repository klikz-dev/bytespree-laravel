<script type="text/x-template" id="publish-snapshot-template">
    <div class="dmiux_popup" id="modal-publish_snapshot" role="dialog" tabindex="-1">
        <div class="dmiux_popup__window dmiux_popup__window_lg" role="document">
            <div class="dmiux_popup__head">
                <h4 v-if="this.id != -1" class="dmiux_popup__title">Update Snapshot</h4>
                <h4 v-else class="dmiux_popup__title">Publish to Snapshot</h4>
                <button id="x-button" type="button" class="dmiux_popup__close"></button>
            </div>
            <form class="dmiux_popup__cont" onsubmit="event.preventDefault();" autocomplete="off">
                <chars limit="30"
                       id="input-name"
                       name="Name"
                       type="input"
                       :required="true"
                       additional-help-text="Snapshot names may only contain lowercase characters, underscores, and numbers and must begin with a letter."
                       @input='nameChanged'
                       :value="name">
                </chars>
                <chars limit="500"
                       id="input-description-publish-snapshot"
                       name="Description"
                       type="textarea"
                       :required="false"
                       @input='descriptionChanged'
                       :value="description ?? ''">
                </chars>

                <div class="dmiux_checkbox">
                    <input type="checkbox" v-model="options.append_timestamp" class="dmiux_checkbox__input">
                    <div class="dmiux_checkbox__check"></div>
                    <div class="dmiux_checkbox__label">
                        Append a timestamp to the snapshot name
                    </div>
                </div>
                <div v-if="! options.append_timestamp && publish_type == 'scheduled'" class="alert alert-info mt-3">
                    Without timestamps, the destination table will be replaced each time it is published.
                </div>

                <publisher-scheduling
                       element_prefix="snapshot"
                       :can_change_publish_type="this.id == -1"
                       :schedule="this.schedule"
                       :publish_type="publish_type"
                       @update-schedule="schedule_changed"
                       @update-publish-type="publish_type_changed">
                </publisher-scheduling>
            </form>
            <div class="dmiux_popup__foot">
                <div class="dmiux_grid-row">
                    <div class="dmiux_grid-col"></div>
                    <div class="dmiux_grid-col dmiux_grid-col_auto">
                        <button id="cancel-button-publish-snapshot" class="dmiux_button dmiux_button_secondary dmiux_popup__close_popup dmiux_popup__cancel" type="button">Cancel</button>
                    </div>
                    <div class="dmiux_grid-col dmiux_grid-col_auto pl-0">
                        <button class="dmiux_button" type="button" @click="publish();">Submit</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>
