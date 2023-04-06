<?php echo view("components/head"); ?>
<?php echo view("components/component-toolbar"); ?>
<?php echo view("components/modals/add_microsoft_sql_server"); ?>
<?php echo view("components/modals/delete_confirmation"); ?>
<div id="app">
    <toolbar
        :buttons="toolbar.buttons"
        :breadcrumbs="toolbar.breadcrumbs">
    </toolbar>
    <div class="dmiux_content">
        <?php echo view("components/admin/menu", ['selected' => 'mssql-servers']); ?>
        <div v-if="currentUser.is_admin == true" class="dmiux_grid-cont dmiux_grid-cont_fw dmiux_data-table dmiux_data-table__cont">
            <table v-if="servers.length > 0" class="dmiux_data-table__table">
                <thead>
                    <tr>
                        <th></th> 
                        <th>Hostname</th>
                        <th>Port</th>
                        <th>Username</th>
                        <th>Last Modified</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(server, index) in servers" class="dmiux_input">
                        <td style="width: 20px !important;">
                            <div class="dmiux_data-table__actions">
                                <div class="dmiux_actionswrap--edit cursor-p" @click="edit(server)" data-toggle="tooltip" title="Edit SQL Server"></div>
                                <div class="dmiux_actionswrap--bin cursor-p" @click="remove(server.id, server.publishers)" data-toggle="tooltip" title="Delete SQL Server"></div>
                            </div>
                        </td>
                        <td>{{ server.data.hostname }}</td>
                        <td>{{ server.data.port }}</td>
                        <td>{{ server.data.username }}</td>
                        <td>{{ server.updated_at_formatted }}</td>
                    </tr>
                </tbody>
            </table>
            <div v-else class="alert alert-info mt-2">There are no Microsoft SQL Servers yet. <a href="javascript:void(0)" @click="modalOpen();">Add a Microsoft SQL Server</a>.</div>
        </div>
    </div>
    <add-microsoft_sql_server :server="server"
              :editing="editing">
    </add-microsoft_sql_server>

    <delete-confirmation ref="delete"
                         :subject="'Microsoft SQL Server'"
                         :type="'publishers in these project(s)'"
                         :controller="'/internal-api/v1/admin'"
                         :method="'mssql'"
                         :callback="getServers">
    </delete-confirmation>
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
        data: {
            toolbar: {
                "breadcrumbs": [
                    {   
                        title: "Microsoft SQL Servers",
                        location: "/Publishers/Mssql"
                    }
                ],
                "buttons": [
                    {
                        "onclick": "app.addServer()",
                        "text": "Add Microsoft SQL Server &nbsp; <span class=\"fas fa-plus\"></span>",
                        "class": "dmiux_button dmiux_button_secondary"
                    }
                ]
            },
            server: {},
            servers: [],
            editing: false,
            currentUser : {
                "is_admin" : false
            },
            locked: false
        },
        components: {
            'toolbar': toolbar,
            'add-microsoft_sql_server': add_microsoft_sql_server
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
            getCurrentUser: function() {
                fetch("/internal-api/v1/me")
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.currentUser = json.data;
                    })
            },
            getServers: function() {
                this.loading(true);
                fetch(`/internal-api/v1/admin/mssql`)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.servers = json.data;

                        this.servers = this.servers.map((server) => {
                            var formated_date = server.updated_at.replace(/-/g, '/');
                            server.updated_at_formatted = formatLocaleDateTimeString(formated_date);
                            return server;
                        });

                        this.loading(false);
                    })
                    .catch((error) => {
                        this.loading(false);
                        ResponseHelper.handleErrorMessage(error, 'Could not retrieve server list.');
                    });
            },
            addServer: function() {
                this.editing = false;
                this.server = { 
                    default_path: "", 
                    hostname: "",
                    username: "",
                    password: "",
                    port: "1433",
                    id: "",
                };
                this.modalOpen();
            },
            edit: function(server) {
                this.editing = true;
                server.password = '';
                server.username = server.data.username;
                server.port = server.data.port;
                server.hostname = server.data.hostname;
                this.server = server;
                this.modalOpen();
            },
            remove: function(id, data) {
                this.$refs.delete.id = id;
                this.$refs.delete.data = data;
                openModal("#modal-delete_confirmation");
            },
            modalOpen() {
                $(document).on("mousedown", "#dmiux_body", this.modalClose);
                $(document).on("keydown", this.modalClose);
                openModal("#modal-add-microsoft-server");
            },
            modalClose(event) {
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
                // You clicked outside the modal
                $(document).off("mousedown", "#dmiux_body", this.modalClose);
                $(document).off("keydown", this.modalClose);

                // execute any special logic to reset/clear modal
                closeModal('#modal-add-microsoft-server');
                this.getServers();
            }
        },
        mounted: function() {
            this.getCurrentUser();
            this.getServers();
        }
    })
</script>
<?php echo view("components/foot"); ?>
