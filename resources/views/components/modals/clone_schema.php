<script type="text/x-template" id="clone-schema-modal-template">
    <!-- Longest_Ten Modal -->
    <div class="dmiux_popup" id="modal-clone_schema" role="dialog" tabindex="-1">
        <div class="dmiux_popup__window dmiux_popup__window_sm" role="document">
            <div class="dmiux_popup__head">
                <h4 class="dmiux_popup__title">Clone Schema </h4>
                <button type="button" class="dmiux_popup__close"></button>
            </div>
            <div class="dmiux_popup__cont" id="interactive-pane-counts">
                <label for="select-chosen_integration" class="pb-4">Clone your schema from one of the existing databases below:</label>
                <div class="dmiux_select">
                    <select id="select-chosen_integration" class="dmiux_select__select dmiux_select__select_pholder" v-model:value="chosen_integration">
                        <option selected disabled value="null">Select a database</option>
                        <option v-for="integration in integrations" 
                                :value="integration"
                                v-if="$parent.checkPerms('project_manage', integration.id) === true">
                                {{ integration.id }} - {{ integration.database }}
                        </option>
                    </select>
                    <div class="dmiux_select__arrow"></div>
                </div>
                <div class="pb-4"></div>
            </div>
            <div class="dmiux_popup__foot">
                <div class="dmiux_grid-row">
                    <div class="dmiux_grid-col"></div>
                    <div class="dmiux_grid-col dmiux_grid-col_auto pr-0">
                        <button class="dmiux_button dmiux_button_secondary dmiux_popup__close_popup" type="button">Close</button>
                    </div>
                    <div class="dmiux_grid-col dmiux_grid-col_auto">
                        <button class="dmiux_button" type="button" @click="cloneIntegration">Submit</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>

<script>
    Vue.component('clone-schema', {
        template: '#clone-schema-modal-template',
        props: ['integrations'],
        data: function(){
            return {
                chosen_integration: null
            }
        },
        methods: {
            cloneIntegration() {
                if(this.chosen_integration == null) {
                    notify.danger("Please select a database to clone.");
                    return;
                }

                var options = FetchHelper.buildJsonRequest({
                    database_id: this.chosen_integration.id
                }, 'PUT');

                this.$root.loading(true);
                fetch(`/internal-api/v1/admin/schemas/clone/${this.$root.schema_id}`, options)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.$root.loading(false);
                        this.$root.getTables();
                        this.closeCloneSchemaModal();
                    })
                    .catch((error) => {
                        this.$root.loading(false);
                        ResponseHelper.handleErrorMessage(error, "Failed to clone schema");
                    });
            },
            closeCloneSchemaModal() {
                var check = CloseModalHandler.checkClicked("modal-clone_schema", event);
                if(check) {
                    return;
                }
                // You either clicked outside the modal, or the X Button, or the Cancel Button - modal will close
                
                // End of code to run before closing
                this.chosen_integration = null;
                $(document).off("mousedown", "#dmiux_body", this.closeCloneSchemaModal);
                $(document).off("keydown", this.closeCloneSchemaModal);
                closeModal('#modal-clone_schema');
            }
        }
    });
</script>