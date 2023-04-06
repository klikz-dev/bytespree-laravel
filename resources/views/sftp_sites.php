<?php echo view("components/head"); ?>
<?php echo view("components/component-toolbar"); ?>
<?php echo view("components/modals/add_sftp_site"); ?>
<?php echo view("components/modals/delete_confirmation"); ?>
<div id="app">
    <toolbar
        :buttons="toolbar.buttons"
        :breadcrumbs="toolbar.breadcrumbs">
    </toolbar>
    <div class="dmiux_content">
        <?php echo view('components/admin/menu', ['selected' => 'sftp-sites']); ?>
        <div v-if="currentUser.is_admin == true" class="dmiux_grid-cont dmiux_grid-cont_fw dmiux_data-table dmiux_data-table__cont">
            <table v-if="sftps.length > 0" class="dmiux_data-table__table">
                <thead>
                    <tr>
                        <th></th> 
                        <th>Default Path</th>
                        <th>Port</th>
                        <th>Hostname</th>
                        <th>Username</th>
                        <th>Last Modified</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(sftp, index) in sftps" class="dmiux_input">
                        <td style="width: 20px !important;">
                            <div class="dmiux_data-table__actions">
                                <div class="dmiux_actionswrap dmiux_actionswrap--edit" @click="editSite(sftp)" data-toggle="tooltip" title="Toggle Edit SFTP Site"></div>
                                <div class="dmiux_actionswrap dmiux_actionswrap--bin" @click="removeSite(sftp.id, sftp.publishers)" data-toggle="tooltip" title="Delete SFTP Site"></div>
                            </div>
                        </td>
                        <td>{{ sftp.default_path }}</td>
                        <td>{{ sftp.port }}</td>
                        <td>{{ sftp.hostname }}</td>
                        <td>{{ sftp.username }}</td>
                        <td>{{ sftp.updated_at_formatted }}</td>
                    </tr>
                </tbody>
            </table>
            <div v-else class="alert alert-info mt-2">There are no SFTP Sites yet. <a href="javascript:void(0)" @click="modalOpen();">Add an SFTP Site</a>.</div>
        </div>
    </div>
    <add-sftp :sftp="sftp"
              :editing="editing">
    </add-sftp>

    <delete-confirmation ref="delete"
                         :subject="'SFTP Site'"
                         :type="'publishers in these project(s)'"
                         :controller="'/internal-api/v1/admin'"
                         :method="'sftp'"
                         :callback="getSites">
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
                "breadcrumbs": [],
                "buttons": [
                    {
                        "onclick": "app.addSite()",
                        "text": "Add SFTP Site&nbsp; <span class=\"fas fa-plus\"></span>",
                        "class": "dmiux_button dmiux_button_secondary"
                    }
                ]
            },
            sftp: {},
            sftps: [],
            editing: false,
            currentUser : {
                "is_admin" : false
            },
            locked: false
        },
        components: {
            'toolbar': toolbar,
            'add_sftp': add_sftp,
            'delete_confirmation': delete_confirmation
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
                    notify.danger(json.message);
                    return false;
                }
                else {
                    return true;
                }    
            },
            getSites(on_success_callback) {
                this.loading(true);
                fetch("/internal-api/v1/admin/sftp")
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        let sftps = json.data;

                        for(i = 0; i < sftps.length; i++) 
                        {
                            sftps[i].changed = null;
                            var formated_date = sftps[i].updated_at.replace(/-/g, '/');
                            sftps[i].updated_at_formatted = formatLocaleDateTimeString(formated_date);
                        }

                        this.sftps = sftps;

                        this.loading(false);
                        if (on_success_callback !== undefined) {
                            on_success_callback();
                        }
                    })
                    .catch((error) => {
                        this.loading(false);
                        ResponseHelper.handleErrorMessage(error, 'SFTP sites could not be loaded.');
                    });
            },
            getCurrentUser: function() {
                fetch("/internal-api/v1/me")
                    .then(response => response.json())
                    .then(json => {
                        this.currentUser = json.data;
                    })
            },
            getBreadcrumbs: function() {
                fetch("/internal-api/v1/crumbs")
                    .then(response => response.json())
                    .then(json => {
                        this.toolbar.breadcrumbs = json.data;
                    });
            },
            addSite: function() {
                this.editing = false;
                this.sftp = { 
                    default_path: "", 
                    hostname: "",
                    username: "",
                    password: "",
                    port: "22",
                    id: "",
                };
                this.modalOpen();
            },
            editSite: function(sftp) {
                this.editing = true;
                sftp.password = '';
                this.sftp = sftp;
                this.modalOpen();
            },
            removeSite: function(id, data) {
                this.$refs.delete.id = id;
                this.$refs.delete.data = data;
                openModal("#modal-delete_confirmation");
            },
            modalOpen() {
                $(document).on("mousedown", "#dmiux_body", this.modalClose);
                $(document).on("keydown", this.modalClose);
                openModal("#modal-add-sftp");
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
                closeModal('#modal-add-sftp');
                this.getSites();
            }
        },
        mounted: function() {
            this.getCurrentUser();
            this.getSites();
            this.getBreadcrumbs();
        }
    })
</script>
<?php echo view("components/foot"); ?>
