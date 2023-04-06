var baseUrl = "";
Vue.component('multiselect', window.VueMultiselect.default);

var toolbar = Vue.component('toolbar', {
    template: '#component-toolbar',
    props: [ 'breadcrumbs', 'buttons', 'customers', 'record_counts' ],
    methods: {
        add_integration() {
            window.location.href = `${baseUrl}/data-lake/create`
        }
    }
});

var connector_settings = Vue.component('connector-settings', {
    template: '#connector-settings-template',
    props: [ 'settings', 'table', 'table_index' ],
    methods: {
        valueChanged: function (index, setting, table) {
            if(setting.data_type == 'number') {
                setting.value = setting.value.replace(/[^0-9.]/g, '');
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

var wizard = Vue.component('database-creation-wizard', {
    template: '#component-wizard',
    props : [ 'integrations', 'servers', 'current_user', 'is_selected', 'selected_tab', 'schedule_types', 'system_timezone'],
    data: function() {
        return {
            run_from: "database",
            schedule_type_id: 0,
            schedule_type_name: "",
            properties: [],
            control_id: "",
            connector_tables: [],
            has_tables: false,
            has_instructions: false,
            has_known_limitations: false,
            agree_with_kl: false,
            integration_details: {
                schedule: {
                    schedule_type_id: 0,
                    name: "",
                    properties: []
                },
                settings: [],
                tables: [],
                table_settings: []
            },
            integration_data_store: {},
            table_schedule_type: "one",
            settings_tab: "integration-settings",
            settings_ready: false,
            sync_max_date:"",
        }
    },
    components: {
        connector_settings
    },
    created() {
        setInterval(() => {
          this.runClock();
        }, 1000)
    },
    mounted() {
        this.restrictFutureDate();
    },
    watch: {
        schedule_type_id() {
            this.integration_details.schedule.schedule_type_id = this.schedule_type_id;
        },
        schedule_type_name() {
            this.integration_details.schedule.name = this.schedule_type_name;
        },
        selected_tab() {
            if(this.selected_tab == "database-settings") {
            }
        },
        settings_tab() {
            if(this.settings_tab == "table-settings") {
                var urls = [];
                for(var i = 0; i < this.integration_details.tables.length; i++) {
                    var table = this.integration_details.tables[i];
                    for(var i2 = 0; i2 < table.settings.length; i2++) {
                        var setting = table.settings[i2];
                        if(setting.options != null && typeof(setting.options) != 'array' && typeof(setting.options) != 'object' && (setting.data_type == 'select' || setting.data_type == 'multiselect')) {
                            if(setting.options) {
                                try {
                                    this.integration_details.tables[i].settings[i2].options = JSON.parse(setting.options);
                                }
                                catch(e) {
                                    app.loading(true);

                                    var options = FetchHelper.buildJsonRequest({
                                        settings: this.integration_details.settings,
                                        method_name: setting.options,
                                        table: table.name,
                                        index: [i, i2]
                                    });

                                    urls.push({ 'url': `${baseUrl}/internal-api/v1/connectors/${this.integration_details.id}/metadata`, 'options': options });
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
        }
    },
    methods: {
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
                                this.integration_details.settings[json.data.index[0]].data_type = 'text';
                            else
                                this.integration_details.settings[json.data.index[0]].options = json.data.options;
                        }
                        else {
                            if(json.status != 'ok') 
                                this.integration_details.tables[json.data.index[0]].settings[json.data.index[1]].data_type = 'text';
                            else
                                this.integration_details.tables[json.data.index[0]].settings[json.data.index[1]].options = json.data.options;
                        }
                    });
                    app.loading(false);
                });
        },
        cleanupDatabaseName() {
            this.integration_details.database = this.integration_details.database.toLowerCase();
            this.integration_details.database = this.integration_details.database.trim();
            this.integration_details.database = this.integration_details.database.replace(" ", "");
            this.integration_details.database = this.integration_details.database.replace(/\W/g, '');
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
        showInfo() {
            var isHidden = $("#info-row").hasClass("hidden");
            if (isHidden) {
                $("#info-row").removeClass("hidden");
            }
            this.getConnector();
        },
        hideInfo() {
            var isHidden = $("#info-row").hasClass("hidden");
            if (!isHidden) {
                $("#info-row").addClass("hidden");
            }
        },
        getConnector() {
            app.loading(true);
            fetch(`${baseUrl}/internal-api/v1/connectors/${app.selected_integration.id}`)
            .then(response => response.json())
            .then(json => {
                json.data.tables = [];
                if (json.data.schedule == undefined) {
                    json.data.schedule = {
                        schedule_type_id: 0,
                        name: "",
                        properties: []
                    };
                }
                let integration_details = json.data;
                for(i = 0; i < integration_details.settings.length; i++) {
                    if (integration_details.settings[i].data_type == "multiselect" && Array.isArray(integration_details.settings[i].value) == false)
                        integration_details.settings[i].value = [];
                    integration_details.settings[i].value = integration_details.settings[i].default_value;
                }
                this.integration_details = integration_details;
                app.is_selected = true;
                this.$root.oauth_complete = false;
                app.validateForm('database-settings');
                this.checkOptions();
                app.loading(false);
            });
        },
        checkOptions() {
            var urls = [];
            for(var i = 0; i < this.integration_details.settings.length; i++) {
                var setting = this.integration_details.settings[i];
                if(setting.options != null && typeof(setting.options) != 'array' && typeof(setting.options) != 'object' && (setting.data_type == 'select' || setting.data_type == 'multiselect')) {
                    try {
                        this.integration_details.settings[i].options = JSON.parse(setting.options);
                    }
                    catch(e) {
                        app.loading(true);

                        var options = FetchHelper.buildJsonRequest({
                            settings: this.integration_details.settings,
                            method_name: setting.options,
                            index: [i]
                        });

                        urls.push({ 'url': `${baseUrl}/internal-api/v1/connectors/${this.integration_details.id}/metadata`, 'options': options });
                    }
                }
            }

            let requests = urls.flatMap(({ url, options }) => fetch(url, options));
            if(requests.length > 0) {
                this.makeOptionCalls(requests);
            }
        },
        chooseIntegration(integration) {
            $(".dmiux_card-with-hdr__container").removeClass("active");
            $(event.target).closest(".dmiux_card-with-hdr__container").addClass("active");
            $(event.target).closest(".card").find("input").prop("checked", true);

            app.selected_integration = integration;
            this.has_instructions = false;
            this.has_known_limitations = false;
            if (app.selected_integration.instructions && app.selected_integration.instructions != '') {
                this.has_instructions = true;
            }
            if (app.selected_integration.known_limitations && app.selected_integration.known_limitations.length != 0) {
                this.has_known_limitations = true;
            }
            if (this.has_instructions || this.has_known_limitations)
                this.settings_tab = "instructions";
            else {
                this.settings_tab = "integration-settings"
            }
            this.showInfo();
        },
        changeSettingsTab(requested_settings_tab) {
            if (requested_settings_tab == 'integration-settings') {
                if (this.has_known_limitations && !this.agree_with_kl) {
                    notify.danger("You must accept the known limitations agreement before continuing.");
                    return;
                }
            }
            this.settings_tab = requested_settings_tab;
            return;
        },
        showTables(id, settings) {
            if(this.settings_ready == false)
                return; 

            this.settings_tab = 'tables';
            if (this.integration_details.tables.length > 0) {
                return;
            }
            app.loading(true);
            var settings_entered = true;
            for (var i=0; i<settings.length; i++) {
                if (this.isSettingRequired(settings[i]) && this.isSettingVisible(settings[i].visible_if)) {
                    if (settings[i].value == null || (settings[i].value == "" && settings[i].data_type != "boolean")) {
                        settings_entered = false;
                        this.has_tables = false;
                        app.loading(false);

                        let oauth_message = "";
                        if(this.integration_details.is_oauth == "t")
                            oauth_message = " You may need to reauthorize Bytespree.";

                        notify.send("You have not entered all of the Connector Settings!" + oauth_message, 'danger');
                        this.settings_tab = 'integration-settings';
                        return;
                    }
                }
            }
            if (settings_entered == true) {
                
                var options = FetchHelper.buildJsonRequest({
                    settings: settings
                });
                fetch(`${baseUrl}/internal-api/v1/connectors/${id}/tables`, options)
                    .then(response => {
                        if (response.status == 500) {
                            this.has_tables = false;
                            app.loading(false);
                            notify.send("Unable to retrieve tables. Check connector settings.", 'danger');
                        }
                        else {
                            response.json().then(json => {
                                if(!app.checkForError(json)) {
                                    app.loading(false);
                                    return;
                                }
                                this.connector_tables = json.data.tables;
                                this.integration_details.settings = json.data.settings;
                                
                                if (this.connector_tables.length > 0) {
                                    this.has_tables = true;
                                    app.loading(false);
                                }
                                else {
                                    this.has_tables = false;
                                    app.loading(false);
                                    
                                    // Hack to not show the error notification below for Google Analytics & other connectors that may not require pre-existing tables
                                    if (this.integration_details.ignore_no_tables != undefined && this.integration_details.ignore_no_tables == true) {
                                        this.addTable();
                                        return;
                                    }

                                    notify.send("No tables were found.  You can check connector settings, or manually enter tables.", 'danger');
                                }
                            });
                        }
                    })
            }
        },
        addAllTables() {
            // First delete all integration_details.tables
            this.integration_details.tables = [];
            // Now add all connector_tables to integration_details.tables
            for (var i=0; i<this.connector_tables.length; i++) {
                var table_settings = [];
                for(table_setting in this.integration_details.table_settings) {
                    table_settings.push({
                        id: this.integration_details.table_settings[table_setting].id,
                        is_required: this.integration_details.table_settings[table_setting].is_required,
                        value: this.integration_details.table_settings[table_setting].default_value,
                        name: this.integration_details.table_settings[table_setting].name,
                        friendly_name: this.integration_details.table_settings[table_setting].friendly_name,
                        data_type: this.integration_details.table_settings[table_setting].data_type,
                        description: this.integration_details.table_settings[table_setting].description,
                        options: this.integration_details.table_settings[table_setting].options,
                        visible_if: this.integration_details.table_settings[table_setting].visible_if,
                        required_if: this.integration_details.table_settings[table_setting].required_if
                    });
                }

                var schedule = {
                    id : 0,
                    partner_integration_table_id : 0,
                    added :true,
                    changed : false,
                    schedule_type_id: 0,
                    name: "",
                    properties: []
                };

                this.integration_details.tables.push({
                    "name": this.connector_tables[i].table_name,
                    "is_active": true,
                    "added": true,
                    "changed": false,
                    "deleted": false,
                    "connector_table_index": i,
                    "settings": table_settings,
                    "schedule": schedule
                });
            }
            // Now set used = true to all connector tables
            for (var j=0; j<this.connector_tables.length; j++) {
                this.connector_tables[j].used = true;
            }
        },
        removeAllTables() {
            // First delete all integration_details.tables
            this.integration_details.tables = [];
            // Now set used = false to all connector tables
            for (var j=0; j<this.connector_tables.length; j++) {
                this.connector_tables[j].used = false;
            }
        },
        addThisTable(i) {
            // First push this connector table with default values to integration_details
            var table_settings = [];
            for(table_setting in this.integration_details.table_settings) {
                table_settings.push({
                    id: this.integration_details.table_settings[table_setting].id,
                    is_required: this.integration_details.table_settings[table_setting].is_required,
                    value: this.integration_details.table_settings[table_setting].default_value,
                    name: this.integration_details.table_settings[table_setting].name,
                    friendly_name: this.integration_details.table_settings[table_setting].friendly_name,
                    data_type: this.integration_details.table_settings[table_setting].data_type,
                    description: this.integration_details.table_settings[table_setting].description,
                    options: this.integration_details.table_settings[table_setting].options,
                    visible_if: this.integration_details.table_settings[table_setting].visible_if,
                    required_if: this.integration_details.table_settings[table_setting].required_if
                });
            }

            var schedule = {
                id : 0,
                partner_integration_table_id : 0,
                added :true,
                changed : false,
                schedule_type_id: 0,
                name: "",
                properties: []
            };

            this.integration_details.tables.push({
                "name": this.connector_tables[i].table_name,
                "is_active": true,
                "added": true,
                "changed": false,
                "deleted": false,
                "connector_table_index": i,
                "settings": table_settings,
                "schedule": schedule
            });
            // Now set used = true to this connector table
            this.connector_tables[i].used = true;
        },
        removeThisTable(i) {
            // First get the connector_tables index for this item
            var j = this.integration_details.tables[i].connector_table_index;
            // Then slice this table from integration_details
            var newTables = this.integration_details.tables.slice(0, i).concat(this.integration_details.tables.slice(i + 1));
            this.integration_details.tables = newTables;
            // Then set used = false to this connector table
            this.connector_tables[j].used = false;
            
        },
        addTable() {  // add table manually
            var table_settings = this.buildTableSettings();

            var schedule = {
                id : 0,
                partner_integration_table_id : 0,
                added :true,
                changed : false,
                schedule_type_id: 0,
                name: "",
                properties: []
            };

            this.integration_details.tables.push({
                "name": "",
                "is_active": true,
                "added": true,
                "changed": false,
                "deleted": false,
                "settings": table_settings,
                "schedule" : schedule
            });
        },
        updateTable(index) {
            var table_settings = this.buildTableSettings();
            this.integration_details.tables[index].settings = table_settings;
        },
        buildTableSettings() {
            var table_settings = [];
            for(table_setting in this.integration_details.table_settings) {
                table_settings.push({
                    id: this.integration_details.table_settings[table_setting].id,
                    is_required: this.integration_details.table_settings[table_setting].is_required,
                    value: this.integration_details.table_settings[table_setting].default_value,
                    name: this.integration_details.table_settings[table_setting].name,
                    friendly_name: this.integration_details.table_settings[table_setting].friendly_name,
                    data_type: this.integration_details.table_settings[table_setting].data_type,
                    description: this.integration_details.table_settings[table_setting].description,
                    options: this.integration_details.table_settings[table_setting].options,
                    visible_if: this.integration_details.table_settings[table_setting].visible_if,
                    required_if: this.integration_details.table_settings[table_setting].required_if
                });
            }

            return table_settings;
        },
        deleteTable(table, index) {
            if (confirm("Are you sure you want to delete this table?")) {
                if (table.added) {
                    this.integration_details.tables.splice(index, 1);
                }
                else {
                    table.deleted = true;
                }
            }
        },
        addIntegration() {
            app.loading(true);
            delete this.integration_details.logo;
            let options = FetchHelper.buildJsonRequest(this.integration_details);
            fetch(baseUrl + "/internal-api/v1/data-lakes" , options)
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    app.loading(false);
                    localStorage.removeItem("projects-" + this.current_user.name);
                    window.location.href = baseUrl + "/data-lake";
                })
                .catch((error) => {
                    app.loading(false);
                    ResponseHelper.handleErrorMessage(error, "The database was unable to be added.");
                });
        },
        checkSchedule: function() {
            if(this.table_schedule_type == "one") {
                if (this.schedule_type_id == 0) {
                    alert ("Please choose a frequency");
                    return;
                } 
                // schedule_type_id is not 0 - it is 1, 2, 3, or 4 (right now)
                if (this.properties.length > 0) {
                    for (var i=0; i<this.properties.length; i++) {
                        if (this.properties[i].value === "" || this.properties[i].value === "none" || this.properties[i].value === undefined) {
                            alert ("Please enter " + this.properties[i].name);
                            return;
                        }
                    }
                }
                this.addIntegration();
            }
            else {
                for(var i=0; i < this.integration_details.tables.length; i++) {
                    if (this.integration_details.tables[i].schedule.schedule_type_id == 0) {
                        alert ("Please choose a frequency for table " + this.integration_details.tables[i].name);
                        return;
                    } 
                    for(var j=0; j < this.integration_details.tables[i].schedule.properties.length; j++) {
                        if (this.integration_details.tables[i].schedule.properties[j].value === "" || this.integration_details.tables[i].schedule.properties[j].value === null || this.integration_details.tables[i].schedule.properties[j].value === undefined) {
                            alert ("Please enter " + this.integration_details.tables[i].schedule.properties[j].name + " for table " + this.integration_details.tables[i].name);
                            return;
                        }
                    }
                }
                this.addIntegration();
            }
        },
        changeAllTableSchedules: function() {
            if (this.integration_details.use_tables == true) {
                for(var i=0; i < this.integration_details.tables.length; i++) {
                    this.integration_details.tables[i].schedule.schedule_type_id = this.schedule_type_id;
                    this.integration_details.tables[i].schedule.name = this.schedule_type_name;
                    this.integration_details.tables[i].schedule.properties = JSON.parse(JSON.stringify(this.properties));
                }
            }
            else {
                this.integration_details.schedule.properties = JSON.parse(JSON.stringify(this.properties));
            }
        },
        changeAllTableScheduleProperties: function() {
            if (this.integration_details.use_tables == true) {
                for(var i=0; i < this.integration_details.tables.length; i++) {
                    for(var j=0; j < this.integration_details.tables[i].schedule.properties.length; j++) {
                        for(var k=0; k < this.properties.length; k++) {
                            if(this.integration_details.tables[i].schedule.properties[j].id == this.properties[k].id &&
                                this.integration_details.tables[i].schedule.properties[j].value != this.properties[k].value) {
                                this.integration_details.tables[i].schedule.properties[j].value = this.properties[k].value
                            }
                        }
                    }
                }
            }
            else {
                for(var j=0; j < this.integration_details.schedule.properties.length; j++) {
                    for(var k=0; k < this.properties.length; k++) {
                        if(this.integration_details.schedule.properties[j].id == this.properties[k].id &&
                            this.integration_details.schedule.properties[j].value != this.properties[k].value) {
                            this.integration_details.schedule.properties[j].value = this.properties[k].value
                        }
                    }
                }
            }
        },
        getScheduleTypeName(schedule_type_id) {
            for(var i=0; i < this.schedule_types.length; i++) {
                if (this.schedule_types[i].id == schedule_type_id) {
                    return this.schedule_types[i].name;
                }
            }
            return "";
        },
        setFrequency: function () {
            fetch(baseUrl + `/internal-api/v1/data-lakes/schedules/${this.schedule_type_id}/properties`)
            .then(response => response.json())
            .then(json => {
                if (app.checkForError(json)) {
                    this.properties = json.data;
                }
                else {
                    alert("Unable to get schedule properties");
                }
            });            
        },
        getIntegrationScheduleTypeProperties: function() {  
            fetch(baseUrl + `/internal-api/v1/data-lakes/schedules/${this.schedule_type_id}/properties`)
                .then(response => response.json())
                .then(json => {
                    if (!app.checkForError(json)) {
                        return;
                    }
                    this.properties = json.data;
                    this.changeAllTableSchedules();
                });
        },
        changeFrequency: function() {
            if (this.integration_details.use_tables === false) {
                this.integration_details.schedule.name = "";
                this.integration_details.properties = [];
            }
            this.schedule_type_name = this.getScheduleTypeName(this.schedule_type_id);
            this.getIntegrationScheduleTypeProperties();
        },
        changeTableFrequency: function(schedule) {
            schedule.name = this.getScheduleTypeName(schedule.schedule_type_id);
            this.getIntegrationTableScheduleTypeProperties(schedule);
        },
        getIntegrationTableScheduleTypeProperties: function(schedule) {  
            fetch(baseUrl + `/internal-api/v1/data-lakes/schedules/${this.schedule_type_id}/properties`)
                .then(response => response.json())
                .then(json => {
                    if (!app.checkForError(json)) {
                        return;
                    }
                    schedule.properties = json.data;
                });
        },
        makeOAuthCall: function (url) {
            var vue_state = {
                "integration_details": this.integration_details,
                "selected_tab": app.selected_tab,
                "properties": this.properties,
                "schedule_type_id": this.schedule_type_id,
                "selected_integration": app.selected_integration,
                "settings_tab": this.settings_tab,
                "table_schedule_type": this.table_schedule_type
            };
            var call = { "integration_id": this.integration_details.id, "settings": {}, "url": url, "vue_state": vue_state, "from": "database" }
            for(var index = 0; index < this.integration_details.settings.length; index++) 
            {
                var setting = this.integration_details.settings[index];

                if(setting.is_private == false && setting.is_required == true && (setting.value == "" || setting.value == null)) {
                    notify.danger("Please enter the required information or show all information.");
                    return;
                }
                else {
                    call["settings"][setting.name] = setting.value;
                }
            }

            var options = FetchHelper.buildJsonRequest(call);

            fetch(baseUrl + "/OAuth/sendOAuth", options)
                .then(response => response.json())
                .then(json => {
                    if (!app.checkForError(json)) {
                        return;
                    }
                    document.cookie = "bytespree_state=" + json.data.state + ";path=/;domain=bytespree.com";
                    window.location = json.data.url;
                });
        },
        settingsReady: function () {
            for (var i = 0; i < this.integration_details.settings.length; i++) {
                if (this.isSettingRequired(this.integration_details.settings[i]) && this.isSettingVisible(this.integration_details.settings[i].visible_if)) {
                    if (this.integration_details.settings[i].value == null || (this.integration_details.settings[i].value == "" && this.integration_details.settings[i].data_type != "boolean") || (this.integration_details.settings[i].data_type == "boolean" && this.integration_details.settings[i].value == false)) {
                        this.settings_ready = false;
                        return false;
                    }
                }
            }
            this.settings_ready = true;
            return true;
        },
        tableSettingsReady: function () {
            for (var i = 0; i < this.integration_details.tables.length; i++) {
                var table = this.integration_details.tables[i];
                for(var i2 = 0; i2 < table.settings.length; i2++) {
                    var setting = table.settings[i2];
                    if (this.isSettingRequired(setting, table.name) && this.isSettingVisible(setting.visible_if, table.name)) {
                        if (setting.value == null || (setting.value == "" && setting.data_type != "boolean") || (setting.data_type == "boolean" && setting.value == false)) {
                            return false;
                        }
                    }
                }
            }
            return true;
        },
        tableSchedulesReady() {
            for (var i = 0; i < this.integration_details.tables.length; i++) {
                if (this.integration_details.tables[i].schedule.schedule_type_id == 0) {
                    return false;
                }
            }
            return true;
        },
        getVariables(null_invisibles, table_name = "") {
            var variables = {};
            this.integration_details.settings.forEach((setting) => {
                if (null_invisibles) {
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
                this.integration_details.tables.forEach((table) => {
                    if (table.name == table_name) {
                        if (Array.isArray(table.settings)) {
                            table.settings.forEach((setting) => {
                                variables[setting.name] = setting.value;
                            });
                        }
                    }
                });
            }
            return variables;
        },
        isSettingVisible: function (visible_if, table_name = "") {
            if (visible_if == "" || visible_if == null) return true;
            var variables = this.getVariables(false, table_name);
            var result = ConditionParser.evaluate(visible_if, variables, true);
            return result;
        },
        isSettingRequired(setting, table_name = '') {
            required_if = setting.required_if;
            if (setting.is_required == true) return true;
            if (required_if == "" || required_if == null) return false;
            var variables = this.getVariables(true, table_name);
            var result = ConditionParser.evaluate(required_if, variables, false);
            return result;
        },
        // function restrict to select future dates from input type date
        restrictFutureDate(){
            var dtToday = new Date();
            dtToday.toUTCString();
            var month = dtToday.getMonth() + 1;
            var day = dtToday.getDate();
            var year = dtToday.getFullYear();
            if(month < 10)
                month = '0' + month.toString();
            if(day < 10)
                day = '0' + day.toString();
            var date = year + '-' + month + '-' + day;
            this.sync_max_date = date;
        },
        settingChangedFromChildComponent(setting_key, value) {
            this.storeIntegrationData(setting_key, value);
            this.integration_details.settings.forEach((setting, index) => {
                if (setting.name == setting_key){
                    this.integration_details.settings[index].value = value;
                }
            });
        },
        storeIntegrationData(data_key, data) {
            this.integration_data_store[data_key] = data;
        }
    },
    computed: {
        databaseComplete() {
            if (this.integration_details.name == 'Basic') {
                if (this.integration_details.database && this.integration_details.database != '') {
                    if (this.integration_details.server_id && this.integration_details.server_id != '') {
                        return true;
                    }
                    else {
                        return false;
                    }
                }
                else {
                    return false;
                }
            }

            if (!this.settingsReady()) {
                return false;
            }

            if (this.integration_details.use_tables === true) {
                if (!this.tableSettingsReady()) {
                    return false;
                }
                if (this.table_schedule_type == "multi" && !this.tableSchedulesReady()) {
                    return false;
                }

                if ((!this.integration_details.database) || (this.integration_details.database.trim() === "") || 
                (!this.integration_details.server_id) || (this.integration_details.server_id === "") ||
                (this.integration_details.tables.length === 0) || (this.integration_details.tables[0].name.trim() === "") ||
                (this.schedule_type_id == 0 && this.table_schedule_type === "one") || app.is_selected === false) {
                    return false;
                }
                else {
                    return true;
                }                
            }
            else {
                if ((!this.integration_details.database) || (this.integration_details.database.trim() === "") || 
                (!this.integration_details.server_id) || (this.integration_details.server_id === "") ||
                (this.schedule_type_id == 0) || app.is_selected === false) {
                    return false;
                }
                else {
                    return true;
                }                
            }
        },
        tableSettingsCount() {
            var count = 0;
            for(var index = 0; index < this.integration_details.table_settings.length; index++)
            {
                if(this.integration_details.table_settings[index].is_private == false)
                    count++;
            }

            return count;
        },
        settingsComponent() {
            if (this.$root.selected_integration.name == '') {
                return;
            }

            let possible_name = this.$root.selected_integration.name.toLowerCase().replace(' ', '-') + '-settings';

            let componentExists = possible_name in Vue.options.components;
            
            if(! componentExists ) {
                return;
            }

            return possible_name;
        },
        tablesComponent() {
            if (this.$root.selected_integration.name == '') {
                return;
            }

            let possible_name = this.$root.selected_integration.name.toLowerCase().replace(' ', '-') + '-tables';

            let componentExists = possible_name in Vue.options.components;

            if(! componentExists ) {
                return;
            }

            return possible_name;
        }
    }
})


var app = new Vue({
    el: '#app',
    name: 'Database',
    components: {
        wizard
    },
    data: {
        integrations: [],
        selected_integration: {
            "name": '',
            "database": ''
        },
        servers : [],
        show: true,
        selected_tab: 'database-integration',
        toolbar: {
            "breadcrumbs": []
        },
        current_user : null,
        schedule_types: [],
        is_selected: false,
        oauth_complete: false,
        datetime: ''
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
        getConnectors() {
            this.loading(true);
            fetch(baseUrl + "/internal-api/v1/connectors")
                .then(response => response.json())
                .then(json => {
                    this.integrations = json.data;
                    this.loading(false);
                });
        },
        getBreadcrumbs() {
            this.loading(true);
            fetch(baseUrl + "/internal-api/v1/crumbs")
                .then(response => response.json())
                .then(json => {
                    this.toolbar.breadcrumbs = json.data;
                });
        },
        getServers() {
            this.loading(true);
            fetch(baseUrl + "/internal-api/v1/servers")
                .then(response => response.json())
                .then(json => {
                    this.servers = json.data;
                });
        },
        getCurrentUser() {          
            fetch(baseUrl + "/internal-api/v1/me")
                .then(response => response.json())
                .then(json => {
                    if (!app.checkForError(json)) {
                        return;
                    }
                    app.callbacks = json.data;
                    this.current_user = json.data;
                });         
        },
        getScheduleTypes() {
            fetch(baseUrl + "/internal-api/v1/data-lakes/schedules/types")
                .then(response => response.json())
                .then(json => {
                    this.schedule_types = json.data;
                });
        },
        validateForm(requestedTab) {
            if(requestedTab == 'database-settings') {
                if(!this.is_selected) {
                    notify.danger("You must choose a connector.");
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
                    notify.danger("You must choose a connector.");
                    this.selected_tab = "database-integration";
                    return;
                }

                if (!this.$refs.wizard.settingsReady()) {
                    notify.danger("You must fill in all required settings.");
                    this.selected_tab = "database-settings";
                    return;
                }

                if (this.$refs.wizard.integration_details.use_tables === true) {
                    if(!this.$refs.wizard.tableSettingsReady()) {
                        notify.danger("You must fill in all required settings.");
                        this.selected_tab = "database-settings";
                        return;
                    }

                    if (this.$refs.wizard.integration_details.tables.length == 0) {
                        notify.danger("You must add at least one table for this database.");
                        this.selected_tab = "database-settings";
                        return;
                    }
                }
            }
            this.selected_tab = requestedTab;   
        },

        checkForError(json) {
            if (json.status == "error") {
                notify.danger(json.message);
                return false;
            }
            else {
                return true;
            }    
        },
        setOAuthData(json) {
            this.is_selected = true;
            this.settings_tab = json.data.settings_tab;
            this.selected_tab = json.data.selected_tab;
            this.$refs.wizard.integration_details = json.data.integration_details;
            this.$refs.wizard.properties = json.data.properties;
            this.$refs.wizard.schedule_type_id = json.data.schedule_type_id;
            app.selected_integration = json.data.selected_integration;
            this.$refs.wizard.table_schedule_type = json.data.table_schedule_type;
            document.cookie = "bytespree_state=; expires=Thu, 01 Jan 1970 00:00:00 UTC;path=/;domain=bytespree.com";
        }
    },
    mounted: function() {
        this.loading(false);
        this.getConnectors();
        this.getBreadcrumbs();
        this.getServers();
        this.getCurrentUser();
        this.getScheduleTypes();

        var url = new URL(window.location.href);
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
                        if(json.data.error_message != undefined) {
                            notify.danger(json.data.error_message);
                        } 
                        else {
                            this.oauth_complete = true;
                        }
                        this.setOAuthData(json);
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
    },
    computed: {
        schedule_types_computed() {
            var types = [];

            for(var i = 0; i < this.schedule_types.length; i++)
            {
                if(this.$refs.wizard.integration_details.fully_replace_tables == true && this.$refs.wizard.integration_details.use_tables == true && (this.schedule_types[i].name == "Every Hour" || this.schedule_types[i].name == "Every 15 minutes"))
                    continue;
                else
                    types.push(this.schedule_types[i]);
            }

            return types;
        }
    }
})

const inputs = document.querySelectorAll("input, select, textarea");

inputs.forEach(input => {
    input.addEventListener(
        "invalid",
        event => {
            input.classList.add("invalid");
            input.focus();
        },
        false
    );
});
