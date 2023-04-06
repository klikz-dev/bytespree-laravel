<?php echo view("components/head"); ?>
<?php echo view("components/modals/clone_schema"); ?>
<?php echo view("components/component-toolbar"); ?>
<div id="app">
    <toolbar
        :buttons="toolbar.buttons"
        :breadcrumbs="toolbar.breadcrumbs">
    </toolbar>
    <div class="dmiux_content">
        <?php echo view('components/admin/menu', ['selected' => 'schemas']); ?>
        <div v-if="currentUser.is_admin == true" class="dmiux_grid-cont dmiux_grid-cont_fw dmiux_data-table dmiux_data-table__cont mt-2">
            <table v-if="tables.length > 0" id="table-data" class="dmiux_data-table__table">
                <thead>
                    <tr>
                        <th></th>
                        <th>Name</th>
                        <th>Last Modified</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(table, index) in tables" class="dmiux_input">
                        <td style="width: 20px !important;">
                            <div class="dmiux_data-table__actions" v-if="index === tableToUpsert">
                                <div class="dmiux_actionswrap dmiux_actionswrap--cancel" @click="editingCancel()" data-toggle="tooltip" title="Cancel"></div>
                                <div class="dmiux_actionswrap dmiux_actionswrap--save" @click="editingSave()" data-toggle="tooltip" title="Save"></div>
                            </div>
                            <div class="dmiux_data-table__actions" v-else>
                                <div class="dmiux_actionswrap dmiux_actionswrap--edit" @click="editingStart(index)" data-toggle="tooltip" title="Update"></div>
                                <div class="dmiux_actionswrap dmiux_actionswrap--bin" @click="deleteTable(table.id)" data-toggle="tooltip" title="Delete"></div>
                            </div>
                        </td>
                        <td v-if="index === tableToUpsert">
                            <input class="dmiux_input__input" @input="cleanupName(table)" v-model:value="table.name" id="name-edit" placeholder="Table name" autocomplete="off" />
                        </td>
                        <td v-else>
                            <a :href="'/admin/schemas/table/' + table.id + '/columns'" :title="table.name">{{ table.name.substring(0, 100) }}<template v-if="table.name.length > 100">...</template></a>
                        </td>
                        <td>{{ table.updated_at_formatted }}</td>
                    </tr>
                </tbody>
            </table>
            <div v-else class="alert alert-info mt-2">There is no data in this schema yet. 
                <a href="javascript:void(0)" @click="editingStart(false);">Add a table</a>
                <span>, or </span>
                <a href="javascript:void(0)" @click="cloneStart(false);">Clone an Existing Schema</a>
            </div>
        </div>
    </div>
    <clone-schema 
        ref="cloneSchema"
        :integrations="integrations">
    </clone-schema>
</div>
<script>
    var schema_id = <?php echo $schema_id; ?>;
    var toolbar = Vue.component('toolbar', {
        template: '#component-toolbar',
        props: [ 'breadcrumbs', 'buttons', 'record_counts' ],
        methods: {
        }
    });

    var app = new Vue({
        el: '#app',
        data: {
            schema_id: schema_id,
            toolbar: {
                "breadcrumbs": [],
                "buttons": [
                    {
                        "onclick": "app.editingStart(false)",
                        "text": "Add Table&nbsp; <span class=\"fas fa-plus\"></span>",
                        "class": "dmiux_button dmiux_button_secondary mr-1"
                    },
                    {
                        "onclick": "app.cloneStart()",
                        "text": "Clone Existing Schema&nbsp;",
                        "class": "dmiux_button dmiux_button_secondary mr-1",
                        "id": "clone_id"
                    },
                    {
                        "onclick": "app.resyncStart()",
                        "text": "Re-sync Schema&nbsp;",
                        "class": "dmiux_button dmiux_button_secondary mr-1",
                        "id": "resync_id"
                    },
                    {
                        "onclick": "window.location.href = '/admin/schemas/" + this.schema_id + "/modules';",
                        "text": "Manage Modules",
                        "class": "dmiux_button dmiux_button_secondary"
                    }
                ]
            },
            tables: [],
            integrations: [],
            tableToUpsert: false,
            currentUser : {
                "is_admin" : false
            }
        },
        components: {
            'toolbar': toolbar
        },
        methods: {
            /** Vue.js Method
             *******************************************************************************
             * checkPerms
             * Params:
             * * perm_name		string 		A particular permission identifier.
             * * control_id		integer 	A database identifier.
             *******************************************************************************
             * Checks to see if a particular function of the UX should be accessible by the
             * current user.
             */
            checkPerms: function (perm_name, product_child_id) {
                var result = false;
                if (this.currentUser.is_admin === true) {
                    result = true;
                }
                else {
                    for(var i=0; i < this.permissions.length; i++) {
                        if (this.permissions[i].product_child_id == product_child_id) {
                            for (var j=0; j < this.permissions[i].name.length; j++) {
                                if (this.permissions[i].name[j] == perm_name) {
                                    result = true;
                                }
                            }
                        }
                    }
                }
                return result;
            },
            loading: function(status) {
                if(status === true) {
                    $(".loader").show();
                }
                else {
                    $(".loader").hide();
                }
            },
            getTables: function() {
                this.loading(true);
                fetch(`/internal-api/v1/admin/schema-tables/${this.schema_id}`)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.tables = json.data;
                        for (var i=0; i<this.tables.length; i++) {
                            this.tables[i].updated_at_formatted = DateHelper.formatLocaleCarbonDate(this.tables[i].updated_at);
                        }

                        if (this.tables.length > 0) {
                            this.toolbar.buttons[1].class = "dmiux_button dmiux_button_secondary mr-1 hidden";
                            this.toolbar.buttons[2].class = "dmiux_button dmiux_button_secondary mr-1";
                        } else {
                            this.toolbar.buttons[1].class = "dmiux_button dmiux_button_secondary mr-1";
                            this.toolbar.buttons[2].class = "dmiux_button dmiux_button_secondary mr-1 hidden";
                        }
                        this.loading(false);
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
                fetch(baseUrl + "/internal-api/v1/crumbs")
                    .then(response => response.json())
                    .then(json => {
                        this.toolbar.breadcrumbs = json.data;
                    });
            },
            cleanupName(table) {
                table.name = table.name.substring(0, 200);
            },
            saveTable: function(table) {
                Object.keys(table).forEach(key => table[key] === undefined && delete table[key]);

                var options = FetchHelper.buildJsonRequest({
                    managed_database_id: this.schema_id,
                    name:                table.name
                }, table.id ? 'PUT' : 'POST');

                if(table.id) { // We're updating a table
                    var url = `/internal-api/v1/admin/schema-tables/${table.id}`;
                } else { // We're creating a table
                    var url = `/internal-api/v1/admin/schema-tables`;
                }

                fetch(url, options)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.loading(false);
                        this.getTables();
                        notify.success(`Successfully ${table.id ? 'updated' : 'created'} table.`);
                    })
                    .catch((error) => {
                        this.loading(false);
                        this.getTables();
                        ResponseHelper.handleErrorMessage(error, `Failed to ${table.id ? 'update' : 'create'} table.`);
                    });
            },
            deleteTable: function(table_id) {
                if(!confirm("Are you sure you want to delete this table?")) {
                    return false;
                }

                fetch(`/internal-api/v1/admin/schema-tables/${table_id}`, {method: 'delete'})
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.getTables();
                        notify.success(`Successfully deleted table.`);
                    })
                    .catch((error) => {
                        this.loading(false);
                        ResponseHelper.handleErrorMessage(error, `Failed to delete table.`);
                    });
            },
            cloneStart: function() {
                this.loading(true);

                fetch('/internal-api/v1/data-lakes')
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.integrations = json.data.user_databases;
                        this.loading(false);

                        $(document).on("mousedown", "#dmiux_body", this.$refs.cloneSchema.closeCloneSchemaModal);
                        $(document).on("keydown", this.$refs.cloneSchema.closeCloneSchemaModal);
                        openModal('#modal-clone_schema');
                    });
            },
            resyncStart: function() {
                if (!confirm("Are you sure you want to re-sync this schema?  If so, all current tables and columns will be completely overwritten with new ones.")) 
                    return false;

                this.loading(true);
                fetch(`/internal-api/v1/admin/schemas/resync/${this.schema_id}`, {method: 'PUT'})
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.getTables();
                    })
                    .catch((error) => {
                        this.$root.loading(false);
                        ResponseHelper.handleErrorMessage(error, "Failed to re-sync schema");
                    });

            },
            editingStart: function(tableKey) {
                if(tableKey !== false) {
                    this.tableToUpsert = tableKey;
                }
                else {
                    this.tables.push({name: ''});
                    this.tableToUpsert = (this.tables.length - 1);
                    setTimeout(function() { document.getElementById("name-edit").focus(); }, 500);
                }
            },
            editingSave: function() {
                var newTable = this.tables[this.tableToUpsert];
                var name = newTable.name;
                var names = [];

                if(name.trim() == "") {
                    notify.danger("Please enter a name before saving the table.");
                    return;
                }

                for(i = 0; i < this.tables.length; i++) {
                    if(i != this.tableToUpsert) {
                        names.push(this.tables[i].name.toLowerCase());
                    }
                }

                if(names.includes(newTable.name.toLowerCase())) {
                    alert("The table name you specified has already been used. Please use a different name.");
                    return false;
                }

                this.saveTable(newTable);
                this.tableToUpsert = false;
            },
            editingCancel: function() {
                if(!this.tables[this.tableToUpsert].id) {
                    this.tables.pop();
                }
                this.tableToUpsert = false;
            },
            checkForError: function (json) {
                if (json.status == "error") {
                    alert(json.message);
                    return false;
                }
                return true;
            },
        },
        mounted: function() {
            this.getBreadcrumbs();
            this.getCurrentUser();
            this.getTables();
        }
    })
</script>
<?php echo view("components/foot"); ?> 