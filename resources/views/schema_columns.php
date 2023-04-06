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
            <table v-if="columns.length > 0" id="column-data" class="dmiux_data-table__table">
                <thead>
                    <tr>
                        <th></th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Length</th>
                        <th>Precision</th>
                        <th>Last Modified</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(column, index) in columns" class="dmiux_input">
                        <td style="width: 20px !important;">
                            <div class="dmiux_data-table__actions" v-if="index === columnToUpsert">
                                <div class="dmiux_actionswrap dmiux_actionswrap--cancel" @click="editingCancel()" data-toggle="tooltip" title="Cancel"></div>
                                <div class="dmiux_actionswrap dmiux_actionswrap--save" @click="editingSave()" data-toggle="tooltip" title="Save"></div>
                            </div>
                            <div class="dmiux_data-table__actions" v-else>
                                <div class="dmiux_actionswrap dmiux_actionswrap--edit" @click="editingStart(index)" data-toggle="tooltip" title="Update"></div>
                                <div class="dmiux_actionswrap dmiux_actionswrap--bin" @click="deleteColumn(column.id)" data-toggle="tooltip" title="Delete"></div>
                            </div>
                        </td>
                        <td v-if="index === columnToUpsert">
                            <input type="text" @input="cleanupName(column)" class="dmiux_input__input" v-model:value="column.name" id="name-edit" placeholder="Column name" autocomplete="off" />
                        </td>
                        <td v-else :title="column.name">{{ column.name.substring(0, 30) }}<template v-if="column.name.length > 30">...</template></td>
                        <td v-if="index === columnToUpsert">
                            <div class="dmiux_select">
                                <select class="dmiux_select__select" @change="reset_type_length()" v-model:value="column.type">
                                    <option value="character">CHARACTER</option>
                                    <option value="varchar">VARCHAR</option>
                                    <option value="integer">INTEGER</option>
                                    <option value="bigint">BIGINT</option>
                                    <option value="boolean">BOOLEAN</option>
                                    <option value="decimal">DECIMAL</option>
                                    <option value="numeric">NUMERIC</option>
                                    <option value="date">DATE</option>
                                    <option value="timestamp">TIMESTAMP</option>
                                </select>
                                <div class="dmiux_select__arrow"></div>
                            </div>
                        </td>
                        <td v-else>{{ column.type }}</td>
                        <td v-if="index === columnToUpsert && (column.type == 'varchar' || column.type == 'integer' || column.type == 'character' || column.type == 'decimal')">
                            <input type="number" class="dmiux_input__input" v-model:value="column.length" autocomplete="off" />
                        </td>
                        <td v-else>{{ column.length }}</td>
                        <td v-if="index === columnToUpsert && column.type == 'decimal'">
                            <input type="number" class="dmiux_input__input" v-model:value="column.precision" autocomplete="off" />
                        </td>
                        <td v-else>{{ column.precision }}</td>
                        <td>{{ column.updated_at_formatted }}</td>
                    </tr>
                </tbody>
            </table>
            <div v-else class="alert alert-info mt-2">There are no columns in this table yet. <a href="javascript:void(0)" @click="editingStart(false);">Add a column</a>.</div>
        </div>
    </div>
</div>
<script>
    var table_id = <?php echo $table_id; ?>;
    var toolbar = Vue.component('toolbar', {
        template: '#component-toolbar',
        props: [ 'breadcrumbs', 'buttons', 'record_counts' ],
        methods: {
        }
    });

    var app = new Vue({
        el: '#app',
        data: {
            table_id: table_id,
            toolbar: {
                "breadcrumbs": [],
                "buttons": [
                    {
                        "onclick": "app.editingStart(false)",
                        "text": "Add Column&nbsp; <span class=\"fas fa-plus\"></span>",
                        "class": "dmiux_button dmiux_button_secondary"
                    }
                ]
            },
            columns: [],
            columnToUpsert: false,
            currentUser : {
                "is_admin" : false
            }
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
            getColumns: function() {
                this.loading(true);
                fetch(`/internal-api/v1/admin/schema-columns/${this.table_id}`)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.loading(false);
                        this.columns = json.data;
                        for (var i=0; i<this.columns.length; i++) {
                            this.columns[i].updated_at_formatted = DateHelper.formatLocaleCarbonDate(this.columns[i].updated_at);
                        }
                    })
                    .catch((error) => {
                        this.loading(false);
                        ResponseHelper.handleErrorMessage(error, `Failed to get columns`);
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
            saveColumn: function(column) {
                Object.keys(column).forEach(key => column[key] === undefined && delete column[key]);

                var options = FetchHelper.buildJsonRequest({
                    managed_database_table_id: this.table_id,
                    name:                      column.name,
                    type:                      column.type,
                    length:                    column.length,
                    precision:                 column.precision
                }, column.id ? 'PUT' : 'POST');

                if(column.id) { // We're updating a column
                    var url = `/internal-api/v1/admin/schema-columns/${column.id}`;
                } else { // We're creating a column
                    var url = `/internal-api/v1/admin/schema-columns`;
                }

                fetch(url, options)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.loading(false);
                        this.getColumns();
                        notify.success(`Successfully ${column.id ? 'updated' : 'created'} column.`);
                    })
                    .catch((error) => {
                        this.loading(false);
                        this.getColumns();
                        ResponseHelper.handleErrorMessage(error, `Failed to ${column.id ? 'update' : 'create'} column.`);
                    });
            },
            deleteColumn: function(column_id) {
                if(!confirm("Are you sure you want to delete this column?")) {
                    return false;
                }

                fetch(`/internal-api/v1/admin/schema-columns/${column_id}`, {method: 'delete'})
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.getColumns();
                        notify.success(`Successfully deleted column.`);
                    })
                    .catch((error) => {
                        this.loading(false);
                        ResponseHelper.handleErrorMessage(error, `Failed to delete column.`);
                    });
            },
            editingStart: function(columnKey) {
                if(columnKey !== false) {
                    this.columnToUpsert = columnKey;
                }
                else {
                    this.columns.push({
                        name: '',
                        type: 'varchar',
                        length: '',
                        precision: ''
                    });
                    this.columnToUpsert = (this.columns.length - 1);
                    setTimeout(function() { document.getElementById("name-edit").focus(); }, 500);
                }
            },
            reset_type_length: function() {
                this.columns[this.columnToUpsert].length = "";
                this.columns[this.columnToUpsert].precision = "";
            },
            cleanupName(column) {
                column.name = column.name.substring(0, 200);
            },
            editingSave: function() {
                var newColumn = this.columns[this.columnToUpsert];
                var names = [];

                if(newColumn.name.trim() == "") {
                    notify.danger("Please enter a name before saving the column.");
                    return;
                }

                for(i = 0; i < this.columns.length; i++) {
                    if(i != this.columnToUpsert) {
                        names.push(this.columns[i].name.toLowerCase());
                    }
                }

                if(names.includes(newColumn.name.toLowerCase())) {
                    alert("The column name you specified has already been used. Please use a different name.");
                    return false;
                }

                this.saveColumn(newColumn);
                this.columnToUpsert = false;
            },
            editingCancel: function() {
                if(!this.columns[this.columnToUpsert].id) {
                    this.columns.pop();
                }
                this.columnToUpsert = false;
            }
        },
        mounted: function() {
            this.getBreadcrumbs();
            this.getCurrentUser();
            this.getColumns();
        }
    })
</script>
<?php echo view("components/foot"); ?>