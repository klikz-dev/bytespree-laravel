<?php echo view("components/head"); ?>
<?php echo view("components/component-toolbar"); ?>
<?php echo view("components/modals/add_server"); ?>
<div id="app">
    <toolbar
        :buttons="toolbar.buttons"
        :breadcrumbs="toolbar.breadcrumbs">
    </toolbar>
    <div class="dmiux_content">
        <?php echo view('components/admin/menu', ['selected' => 'servers']); ?>
        <div @scroll="hideShowArrows()" class="dmiux_data-table dmiux_data-table__cont" id="servers_list">
            <button type="button" class="dmiux_data-table__arrow dmiux_data-table__arrow_left server_left" onclick="scroll_left('servers_list', 'server_')"><i></i></button>
            <button type="button" class="dmiux_data-table__arrow dmiux_data-table__arrow_right server_right" onclick="scroll_right('servers_list', 'server_')"><i></i></button>
            <table v-if="currentUser.is_admin == true" v-if="servers.length > 0" id="server-data" class="dmiux_data-table__table">
                <thead>
                    <tr>
                        <th></th>
                        <th>Server Name</th>
                        <th>Hostname</th>
                        <th class="text-right">Total Disk Space</th>
                        <th class="text-right">Total Memory</th>
                        <th class="text-right"># CPUs</th>
                        <th class="text-right">Available Nodes</th>
                        <th v-if="showPrice" class="text-right">Cost Per Month</th>
                        <th>Last Modified</th>
                        <th>Starting Day</th>
                        <th>Starting Hour</th>
                        <th>Ending Day</th>
                        <th>Ending Hour</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(server, index) in servers" class="dmiux_input">
                        <td style="width: 20px !important;">
                            <div class="dmiux_data-table__actions">
                                <div v-if="server.status == '' || server.status == 'online'" class="dmiux_actionswrap dmiux_actionswrap--edit" @click="editServer(server,index)" data-toggle="tooltip" title="Toggle Edit Server"></div>
                                <div v-if="server.status == '' || server.status == 'online'" class="dmiux_actionswrap dmiux_actionswrap--bin" @click="removeServer(server.id)" data-toggle="tooltip" title="Delete Server"></div>
                            </div>
                        </td>
                        <td>{{ server.name }}</td>
                        <td>{{ server.hostname }}</td>
                        <td class="text-right">{{ server.storage }}</td>
                        <td class="text-right">{{ server.memory }}</td>
                        <td class="text-right">{{ server.cpus }}</td>
                        <td class="text-right">{{ server.nodes }}</td>
                        <td v-if="showPrice" class="text-right">{{ server.resale_price }}</td>
                        <td>{{ server.formatted_updated_at }}</td>
                        <td>{{ formatDay(server.shown_start_day) }}</td>
                        <td>{{ convertTime(server.start_time) }}</td>
                        <td>{{ formatDay(server.shown_end_day) }}</td>
                        <td>{{ convertTime(server.end_time) }}</td>
                    </tr>
                </tbody>
            </table>
            <div v-else class="alert alert-info mt-2">There are no servers yet. <a href="javascript:void(0)" @click="add_server();">Add a server</a>.</div>
        </div>
    </div>
    <add-server :configurations="configurations"
                :server="selected_server"
                :editing="editing"
                :open="modals.add_server">
    </add-server>
</div>
<script>
    var toolbar = Vue.component('toolbar', {
        template: '#component-toolbar',
        props: [ 'breadcrumbs', 'buttons', 'record_counts' ],
        methods: {
        }
    });

    var app = new Vue({
        el: '#app',
        name: 'ServerManagement',
        data: {
            toolbar: {
                "breadcrumbs": [],
                "buttons": [
                    {
                        "onclick": "app.addServer()",
                        "text": "Add Server&nbsp; <span class=\"fas fa-plus\"></span>",
                        "class": "dmiux_button dmiux_button_secondary"
                    }
                ]
            },
            servers : [],
            selected_server: {},
            configurations: [],
            editing: false,
            currentUser : {
                "is_admin" : false
            },
            locked: false,
            activeServerIndex: 0,
            team_details:{},
            modals: {
                add_server: false
            },
            showPrice: false
        },
        components: {
            'toolbar': toolbar,
            'add_server': add_server
        },
        methods: {
            loading: function(status) {
                if(status === true) {
                    $(".loader").show();
                }
                else {
                    $(".loader").hide();
                }
            },
            checkForError: function (json) {
                if (json.status == "error") {
                    alert(json.message);
                    return false;
                }
                else {
                    return true;
                }    
            },
            getServers: function() {
                this.loading(true);
                fetch(baseUrl + "/internal-api/v1/admin/servers")
                .then(response => response.json())
                .then(json => {
                    this.servers = json.data;

                    for(i = 0; i < this.servers.length; i++) 
                    {
                        if (this.servers[i].start_day === null)
                            this.servers[i].start_day = "";

                        if (this.servers[i].end_day === null)
                            this.servers[i].end_day = "";

                        var start_time = "";
                        var end_time = "";

                        if (this.servers[i].start_time != null) {
                            start_time = this.servers[i].start_time.split(/\+|\-/);
                            start_time = start_time[0];
                        }
                        if (this.servers[i].end_time != null) {
                            end_time = this.servers[i].end_time.split(/\+|\-/);
                            end_time = end_time[0];
                        }

                        this.servers[i].start_time_no_timezone = start_time;
                        this.servers[i].end_time_no_timezone = end_time;

                        this.servers[i].changed = null; 
                        this.servers[i].formatted_updated_at = DateHelper.formatLocaleCarbonDate(this.servers[i].updated_at);

                        if (this.showPrice == false && this.servers[i].resale_price != null && this.servers[i].resale_price != "") {
                            this.showPrice = true;
                        }
                    }
                    this.loading(false);
                })
            },
            getCurrentUser: function() {
                fetch(baseUrl + "/internal-api/v1/me")
                .then(response => response.json())
                .then(json => {
                    this.currentUser = json.data; 
                })
            },
            getBreadcrumbs: function() {
                fetch(baseUrl + "/internal-api/v1/crumbs")
                    .then(response => response.json())
                    .then(json => {
                        this.toolbar.breadcrumbs = json.data;
                    });
            },
            getProviderConfigurations: function() {
                fetch(baseUrl + "/internal-api/v1/admin/servers/configurations")
                    .then(response => response.json())
                    .then(json => {
                        this.configurations = json.data;
                });
            },
            addServer: function() {
                this.editing = false;
                this.selected_server = { 
                    type: "do", 
                    name: "",
                    hostname: "",
                    username: "",
                    password: "",
                    default_database: "",
                    port: "",
                    driver: "",
                    server_provider_configuration_id: "",
                    id: "",
                    groups: [],
                    start_day: "",
                    start_time: "",
                    start_time_no_timezone: "",
                    end_day: "",
                    end_time: "",
                    end_time_no_timezone: ""
                };
                this.modals.add_server = true;
            },
            editServer: function(selServer, index=0) {
                this.activeServerIndex = index;
                this.editing = true;
                this.selected_server = selServer;
                if(this.selected_server.server_provider_configuration_id != null)
                {
                    this.selected_server.type = "do";
                }
                else {
                    this.selected_server.type = "ex";
                    this.selected_server.password = "";
                }
                this.modals.add_server = true;
            },
            removeServer: function(id) {
                var check = prompt("Are you sure you want to delete this server? Deleting this server will delete all databases and Studio projects hosted on it? To confirm this irreversible decision, type DELETE in the input below.")

                if(check != null && check.toLowerCase() == "delete")
                {
                    this.loading(true);
                    fetch(baseUrl + "/internal-api/v1/admin/servers/" + id, FetchHelper.buildJsonRequest({}, 'delete'))
                    .then(response => response.json())
                    .then(json => {
                        if(!app.checkForError(json)) {
                            this.loading(false);
                            return;
                        }
                        app.getServers();
                        this.loading(false);
                    })
                }        
            },
            convertTime(time) {
                return DateHelper.formatLocaleTimeString(time);
            },
            hideShowArrows() {
                BytespreeUiHelper.hideShowArrows("servers_list", "server_");
            },
            formatDay(day) {
                if(day == null) 
                    return ""
                else 
                    return day.charAt(0).toUpperCase() + day.slice(1);
            },
            getTeamDetails(){
                fetch(baseUrl + "/internal-api/v1/team")
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.team_details = json.data;
                    })
                    .catch((error) => {
                        ResponseHelper.handleErrorMessage(error, "Unable to get team details.");
                    });
            }
        },
        mounted: function() {
            this.loading(true);
            this.getCurrentUser();
            this.getServers();
            this.getBreadcrumbs();
            this.getProviderConfigurations();
            this.getTeamDetails();
        },
        updated() {
            this.$nextTick().then(this.hideShowArrows('mapping-modal'));
        }
    });
</script>
<?php echo view("components/foot"); ?>
