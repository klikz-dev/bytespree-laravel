<script type="text/x-template" id="table-modal-template">
	<div class="dmiux_popup" id="modal-add_table">
		<div class="dmiux_popup__window dmiux_popup__window_md" role="document">
            <div class="dmiux_popup__head">
                <h4 v-if="this.is_appending" class="dmiux_popup__title">Appending to a Table</h4>
                <h4 v-else-if="this.is_replacing" class="dmiux_popup__title">Replace a Table</h4>
                <h4 v-else class="dmiux_popup__title">Add a Table</h4>
                <button type="button" class="dmiux_popup__close" @click="modalClose($event)" id="x-button-add-table"></button>
            </div>
            <div class="dmiux_popup__cont">
                <form v-if="isInitial || isFailed" method="post" enctype="multipart/form-data" id="form-add_table" autocomplete="off" novalidate>
                    <div class="alert alert-info mb-0" role="alert">
                        You will be notified by email when your import completes.
                    </div>
                    <div class="dmiux_grid-row">
                        <div class="dmiux_grid-col dmiux_grid-col_auto">
                            <label class="dmiux_popup__label" for="input-upload_table">Upload Table</label>
                            <div class="dmiux_button button_nom dmiux_button_secondary">Choose File
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 12 16">
                                    <path fill="currentColor" d="M3 5v4c0 1.7 1.3 3 3 3s3-1.3 3-3V4.5C9 2 7 0 4.5 0S0 2 0 4.5V10c0 3.3 2.7 6 6 6s6-2.7 6-6V4h-2v6c0 2.2-1.8 4-4 4s-4-1.8-4-4V4.5C2 3.1 3.1 2 4.5 2S7 3.1 7 4.5V9c0 .6-.4 1-1 1s-1-.4-1-1V5H3z"></path>
                                </svg>
                                <input type="file"
                                       :disabled="isSaving"
                                       name="file"
                                       id="input-upload_table"
                                       @change="filesChange($event.target.name, $event.target.files);" />
                            </div>
                            <div v-if="hasValidFileSelected" class="mt-2">
                                <small class="text-muted">{{ truncatedFileName }}</small>
                            </div>
                            <p v-if="isSaving">Uploading your file...</p>
                        </div>
                    </div>
                    <div class="dmiux_grid-row">
                        <div class="dmiux_grid-col dmiux_grid-col_6">
                            <label class="dmiux_popup__label" for="delimiter">Delimiter</label>
                            <div class="dmiux_input mt-2">
                                <input type="text" v-model="delimiter" id="delimiter" value="," class="dmiux_input__input">
                            </div>
                        </div>
                        <div class="dmiux_grid-col dmiux_grid-col_6">
                            <label class="dmiux_popup__label" for="enclosed">Enclosed Character</label>
                            <div class="dmiux_input mt-2">
                                <input type="text" @input="handleEscape()" v-model="enclosed" id="enclosed" value='"' class="dmiux_input__input">
                            </div>
                        </div>
                    </div>
                    <div class="dmiux_grid-row">
                        <div class="dmiux_grid-col dmiux_grid-col_6">
                            <label class="dmiux_popup__label" for="escape">Escape Character</label>
                            <div class="dmiux_input mt-2">
                                <input type="text" :disabled="enclosed == ''" v-model="escape" id="escape" value='\' class="dmiux_input__input">
                            </div>
                        </div>
                        <div class="dmiux_grid-col dmiux_grid-col_6">
                            <label class="dmiux_popup__label" for="encoding">Encoding</label>
                            <div class="dmiux_select mt-2">
                                <select v-model="encoding" id="encoding" class="dmiux_select__select">
                                    <option value="utf-8" selected>UTF-8</option>
                                    <option value="latin-1">LATIN-1</option>
                                </select>
                                <div class="dmiux_select__arrow"></div>
                            </div>
                        </div>
                    </div>
                    <div class="dmiux_grid-row">
                        <div class="dmiux_grid-col dmiux_grid-col_auto">
                            <div class="dmiux_checkbox">
                                <input @change="ignore_empty = true" type="checkbox" class="dmiux_checkbox__input" v-model="ignore_errors">
                                <div class="dmiux_checkbox__check"></div>
                                <div class="dmiux_checkbox__label">Ignore errors on import</div>
                            </div>
                            <div class="dmiux_checkbox">
                                <input :disabled="ignore_errors" type="checkbox" class="dmiux_checkbox__input" v-model="ignore_empty">
                                <div class="dmiux_checkbox__check"></div>
                                <div class="dmiux_checkbox__label">Ignore empty lines on import</div>
                            </div>
                            <div class="dmiux_checkbox">
                                <input type="checkbox" class="dmiux_checkbox__input" v-model="has_columns">
                                <div class="dmiux_checkbox__check"></div>
                                <div class="dmiux_checkbox__label">First row has column names</div>
                            </div>
                            <div class="dmiux_checkbox" v-if="this.is_replacing || this.is_appending">
                                <input type="checkbox" class="dmiux_checkbox__input" v-model="file_format_matches">
                                <div class="dmiux_checkbox__check"></div>
                                <div class="dmiux_checkbox__label">File format matches table columns</div>
                            </div>
                        </div>
                    </div>
                </form>
                <template v-else-if="isSuccess">
                    <div v-if="has_mismatched_column_count" class="alert alert-danger alert-no-icon">
                        <p><strong>Uh oh.</strong></p>
                        <p>The number of columns in this file do not match the number of columns in the destination table.</p>
                        <p><strong>The number of columns must be equal to proceed.</strong></p>
                    </div>
                    <div v-else-if="is_appending && has_mismatched_column_names" class="alert alert-warning alert-no-icon">
                        <p><strong>Uh oh.</strong></p>
                        <p>The column names in this file do not match the column names in the destination table. You may continue, 
                        but you will need to map the columns in the source file to the columns in the destination table.</p>
                        <p><strong>All columns must be mapped before your import may be submitted.</strong></p>
                    </div>
                    <template v-else-if="is_replacing && (has_mismatched_column_names || ! file_format_matches) && dependant_views.length > 0">
                        <div class="alert alert-warning alert-no-icon">
                            <p><strong>Uh oh.</strong></p>
                            <p>Rebuilding the table is required for this file. You may continue, 
                            but you may lose the views related to this table when you replace it.</p>
                        </div>
                        <div class="mx-1">
                            <a href="#" class="cursor-p" @click="show_extra = ! show_extra" v-if="! show_extra">Show dependant views</a>
                            <a href="#" class="cursor-p" @click="show_extra = ! show_extra" v-else>Hide dependant views</a>
                            <ul v-if="show_extra">
                                <li class="add-table_view_overflow" :title="view.trim()" v-for="view in dependant_views">{{ view.trim() }}</li>
                            </ul>
                        </div>
                    </template>
                    <div v-else class="alert alert-success alert-no-icon"><b>Hooray! </b> Your file upload was successful.</div>
                </template>
                <div class="progress" v-if="isSaving">
                    <span v-if="upload_percentage == 100" class="progress-bar-text">Processing file...</span>
                    <span v-else class="progress-bar-text">{{ upload_percentage }}%</span>
                    <div class="progress-bar progress-bar-striped" role="progressbar" v-bind:aria-valuenow="upload_percentage" v-bind:aria-valuemin="upload_percentage" aria-valuemax="100" v-bind:style="{width: upload_percentage + '%'}">
                    
                    </div>
                </div>
            </div>
            <div class="dmiux_popup__foot">
                <div class="dmiux_grid-row">
                    <div class="dmiux_grid-col">
                        <template v-if="isInitial || isFailed">
                            <button class="dmiux_button float-right ml-1" type="button" @click="addTable();">Upload</button>
                        </template>
                        <template v-if="isSuccess && ! has_mismatched_column_count">
                            <button v-if="file_format_matches && is_appending && ! has_mismatched_column_names" class="dmiux_button dmiux_button_primary float-right ml-1" type="button" @click="appendData()">Append Data</button>
                            <button v-else-if="file_format_matches && is_replacing && ! has_mismatched_column_names" class="dmiux_button dmiux_button_primary float-right ml-1" type="button" @click="replaceData()">Replace Data</button>
                            <button v-else class="dmiux_button dmiux_button_primary float-right ml-1" type="button" @click="mapData();">Next: Map Fields</button>
                        </template>
                        <button v-if="has_mismatched_column_count" class="dmiux_button dmiux_button_primary float-right ml-1" type="button" @click="modalRestart($event)" id="back-button-add-table">Back</button>
                        <button class="dmiux_button dmiux_button_secondary dmiux_popup__close_popup dmiux_popup__cancel float-right mx-1" type="button" @click="modalClose($event)" id="cancel-button-add-table">Cancel</button>
                    </div>
                </div>	
            </div>
		</div>
	</div>
</script>

<script>
    const STATUS_INITIAL = 0, STATUS_SAVING = 1, STATUS_SUCCESS = 2, STATUS_FAILED = 3;
    var addTable = {
        template: '#table-modal-template',
        props: [ 'control_id', 'breadcrumbs' ],
        data() {
            return {
                table_id: 0,
                delimiter: ",",
                escape: '\\',
                encoding: "utf-8",
                has_columns: true,
                enclosed: '"',
                ignore_errors: true,
                ignore_empty: true,
                files: [],
                currentStatus: null,
                file_name: "",
                is_replacing: false,
                upload_percentage : 0,
                upload_file_name: "",
                dependant_views: [],
                show_extra: false,
                is_uploading: false,
                is_appending: false,
                file_format_matches: false,
                has_mismatched_column_names: false,
                has_mismatched_column_count: false,
                transfer_token: null
            }
        },
        computed: {
            isInitial() {
                return this.currentStatus === STATUS_INITIAL;
            },
            isSaving() {
                return this.currentStatus === STATUS_SAVING;
            },
            isSuccess() {
                return this.currentStatus === STATUS_SUCCESS;
            },
            isFailed() {
                return this.currentStatus === STATUS_FAILED;
            },
            hasValidFileSelected() {
                return this.upload_file_name.length > 0;
            },
            truncatedFileName() {
                // Middle-truncate the filename if its length is > 30, return original if not
                if(this.upload_file_name.length <= 30) {
                    return this.upload_file_name;
                }

                return this.upload_file_name.slice(0, 20) + '....' + this.upload_file_name.split('.').pop();
            }
        },
        watch: {
            is_uploading() {
                if (this.is_uploading) {
                    // Remove any handlers that may accidentally close the modal while uploading a file.
                    $(document).off("mousedown", "#dmiux_body", this.modalClose);
                }
            }
        },
        methods: {
            mapData() {
                var database_name = this.breadcrumbs[0].title;
                window.location.href = `/data-lake/database-manager/${this.control_id}/map` +
                                       `?file_name=${this.file_name}` +
                                       `&table_id=${this.table_id}` +
                                       `&database_name=${database_name}` +
                                       `&delimiter=${this.delimiter}` +
                                       `&escape=${this.escape}` +
                                       `&encoding=${this.encoding}` +
                                       `&enclosed=${this.enclosed}` +
                                       `&ignore_errors=${this.ignore_errors}` +
                                       `&ignore_empty=${this.ignore_empty}` +
                                       `&has_columns=${this.has_columns}` +
                                       `&replace=${this.is_replacing}` + 
                                       `&append=${this.is_appending}` +
                                       `&table_name=${this.$root.$refs.databaseManager.selectedTable.name}`;
            },
            async addTable() {
                if(! this.hasValidFileSelected) {
                    alert("Select a valid file to be uploaded.");
                    return;
                }

                let upload_token = await this.getUploadToken();

                if (upload_token == null) {
                    notify.danger('There was a problem initializing your upload.');
                    return;
                }

                FileUploader.init(`${file_upload_url}/upload`, upload_token);

                FileUploader.on_update = this.progressHandler;
                FileUploader.on_success = this.completeHandler;
                FileUploader.on_error = this.errorHandler;

                FileUploader.upload(document.querySelector('#input-upload_table'));

                this.is_uploading = true;

                this.currentStatus = STATUS_SAVING;
            },
            handleEscape() {
                if(this.enclosed == "") {
                    this.escape = "\\";
                }
            },
            progressHandler(event) {
                this.upload_percentage = event.progress;
            },
            completeHandler(json_response) {
                if(json_response.transfer_token == undefined) {
                    this.currentStatus = STATUS_FAILED;
                    alert("A problem occurred.");
                    return false;
                }
                
                this.transfer_token = json_response.transfer_token;

                this.transferFile();            
            },
            errorHandler(event) {
                alert("A problem occurred when attempting to upload your file.");
                this.upload_file_name = '';
                this.currentStatus = STATUS_FAILED;
                return false;
            },
            reset() {
                // reset form to initial state
                this.table_id = 0;
                this.currentStatus = STATUS_INITIAL;
                this.uploadedFile = [];
                this.files = [];
                this.upload_file_name = "";
                this.upload_percentage = "";
                this.is_replacing = false;
                this.encoding = "utf-8";
                this.escape = '\\';
                this.delimiter = ",";
                this.has_columns = true;
                this.dependant_views = [];
                this.show_extra = false;
                this.ignore_errors = true;
                this.ignore_empty = true;
                this.enclosed = '"';
                this.is_appending = false;
                this.file_format_matches = false;
                this.has_mismatched_column_names = false;
                this.has_mismatched_column_count = false;
                var element = document.getElementById("input-upload_table");
                if(element != undefined) {
                    element.value = '';
                }
                this.is_uploading = false;
                this.transfer_token = null;
            },
            filesChange(fieldName, fileList, fileLabel) {
                if (!fileList.length) {
                    this.upload_file_name = '';
                    return;
                }

                if(fileList.length > 1) {
                    this.upload_file_name = '';
                    alert("Only one file is allowed.");
                    return;
                }

                if(!this.checkFile(fileList[0])) {
                    this.upload_file_name = '';
                    return;
                }

                this.upload_file_name = fileList[0].name;

                this.files = fileList;
                return true;
            },
            checkFile(file) {
                var validExts = [ ".csv", ".txt", ".pipe", ".psv", ".tab", ".tsv", ".zip" ];
                var fileExt = file.name;
                fileExt = fileExt.substring(fileExt.lastIndexOf('.')).toLowerCase();
                if (validExts.indexOf(fileExt) < 0) {
                    alert("Invalid file selected, valid files are of " + validExts.toString() + " types.");
                    return false;
                }
                else {
                    return true;
                }
            },
            modalClose(event) {
                if (event != undefined) {
                    event.stopPropagation();
                    if(event.key != undefined) {
                        if(event.key != 'Escape') // not escape
                            return;
                    }
                    else {
                        var clicked_element = event.target;
                        if (clicked_element.closest(".dmiux_popup__window")) {
                            // You clicked inside the modal
                            if (clicked_element.id != "x-button-add-table" && clicked_element.id != "cancel-button-add-table")
                                return;
                        }
                    }
                }

                FileUploader.abort();

                $(document).off("mousedown", "#dmiux_body", this.modalClose);
                $(document).off("mousedown", "#dmiux_body", this.modalClose);

                closeModal();
            },
            modalOpen() {
                this.reset();
                $('#dmiux_overlay').addClass('dmiux_overlay_visible');
                $('#modal-add_table').addClass('dmiux_popup_visible').siblings('.popup').removeClass('dmiux_popup_visible');
                $('#dmiux_body').addClass('suppress_scrolling');
                $(document).off("keydown", closeModalOnEscape);
                $(document).on("mousedown", "#dmiux_body", this.modalClose);
                $(document).on("keydown", this.modalClose);
            },
            modalRestart() {
                this.currentStatus = STATUS_INITIAL;
                this.upload_file_name = '';
                this.has_mismatched_column_names = false;
                this.has_mismatched_column_count = false;
            },
            appendData() {
                let options = FetchHelper.buildJsonRequest({
                    table_name: this.$root.$refs.databaseManager.selectedTable.name,
                    table_id: this.table_id,
                    file_name: this.file_name,
                    encoding: this.encoding,
                    escape: this.escape,
                    enclosed: this.enclosed,
                    delimiter: this.delimiter,
                    ignore_errors: this.ignore_errors,
                    ignore_empty: this.ignore_empty,
                    has_columns: this.has_columns,
                    is_replacing: false,
                    is_appending: true
                }, 'put');

                fetch(`/internal-api/v1/data-lakes/${this.$root.control_id}/tables`, options)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        $(document).off("click", "#dmiux_body", this.modalClose);
                        $(document).off("mousedown", "#dmiux_body", this.modalClose);

                        closeModal();

                        notify.success("Your data is being imported.");
                    })
                    .catch(error => {
                        ResponseHelper.handleErrorMessage(error, 'There was an issue appending the data to the table.');
                    });
            },
            replaceData() {
                let options = FetchHelper.buildJsonRequest({
                    table_name: this.$root.$refs.databaseManager.selectedTable.name,
                    table_id: this.table_id,
                    file_name: this.file_name,
                    encoding: this.encoding,
                    escape: this.escape,
                    enclosed: this.enclosed,
                    delimiter: this.delimiter,
                    ignore_errors: this.ignore_errors,
                    ignore_empty: this.ignore_empty,
                    has_columns: this.has_columns,
                    is_replacing: true,
                    is_appending: false
                }, 'put');

                fetch(`/internal-api/v1/data-lakes/${this.$root.control_id}/tables`, options)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        $(document).off("click", "#dmiux_body", this.modalClose);
                        $(document).off("mousedown", "#dmiux_body", this.modalClose);

                        closeModal();

                        notify.success("Your data is being imported.");
                    })
                    .catch(error => {
                        this.upload_file_name = '';
                        ResponseHelper.handleErrorMessage(error, 'There was an issue replacing the data in the table.');
                    });
            },
            validateFileColumns() {
                this.is_uploading = false;

                let options = FetchHelper.buildJsonRequest({
                    delimiter: this.delimiter,
                    has_columns: this.has_columns,
                    table_name: this.$root.$refs.databaseManager.selectedTable.name,
                    temp_file: this.file_name,
                    is_replacing: this.is_replacing
                });

                fetch(`/internal-api/v1/data-lakes/${this.$root.control_id}/tables/compare-columns`, options)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.currentStatus = STATUS_SUCCESS;
                    })
                    .catch((error) => {
                        try {

                            if (error.json.data['column_mismatch']) {
                                this.has_mismatched_column_names = true;
                            }

                            if (error.json.data['counts_mismatch']) {
                                this.has_mismatched_column_count = true;
                            }

                            this.currentStatus = STATUS_SUCCESS;
                        } catch (e) {
                            this.currentStatus = STATUS_FAILED;
                            ResponseHelper.handleErrorMessage(error, 'File could not be validated.');
                        }
                    });
            },
            async getUploadToken() {
                try {
                    let response = await fetch(`/internal-api/v1/uploads`, { method: 'post' });

                    if (! response.ok) {
                        throw new Error('Looks like an invalid response came in.');
                    }

                    json = await response.json();

                    return json.data.token;
                } catch(err) {
                    return null;
                }
            },
            transferFile() {
                fetch(`/internal-api/v1/uploads/${this.transfer_token}`)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.currentStatus = STATUS_SUCCESS;
                        this.file_name = json.data;
                        if (this.is_appending || this.is_replacing) {
                            this.validateFileColumns();
                            return;
                        }
                        this.currentStatus = STATUS_SUCCESS;
                    })
                    .catch((error) => {
                        this.currentStatus = STATUS_FAILED;
                        this.upload_file_name = '';
                        ResponseHelper.handleErrorMessage(error, 'There was an issue uploading your file.');
                    });
                
            }
        },
        mounted() {
            this.reset();

            // Hook into the parent event for appending data to a table
            let modal = this;
            this.$root.$on('appendDataToTable', function(data){
                modal.reset();
                modal.is_appending = true;
                modal.file_format_matches = true;
                modal.table_id = data.table_id;
                openModal('#modal-add_table');
            });
        }
    }
</script>