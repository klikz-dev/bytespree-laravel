<?php echo view("components/head"); ?>
<?php echo view("components/component-toolbar"); ?>
<?php echo view("components/modals/user"); ?>
<?php echo view("components/modals/invite_user"); ?>
<div id="app">
    <toolbar
        :buttons="toolbar.buttons"
        :breadcrumbs="toolbar.breadcrumbs">
    </toolbar>
    <div class="dmiux_content">
        <?php echo view('components/admin/menu', ['selected' => 'users']); ?>
        <div class="dmiux_grid-cont dmiux_grid-cont_fw">
            <div class="dmiux_data-table">
                <div class="dmiux_data-table__cont">
                    <table v-show="currentUser.is_admin == true" id="users-data" class="dmiux_data-table__table">
                        <thead>
                            <tr>
                                <th></th>
                                <th class="width-70">User</th>
                                <th>Team Admin?</th>
                                <th>Notifications</th>
                                <th>Dual Factor Authentication</th>
                                <th>Last Modified</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="user in users">
                                <td>
                                    <div class="dmiux_data-table__actions">
                                        <div v-if="user.is_pending == false">
                                            <div class="dmiux_actionswrap dmiux_actionswrap--bin" @click="removeUser(user)" data-toggle="tooltip" title="Remove User"></div>
                                        </div>
                                        <div v-else>
                                            <div class="dmiux_actionswrap dmiux_actionswrap--bin" @click="removeInvitedUser(user)" data-toggle="tooltip" title="Rescind Invitation"></div>
                                        </div>
                                    </div>
                                </td>
                                <td v-if="user.is_pending == false">
                                    <a href="#" @click="openUserModal(user)">{{ user.name }}</a>
                                </td>
                                <td v-else data-toggle="tooltip" title="Invitation Sent">
                                    <a href="#" @click="openUserModal(user)">{{ user.email }} (invited user)</a>
                                </td>
                                <td v-if="user.is_pending == false" class="text-center"> 
                                    <div v-if="user.is_admin == true || user.is_admin == 'true'">Yes</div>
                                    <div v-else>No</div>
                                </td>
                                <td v-else></td>
                                <td v-if="user.is_pending == false" class="text-center"> 
                                    <div v-if="user.is_admin == true || user.is_admin == 'true'">
                                        <label>
                                            <input class="tooltip-pretty" title="Receive notifications via email for all databases" type="checkbox" @click="updateNotificationStatus(user.id,user.send_database_job_failure_email)" v-model="user.send_database_job_failure_email">
                                        </label>
                                    </div>
                                    <div v-else></div>
                                </td>
                                <td v-else></td>
                                <td v-if="user.dfa_preference == ''" class="text-center">Disabled</td>
                                <td v-else class="text-center">Enabled</td>
                                <td v-if="user.is_pending == false" class="text-center">
                                    <span class="hidden">{{ user.updated_at }}</span>
                                    {{user.formatted_updated_at}}
                                </td>
                                <td v-else></td>
                            </tr>
                        </tbody>
                    </table>
                    <button type="button" class="dmiux_data-table__arrow dmiux_data-table__arrow_left"><i></i></button>
                    <button type="button" class="dmiux_data-table__arrow dmiux_data-table__arrow_right"><i></i></button>
                </div>
            </div>
        </div>
    </div>
    <user-modal ref="userModal" :projects="projects" :datalakes="datalakes" :user="selectedUser" :user-projects.sync="userProjects" :roles="roles" v-on:update:user-projects="userProjects = $event"></user-modal>
    <invite-user-modal ref="inviteUserModal"></invite-user-modal>
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
        name: "Users",
        data: {
            toolbar: {
                "breadcrumbs": [],
                "buttons": [
                    {
                        "onclick": "app.openInvitationModal()",
                        "text": "Add User&nbsp; <span class=\"fas fa-plus\"></span>",
                        "class": "dmiux_button dmiux_button_secondary"
                    }
                ]
            },
            users: [],
            deleted_users: [],
            invitedUsers: [],
            roles : [],
            currentUser : {
                "is_admin" : false
            },
            selectedUser : {
                "user_handle" : ""
            },
            projects: [],
            userProjects : [],   
            userAddRole: 4,
            dataTable : {},
            products : [],
            datalakes: [],
            selectedProduct: 0
        },
        components: {
            'toolbar': toolbar,
            'userModal' : userModal,
            'inviteUserModal' : inviteUserModal 
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
                this.loading(false);
                if (json.status == "error") {
                    notify.send(json.message, "danger");
                    return false;
                }
                return true;
            },
            getUsers: function() {
                this.loading(true);
                fetch(`${baseUrl}/internal-api/v1/admin/users`)
                    .then(response => {
                        this.loading(false);
                        return response;
                    })
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.users = json.data.users;
                        this.deleted_users = json.data.deleted_users;

                        for (var i=0; i<this.users.length; i++) {
                            this.users[i].formatted_updated_at = DateHelper.formatLocaleCarbonDate(this.users[i].updated_at);
                        }
                        $('#users-data').DataTable().destroy();
                    })
                    .then(() => {
                        $(".tooltip-pretty").tooltipster();
                        $('#users-data').DataTable({
                            "order": [[ 1, "asc" ]]
                        });
                    })
                    .catch((error) => {
                        ResponseHelper.handleErrorMessage(error, "Failed to get team users");
                    });
            },
            isCurrentUserInternal() {
                if(this.currentUser.email != undefined) {
                    if(this.currentUser.email.toLowerCase().indexOf("@rkdgroup.com") != -1 || this.currentUser.email.toLowerCase().indexOf("@data-management.com") != -1)
                        return true;
                    else 
                        return false;
                }

                return false;
            },
            updateNotificationStatus: function(user_id,status) {
                status = !status;
                this.loading(true);
                let options = FetchHelper.buildJsonRequest({
                    send_database_job_failure_email: status
                }, 'put');
                fetch(`${baseUrl}/internal-api/v1/admin/users/${user_id}`, options)
                    .then(response => response.json())
                    .then(json => {
                        if(json.status == 'ok'){
                            notify.send(json.message, "success");
                        }else{
                            notify.send(json.message, "danger");
                        }
                        this.loading(false);
                    });
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
            getProjects: function() {
                this.loading(true);
                fetch(baseUrl + "/internal-api/v1/explorer/projects")
                .then(response => response.json())
                .then(json => {
                    this.projects = json.data;
                    this.loading(false);
                });
            },     
            getUserProjects: function() {
                this.loading(true);
                fetch(baseUrl + `/internal-api/v1/admin/users/${this.selectedUser.id}/projects`)
                .then(response => response.json())
                .then(json => {
                    this.userProjects = json.data;
                    openModal("#modal-user");
                    this.loading(false);
                });
            },
            getDatalakes: function() {
                this.loading(true);
                fetch(baseUrl + "/internal-api/v1/data-lakes?source=admin")
                .then(response => response.json())
                .then(json => {
                    this.datalakes = json.data;
                    this.loading(false);
                });
            },
            openInvitationModal: function() {
                this.$refs.inviteUserModal.initFields();
                openModal("#modal-invite-user");
            },
            openUserModal: function(user) {
                this.loading(true);
                this.selectedUser = user;
                this.getUserProjects();
                this.$refs.userModal.initDropdowns(user.is_admin);
                this.$refs.userModal.getUserPermissions(user.id);
            },
            removeUser: function(user) {
                if (confirm("Are you sure you want to remove this user?")) {
                    this.loading(true);
                    fetch(baseUrl + "/internal-api/v1/admin/users/" + user.id, {method: 'delete'})
                        .then(response => response.json())
                        .then(json => {
                            var noError = this.checkForError(json);
                            if (noError && user.user_handle == this.currentUser.user_handle) {
                                window.location = "/auth/logout";
                            }
                            notify.send(json.message, "success");
                            this.getUsers();
                            this.loading(false);
                    });
                }
            },
            removeInvitedUser: function(user) {
                if (confirm("Are you sure you want to rescind the invitation to this email?")) {
                    this.loading(true);
                    fetch(baseUrl + "/internal-api/v1/admin/users/" + user.id, {method: 'delete'})
                        .then(response => {
                            this.loading(false);
                            return response;
                        })
                        .then(FetchHelper.handleJsonResponse)
                        .then(json => {
                            notify.send('Invitation rescinded.', "success");
                            this.getUsers();
                        })
                        .catch((error) => {
                            ResponseHelper.handleErrorMessage(error, "Failed: Invitation has not been removed");
                        });
                }
            }
        },
        mounted: function() {
            this.getCurrentUser();
            this.getUsers();
            this.getProjects();
            this.getDatalakes();
            this.getBreadcrumbs();
        }
    })
</script>
<?php echo view("components/foot"); ?>
