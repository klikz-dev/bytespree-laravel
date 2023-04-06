<script type="text/x-template" id="add-server-modal-template">
    <!-- Server Modal -->
    <div class="dmiux_popup" id="modal-add_server" role="dialog" tabindex="-1">
        <div class="dmiux_popup__window dmiux_popup__window_lg" role="document">
            <div class="dmiux_popup__head">
                <h4 class="dmiux_popup__title">
                    <template v-if="editing == false">Add Server</template>
                    <template v-else>Edit - {{ server.name.substring(0, 20) }}<template v-if="server.name.length > 20">...</template></template>
                </h4>
                <button type="button" class="dmiux_popup__close" @click="modalClose()"></button>
            </div>
            <form id="form-add_server" autocomplete="off" onSubmit="event.preventDefault()">
                <div class="dmiux_popup__cont">
                    <div v-if="server.type == 'do'">
                        <div class="dmiux_input">
                            <label class="dmiux_popup__label" for="add_server_name">Name <span class="text-danger">*</span></label>
                            <input type="text" @input="cleanupName()" class="dmiux_input__input" id="add_server_name" v-model="server.name">
                            <small>Must be alphanumeric with no spaces</small>
                        </div>
                        <template v-if="server.groups.length > 0">
                            <label class="dmiux_popup__label" for="manage_server_ips">Allowed IP Addresses <small>(Optional)</small> <small v-if="this.server.groups[0].ip != ''" @click="addGroup()" class="float-right cursor-p link-color-blue">Add a New IP Group</small></label>
                            <div class="card-group">
                                <div v-for="(group, index) in server.groups" class="dmiux_cards__col dmiux_grid-col_12 dmiux_cards__item mb-2">
                                    <div class="dmiux_cards__heading pt-1">
                                        IP Group {{index + 1}}
                                        <button title="Remove IP Group" type="button" tabindex="-1" class="dmiux_account__button dmiux_account__button_remove transformation_buttons server_ip_remove_button" @click="removeGroup(index)"></button>
                                    </div>
                                    <div class="dmiux_grid-row"> 
                                        <div class="dmiux_grid-col dmiux_grid-col_6 dmiux_input mt-1">
                                            <textarea placeholder="255.255.255.255, 255.255.255.254" type="text" size="15" class="dmiux_input__input textarea_fix-height" id="manage_server_ips" v-model="group.ips"></textarea>
                                        </div>
                                        <div class="dmiux_grid-col dmiux_grid-col_6 dmiux_input mt-1">
                                            <textarea placeholder="Note" type="text" class="dmiux_input__input textarea_fix-height" id="manage_server_ips" v-model="group.notes"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <template v-else>
                            <div class="dmiux_checkbox">
                                <input @change="addGroup()" type="checkbox" class="dmiux_checkbox__input">
                                <div class="dmiux_checkbox__check"></div>
                                <div class="dmiux_checkbox__label">Allow access from external IP addresses</div>
                            </div>
                        </template>

                        <div class="dmiux_checkbox">
                            <input :checked="this.server_is_default" @change="updateDefaultStatus()" type="checkbox" name="input-is_default_server" class="dmiux_checkbox__input" />
                            <div class="dmiux_checkbox__check"></div>
                            <div class="dmiux_checkbox__label">Use this server as default when creating databases</div>
                        </div>

                        <label class="dmiux_popup__label" for="input-add_server_configuration">Configuration</label>
                        <div class="dmiux_select">
                            <select class="dmiux_select__select" id="input-add_server_configuration" v-model="server.server_provider_configuration_id">
                                <option disabled value="">Choose a configuration</option>
                                <option v-for="c in configurations" v-if="!server.group_hierarchy || c.group_hierarchy >= server.group_hierarchy" :value="c.id">{{ c.memory }}GB RAM | {{ c.storage }}GB SSD | {{ c.cpus}} vCPUs | {{ c.nodes - 1 }} STANDBY NODES{{ c.resale_price ? ' | $' + c.resale_price + '/month' : '' }}</option>
                            </select>
                            <div class="dmiux_select__arrow"></div>
                        </div>
                        <hr v-if="! editing" />
                        <a v-if="! editing && $root.team_details.custom_postgres_server" href="#" @click="change_type">Use Existing Server</a>
                    </div>
                    <div v-else>
                        <div class="dmiux_grid-row">
                            <div class="dmiux_grid-col dmiux_grid-col_12">
                                <h5>Server Connection Details</h5>
                            </div>
                        </div>
                        <div class="dmiux_grid-row">
                            <div class="dmiux_grid-col dmiux_grid-col_6 dmiux_input">
                                <label class="dmiux_popup__label" for="input-add_server_name">Name <span class="text-danger">*</span></label>
                                <input type="text" class="dmiux_input__input" @input="cleanupName()" id="input-add_server_name" v-model="server.name">
                                <small>Name must be alphanumeric with no spaces</small>
                            </div>
                            <div class="dmiux_grid-col dmiux_grid-col_6 dmiux_input">
                                <label class="dmiux_popup__label" for="input-add_server_hostname">Hostname <span class="text-danger">*</span></label>
                                <input type="text" class="dmiux_input__input" id="input-add_server_hostname" v-model="server.hostname">
                            </div>
                        </div>
                        <div class="dmiux_grid-row">
                            <div class="dmiux_grid-col dmiux_grid-col_6 dmiux_input">
                                <label class="dmiux_popup__label" for="input-add_server_username">Username <span class="text-danger">*</span></label>
                                <input type="text" class="dmiux_input__input" id="input-add_server_username" v-model="server.username">
                            </div>
                            <div class="dmiux_grid-col dmiux_grid-col_6 dmiux_input">
                                <label class="dmiux_popup__label" for="input-add_server_password">Password <span class="text-danger">*</span></label>
                                <input type="text" class="dmiux_input__input" id="input-add_server_password" v-model="server.password">
                            </div>
                        </div>
                        <div class="dmiux_grid-row">
                            <div class="dmiux_grid-col dmiux_grid-col_6 dmiux_input">
                                <label class="dmiux_popup__label" for="input-add_server_port">Port <span class="text-danger">*</span></label>
                                <input type="number" class="dmiux_input__input" id="input-add_server_port" v-model="server.port">
                            </div>
                            <div class="dmiux_grid-col dmiux_grid-col_6 dmiux_input">
                                <label class="dmiux_popup__label" for="input-add_server_default_database">Default Database <span class="text-danger">*</span></label>
                                <input type="text" class="dmiux_input__input" id="input-add_server_default_database" v-model="server.default_database">
                                <small>This is typically "postgres"</small>
                            </div>
                        </div>
                        <div class="dmiux_grid-row">
                            <div class="dmiux_grid-col dmiux_grid-col_12 dmiux_input">
                                <label class="dmiux_popup__label" for="input-add_server_driver">Driver <span class="text-danger">*</span></label>
                                <div class="dmiux_select">
                                    <select class="dmiux_select__select" id="input-add_server_driver" v-model="server.driver">
                                        <option selected disabled value="">Select a driver</option>
                                        <option value="postgre">PostgreSQL</option>
                                    </select>
                                    <div class="dmiux_select__arrow"></div>
                                </div>
                            </div>
                        </div>

                        <div class="dmiux_grid-row">
                            <div class="dmiux_grid-col dmiux_grid-col_12 dmiux_checkbox">
                                <input type="checkbox" @change="updateDefaultStatus()" name="input-is_default_server" class="dmiux_checkbox__input" :checked="this.server_is_default" />
                                <div class="dmiux_checkbox__check"></div>
                                <div class="dmiux_checkbox__label">Use this server as default when creating databases</div>
                            </div>
                        </div>

                        <div class="dmiux_grid-row mt-3">
                            <div class="dmiux_grid-col dmiux_grid-col_6">
                                <h5>Server Maintenance Window</h5>
                            </div>
                            <div class="dmiux_grid-col dmiux_grid-col_6 text-right">
                                <a v-if="hasMaintenanceWindow()" href="#" @click="clearMaintenanceWindow()">Clear Maintenance Window</a>
                            </div>
                        </div>
                        <div class="alert alert-warning mb-1">
                            <strong>Heads up!</strong> Times should be entered using UTC time.</a>
                        </div>
                        <div class="dmiux_grid-row">
                            <div class="dmiux_grid-col dmiux_grid-col_6">
                                <label class="dmiux_popup__label" for="input-add_server_start_day">Starting Day Of The Week</label>
                                <div class="dmiux_select">
                                    <select class="dmiux_select__select" id="input-add_server_start_day" v-model="server.start_day">
                                        <option disabled value="">Choose a day</option>
                                        <option v-for="day in week_days" :value="day">{{ $root.formatDay(day) }}</option>
                                    </select>
                                    <div class="dmiux_select__arrow"></div>
                                </div>
                            </div>
                            <div class="dmiux_grid-col dmiux_grid-col_6 dmiux_input">
                                <label class="dmiux_popup__label" for="input-add_server_end_day">Ending Day Of The Week</label>
                                <div class="dmiux_select">
                                    <select class="dmiux_select__select" id="input-add_server_end_day" v-model="server.end_day">
                                        <option disabled value="">Choose a day</option>
                                        <option v-for="day in week_days" :value="day">{{ $root.formatDay(day) }}</option>
                                    </select>
                                    <div class="dmiux_select__arrow"></div>
                                </div>
                            </div>
                        </div>
                        <div class="dmiux_grid-row">
                            <div class="dmiux_grid-col dmiux_grid-col_6 dmiux_input">
                                <label class="dmiux_popup__label" for="input-add_server_start_time">Starting Hours</label>
                                <input type="time" class="dmiux_input__input" id="input-add_server_start_time" @change="changeStartTime" v-model="server.start_time_no_timezone">
                                <small class="ml-1">{{ local_start_time }}</small>
                            </div>
                            <div class="dmiux_grid-col dmiux_grid-col_6 dmiux_input">
                                <label class="dmiux_popup__label" for="input-add_server_end_time">Ending Hours</label>
                                <input type="time" class="dmiux_input__input" id="input-add_server_end_time" @change="changeEndTime" v-model="server.end_time_no_timezone">
                                <small class="ml-1">{{ local_end_time }}</small>
                            </div>
                        </div>
                        <hr v-if="!editing" />
                        <a v-if="!editing" href="#" @click="change_type">Use Cloud Server</a>
                    </div>
                </div>
                <div class="dmiux_popup__foot">
                    <div class="dmiux_grid-row">
                        <div class="dmiux_grid-col"></div>
                        <div class="dmiux_grid-col dmiux_grid-col_auto">
                            <button class="dmiux_button dmiux_button_secondary dmiux_popup__close_popup" type="button" @click="modalClose()">Cancel</button>
                        </div>
                        <div class="dmiux_grid-col dmiux_grid-col_auto pl-0">
                            <button class="dmiux_button" type="button" @click="submit_server();">Submit</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</script>

<script>
    var add_server = Vue.component('add-server', {
        template: '#add-server-modal-template',
        props: [ "configurations", "server", "editing", "open"],
        data: function() {
            return {
                local_start_time: "",
                local_end_time: "",
                week_days: [
                    "sunday",
                    "monday",
                    "tuesday",
                    "wednesday",
                    "thursday",
                    "friday",
                    "saturday"
                ]
            }
        },
        watch: {
            open(value) {
                if (value) {
                    this.modalOpen();
                }
                else {
                    this.modalClose();
                }
            }
        },
        computed: {
            server_is_default() {
                return this.server.is_default;
            }
        },
        methods: {
            change_type: function()
            {
                if(this.server.type == "ex") {
                    this.server.type = "do";
                }
                else {
                    this.server.type = "ex";
                }
            },
            submit_server: function() {
                app.loading(true);

                this.server.name = this.server.name.replace(/\W/g, '').toLowerCase();

                if(this.server.name == "")
                {
                    alert("You must provide all required arguments");
                    app.loading(false);
                    return;
                }

                if(!StringHelper.isEmpty(this.server.start_day) || !StringHelper.isEmpty(this.server.end_day) || !StringHelper.isEmpty(this.server.start_time_no_timezone) || !StringHelper.isEmpty(this.server.end_time_no_timezone)) {
                    if(this.server.start_day == "" || this.server.start_day == null || this.server.end_day == "" || this.server.end_day == null || this.server.start_time_no_timezone == "" || this.server.start_time_no_timezone == null || this.server.end_time_no_timezone == "" || this.server.end_time_no_timezone == null) {
                        alert("You must enter values for all maintenance window fields.");
                        app.loading(false);
                        return;
                    }
                }

                if(this.server.type == "do")
                {
                    if(this.server.server_provider_configuration_id == "")
                    {
                        alert("You must provide all required arguments");
                        app.loading(false);
                        return;
                    }

                    if(this.server.groups.length > 0){
                        let applyValidation = this.validateIPAddressWithMask;
                        let problematic_ips = [];
                        let duplicate_group_ips = [];
                        let group_num = 0;

                        this.server.groups.forEach(function(group) {
                            let dupe_check = [];
                            let duplicate_ips = [];
                            let ips = group.ips.split(',');
                            group_num++;

                            ips.forEach(function(ip) {
                                ip = ip.trim();

                                if(dupe_check.includes(ip)) {
                                    duplicate_ips.push(ip);
                                } else if(! applyValidation(ip) || ip == '') {
                                    problematic_ips.push(ip);
                                }

                                dupe_check.push(ip);
                            })

                            if(duplicate_ips.length > 0) {
                                duplicate_group_ips[group_num] = duplicate_ips;
                            }

                            return;
                        });

                        if(problematic_ips.length > 0){
                            alert("One or more IPs are invalid or empty:\n\n" + problematic_ips.join("\n"));
                            app.loading(false);
                            return;
                        }

                        if(Object.keys(duplicate_group_ips).length > 0) {
                            let string = "Duplicate IPs detected in same group:\n";
                            for(let i = 1; i <= Object.keys(duplicate_group_ips).length; i++) {
                                let test = duplicate_group_ips[i];
                                string += 'Group ' + i + ': ' + duplicate_group_ips[i].join(', ') + '\n';
                            }
                            alert(string);
                            app.loading(false);
                            return;
                        }
                    }
                }

                if(this.server.type == "ex" && (this.server.hostname == "" || this.server.port == "" || this.server.username == "" || this.server.default_database == "" || this.server.driver == ""))
                {
                    alert("You must provide all required arguments");
                    app.loading(false);
                    return;
                }

                let options = FetchHelper.buildJsonRequest({
                    hostname: this.server.hostname,
                    name: this.server.name,
                    username: this.server.username,
                    password: this.server.password,
                    default_database: this.server.default_database,
                    port: this.server.port,
                    driver: this.server.driver,
                    server_provider_configuration_id: this.server.server_provider_configuration_id,
                    type: this.server.type,
                    id: this.server.id,
                    groups: this.server.groups,
                    start_day: this.server.start_day,
                    end_day: this.server.end_day,
                    start_time: this.server.start_time_no_timezone,
                    end_time: this.server.end_time_no_timezone,
                    is_default: this.server.is_default
                }, this.editing ? 'put' : 'post');

                if(this.editing)
                {
                    app.servers[app.activeServerIndex].status = 'modified';
                    fetch(baseUrl + "/internal-api/v1/admin/servers/" + this.server.id, options)
                        .then(FetchHelper.handleJsonResponse)
                        .then(json => {
                            app.servers[app.activeServerIndex].status = '';
                            app.loading(false);
                            app.getServers();

                            this.modalClose();
                        })
                        .catch((error) => {
                            app.loading(false);
                            ResponseHelper.handleErrorMessage(error, "Server was unable to be updated.");
                        });
                }
                else
                {
                    fetch(baseUrl + "/internal-api/v1/admin/servers", options)
                        .then(FetchHelper.handleJsonResponse)
                        .then(json => {
                            app.loading(false);

                            app.getServers();
                            if(this.server.type == "do") {
                                alert("Your new server is being created and should be available within 10 minutes.");
                            }

                            this.modalClose();
                        })
                        .catch((error) => {
                            app.loading(false);
                            ResponseHelper.handleErrorMessage(error, "Server was unable to be added.");
                        });
                }
            },
            addGroup: function ()
            {
                this.server.groups.push({ ips: '', notes: ''});
            },
            removeGroup: function (index)
            {
                if(! confirm('Are you sure you want to remove this IP group?')) {
                    return;
                }

                this.server.groups.splice(index, 1);
            },
            validateIPAddressWithMask (input){
                let regex = new RegExp(/^((\b|\.)(0|1|2(?!5(?=6|7|8|9)|6|7|8|9))?\d{1,2}){4}(\/(((?!00)(0|1|2|3(?=0|1|2))\d|\d)))?$/);

                return regex.test(input);
            },
            cleanupName: function(){
                this.server.name = this.server.name.substring(0, 200);
            },
            changeStartTime() {
                var time = this.$parent.convertTime(this.server.start_time_no_timezone + '+00');
                if (time !== "") {
                    this.local_start_time = time + " local time";
                    return;
                }
                this.local_start_time = "";
            },
            changeEndTime() {
                var time = this.$parent.convertTime(this.server.end_time_no_timezone + '+00');
                if (time !== "") {
                    this.local_end_time = time + " local time";
                    return;
                }
                this.local_end_time = "";
            },
            hasMaintenanceWindow() {
                if(!StringHelper.isEmpty(this.server.start_day) || !StringHelper.isEmpty(this.server.end_day) || !StringHelper.isEmpty(this.server.start_time_no_timezone) || !StringHelper.isEmpty(this.server.end_time_no_timezone)) {
                    return true;
                }
                else {
                    return false;
                }
            },
            clearMaintenanceWindow() {
                this.server.start_day = '';
                this.server.start_time = null;
                this.server.start_time_no_timezone = null;
                this.server.end_day = '';
                this.server.end_time = null;
                this.server.end_time_no_timezone = null;
            },
            updateDefaultStatus(val) {
                this.server.is_default = ! this.server.is_default;
            },
            modalOpen() {
                openModal("#modal-add_server");
                $(document).on("mousedown", "#dmiux_body", this.modalClose);
                $(document).on("keydown", this.modalClose);
                this.changeStartTime(); 
                this.changeEndTime(); 
            },
            modalClose(event = undefined) {
                if (event !== undefined) {
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
                // You clicked outside the modal
                $(document).off("mousedown", "#dmiux_body", this.modalClose);
                $(document).off("keydown", this.modalClose);

                // execute any special logic to reset/clear modal
                closeModal('#modal-add_server');
                this.$parent.getServers();
                this.$parent.modals.add_server = false;
            }
        }
    });
</script>
