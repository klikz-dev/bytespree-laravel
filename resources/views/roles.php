<?php echo view("components/head"); ?>
<?php echo view("components/component-toolbar"); ?>
<?php echo view("components/modals/add_role"); ?>
<?php echo view("components/modals/assign_role"); ?>
<div id="app">
    <toolbar
        :buttons="toolbar.buttons"
        :breadcrumbs="toolbar.breadcrumbs">
    </toolbar>
    <div class="dmiux_content">
        <?php echo view('components/admin/menu', ['selected' => 'roles']); ?>
        <div class="dmiux_grid-row">
            <div class="dmiux_grid-col dmiux_grid-col_25 dmiux_grid-col_lg-3 dmiux_grid-col_md-12">
                <div class="dmiux_block m-2">
                    <div class="dmiux_vtabs">
                        <div class="dmiux_vtabs__head">
                            <div class="dmiux_vtabs__title">Applications</div>
                        </div>
                        <div class="dmiux_vtabs__cont dmiux_noscrollbar">
                            <button v-for="product in products" v-if="isProductEnabled(product.id) || isCurrentUserInternal()" @click="tab = product.name" type="button" class="dmiux_vtabs__item" :class="tab == product.name ? 'dmiux_active' : ''">{{ product.display_name }}</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="dmiux_grid-col dmiux_grid-col_95 dmiux_grid-col_lg-9 dmiux_grid-col_md-12">
                <div class="dmiux_block m-2 dmiux_data-table dmiux_data-table__cont">
                    <div v-if="tab == 'datalake'">
                        <table v-if="currentUser.is_admin == true" class="dmiux_data-table__table">
                            <thead>
                                <tr>
                                    <th class="checkbox-header">Permission</th>
                                    <th v-for="data_lake_role in data_lake_roles" class="checkbox-header">
                                        <div class="dmiux_data-table__actions float-left">
                                            <div v-if="selected_role.id != -1 && selected_role.id != data_lake_role.id">
                                                <div class="dmiux_actionswrap dmiux_actionswrap--edit-white roles-edit-icon" @click="editRole(data_lake_role)" data-toggle="tooltip" title="Edit Role"></div>
                                                <div data-toggle="tooltip" title="Delete Role" class="dmiux_actionswrap dmiux_actionswrap--bin-white roles-delete-icon" @click="deleteRole(data_lake_role.id)"></div>
                                            </div>
                                            <div class="dmiux_data-table__actions">
                                                <div v-if="selected_role.id == data_lake_role.id">
                                                    <div class="dmiux_actionswrap dmiux_actionswrap--save-white" @click="saveRole(selected_role.id)" data-toggle="tooltip" title="Save Role"></div>
                                                </div>
                                                <div v-if="selected_role.id == data_lake_role.id">
                                                    <div class="dmiux_actionswrap dmiux_actionswrap--cancel-white" @click="cancelRole(selected_role.id)" data-toggle="tooltip" title="Cancel Edit"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div v-if="selected_role.id != data_lake_role.id">
                                            <p class="ml-5 mb-0">{{ data_lake_role.role_name }}</p>
                                        </div>
                                        <div v-if="selected_role.id == data_lake_role.id" class="min-width-200 ml-5 mr-1">
                                            <input class="dmiux_input__input" @input="cleanupRoleName('selected_role.role_name')" v-model="selected_role.role_name" id="name-edit" type="text" autocomplete="off" />
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="table-bordered">
                                <tr v-for="permission in permissions" v-if="permission.product.name == 'datalake' && permission.type != 'user'">
                                    <td>
                                        <div>
                                        <span class="tooltip-pretty fas fa-info-circle fa-lg mr-1 text-primary cursor-p" :title="permission.description"></span><span>{{ permission.name }}</span>
                                        </div>
                                    </td>
                                    <td v-for="data_lake_role in data_lake_roles">
                                        <div v-for="data_lake_role_permission in data_lake_role.permissions" v-if="data_lake_role_permission.name == permission.name && data_lake_role_permission.product.name == 'datalake'" class="dmiux_checkbox">
                                            <input type="checkbox" v-model="data_lake_role_permission.has_permission" class="dmiux_checkbox__input" :disabled="selected_role.id != data_lake_role.id">
                                            <div class="checkbox-center dmiux_checkbox__check"></div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-if="tab == 'studio'"> 
                        <table v-if="currentUser.is_admin == true" class="dmiux_data-table__table">
                            <thead>
                                <tr>
                                    <th class="checkbox-header">Permission</th>
                                    <th v-for="(studio_role, index) in studio_roles" class="checkbox-header">
                                        <div>
                                            <div class="dmiux_data-table__actions float-left">
                                                <div v-if="selected_role.id != -1 && selected_role.id != studio_role.id">
                                                    <div class="dmiux_actionswrap dmiux_actionswrap--edit-white roles-edit-icon" @click="editRole(studio_role)" data-toggle="tooltip" title="Edit Role"></div>
                                                    <div data-toggle="tooltip" title="Delete Role" class="dmiux_actionswrap dmiux_actionswrap--bin-white roles-delete-icon" @click="deleteRole(studio_role.id)"></div>
                                                </div>
                                                <div v-if="selected_role.id == studio_role.id">
                                                    <div class="dmiux_actionswrap dmiux_actionswrap--save-white" @click="saveRole(selected_role.id)" data-toggle="tooltip" title="Save Role"></div>
                                                </div>
                                                <div v-if="selected_role.id == studio_role.id">
                                                    <div class="dmiux_actionswrap dmiux_actionswrap--cancel-white" @click="cancelRole(selected_role.id)" data-toggle="tooltip" title="Cancel Edit"></div>
                                                </div>
                                            </div>
                                            <div v-if="selected_role.id != studio_role.id">
                                                <p class="ml-5 mb-0">{{ studio_role.role_name }}</p>
                                            </div>
                                            <div v-if="selected_role.id == studio_role.id" class="min-width-200 ml-5 mr-1">
                                                <input class="dmiux_input__input" @input="cleanupRoleName('studio_roles.role_name',index)" v-model="studio_role.role_name" id="name-edit" type="text" autocomplete="off" />
                                            </div>
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="table-bordered">
                                <tr v-for="permission in permissions" v-if="permission.product.name == 'studio' && permission.type != 'user'">
                                    <td>
                                        <div>
                                            <span class="tooltip-pretty fas fa-info-circle fa-lg mr-1 text-primary cursor-p" :title="permission.description"></span><span>{{ permission.name }}</span>
                                        </div>
                                    </td>
                                    <td v-for="studio_role in studio_roles">
                                        <div v-for="studio_role_permission in studio_role.permissions" v-if="studio_role_permission.name == permission.name && studio_role_permission.product.name == 'studio'" class="dmiux_checkbox">
                                            <input type="checkbox" v-model="studio_role_permission.has_permission" class="dmiux_checkbox__input" :disabled="selected_role.id != studio_role.id">
                                            <div class="checkbox-center dmiux_checkbox__check"></div>    
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>     
        </div>
    </div>
    <add-role :products="products"></add-role>
    <assign-role :role_detail="role_detail"></assign-role>


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
                        "onclick": "app.addRole();",
                        "text": "Add Role&nbsp; <span class=\"fas fa-plus\"></span>",
                        "class": "dmiux_button dmiux_button_secondary"
                    }
                ]
            },
            roles: [],
            studio_roles: [],
            data_lake_roles: [],
            permissions: [],
            currentUser : {
                "is_admin" : false
            },
            locked: false,
            selected_role: {
                product_id: 0
            },
            products: [],
            role_detail: [],
            tab: 'datalake'
        },
        components: {
            'toolbar': toolbar,
            'addRole': addRole,
            'assignRole': assignRole
        },
        methods: {
            loading: function(status) {
                if(status === true) {
                    $(".loader").show();
                } else {
                    $(".loader").hide();
                }
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
            isProductEnabled(id) {
                let product = this.products.filter((product) => {
                    if(product.id == id) 
                        return product;
                });

                if(product.length > 0) {
                    if(product[0].is_enabled == true) {
                        return true;
                    } else { 
                        return false;
                    }
                } else {
                    return false;
                }
            },
            getProducts: function() {
                fetch("/internal-api/v1/products")
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.products = json.data;
                    })
                    .catch((error) => {
                        this.loading(false);
                        ResponseHelper.handleErrorMessage(error, 'Could not fetch products.');
                    });
            },                   
            getRoles: function() {
                fetch("/internal-api/v1/roles")
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.roles = json.data;
                    })
                    .catch((error) => {
                        this.loading(false);
                        ResponseHelper.handleErrorMessage(error, 'Could not fetch roles.');
                    });
            },
            getPermissions: function() {
                fetch("/internal-api/v1/permissions")
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.permissions = json.data;
                        for (var i=0; i<this.permissions.length; i++) {
                            this.permissions[i].display_name = this.permissions[i].name.replace("_", "<br>");
                        }
                    })
                    .then(() => {
                        $(".tooltip-pretty").tooltipster();
                    })
                    .catch((error) => {
                        this.loading(false);
                        ResponseHelper.handleErrorMessage(error, 'Could not fetch permissions.');
                    });
            },
            getRolePerms: function() {
                fetch("/internal-api/v1/roles/permissions")
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.studio_roles = json.data.studio_roles;
                        this.data_lake_roles = json.data.data_lake_roles;
                        this.getPermissions();
                    })
                    .catch((error) => {
                        this.loading(false);
                        ResponseHelper.handleErrorMessage(error, 'Could not fetch role permissions.');
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
            cancelRole: function(role_id) {
                this.selected_role = {};
                this.getRolePerms();
            },
            editRole: function(role) {
                this.selected_role = role;
            },
            addRole() {
                openModal("#modal-add_role");
            },
            saveRole: function(role_id) {
                if (this.selected_role.role_name == "") {
                    notify.danger("You must specify a role name");
                    return;
                }

                let options = FetchHelper.buildJsonRequest({
                    role_name: this.selected_role.role_name,
                    permissions: this.selected_role.permissions
                }, 'PUT');

                fetch(`/internal-api/v1/roles/${role_id}`, options)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.getRolePerms(); 
                        this.selected_role = {};
                    })
                    .catch((error) => {
                        this.loading(false);
                        ResponseHelper.handleErrorMessage(error, 'Could not save role.');
                    });
            },
            cleanupRoleName: function (type, index=0) {
                if (type == "selected_role.role_name"){
                    this.selected_role.role_name = this.selected_role.role_name.substring(0, 200);
                } else if (type == "studio_roles.role_name") {
                    this.studio_roles[index].role_name = this.studio_roles[index].role_name.substring(0, 200);
                }  
            },
            deleteRole: function(role_id) {
                if (! confirm("Are you sure you want to delete this role?")) {
                    return;
                }

                this.loading(true);
                fetch(`/internal-api/v1/roles/${role_id}`, {method: 'delete'})
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.loading(false);
                        if(json.data.user_count > 0){
                            this.role_detail = json.data;
                            openModal("#modal-assign_role");
                        } else {
                            notify.success("Role has been deleted");
                            this.getRolePerms();
                        }
                    })
                    .catch((error) => {
                        this.loading(false);
                        ResponseHelper.handleErrorMessage(error, 'Could not delete role.');
                    });
            },
        },
        mounted: function() {
            this.getBreadcrumbs();
            this.getCurrentUser();
            this.getProducts();    
            this.getRoles();
            this.getPermissions();
            this.getRolePerms();
            this.loading(false);
        }
    });
</script>
<?php echo view("components/foot"); ?>
