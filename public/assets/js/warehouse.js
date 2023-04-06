var baseUrl = "";
Vue.component('multiselect', window.VueMultiselect.default);

const db_password_length = 10;
const db_password_charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

/** Vue.js Component
 *******************************************************************************
 * card-warehouse
 *******************************************************************************
 * Renders the listing of available databases on card format
 */
var cardDatabases = Vue.component('card-warehouse', {
    template: '#component-card-databases',
    props: ['databases', 'tags', 'callbacks'],
    data() {
        return {
            file: '',
            action: '',
            library: '',
            database: {},
            adding_tag: false,
            show_unauthorized: false
        }
    },
    created() {
        setInterval(() => {
          this.runClock();
        }, 1000)
    },
    methods: {
        manageTables(control_id) {
            app.loading(true);
            window.location.href = baseUrl + "/data-lake/database-manager/" + control_id;
            
        },
        /** Method to View Integration Logs (il) **/
        integrationLogs(control_id) {
            openModal("#modal-integration-logs");
            this.$root.modals.integration_logs = true;
            this.$root.$refs.integration_logs.control_id = control_id;
        },
        /** Method gets all dependencies related to database **/
        getDependenciesRelatedToDatabase(database_id) {
            fetch(baseUrl + `/internal-api/v1/data-lakes/${database_id}/dependencies`)
                .then(response => response.json())
                .then(json => {
                    app.databaseDependencies = json.data;
                });
        },
        /** Active default tab in project settings  **/
         defaultActiveTab(){
            $('[data-tab-open]').off('click');

            $('[data-tab-open]').on('click', function(event) {
                var target = $($(this).data('tab-open'));
                
                if( target.hasClass('dmiux_removed') ) {
                    $(this).addClass('dmiux_active').siblings().removeClass('dmiux_active');
                    target.removeClass('dmiux_removed').siblings().addClass('dmiux_removed');
                }
                else {
                    target.addClass('visible').siblings().removeClass('visible');
                }
            });
            $("#general-tab-heading").click();
        },
        /** Method to Manage Database Settings (is) **/
        getDatabase(control_id) {
            app.loadingUntilReady(['database', 'schedule_types', 'partner_integration_schedule_types', 'connector_settings']);
            fetch(baseUrl + "/internal-api/v1/data-lakes/" + control_id)
                .then(response => response.json())
                .then(json => {
                    if (!app.checkForError(json)) {
                        return;
                    }

                    this.database = json.data;
                    this.getDependenciesRelatedToDatabase(control_id);
                    app.selectedDatabase = this.database; 

                    fetch(baseUrl + `/internal-api/v1/data-lakes/${app.selectedDatabase.id}/sql-user`)
                        .then(FetchHelper.handleJsonResponse)
                        .then(json => {
                            if (!app.checkForError(json)) {
                                return;
                            }
                            app.selectedDatabase["user"] = json.data.user;
                            app.selectedDatabase["isReadOnly"] = json.data.exists;
                            app.selectedDatabase["isReadOnlyOrig"] = json.data.exists;

                            this.getCallbacks();
                        }) 
                        .catch((error) => {
                            app.selectedDatabase["user"] = "";
                            app.selectedDatabase["isReadOnly"] = false;
                            app.selectedDatabase["isReadOnlyOrig"] = false;

                            this.getCallbacks();
                        });     
                    });  
        },
        getCallbacks() {
            fetch(baseUrl + `/internal-api/v1/data-lakes/${app.selectedDatabase.id}/callbacks`)
                .then(response => response.json())
                .then(json => {
                    if (!app.checkForError(json)) {
                        return;
                    }
                    app.callbacks = json.data;
                    openModal("#modal-project-settings");
                    this.defaultActiveTab();
                    app.ready.database = true;
                });
        },
        /** Method called whenever a dropdown selection has changed **/
        get_action(database, event) {
            var type = event.target.value;
            if (type === "il") {
                this.integrationLogs(database.id)
            } else if (type === "is") {
                this.getDatabase(database.id);
            } else if (type === "mt") {
                this.manageTables(database.id);
            } else if (type === "rif") {
                database.is_running = true;
                this.$root.runIntegration(database.id);
            } else if (type === "rit") {
                this.$root.getDatabaseJobs(database.id);
            }
            event.target.value = "";
        },
        runClock() {
            if (system_time_offset === undefined) {
                system_time_offset = 0;
            }

            var date_with_offset = new Date();
            date_with_offset.setSeconds(date_with_offset.getSeconds() + parseFloat(system_time_offset));

            app.datetime = this.getUTCFormattedDate(date_with_offset);
        },
        getUTCFormattedDate(date) {
            var year = date.getUTCFullYear();
            var month = (1 + date.getUTCMonth()).toString().padStart(2, '0');
            var day = date.getUTCDate().toString().padStart(2, '0');
            var hour = date.getUTCHours().toString().padStart(2, '0');  
            var minute = date.getUTCMinutes().toString().padStart(2, '0');
            var second = date.getUTCSeconds().toString().padStart(2, '0');
            return month + '/' + day + '/' + year + '  ' + hour + ':' + minute + ':' + second; 
        },
        addDatabaseTag(tag, database, index)
        {
            if(this.adding_tag) {
                return;
            }

            var tag_id = tag.id;
            var in_database = this.inDatabase(tag_id, index);

            let options = FetchHelper.buildJsonRequest({
                tag_id: tag_id
            }, in_database ? 'delete' : 'post');

            this.adding_tag = true;
            fetch(`${baseUrl}/internal-api/v1/data-lakes/${database.id}/tags`, options)
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    this.adding_tag = false;
                    this.$root.databases.user_databases = this.$root.databases.user_databases.map(function(value) {
                        if(value.id == database.id) {
                            value.tags = json.data;
                        }

                        return value;
                    });
                })
                .catch((error) => {
                    if(error.json != null) { 
                        ResponseHelper.handleErrorMessage(error, error.json.message); 
                    } else {
                        ResponseHelper.handleErrorMessage(error, `There was a problem ${data.type}ing the label from the project.`); 
                    }
                });
        },
        inDatabase(tag_id, index)
        {
            var check = this.databases.user_databases[index].tags.filter(function(tag){ 
                if(tag.tag_id == tag_id){
                    return tag;
                }
            });

            return check != null && check.length > 0;
        },
        toggle_dropdown(id)
        {
            var elem = document.getElementById("warehouse_tags_" + id);

            if(elem.style.opacity == undefined || elem.style.opacity  == 0)
            {
                elem.style.opacity = 1;
                elem.style.pointerEvents = "auto";
            }
            else
            {
                elem.style.opacity = 0;
                elem.style.pointerEvents = "none";
            }
        },
        headerClass(color) {
            if (color == 'red' || color == 'build_red') {
                return 'dmiux_db-card--header-error';
            } else if (color == 'yellow') {
                return 'dmiux_db-card--header-warning';
            }
            return '';
        },
        headerTooltip(color, jobs) {
            if (color == 'yellow') {
                return 'Bytespree is unable to connect to the API for this database.<br/> ' +
                    'Confirm that your credentials are correct and either run the<br/> ' +
                    'integration again or wait for the next scheduled run to occur.';
            }
    
            if (jobs === null) return '';
            
            let base_string = "The following tables are in an error status ";
            let sync_errors = "";
            let red_count = 0;

            for (let index = 0; index < jobs.length; index++) {
                if(red_count == 0) {
                    sync_errors = "<ul>"
                    sync_errors = sync_errors + "<li>" + jobs[index] + "</li>";
                } else { 
                    sync_errors = sync_errors + "<li>" + jobs[index] + "</li>";
                }

                red_count++;
            }

            if (red_count >0) {
                sync_errors += '</ul>';
            }

            if(red_count != 0) {
                base_string = base_string + "<br/>" + sync_errors;
            }

            return base_string;
        },
        toggleUnauthorized() {
            let element = document.getElementById('section-other-databases');
            if (this.show_unauthorized) {
                this.show_unauthorized = false;
                element.style.maxHeight = '0px';
            }
            else {
                this.show_unauthorized = true;
                element.style.maxHeight = '2000px';
                setTimeout(() => {
                    // After CSS transition, make sure everything fits
                    element.style.maxHeight = 'max-content';
                    let offsetHeight = element.offsetHeight;
                    element.style.maxHeight = offsetHeight + 'px';
                }, 501);
            }
        },
        requestAccess(database) {
            this.$root.requestedDatabase = database;
            this.$root.requestedDatabase.settings = [];
            this.$root.requestedDatabase.tables = [];
            this.$root.modals.request_access = true;
        }
    }
});

/** Vue.js Component
 *******************************************************************************
 * integration-logs
 *******************************************************************************
 * Renders the integration integration logs in a modal for the selected
 * database.
 */
Vue.component('integration-logs', {
    template: '#integration-logs-modal-template',
    props: [ "open" ],
    data() {
        return {
            control_id: 0,
            filters: {
                'table': '',
                'status': '',
                'type': ''
            },
            integration_logs: [],
            tables: [],
            console_text: "",
            build_id: 0,
            current_index: 0
        }
    },
    watch: {
        open() {
            if(this.open == true) {
                $(document).on("mousedown", "#dmiux_body", this.clearModal);
                $(document).on("keydown", this.clearModal);

                this.getLogs();
            }
        }
    },
    methods: {
        getLogs() {
            let filterString = (new URLSearchParams(this.filters)).toString();
            this.$root.loading(true);
            fetch(baseUrl + `/internal-api/v1/data-lakes/${this.control_id}/logs?${filterString}`)
                .then(response => {
                    this.$root.loading(false);
                    return response;
                })
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    this.integration_logs = json.data.logs;
                    this.tables = json.data.tables;

                    $("#integration-logs-table").DataTable().destroy();
                })
                .then(() => {
                    $("#integration-logs-table").DataTable();
                })
                .catch((error) => {
                    ResponseHelper.handleErrorMessage(error, 'An error occurred while attempting to get integration logs.');
                });
        },
        sendLogEmail(index) {
            this.$root.loading(true);
            fetch(baseUrl + `/internal-api/v1/data-lakes/${this.control_id}/logs/${this.integration_logs[index].id}?email`)
                .then(response => {
                    this.$root.loading(false);
                    return response;
                })
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    notify.success("This log has been sent to your email.");
                })
                .catch((error) => {
                    ResponseHelper.handleErrorMessage(error, 'Log could not be retrieved. Email has not been sent.');
                });
        },
        getLogText(index) {
            this.$root.loading(true);
            fetch(baseUrl + `/internal-api/v1/data-lakes/${this.control_id}/logs/${this.integration_logs[index].id}`)
                .then(response => {
                    this.$root.loading(false);
                    return response;
                })
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    if(json.data.hasOwnProperty('got_all')) {
                        if(json.data.got_all == false) {
                            notify.info("The log requested is very large and only a preview is provided.  Please download the log to see it in full.");
                        }

                        this.console_text = json.data.console_text;
                    } else {
                        this.console_text = json.data;
                    }

                    this.build_id = this.integration_logs[index].id;
                    this.current_index = index;
                    this.$forceUpdate();
                })
                .then(() => {
                    var elem = document.getElementById("build_log");
                    if(elem != undefined)
                        elem.scrollIntoView({behavior: "smooth" });
                })
                .catch((error) => {
                    ResponseHelper.handleErrorMessage(error, 'Log could not be retrieved');
                });
        },
        setFilters() {
            this.console_text = "";
            this.getLogs();
        },
        clearModal(event) {
            event.stopPropagation();
            if(event.key != undefined) {
                if(event.key != 'Escape') // not escape
                    return;
            }
            else {
                var clicked_element = event.target;
                if (clicked_element.closest(".dmiux_popup__window")) {
                    // You clicked inside the modal
                    if (clicked_element.id != "x-button" && !(clicked_element.classList.contains("dmiux_popup__cancel")))
                        return;
                }
            }
            $(document).off("mousedown", "#dmiux_body", this.clearModal);
            $(document).off("keydown", this.clearModal);

            this.$root.modals.integration_logs = false;
            this.filters = {
                'table': '',
                'status': '',
                'type': ''
            };
            this.integration_logs = [];
            this.console_text = "";
            closeModal('#modal-integration-logs');
        }
    }
});

Vue.component('integration-manager', {
    template: '#integration-manager-modal-template',
    props: [ 'jobs' ],
    methods: {
        runIntegration(name, database, index)
        {
            app.database_jobs[index].is_running = true;
            app.loading(true);

            let options = FetchHelper.buildJsonRequest({job: name});

            fetch(baseUrl + `/internal-api/v1/data-lakes/${database}/jobs/run`, options)
                .then(response => response.json())
                .then(json => {
                    if (!app.checkForError(json)) {
                        return;
                    }

                    notify.send('Synchronization has started for ' + name + '.', 'success');
                    app.getDatabases();
                    app.loading(false);
                });
        }
    }
});

var connector_settings = Vue.component('connector-settings', {
    template: '#connector-settings-template',
    props: [ 'settings', 'table', 'table_index' ],
    methods: {
        valueChanged(index, setting, table, event) {
            if(setting.data_type == 'number') {
                setting.value = setting.value.replace(/[^0-9.]/g, '');
            }

            if(table == "") {
                app.selectedDatabase.settings[index].changed = true;
            } else {
                setting.changed = true;
            }
        },
        getProperty(key, properties, fallback_value) {
            if(properties != null && key in properties && properties[key] != undefined) {
                return properties[key];
            }
            
            return fallback_value;
        }
    }
});

/** Vue.js Component
 *******************************************************************************
 * project-settings
 *******************************************************************************
 * Renders the new integration setup wizard in a modal.
 */
var projectSettings = Vue.component('project-settings', {
    template: '#project-settings-template',
    props: ['settings', 'selectedDatabase', 'callbacks', 'schedule_types', 'dependencies'],
    data() {
        return {
            callbackToUpsert: false,
            cbType: "",
            savedURL: "",
            schedule_type_id: 0,
            schedule_id: 0,
            table_schedule_type: "one",
            previous_schedule_type_id: 0,
            schedule: null,
            types: [],
            properties: [],
            available_tables: [],
            selected_tbl: {
                table_name: '',
                used: false
            },
            table_selected: true,
            has_tables: false,
            sql_user: ''      // this will be add, delete, or update
        }
    },
    components: {
        connector_settings
    },
    watch: {
        selectedDatabase() {
            this.getIntegrationScheduleTypes();
            if (this.selectedDatabase.use_tables === true) {
                this.getTables();
            }
            else {
                app.ready.connector_settings = true;
            }
        }
    },
    methods: {
        /**  Run when modal is exited - by cancel, by x-ing out, or by clicking out of modal  **/
        clearData() {
            this.savedURL = "";
            this.cbType = "";
            this.callbackToUpsert = false;
            app.run_clock = false;
            this.callbacks = [];
        },
        cleanupServerThreshold() {
            if(this.selectedDatabase.server.alert_threshold == '') {
                this.selectedDatabase.server.alert_threshold = 75;
            }

            this.selectedDatabase.server.alert_threshold = parseFloat(this.selectedDatabase.server.alert_threshold).toFixed(2);

            if(this.selectedDatabase.server.alert_threshold > 100) {
                this.selectedDatabase.server.alert_threshold = 100;
            }
        },
        /**  Methods for Callback Tab  **/
        deleteCallback(callbackId) {
            if(!confirm("Are you sure you want to delete this callback?")) {
                return false;
            }
            let options = FetchHelper.buildJsonRequest({'callback_id': callbackId}, 'delete');
            fetch(baseUrl + `/internal-api/v1/data-lakes/${app.selectedDatabase.id}/callbacks`, options)
            .then(response => response.json())
            .then(json => {
                if (!app.checkForError(json)) {
                    return;
                }
                fetch(baseUrl + `/internal-api/v1/data-lakes/${app.selectedDatabase.id}/callbacks`)
                .then(response => response.json())
                .then(json => {
                    if (!app.checkForError(json)) {
                        return;
                    }
                    app.callbacks = json.data;
                });

            });
        },
        editingStart(cbt, callbackKey) {
            this.cbType = cbt;
            if(callbackKey !== false) { //Editing existing callback
                this.callbackToUpsert = callbackKey;
                this.savedURL = this.callbacks[this.callbackToUpsert].callback_url;
                if (this.cbType === 'redo') {  // Edit the callback key only
                    if(!confirm("Are you sure you want to generate a new key for this callback?")) {
                        this.callbackToUpsert = false;
                        return false;
                    }
                    var key = this.generateKey(20);
                    var id = this.callbacks[this.callbackToUpsert].id; 
                    var callback_url = this.callbacks[this.callbackToUpsert].callback_url; 
                    this.callbacks[this.callbackToUpsert].key = key;
                    this.saveCallback(id, callback_url, key);
                    this.callbackToUpsert = false;
                }
            }
            else {  //New callback
                this.callbacks.push({});
                this.callbackToUpsert = (this.callbacks.length - 1);
                setTimeout(function() { 
                    document.getElementById("url-edit").focus(); 
                }, 500);
            }
        },
        editingSave() {
            var callback_url = this.callbacks[this.callbackToUpsert].callback_url;
            if (this.cbType === "new") {
                var key = this.generateKey(20);
                this.callbacks[this.callbackToUpsert].key = key;
            } else {
                var key = this.callbacks[this.callbackToUpsert].key;
            }
            if (callback_url === "" || callback_url === undefined) {
                alert("You must specify the URL.");
                return false;
            }
            // Make sure the callback url and key has not already been used
            if (this.callbackToUpsert > 0) {
                var settingsOK = true;
                for (var i = 0; i < this.callbacks.length - 1; i++) {
                    if (this.callbacks[i].callback_url === callback_url && this.callbacks[i].key === key) {
                        alert("the callback url and key you specified has already been used.  Try again using a different url/key combination, or delete the previous callback.");
                        settingsOK = false;
                        break;
                    }
                }
                if (!settingsOK) {
                    this.callbacks.pop({});
                    fetch(baseUrl + `/internal-api/v1/data-lakes/${app.selectedDatabase.id}/callbacks`)
                        .then(response => response.json())
                        .then(json => {
                            if (!app.checkForError(json)) {
                                return;
                            }
                            app.callbacks = json.data;
                            return;
                        }); 
                }
            }
            if (this.cbType === "new") {
                var id = 0;
            } else {
                var id = this.callbacks[this.callbackToUpsert].id;
            }
            this.saveCallback(id, callback_url, key);
        },
        saveCallback(cbId, cbUrl, cbKey) {
            if(this.cbType === "edit" || this.cbType === "redo") { // We're updating a callback
                var options = FetchHelper.buildJsonRequest({
                    callback_url: cbUrl,
                    callback_key: cbKey,
                    callback_id: cbId
                }, 'put');
            }
            else { // We're creating a callback
                var options = FetchHelper.buildJsonRequest({
                    callback_url: cbUrl,
                    callback_key: cbKey
                }, 'post');
            }
            fetch(baseUrl + `/internal-api/v1/data-lakes/${app.selectedDatabase.id}/callbacks`, options)
                .then(response => response.json())
                .then(json => {
                    if (!app.checkForError(json)) {
                        return;
                    }
                    if (this.cbType === "new") {
                        this.callbacks[this.callbackToUpsert] = json.data;
                    }
                    this.callbackToUpsert = false;
                });
        },
        generateKey(length) {
            var result           = '';
            var characters       = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            var charactersLength = characters.length;
            for ( var i = 0; i < length; i++ ) {
                if (i != 0 && i % 4 === 0) {
                    result += "-";
                }
               result += characters.charAt(Math.floor(Math.random() * charactersLength));
            }
            return result;
        },
        isUpgradeAvailable(tap_version, version) {
            return version_compare(tap_version, version);
        },
        upgradeConnector() {
            if(! confirm('Are you sure you want to upgrade this database?')) {
                return;
            }

            this.$root.loading(true);
            fetch(`/internal-api/v1/data-lakes/${this.$root.selectedDatabase.id}/version`, {method: 'put'})
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    this.$root.loading(false);
                    this.$root.selectedDatabase.tap_version = this.$root.selectedDatabase.version;
                    notify.success('Database has been successfully upgraded.');
                })
                .catch((error) => {
                    this.$root.loading(false);
                    ResponseHelper.handleErrorMessage(error, "Failed to upgrade database.");
                });
        },
        editingCancel() {
            if(!this.callbacks[this.callbackToUpsert].id) {
                this.callbacks.pop();
            } else {
                this.callbacks[this.callbackToUpsert].callback_url = this.savedURL;
            }
            this.callbackToUpsert = false;
        },
        /**  End of Methods for Callback Tab  **/
        /**  Methods for Integration Tab  **/
        showValue(index) {
            var id = app.selectedDatabase.settings[index].id;
            app.loading(true);
            fetch(`${baseUrl}/internal-api/v1/data-lakes/${app.selectedDatabase.id}/settings/${id}`)
                .then(response => response.json())
                .then(json => {
                    if (!app.checkForError(json)) {
                        return;
                    }
                    app.selectedDatabase.settings[index].is_secure = false;
                    app.selectedDatabase.settings[index].value = json.data.value;
                    app.loading(false);
                });
        },
        showTableValue(table_index, setting_name) {
            var id = app.selectedDatabase.tables[table_index].settings[setting_name].id;
            app.loading(true);
            fetch(`${baseUrl}/internal-api/v1/data-lakes/${app.selectedDatabase.id}/settings/${id}`)
                .then(response => response.json())
                .then(json => {
                    if (!app.checkForError(json)) {
                        return;
                    }
                    app.selectedDatabase.tables[table_index].settings[setting_name].is_secure = false;
                    app.selectedDatabase.tables[table_index].settings[setting_name].value = json.data.value; 
                    app.loading(false);
                });
        },
        /**  End of Methods for Integration Tab  **/
        /**  Methods for Tables Tab  **/
        getTables() {
            var id = this.selectedDatabase.integration_id;
            app.loading(true);
            var options = FetchHelper.buildJsonRequest({
                database_id: this.selectedDatabase.id
            });
            fetch(`${baseUrl}/internal-api/v1/connectors/${id}/tables`, options)
                .then(response => {
                    if (!response.ok) {
                        this.has_tables = false;
                        this.available_tables = [];
                        app.ready.connector_settings = true;
                        notify.danger("Unable to retrieve tables. Check connector settings.");
                    }
                    else {
                        response.text().then(text => {
                            var json = null;
                            try {
                                json = JSON.parse(text);
                            }
                            catch(e) {
                                json = {
                                    "status" : "error",
                                    "message" : "Unable to retrieve tables."
                                };
                            }
                            if(!app.checkForError(json)) {
                                this.has_tables = false;
                                this.available_tables = [];
                                app.ready.connector_settings = true;
                                return;
                            }
                            this.available_tables = json.data.tables;

                            if (this.available_tables.length > 0) {
                                this.has_tables = true;
                                this.table_selected = true;  // needs to be true for the first time a table is added
                                this.selected_tbl = {
                                    table_name: '',
                                    used: false
                                };
                                // if selectedDatabase has tables already, those tables in available_tables need to be set used = true
                                for (var i=0; i<this.available_tables.length; i++) {
                                    for (var j=0; j<this.selectedDatabase.tables.length; j++) {
                                        this.selectedDatabase.tables[j].added = false;
                                        if (this.available_tables[i].table_name == this.selectedDatabase.tables[j].name) {
                                            this.available_tables[i].used = true;
                                        }
                                    }
                                }
                            }
                            else {
                                this.has_tables = false;
                            }
                            app.ready.connector_settings = true;
                        });
                    }
                })
        },
        addThisTable(table, index) {
            var table_settings = this.buildTableSettings();
            this.selectedDatabase.tables[index].settings = table_settings;
            this.table_selected = true;
            
            // Find index of table in available_tables
            var idx = -1;
            for (var i=0; i<this.available_tables.length; i++) {
                if (table == this.available_tables[i].table_name) {
                    idx = i;
                    break;
                }
            }

            // Find out if available table is in selectedDatabase but "soft-deleted"
            var delete_reversed = false;
            for (var j=0; j<this.selectedDatabase.tables.length; j++) {
                if (table == this.selectedDatabase.tables[j].name) {
                        if (this.selectedDatabase.tables[j].added == false && this.selectedDatabase.tables[j].deleted == true) {    
                        this.selectedDatabase.tables[j].deleted = false;
                        delete_reversed = true;
                        break;
                    }
                }
            }
            if (delete_reversed == true){
                this.selectedDatabase.tables.pop();
            }
            else {
                this.selectedDatabase.tables[index].name = table;
                this.selectedDatabase.tables[index].id = 0;
                this.selectedDatabase.tables[index].partner_integration_id = this.selectedDatabase.id;
                this.selectedDatabase.tables[index].editing = false;
            }
            this.available_tables[idx].used = true;
        },
        addTable() {
            if (this.has_tables) {
                if (!this.table_selected) {
                    return;
                }
            }
            var table_settings = this.buildTableSettings();

            var schedule = {
                id : 0,
                name : "Manually",
                added :true,
                changed : false,
                schedule_type_id : 0,
                properties : []
            };

            if (this.table_schedule_type === "one") {
                schedule.schedule_type_id = this.schedule_type_id;
                schedule.name = this.getScheduleTypeName(this.schedule_type_id);
                schedule.properties = JSON.parse(JSON.stringify(this.properties));
            }
            
            app.selectedDatabase.tables.push({
                "name": "",
                "last_started": "",
                "last_finished": "",
                "is_active": true,
                "added": true,
                "editing": true,
                "changed": false,
                "deleted": false,
                "settings": table_settings,
                "schedule" : schedule
            });
            this.selected_tbl = {
                table_name: '',
                used: false
            };
            this.table_selected = false;
        },
        buildTableSettings() {
            var table_settings = {};

            for(table_setting in this.selectedDatabase.table_settings)
            {
                var value = null;
                if(this.selectedDatabase.table_settings[table_setting].default_value != null || this.selectedDatabase.table_settings[table_setting].default_value != undefined) {
                    value = this.selectedDatabase.table_settings[table_setting].default_value;
                }

                table_settings[this.selectedDatabase.table_settings[table_setting].name] = {
                    added: true,
                    changed: false,
                    integration_setting_id: this.selectedDatabase.table_settings[table_setting].integration_setting_id,
                    value: value,
                    name: this.selectedDatabase.table_settings[table_setting].name,
                    friendly_name: this.selectedDatabase.table_settings[table_setting].friendly_name,
                    data_type: this.selectedDatabase.table_settings[table_setting].data_type,
                    default_value: this.selectedDatabase.table_settings[table_setting].default_value,
                    description: this.selectedDatabase.table_settings[table_setting].description,
                    options: this.selectedDatabase.table_settings[table_setting].options,
                    visible_if: this.selectedDatabase.table_settings[table_setting].visible_if,
                    required_if: this.selectedDatabase.table_settings[table_setting].required_if,
                    is_required: this.selectedDatabase.table_settings[table_setting].is_required,
                    is_private: this.selectedDatabase.table_settings[table_setting].is_private,
                    is_secure: this.selectedDatabase.table_settings[table_setting].is_secure
                };
            }

            return table_settings;
        },
        removeThisTable(table, index) {
            if (confirm("Are you sure you want to delete this table?")) {
                // If this table has just been added, delete it
                if (this.selectedDatabase.tables[index].added == true) {
                    this.selectedDatabase.tables.splice(index, 1);
                    this.table_selected = true;
                } else {
                    fetch(`/internal-api/v1/data-lakes/${app.selectedDatabase.id}/tables/public/${table.orig_name}/check-dependencies`)
                        .then(FetchHelper.handleJsonResponse)
                        .then(json => {
                            if(json.data == "warning") {
                                if(!confirm(json.message)) {
                                    return;
                                }
                            }

                            // Get the available_tables index for this item
                            for (var j=0; j<this.available_tables.length; j++) {
                                if (table.name == this.available_tables[j].table_name) {
                                    // Set used = false to this connector table
                                    this.available_tables[j].used = false;
                                    break;
                                }
                            }

                            // "Soft-delete" this table from selectedDatabase
                            this.selectedDatabase.tables[index].deleted = true;
                        })
                        .catch((error) => {
                            ResponseHelper.handleErrorMessage(error, "Failed to delete table");
                        });
                }
            }
        },
        deleteTable(table, index) {
            if (confirm("Are you sure you want to delete this table?")) {
                if (table.added) {
                    app.selectedDatabase.tables.splice(index, 1);
                } else {
                    fetch(`/internal-api/v1/data-lakes/${app.selectedDatabase.id}/tables/public/${table.orig_name}/check-dependencies`)
                        .then(FetchHelper.handleJsonResponse)
                        .then(json => {
                            if(json.data == "warning") {
                                if(! confirm(json.message)) {
                                    return;
                                }
                            }
                            
                            table.deleted = true;
                        })
                        .catch((error) => {
                            ResponseHelper.handleErrorMessage(error, "Failed to delete table");
                        });
                }
            }
        },
        tableChanged(table, index = 0) {
            if(index != 0) {
                var table_settings = this.buildTableSettings();
                this.selectedDatabase.tables[index].settings = table_settings;
            }

            table.changed = true;
            this.$forceUpdate();
        },
        checkSchedule() {
            
            if(this.table_schedule_type == "one") {
                if (this.schedule_type_id == 0) {
                    alert ("Please use the Schedule tab to choose a synchronization frequency");
                    return false;
                } 
                // schedule_type_id is not 0 - it is 1, 2, 3, or 4 (right now)
                if (this.properties.length > 0) {
                    for (var i=0; i<this.properties.length; i++) {
                        if (this.properties[i].value === "" || this.properties[i].value === "none" || this.properties[i].value === undefined) {
                            alert ("Please enter " + this.properties[i].name+' in schedule.');
                            return false;
                        }
                    }
                }
            }
            else {
                for(var i=0; i <  app.selectedDatabase.tables.length; i++) {
                    if (app.selectedDatabase.tables[i].schedule.schedule_type_id == 0) {
                        alert ("Please use the Schedule tab to choose a synchronization frequency for table " + app.selectedDatabase.tables[i].name  + '.');
                        return;
                    } 
                    for(var j=0; j < app.selectedDatabase.tables[i].schedule.properties.length; j++) {
                        if (app.selectedDatabase.tables[i].schedule.properties[j].value === "" || app.selectedDatabase.tables[i].schedule.properties[j].value === null || app.selectedDatabase.tables[i].schedule.properties[j].value === undefined || app.selectedDatabase.tables[i].schedule.properties[j].value === 'none') {
                            alert ("Please enter " + app.selectedDatabase.tables[i].schedule.properties[j].name + " for table " + app.selectedDatabase.tables[i].name+' in schedule.');
                            return false;
                        }
                    }
                }
            }
            return true;
        },
        /**  End of Methods for Tables Tab  **/
        saveChanges() {
            var save_settings = {
                notificants: "",
                retry_syncs: false,
                alert_threshold: 75,
                settings: []
            };
            save_settings.retry_syncs = app.selectedDatabase.retry_syncs;
            save_settings.notificants = app.selectedDatabase.notificants;
            save_settings.alert_threshold = app.selectedDatabase.server.alert_threshold;
            for (var i = 0; i < app.selectedDatabase.settings.length; i++) {
                if (app.selectedDatabase.settings[i].changed) {
                    save_settings.settings.push({
                        "id": app.selectedDatabase.settings[i].id,
                        "integration_setting_id": app.selectedDatabase.settings[i].integration_setting_id,
                        "value": app.selectedDatabase.settings[i].value
                    });
                }
            }

            if(!this.settingsReady()) {
                notify.danger("You must fill in all required settings.");
                return;
            }

            if(app.selectedDatabase.use_tables === true) {
                if(!this.tableSettingsReady()) {
                    notify.danger("You must fill in all required settings.");
                    return;
                }

                for (var i=app.selectedDatabase.tables.length-1; i>=0; i--) {
                    if (app.selectedDatabase.tables[i].name == "") {
                        app.selectedDatabase.tables.splice(i, 1);
                    }
                }
            }
            if(app.selectedDatabase.integration_id != 0){
                if(!this.checkSchedule()) {
                    return;
                }
            }

            const exception_message = "Unable to save settings";

            this.$root.loading(true);
            fetch (`${baseUrl}/internal-api/v1/data-lakes/${app.selectedDatabase.id}/settings/test`, {
                method: "POST",
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(app.selectedDatabase.settings)
            })
            .then(FetchHelper.handleJsonResponse)
            .then(json => {
                fetch(baseUrl + `/internal-api/v1/data-lakes/${app.selectedDatabase.id}/settings`, {
                    method: "PUT",
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(save_settings)
                })
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    if (this.$root.selectedDatabase.use_tables) {
                        this.saveTables();
                    } else {
                        this.saveSchedule();
                    }
                })
                .catch((error) => {
                    this.$root.loading(false);
                    ResponseHelper.handleErrorMessage(error, exception_message);
                });
            })
            .catch((error) => {
                app.loading(false);
                ResponseHelper.handleErrorMessage(error, exception_message);
            });
        },
        saveSchedule() {
            const exception_message = "Unable to save schedule";
            let options = FetchHelper.buildJsonRequest({
                id: this.schedule_id,
                schedule_type_id: this.schedule_type_id,
                name: this.getScheduleTypeName(this.schedule_type_id),
                previous_schedule_type_id: this.previous_schedule_type_id,
                properties: this.properties
            }, 'put')
            fetch(baseUrl + `/internal-api/v1/data-lakes/${this.selectedDatabase.id}/schedule`, options)
            .then(FetchHelper.handleJsonResponse)
            .then(json => {
                if(app.selectedDatabase.isReadOnlyOrig != app.selectedDatabase.isReadOnly) {
                    if (app.selectedDatabase.isReadOnly) {
                        this.sql_user = 'add';
                    }
                    else {
                        this.sql_user = 'delete';
                    }
                    
                }
                else {
                    if (this.sql_user != 'update') {
                        this.sql_user = '';
                    }
                }
                if (this.sql_user != '') {
                    this.toggleReadOnlyUser(this.sql_user);
                }

                this.$root.loading(false);
                $('.dmiux_popup__close_popup').trigger('click');
            })
            .catch((error) => {
                app.loading(false);
                ResponseHelper.handleErrorMessage(error, exception_message);
            });
        
        },
        saveTables() {
            const exception_message = "Unable to save tables";
            fetch(baseUrl + `/internal-api/v1/data-lakes/${app.selectedDatabase.id}/saveTables`, {
                method: "PUT",
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(app.selectedDatabase.tables)
            })
            .then(FetchHelper.handleJsonResponse)
            .then(json => {
                if(app.selectedDatabase.isReadOnlyOrig != app.selectedDatabase.isReadOnly) {
                    if (app.selectedDatabase.isReadOnly) {
                        this.sql_user = 'add';
                    }
                    else {
                        this.sql_user = 'delete';
                    }
                }

                if (this.sql_user != '') {
                    this.toggleReadOnlyUser(this.sql_user);
                }

                this.$root.getDatabases();
                this.$root.loading(false);
                $('.dmiux_popup__close_popup').trigger('click');
            })
            .catch((error) => {
                app.loading(false);
                ResponseHelper.handleErrorMessage(error, exception_message);
            });
        },
        changeAllTableSchedules() {
            if (this.selectedDatabase.use_tables === true) {
                for(var i=0; i < app.selectedDatabase.tables.length; i++) {
                    if (this.schedule_type_id != app.selectedDatabase.tables[i].schedule.previous_schedule_type_id) {
                        app.selectedDatabase.tables[i].schedule.schedule_type_id = this.schedule_type_id;
                        app.selectedDatabase.tables[i].schedule.properties = JSON.parse(JSON.stringify(this.properties));
                    }
                    else {
                        app.selectedDatabase.tables[i].schedule.schedule_type_id = this.schedule_type_id;
                        app.selectedDatabase.tables[i].schedule.properties = app.selectedDatabase.tables[i].schedule.previous_properties;
                    }
                    if (app.selectedDatabase.tables[i].schedule.id == 0) {
                        app.selectedDatabase.tables[i].schedule.added = true;
                    }
                    else {
                        app.selectedDatabase.tables[i].schedule.changed = true;
                    }
                }
            }
        },
        changeTableScheduleProperty(schedule) {
            if (schedule.id == 0) {
                schedule.added = true;
            }
            else {
                schedule.changed = true;
            }
        },
        changeAllTableScheduleProperties() {
            if (this.selectedDatabase.use_tables === true) {
                for(var i=0; i < app.selectedDatabase.tables.length; i++) {
                    for(var j=0; j < app.selectedDatabase.tables[i].schedule.properties.length; j++) {
                        for(var k=0; k < this.properties.length; k++) {
                            if(app.selectedDatabase.tables[i].schedule.properties[j].id == this.properties[k].id &&
                                app.selectedDatabase.tables[i].schedule.properties[j].value != this.properties[k].value) {
                                if (app.selectedDatabase.tables[i].schedule.id == 0) {
                                    app.selectedDatabase.tables[i].schedule.added = true;
                                }
                                else {
                                    app.selectedDatabase.tables[i].schedule.changed = true;
                                }
                                app.selectedDatabase.tables[i].schedule.properties[j].value = this.properties[k].value
                            }
                        }
                    }
                }
            }
        },
        /**  Run immediately when user changes drop-down for Schedule Frequency  **/
        changeFrequency() {
            this.getIntegrationScheduleTypeProperties();
        },
        /**  Run immediately when user changes drop-down for Schedule Frequency  **/
        changeTableFrequency(schedule) {
            if (schedule.id == 0) {
                schedule.added = true;
                this.getIntegrationTableScheduleTypeProperties(schedule);
            }
            else {
                schedule.changed = true;
                this.getPartnerIntegrationTableScheduleTypeProperties(schedule);
            }
        },
        changeTableFrequencyPropertyValue(schedule) {
            schedule.changed = true;
        },
        /**  Read in Current Schedule Values from the tables  **/
        getIntegrationScheduleTypes() {
            this.schedule_type_id = 0;
            this.previous_schedule_type_id = 0;
            this.schedule = [];
            this.types = [];
            this.properties = [];
            fetch(baseUrl + "/internal-api/v1/data-lakes/schedules/types")
                .then(response => response.json())
                .then(json => {
                    if (!app.checkForError(json)) {
                        app.ready.schedule_types = true;
                        return;
                    }
                    this.types = json.data;
                    if (this.selectedDatabase.use_tables === true) {
                        app.ready.partner_integration_schedule_types = true;
                        var previous_schedule_type_id = 0;
                        var previous_properties = [];
                        for (var i=0; i < this.selectedDatabase.tables.length; i++) {
                            var current_properties = [];
                            if (this.selectedDatabase.tables[i].schedule == undefined) {
                                this.selectedDatabase.tables[i].schedule = {};
                            }
                            if (this.selectedDatabase.tables[i].schedule.id == undefined) {
                                this.selectedDatabase.tables[i].schedule.id = 0;
                            }
                            if (this.selectedDatabase.tables[i].schedule.properties == undefined) {
                                this.selectedDatabase.tables[i].schedule.properties = [];
                            }
                            if (this.selectedDatabase.tables[i].schedule.schedule_type_id == undefined) {
                                this.selectedDatabase.tables[i].schedule.schedule_type_id = 0;
                            }

                            for (var j=0; j<this.selectedDatabase.tables[i].schedule.properties.length; j++) {
                                current_properties.push({
                                    "schedule_type_property_id" : this.selectedDatabase.tables[i].schedule.properties[j].schedule_type_property_id,
                                    "value" : this.selectedDatabase.tables[i].schedule.properties[j].value
                                });
                            }
                            if ((this.selectedDatabase.tables[i].schedule.schedule_type_id != previous_schedule_type_id || 
                                 this.scheduleTypePropertiesEqual(current_properties, previous_properties) != true) &&
                                 previous_schedule_type_id != 0 && previous_properties != []) {
                                 this.table_schedule_type = "multi";
                                 app.ready.schedule_types = true;
                                 return;
                            }
                            previous_schedule_type_id = this.selectedDatabase.tables[i].schedule.schedule_type_id;
                            previous_properties = JSON.parse(JSON.stringify(current_properties));
                        }
                        this.table_schedule_type = "one";
                        var new_properties = [];
                        if (Array.isArray(this.selectedDatabase.tables) && this.selectedDatabase.tables.length > 0) {
                            this.schedule_type_id = this.selectedDatabase.tables[0].schedule.schedule_type_id;
                            new_properties = JSON.parse(JSON.stringify(this.selectedDatabase.tables[0].schedule.properties));
                        }
                        this.properties = new_properties;
                    }
                    else {
                        this.getPartnerIntegrationScheduleType();
                    }
                    app.ready.schedule_types = true;
                });
        },
        scheduleTypePropertiesEqual(properties1, properties2) {
            var equal = true;
            if (properties1.length != properties2.length) {
                equal = false;
            }
            else {
                for(var i=0; i<properties1.length; i++) {
                    if (properties1[i].schedule_type_property_id != properties2[i].schedule_type_property_id ) {
                        equal = false;
                        break;
                    }
                    if (properties1[i].value != properties2[i].value ) {
                        equal = false;
                        break;
                    }
                }
            }
            return equal;
        },
        getIntegrationScheduleTypeProperties() {
            fetch(baseUrl + `/internal-api/v1/data-lakes/schedules/${this.schedule_type_id}/properties`)
                .then(response => response.json())
                .then(json => {
                    if (!app.checkForError(json)) {
                        return;
                    }
                    this.properties = json.data;
                    this.properties = this.setDefaultValues(this.properties);
                    this.changeAllTableSchedules();
                });
        },
        getPartnerIntegrationScheduleType() {
            this.schedule = null;
            this.properties = [];
            this.schedule_id = 0;
            this.schedule_type_id = 0;
            this.previous_schedule_type_id = 0;
            fetch(baseUrl + `/internal-api/v1/data-lakes/${app.selectedDatabase.id}/schedule`)
                .then(data => {
                    this.$root.ready.partner_integration_schedule_types = true;
                    return data;
                })
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    if (!this.$root.checkForError(json)) {
                        return;
                    }
                    if (json.data) {
                        this.schedule_id = json.data.id;
                        this.schedule_type_id = json.data.schedule_type_id;
                        this.previous_schedule_type_id = json.data.schedule_type_id;
                        this.getPartnerIntegrationScheduleTypeProperties();
                    }
                    this.$root.ready.partner_integration_schedule_types = true;
                })
                .catch((error) => {
                    ResponseHelper.handleErrorMessage(error, "The database's schedule failed to load");
                });
        },
        setDefaultValues:function(properties){
            if (properties.length > 0) {
                for (var i=0; i<properties.length; i++) {
                    if (properties[i].value === ""  || properties[i].value === undefined || properties[i].value === null) {
                        properties[i].value = 'none';
                    }
                }
            }   
            return properties;
        },
        getPartnerIntegrationScheduleTypeProperties() {
            fetch(`/internal-api/v1/data-lakes/${this.selectedDatabase.id}/schedule/${this.schedule_id}/values`)
                .then(response => response.json())
                .then(json => {
                    if (!app.checkForError(json)) {
                        return;
                    }
                    this.properties = json.data;
                    this.properties = this.setDefaultValues(this.properties);
                    this.changeAllTableSchedules();
                });
        },
        getIntegrationTableScheduleTypeProperties(schedule) {
            fetch(baseUrl + `/internal-api/v1/data-lakes/schedules/${this.schedule_type_id}/properties`)
                .then(response => response.json())
                .then(json => {
                    if (!app.checkForError(json)) {
                        return;
                    }
                    schedule.properties = json.data;
                    schedule.properties = this.setDefaultValues(schedule.properties);
                });
        },
        getPartnerIntegrationTableScheduleTypeProperties(schedule) {
            fetch(baseUrl + `/internal-api/v1/data-lakes/schedules/${schedule.schedule_type_id}/properties?schedule_id=${schedule.id}`)
                .then(response => response.json())
                .then(json => {
                    if (!app.checkForError(json)) {
                        return;
                    }
                    schedule.properties = json.data;
                    schedule.properties = this.setDefaultValues(schedule.properties);
                });
        },
        deleteIntegration() {
            var check = prompt("If you delete this database, you will not be able to get it back. Type 'DELETE' to continue.")

            if(check != null && check.toLowerCase() == "delete")
            {
                app.loading(true);
                $('.dmiux_popup__close_popup').trigger('click');
                fetch(baseUrl + "/internal-api/v1/data-lakes/" + app.selectedDatabase.id, {method: 'delete'})
                    .then(response => response.json())
                    .then(json => {
                        app.getDatabases();
                        app.loading(false);
                        if (!app.checkForError(json)) {
                            return;
                        }
                    });
            }
        },
        convertIntegrationToBasic() {
            var resp = prompt("Converting a database cannot be done. If you wish to proceed, confirm by typing 'CONVERT'.");

            if (resp == null || resp.toLowerCase() != 'convert') {
                return;
            }

            let options = FetchHelper.buildJsonRequest({ });

            app.loading(true);
            
            fetch(baseUrl + `/internal-api/v1/data-lakes/${app.selectedDatabase.id}/convert-to-basic`, options)
                .then(data => {
                    app.loading(false);
                    return data;
                })
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    $('.dmiux_popup__close_popup').trigger('click');
                    notify.send(json.message, 'success');
                    app.getDatabases();
                })
                .catch((error) => {
                    ResponseHelper.handleErrorMessage(error, "The database could not be converted to a basic database.");
                });
        },
        manageSqlUser() {
            if(!this.selectedDatabase.isReadOnly) {
                var password = this.generatePassword(db_password_length);
                this.selectedDatabase.user.password = password; 
                fetch(baseUrl + `/internal-api/v1/data-lakes/${app.selectedDatabase.id}/sql-user/create`)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        if (!app.checkForError(json)) {
                            return;
                        }
                        this.selectedDatabase.user.username = json.data.username;
                        this.$set(this.selectedDatabase, 'isReadOnly', true);
                        this.$forceUpdate();
                    })
                    .catch((error) => {
                        ResponseHelper.handleErrorMessage(error, "Could not generate a username");
                    });
            }
            else {
                this.$set(this.selectedDatabase, 'isReadOnly', false);
                this.$forceUpdate();
            }
        },
        updatePassword() {
            var password = this.generatePassword(db_password_length);
            this.selectedDatabase.user.password = password;
            this.sql_user = 'update';
            this.$forceUpdate();
        },
        generatePassword(length) {
            var password = "";
            for (var i = 0, n = db_password_charset.length; i < length; ++i) {
                password += db_password_charset.charAt(Math.floor(Math.random() * n));
            }
            return password;
        },
        toggleReadOnlyUser(action) {

            if (action == 'delete') {
                action = 'DELETE';
            } else if (action === 'update') {
                action = 'PUT';
            } else {
                action = 'POST';
            }
            
            fetch(baseUrl + `/internal-api/v1/data-lakes/${app.selectedDatabase.id}/sql-user`, {
                method: action,
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    "database": app.selectedDatabase.database,
                    "id": this.selectedDatabase.user.id,
                    "username": this.selectedDatabase.user.username,
                    "password": this.selectedDatabase.user.password
                })
            })
            .then(FetchHelper.handleJsonResponse)
            .catch((error) => {
                if(error.json == null)
                    ResponseHelper.handleErrorMessage(error, `Failed to ${action} readonly user`);
                else 
                    ResponseHelper.handleErrorMessage(error, error.json.message);
            });
        },
        makeOAuthCall(url) {
            if(! confirm("Changes made outside of this tab will be discarded. Do you want to continue?")) {
                return;
            }

            var urls = [];
            for(var index = 0; index < app.selectedDatabase.settings.length; index++) 
            {
                var setting = app.selectedDatabase.settings[index];

                if(setting.is_secure === true) {
                    urls.push(`/internal-api/v1/data-lakes/${this.selectedDatabase.id}/settings/${setting.id}`)
                }
            }

            let requests = urls.map(url => fetch(url));

            // wait for all the fetch requests and json responses to finish processing 
            // before showing values
            Promise.all(requests)
                .then(responses => {
                    return Promise.all(responses.map(response => {
                        return response.json();
                    }));
                })
                .then(json_array => {
                    json_array.forEach( json => {
                        for(var index = 0; index < app.selectedDatabase.settings.length; index++) 
                        {
                            var setting = app.selectedDatabase.settings[index];
                            if(setting.id == json.id) {  
                                app.selectedDatabase.settings[index].is_secure = false;
                                app.selectedDatabase.settings[index].value = json.data.value;
                            }
                        }
                    });
                    
                    this.sendOAuth(url);
                });
        },
        sendOAuth(url) {
            var vue_state = {
                "selectedDatabase": app.selectedDatabase,
                "callbacks": app.callbacks,
                "properties": this.properties,
                "schedule_type_id": this.schedule_type_id,
                "previous_schedule_type_id": this.previous_schedule_type_id,
                "table_schedule_type": this.table_schedule_type,
                "cbType": this.cbType,
                "callbackToUpsert": this.callbackToUpsert
            };
            var call = { "integration_id": this.selectedDatabase.id, "settings": {}, "url": url, "vue_state": vue_state, "from": "dataLake" }
            for(var index = 0; index < this.selectedDatabase.settings.length; index++) 
            {
                var setting = this.selectedDatabase.settings[index];

                if(setting.is_private !== true && setting.is_required === true && (setting.value == "" || setting.value == null)) {
                    notify.danger("Please enter the required information or show all information.");
                    return;
                }
                else {
                    call["settings"][setting.name] = setting.value;
                }
            }

            var options = FetchHelper.buildJsonRequest(call);
            fetch("/OAuth/sendOAuth", options)
                .then(response => response.json())
                .then(json => {
                    if (!app.checkForError(json)) {
                        return;
                    }
                    document.cookie = "bytespree_state=" + json.data.state + ";path=/;domain=bytespree.com";
                    window.location = json.data.url;
                });
        },
        checkOptions(tab) {
            var urls = [];
            if (tab == "settings") {
                for(var i = 0; i < app.selectedDatabase.settings.length; i++) {
                    var setting = app.selectedDatabase.settings[i];
                    if(setting.options != null && typeof(setting.options) != 'array' && typeof(setting.options) != 'object' && (setting.data_type == 'select' || setting.data_type == 'multiselect')) {
                        try {
                            app.selectedDatabase.settings[i].options = JSON.parse(setting.options);
                        }
                        catch(e) {
                            app.loading(true);

                            var options = FetchHelper.buildJsonRequest({
                                control_id: app.selectedDatabase.id,
                                method_name: setting.options,
                                index: [i]
                            });

                            urls.push({ 'url': `${baseUrl}/internal-api/v1/connectors/${app.selectedDatabase.integration_id}/metadata`, 'options': options });
                        }
                    }
                }

                let requests = urls.flatMap(({ url, options }) => fetch(url, options));
                if(requests.length > 0) {
                    this.makeOptionCalls(requests);
                }
            }
            else if(tab == "table-settings") {
                for(var i = 0; i < app.selectedDatabase.tables.length; i++) {
                    var table = app.selectedDatabase.tables[i];
                    for(const [i2, setting] of Object.entries(table.settings)) {
                        if(setting.options != null && typeof(setting.options) != 'array' && typeof(setting.options) != 'object' && (setting.data_type == 'select' || setting.data_type == 'multiselect')) {
                            if(setting.options) {
                                try {
                                    app.selectedDatabase.tables[i].settings[i2].options = JSON.parse(setting.options);
                                }
                                catch(e) {
                                    app.loading(true);

                                    var options = FetchHelper.buildJsonRequest({
                                        control_id: app.selectedDatabase.id,
                                        method_name: setting.options,
                                        table: table.name,
                                        index: [i, i2]
                                    });

                                    urls.push({ 'url': `${baseUrl}/internal-api/v1/connectors/${app.selectedDatabase.integration_id}/metadata`, 'options': options });
                                }
                            }
                        }
                    }
                }

                let requests = urls.flatMap(({ url, options }) => fetch(url, options));
                if(requests.length > 0) {
                    this.makeOptionCalls(requests);
                }
            }
        },
        getVariables(null_invisible, table_name = "") {
            var variables = {};
            app.selectedDatabase.settings.forEach((setting) => {
                if (null_invisible) {
                    if (this.isSettingVisible(setting.visible_if, table_name)) {
                        variables[setting.name] = setting.value;
                    }
                    else {
                        variables[setting.name] = null;
                    }
                }
                else {
                    variables[setting.name] = setting.value;
                }
            });
            if (table_name != "") {
                app.selectedDatabase.tables.forEach((table) => {
                    if (table.name == table_name) {
                        if (typeof table.settings == 'object') {
                            for (var property in table.settings) {
                                variables[property] = table.settings[property].value
                            }
                        }
                    }
                });
            }
            return variables;
        },
        isSettingVisible(visible_if, table_name = "") {
            if (visible_if == "" || visible_if == null) return true;
            var variables = this.getVariables(false, table_name);
            var result = ConditionParser.evaluate(visible_if, variables, true);
            return result;
        },
        isSettingRequired(setting, table_name = '') {
            required_if = setting.required_if;
            if (setting.is_required === true) return true;
            if (required_if == "" || required_if == null) return false;
            var variables = this.getVariables(true, table_name);
            var result = ConditionParser.evaluate(required_if, variables, false);
            return result;
        },
        settingsReady() {
            for (var i = 0; i < app.selectedDatabase.settings.length; i++) {
                if (app.selectedDatabase.settings[i].is_secure !== true && this.isSettingRequired(app.selectedDatabase.settings[i]) && this.isSettingVisible(app.selectedDatabase.settings[i].visible_if)) {
                    if (app.selectedDatabase.settings[i].value == null || (app.selectedDatabase.settings[i].value == "" && app.selectedDatabase.settings[i].data_type != "boolean") || (app.selectedDatabase.settings[i].data_type == "boolean" && app.selectedDatabase.settings[i].value == false)) {
                        return false;
                    }
                }
            }
            return true;
        },
        tableSettingsReady() {
            for (var i = 0; i < app.selectedDatabase.tables.length; i++) {
                var table = app.selectedDatabase.tables[i];
                for(i2 in table.settings) {
                    var setting = table.settings[i2];
                    if (setting.is_secure !== true && this.isSettingRequired(setting, table.name) && this.isSettingVisible(setting.visible_if, table.name)) {
                        if (setting.value == null || (setting.value == "" && setting.data_type != "boolean") || (setting.data_type == "boolean" && setting.value == false)) {
                            return false;
                        }
                    }
                }
            }
            return true;
        },
        makeOptionCalls(requests) {
            app.loading(true);
            Promise.all(requests)
                .then(responses => {
                    return Promise.all(responses.map(response => {
                        return response.json();
                    }));
                })
                .then(json_array => {
                    json_array.forEach( json => {
                        if(json.data.index.length == 1) {
                            if(json.status != 'ok') 
                                app.selectedDatabase.settings[json.data.index[0]].data_type = 'text';
                            else
                                app.selectedDatabase.settings[json.data.index[0]].options = json.data.options;
                        }
                        else {
                            if(json.status != 'ok') 
                                app.selectedDatabase.tables[json.data.index[0]].settings[json.data.index[1]].data_type = 'text';
                            else 
                                app.selectedDatabase.tables[json.data.index[0]].settings[json.data.index[1]].options = json.data.options;
                        }
                    });
                    app.loading(false);
                });
        },
        getScheduleTypeName(schedule_type_id) {
            for(var i=0; i<this.types.length; i++) {
                if (this.types[i].id == schedule_type_id) {
                    return this.types[i].name;
                }
            }
            return '';
        }
    },
    mounted() {
        $(this.$refs.proj_settings_modal).on("hidden.bs.modal", this.clearData);
        notyfHelper.showUrlMessage();
    },
    computed: {
        tableSettingsCount() {
            var count = 0;
            for(var index = 0; index < this.selectedDatabase.tables.length; index++)
            {
                for(setting in this.selectedDatabase.tables[index].settings) {
                    if (this.selectedDatabase.tables[index].settings[setting].is_private !== true) {
                        count++;
                    }
                }
            }
            return count;
        }
    }
});

/** Vue.js Component
 *******************************************************************************
 * create-empty-database
 *******************************************************************************
 * Creates empty database if user don't want to select any connector.
 */
 var createEmptyDatabase = Vue.component('create-empty-database', {
    template: '#create-empty-database-template',
    props:[ 'open', 'servers'],
    data() {
        return {
            database: {
                name: "",
                server_id: "",
                checkbox_value:false
            }
        }
    },
    watch: {
        open() {
            if (default_server_id != null) {
                this.database.server_id = default_server_id;
            }

            if(this.open == true) {
                $(document).on("mousedown", "#dmiux_body", this.closeAddModel);
                $(document).off('keydown', closeModalOnEscape);
                $(document).on("keydown", this.closeAddModel);
            }
        }
    },
    methods: {
        cleanupDatabaseName() {
            var firstChar = this.database.name.charAt(0);
            var regex = new RegExp(/[a-z]/i);
            while(regex.test(firstChar) == false && this.database.name.length > 0) {
                this.database.name = this.database.name.replace(firstChar, '');
                firstChar = this.database.name.charAt(0);
            }
            this.database.name = this.database.name.toLowerCase();
            this.database.name = this.database.name.trim();
            this.database.name = this.database.name.replace(" ", "");
            this.database.name = this.database.name.replace(/\W/g, '');
        },
        clearInputs(){
            app.add_model_open = false;
            this.database.name = '';
            this.database.server_id = '';
            this.database.checkbox_value = false;
        },
        closeAddModel(event) {
            event.stopPropagation();
            if(event.key != undefined) {
                if(event.key != 'Escape') // not escape
                    return;
            }
            else {
                var clicked_element = event.target;
                if (clicked_element.closest(".dmiux_popup__window")) {
                    // You clicked inside the modal
                    if (clicked_element.id != "x-button" && !(clicked_element.classList.contains("dmiux_popup__cancel")))
                        return;
                }
            }
            // You clicked either on the X Button, the Cancel Button , or outside the modal - the modal will close
            this.clearInputs();
            closeModal("#modal-create-empty-database");
        },
        saveEmptyDatabase:function(){
            if(this.database.server_id == "" || this.database.name == "" || this.database.server_id === null){
                notify.send("Database name and server cannot be empty.", 'danger');
                return;
            }

            let options = FetchHelper.buildJsonRequest(this.database, 'post');

            this.$root.loading(true);
            fetch(baseUrl + "/internal-api/v1/data-lakes", options)
                .then(response => response.json())
                .then(json => {
                    this.$root.loading(false);
                    if(json.status == "ok"){
                        if(this.database.checkbox_value == false){
                            notify.success('Your database has been created!');
                            $('.dmiux_popup__close_popup').trigger('click');
                            this.$root.getAllPermissions();
                            this.$root.getDatabases();
                            this.clearInputs();
                        } else {
                            window.location.href = `/data-lake/database-manager/${json.data.id}?open_add_modal`;
                        }
                    } else {
                        notify.danger(json.message);
                    }
                });
        }
    }
});

var requestAccess = Vue.component('request-access', {
    template: '#request-access-modal-template',
    props: [ 'open', 'selectedDatabase' ],
    data() {
        return {
            reason: ''
        }
    },
    watch: {
        open() {
            if(this.open == true) {
                this.reason = '';
                $(document).on("mousedown", "#dmiux_body", this.modalClose);
                $(document).on("keydown", this.modalClose);
                openModal('#modal-request_access');
                this.$nextTick(() => {
                    this.$refs.reason.focus();
                    this.$root.destroyTooltips();
                    this.$root.restoreTooltips();
                });
            }
            else {
                $(document).off("mousedown", "#dmiux_body", this.modalClose);
                $(document).off("keydown", this.modalClose);
                closeModal('#modal-request_access');
            }
        }
    },
    methods: {
        sendRequest() {
            if (this.reason.trim() == '') {
                if (!confirm("Providing a reason is strongly recommended. Are you sure you want to request access without a reason?")) {
                    return;
                }
            }

            var options = FetchHelper.buildJsonRequest({
                "reason": this.reason
            });

            this.$root.loading(true);
            fetch(`${baseUrl}/internal-api/v1/data-lakes/${this.selectedDatabase.id}/request-access`, options)
                .then(response => {
                    this.$root.loading(false);
                    return response;
                })
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    if (json.status == 'ok') {
                        notify.success("Access request sent");
                        this.$root.modals.request_access = false;
                    }
                })
                .catch((error) => {
                    ResponseHelper.handleErrorMessage(error, 'An error occurred while attempting to get integration logs.');
                });
        },
        modalClose(event) {
            event.stopPropagation();
            if(event.key != undefined) {
                if(event.key != 'Escape') // not escape
                    return;
            }
            else {
                var clicked_element = event.target;
                if (clicked_element.closest(".dmiux_popup__window")) {
                    // You clicked inside the modal
                    if (!clicked_element.classList.contains("dmiux_popup__close") && !(clicked_element.classList.contains("dmiux_popup__cancel")))
                        return;
                }
            }

            this.$root.modals.request_access = false;
        }
    }
});

/** Vue.js Component
 *******************************************************************************
 * toolbar
 *******************************************************************************
 * Renders the toolbar in which the application controls and breadcrumbs are
 * displayed.
 */
var toolbar = Vue.component('toolbar', {
    template: '#component-toolbar',
    props: [ 'breadcrumbs', 'buttons', 'tags', 'record_counts', 'current_user', 'servers' ],
    data() {
        return {
            filter: null
        }
    },
    watch: {
        filter() {
            app.filter_tag_id = this.filter;
        }
    },
    methods: {
        compare() {
            window.location.href = baseUrl + "/data-lake/compare"
        },
        add_integration() {
            window.location.href = baseUrl + "/data-lake/create"
        },
        create_empty_database(){
            app.add_model_open = true;
            openModal("#modal-create-empty-database");
        }
    }
});

/** Vue.js Application
 *******************************************************************************
 * app
 *******************************************************************************
 * Controls the UX for the data warehouse application.
 */
var app = new Vue({
    el: '#app',
    name: 'Warehouse',
    data: {
        toolbar: {
            "breadcrumbs": [],
            "buttons": []
        },
        integrations: [],
        selected_integration: {
            "name": '',
            "database": ''
        },
        currentUser: {
            "is_admin": false,
            "name": ""
        },
        modals: {
            "integration_logs": false,
            "request_access": false
        },
        databases: {
            user_databases: [],
            other_databases: []
        },
        selectedCustomer: null,
        newCallback: false,
        metadata_result: "",
        settings : [],
        permissions: [],
        user_permissions: {
            name: []
        },
        flashError : flashError,
        is_selected: false,
        selectedDatabase : {
            id: 0,
            use_tables: "",
            use_hooks: "",
            settings : [],
            table_settings: [],
            user: {},
            tables: [],
            server: {
                alert_threshold: ''
            }
        },
        requestedDatabase: {},
        database_jobs: [],
        tags: [],
        callbacks: [],
        selected_tab: "database-integration",
        filter_tag_id: null,
        servers: [],
        connector_just_added: false,
        database_just_added: false,
        schedule_types: [],
        datetime: '',
        system_timezone: '',
        first_name: "",
        teamconnectors: [],
        baseUrl: baseUrl,
        databaseDependencies: {
            projects: [],
            foreign_projects: [],
            warehouse_foreign_databases: []
        },
        add_model_open:false,
        ready: {
            databases: false,
            database: false,
            schedule_types: false,
            partner_integration_schedule_types: false,
            connector_settings: false
        },
        ready_array: [],
        show_database_options: false,
        pending_jobs: [],
        first_load: true
    },
    components: {
        'cardDatabases': cardDatabases,
        'toolbar': toolbar,
        'projectSettings': projectSettings,
        'createEmptyDatabase':createEmptyDatabase,
        'requestAccess': requestAccess
    },
    methods: {
    	/** Vue.js Method
 		 *******************************************************************************
 		 * checkPerms
 		 * Params:
 		 * * perm_name		    string 		A particular permission identifier.
 		 * * product_child_id	integer 	A database identifier.
 		 *******************************************************************************
 		 * Checks to see if a particular function of the UX should be accessible by the
 		 * current user.
 		 */
        checkPerms(perm_name, product_child_id) {
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
        movePage(url) {
            window.location.href = baseUrl + url
        },
        checkUserPerms(perm_name) {
            var result = false;
            if (this.currentUser.is_admin === true) {
                result = true;
            }
            else {
                for(var i=0; i < this.user_permissions.name.length; i++) {
                    if (this.user_permissions.name[i] == perm_name) {
                        result = true;
                        break;
                    }
                }
            }
            return result;
        },

        loadingUntilReady(ready_array) {
            if (this.ready_array.length > 0){
                console.error("loadingUntilReady: Can only watch one set of properties at a time");
            }
            for (var i=0; i < ready_array.length; i++) {
                var name = ready_array[i];
                if (this.ready[name] == undefined) {
                    console.error(`loadingUntilReady: Cannot show loader because ${name} is not a valid ready property`);
                    return;
                }
                else {
                    this.ready[name] = false;
                }
            }
            this.ready_array = ready_array;
            this.loading(true);
        },

    	/** Vue.js Method
 		 *******************************************************************************
 		 * loading
 		 * Params:
 		 * * status			boolean		Whether to start or end loading. 		
 		 *******************************************************************************
 		 * Displays an overlay and an animated progress bar to indicate that the UX is
 		 * currently unavailable.
 		 */
        loading(status) {
            if (status === true) {
                $(".loader").show();
            }
            else {
                if (this.ready_array.length == 0)
                    $(".loader").hide();
            }
        },

        /** Vue.js Method
        *******************************************************************************
        * getCurrentUser
        *******************************************************************************
        * Retrieves information about the current user. 
        */
        getCurrentUser() {         
            fetch(baseUrl + "/internal-api/v1/me")
                .then(response => response.json())
                .then(json => {
                    if (!app.checkForError(json)) {
                        return;
                    }
                    this.currentUser = json.data;
                    this.first_name = this.currentUser.first_name;
                });         
        },

        /** Vue.js Method
         *******************************************************************************
         * getServers
         *******************************************************************************
         * Checks to see if team has one or more servers, and loads them into app.servers.
         */
         getServers() {
            this.loading(true);
            fetch(baseUrl + "/internal-api/v1/servers")
                .then(response => response.json())
                .then(json => {
                    
                    this.servers = json.data;
                    this.getIntegrations();  
                });
        },

        /** Vue.js Method
         *******************************************************************************
         * getIntegrations
         *******************************************************************************
         * Retrieves all of the available integrations that could be used to set up a
         * new database. 
         */
        getIntegrations() {
            if (this.shouldFetchFromAPI("integrations_last_run_time", "integrations")) {
                this.loading(true);
                fetch(baseUrl + "/internal-api/v1/connectors")
                    .then(response => response.json())
                    .then(json => {
                        if (!app.checkForError(json)) {
                            return;
                        }
                        this.integrations = json;
                        // Convert to JSON string so we can store in local storage, which only accepts strings
                        localStorage.setItem("integrations-" + this.currentUser["name"], JSON.stringify(json));
                        localStorage.setItem("integrations_last_run_time-" + this.currentUser["name"], Date.now().toString());
                    });
            } else {
                // Set Vue component property to be data in local storage, parsed back to JSON
                this.integrations = JSON.parse(localStorage.getItem("integrations-" + this.currentUser["name"]));
            }

            this.getDatabases();
        },

        /** Vue.js Method
         *******************************************************************************
         * shouldFetchFromAPI
         * Params:
         * * last_run_time  string      Timestamp of last time item was ran. 
         * * name			string      Name of the item to be checked. 		 		
         *******************************************************************************
         * Method that returns whether or not a given warehouse function should be run
         * based on the whether there is data in local storage or not. Data is stored in 
         * local storage until 15 minutes (in milliseconds) have elapsed, at which point 
         * the data is fetched again. If local storage has no last run time or data, we
         * automatically run.
         */   
         shouldFetchFromAPI(last_run_time, name) {
            if (localStorage.getItem(last_run_time + "-" + this.currentUser["name"]) == undefined || 
            (localStorage.getItem(name + "-" + this.currentUser["name"]) == undefined)) {
                return true;
            } 
            else if (Date.now() - localStorage.getItem(last_run_time + "-" + this.currentUser["name"]) > 900000) {
                return true;
            } 
            else {
                return false;
            }
        },

        /** Vue.js Method
         *******************************************************************************
         * getDatabases
         *******************************************************************************
         * Retrieves a listing of all databases available to the current user.
         */
         getDatabases() {
            if (! this.ready.databases && ! this.first_load) {
                return;
            }
            this.ready.databases = false;
            var url = baseUrl + "/internal-api/v1/data-lakes";    
            this.destroyTooltips();

            fetch(url)
                .then(response => response.json())
                .then(json => {
                    if (!app.checkForError(json)) {
                        return;
                    }
                    this.databases = json.data;
                    this.getDatabasesFilter();
                    this.ready.databases = true;
                    this.first_load = false;
                })
                .then(() => {
                    this.restoreTooltips();
                    this.loading(false);
                });
        },

        destroyTooltips() {
            $(".tooltip-pretty").each((index, element) => {
                if ($(element).hasClass('tooltipstered')) {
                    $(element).tooltipster('destroy');
                }
            });
        },

        restoreTooltips() {
            $(".tooltip-pretty").tooltipster({
                contentAsHTML: true
            });
        },

        /** Vue.js Method
        *******************************************************************************
        * getBreadcrumbs
        *******************************************************************************
        * Retrieves breadcrumb details for the current UX. Breadcrumbs are initially set 
        * from local storage and subsequently fetched from server.
        */
        getBreadcrumbs() {
            if (localStorage.getItem("breadcrumbs" != undefined)) {
                this.toolbar.breadcrumbs = JSON.parse(localStorage.getItem("breadcrumbs"));
            }

            fetch(baseUrl + "/internal-api/v1/crumbs")
                .then(response => response.json())
                .then(json => {
                    if (!app.checkForError(json)) {
                        return;
                    }
                    this.toolbar.breadcrumbs = json.data;
                    localStorage.setItem("breadcrumbs", JSON.stringify(json.data));
                });
        },

        /** Vue.js Method
 		 *******************************************************************************
 		 * getAllPermissions
 		 *******************************************************************************
 		 * Retrieves role permissions for the current user
 		 */
        getAllPermissions() {
            fetch(baseUrl + '/internal-api/v1/me/permissions?product=datalake')
                .then(response => response.json())
                .then(json => {
                    if (!app.checkForError(json)) {
                        return;
                    }
                    this.permissions = json.data;
                });
        },

        /** Vue.js Method
 		 *******************************************************************************
 		 * getAllUserPermissions
 		 *******************************************************************************
 		 * Retrieves user permissions for the current user
 		 */
        getAllUserPermissions() {
            fetch(baseUrl + '/internal-api/v1/me/permissions')
                .then(response => response.json())
                .then(json => {
                    if (!app.checkForError(json)) {
                        return;
                    }
                    this.user_permissions = json.data;
                });
        },

        /** Vue.js Method
         *******************************************************************************
         * getTags
         *******************************************************************************
         * Retrieves tags for the current user's databases
         */
        getTags() {
            fetch(baseUrl + "/internal-api/v1/tags")
                .then(response => response.json())
                .then(json => {
                    this.tags = json.data;
                });
        },
        /** Vue.js Method
        *******************************************************************************
        * getDatabasesFilter
        * Params:
        * * tag			string		A tag name.
        *******************************************************************************
        * Retrieves a listing of all databases available to the current user for a
        * given tag.
        */
        getDatabasesFilter() {
            var databases = this.databases.user_databases;
            if(this.filter_tag_id == null) {
                for(i = 0; i < databases.length; i++) {
                    databases[i].hidden = false;
                }
                return;
            }

            for(i = 0; i < databases.length; i++) {
                // Hide this database
                databases[i].hidden = true;
                for(j = 0; j < databases[i].tags.length; j++) {
                    if(databases[i].tags[j].tag_id == this.filter_tag_id) {
                        // Show this database
                        databases[i].hidden = false;
                    }
                }
            }
        },

        checkForError(json) {
            if (json.status == "error") {
                this.loading(false);
                notify.send(json.message, "danger");
                return false;
            }
            return true;
        },

        /** Vue.js Method
        *******************************************************************************
        * getScheduleTypes
        *******************************************************************************
        * Retrieves a list of all schedule types for the schedule drop-down.
        */
        getScheduleTypes() {
            fetch(baseUrl + "/internal-api/v1/data-lakes/schedules/types")
                .then(response => response.json())
                .then(json => {
                    if (!app.checkForError(json)) {
                        return;
                    }
                    this.schedule_types = json.data;
                });
        },

        validateForm(requestedTab) {
            if(requestedTab == 'database-settings') {

                if(!this.is_selected) {
                    alert("You must choose a connector.");
                    this.selected_tab = "database-integration";
                    return;
                }
                else {
                    this.selected_tab = "database-settings";
                    $("#integ-settings").addClass('dmiux_active dmiux_query-tabs__item_active visible');
                    $("#integ-settings").siblings().removeClass('dmiux_active dmiux_query-tabs__item_active visible');
                    $("#dmiux_query-tab_integ").removeClass('dmiux_removed');
                    $("#dmiux_query-tab_integ").siblings().addClass('dmiux_removed');
                }
            }
            else if(requestedTab == 'database-schedule') {
                if(!this.is_selected) {
                    alert("You must choose a connector.");
                    this.selected_tab = "database-integration";
                    return;
                }

                if (this.selectedProject.use_tables === true) {
                    if (this.selectedProject.tables.length == 0) {
                        alert("You must add at least one table for this database.");
                    this.selected_tab = "database-settings";
                    return;
                    }
                }

                for (var i=0; i<this.selectedProject.settings.length; i++) {
                    if (this.selectedProject.settings[i].is_required == true) {
                        if (this.selectedProject.settings[i].value == undefined || this.selectedProject.settings[i].value == '') {
                            alert("You must fill in all required settings.");
                            this.selected_tab = "database-settings";
                            return;
                        }
                    }
                }
            }
            this.selected_tab = requestedTab;   
        },
        getTeamConnectors() {
            fetch(`/internal-api/v1/connectors`)
                .then(response => response.json())
                .then(resp => {
                    if(resp.status == "ok") {
                        this.teamconnectors = resp.data;
                    }
                });
        },
        getDatabaseJobs(control_id) {
            fetch(baseUrl + "/internal-api/v1/data-lakes/" + control_id + '/jobs')
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    if (!this.$root.checkForError(json)) {
                        return;
                    }
                    this.database_jobs = json.data;
                    this.updatePendingJobs();
                    openModal("#modal-integration-manager");
                 })
                 .catch((error) => {
                    ResponseHelper.handleErrorMessage(error, 'An error occurred while attempting to get database jobs.');
                })
        },
        hideTeamConnector(id) {
            for (var i=0; i<this.teamconnectors.length; i++) {
                if (this.teamconnectors[i].id == id) {
                    this.teamconnectors[i].visible = false;
                    break;
                }
            }
        },
        setOAuthData(json) {
            paramsHelper.clearOAuth();
            this.selectedDatabase = json.data.selectedDatabase;
            this.callbacks = json.data.callbacks;
            this.$refs.project_settings.properties = json.data.properties;
            this.$refs.project_settings.schedule_type_id = json.data.schedule_type_id;
            this.$refs.project_settings.previous_schedule_type_id = json.data.previous_schedule_type_id;
            this.$refs.project_settings.table_schedule_type = json.data.table_schedule_type;
            this.$refs.project_settings.cbType = json.data.cbType;
            this.$refs.project_settings.callbackToUpsert = json.data.callbackToUpsert;
            openModal("#modal-project-settings");
            $("#tab-connector").click();
        },
        tag_database_total(id) {
                let filtered_databases = this.$root.databases.user_databases.filter(function(database){
                    return database.tags.filter(function(tag) {
                        return tag.tag_id == id;
                        }).length > 0;
                });
                return filtered_databases.length;
        },
        runIntegration(database_id, name = '') {
            this.loading(true);

            let options = FetchHelper.buildJsonRequest({job: name});

            this.database_jobs.filter((job) => {
                if (job.partner_integration_id == database_id && job.name == name) {
                    job.is_running = true;
                    this.pending_jobs.push(job);
                }
            });

            fetch(`/internal-api/v1/data-lakes/${database_id}/jobs/run`, options)
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    notify.send('Synchronization has started.', 'success');
                    this.getDatabases();
                    this.loading(false);
                })
                .catch((error) => {
                    if(error.json != null) { 
                        ResponseHelper.handleErrorMessage(error, error.json.message); 
                    } else {
                        ResponseHelper.handleErrorMessage(error, 'An unknown error occurred when starting the integration.'); 
                    }
                });
        },
        updatePendingJobs() {
            for(var i=0; i < this.database_jobs.length; i++) {
                this.database_jobs[i].is_pending = this.isJobPending(this.database_jobs[i].partner_integration_id, this.database_jobs[i].name);
            }
        },
        clearPendingJobs() {
            this.pending_jobs = [];
            for(var i=0; i < this.database_jobs.length; i++) {
                this.database_jobs[i].is_pending = false;
            }
        },
        isJobPending(control_id, name) {
            for(var i=0; i<this.pending_jobs.length;i++) {
                if (this.pending_jobs[i].partner_integration_id == control_id && this.pending_jobs[i].name == name) {
                    return true;
                }
            }
            return false;
        },
        subscribe() {
            if (typeof(header_icons) == 'undefined') {
                setTimeout(() => {
                    this.subscribe();
                }, 500)
                return;
            }
            header_icons.$on('job-count-updated', (data) => {
                this.clearPendingJobs();
            });
        }
    },
    watch: {
        filter_tag_id() {
            this.getDatabasesFilter();
        },
        ready: {
            deep: true,
            handler() {
                if (this.ready_array.length > 0) {
                    for (var i=0; i < this.ready_array.length; i++) {
                        var name = this.ready_array[i];
                        if (this.ready[name] === false) {
                            return;
                        }
                    }
                    this.ready_array = [];
                    this.loading(false);
                }
            }
        }
    },
    computed: {
        database_count() {
            return this.databases.user_databases.length + this.databases.other_databases.length;
        }
    },
    mounted() {
        this.system_timezone = system_timezone;
        this.getCurrentUser();
        this.getScheduleTypes();
        this.getBreadcrumbs();
        this.getAllPermissions();
        this.getAllUserPermissions();
        this.getTags();
        this.getTeamConnectors();
        this.getServers();
        $('#modal-project-settings').on("click", '.dmiux_popup__close, .dmiux_popup__close_popup', function(e) {
            $('#tab-global-settings').trigger("click");
        });

        this.subscribe();

        url = new URL(window.location.href);
        var bytespree_state = Cookie.get("bytespree_state");
        if (url.searchParams.get('code')) {
            var code = url.searchParams.get('code');
            var guid = url.searchParams.get('guid');
            this.loading(true);
            fetch(baseUrl + "/OAuth/getOAuth/" + btoa(code) + "/" + guid)
                .then(response => response.json())
                .then(json => {
                    this.loading(false);
                    if (this.checkForError(json)) {
                        paramsHelper.clearOAuth();
                        if(json.data.error_message != undefined) {
                            notify.danger(json.data.error_message);
                        } 
                        else {
                            notify.success("You have successfully reauthorized " + json.data.selectedDatabase.integration_name + " to Bytespree and connector settings have been saved.");
                        }
                    }
                });
        }
        else if (url.searchParams.get('error')) {
            var guid = url.searchParams.get('guid');
            this.loading(true);
            fetch(baseUrl + "/OAuth/getOAuth/" + false + "/" + guid)
                .then(response => response.json())
                .then(json => {
                    this.loading(false);
                    notify.danger(url.searchParams.get('error') + ": " + url.searchParams.get('error_description'));
                    if (this.checkForError(json)) {
                        this.setOAuthData(json);
                    }
                });
        }
        else if (url.searchParams.get('show_database_options') != null) {
            this.show_database_options = true;
        }
        else if(bytespree_state != "") {
            this.loading(true);
            fetch(baseUrl + "/OAuth/getOAuth/" + false + "/" + bytespree_state)
                .then(response => response.json())
                .then(json => {
                    this.loading(false);
                    if (this.checkForError(json)) {
                        this.setOAuthData(json);
                    }
                });
        }
    }
});
