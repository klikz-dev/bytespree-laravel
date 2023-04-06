<script type="text/x-template" id="component-widget-users">
    <form id="form-project_roles" autocomplete="off" onSubmit="event.preventDefault()">
        <div class="dmiux_grid-cont dmiux_grid-cont_fw" id="users">
            <button class="dmiux_button float-right mb-1" @click="saveUserToProject()" type="button">Save Users</button>
            <br>
            <br>
            <div class="dmiux_data-table dmiux_data-table__cont">
                <table id="widget-users-table" class="dmiux_data-table__table">
                    <thead>
                        <tr>
                            <th>Users</th>
                            <th>Assign Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="user in users">
                            <td>
                                <template v-if="user.name == null">{{ user.user_handle }} (invited user)</template>
                                <template v-else>{{ user.name }}</template>
                            </td>
                            <td>
                                <div v-if="user.is_admin != true" class="dmiux_select">
                                    <div v-if="($root.checkPerms('project_manage', true) || $root.checkPerms('project_grant')) && user.user_handle != $root.currentUser.user_handle">
                                        <select class="dmiux_select__select" @change="addUserRole(user.id, user.role_id, $event)">
                                            <option disabled>Choose a Role</option>
                                            <option value="">No Role</option>
                                            <option v-for="role in roles" :value="role.id" :selected="hasRole(user.id, role.id)">{{ role.role_name }}</option>
                                        </select>
                                        <div class="dmiux_select__arrow"></div>
                                    </div>
                                    <div v-else>
                                        {{ getRoleName(user.role_id) }}
                                    </div>
                                </div>
                                <div v-else>
                                        User is a Bytespree Admin
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </form>
</script>

<script>
    var widget_users = Vue.component('project-roles', {
        template: '#component-widget-users',
        data: function() {
            return {
                users: [],
                roles: [],
                user_roles: [],
                user_add_role: 0,
            }
        },
        methods: {
            getUsers() {
                this.$root.loading(true);
                fetch(`/internal-api/v1/studio/projects/${this.$root.project_id}/users`)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.users = json.data;
                        $("#widget-users-table").DataTable().destroy();
                    })
                    .then(() => {
                        $("#widget-users-table").DataTable({
                                "order": [[ 0, "asc" ]]
                            });
                        this.getRoles();
                    })
                    .catch((error) => {
                        ResponseHelper.handleErrorMessage(error, "Users could not be retrieved.");
                        this.$root.loading(false);
                    });
            },
            getRoles() {
                fetch(`/internal-api/v1/studio/projects/${this.$root.project_id}/roles`)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.roles = json.data;
                        this.setUserRoles();
                        this.$root.loading(false);
                    })
                    .catch((error) => {
                        this.$root.loading(false);
                    });
            },
            addRole: function (event) {
                this.user_add_role = event.target.value;
            },
            saveUserToProject: function () {
                let options = FetchHelper.buildJsonRequest({
                    "user_roles": this.user_roles
                }, 'put');
            
                this.$root.loading(true);
                fetch(`/internal-api/v1/studio/projects/${this.$root.project_id}/users`, options)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.$root.loading(false);
                        notify.success('User role changed successfully.');
                        $('.dmiux_popup__close_popup').trigger('click');
                        this.getUsers();
                    })
                    .catch((error) => {
                        this.$root.loading(false);
                        ResponseHelper.handleErrorMessage(error, "Users could not be saved.");
                    });
            },
            hasRole: function (user_id, role_id) {
                for(var i = 0; i < this.user_roles.length; i++) {
                    if(this.user_roles[i].role_id == role_id && this.user_roles[i].user_id == user_id) {
                        return true;
                    }
                }

                return false;
            },
            setUserRoles() {
                this.user_roles = this.users.map((user) => {
                    if(user.role_id != null) {
                        return {
                            'user_id': user.id,
                            'orig_role_id': user.role_id,
                            'role_id': user.role_id,
                            'action': 'upsert'
                        };
                    }
                }).filter((user_role) => { 
                    return user_role != undefined; 
                });
            },
            addUserRole: function(user_id, orig_role_id, event) {
                var role_id = event.target.value;
                for(var i = 0; i < this.user_roles.length; i++) {
                    if(user_id == this.user_roles[i].user_id && role_id == '') {
                        this.user_roles[i].action = 'delete';
                    } else if (user_id == this.user_roles[i].user_id) {
                        this.user_roles[i].role_id = role_id;
                    }
                }

                if(orig_role_id == null) {
                    this.user_roles.push({
                        'user_id': user_id,
                        'orig_role_id': 0,
                        'role_id': role_id,
                        'action': 'upsert'          
                    });
                }
            },
            getRoleName(role_id) {
                if(role_id == null)
                {
                    return "No Role";
                }
                let role_name_get = this.roles.filter(role => role.id == role_id)
                if(role_name_get.length > 0)
                {
                    return role_name_get[0].role_name;
                }
                else
                {
                    return "";
                }
                
            }
        },
        mounted() {
            this.$root.pageLoad();
            this.getUsers();
        }
    })
</script>