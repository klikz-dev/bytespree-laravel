<?php echo view("components/head"); ?>
<?php echo view("components/component-toolbar"); ?>
<div id="app">
    <toolbar
        :buttons="toolbar.buttons"
        :breadcrumbs="toolbar.breadcrumbs">
    </toolbar>
    <div class="dmiux_content">
        <div class="dmiux_grid-cont dmiux_grid-cont_fw dmiux_data-table dmiux_data-table__cont">
                <div class="dmiux_new-search" id="table-columns-form">
                    <table class="dmiux_data-table__table">
                        <thead>
                            <tr>
                                <th>Column from Bytespree</th> 
                                <th>Column in Destination</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(source_column, index) in source_columns" class="dmiux_input">
                                <td>{{ source_column.name }}</td>
                                <td>
                                    <div class="dmiux_select">
                                        <select :id="'source_column_' + index" class="dmiux_select__select" v-model="source_column.destination_column" :disabled="source_column.matchingColumns.length == 0">
                                            <template v-if="filteredMatchingColumns(source_column).length == 0">
                                                <option v-if="source_column.matching_type == 'unsupported'" value="null" selected>This data type is not supported by the publisher.</option>
                                                <option v-else value="null" selected>No matching data types found in destination database ({{ source_column.matching_type }}).</option>
                                            </template>
                                            <template v-else>
                                                <option value="null">Select a Column</option>
                                                <option v-for="matching_column in filteredMatchingColumns(source_column)" :value="matching_column.name">{{ matching_column.name }}</option>
                                            </template>
                                        </select>
                                        <div class="dmiux_select__arrow"></div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td></td>
                                <td class="text-right pr-3 py-3 text-muted">
                                    <i class="fal fa fa-info-circle"></i> Columns shown in destination match the data type found in Bytespree.
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                    <div class="dmiux_new-search__foot">
                        <div class="dmiux_grid-row">
                            <div class="dmiux_grid-col"></div>
                            <div class="dmiux_grid-col dmiux_grid-col_auto">
                                <button type="button"
                                        @click="goBack();"
                                        class="dmiux_button dmiux_button_secondary">
                                    Back
                                </button>
                            </div>
                            <div class="dmiux_grid-col dmiux_grid-col_auto pl-0">
                                <button class="dmiux_button" @click="submitMapping();"type="button">Submit</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
                "breadcrumbs": [
                    {
                        title: '<?php echo $table; ?>',
                        location: '<?php echo $callback_url;  ?>'
                    },
                    {
                        title: 'Mapping SQL Server Publishing',
                        location: '#'
                    }
                ],
                'buttons': [ ]
            },
            project_id: <?php echo $project_id; ?>,
            guid: '<?php echo $guid; ?>',
            schema: '<?php echo $schema; ?>',
            table: '<?php echo $table; ?>',
            source_columns: [],
            editing: false,
            currentUser : {
                "is_admin" : false
            },
            locked: false
        },
        components: {
            'toolbar': toolbar
        },
        methods: {
            loading(status) {
                $(".loader").toggle(status);
            },
            goBack() {
                window.location = this.toolbar.breadcrumbs[0].location;
            },
            getMappingDetails() {
                fetch(`/internal-api/v1/studio/projects/${this.project_id}/tables/${this.schema}/${this.table}/publishers/mssql/details/${this.guid}`)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.loading(false);
                        this.source_columns = json.data.source_columns.map((column) => {
                            column.matchingColumns = json.data.destination_columns.filter((dest_col) => dest_col.data_type == column.matching_type);

                            var matches = column.matchingColumns.filter((match_col) => match_col.name.toLowerCase() == column.name.toLowerCase());
                            
                            if(column.destination_column == null) {
                                column.destination_column = matches.length > 0 ? matches[0].name : null;
                            }

                            return column;
                        });
                    })
                    .catch((error) => {
                        this.loading(false);
                        ResponseHelper.handleErrorMessage(error, "Unable to retrieve publishing data.");
                    });
            },
            submitMapping() {
                this.loading(true);

                let options = FetchHelper.buildJsonRequest({
                    columns: this.source_columns
                });

                fetch(`/internal-api/v1/studio/projects/${this.project_id}/tables/${this.schema}/${this.table}/publishers/mssql/map/${this.guid}`, options)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        if (typeof(json.data.redirect) != 'undefined') {
                            window.location = json.data.redirect;
                        }
                    })
                    .catch((error) => {
                        this.loading(false);
                        ResponseHelper.handleErrorMessage(error, "Failed to publish view.");
                    });
            },
            filteredMatchingColumns(column) {
                return column.matchingColumns.filter((matching_col) => {
                    return this.source_columns.filter((inner_column) => {
                        return inner_column.destination_column == matching_col.name && inner_column.name != column.name;
                    }).length == 0;
                });
            }
        },
        mounted() {
            this.loading(true);
            this.getMappingDetails();
        }
    })
</script>
<?php echo view("components/foot"); ?>
