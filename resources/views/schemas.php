<?php echo view("components/head"); ?>
<?php echo view("components/component-toolbar"); ?>
<div id="app">
    <toolbar
        :buttons="toolbar.buttons"
        :breadcrumbs="toolbar.breadcrumbs">
    </toolbar>
    <div class="dmiux_content">
        <?php echo view('components/admin/menu', ['selected' => 'schemas']); ?>
        <div v-if="currentUser.is_admin == true" class="dmiux_grid-cont dmiux_grid-cont_fw dmiux_data-table dmiux_data-table__cont mt-2">
            <table v-if="schemas.length > 0" id="schema-data" class="dmiux_data-table__table">
                <thead>
                    <tr>
                        <th></th>
                        <th>Name</th>
                        <th>Last Modified</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(schema, index) in schemas" class="dmiux_input">
                        <td style="width: 20px !important;">
                            <div class="dmiux_data-table__actions" v-if="index === schemaToUpsert">
                                <div class="dmiux_actionswrap dmiux_actionswrap--cancel" @click="editingCancel()" data-toggle="tooltip" title="Cancel"></div>
                                <div class="dmiux_actionswrap dmiux_actionswrap--save" @click="editingSave()" data-toggle="tooltip" title="Save"></div>
                            </div>
                            <div class="dmiux_data-table__actions" v-else>
                                <div class="dmiux_actionswrap dmiux_actionswrap--edit" @click="editingStart(index)" data-toggle="tooltip" title="Update"></div>
                                <div class="dmiux_actionswrap dmiux_actionswrap--bin" @click="deleteSchema(schema.id)" data-toggle="tooltip" title="Delete"></div>
                            </div>
                        </td>
                        <td v-if="index === schemaToUpsert">
                            <input class="dmiux_input__input" @input="cleanupName(schema)" v-model:value="schema.name" id="name-edit" placeholder="Schema name" autocomplete="off" />
                        </td>
                        <td v-else>
                            <a :href="'/admin/schemas/' + schema.id + '/tables'" :title="schema.name">{{ schema.name.substring(0, 100) }}<template v-if="schema.name.length > 100">...</template></a>
                        </td>
                        <td>{{ schema.updated_at_formatted }}</td>
                    </tr>
                </tbody>
            </table>
            <div v-else class="alert alert-info mt-2">There are no schemas yet. <a href="javascript:void(0)" @click="editingStart(false);">Add a schema</a>.</div>
        </div>
    </div>
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
                        "onclick": "app.editingStart(false)",
                        "text": "Add Schema&nbsp; <span class=\"fas fa-plus\"></span>",
                        "class": "dmiux_button dmiux_button_secondary"
                    }
                ]
            },
            schemas: [],
            schemaToUpsert: false,
            currentUser : {
                "is_admin" : false
            },
            locked: false
        },
        components: {
            'toolbar': toolbar
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
            getSchemas: function() {
                this.loading(true);
                fetch("/internal-api/v1/admin/schemas")
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.loading(false);
                        this.schemas = json.data;

                        for (var i=0; i<this.schemas.length; i++) {
                            this.schemas[i].updated_at_formatted = DateHelper.formatLocaleCarbonDate(this.schemas[i].updated_at);
                        }
                    })
                    .catch((error) => {
                        this.loading(false);
                        ResponseHelper.handleErrorMessage(error, 'Failed to get schemas.');
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
            cleanupName(schema) {
                schema.name = schema.name.substring(0, 200);
            },
            saveSchema: function(schema) {
                Object.keys(schema).forEach(key => schema[key] === undefined && delete schema[key]);

                var options = FetchHelper.buildJsonRequest({
                    name: schema.name
                }, schema.id ? 'PUT' : 'POST');

                if(schema.id) { // We're updating a schema
                    var url = `/internal-api/v1/admin/schemas/${schema.id}`;
                } else { // We're creating a schema
                    var url = "/internal-api/v1/admin/schemas";
                }

                fetch(url, options)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.loading(false);
                        this.getSchemas();
                        notify.success(`Successfully ${schema.id ? 'updated' : 'created'} schema.`);
                    })
                    .catch((error) => {
                        this.loading(false);
                        this.getSchemas();
                        ResponseHelper.handleErrorMessage(error, `Failed to ${schema.id ? 'update' : 'create'} schema.`);
                    });
            },
            deleteSchema: function(schema_id) {
                if(!confirm("Are you sure you want to delete this schema?")) {
                    return false;
                }

                fetch(`/internal-api/v1/admin/schemas/${schema_id}`, {method: 'delete'})
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    this.loading(false);
                    this.getSchemas();
                    notify.success(`Successfully deleted schema.`);
                })
                .catch((error) => {
                    this.loading(false);
                    ResponseHelper.handleErrorMessage(error, `Failed to delete schema.`);
                });
            },
            editingStart: function(schemaKey) {
                if(schemaKey !== false) {
                    this.schemaToUpsert = schemaKey;
                }
                else {
                    this.schemas.push({name: ''});
                    this.schemaToUpsert = (this.schemas.length - 1);
                    setTimeout(function() { document.getElementById("name-edit").focus(); }, 500);
                }
            },
            editingSave: function() {
                var newSchema = this.schemas[this.schemaToUpsert];
                var name = newSchema.name;
                var names = [];

                if (name.trim() == '') {
                    notify.danger("Please enter a name before saving the schema.");
                    return;
                }

                for(i = 0; i < this.schemas.length; i++) {
                    if(i != this.schemaToUpsert) {
                        names.push(this.schemas[i].name.toLowerCase());
                    }
                }

                if(names.includes(newSchema.name.toLowerCase())) {
                    alert("The schema name you specified has already been used. Please use a different name.");
                    return false;
                }

                this.saveSchema(newSchema);
                this.schemaToUpsert = false;
            },
            editingCancel: function() {
                app.getSchemas();
                this.schemaToUpsert = false;
                return;
            }
        },
        mounted: function() {
            this.getBreadcrumbs();
            this.getCurrentUser();
            this.getSchemas();
        }
    })
</script>
<?php echo view("components/foot"); ?>
