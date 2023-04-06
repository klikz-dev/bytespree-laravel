<script type="text/x-template" id="user-modal-template">
    <!-- User Modal -->
    <div class="dmiux_popup" id="modal-user">
        <div class="dmiux_popup__window dmiux_popup__window_lg">
            <div class="dmiux_popup__head">
                <h4 class="dmiux_popup__title">Manage {{ user.name ?? user.user_handle }}'s Database Access</h4>
                <button type="button" class="dmiux_popup__close"></button>
            </div>
            <form id="form-edit_user" autocomplete="off" onSubmit="event.preventDefault()">
                <div class="dmiux_popup__cont">
                    <div v-if="!is_admin && $root.selectedUser.is_admin == true" class="alert alert-warning mt-4">Demoting a user to a non-admin will strip the demoted user of all privileges unless defined below.</div>
                    <div class="dmiux_checkbox">
                        <input @click="checkProjects"
                            v-model="is_admin"
                            value="1"
                            name="is_admin"
                            type="checkbox"
                            id="is_admin"
                            class="dmiux_checkbox__input">
                        <div class="dmiux_checkbox__check"></div>
                        <div class="dmiux_checkbox__label">Team Admin?</div>
                    </div>
                    <div class="dmiux_data-table dmiux_data-table__cont" v-if="is_admin == false || is_admin == 'false'">
                        <div class="mb-3">
                            <div>User Permissions</div>
                            <div v-for="permission in user_permissions" v-if="isProductEnabled(permission.product_id) || (isInternalUser() && $root.isCurrentUserInternal())" class="dmiux_checkbox">
                                <input @click="clickPermission"
                                    v-model="permission.value"
                                    type="checkbox"
                                    :id="'up_' + permission.name"
                                    class="dmiux_checkbox__input">
                                <div class="dmiux_checkbox__check"></div>
                                <div :for="'up_' + permission.name" class="dmiux_checkbox__label">{{ permission.description }}</div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div>Product</div>
                            <div class="dmiux_select">
                                <select class="dmiux_select__select" v-model="selectedProduct">
                                    <option v-for="(product, index) in products" v-if="isProductEnabled(product.id) || (isInternalUser() && $root.isCurrentUserInternal())" :selected="selectedProduct == product" :value="product">{{ product.display_name }}</option>
                                </select>
                                <div class="dmiux_select__arrow"></div>
                            </div>
                        </div>
                        <template v-if="selectedProduct.name == 'studio'">
                            <table id="custom_column_table" class="dmiux_data-table__table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Roles</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="project in projects">
                                        <td class="user-modal-overflow_text" :title="project.display_name">{{ project.display_name }}</td>
                                        <td>
                                            <div class="dmiux_select">
                                                <select class="dmiux_select__select dropdown-roles" @change="addUserProject(project.id, $event)" id="input-role">
                                                    <option disabled>Choose a Role</option>
                                                    <option value="">No Role</option>
                                                    <option v-for="role in roles.studio" :value="role.id" :selected="hasRole(project.id, role.id)">{{ role.role_name }}</option>
                                                </select>
                                                <div class="dmiux_select__arrow"></div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </template>
                        <template v-if="selectedProduct.name == 'datalake'">
                            <table id="custom_column_table" class="dmiux_data-table__table">
                                <thead>
                                    <tr>
                                        <th>Database</th>
                                        <th>Roles</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="datalake in datalakes">
                                        <td>{{ datalake.database }}</td>
                                        <td>
                                            <div class="dmiux_select">
                                                <select class="dmiux_select__select dropdown-roles" @change="addUserProject(datalake.id, $event)" id="input-role">
                                                    <option disabled>Choose a Role</option>
                                                    <option value="">No Role</option>
                                                    <option v-for="role in roles.datalake" :value="role.id" :selected="hasRole(datalake.id, role.id)">{{ role.role_name }}</option>
                                                </select>
                                                <div class="dmiux_select__arrow"></div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </template>
                    </div>
                    <div v-else-if="is_admin && $root.selectedUser.is_admin == false" class="alert alert-warning mt-4">Promoting a user to a team admin will purge any existing, individual permissions for this user. Team admins have unlimited permissions within Bytespree.</div>
                    <div class="alert alert-info mt-4" v-else>Team admins have unlimited access to all databases.</div>
                </div>

                <div class="dmiux_popup__foot">
                    <div class="dmiux_grid-row">
                        <div class="dmiux_grid-col"></div>
                        <div class="dmiux_grid-col dmiux_grid-col_auto">
                            <button class="dmiux_button dmiux_button_secondary dmiux_popup__close_popup" data-dismiss="modal" type="button">Close</button>
                        </div>
                        <div class="dmiux_grid-col dmiux_grid-col_auto pl-0">
                            <button class="dmiux_button" @click="saveUser()" type="button">Submit</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</script>

<script>
    var userModal = Vue.component('user-modal', {
        template: '#user-modal-template',
        props: [ "projects", "datalakes", "user", "userProjects"],
        data() {
            return {
                products: [],
                roles: {
                    studio: [],
                    datalake: []
                },
                selectedProduct: {},
                permissions_changed: false,
                user_permissions: [],
                is_admin: false,
                is_redirecting: false
            }
        },
        methods: {
            isInternalUser() {
                if(this.user.email != undefined) {
                    if(this.user.email.toLowerCase().indexOf("@rkdgroup.com") != -1 || this.user.email.toLowerCase().indexOf("@data-management.com") != -1)
                        return true;
                    else 
                        return false;
                }

                return false;
            },
            isProductEnabled(id) {
                let product = this.products.filter((product) => {
                    if(product.id == id) 
                        return product;
                });

                if(product.length > 0) {
                    if(product[0].is_enabled)
                        return true;
                    else
                        return false;
                }
                else {
                    return false;
                }
            },
            addProject (pid) {
                userProjects.push(pid);
            },
            addRole (event) {
                app.userAddRole = event.target.value;
            },
            checkProjects(event) {
                this.initDropdowns(event.target.checked);
            },
            clickPermission(event) {
                this.permissions_changed = true;
            },
            initDropdowns(is_admin){
                var dropdown = document.querySelectorAll(".dropdown-roles");
                this.is_admin = is_admin;
                if (is_admin) {
                    for(var i=0; i < dropdown.length; i++) {
                        dropdown[i].disabled = true;
                        dropdown[i].title = "Disabled because user is admin.  Administrators see everything.";
                    }
                }
                else {
                    for(var i=0; i < dropdown.length; i++) {
                        dropdown[i].disabled = false;
                        dropdown[i].title = "";
                    }
                }
            },
            saveUser() {
                this.$parent.loading(true);

                let options = FetchHelper.buildJsonRequest({
                        is_admin : this.is_admin,
                        projects : this.userProjects,
                        permissions_changed : this.permissions_changed,
                        permissions : this.user_permissions
                }, 'put');

                fetch(`${baseUrl}/internal-api/v1/admin/users/${this.user.id}/permissions`, options)
                    .then(response => {
                        this.$parent.loading(false);
                        return response;
                    })
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        if (json.data.redirect === true) {
                            this.is_redirecting = true;
                            window.location = json.data.redirect_location + "?message=" + encodeURIComponent('Your permissions have been updated.');
                        } else {
                            notify.send(json.message, 'success');
                        }
                    })
                    .then(response => {
                        this.closeUserModal();
                    })
                    .catch((error) => {
                        this.closeUserModal();
                        ResponseHelper.handleErrorMessage(error, 'Error occurred; user not updated');
                    });
            },
            hasRole (product_child_id, role_id) {
                for(var i = 0; i < this.userProjects.length; i++) {
                    if(this.userProjects[i].role_id == role_id 
                    && this.userProjects[i].product_child_id == product_child_id) {
                        return true;
                    }
                }
                return false;
            },
            addUserProject(product_child_id, event) {
                var role_id = event.target.value;
                for(var i = 0; i < this.userProjects.length; i++) {
                    if(this.userProjects[i].product_id === this.selectedProduct.id && this.userProjects[i].product_child_id == product_child_id) {
                        this.userProjects[i].role_id = role_id
                        return;
                    }
                }
                this.userProjects.push({
                    "product_id": this.selectedProduct.id,
                    "product_child_id": product_child_id,
                    "role_id": role_id
                });
            },
            closeUserModal () {
                $('.dmiux_popup__close_popup').trigger('click');
                if(! this.is_redirecting){
                    app.getUsers();
                }
            }, 
            getProducts() {
                fetch(baseUrl + "/internal-api/v1/products")
                    .then(response => response.json())
                    .then(json => {
                        if(json.status && json.status === "error") {
                            alert(json.message);
                        }
                        else {
                            this.products = json.data;
                            if (this.products.length > 0) {
                                this.selectedProduct = this.products[0];
                            }
                            this.getRolesForProductId();
                        }
                    });
            },
            getRolesForProductId() {
                for(var i=0; i<this.products.length; i++) {
                    app.loading(true);
                    fetch(baseUrl + `/internal-api/v1/roles?product_id=${this.products[i].id}`)
                        .then(response => response.json())
                        .then(json => {
                            var roles = json.data;
                            if (roles.length > 0) {
                                this.roles[roles[0].product.name] = roles;
                            }
                            app.loading(false);
                        });
                }
            },
            getUserPermissions(user_id) {
                fetch(baseUrl + `/internal-api/v1/admin/users/${user_id}/permissions`)
                    .then(response => response.json())
                    .then(json => {
                        this.user_permissions = json.data;
                    })
            }
        },
        mounted() {
            this.getProducts();
        }
    });
</script>