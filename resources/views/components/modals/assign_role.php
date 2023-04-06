<script type="text/x-template" id="assign-role-modal-template">
    <div class="dmiux_popup" id="modal-assign_role" role="dialog" tabindex="-1">
        <div class="dmiux_popup__window dmiux_popup__window_lg" role="document">
            <div class="dmiux_popup__head">
                <h4 class="dmiux_popup__title">Deleting "{{role_detail.role_name}}" Role - Select a New Role</h4>
                <button type="button" class="dmiux_popup__close"></button>
            </div>
            <div class="dmiux_popup__cont">
                <h6>The <span v-if="role_detail.user_count > 1">users</span><span v-else>user</span> below <span v-if="role_detail.user_count > 1">are</span><span v-else>is</span> currently assigned to this role. Please select a new role for <span v-if="role_detail.user_count > 1">these users</span><span v-else>this user</span>.</h6>
                <table id="assign_role_table" class="dmiux_data-table__table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th> <span v-if="role_detail.product_name == 'warehouse'">Database</span><span v-else> Project</span></th>
                            <th>Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="metadata in role_detail.user_metadata">
                            <td>{{metadata.user.name ?? metadata.user.user_handle}}</td>
                            <td> <span v-if="role_detail.product_name == 'studio'">{{metadata.product_child.name}}</span><span v-else>{{ metadata.product_child.database }}</span></td>
                            <td>
                                <div class="dmiux_select">
                                    <select class="dmiux_select__select dropdown-roles select-roles-dropdown" @change="changeRole(metadata.user.id, metadata.product_child.id, $event)" :id="'input-role-' + metadata.product_child.id" >
                                        <option value="" selected>Choose a Role</option>
                                        <option v-for="roles in role_detail.roles" :value="roles.id" >{{ roles.role_name }}</option>
                                    </select>
                                    <div class="dmiux_select__arrow"></div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>     
            </div>
            <div class="dmiux_popup__foot">
                <div class="dmiux_grid-row">
                    <div class="dmiux_grid-col"></div>
                    <div class="dmiux_grid-col dmiux_grid-col_auto">
                        <button class="dmiux_button dmiux_button_secondary dmiux_popup__close_popup" data-dismiss="modal" type="button">Cancel</button>
                    </div>
                    <div class="dmiux_grid-col dmiux_grid-col_auto pl-0">
                        <button class="dmiux_button" type="button" @click="moveUsers()">Update <span v-if="role_detail.roles_count > 1">Roles</span><span v-else>Role</span></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>

<script>
    var assignRole = Vue.component('assignRole', {
        template: "#assign-role-modal-template",
        props: ['role_detail'],
        data: function () {
            return {
                new_roles: []
            }
        },
        methods: {
            changeRole: function(user_id, product_child_id, event) {
                var role_id = event.target.value;
                for (var i=0; i<this.new_roles.length; i++) {
                    if (this.new_roles[i].user_id == user_id && this.new_roles[i].product_child_id == product_child_id) {
                        this.new_roles[i].role_id = role_id;
                        return;
                    }
                }
                this.new_roles.push({
                    "user_id": user_id,
                    "product_child_id": product_child_id,
                    "role_id": role_id,
                });
            },
            moveUsers: function() {
                if ($(".select-roles-dropdown option:selected[value='']").length > 0) {
                    notify.danger(`Please select role for user(s)`);
                    return;
                }
                
                let options = FetchHelper.buildJsonRequest({
                    new_roles: this.new_roles
                }, 'DELETE');
    
                this.$root.loading(true);
                fetch(`/internal-api/v1/roles/move/${this.role_detail.role_id}`, options)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        notify.success(json.message);
                        this.$root.getRolePerms();
                        this.$root.loading(false);
                        this.new_roles = [];
                        $('.dmiux_popup__close_popup').trigger('click');
                    })
                    .catch((error) => {
                        this.$root.loading(false);
                        ResponseHelper.handleErrorMessage(error, 'Failed to move users.');
                    });
            },
        }
    });
</script>