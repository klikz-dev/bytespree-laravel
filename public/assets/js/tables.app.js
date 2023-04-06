var app = new Vue({
    el: '#app',
    name: 'StudioExplorer',
    components: {
        "toolbar": toolbar,
        "records": records,
        "ribbon": ribbon,
        "change-column-preference": modals.change_column_preference,
        "join-manager": modals.join_manager,
        "union-manager": modals.union_manager,
        "map-column": modals.map_column,
        "custom-filter": modals.custom_filter,
        "longest": modals.longest,
        "table-notes": modals.table_notes,
        "counts": modals.counts,
        "copy-column": modals.copy_column,
        "add-flag": modals.add_flag,
        "add-transformation": modals.add_transformation,
        "add-attachment": modals.add_attachment,
        "add-comment": modals.add_comment,
        "custom-sort": modals.custom_sort
    },
    data: {
        initial_mount: true,
        active_custom_id: "",
        modals: {
            counts: false,
            copy_column: false,
            longest: false,
            custom_filter: false,
            add_attachment: false,
            table_notes: false,
            map_column: false,
            change_column_preference: false,
            join_manager: false,
            add_comment: false,
            custom_sort: false,
            union_manager: false,
        },
        control_id: control_id,
        destination_schema_id: destination_schema_id,
        mapping: {
            selected_mapping_module: 0,
            modules: [],
            mappings: []
        },
        explorer: {
            record_count: 0,
            type: "",   
            selected_column: "",
            selected_column_data_type: "",
            selected_column_index: 0,
            selected_column_unstructured: false,
            selected_alias: "",
            selected_sql_definition: "",
            selected_is_aggregate: false,
            selected_prefix: "",
            tables: [],
            all_columns: [],
            active_users: [],
            records: [],
            viewing_type: "Standard",
            limit: 10,
            offset: 0,
            page_num: 1,
            page_amt: 1,
            pivoted: false,
            prefix_list: [],
            valid_prefix_list: [],
            send_columns: false,
            scroll_to_column: sent_column,
            sent_column: sent_column,
            selected_view : {},
            pending_count: true,
            query: {
                columns: [],
                table: table,
                prefix: table,
                schema: schema,
                filters: [],
                joins: [],
                order: {
                    custom_expression: "",
                    order_column: "",
                    alias: "",
                    order_type: "desc",
                    prefix: "",
                    sql_definition: ""
                },
                is_grouped: false,
                transformations: {},
                unions: [],
                union_all: false,
            },
            valid_query: {
                columns: [],
                table: table,
                prefix: table,
                schema: schema,
                filters: [],
                joins: [],
                order: {
                    order_column: "",
                    alias: "",
                    order_type: "desc",
                    prefix: "",
                    sql_definition: ""
                },
                is_grouped: false,
                transformations: {}
            },
            validation_queries: {
                current_query: "",
                saved_query: ""
            },
            origin_query: {},
            view_mode : '',
            view: null,
            saved_query: {
                'id': -1,
                'name': "",
                'description': ""
            },
            publisher: {
                id: -1
            }
        },
        temp_selected_allow_clear: false,
        temp_selected_column: "",
        temp_selected_prefix: "",
        temp_selected_column_data_type: "",
        temp_selected_alias: "",
        temp_selected_sql_definition: "",
        temp_selected_is_aggregate: false,
        selected_filter: {},
        counts: {
            quantity: 25,
            counts: []
        },
        toolbar: {
            breadcrumbs: [],
            buttons: []
        },
        added_columns: [],
        comments: [],
        flags: [],
        attachments: [],
        mappings: [],
        currentUser: {
            is_admin: false,
            name: ""
        },
        table_notes: [],
        table_notes_fetched: false,
        publishing_destinations: [],
        edit_mode: false,
        permissions: [],
        flashError: flashError,
        max_upload_size: max_upload_size,
        options:
        {
            method: 'post',
            headers: {
                'Content-type': 'application/x-www-form-urlencoded; charset=UTF-8'
            }
        },
        modified: false,
        completed: completed,
        mobile: false,
        users: [],
        ready: false,
        loaded : {
            all_columns: false,
            columns: false,
            records: false
        },
        rename_view_status: false,
        pollingForTables: null,
        pollingForTableName: null,
        pollingForCatalog: null,
        show_note_id: null,
        table_exists: true,
        whenTablesLoaded: {
            success: null,
            failure: null
        }
    },
    methods: {
        /** Vue.js Method
          *******************************************************************************
          * checkPerms
          * Params:
          * * perm_name        string         A particular permission identifier.
          *******************************************************************************
          * Checks to see if a particular function of the UX should be accessible by the
          * current user.
          */
        checkPerms(perm_name) {
            if (this.currentUser.is_admin === true) {
                return true;
            }
            else {
                for(var i = 0; i < this.permissions.length; i++) {
                    if (this.permissions[i].product_child_id == this.control_id) {
                        for (var j = 0; j < this.permissions[i].name.length; j++) {
                            if (this.permissions[i].name[j] == perm_name) {
                                return true;
                            }
                        }
                    }
                }
            }
            return false;
        },
        /** Vue.js Method
          *******************************************************************************
          * getPermissions
          *******************************************************************************
          * Retrieves permissions for the current user
          */
        getAllPermissions() {
            fetch(`${baseUrl}/internal-api/v1/me/permissions?product=studio`)
                .then(response => response.json())
                .then(json => {
                    this.permissions = json.data;
                });
        },
        /** Vue.js Method
            *******************************************************************************
            * getCurrentUser
            *******************************************************************************
            * Retrieves information about the current user. Data is stored in local storage 
            * until 15 minutes (in milliseconds) have elapsed, at which point the data is 
            * fetched again.
            */
        getCurrentUser() {
            fetch(`${baseUrl}/internal-api/v1/me`)
                .then(response => response.json())
                .then(json => {
                    this.currentUser = json.data;
                })
        },
        getBreadcrumbs() {
            this.toolbar.breadcrumbs = [
            {
                title: project_name,
                location: `/studio/projects/${this.control_id}`
            },
            {
                title: this.explorer.query.table,
                location: `/studio/projects/${this.control_id}/tables/${this.explorer.query.schema}/${this.explorer.query.table}`
            }];
        },
        getTables(type = false) {
            fetch(`${baseUrl}/internal-api/v1/studio/projects/${this.control_id}/tables?get_types=${type}`)
                .then(FetchHelper.handleJsonResponse)
                .then(json => {                   
                    this.explorer.tables = json.data.tables;
                    if(type == true){
                        this.explorer.selected_view = this.explorer.tables.filter((tbl) => {
                            if(tbl.table_name == this.explorer.query.table && tbl.table_schema == this.explorer.query.schema) 
                                return tbl;
                        });
                        this.explorer.selected_view = this.explorer.selected_view[0];
                    }
                })
                .catch((error) => {
                    ResponseHelper.handleErrorMessage(error, "Failed to get tables");
                });
        },
        getFlags() {
            fetch(`${baseUrl}/internal-api/v1/studio/projects/${this.control_id}/tables/${this.explorer.query.schema}/${this.explorer.query.table}/flags`)
                .then(response => response.json())
                .then(json => {
                    this.flags = json.data;
                });
        },
        getPublishingDestinations() {
            fetch(`${baseUrl}/internal-api/v1/studio/projects/${this.control_id}/publishers/destinations`)
                .then(response => response.json())
                .then(json => {
                    this.publishing_destinations = json.data;
                });
        },
        isColumnSelected() {
            if(this.explorer.selected_column == "") {
                alert("You must select a column.");
                this.modals.longest = false;
                this.modals.add_attachment = false;
                this.modals.counts = false;
                return false;
            }
            else {
                return true;
            }
        },
        getAttachments() {
            fetch(`${baseUrl}/internal-api/v1/studio/projects/${this.control_id}/tables/${this.explorer.query.schema}/${this.explorer.query.table}/files`)
                .then(response => response.json())
                .then(json => {
                    this.attachments = json.data;
                });
        },
        closeModal() {
            app.modals.custom_filter = false;
            app.edit_mode = false;
            this.selected_filter = {};
            $('.dmiux_popup__close_popup').trigger('click');
        },
        getComments() {
            fetch(`${baseUrl}/internal-api/v1/studio/projects/${this.control_id}/tables/${this.explorer.query.schema}/${this.explorer.query.table}/comments`)
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    this.comments = json.data;
                    if(this.comments){
                        var _self = this;
                        Object.keys(_self.comments).forEach(function(item, index){
                            if(item){
                                Object.keys(_self.comments[item]).forEach(function(item1, index1){
                                    if(_self.comments[item][index1]){
                                        _self.comments[item][index1]['created_at'] = DateHelper.formatLocaleCarbonDate(_self.comments[item][index1]['created_at']);
                                    }
                                });
                            }
                        });
                    }
                });
        },
        getActiveUsers() {
            fetch(`${baseUrl}/internal-api/v1/studio/projects/${this.control_id}/tables/${this.explorer.query.schema}/${this.explorer.query.table}/active-users`)
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    this.explorer.active_users = json.data;
                });
        },
        getProjectUsers() {
            fetch(`${baseUrl}/internal-api/v1/studio/projects/${this.control_id}/users`)
                .then(response => response.json())
                .then(json => {
                    this.users = json.data;
                    
                    var mentionableUsers = [];
                    for(i = 0; i < this.users.length; i++) {
                        if (this.users[i].name != null) {
                            mentionableUsers.push({
                                key: this.users[i].name,
                                value: this.users[i].user_handle
                            });
                        }
                    }

                    var tribute = new Tribute({
                        values: mentionableUsers
                    });

                    tribute.attach(document.querySelectorAll(".atmention"));
                });
        },
        getProjectMappingModules() {
            fetch(`/internal-api/v1/studio/projects/${this.control_id}/mapping-modules`)
                .then(response => response.json())
                .then(json => {
                    for(module in json.data) {
                        for(field in json.data[module].fields) {
                            var keyVals = [];
                            eval(json.data[module].fields[field].calculation_script);
                            json.data[module].fields[field].options = keyVals;
                        }
                    }
                    this.mapping.modules = json.data;
                }); 
        },
        getMappings() {
            fetch(`${baseUrl}/internal-api/v1/studio/projects/${this.control_id}/tables/${this.explorer.query.schema}/${this.explorer.query.table}/mappings`)
                .then(response => response.json())
                .then(json => {
                    this.mappings = json.data;
                });
        },
        getRecords(callback, count_records) {
            this.loaded.records = false;

            if(! count_records) {
                this.ready = false;
            }

            // This prevents the user from going past the maximum number of pages somehow
            if(this.explorer.page_num > this.explorer.page_amt && this.explorer.page_amt > 0)
            {
                this.page_num = this.page_amt;
            }

            var joins = [];
            var columns = [];
            if(this.explorer.viewing_type == "Join") {
                if(this.explorer.query.joins == []) {
                    notify.danger("Please specify a join before applying.");
                    return;
                }
                
                joins = this.explorer.query.joins;
            }

            if (this.explorer.send_columns) {
                columns = this.explorer.query.columns;
            }

            let options = FetchHelper.buildJsonRequest({
                prefix: this.explorer.query.prefix,                                
                order: this.explorer.query.order,
                limit: this.explorer.limit,
                offset: this.explorer.offset,
                page_num: this.explorer.page_num,
                filters: this.explorer.query.filters,
                transformations: this.explorer.query.transformations,
                joins: joins,
                columns: columns,
                is_grouped : this.explorer.query.is_grouped,
                count_records: count_records,
                schema: this.explorer.query.schema,
                unions: this.explorer.query.unions,
                union_all: this.explorer.query.union_all,
            });

            if (count_records) {
                var sub_path = 'count';
            } else {
                var sub_path = 'records';
            }

            fetch(`${baseUrl}/internal-api/v1/studio/projects/${this.control_id}/tables/${this.explorer.query.schema}/${this.explorer.query.table}/${sub_path}`, options)
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    if (! count_records) {
                        this.ready = true;
                        
                        if (count_records == undefined) {
                            this.explorer.pending_count = true;
                            this.getRecords(null, true);
                        }
                    } else {
                        this.explorer.pending_count = false;
                        this.explorer.record_count = json.data.total;
                        this.explorer.page_amt = Math.ceil(json.data.total / this.explorer.limit);
                        this.explorer.type = json.data.total_type;
                        return;
                    }

                    this.explorer.valid_query = JSON.parse(JSON.stringify(this.explorer.query));
                    this.explorer.valid_prefix_list = JSON.parse(JSON.stringify(this.explorer.prefix_list));
                    this.explorer.validation_queries.current_query = json.data.md5_query;

                    this.explorer.records = json.data.records;
                    window.document.title = "Bytespree | " + this.explorer.query.table;
                    this.loaded.records = true;

                    if(typeof(callback) != "undefined" && callback != null) {
                        if(typeof(callback.success) != "undefined") {
                            callback.success();
                        }
                    }

                    if (this.whenTablesLoaded.success != null) {
                        this.whenTablesLoaded.success();
                        this.whenTablesLoaded.success = null;
                    }          
                })
                .then(() => {
                    if (this.initial_mount) {
                        if (localStorage.getItem("pivoted") == null) {
                            localStorage.setItem("pivoted", "false");
                        }
                        else if (localStorage.getItem("pivoted") == "true") {
                            this.explorer.pivoted = true;
                        }
                        this.initial_mount = false;
                    }
                    else {
                        if (localStorage.getItem("pivoted") == "false" && this.explorer.pivoted == true) {
                            this.explorer.pivoted = false;
                        }
                    }                
                    setTableStyles();
                    var sent_column_elem = document.getElementById('table_' + this.explorer.query.prefix + '_' + this.explorer.scroll_to_column);
                    if(sent_column_elem != null) {
                        sent_column_elem.scrollIntoView({behavior: "smooth" });
                        this.explorer.scroll_to_column = "";
                    }
                    this.$refs.records.hideShowArrows();
                })
                .catch((error) => {
                    this.ready = true;

                    if(error.json != undefined && error.json.data == true) {
                        window.location.href = `${baseUrl}/studio/projects/${app.control_id}`;
                    }

                    this.explorer.query = JSON.parse(JSON.stringify(this.explorer.valid_query));
                    this.explorer.prefix_list = JSON.parse(JSON.stringify(this.explorer.valid_prefix_list));
                    if(typeof(callback) != "undefined" && callback != null) {
                        if(typeof(callback.failure) != "undefined") {
                            callback.failure();
                        }
                    } else {
                        ResponseHelper.handleErrorMessage(error, "An error occurred while getting records.");
                    }
                });
        },
        publishView(view_name, view_type, view_frequency, view_schedule, view_message) {
            this.ready = false;

            if(this.explorer.viewing_type == "Join") {
                if(this.explorer.valid_query.joins == []) {
                    notify.danger("Please specify a join before applying.");
                    return;
                }
            }

            var query = JSON.parse(JSON.stringify(this.explorer.valid_query));
            query.view_name = view_name;
            query.view_type = view_type;
            query.view_frequency = view_frequency;
            query.view_schedule = view_schedule;
            query.view_message = view_message;

            this.cleanupQueryForSave(query);

            let options = FetchHelper.buildJsonRequest(query);

            fetch(`/internal-api/v1/studio/projects/${this.control_id}/views`, options)
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    this.ready = true;
                    if(json.status == "ok") {
                        notify.success("View has been created successfully.");
                        this.updateSavedQuery();
                        this.getTablesOnTimer(json.data.view_name, json.data.view_catalog); // Start polling for view creation
                        this.closeModal();
                    }
                })
                .catch((error) => {
                    this.ready = true;
                    this.closeModal();
                    ResponseHelper.handleErrorMessage(error, "Unable to publish view at this time.");
                });
        },
        getTablesOnTimer(table_name, catalog) {
            this.pollingForTableName = table_name;
            this.pollingForCatalog = catalog;
            this.pollingForTables = setInterval(() => {
                fetch(`${baseUrl}/internal-api/v1/studio/projects/${this.control_id}?get_types=false`)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.explorer.tables = json.data.tables;

                        if(this.explorer.tables.filter((tbl) => tbl.table_name == this.pollingForTableName && tbl.table_catalog == this.pollingForCatalog).length > 0) {
                            clearInterval(this.pollingForTables);
                            this.pollingForTableName = null;
                            this.pollingForCatalog = null;
                        }
                    })
                    .catch((error) => {
                        clearInterval(this.pollingForTables);
                    });
            }, 6000);
        },
        saveView(view_frequency, view_schedule, view_message) {
            this.ready = false;
            var view_id = this.explorer.view.id;

            var query = JSON.parse(JSON.stringify(this.explorer.valid_query));

            if(this.explorer.viewing_type == "Join") {
                if(query.joins == []) {
                    notify.danger("Please specify a join before applying.");
                    return;
                }
            } else {
                query.joins = [];
            }

            query.view_message = view_message;
            query.view_frequency = view_frequency;
            query.view_schedule = view_schedule;

            this.cleanupQueryForSave(query);

            let options = FetchHelper.buildJsonRequest(query, 'put');

            fetch(`/internal-api/v1/studio/projects/${this.control_id}/views/${view_id}`, options) // todo
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    this.ready = true;
                    this.whenTablesLoaded.success = function(){
                        notify.success("View has been updated successfully.");
                    };
                    this.returnToView(true);
                })
                .catch((error) => {
                    this.ready = true;
                    ResponseHelper.handleErrorMessage(error, "Unable to publish view at this time.");
                });
        },
        cleanupQueryForSave(query) {
            if (Array.isArray(query.joins)) {
                query.joins.forEach(join => {
                    delete join.source_columns;
                });
            }
        },
        getTableNotes() {
            fetch(`/internal-api/v1/studio/projects/${this.control_id}/tables/${this.explorer.query.schema}/${this.explorer.query.table}/notes`)
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    let notes = json.data;
                    if (notes != null && typeof notes == 'object') {
                        notes = Object.values(notes);
                    } else {
                        notes = [];
                    }
                    notes = notes.map((note) => {
                        note.is_expanded = false;
                        note.is_overflowing = false;
                        note.updated_at = DateHelper.formatLocaleCarbonDate(note.updated_at);
                        return note;
                    });

                    this.table_notes = notes;
                    this.table_notes_fetched = true;

                    let table_schema = this.$root.explorer.query.schema;

                    this.table_notes = this.table_notes.filter(function(note){
                        return note.schema == table_schema;
                    });
                    
                    if(this.table_notes.length > 0) {
                        var note = this.table_notes.slice(0, 1)[0];
                        this.$refs.records.$refs.table_summary.isNotesOverflowing(note.id, note.is_overflowing);
                    }
                });
        },
        getProjectTableColumns(callback, ignore_user_preferences, previous_prefix, timeout = 0) {
            this.loaded.columns = false;
            
            var joins = [];
            var columns = [];
            var transformations = [];

            if(this.explorer.viewing_type == "Join") {
                joins = this.explorer.query.joins;
            }

            if (this.explorer.send_columns) {
                columns = this.explorer.query.columns;
            }
            transformations = this.explorer.query.transformations;

            let options = FetchHelper.buildJsonRequest({
                "prefix" : this.explorer.query.prefix,
                "schema": this.explorer.query.schema,
                "joins" : joins,
                "columns" : columns,
                "transformations" : transformations,
                "ignore_user_preferences" : ignore_user_preferences,
                "previous_prefix": previous_prefix
            });

            var url = `${baseUrl}/internal-api/v1/studio/projects/${this.control_id}/tables/${this.explorer.query.schema}/${this.explorer.query.table}/columns` // todo

            let successful = false;
            let timeout_id = null;
            let controller = null;
            
            if (timeout > 0) {
                controller = new AbortController();
                timeout_id = setTimeout(() => controller.abort(), timeout);
                options.signal = controller.signal;
            }

            fetch(url, options)
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    if (timeout_id !== null) {
                        clearTimeout(timeout_id);
                    }
                    this.loaded.columns = true;
                    if (json.status == "ok") {
                        this.explorer.query.columns = json.data;
                        this.refreshColumns();
                        this.explorer.send_columns = true;
                        successful = true;
                    }
                })
                .then(() => {
                    if (typeof(callback) != 'undefined') {
                        if (successful && typeof(callback.success) != 'undefined') {
                            callback.success();
                        }
                        else if (! successful && typeof(callback.failure) != 'undefined') {
                            callback.failure();
                        }
                        else {
                            callback();
                        }
                    }
                    this.active_custom_id = "";
                })
                .catch((error) => {
                    if (typeof(callback) != 'undefined') {
                        if (typeof(callback.failure) != 'undefined') {
                            callback.failure();
                        }
                        else {
                            callback();
                        }
                    }
                    ResponseHelper.handleErrorMessage(error, "Failed to get table columns");
                });
        },
        refreshColumns() {
            // initialize prefix list
            this.explorer.prefix_list = [ this.explorer.query.prefix, "custom" ];
            this.explorer.query.columns.forEach((column) => {
                if (this.explorer.prefix_list.includes(column.prefix) == false) {
                    this.explorer.prefix_list.push(column.prefix);
                }
                if (column.checked === false && 
                    column.prefix == this.explorer.query.order.prefix &&
                    column.column_name == this.explorer.query.order.order_column) {
                    this.explorer.query.order.prefix = "";
                    this.explorer.query.order.order_column = "";
                }
            });

            // initialize selected column
            var temp_column = this.explorer.query.columns.filter((column) => {
                if(column.prefix == this.explorer.selected_prefix &&
                   column.column_name == this.explorer.selected_column) 
                    return column;
            });
            
            if(temp_column.length > 0) {
                this.explorer.selected_alias = temp_column[0]["alias"];
                this.explorer.selected_sql_definition = temp_column[0]["sql_definition"];
                this.explorer.selected_is_aggregate = temp_column[0]["is_aggregate"];
                this.explorer.selected_column_data_type = temp_column[0]["data_type"];
            }

            // initialize order column
            var temp_order_column = this.explorer.query.columns.filter((column) => {
                if(column.prefx == this.explorer.query.order.prefix &&
                   column.column_name == this.explorer.query.order.order_column) 
                    return column;
            });

            if(temp_order_column.length > 0) {
                this.explorer.query.order.sql_definition = temp_order_column[0]["sql_definition"];
            }

            // initialize filters
            this.explorer.query.filters = this.explorer.query.filters.filter((filter) => {
                var new_column = this.explorer.query.columns.filter((column) => {
                    if(filter.column == column.column_name) {
                        return column;
                    }
                });
                if(new_column.length > 0) {
                    filter.alias = new_column[0].alias;
                }
                return filter;
            });
        },
        getColumnByNameAndPrefix(name, prefix) {
            for(var i = 0; i < this.explorer.query.columns.length; i++) {
                var column = this.explorer.query.columns[i];
                if(column.column_name == name && column.prefix == prefix)
                    return column;
            }
            return null;
        },
        addFilter(value, source, operator) {
            var column = JSON.parse(JSON.stringify(this.explorer.selected_column));
            var prefix = JSON.parse(JSON.stringify(this.explorer.selected_prefix));
            if (value == null) {
                value = 'null';
            }
            this.explorer.query.filters.push({
                column: this.explorer.selected_column,
                data_type: this.explorer.selected_column_data_type,
                value: value,
                operator: operator,
                from: source,
                prefix: this.explorer.selected_prefix,
                alias: this.explorer.selected_alias,
                sql_definition: this.explorer.selected_sql_definition,
                is_aggregate: this.explorer.selected_is_aggregate
            });
            if (this.edit_mode == true) {
                this.edit_mode = false;
            }
            this.explorer.sent_column = '';
            this.explorer.offset = 0;
            this.explorer.page_num = 1;
            app.getRecords({ success: () => {
                this.$nextTick(() => {
                    var sent_column_elem = document.getElementById('table_' + prefix + '_' + column);
                    if(sent_column_elem != null) {
                        sent_column_elem.scrollIntoView({behavior: "smooth", inline: "center" });
                    }
                });

                app.closeModal();
            }, failure: () => {
                notify.danger("The filter could not be added.");
            }});
        },
        removeFilter(index, reload) {
            this.explorer.query.filters.splice(index, 1);
            this.explorer.page_num = 1;
            if (typeof(reload) == 'undefined') {
                reload = true;
            }
            if (reload) {
                app.getRecords();
            }
        },
        editFilter(index) {
            this.edit_mode = false;
            this.modals.longest = false;
            this.modals.counts = false;
            this.modals.custom_filter = false;
            this.selected_filter = this.explorer.query.filters[index];
            this.selected_filter.index = index;
            if (this.explorer.selected_prefix + this.explorer.selected_column != this.selected_filter.prefix + this.selected_filter.column) {
                this.saveColumnStateForFilter();
            }
            this.explorer.selected_column = this.selected_filter.column;
            this.explorer.selected_column_data_type = this.selected_filter.data_type;
            this.explorer.selected_prefix = this.selected_filter.prefix;
            this.explorer.selected_alias = this.selected_filter.alias;
            this.explorer.selected_sql_definition = this.selected_filter.sql_definition;
            this.explorer.selected_is_aggregate = this.selected_filter.is_aggregate;
            this.edit_mode = true;

            if (this.selected_filter.from == 'longest') {
                this.modals.longest = true;
                openModal('#modal-longest');
            }
            else if (this.selected_filter.from == 'Top 25') {
                this.modals.counts = 25;
                openModal('#modal-counts');
            }
            else if (this.selected_filter.from == 'Top 100') {
                this.modals.counts = 100;
                openModal('#modal-counts');
            }
            else if (this.selected_filter.from == 'Top 250') {
                this.modals.counts = 250;
                openModal('#modal-counts');
            }
            else if (this.selected_filter.from == 'custom') {
                this.modals.custom_filter = true;
            }
        },
        saveColumnStateForFilter() {
            if (this.explorer.selected_column == "") {
                this.temp_selected_allow_clear = true;
            }
            else {
                this.temp_selected_allow_clear = false;
            }
            this.temp_selected_column = this.explorer.selected_column;
            this.temp_selected_prefix = this.explorer.selected_prefix;
            this.temp_selected_column_data_type = this.explorer.selected_column_data_type;
            this.temp_selected_alias = this.explorer.selected_alias;
            this.temp_selected_sql_definition = this.explorer.selected_sql_definition;
            this.temp_selected_is_aggregate = this.explorer.selected_is_aggregate;
        },
        restoreColumnStateForFilter() {
            if (this.temp_selected_column != "" || (this.temp_selected_column == "" && this.temp_selected_allow_clear)) {
                this.explorer.selected_column = this.temp_selected_column;
                this.explorer.selected_prefix = this.temp_selected_prefix;
                this.explorer.selected_column_data_type = this.temp_selected_column_data_type;
                this.explorer.selected_alias = this.temp_selected_alias;
                this.explorer.selected_sql_definition = this.temp_selected_sql_definition;
                this.explorer.selected_is_aggregate = this.temp_selected_is_aggregate;
            }
            this.temp_selected_allow_clear = false;
            this.temp_selected_column = "";
            this.temp_selected_prefix = "";
            this.temp_selected_column_data_type = "";
            this.temp_selected_alias = "";
            this.temp_selected_sql_definition = "";
            this.temp_selected_is_aggregate = false;
        },
        checkIfStudioView() {
            fetch(`${baseUrl}/internal-api/v1/studio/projects/${this.control_id}/tables/${this.explorer.query.schema}/${this.explorer.query.table}/meta`)
                .then(response => response.json())
                .then(json => {
                    if(json.status == "ok"){
                        this.explorer.view_mode = 'edit';
                        this.explorer.view = json.data;
                    }
                    else {
                        this.explorer.view_mode = '';
                        this.explorer.view = null;
                    }
                });
        },
        updatePrefix(old_prefix, new_prefix, update_primary_prefix = true) {
            for (const [key, value] of Object.entries(this.explorer.query.transformations)) {
                var transform_column = this.$refs.records.getColumnName(old_prefix, key);
                var transform_prefix = this.$refs.records.getColumnPrefix(key);

                if(transform_prefix == old_prefix && transform_prefix != new_prefix)
                {
                    Vue.delete(this.explorer.query.transformations, key);
                    Vue.set(this.explorer.query.transformations, new_prefix + "_" + transform_column, value);
                }
            }
            
            if (this.explorer.selected_prefix == old_prefix) {
                this.explorer.selected_prefix = new_prefix;
                this.$refs.records.column = new_prefix + '_' + this.explorer.selected_column;
            }

            for (var i=0; i < this.explorer.prefix_list.length; i++) {
                if (this.explorer.prefix_list[i] == old_prefix) {
                    this.explorer.prefix_list[i] = new_prefix
                }
            };

            this.explorer.query.joins.forEach((join) => {
                if (join.prefix == old_prefix) {
                    join.prefix = new_prefix;
                }
                if (join.source_prefix == old_prefix) {
                    join.source_prefix = new_prefix;
                    join.source_target_column = join.source_prefix + '_' + join.source_column
                }
            });

            this.explorer.query.columns.forEach((column) => {
                if (column.prefix == old_prefix) {
                    column.prefix = new_prefix;
                }
            });

            this.explorer.query.filters.forEach((filter) => {
                if (filter.prefix == old_prefix) {
                    filter.prefix = new_prefix;
                }
            });

            if (this.explorer.query.order.prefix == old_prefix) {
                this.explorer.query.order.prefix = new_prefix;
            }

            if (update_primary_prefix) {
                this.explorer.query.prefix = new_prefix;
            }
        },
        updateAlias(prefix, column, alias) {
            if (this.explorer.selected_prefix == prefix && this.explorer.selected_column == column) {
                this.explorer.selected_alias = alias;
            }
        },
        checkForError(json) {
            this.ready = true;
            if(json.status == "error") {
                notify.danger(json.message);
                return false;
            }
            return true;
        },
        pivot_explorer() {
            if (localStorage.getItem("pivoted") == "false") {
                localStorage.setItem("pivoted", "true");
            }
            else {
                localStorage.setItem("pivoted", "false");
            }
            this.explorer.pivoted = !this.explorer.pivoted;
        },
        checkType(column_name, type) {
            var type = this.explorer.query.columns.filter(function(column) { 
                if(column.target_column_name == column_name && column.data_type == type) {
                    return column;
                }
            });

            if(type.length > 0)
                return true;
            else 
                return false;
        },
        isSelectedColumnAggregate() {
            if(this.explorer.selected_is_aggregate == true || this.explorer.selected_prefix == 'aggregate')
                return true;
            else
                return false;
        },
        canAddTransformationToColumn() {
            // No JSON grouping
            if(this.explorer.selected_column_unstructured == true) {
                return false;
            } 
            
            // Don't group on bool
            if(this.explorer.selected_column_data_type == 'boolean') {
                return false;
            }
            
            // Do not allow custom columns except the count(*) column
            if(this.explorer.selected_prefix == 'custom' || (this.isSelectedColumnAggregate() && this.$root.explorer.query.is_grouped == false)) {
                return false;
            }

            let currentColumnFilters = this.explorer.query.filters.filter((filter) => {
                return filter.column == this.explorer.selected_column;
            });

            // Do we have a numeric column selected w/a numeric only filter applied?
            let numericTypes = ['integer', 'decimal', 'bigint', 'numeric', 'float'];
            if (numericTypes.indexOf(this.explorer.selected_column_data_type) > -1) {
                let numericOperators = [
                    '>=',
                    '>',
                    '<',
                    '<=',
                    'between'
                ];

                if(currentColumnFilters.filter((f) => numericOperators.indexOf(f.operator) > -1).length > 0){
                    return false;
                }
            }

            // Are we applying date specific filtering?
            let dateTypes = ['date', 'timestamp without time zone', 'timestamp with time zone'];
            if(dateTypes.indexOf(this.explorer.selected_column_data_type) > -1) {
                let dateOperators = [
                    '>=',
                    '>',
                    '<',
                    '<=',
                    'between'
                ];

                if(currentColumnFilters.filter((f) => dateOperators.indexOf(f.operator) > -1).length > 0){
                    return false;
                }
            }

            return true;
        },
        applyJoins() {
            if (this.explorer.viewing_type == "Join") {
                for(var i=0; i<this.explorer.query.joins.length; i++) {
                    this.explorer.query.joins[i].applied = true;
                }
            }
        },
        unapplyJoins() {
            for(var i=0; i<this.explorer.query.joins.length; i++) {
                this.explorer.query.joins[i].applied = false;
            }
        },
        clearSelectedColumn() {
            this.explorer.selected_column = "";
            this.explorer.selected_column_data_type = "";
            this.explorer.selected_column_index = 0;
            this.explorer.selected_column_unstructured = false;
            this.explorer.selected_prefix = "";
            this.explorer.selected_alias = "";
            this.explorer.selected_sql_definition = "";
            this.explorer.selected_is_aggregate = false;
        },
        manageSentData(data) {
            try {
                this.explorer.valid_query = JSON.parse(data.query);
                this.explorer.query = JSON.parse(data.query);
            }
            catch($e) {
                this.explorer.valid_query = data.query;
                this.explorer.query = data.query;
            }

            if(this.explorer.query.transformations.length == 0) {
                this.explorer.query.transformations = {};
            }

            this.explorer.send_columns = true;
            if(this.explorer.query.joins.length > 0) {
                this.explorer.viewing_type = "Join";
            }
        },
        rebuild() {
            this.clearSelectedColumn();
            if(saved_query.id != -1) {
                this.manageSentData(saved_query);
                this.explorer.saved_query = { 
                    "id": saved_query.id,
                    "name": saved_query.name,
                    "description": saved_query.description
                }
                saved_query = { id: -1 };
            } 
            else if(publisher_data.id != -1) {
                this.manageSentData(publisher_data.destination_options);
                this.explorer.publisher = publisher_data;
            }
            else {
                this.explorer.viewing_type = "Standard";
                this.explorer.query.joins = [];
                this.explorer.query.filters = [];
                this.explorer.query.transformations = {};
                this.explorer.query.order = {
                    order_column: "",
                    order_type: "desc",
                    prefix: ""
                };
                this.explorer.send_columns = false;
                if (this.$refs.records.$refs.table_summary != undefined) {
                    this.$refs.records.$refs.table_summary.reset();
                }
            }

            this.explorer.view = {};
            this.explorer.view_mode = '';
            this.getCurrentUser();
            this.getActiveUsers();
            this.getProjectUsers();
            this.getAllPermissions();
            this.checkIfStudioView();
            this.getPublishingDestinations();
            this.rebuildTable();
        },
        rebuildTable(update_query = true) {
            this.getBreadcrumbs();
            this.getProjectTableColumns(() => {
                this.getRecords({ 
                    success: () => {
                        if(update_query) {
                            this.updateSavedQuery();
                        }
                    }
                });
            });
            this.getTables();
            this.getTables(true);
            this.getProjectMappingModules();
            this.getFlags();
            this.getMappings();
            this.getComments();
            this.getAttachments();
            this.getTableNotes();
        },
        selectTable(table, schema = 'public', rebuild = true) {
            if(table == this.table_name && schema == this.schema) {
                return;
            }
            this.explorer.query.table = table;
            this.explorer.query.prefix = table;
            this.explorer.query.schema = schema;
            this.clearSelectedColumn();
            window.history.pushState(
                '',
                table,
                `/studio/projects/${this.control_id}/tables/${schema}/${table}`
            );

            if(rebuild) {
                this.rebuild();
            }
        },
        returnToView(rebuild_all = false) {
            if(! confirm('Are you sure you want to cancel editing?')) {
                return;
            }

            this.ready = false;

            this.explorer.query = this.explorer.origin_query;
            this.explorer.origin_query = {};

            if (this.explorer.query.joins.length > 0) {
                this.explorer.viewing_type = "Join";
            }
            else {
                this.explorer.viewing_type = "Standard";
            }

            this.selectTable(this.explorer.view.view_name, this.explorer.view.view_schema, false);
            this.explorer.view = {};
            this.explorer.view_mode = '';

            if (rebuild_all) {
                this.rebuild();
            } else {
                this.checkIfStudioView();
                this.rebuildTable();
            }
        },
        endPreview() {
            this.ready = false;

            this.explorer.query = this.explorer.origin_query;
            this.explorer.origin_query = {};

            if (this.explorer.query.joins.length == 0) {
                this.explorer.viewing_type = "Standard";
            } else {
                this.explorer.viewing_type = "Join";
            }

            this.getRecords();
        },
        updateSavedQuery() {
            this.explorer.validation_queries.saved_query = this.explorer.validation_queries.current_query;
        }
    },
    watch: {
        ready(status) {
            if(status == true) {
                setTimeout(function() {
                    if(app.ready == true) {
                        $(".loader").hide();
                    }
                }, 500);
            }
            else {
                $(".loader").show();
            }
        },
        loaded: {
            deep: true,
            handler() {
                if (this.loaded.columns === true && this.loaded.records === true) {
                    if (this.explorer.query.joins.length > 0) {
                        this.applyJoins();
                    }
                }
            }
        }
    },
    mounted() {
        this.table_exists = table_exists;
        this.rebuild();
    },
    updated() {
        setTableStyles();
    },
    beforeDestroy() {
        clearInterval(this.pollingForTables);
    }
});

window.addEventListener('beforeunload', function (e) {
    if(app.explorer.validation_queries.saved_query != app.explorer.validation_queries.current_query) {
        e.preventDefault();
        // These being populated is for older browsers newer browsers do not support custom messages
        e.returnValue = 'The changes you have made have not been saved or published and will be discarded';
        return 'The changes you have made have not been saved or published and will be discarded';
    } else {
        return undefined;
    }
});