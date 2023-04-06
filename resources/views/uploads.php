<?php echo view("components/head"); ?>
<?php echo view("components/uploads/component-toolbar"); ?>
<div id="app">
    <toolbar :breadcrumbs="toolbar.breadcrumbs"
             :control_id="control_id"
             :column="column">
    </toolbar>
    <div class="upload_csv">
        <div class="dmiux_content">
            <div class="dmiux_grid-cont">
                <div class="dmiux_new-search" id="table-columns-form">
                    <form id="buildTableForm" method="post" autocomplete="off" onsubmit="event.preventDefault()">
                        <div class="dmiux_new-search__head">
                            <h2 v-if="is_appending" class="dmiux_new-search__title">Appending Data to a Table</h2>
                            <h2 v-else class="dmiux_new-search__title">Upload CSV</h2>
                            <div class="dmiux_input">
                                <label for="tableName" class="dmiux_popup__label">Table Name <span class="text-danger">*</span></label>
                                <input id="tableName" name="tableName" @input="formatTableName()" class="dmiux_input__input" v-model="table_name" :disabled="is_appending || is_replacing" />
                            </div>
                        </div>
                        <template v-if="is_appending || is_replacing">
                            <div class="px-4">
                                <table class="table table-striped">
                                    <thead>
                                        <th>Column in CSV</th>
                                        <th>Column in Table</th>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(col, index) in column">
                                            <template v-if="! col.column.startsWith('__bytespree') && checkForNewColumn(index)">
                                                <td>
                                                    <label :for="'column-map-'+index" class="dmiux_popup__label">{{ col.column }}</label>
                                                </td>
                                                <td>
                                                    <select v-model="col.map_to" class="dmiux_select__select" :id="'column-map-'+index" @change="preventDuplicateMappingDestinations(col.map_to, index)">
                                                        <option value="null">Select a Table Column</option>
                                                        <option v-for="(col, index) in table_columns">
                                                            {{ col }}
                                                        </option>
                                                    </select>
                                                </td>
                                            </template>
                                            <template v-else-if="! col.column.startsWith('__bytespree')">
                                                <td>
                                                    <div class="dmiux_input pr-3">
                                                        <label :for="'column-name'+index" class="dmiux_popup__label">Column Name <span class="text-danger">*</span></label>
                                                        <input @input="checkForDuplicateColNames(col.column, index)" v-model="col.column" class="dmiux_input__input column" :id="'column-name'+index">
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="dmiux_grid-row dmiux_grid-row_aic dmiux_grid-row_nog">
                                                        <div class="dmiux_grid-col" :class="col.type == 'decimal' || col.type == 'varchar' ? 'dmiux_grid-col_6' : 'dmiux_grid-col_12'">
                                                            <label :for="'data-type'+index" class="dmiux_popup__label" >Data Type <span class="text-danger">*</span></label>
                                                            <div class="dmiux_select">
                                                                <select v-model="col.type" class="dmiux_select__select" :id="'data-type'+index" >
                                                                    <option value="varchar">Varchar</option>
                                                                    <option value="int">Int</option>
                                                                    <option value="bigint">BigInt</option>
                                                                    <option value="jsonb">Json</option>
                                                                    <option value="text">Text</option>
                                                                    <option value="decimal">Decimal</option>
                                                                </select>
                                                                <div class="dmiux_select__arrow"></div>
                                                            </div>
                                                        </div>
                                                        <div v-if="col.type == 'decimal' || col.type == 'varchar'" 
                                                             class="dmiux_grid-col" 
                                                             :class="[col.type == 'decimal' ? 'dmiux_grid-col_3' : '', col.type == 'varchar' ? 'dmiux_grid-col_6' : '']">
                                                            <div class="dmiux_input pl-3 pr-3">
                                                            <label :for="'field-length'+index" class="dmiux_popup__label column">Field Length <span class="text-danger">*</span></label>
                                                                <input type="number" v-model="col.value" class="dmiux_input__input" :id="'field-length'+index">
                                                            </div>
                                                        </div>
                                                        <div v-if="col.type == 'decimal'" class="dmiux_grid-col dmiux_grid-col_3">
                                                            <div class="dmiux_input pr-3">
                                                                <label :for="'field-precision'+index" class="dmiux_popup__label column">Field Precision <span class="text-danger">*</span></label>
                                                                <input type="number" v-model="col.precision" class="dmiux_input__input" :id="'field-precision'+index">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </template>
                                            <template v-else>
                                                <td colspan="2">
                                                    Columns that start with __bytespree are system columns and will be ignored
                                                </td>
                                            </template>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </template>
                        <template v-else>
                            <div class="dmiux_new-search__cont">
                                <div v-for="(col, index) in column" class="dmiux_new-search__row dmiux_full-width-search__row">
                                    <div v-if="! col.column.startsWith('__bytespree')" class="dmiux_grid-row dmiux_grid-row_aic dmiux_grid-row_nog">
                                        <div class="dmiux_grid-col" :class="col.type == 'decimal'  ? 'dmiux_grid-col_3' : 'dmiux_grid-col_6'">
                                            <div class="dmiux_input pr-3">
                                            <label :for="'column-name'+index" class="dmiux_popup__label">Column Name <span class="text-danger">*</span></label>
                                                <input @input="checkForDuplicateColNames(col.column, index)" v-model="col.column" class="dmiux_input__input column" :id="'column-name'+index">
                                            </div>
                                        </div>
                                        <div class="dmiux_grid-col" :class="col.type == 'decimal' || col.type == 'varchar' ? 'dmiux_grid-col_3' : 'dmiux_grid-col_6'">
                                        <label :for="'data-type'+index" class="dmiux_popup__label" >Data Type <span class="text-danger">*</span></label>
                                            <div class="dmiux_select">
                                                <select v-model="col.type" class="dmiux_select__select" :id="'data-type'+index" >
                                                    <option value="varchar">Varchar</option>
                                                    <option value="int">Int</option>
                                                    <option value="bigint">BigInt</option>
                                                    <option value="jsonb">Json</option>
                                                    <option value="text">Text</option>
                                                    <option value="decimal">Decimal</option>
                                                </select>
                                                <div class="dmiux_select__arrow"></div>
                                            </div>
                                        </div>
                                        <div v-if="col.type == 'decimal' || col.type == 'varchar'" class="dmiux_grid-col dmiux_grid-col_3">
                                            <div class="dmiux_input pl-3 pr-3">
                                            <label :for="'field-length'+index" class="dmiux_popup__label column">Field Length <span class="text-danger">*</span></label>
                                                <input type="number" v-model="col.value" class="dmiux_input__input" :id="'field-length'+index">
                                            </div>
                                        </div>
                                        <div v-if="col.type == 'decimal'" class="dmiux_grid-col dmiux_grid-col_3">
                                            <div class="dmiux_input pr-3">
                                                <label :for="'field-precision'+index" class="dmiux_popup__label column">Field Precision <span class="text-danger">*</span></label>
                                                <input type="number" v-model="col.precision" class="dmiux_input__input" :id="'field-precision'+index">
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else class="dmiux_grid-row dmiux_grid-row_aic dmiux_grid-row_nog">
                                        <div class="dmiux_grid-col dmiux_grid-col_12">
                                            <label class="mt-4">Columns that start with __bytespree are system columns and will be ignored</label>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </form>
                    <div class="dmiux_new-search__foot">
                        <div class="dmiux_grid-row">
                            <div class="dmiux_grid-col dmiux_grid-col_auto"><span class="float-left">Max File Size is {{ max_size }}</span></div>
                            <div class="dmiux_grid-col"></div>
                            <div class="dmiux_grid-col dmiux_grid-col_auto">
                                <button type="button"
                                        @click="closePage();"
                                        class="dmiux_button dmiux_button_secondary">
                                    Back
                                </button>
                            </div>
                            <div class="dmiux_grid-col dmiux_grid-col_auto pl-0">
                                <button class="dmiux_button" @click="submitTable();" type="button">Submit</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    var control_id = <?php echo $database_id; ?>;
    var table_id = <?php echo $table_id; ?>;
    var file_name = "<?php echo $file_name; ?>";
    var table_name = "<?php echo $table_name; ?>";
    var ignore_errors = <?php echo $ignore_errors; ?>;
    var ignore_empty = <?php echo $ignore_empty; ?>;
    var has_columns = <?php echo $has_columns; ?>;
    var delimiter = '<?php echo $delimiter; ?>';
    var v_escape = '<?php echo $escape; ?>';
    var enclosed = '<?php echo $enclosed; ?>';
    var encoding = '<?php echo $encoding; ?>';
    var max_size = '<?php echo $max_size; ?>';
    var is_replacing = <?php echo $is_replacing ? 'true' : 'false'; ?>;
    var is_appending = <?php echo $is_appending ? 'true' : 'false'; ?>;
    var table_columns = JSON.parse('<?php echo $table_columns; ?>');

    var baseUrl = "";

    var toolbar = Vue.component('toolbar', {
        template: '#component-toolbar',
        props: [ 'breadcrumbs','control_id']
    });

    var app = new Vue({
        el: '#app',
        data: {
            file: '',
            toolbar: {
                "breadcrumbs": []
            },
            control_id: control_id,
            table_id: table_id,
            file_name: file_name,
            table_name: table_name,
            ignore_errors: ignore_errors,
            ignore_empty: ignore_empty,
            has_columns: has_columns,
            delimiter: delimiter,
            encoding: encoding,
            enclosed: enclosed,
            escape: v_escape,
            column: [],
            max_size: max_size,
            is_replacing: is_replacing,
            dupes: false,
            is_appending: is_appending,
            table_columns: table_columns
        },
        methods: {
            loading(status) {
                if(status === true) {
                    $(".loader").show();
                }
                else {
                    $(".loader").hide();
                }
            },
            updateDataArray(event, index, type) {
                this.column[index][type] = event.target.value;
            },
            formatTableName() {
                var firstChar = this.table_name.charAt(0);
                var regex = new RegExp(/[a-z]/i);
                while(regex.test(firstChar) == false && this.table_name.length > 0) {
                    this.table_name = this.table_name.replace(firstChar, '');
                    firstChar = this.table_name.charAt(0);
                }
                this.table_name = this.table_name.toLowerCase()
                                    .trim()
                                    .replace(" ", "")
                                    .replace(/\W/g, '')
                                    .substring(0, 63);
            },
            btoa(str) {
                return btoa(str);
            },
            atob(str) {
                return atob(str);
            },
            checkForNewColumn(index) {
                this.column.forEach(col => {
                    if(col.column.startsWith('__bytespree')) {
                        index--;
                    }
                });

                return index < this.table_columns.length;
            },
            closePage() {
                app.loading(true);
                window.location.href = `/data-lake/database-manager/${control_id}`;
            },
            getColumns() {
                fetch(`/internal-api/v1/uploads/${this.file_name}/columns/${this.has_columns}/${this.delimiter}`)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.loading(false);
                        this.column = json.data.map((col) => {
                            col.map_to = this.findPotentialMappingDestinationColumn(col);
                            return col;
                        });
                    })
                    .catch(error => {
                        ResponseHelper.handleErrorMessage(error, 'Failed to get columns.');
                    });
            },
            submitTable() {
                this.checkForDuplicateColNames();
                if(this.dupes.length > 0) {
                    return;
                }
                
                var first = this.table_name.substr(0, 1);
                if (isNaN(first) === false) {
                    alert("First character of table name cannot be a number.");
                    return;
                }      
                var pattern = /^[0-9a-z_]+$/;
                if (pattern.test(this.table_name) === false) {
                    alert('Invalid character(s) in table name. Name must contain only letters, numbers, and underscores.');
                    return;
                }       

                this.column = this.column.filter((column) => {
                    column.column = column.column.trim();
                    return column;
                });

                if (is_appending) {
                    if (this.column.filter(col => col.map_to == null && !col.column.startsWith("__bytespree")).length > 0) {
                        alert('All file columns must be mapped to an existing table column.');
                        return;
                    }
                } else if (is_replacing) {
                    let bad_columns = this.column.filter((col, index) => {
                        return (col.map_to == null || col.map_to == 'null') && ! col.column.startsWith("__bytespree") && this.checkForNewColumn(index);
                    });

                    if (bad_columns.length > 0) {
                        alert('All file columns must be mapped to an existing table column.');
                        return;
                    }
                } else {
                    var invalid_columns = [];
                    invalid_columns = this.column.filter((column) => {
                        var column_name = column.column;
                        var first = column_name.substr(0, 1);
                        var pattern = /^[0-9a-z_]+$/;

                        if(isNaN(first) === false || pattern.test(column_name) === false) {
                            return column;
                        }
                    });

                    if (invalid_columns.length > 0) {
                        var invalid_column_str = invalid_columns.map((column) => { return column.column }).join(', ');
                        alert("One or more columns are invalid. The first character of a column cannot be a number. The column name must contain only letters, numbers, and underscores.\n\n Invalid Columns: " + invalid_column_str);
                        return;
                    }
                }

                $(".loader").show();

                this.loading(true);         
                let options = FetchHelper.buildJsonRequest({
                    table_name: this.table_name,
                    table_id: this.table_id,
                    file_name: this.file_name,
                    columns_temp: this.column,
                    encoding: this.encoding,
                    escape: this.escape,
                    enclosed: this.enclosed,
                    delimiter: this.delimiter,
                    ignore_errors: this.ignore_errors,
                    ignore_empty: this.ignore_empty,
                    has_columns: this.has_columns,
                    is_replacing: this.is_replacing,
                    is_appending: this.is_appending
                });
                
                fetch(`/internal-api/v1/data-lakes/${this.control_id}/tables`, options)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        window.location.href = `/data-lake/database-manager/${this.control_id}?message=${encodeURIComponent('Your import is processing and may take several minutes to complete. It will finish in the background.')}`;
                    })
                    .catch((error) => {
                        this.loading(false);
                        ResponseHelper.handleErrorMessage(error, 'Failed to import table.');
                    });
            },
            getBreadcrumbs() {
                this.loading(false);
                fetch(`/internal-api/v1/crumbs`)
                    .then(response => response.json())
                    .then(json => {
                        this.toolbar.breadcrumbs = json.data;
                    });
            },
            checkForDuplicateColNames(column_name, index) {
                if(column_name != undefined && column_name == '__bytespree') {
                    notify.danger("__bytespree is a reserved column name for Bytespree system columns");
                    this.column[index].column = '';
                    return;
                }

                var colList = [];
                this.dupes = [];
                const findDupes = array => array.filter((item, index) => array.indexOf(item) !== index);

                for(i = 0; i < this.column.length; i++) {
                    colList.push(this.column[i].column.toLowerCase());
                }

                var dupes = findDupes(colList);
                if(dupes.length > 0) {
                    if (column_name != undefined) {
                        if (dupes.includes(column_name.toLowerCase())) {
                            alert("Another column with the name " + column_name + " already exists. Please choose a different name.");
                        }   
                        // Don't show an error message when editing a column if it's not matching another     
                        return;
                    }
                    var text = "Duplicate names have been detected. You cannot submit this form until these issues have been resolved.\nThe following column(s) are duplicated: ";
                    for(i = 0; i < dupes.length; i++) {
                        this.dupes.push(dupes[i]);
                        text = text + this.dupes[i] + ", ";
                    }
                    notify.danger(text.replace(/,\s*$/, ""));
                }
            },
            findPotentialMappingDestinationColumn (col){
                let potential_matches = table_columns.filter((column) => column == col.column);

                return potential_matches.length == 0 ? null : potential_matches[0];
            },
            preventDuplicateMappingDestinations(value, current_index) {
                if (value === null) {
                    return;
                }

                this.column.forEach((col, index) => {
                    if (col.map_to == value && index != current_index) {
                        col.map_to = null;
                    }
                });
            }
        },
        mounted: function() {
            this.getBreadcrumbs();
            this.getColumns();
            this.checkForDuplicateColNames();
        }
    })
</script>
<?php echo view("components/foot"); ?>
