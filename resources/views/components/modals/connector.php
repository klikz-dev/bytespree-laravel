<script type="text/x-template" id="connector-modal-template">
    <!-- Integration Settings Modal -->
    <div class="dmiux_popup" id="modal-connector" role="dialog" tabindex="-1">
        <div class="dmiux_popup__window dmiux_popup__window_lg" role="document">
            <div class="dmiux_popup__head">
                <h4 class="dmiux_popup__title">Add Connector</h4>
                <button id="x-button" @click="modalClose($event)" type="button" class="dmiux_popup__close"></button>
            </div>
            <div class="dmiux_popup__cont_search">
                <div class="dmiux_input">
                    <input class="dmiux_input__input" v-model="filter" @input="filterConnectors($event)" placeholder="Search Connectors" />
                    <div class="dmiux_input__icon">
                        <svg height="16" viewBox="0 0 16 16" width="16" xmlns="http://www.w3.org/2000/svg">
                        <path d="M265.7,19.2298137 C266.6,18.0372671 267.1,16.6459627 267.1,15.0559006 C267.1,11.1801242 264,8 260.1,8 C256.2,8 253,11.1801242 253,15.0559006 C253,18.931677 256.2,22.1118012 260.1,22.1118012 C261.7,22.1118012 263.2,21.6149068 264.3,20.7204969 L267.3,23.7018634 C267.5,23.9006211 267.8,24 268,24 C268.2,24 268.5,23.9006211 268.7,23.7018634 C269.1,23.3043478 269.1,22.7080745 268.7,22.310559 L265.7,19.2298137 Z M260.05,20.1 C257.277451,20.1 255,17.9 255,15.1 C255,12.3 257.277451,10 260.05,10 C262.822549,10 265.1,12.3 265.1,15.1 C265.1,17.9 262.822549,20.1 260.05,20.1 Z" fill="currentColor" transform="translate(-253 -8)"></path></svg>
                    </div>
                </div>
            </div>
            <div class="dmiux_popup__cont dmiux_popup__cont_connector">
                <div class="dmiux_grid-row">
                    <div v-for="teamconnector in teamconnectors" class="dmiux_grid-col_lg-6 dmiux_grid-col_xs-12 dmiux_grid-col_6" v-if="teamconnector.visible == true && teamconnector.installed == false">
                        <div class="text-center">
                            <div class="dmiux_card-with-hdr__container mx-2">
                                <a href="#" class="text-decoration-none" @click="upload(teamconnector.id)">
                                    <!--todo: restore with custom pricing feature -->
                                    <div class="dmiux_db-card--header">{{ teamconnector.name }}</div>
                                    <div class="vertical-center database-card-image-container">
                                        <img :src="'/admin/connectors/' + teamconnector.id + '/orchestration-logo?v3.0.1'" alt="Avatar" class="img-fluid"/>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="dmiux_popup__foot">
                <div class="dmiux_grid-row">
                    <div class="dmiux_grid-col"></div>
                    <div class="dmiux_grid-col dmiux_grid-col_auto">
                        <button id="cancel-button-connector" @click="modalClose($event)" class="dmiux_button dmiux_button_secondary dmiux_popup__close_popup dmiux_popup__cancel" data-dismiss="modal" type="button" title="Close add connector">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>

<script>
    var connector = Vue.component('connector', {
        template: '#connector-modal-template',
        props: [ 'mode', 'open', 'connector', 'setting_types', 'teamconnectors' ],
        data : function() {
            return {
                "file": false,
                "filter": ''
            }
        },
        methods : {
            filterConnectors(event) {
                this.teamconnectors.forEach(connector => {
                    if (connector.name.toLowerCase().includes(this.filter.toLowerCase())) {
                        connector.visible = true;
                    }
                    else {
                        connector.visible = false;
                    }
                });
            },
            upload(id) {
                this.$parent.loading(true);
                if(!confirm("Are you sure you want to add this connector?")) {
                    this.$parent.loading(false);
                    return false;
                }
                
                let options = FetchHelper.buildJsonRequest({
                    id: id
                });

                fetch(`${baseUrl}/internal-api/v1/admin/connectors`, options)
                .then(FetchHelper.handleJsonResponse)
                .then(resp => {
                    this.$parent.loading(false);
                    this.$parent.getConnectors();
                    this.$parent.getTeamConnectors();
                    notify.success(resp.message);
                })
                .catch((error) => {
                    ResponseHelper.handleErrorMessage(error, "There was a problem adding the connector.");
                });
            },
            modalClose(event) {
                if (event != undefined) {
                    event.stopPropagation();
                    if(event.key != undefined) {
                        if(event.key != 'Escape') // not escape
                            return;
                    }
                    else {
                        var clicked_element = event.target;
                        if (clicked_element.closest(".dmiux_popup__window")) {
                            // You clicked inside the modal
                            if (clicked_element.id != "x-button" && !(clicked_element.classList.contains("dmiux_popup__cancel")))
                                return;
                        }
                    }
                }
                // You either clicked outside the modal, or the X Button, or the Cancel Button - modal will close
                this.filter = '';
                this.filterConnectors();
                this.$root.modal_add_connector = false;

                // End of code to run before closing
                $(document).off("mousedown", "#dmiux_body", this.modalClose);
                $(document).off("keydown", this.modalClose);
                closeModal('#modal-connector');
            }
        },
        watch: {
            open() {
                if (this.open) {
                    $(document).on("mousedown", "#dmiux_body", this.modalClose);
                    $(document).on("keydown", this.modalClose);
                    openModal("#modal-connector");
                }
            }
        }
    });
</script>