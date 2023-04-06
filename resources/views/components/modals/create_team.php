<!-- Update password Modal -->
<div class="dmiux_popup" id="modal-create_team" role="dialog" tabindex="-1">
    <div class="dmiux_popup__window" role="document">
        <div class="dmiux_popup__head">
            <h4 class="dmiux_popup__title">Create Team</h4>
            <button type="button" class="dmiux_popup__close" @click="popupClose()"></button>
        </div>
        <div class="dmiux_popup__cont">
            <div v-if="message != ''" :class="messageClass" class="alert">{{ message }}</div>
            <div class="dmiux_input">
                <label class="dmiux_popup__label" for="input-team_name">Team name</label>
                <input @input="sanitizeTeamDomain"
                       type="text"
                       class="dmiux_input__input"
                       id="input-team_name"
                       v-model="name" />
            </div>
            <div class="dmiux_input">
                <label class="dmiux_popup__label" for="inputTeamRegion">Choose a region</label>
                <select v-model="selected_region_id" class="dmiux_input__input" id="inputTeamRegion" required>
                    <option value="" selected disabled>Choose a data center location</option>
                    <option v-for="region in regions" :value="region.id">{{ region.name }}</option>
                </select>
                <div class="dmiux_select__arrow mt-2"></div>
            </div>
            <div class="form-group mt-3">
                <label for="input-connector_select">Choose connectors</label>
                <multiselect v-model="selected_connectors"
                             id="input-connector_select"
                             :multiple="true" 
                             :options="connectors" 
                             :show-labels="false" 
                             :close-on-select="false"
                             :clear-on-select="false"
                             label="name"
                             track-by="name"
                             :preselect-first="true">
                </multiselect>
            </div>
        </div>
        <div class="dmiux_popup__foot">
            <div class="dmiux_grid-row">
                <div class="dmiux_grid-col"></div>
                <div class="dmiux_grid-col dmiux_grid-col_auto">
                    <button class="dmiux_button dmiux_button_secondary dmiux_popup__close_popup" @click="popupClose()" type="button">Close</button>
                </div>
                <div class="dmiux_grid-col dmiux_grid-col_auto pl-0">
                    <button class="dmiux_button" type="button" @click="createTeam()">Create</button>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    Vue.component('multiselect', window.VueMultiselect.default);
    var create_team = new Vue({
        el: "#modal-create_team",
        data: {
            name: "",
            regions: [],
            connectors: [],
            message: "",
            messageClass: "",
            selected_region_id: 0,
            selected_connectors: []
        },
        methods: {
            loading: function(status) {
                if(status === true) {
                    $(".loader").show();
                    $(document).off("mousedown", "#dmiux_body", autoClose);
                    $(document).off('keydown', closeModalOnEscape);
                }
                else {
                    $(".loader").hide();
                    $(document).on("mousedown", "#dmiux_body", autoClose);
                    $(document).on('keydown', closeModalOnEscape);
                }
            },
            sanitizeTeamDomain() {
                // Clean up name
                this.name = this.name.replace(/[^0-9a-z]/gi, '').toLowerCase().substring(0, 20);
                this.name = this.name.replace(/^[0-9]/, '');
            },
            getRegions() {
                fetch(`/App/getRegions`)
                    .then(response => response.json())
                    .then(resp => {
                        if(resp.status == "ok") {
                            this.regions = resp.data;
                        }
                    });
            },
            getConnectors() {
                fetch(`/App/getConnectors`)
                    .then(response => response.json())
                    .then(resp => {
                        if(resp.status == "ok") {
                            this.connectors = resp.data;
                        }
                    });
            },
            popupClose() {
                this.selected_region_id = "";
                this.name = "";
                this.message = ""; 
            },
            createTeam() {
                this.loading(true);
                this.message = "";

                const options = {
                    method: 'post',
                    headers: {
                        'Content-type': 'application/x-www-form-urlencoded; charset=UTF-8'
                    },
                    body: `domain=${this.name}` +
                          `&region_id=${this.selected_region_id}` +
                          `&connectors=${JSON.stringify(this.selected_connectors)}`
                }

                fetch(`/App/createTeam`, options)
                    .then(response => response.json())
                    .then(resp => {
                        if(resp.status == "ok") {
                            setTimeout(this.checkForTeamCreation, 5000);
                        }
                        else {
                            this.loading(false);
                            this.messageClass = "alert-danger";
                            this.message = resp.message;
                        }
                    });
            },
            checkForTeamCreation: function() {
                const options = {
                    method: 'post',
                    headers: {
                        'Content-type': 'application/x-www-form-urlencoded; charset=UTF-8'
                    },
                    body: `domain=${this.name}` +
                          `&region_id=${this.selected_region_id}`
                }

                fetch(`/App/checkForTeamCreation`, options)
                    .then(response => response.json())
                    .then(resp => {
                        if(resp.status == "ok") {
                            if (resp.data.status == "SUCCESS") {
                                this.loading(false);
                                this.messageClass = "alert-success";
                                this.selected_region_id = "";
                                this.name = "";
                                this.message = resp.message;
                                this.loading(false);
                            }
                            else {  // status is one of these: QUEUED, RUNNING, WAITING
                                setTimeout(this.checkForTeamCreation, 5000);
                            }
                        }
                        else { // status is FAILURE, UNSTABLE, ABORTED, Error retrieving team creation job, or Error running team creation job
                            this.loading(false);
                            this.messageClass = "alert-danger";
                            this.message = resp.message;
                        }
                    });
            }
        },
        mounted() {
            this.getRegions();
            this.getConnectors();
        }
    });
</script>
