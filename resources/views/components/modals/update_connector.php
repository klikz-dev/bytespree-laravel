<script type="text/x-template" id="update-connector-modal-template">
    <!-- Update Connector Modal -->
    <div class="dmiux_popup" id="modal-update-connector" role="dialog" tabindex="-1">
        <div class="dmiux_popup__window dmiux_popup__window_lg" role="document">
            <div class="dmiux_popup__head">
                <h4 class="dmiux_popup__title">Update Connector</h4>
                <button type="button" class="dmiux_popup__close"></button>
            </div>
            <div class="dmiux_popup__cont dmiux_popup_cont_nav">
                <div class="dmiux_block mb-1 mt-3">
                    <label><strong>Version</strong></label>
                    <p>{{ selected_connector.version }} -> {{ orchestration_connector.version }}</p>
                    <label><strong>Release Notes</strong></label>
                    <p>{{ orchestration_connector.release_notes }}</p>
                </div>
            </div>
            <div class="dmiux_popup__foot">
                <div class="dmiux_grid-row">
                    <div class="dmiux_grid-col"></div>
                    <div class="dmiux_grid-col dmiux_grid-col_auto pr-0">
                        <button class="dmiux_button dmiux_button_secondary dmiux_popup__close_popup" data-dismiss="modal" type="button">Cancel</button>
                    </div>
                    <div class="dmiux_grid-col dmiux_grid-col_auto">
                        <button class="dmiux_button" @click="update()" type="button">Update</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>

<script>
    var updateConnector = Vue.component('update-connector', {
        template: '#update-connector-modal-template',
        props: [ "selected_connector", "orchestration_connector" ],
        methods : {
            update() {
                this.$parent.loading(true);
                fetch(`${baseUrl}/internal-api/v1/admin/connectors/${this.orchestration_connector.id}`, { method: 'put' })
                    .then(response => response.json())
                    .then(resp => {
                        this.$parent.loading(false);
                        if(resp.status != 'ok') 
                        {
                            notify.danger("There was a problem updating the connector.");
                            return;
                        }
                        this.$parent.getConnectors();
                        notify.success("Your connector is being processed and will be updated soon.");
                    });
            }
        }
    });
</script>