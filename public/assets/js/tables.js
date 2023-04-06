/** Begin components */
Vue.component('v-select', VueSelect.VueSelect);
Vue.component('multiselect', window.VueMultiselect.default);

var modals = [];

/** Vue.js Component
 *******************************************************************************
 * unstructured-stage
 *******************************************************************************
 * Renders the table-based UX for unstrucutred data using recursive object
 * and array detection.
 */

var unstructured_stage = {
    template: "#unstructured-stage",
    name: 'unstructured-stage',
    props: [
        'val',
        'selected_column',
        'selected_column_index',
        'flags',
        'comments',
        'mappings',
        'attachments',
        'prefix',
        'table_name',
        'sql_definition',
        'column_name'
    ],
    methods: {
        /** Vue.js Method
         *******************************************************************************
         * isJson
         * Params:
         * * str            string      A string to attempt to parse as JSON.
         *******************************************************************************
         * Attempts to parse a string into a JSON object.
         */
        isJson(str) {
            try {
                var check = JSON.parse(str);
                if(Number.isInteger(str) || !isNaN(str) || str == '' || str == null || check == null || check.length == 0)
                {
                    return false;
                }
                else if (check && typeof check === "object") {
                    return true;
                }
                else {
                    return false;
                }
            }
            catch (e) {
                try {
                    check = JSON.parse(JSON.stringify(str));

                    if (check && typeof check === "object") {
                        return true;
                    }
                }
                catch (e2) {
                    return false;
                }
                return false;
            }
        },
        getType(str) {
            if (this.isJson(str))
                return 'json';
            else
                return 'character varying';
        },
        /** Vue.js Method
         *******************************************************************************
         * returnJson
         * Params:
         * * str            string      A JSON string that should become a JS object.
         *******************************************************************************
         * Accepts a string and returns the JSON object equivelant.
         */
        returnJson(str) {
            try {
                return JSON.parse(str);
            }
            catch (e) {
                return JSON.parse(JSON.stringify(str));
            }
        },
        setCol(selected_column = "", col, base_col, val, sql_definition) {
            var orig_col = col;
            var separator = '';

            if(val == null || Number.isInteger(val) || typeof(val) == "string") {
                separator = "->>";
            }
            else {
                separator = "->";
            }

            if(isNaN(col)) {
                col = `'${col}'`;
            }

            if(!selected_column.includes(base_col)) {
                selected_column = `"${base_col}"`;
            }

            var sql_definition = '';

            if (sql_definition !== undefined) {
                sql_definition = sql_definition + separator + col;
            }

            selected_column = selected_column + separator + col;

            return [ orig_col, selected_column, sql_definition ];
        },
        getSqlDef(sql_definition, col, val) {
            if(sql_definition == "")
                return "";
               
            let separator = "";
            if(val == null || Number.isInteger(val) || typeof(val) == "string") {
                separator = "->>";
            }
            else {
                separator = "->";
            }

            if(isNaN(col)) {
                col = `'${col}'`;
            }

            sql_definition = sql_definition + separator + col;

            return sql_definition;
        },
        checkWhere(col) {
            var matches = app.explorer.query.filters.filter(function(filter){ 
                var filter_column = filter.prefix + "." + filter.column.replace(' ', '');
                if(filter_column == col.replace(' ', '')){
                    return filter;
                }
            });

            if(matches.length > 0 || this.$root.explorer.sent_column.replace(' ', '') == col.replace(' ', '').replace(/'/g, ''))
                return true;
            else
                return false;
        }
    }
}

/** Vue.js Component
 *******************************************************************************
 * join-manager
 *******************************************************************************
 * Renders a modal that allows users to specify joins to mutate the data
 */
modals.join_manager = {
    template: '#join-modal-template',
    props: [ 'open', 'control_id', 'tables', 'table', 'columns', 'viewing_type' ],
    data: function() {
        return {
            prefix: '',
            edit_prefix: false,
            target_columns: [],
            joins: []
        }
    },
    computed: {
        prefix_changed() {
            if (this.$root.explorer.query.prefix != this.prefix) {
                return true;
            }
            else {
                return false;
            }
        },
        pending_changes() {
            for(var i = 0; i < this.joins.length; i++) {
                if (this.joins[i].editing == true) {
                    return true;
                }
            }
            return false;
        }
    },
    watch: {
        open() {
            if(this.open == true) {
                this.hideShowArrows();
                this.loadJoinsFromQuery();
                this.prefix = this.$root.explorer.query.prefix;
                this.edit_prefix = false;
                this.target_columns = [];
                openModal('#modal-join', (event) => {
                    if(event != undefined) {
                        event.stopPropagation();
                    }

                    // Code to run before closing:
                    // (ensure all joins are saved)
                    if(this.discardChanges() == false) {
                        return true;
                    }

                    // End of code to run before closing
                    app.modals.join_manager = false;
                    return false;
                });
                $(document).on("mousedown", "#dmiux_body", this.modalCloseJoin);
                $(document).off('keydown', closeModalOnEscape);
                $(document).on("keydown", this.modalCloseJoin);
            }
        }
    },
    methods: {
        loadJoinsFromQuery() {
            this.joins = JSON.parse(JSON.stringify(this.$root.explorer.query.joins));
            this.joins.forEach((join) => {
                join.original_prefix = join.prefix;
                join.source_columns = this.getSourceColumns(join, true);
            });
        },
        getOriginalJoin(join) {
            for(var i=0; i < this.$root.explorer.query.joins.length; i++) {
                if(this.$root.explorer.query.joins[i].uuid == join.uuid) { 
                    orig_join = this.$root.explorer.query.joins[i];
                    orig_join.index = i;
                    return orig_join;
                }
            }
            return null;
        },
        getSourceColumns(join, loading = false) {
            let original_join = null;
            if (!loading) {
                original_join = this.getOriginalJoin(join);
            }
            let columns = this.columns.filter(column => {
                if (column.prefix != join.prefix && (original_join == null || column.prefix != original_join.prefix)) {
                    return column;
                }
            });
            return columns;
        },
        getSelectedTableColumns(index) {
            this.$root.ready = false;
            this.joins[index].schema = this.joins[index].schema_table.split('.')[0];
            this.joins[index].table = this.joins[index].schema_table.split('.')[1];
            this.joins[index].prefix = this.joins[index].table;
            this.joins[index].new = true;
            let join = this.joins[index];
            let suffix = 0;
            while (this.$root.explorer.prefix_list.includes(join.prefix)) {
                if(join.table.length > 3)
                    join.prefix = join.table.substring(0, 3) + suffix;
                else 
                    join.prefix = join.table + suffix;
                suffix++;
            }

            join.source_columns = this.getSourceColumns(join);
            join.original_prefix = join.prefix;

            fetch(`${baseUrl}/internal-api/v1/studio/projects/${app.control_id}/tables/${this.joins[index].schema}/${this.joins[index].table}/table-columns`)
                .then(response => response.json())
                .then(json => {
                    this.joins[index].target_columns = json.data;
                    this.$root.ready = true;
                })
        },
        addJoin() {
            if (this.prefix_changed) {
                notify.danger("You must apply the alias change for primary table before adding another join.");
                return;
            }
            var join = {
                'schema_table': '',
                'table': '',
                'type': '',
                'original_prefix': '',
                'prefix': '',
                'source_target_column': '',
                'source_prefix': '',
                'source_column': '',
                'target_column': '',
                'cast': false,
                'cast_type': '',
                'join_type': 'INNER',
                'source_columns' : [],
                'target_columns' : [],
                'editing': true,
                'new': true,
                'applied': false,
                'is_custom': false,
                'custom_column_name': '',
                'uuid': this.uuid()
            };
            this.joins.push(join);
            var showIndex = this.joins.length-1;
            this.$nextTick(() => {
                var element = document.getElementById("join_" + showIndex);
                if (element) {
                    element.scrollIntoView();
                }
            });
        },
        editJoin(index) {
            this.joins[index].editing = true;
        },
        setSourceColumnAndPrefix(e, index) {
            var select = e.target;
            this.joins[index].source_prefix = select.options[select.selectedIndex].dataset.prefix;
            this.joins[index].source_column = select.options[select.selectedIndex].dataset.column_name;
        },
        updateSourcePrefix(old_prefix, new_prefix) {
            this.joins.forEach(join => {
                if (join.source_prefix == old_prefix) {
                    join.source_prefix = new_prefix;
                    join.source_target_column = join.source_prefix + '_' + join.source_column;
                }
            });
        },
        editPrefix() {
            if (this.pending_changes) {
                notify.danger("You must save all joins to change this alias.");
                return;
            }
            this.edit_prefix = true;
        },
        cancelPrefixChange() {
            if (confirm("Are you sure you want to discard any changes?")) {
                this.prefix = app.explorer.query.prefix;
                this.edit_prefix = false;
            }
        },
        applyPrefixChange() {
            var previous_prefix = null;
            previous_prefix = app.explorer.query.prefix;
            if (this.prefix == "") {
                this.prefix = app.explorer.query.table;
                notify.info(`Alias for table set to '${this.prefix}'.`)
            }

            if(previous_prefix != this.prefix) {
                if (this.$root.explorer.prefix_list.includes(this.prefix)) {
                    notify.danger("The alias entered already exists. Please enter a new alias for the table.");
                    return;
                }
                app.updatePrefix(previous_prefix, this.prefix);
            }
            
            this.$root.ready = false;
            this.$root.getProjectTableColumns(() => {
                this.updateJoinColumnNames();
                this.$root.getRecords({ 
                    success: () => {
                        Vue.nextTick(() => {
                            this.loadJoinsFromQuery();
                            this.edit_prefix = false;
                        });
                    },
                    failure: () => {
                        notify.danger("Applying your new alias has failed.");
                    }
                });
            }, null, previous_prefix);
        },
        reloadJoinTableColumns(callback, previous_prefix) {
            this.$root.ready = false;
            this.$root.getProjectTableColumns({
                success: () => {
                    this.$root.ready = true;
                    this.updateJoinColumnNames();
                    if (typeof(callback.success) != 'undefined') {
                        callback.success();
                    }
                },
                failure: () => {
                    this.$root.ready = true;
                    if (typeof(callback.failure) != 'undefined') {
                        callback.failure();
                    }
                }
            }, null, previous_prefix, 10000);
        },
        manageJoin(index, type) {
            if (this.prefix_changed && type == 'delete') {
                notify.danger("You must apply the alias change for primary table before deleting this join.");
                return;
            }
            var join = this.joins[index];
            var orig_join = this.getOriginalJoin(join);
            if (orig_join == null) {
                orig_join = { index: -1, prefix: "" };
            }

            if(type == "save") {
                if (join.prefix == "" || join.table == "" || join.source_column == "" || join.target_column == "") {
                    notify.danger("Please enter in all required values.");
                    return;
                }

                if(join.cast == true && join.cast_type == "") {
                    notify.danger("Please select a type for the cast.");
                    return;
                }

                if (join.prefix != join.original_prefix) {
                    join.original_prefix = join.prefix;                
                }

                let orig_join_prefix = orig_join.prefix;
                let join_prefix = join.prefix;
                let check_reloading = null;
                if (orig_join_prefix != join_prefix) {
                    if (this.$root.explorer.prefix_list.includes(join_prefix)) {
                        notify.danger("The alias entered already exists. Please enter a new alias for the join.");
                        return;
                    }
                    if (orig_join.index == -1) {
                        this.$root.explorer.prefix_list.push(join_prefix);
                    }
                    else {
                        this.$root.updatePrefix(orig_join_prefix, join_prefix, false);
                    }
                    check_reloading = () => {
                        return new Promise((resolve, reject) => {
                            this.reloadJoinTableColumns({ 
                                success: () => {
                                    this.updateSourcePrefix(orig_join_prefix, join_prefix);
                                    resolve();
                                },
                                failure: () => {
                                    notify.danger("Applying your new alias has failed.");
                                    this.$root.explorer.prefix_list = this.$root.explorer.valid_prefix_list;
                                    reject();
                                }
                            }, orig_join_prefix);
                        });
                    };
                }
                else {
                    check_reloading = () => {
                        return new Promise(resolve => resolve())
                    };
                }

                check_reloading()
                    .then(() => {
                        var source_column = this.columns.filter(function(column) {
                            if((join.source_prefix == column.prefix && join.source_column == column.column_name) || join.source_column == column.sql_definition)
                                return column;
                        });

                        var target_column = join.target_columns.filter(function(column) {
                            if(join.target_column == column.column_name)
                                return column;
                        });

                        if((target_column[0].data_type != source_column[0].data_type) && join.cast == false) {
                            notify.danger("Join has mismatching column types. Try applying a cast.");
                            return;
                        }

                        this.$root.ready = false;

                        if(source_column[0].prefix == "custom") {
                            join.is_custom = true;
                            join.custom_column_name = source_column[0].column_name;
                        }

                        join.applied = false;
                        join.index = index;
                        var join_copy = JSON.parse(JSON.stringify(join));
                        if(orig_join.index == -1) {
                            this.$root.explorer.query.joins.push(join_copy);
                        }
                        else {
                            this.$root.explorer.query.joins[orig_join.index] = join_copy;
                        }
                        this.reloadRecords();
                    });
            }
            else if (type == "delete") {
                if (this.prefixInUsedInJoin(this.joins[index].prefix)) {
                    notify.danger("Table is used by another join. Remove or change dependent join first.");
                    return;
                }
                if(!confirm("Join deletions are applied immediately and cannot be undone. Are you sure you want to delete this join?")) {
                    return;
                }

                if (!this.joins[index].applied) {
                    this.joins.splice(index, 1);
                    return;
                }

                this.$root.ready = false;
                var prefix_index = this.$root.explorer.prefix_list.indexOf(this.joins[index].prefix);
                if(prefix_index != -1) {
                    this.$root.explorer.prefix_list.splice(prefix_index, 1);
                }

                this.joins.splice(index, 1);
                this.$root.explorer.query.joins.splice(orig_join.index, 1);
                this.reloadRecords();
            }
        },
        reloadRecords() {
            var prefix = this.$root.explorer.query.prefix;
            if (this.$root.explorer.query.joins.length == 0) {
                if (this.$root.explorer.query.order.prefix != this.$root.explorer.query.prefix) {
                    this.$root.explorer.query.order.prefix = '';
                    this.$root.explorer.query.order.order_column = '';
                    this.$root.explorer.query.order.order_type = 'desc';
                }
                let current_filters = JSON.parse(JSON.stringify(this.$root.explorer.query.filters));
                this.$root.explorer.query.filters = current_filters.filter((filter) => {
                    if(filter.prefix == this.$root.explorer.query.prefix)
                        return filter;
                });
                this.$root.explorer.viewing_type = "Standard";
            }
            else {
                this.$root.explorer.viewing_type = "Join";
            }
            
            this.$root.explorer.offset = 0;
            this.$root.explorer.page_num = 1;
            this.$root.loaded.all_columns = false;
            this.$root.loaded.columns = false;
            this.$root.loaded.records = false;
            this.$root.getProjectTableColumns(() => {
                this.updateJoinColumnNames();
                this.$root.getRecords({ 
                    success: () => {
                        this.joins.forEach(join => {
                            join.new = false;
                            join.editing = false;
                            join.applied = true;
                        });

                        this.$root.explorer.query.joins.forEach(join => {
                            join.new = false;
                            join.editing = false;
                            join.applied = true;
                        });

                        this.$root.explorer.valid_query = JSON.parse(JSON.stringify(this.$root.explorer.query));
                    },
                    failure: () => {
                        if (this.$root.explorer.valid_query.joins.length == 0) {
                            this.$root.explorer.viewing_type = "Standard";
                        } else {
                            this.$root.explorer.viewing_type = "Join";
                        }
                        notify.danger("Your joins have failed. This may be due to custom columns you have set.");
                    }
                });
            }, null, prefix);
        },
        updateJoinColumnNames() {
            for (var i = 0; i < this.$root.explorer.query.joins.length; i++) { 
                var join = this.$root.explorer.query.joins[i];
                
                if(join.is_custom == true) {
                    var source_column = this.$root.explorer.query.columns.filter(function(column) {
                        if(join.custom_column_name == column.column_name)
                            return column;
                    });

                    if(source_column.length > 0) {
                        join.source_column = source_column[0].sql_definition;
                    }
                }
            }
        },
        prefixInUsedInJoin(prefix) {
            if (prefix.trim() === '') {
                return false;
            }
            for(var i=0; i<this.joins.length; i++) {
                if (this.joins[i].source_prefix == prefix) {
                    return true;
                }
            }
            return false;
        },
        hideShowArrows() {
            BytespreeUiHelper.hideShowArrows("joins_list", "join_");
        },
        modalCloseJoin(event) {
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
                        if (clicked_element.id != "button-close_join_manager" && clicked_element.id != "button-cancel_join_manager")
                            return;
                    }
                    else if(clicked_element.closest(".notyf") || clicked_element.closest("#loading")) {
                        return;
                    }
                    else {
                        if(clicked_element.id == "modal-join")
                            return;
                    }
                }
            }
            // You either clicked outside the modal, or the X Button, or the Cancel Button - modal will close

            // Code to run before closing:
            // (ensure all joins are saved)
            if(this.discardChanges() == false) {
                return;
            }

            // End of code to run before closing
            app.modals.join_manager = false;
            $(document).off("mousedown", "#dmiux_body", this.modalCloseJoin);
            $(document).off("keydown", this.modalCloseJoin);
            $(document).on('keydown', closeModalOnEscape);
            closeModal('#modal-join');
        },
        discardChanges() {
            const message = "You have unsaved changes. All changes will discarded. Continue?";
            if (this.prefix_changed) {
                if (confirm(message)) {
                    return true;
                }
                return false;
            }
            for(var i = 0; i < this.joins.length; i++) {
                if (this.joins[i].editing == true) {
                    if (confirm(message) == false) {
                        var join_elem = document.getElementById('join_' + i);
                        if(join_elem != null) {
                            join_elem.scrollIntoView({behavior: "smooth"});
                        }
                        return false;
                    }
                }
            }

            return true;
        },
        uuid() {
            return ([1e7]+-1e3+-4e3+-8e3+-1e11).replace(/[018]/g, c =>
                (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16)
            );
        }
    },
    updated() {
        this.$nextTick().then(this.hideShowArrows());
    }
}

/** Vue.js Component
 *******************************************************************************
 * add-comment
 *******************************************************************************
 * Renders a modal that displays the comment history and the form for adding
 * a new comment.
 */
modals.add_comment = {
    template: '#comment-modal-template',
    props: [
        'open',
        'user',
        'control_id',
        'table',
        'selected_column',
        'comments'
    ],
    data: function() {
        return {
            columnComments: [],
            comment: "",
            closeConfig: {
                closeButtonId:  'button-close_add_comment',
                cancelButtonId: 'button-cancel_add_comment',
            }
        }
    },
    watch: {
        open() {
            if(this.open == true) {
                ModalHelper.open('modal-add_comment', this.modalClose);
            }
            else {
                ModalHelper.close('modal-add_comment', this.modalClose);
            }
        },
        selected_column() {
            this.columnComments = this.comments[this.table + "_" + this.selected_column];
        },
        comments() {
            this.columnComments = this.comments[this.table + "_" + this.selected_column];
            this.comment = "";
        }
    },
    methods: {
        addComment(override = false) {
            if(!app.isColumnSelected()) { return }

            if(this.comment == null || this.comment == "") {
                alert("You must enter a comment.");
                return false;
            }

            let options = FetchHelper.buildJsonRequest({
                comment_text: this.comment,
                column: this.selected_column,
                override: false
            });

            this.$root.ready = false;
            fetch(`${baseUrl}/internal-api/v1/studio/projects/${this.control_id}/tables/${this.$parent.explorer.valid_query.schema}/${this.$parent.explorer.valid_query.table}/comments`, options)
                .then(FetchHelper.handleJsonResponse)
                .then(resp => {
                    this.$root.ready = true;
                    if(resp.status == 'error') {
                        let message = resp.message;
                        if (resp.data.invalid_handles !== undefined) {
                            let words = (resp.data.invalid_handles.length == 1) ? ['handle', 'is'] : ['handles', 'are'];
                            message = message + `. The invalid ${words[0]} ${words[1]} listed below.\n\n`;
                            resp.data.invalid_handles.forEach(handle => {
                                message = message + `- ${handle}\n`; 
                            });
                            message = message + '\nDo you want to add this comment anyway?';
                            if (confirm(message)) {
                                this.addComment(true);
                            }
                            return;
                        }
                        else if (resp.data.send_failures !== undefined) {
                            let words = (resp.data.send_failures.length == 1) ? ['user', 'is'] : ['users', 'are'];
                            message = message + `. The ${words[0]} we failed to contact ${words[1]} listed below.\n\n`;
                            resp.data.send_failures.forEach(name => {
                                message = message + `- ${name}\n`; 
                            });
                            alert(message);                                                   
                        }
                    }
                    this.comment = "";
                    notify.success(`You added a comment on ${this.selected_column}.`);
                    this.$root.getComments();
                    this.$root.modals.add_comment = false;
                })
                .catch((error) => {
                    this.$root.ready = true;
                    ResponseHelper.handleErrorMessage(error, 'A problem occurred while saving the comment.');
                });
        },
        discardChanges() {
            const message = "You have unsaved changes. All changes will discarded. Continue?";
            if (this.comment != '') {
                if (confirm(message)) {
                    this.comment = "";
                    return true;
                }
                return false;
            }
            return true;
        },
        modalClose(event) {
            if(ModalHelper.shouldClose(event, this.closeConfig) && this.discardChanges()) {
                this.$root.modals.add_comment = false;
            }
        },
    }
}

/** Vue.js Component
 *******************************************************************************
 * add-attachment
 *******************************************************************************
 * Renders a modal that displays the run form for viewing existing attachments
 * and adding new ones.
 */
modals.add_attachment = {
    template: '#attachment-modal-template',
    props: [
        'open',
        'control_id',
        'table',
        'selected_column',
        'attachments',
        'max_upload_size',
        'error_msg'
    ],
    data: function() {
        return {
            columnAttachments: []
        }
    },
    watch: {
        open() {
            if(this.open != false) {
                if(!app.isColumnSelected()) { 
                    app.modals.add_attachment = false;
                    return; 
                }
                else if(app.explorer.selected_prefix != app.explorer.query.prefix) { 
                    alert("You cannot add attachments to joined or added columns.");
                    app.modals.add_attachment = false;
                    return; 
                }
                else {
                    openModal('#modal-add_attachment');
                    this.$root.modals.add_attachment = false;
                }
            }   
        },
        selected_column() {
            this.columnAttachments = this.attachments[this.table + "_" + this.selected_column];
        },
        attachments() {
            this.columnAttachments = this.attachments[this.table + "_" + this.selected_column];
        }
    },
    methods: {
        async submitAttachment() {
            if(!app.isColumnSelected()) { return };

            var file = document.querySelector('#fileToUpload');

            if(file.files.length == 0) {
                notify.danger('Please select a file to upload.');
                return
            }

            app.ready = false;

            let token = await this.getUploadToken();

            if (token == null) {
                app.ready = true;
                notify.danger('There was a problem initializing your upload.');
            }

            let uploadData = new FormData();
            uploadData.append('file', file.files[0]);
            uploadData.append('upload_token', token);
            
            const options = {
                method: 'POST',
                body: uploadData
            }

            fetch(`${file_upload_url}/upload`, options)
                .then(response => {
                    if (response.status != 201) {
                        throw 'Your file could not be uploaded.';
                    }

                    return response.json();
                })
                .then(resp => {
                    let _options = FetchHelper.buildJsonRequest({
                        transfer_token: resp.transfer_token,
                        column: this.selected_column
                    });

                    fetch(`${baseUrl}/internal-api/v1/studio/projects/${this.control_id}/tables/${this.$parent.explorer.query.schema}/${this.table}/files`, _options) // todo 
                        .then(FetchHelper.handleJsonResponse)
                        .then(resp => {
                            app.getAttachments();
                            app.modals.add_attachment = false;
                            document.getElementById("fileToUpload").value = "";
                            app.closeModal();
                            app.ready = true;
                        })
                        .catch((err) => {
                            app.ready = true;
                            ResponseHelper.handleErrorMessage(err, 'Your file could not be uploaded.');
                        });
                })
                .catch((err) => {
                    app.ready = true;
                    ResponseHelper.handleErrorMessage(err, 'Your file could not be uploaded.');
                });
        },
        deleteAttachment(id) {
            if(confirm('Are you sure to delete this attachment')) {
                if(!app.isColumnSelected()) { return };

                this.$root.ready = false;
                fetch(`${baseUrl}/internal-api/v1/studio/projects/${this.control_id}/tables/${this.$parent.explorer.query.schema}/${this.table}/files/${id}`, FetchHelper.buildJsonRequest({}, 'delete'))
                    .then(FetchHelper.handleJsonResponse)
                    .then(resp => {
                        this.$root.ready = true;
                        app.getAttachments();
                        app.modals.add_attachment = false;
                        app.closeModal();
                    })
                    .catch((err) => {
                        app.ready = true;
                        ResponseHelper.handleErrorMessage(err, 'The attachment could not be deleted.');
                    });
            }
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
        }
    }
}

/** Vue.js Component
 *******************************************************************************
 * add-flag
 *******************************************************************************
 * Renders a modal that allows users to add a new flag and specify an assignee.
 */
modals.add_flag = {
    template: '#flag-modal-template',
    props: [
        "table_name",
        "column_name",
        "project_id",
        "curr_user",
        "users"
    ],
    data: function () {
        return {
            assigned_user: "",
            reason: ""
        }
    },
    computed: {
        actualUsers() {
            return this.users.filter(user => user['name'] != null);
        }
    },
    watch: {
        curr_user() {
            this.assigned_user = this.curr_user.user_handle;
        }
    },
    methods: {
        addFlag() {
            if(!app.isColumnSelected()) { return };

            if(this.assigned_user == "" || this.assigned_user == undefined) {
                alert("Please choose a user to assign this flag to.");
                return;
            }

            let options = FetchHelper.buildJsonRequest({
                comment_text: this.reason,
                table: this.table_name,
                column: this.column_name,
                user: this.assigned_user, 
                schema: this.$parent.explorer.valid_query.schema
            });

            this.$root.ready = false;
            fetch(`${baseUrl}/internal-api/v1/studio/projects/${this.project_id}/tables/${this.$parent.explorer.valid_query.schema}/${this.$parent.explorer.valid_query.table}/flags`, options)
                .then(FetchHelper.handleJsonResponse)
                .then(resp => {
                    this.$root.ready = true;
                    notify.success(`You added a flag to ${this.column_name}.`);
                    this.reason = "";
                    this.assigned_user = this.curr_user.user_handle;
                    app.closeModal();
                    app.getFlags();
                    app.getComments();
                })
                .catch((error) => {
                    this.$root.ready = true;
                    ResponseHelper.handleErrorMessage(error, 'A problem occurred while adding the flag.');
                });
        }
    }
}

/** Vue.js Component
 *******************************************************************************
 * add-transformation
 *******************************************************************************
 * Renders a modal that allows users to add a new column transformations.
 */
modals.add_transformation = {
    template: '#transformation-modal-template',
    props: [
        "table",
        "selected_column",
        "selected_prefix",
        "selected_alias",
        "project_id",
        "user",
        "viewing_type"
    ],
    data: function () {
        return {
            col_name: "",
            transformations: [],
            transformation_fields: {
                'FindReplace': {
                    'field_1': { 'label': 'Replace', 'value': '', 'type': 'input'},
                    'field_2': { 'label': '&nbsp;With', 'value': '', 'type': 'input'}
                },
                'IfThen': {
                    'field_1': { 'label': '&nbsp;If <strong>${label}</strong>', 'value': '=', 'type': 'select', 'select_values': 'operators'},
                    'field_2': { 'label': '<div class="pt-2 dmiux_grid-row"></div>', 'value': '', 'type': 'input'},
                    'field_3': { 'label': '&nbsp;Then <strong>${label}</strong> is', 'value': '', 'type': 'input'}
                },
                'UpperLower': {
                    'field_1': { 'label': 'Case', 'value': 'UPPER', 'type': 'select', 'select_values': {
                        'Uppercase': 'UPPER',
                        'Lowercase': 'LOWER'
                    }}
                },
                'Cast': {
                    'field_1': { 'label': 'Cast to', 'value': 'text', 'type': 'select', 'select_values': {
                        'Text': 'character varying',
                        'Currency': 'decimal(13, 2)',
                        'Json': 'jsonb',
                        'Integer': 'integer',
                        'Timestamp': 'timestamp without time zone',
                        'Numeric': 'numeric',
                        'Date': 'date'
                    }}
                },
                /*
                'ConditionalLogic':[ {
                        'if_field_1': { 'label': '&nbsp;If <strong>&nbsp;value&nbsp;</strong>', 'value': '=', 'type': 'select', 'select_values': 'operators'},
                        'if_field_2': { 'label': '', 'value': '', 'type': 'input'},
                        'if_field_3': { 'label': '&nbsp;Then <strong>&nbsp;value&nbsp;</strong> is', 'value': '', 'type': 'input'},
                        'if_field_4': { 'label': '', 'value': '', 'type': 'drag'},
                        'if_field_5': { 'label': '', 'value': '', 'type': 'remove'} 
                    }
                ],
                */
            },
            types: {
                "Find and Replace": "FindReplace",
                "If/Then": "IfThen",
                "Change Case": "UpperLower",
                "Cast to Type": "Cast",
                //"Conditional Logic":"ConditionalLogic"
            },
            operators: {
                'is equal to': '=',
                'is not equal to': '!=',
                'is like': 'ilike',
                'is not like': 'not ilike',
                'is greater than': '>',
                'is less than': '<',
                'is greater than or equal to': '>=',
                'is less than or equal to': '<='
            }
        }
    },
    watch: {
        table() {
            this.transformations = [];
        },
        selected_prefix() {
            this.changedColumnName();
        },
        selected_column() {
            this.changedColumnName();
        },
        selected_alias() {
            this.changedColumnName();
        }
    },
    computed:{
        transformation_button(){
            if(this.transformations.filter((transformation) => transformation.status != 'deleted').length > 0 ){
                return '+ Add Another Transformation';
            }else{
                return '+ Add Transformation';
            }
        }
    },
    methods: {
        setOperators() {
            var numeric_types = [
                'numeric',
                'integer',
                'serial',
                'bigserial',
                'bigint',
                'decimal',
                'float'
            ];

            if(!numeric_types.includes(this.$root.explorer.selected_column_data_type)) {
                this.operators = {
                    'is equal to': '=',
                    'is not equal to': '!=',
                    'is like': 'ilike',
                    'is not like': 'not ilike'
                };
            }
            else {
                this.operators = {
                    'is equal to': '=',
                    'is not equal to': '!=',
                    'is greater than': '>',
                    'is less than': '<',
                    'is greater than or equal to': '>=',
                    'is less than or equal to': '<='
                };
            }
        },
        changedColumnName() {
            if (this.selected_alias != "") {
                this.col_name = this.selected_alias;
            }
            else {
                this.col_name = this.selected_column;
            }
            if(this.$root.explorer.query.transformations[this.selected_prefix + "_" + this.selected_column] != undefined)
                this.transformations = this.$root.explorer.query.transformations[this.selected_prefix + "_" + this.selected_column];
            else 
                this.transformations = [];
        },
        replaceLabel(label) {
            if(typeof label !== 'undefined' ){
                return label.replace('${label}', this.col_name);
            }
        },
        addTransformation() {
            var transformation = {
                'transformation_type': 'FindReplace',
                'transformation': JSON.parse(JSON.stringify(this.transformation_fields['FindReplace'])),
                'user_id': this.user.user_handle,
                'status': 'add',
                'prefix': this.$root.explorer.selected_prefix,
                'is_aggregate': this.$root.explorer.selected_is_aggregate,
                'original_transformation_type': null,
                'original_transformation': null,
                'conditional_else_added':false
            };

            this.transformations.push(transformation);
        },
        editTransformation(index) {
            if(this.transformations[index].status == "edit") {
                this.transformations[index].status = "done";
            }
            else { 
                this.transformations[index].status = "edit";
                this.transformations[index].original_transformation_type = this.transformations[index].transformation_type;
                this.transformations[index].original_transformation = JSON.parse(JSON.stringify(this.transformations[index].transformation));
            }
        },
        applyTransformations() {
            if (! this.checkBeforeApplyingJson(this.transformations)) {
                if (! confirm("It appears you're trying to cast this column to JSON, but its data doesn't seem to be JSON. Are you sure you want to continue?")) {
                    return;
                }
            }

            var newTransformations = [];
            var oldTransformations = [];

            var transformations = this.transformations.filter((transformation) => {
                if (transformation.status != "deleted") {
                    if (transformation.status != "done") {
                        newTransformations.push(transformation);
                        transformation.status = "done";
                    } else {
                        oldTransformations.push(transformation);
                    }
                    return transformation;
                }
            });

            if (transformations.length == 0) {
                Vue.delete(app.explorer.query.transformations, this.selected_prefix + "_" + this.selected_column);
            } else {
                Vue.set(this.$root.explorer.query.transformations, this.selected_prefix + "_" + this.selected_column, transformations);
            }


            app.getProjectTableColumns(() => {
                if(this.$root.explorer.query.order.order_column == this.selected_column && this.$root.explorer.query.order.prefix == this.selected_prefix) {
                    var column = app.getColumnByNameAndPrefix(this.selected_column, this.selected_prefix);
                    if(column != null) {
                        this.$root.explorer.query.order.sql_definition = column.sql_definition;
                    }
                }


                app.getRecords({ success: () => {
                    this.transformations = transformations;
                    this.removeCloseEvents();
                    closeModal('#modal-add_transformation');
                }, failure: () => {
                    newTransformations.forEach((transformation) => {
                        if (transformation.original_transformation_type) {
                            transformation.status = "edit";
                        } else {
                            transformation.status = "add";
                        }
                    });

                    // Rollback our transformations
                    if (oldTransformations.length == 0) {
                        Vue.delete(app.explorer.query.transformations, this.selected_prefix + "_" + this.selected_column);
                    }
                    else {
                        Vue.set(app.explorer.query.transformations, this.selected_prefix + "_" + this.selected_column, oldTransformations);
                    }

                    this.$root.explorer.valid_query = JSON.parse(JSON.stringify(this.$root.explorer.query));

                    notify.danger("One or more of the transformations you applied is invalid.");
                }});
            });
        },
        checkBeforeApplyingJson(transformations) {
            let hasJsonTransformations = transformations.filter(transformation => {
                if (transformation.status != 'deleted' && transformation.transformation_type == 'Cast' && transformation.transformation.field_1.value == 'jsonb') {
                    return true;
                }
            }).length > 0;
            
            if (! hasJsonTransformations) {
                return true;
            }

            if (app.explorer.selected_column_data_type != 'character varying') {
                return false;
            }

            // Check our currently displayed data to see if it seems json-ish
            let unJsonlikeValues = app.explorer.records.flatMap(col => col[app.explorer.selected_column])
                .filter(val => {
                    try {
                        if(val == '' || val === null) {
                            return false;
                        }

                        if (['number', 'boolean'].includes(typeof JSON.parse(val))){
                            return true;
                        }
                    }
                    catch (e) {
                        try {
                            check = JSON.parse(JSON.stringify(str));
        
                            if (['number', 'boolean'].includes( typeof JJSON.parse(JSON.stringify(val)) )){
                                return true;
                            }
                        }
                        catch (e2) {
                            return false;
                        }
                    }
                });

            return unJsonlikeValues.length == 0;
        },
        removeTransformation(index) {
            if (this.transformations[index].status == "add") {
                this.transformations.splice(index, 1);
            }
            else {
                this.transformations[index].status = "deleted";
                if (this.transformations[index].original_transformation_type == null) {
                    this.transformations[index].original_transformation_type = this.transformations[index].transformation_type;
                    this.transformations[index].original_transformation = JSON.parse(JSON.stringify(this.transformations[index].transformation));
                }
            }

        },
        changeType(index, event) {
            this.transformations[index].transformation = JSON.parse(JSON.stringify(this.transformation_fields[event.target.value]));
        },
        addCloseEvents() {
            $(document).on("mousedown", "#dmiux_body", this.modalCloseTransformations);
            $(document).on("keydown", this.modalCloseTransformations);
        },
        removeCloseEvents() {
            $(document).off("mousedown", "#dmiux_body", this.modalCloseTransformations);
            $(document).off("keydown", this.modalCloseTransformations);
        },
        modalCloseTransformations(event) {
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

            // You either clicked outside the modal, or the X Button, or the Cancel Button
            for(var i=0; i<this.transformations.length; i++) {
                if (this.transformations[i].status != 'done') {
                    if (confirm("You have unsaved transformations. Changes will be discarded. Are you sure?") == false) {
                        return;
                    }
                    else {
                        break;
                    }
                }
            }

            this.transformations = this.transformations.filter(function(transformation) {
                var statuses = [ 'done', 'edit', 'deleted' ];
                if(statuses.includes(transformation.status)) {
                    if (transformation.status == "edit" || transformation.status == "deleted") {
                        transformation.transformation_type = transformation.original_transformation_type;
                        transformation.transformation = transformation.original_transformation;
                        transformation.status = "done";
                    }
                    return transformation;
                }
            });
            Vue.set(app.explorer.query.transformations, this.selected_prefix + "_" + this.selected_column, this.transformations);

            this.removeCloseEvents();
            this.$root.getProjectTableColumns(() => {
                app.getRecords();
            });
            closeModal('#modal-add_transformation');
        },
        countTranformationRows(transformation){
            let count = 0;
            for (var c in transformation) {
                count = count + 1;
            }
            return count;
        },
        addElseIf(index,type){
            this.transformations.forEach((transformation,key) => {
                if(key == index){    
                    if(type == 'elseif'){  
                        var trans = {
                            ['else_if_field_1']: { 'label': '&nbsp;ElseIf <strong>&nbsp;value&nbsp;</strong>', 'value': '=', 'type': 'select', 'select_values': 'operators'},
                                ['else_if_field_2']: { 'label': '', 'value': '', 'type': 'input'},
                                ['else_if_field_3']: { 'label': '&nbsp;Then <strong>&nbsp;value&nbsp;</strong> is', 'value': '', 'type': 'input'}, 
                                ['else_if_field_4']: { 'label': '', 'value': '', 'type': 'drag'},
                                ['else_if_field_5']: { 'label': '', 'value': '', 'type': 'remove'} 
                        };

                        if (this.transformations[key].conditional_else_added != true) {
                            this.transformations[key].transformation.push(trans);
                        } else {
                            this.transformations[key].transformation.splice(-1, 0, trans);
                        }
                       
                        
                    }else{
                        this.transformations[key].transformation.push({  
                            ['else_field_1']: {  'label': '&nbsp;Else <strong>&nbsp;value&nbsp;</strong> is', 'value': '', 'type': 'input'},
                            ['else_field_2']: { 'label': '', 'value': '', 'type': 'remove'} 
                        });
                        this.transformations[key].conditional_else_added = true;
                    }
                }
            });
        },
        removeConditionalTranformation(trans_key,sub_trans_key,type){
            this.transformations.forEach((transformation,key_t) => {
                if(trans_key == key_t){
                    var trans_obj = transformation.transformation;
                    trans_obj.splice(sub_trans_key, 1);
                    if(type == 'else'){
                        this.transformations[trans_key].conditional_else_added = false;
                    }
                }
            });
        },
        checkMove(e) {
            var count = this.countTranformationRows(e.relatedContext.list);
            var element = e.draggedContext.element;
            if(typeof element.if_field_1 !== 'undefined' || typeof element.if_field_2 !== 'undefined' || typeof element.if_field_3 !== 'undefined' || typeof element.else_field_1 !== 'undefined' || typeof element.else_field_2 !== 'undefined' || e.draggedContext.futureIndex === 0 || e.draggedContext.futureIndex === (count - 1)){
                return false;
            }
        }
    }
}

/** Vue.js Component
 *******************************************************************************
 * copy-column
 *******************************************************************************
 * Renders a modal that allows users to copy the selected column to a new column.
 */
modals.copy_column = {
    template: '#copy-column-modal-template',
    props: [
        "open",
        "columns",
        "table",
        "app_transformations",
        "selected_column",
        "selected_column_index",
        "selected_column_data_type",
        "selected_prefix",
        "selected_alias",
        "selected_sql_definition",
        "project_id",
        "viewing_type",
        "user"
    ],
    watch: {
        open() {
            if(this.open == true) {
                if(!app.isColumnSelected()) { 
                    app.modals.copy_column = false;
                    return; 
                }
                this.initialize();
            }
            else {
                this.new_column.alias = "";
                this.sel_column_found = false;
                this.sel_column_added = false;
                $(document).off("mousedown", "#dmiux_body", this.modalCloseCopy);
                closeModal('#modal-copy_column');
            }
        },
    },
    data: function () {
        return {
            new_column: {
                added: true,
                alias: "",
                character_maximum_length: null,
                checked: true,
                column_name: "",
                data_type: "varchar",
                editing: false,
                numeric_precision: "",
                numeric_scale: 0,
                prefix: "custom",
                target_column_name: "",
                sql_definition: ""
            },
            join_count: 0,
            root_column: "",
            sel_column_found: false,
            sel_column_added: false
        }
    },
    methods: {
        initialize() {
            var column = this.columns[this.selected_column_index];
            if (column.data_type != 'jsonb') {
                notify.danger("You can only copy a column that is in unstructured data.");
                app.modals.copy_column = false;
                return;
            }

            this.root_column = column.column_name;
            this.sel_column_added = false;  // initialize to false
            this.sel_column_found = false;  // initialize to false
            for (var i=0; i<this.columns.length; i++) {
                if (this.columns[i].added == true) {
                    this.sel_column_added = true;  // we found a column that was added
                    continue;  // skip it
                }
                if (this.columns[i].column_name == this.root_column && this.columns[i].prefix == this.selected_prefix) { // current column prefix & name = selected prefix & root_column
                    if (this.columns[i].added == false) {  // and the column was not added
                        this.sel_column_found = true;  // we found the original column that matches the added column
                        break;
                    }
                }
            }
            if (this.sel_column_found == false && this.sel_column_added == true) {
                notify.danger("Please select a column that has not been added.");
                app.modals.copy_column = false;
                return;
            }
            else {
                this.new_column.column_name = "";
                this.new_column.data_type = "varchar";
                openModal("#modal-copy_column");
                $(document).on("mousedown", "#dmiux_body", this.modalCloseCopy);
            }
        },
        copyColumn() {
            // Make sure form is complete
            if (this.new_column.data_type == '' || this.new_column.column_name == '') {
                notify.danger("Please enter all values.");
                return;
            }
            // Make sure new column name is unique
            for (var i=0; i<this.$root.explorer.query.columns.length; i++) {
                if (this.$root.explorer.query.columns[i].column_name == this.new_column.column_name) {
                    notify.danger("You must give your new column a unique name.");
                    return;
                }
            }
            this.join_count = app.explorer.query.joins.length;
            for (var i=0; i<this.$root.explorer.query.columns.length; i++) {
                if ((this.$root.explorer.query.columns[i].column_name == this.root_column) && (this.$root.explorer.query.columns[i].prefix == this.selected_prefix) && (this.$root.explorer.query.columns[i].added == false)) {
                    // this is the column to be copied
                    this.new_column.target_column_name = this.new_column.column_name;

                    if (this.selected_sql_definition !== undefined && this.selected_sql_definition != '') {
                        var column_sql = this.selected_sql_definition;
                    } else {
                        var column_sql = `"${this.selected_prefix}".` + this.selected_column; // Specify the table name because the column may be ambiguously named
                    }

                    this.new_column.sql_definition = 'CAST(' + column_sql + ' AS ' + this.new_column.data_type +')';

                    let new_column = JSON.parse(JSON.stringify(this.new_column));
                    this.$root.explorer.query.columns.splice(i+1, 0, new_column);
                    break;
                }
            }
            app.getRecords({ success: () => {
                app.modals.copy_column = false;
            }, failure: (e) => {
                // Remove the column and re-run the original query
                this.$root.explorer.query.columns = this.$root.explorer.query.columns.filter((col, index) => index != i + 1);
                this.$root.getRecords({
                    success: () => {
                        notify.danger("Your copy has failed. This may be due to casting to an unacceptable type.");
                    },
                    failure: (e) => {
                        notify.danger("Your copy has failed. Rolling back to the original state failed.");
                    }
                });
            }});
        },
        getPosition(string, subString, index) {
            return string.split(subString, index).join(subString).length;
        },
        cleanupName(e) {
            this.new_column.column_name = e.target.value;
            if (this.new_column.column_name.length > 63) {
                this.new_column.column_name = this.new_column.column_name.substring(0, 63);
            }
            this.new_column.column_name = this.new_column.column_name.toLowerCase();
            this.new_column.column_name = this.new_column.column_name.trim();
            this.new_column.column_name = this.new_column.column_name.replace(" ", "");
            this.new_column.column_name = this.new_column.column_name.replace(/\W/g, ''); 
        },
        modalCloseCopy(event) {
            event.stopPropagation();
            var clicked_element = event.target;
            if (clicked_element.closest(".dmiux_popup__window")) {
                // You clicked inside the modal
                if (clicked_element.id != "x-button" && !(clicked_element.classList.contains("dmiux_popup__cancel")))
                    return;
            }
            // You clicked either on the X Button, the Cancel Button , or outside the modal - the modal will close
            // If any columns were copied, delete them.
            this.new_column.alias = "";
            this.new_column.character_maximum_length = null;
            this.new_column.column_name = "";
            this.new_column.data_type = "varchar";
            this.new_column.numeric_precision = "";
            this.new_column.numeric_scale = 0;
            this.new_column.target_column_name = "";
            this.new_column.sql_definition = "";
            this.new_column.prefix = "custom";
            this.sel_column_found = false;
            this.sel_column_added = false;
            app.modals.copy_column = false;
        }
    }
}


/** Vue.js Component
 *******************************************************************************
 * counts
 *******************************************************************************
 * Renders a modal that displays the counts for the distinct top x values
 * for a given column.
 */
modals.counts = {
    template: '#counts-modal-template',
    props: [
        'open',
        'control_id',
        'table',
        'viewing_type',
        'selected_column',
        'selected_prefix',
        'selected_filter',
        'edit_mode',
        'filters'
    ],
    data: function() {
        return {
            quantity: 0,
            applyFilters: true,
            counts: [],
            selected_index: false
        }
    },
    watch: {
        open() {
            if(this.open != false) {
                this.counts = [];
                this.getCounts(this.open);
            }
            else {
                $(document).off("mousedown", "#dmiux_body", this.modalCloseCounts);
                $(document).off("keydown", this.modalCloseCounts);
                app.closeModal();
            }
        }
    },
    methods: {
        getCounts(quantity) {
            if(!app.isColumnSelected()) { return };

            if(quantity) {
                this.quantity = quantity;
            }

            var joins = [];
            if(app.explorer.viewing_type == "Join")
            {
                joins = app.explorer.query.joins;
            }

            var selected_index = this.selected_filter.index;
            var temp_filters = [];
            if(selected_index == undefined)
            {
                temp_filters = this.$root.explorer.query.filters;
            }
            else 
            {
                temp_filters = this.$root.explorer.query.filters.filter(function(filter, index) { 
                    if (index != selected_index) {
                        return filter;
                    }
                });
            }

            let options = FetchHelper.buildJsonRequest({
                selected_column: this.selected_column,
                limit: this.quantity,
                filtered: this.applyFilters,
                filters: temp_filters,
                joins: joins,
                prefix: app.explorer.query.prefix,
                schema: app.explorer.query.schema,
                selected_prefix: app.explorer.selected_prefix,
                selected_sql_definition: app.explorer.selected_sql_definition,
                transformations: app.explorer.query.transformations,
                columns: app.explorer.query.columns,
                is_aggregate: app.explorer.selected_is_aggregate,
                is_grouped: app.explorer.query.is_grouped,
                unions: app.explorer.query.unions,
                union_all: app.explorer.query.union_all,
            });

            this.$root.ready = false;
            fetch(`${baseUrl}/internal-api/v1/studio/projects/${this.control_id}/tables/${app.explorer.query.schema}/${this.table}/popular-counts`, options)
                .then(FetchHelper.handleJsonResponse)
                .then(resp => {
                    this.$root.ready = true;
                    this.counts = resp.data.counts;
                    openModal("#modal-counts");
                    $(document).on("mousedown", "#dmiux_body", this.modalCloseCounts);
                    $(document).on("keydown", this.modalCloseCounts);
                })
                .catch((error) => {
                    this.$root.ready = true;
                    this.counts = [];
                    this.open = false;
                    ResponseHelper.handleErrorMessage(error, 'There was a problem getting counts from the query.');
                });
        },
        addFilter(val) {
            if (app.explorer.query.unions.length > 0) {
                notify.danger("Filters are disabled when unions are applied.");
                return;
            }
            var operator = "=";
            if (this.edit_mode == true) {
                app.removeFilter(this.selected_filter.index, false);
                app.selected_filter = {};
            }

            if(this.isEmpty(val)) {
                operator = "empty";
            }
            
            this.$root.addFilter(val.value, "Top " + this.quantity, operator);
            app.modals.counts = false;
            app.edit_mode = false;
        },
        isEmpty(val) {
            // We have StringHelper.isEmpty but that method does not take string null and NULL into account
            if (! isNaN(parseFloat(val.value)) && isFinite(val.value)) {
                return false;
            }

            if(val.value == [] || val.value == null || val.value == 'null' || val.value == 'NULL' || val.value.trim() == '') {
                return true;
            } else {
                return false;
            }
        },
        modalCloseCounts(event) {
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
            // You either clicked outside the modal, or the X Button, or the Cancel Button
            app.modals.counts = false;
            app.edit_mode = false;
            app.selected_filter = {};
        },
        changeFilterStatus(quantity) {
            this.applyFilters = !this.applyFilters;
            this.getCounts(quantity);
        }
    }
}

modals.table_notes = {
    template: '#table-notes-template',
    props: [ 'open', 'notes', 'note_id'],
    data: function() {
        return {
            type: 'note',
            note: '',
            showing_note_form: false,
            is_editing: false,
            current_note: null
        }
    },
    watch: {
        open() {
            if(! this.open) {
                this.$root.show_note_id = null;
                this.modalClose();
                return;
            }
            this.$root.getTableNotes();
            openModal('#modal-table_notes');
            $(document).on("mousedown", "#dmiux_body", this.modalClose);
            $(document).on("keydown", this.modalClose);
            this.reset();
        },
        note_id() {
            this.noteForm(this.note_id);
        }
    },
    methods: {
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
                        if (clicked_element.id != "x-button" && !(clicked_element.classList.contains("dmiux_popup__cancel"))) {
                            return;
                        }
                    }

                    if(clicked_element.id == 'explorer-loader-backdrop'){
                        return;
                    }
                }
            }

            // You clicked either on the X Button, the Cancel Button , or outside the modal - the modal will close
            this.$parent.modals.table_notes = false;

            this.reset();
            
            $(document).off("mousedown", "#dmiux_body", this.modalClose);
            $(document).off("keydown", this.modalClose);
            closeModal('#modal-table_notes');
        },
        setType(type) {
            this.type = type;
        },
        saveNote() {
            if (this.current_note.id != -1) {
                this.updateNote();
                return;
            }

            this.addNote();
        },
        addNote() {
            let options = FetchHelper.buildJsonRequest({
                note: this.current_note.note
            });
   
            fetch(`/internal-api/v1/studio/projects/${control_id}/tables/${this.$root.explorer.query.schema}/${this.$root.explorer.query.table}/notes`, options)
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    notify.success(json.message);
                    this.$root.getTableNotes();
                    this.reset();
                })
                .catch((error) => {
                    ResponseHelper.handleErrorMessage(error, 'Table note was not saved.');
                });
        },
        updateNote() {
            let options = FetchHelper.buildJsonRequest({
                note: this.current_note.note
            }, 'put');
            
            fetch(`${baseUrl}/internal-api/v1/studio/projects/${control_id}/tables/${this.$root.explorer.query.schema}/${this.$root.explorer.query.table}/notes/${this.current_note.id}`, options)
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    notify.success(json.message);
                    this.$root.getTableNotes();
                    this.reset();
                })
                .catch((error) => {
                    ResponseHelper.handleErrorMessage(error, 'Table note was not updated.');
                });
        },
        deleteNote(id) {
            if(! confirm("Are you sure you want to delete this note? This cannot be undone.")) {
                return;
            }

            fetch(`${baseUrl}/internal-api/v1/studio/projects/${control_id}/tables/${this.$root.explorer.query.schema}/${this.$root.explorer.query.table}/notes/${id}`, {method: 'delete'})
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    notify.success(json.message);
                    this.$root.getTableNotes();
                })
                .catch((error) => {
                    ResponseHelper.handleErrorMessage(error, 'There was a problem deleting the note.');
                });
        },
        noteForm(id) {
            if (id === undefined || id == null) {
                this.current_note = {
                    id: -1,
                    note: ''
                };
                this.is_editing = false;
                this.showing_note_form = true;
                return;
            }

            this.current_note = this.notes.filter((note) => note.id == id)[0];
            this.current_note.original_value = this.current_note.note;
            this.is_editing = true;
            this.showing_note_form = true;
        },
        cancelForm() {
            if(this.current_note != undefined && this.current_note != '') {
                this.current_note.note = this.current_note.original_value;
            }

            this.reset();

            if (this.note_id !== undefined && this.note_id != null) {
                this.$root.modals.table_notes = false;
            }
        },
        reset() {
            this.is_editing = false;
            this.showing_note_form = false;
            this.current_note = {};
        }
    }
}

/** Vue.js Component
 *******************************************************************************
 * longest
 *******************************************************************************
 * Renders a modal that displays the 10 longest values for a given column.
 */
modals.longest = {
    template: '#longest-modal-template',
    props: [
        'open',
        'control_id',
        'table',
        'viewing_type',
        'selected_column',
        'selected_prefix',
        'selected_filter',
        'edit_mode',
        'filters'
    ],
    data: function() {
        return {
            longest: [],
            applyFilters: true,
            selected_index: false
        }
    },
    watch: {
        open() {
            if(this.open != false) {
                this.longest = [];
                this.getLongest();
            }
            else {
                $(document).off("mousedown", "#dmiux_body", this.modalCloseLongest);
                $(document).off("keydown", this.modalCloseLongest);
                app.closeModal();
            }
        }
    },
    methods: {
        getLongest() {
            if(!app.isColumnSelected()) { return };

            var joins = [];
            if(app.explorer.viewing_type == "Join")
            {
                joins = app.explorer.query.joins;
            }

            var selected_index = this.selected_filter.index;
            var temp_filters = [];
            if(selected_index == undefined)
            {
                temp_filters = this.$root.explorer.query.filters;
            }
            else 
            {
                temp_filters = this.$root.explorer.query.filters.filter(function(filter, index) { 
                    if (index != selected_index) {
                        return filter;
                    }
                });
            }

            let options = FetchHelper.buildJsonRequest({
                selected_column: this.selected_column,
                filtered: this.applyFilters,
                filters: temp_filters,
                joins: joins,
                prefix: app.explorer.query.prefix,
                schema: app.explorer.query.schema,
                selected_prefix: app.explorer.selected_prefix,
                selected_sql_definition: app.explorer.selected_sql_definition,
                transformations: app.explorer.query.transformations,
                columns: app.explorer.query.columns,
                is_aggregate: app.explorer.selected_is_aggregate,
                unions: app.explorer.query.unions,
                union_all: app.explorer.query.union_all,
            });

            this.$root.ready = false;
            fetch(`${baseUrl}/internal-api/v1/studio/projects/${this.control_id}/tables/${app.explorer.query.schema}/${this.table}/longest-counts`, options)
                .then(FetchHelper.handleJsonResponse)
                .then(resp => {
                    this.$root.ready = true;
                    this.longest = resp.data.longest;
                    openModal('#modal-longest');
                    $(document).on("mousedown", "#dmiux_body", this.modalCloseLongest);
                    $(document).on("keydown", this.modalCloseLongest);
                })
                .catch((error) => {
                    this.$root.ready = true;
                    app.closeModal();
                    ResponseHelper.handleErrorMessage(error, "Failed to get longest column entries");
                });
        },
        addFilter(val) {
            if (app.explorer.query.unions.length > 0) {
                notify.danger("Filters are disabled when unions are applied.");
                return;
            }
            var operator = "=";
            if (this.edit_mode == true) {
                app.removeFilter(this.selected_filter.index, false);
                app.selected_filter = {};
            }

            if(this.isEmpty(val)) {
                operator = "empty";
            }

            this.$root.addFilter(val.value, "longest", operator);
            app.modals.longest = false;
            app.edit_mode = false;
        },
        isEmpty(val) {
            // We have StringHelper.isEmpty but that method does not take string null and NULL into account
            if (! isNaN(parseFloat(val.value)) && isFinite(val.value)) {
                return false;
            }

            if(val.value == [] || val.value == null || val.value == 'null' || val.value == 'NULL' || val.value.trim() == '') {
                return true;
            } else {
                return false;
            }
        },
        modalCloseLongest(event) {
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
            // You either clicked outside the modal, or the X Button, or the Cancel Button
            app.modals.longest = false;
            app.edit_mode = false;
            app.selected_filter = {};
        },

        changeFilterStatus() {
            this.applyFilters = !this.applyFilters;
            this.getLongest();
        }
    }
}

/** Vue.js Component
 *******************************************************************************
 * custom-filter
 *******************************************************************************
 * Renders a modal that displays a form for building a search criteria.
 */
modals.custom_filter = {
    template: '#filter-modal-template',
    props: [
        'open',
        'selected_column',
        'selected_column_data_type',
        'selected_prefix',
        'viewing_type',
        'edit_mode',
        'selected_filter',
        'tables'
    ],
    data: function() {
        return {
            operators: [{
                operator: "=",
                name: "is equal to",
                disabled: false
            },
            {
                operator: "!=",
                name: "is not equal to",
                disabled: false
            },
            {
                operator: "<",
                name: "is less than",
                disabled: false
            },
            {
                operator: "<=",
                name: "is less than or equal to",
                disabled: false
            },
            {
                operator: ">",
                name: "is greater than",
                disabled: false
            },
            {
                operator: ">=",
                name: "is greater than or equal to",
                disabled: false
            },
            {
                operator: "in",
                name: "is in list of values",
                disabled: false
            },
            {
                operator: "not in",
                name: "is not in list of values",
                disabled: false
            },
            {
                operator: "empty",
                name: "is empty",
                disabled: false
            },
            {
                operator: "not empty",
                name: "is not empty",
                disabled: false
            },
            {
                operator: "ilike",
                name: "contains",
                disabled: false
            },
            {
                operator: "not ilike",
                name: "does not contain",
                disabled: false
            },
            {
                operator: "like",
                name: "contains (case sensitive)",
                disabled: false
            },
            {
                operator: "not like",
                name: "does not contain (case sensitive)",
                disabled: false
            },
            {
                operator: "between",
                name: "is between",
                disabled: false
            },
            {
                operator: "~",
                name: "matches regular expression",
                disabled: false
            },
            {
                operator: "!~",
                name: "does not match regular expression",
                disabled: false
            }],
            operator: "=",
            value: "",
            low_value: "",
            high_value: "",
            in_array: [],
            in_column: {},
            in_type: "string",
            date_interval: {
                type: "days",
                direction: "-",
                high: {
                    type: "days",
                    direction: "+"
                },
                low: {
                    type: "days",
                    direction: "-"
                }
            },
            date_type: "manual",
            columns: []
        }
    },
    watch: {
        open() {
            if(this.open == true) {
                if(!app.isColumnSelected()) { 
                    app.modals.custom_filter = false;
                    app.closeModal();
                    return; 
                }
                this.getCustom();
            }
            else {
                $(document).off("mousedown", "#dmiux_body", this.modalCloseCustom);
                $(document).off("keydown", this.modalCloseCustom);
                app.closeModal();
            }
        },
    },
    methods: {
        getCustom() {
            // Set the disabled attribute for certain operators based on the column data_type
            this.operator = "=";
            var numeric_and_date_types = [
                'numeric',
                'integer',
                'bigint',
                'serial',
                'bigserial',
                'decimal',
                'float',
                'timestamp without time zone',
                'date'
            ];

            var numeric_types = [
                'numeric',
                'integer',
                'serial',
                'bigserial',
                'bigint',
                'decimal',
                'float'
            ];

            var regex_operators = [
                "~",
                "!~"
            ];

            for (var i=0; i < this.operators.length; i++) {
                this.operators[i].disabled = false;
                if ((this.operators[i].operator == "between" || this.operators[i].operator == "<") || (this.operators[i].operator == "<=") || (this.operators[i].operator == ">") || (this.operators[i].operator == ">=")) {
                    if (numeric_and_date_types.includes(this.selected_column_data_type)) {
                        this.operators[i].disabled = false;
                    } else {
                        this.operators[i].disabled = true;
                    }
                } else if(this.operators[i].operator == "like" || this.operators[i].operator == "not like" || this.operators[i].operator == "ilike" || this.operators[i].operator == "not ilike") {
                    if(numeric_types.includes(this.selected_column_data_type)) {
                        this.operators[i].disabled = true;
                    } else {
                        this.operators[i].disabled = false;
                    }
                } else if(regex_operators.includes(this.operators[i].operator)) {
                    if(numeric_and_date_types.includes(this.selected_column_data_type)) {
                        this.operators[i].disabled = true;
                    } else {
                        this.operators[i].disabled = false;
                    }
                }
            }

            //check if boolean column and adjust modal
            if (this.selected_column_data_type == 'boolean' ) {
                var allowedOperators = ['=', '!=', "empty", "not empty"];
                for (var i=0; i < this.operators.length; i++) {
                    if (allowedOperators.includes(this.operators[i].operator)) {
                        this.operators[i].disabled = false;
                    }
                    else {
                        this.operators[i].disabled = true;
                    }
                }
            }

            //check if date or timestamp column and adjust modal
            if (this.selected_column_data_type == 'date' || this.selected_column_data_type == 'timestamp without time zone') {
                if(this.selected_column_data_type == 'date') {
                    var allowedOperators = ["=", "!=", "empty", "not empty", "between", "<", "<=", ">", ">="];
                }
                else {
                    this.operator = "<";
                    var allowedOperators = ["empty", "not empty", "between", "<", "<=", ">", ">="];
                }

                for (var i=0; i < this.operators.length; i++) {
                    if (allowedOperators.includes(this.operators[i].operator)) {
                        this.operators[i].disabled = false;
                    }
                    else {
                        this.operators[i].disabled = true;
                    }
                }
            }

            // If we are in "edit" mode, populate the values of the filter we are editing
            if (this.edit_mode == true) {
                // Load the current values of the filter into "in_array" or "value"
                if (this.selected_filter.operator == "in" || this.selected_filter.operator == "not in") {
                    if(this.selected_filter.value.type == "string") {
                        for (var i=0; i < this.selected_filter.value.info.length; i++) {
                            this.in_array.push(this.selected_filter.value.info[i]);
                        }
                    }
                    else {
                        this.in_type = "column";
                        var column = { "column": this.selected_filter.value.info.column, "table": this.selected_filter.value.info.table, "schema": this.selected_filter.value.info.schema, "schema_table": this.selected_filter.value.info.schema_table };
                        this.in_column = column;
                        this.getInColumns(false);
                    }
                }
                else if (this.selected_filter.operator == 'between') {
                    if(this.selected_filter.value.type == "interval") {
                        this.date_interval.low = JSON.parse(this.selected_filter.value.info.low_val);
                        this.date_interval.high = JSON.parse(this.selected_filter.value.info.high_val);
                        this.date_type = "interval"
                    }
                    else {
                        this.low_value = this.selected_filter.value.info.low_val;
                        this.high_value = this.selected_filter.value.info.high_val;
                    }
                }
                else {
                    if(this.selected_filter.value.type == "interval") {
                        this.date_interval = JSON.parse(this.selected_filter.value.info);
                        this.date_type = "interval"
                    }
                    else {
                        this.value = this.selected_filter.value;
                    }
                }
                this.operator = this.selected_filter.operator;
            }

            // Now that all fields are populated, open the modal
            openModal('#modal-custom_filter');

            $(document).on("mousedown", "#dmiux_body", this.modalCloseCustom);
            $(document).on("keydown", this.modalCloseCustom);
        },
        setOperationChange() {
            if (this.edit_mode == true) {
                this.value = "";
                this.low_value = "";
                this.high_value = "";
                this.in_column = {};
                this.in_type = "string";
                this.date_interval = {
                    type: "days",
                    direction: "-",
                    high: {
                        type: "days",
                        direction: "+"
                    },
                    low: {
                        type: "days",
                        direction: "-"
                    }
                };
                this.date_type = "manual";
                this.in_array = [];
            }
        },
        cleanupDate(which_date) {
            if (which_date == 'low') {
                var year = this.low_value.substring(0, this.low_value.indexOf("-"));
                if (year.length > 4)
                    this.low_value = this.low_value.replace(this.low_value.substring(0, this.low_value.indexOf("-")), this.low_value.substring(0, 4));    
            }
            else if (which_date == 'high') {
                var year = this.high_value.substring(0, this.high_value.indexOf("-"));
                if (year.length > 4)
                    this.high_value = this.high_value.replace(this.high_value.substring(0, this.high_value.indexOf("-")), this.high_value.substring(0, 4));    
            }
            else if (which_date == 'single') {
                var year = this.value.substring(0, this.value.indexOf("-"));
                if (year.length > 4)
                    this.value = this.value.replace(this.value.substring(0, this.value.indexOf("-")), this.value.substring(0, 4));    
            }
        },
        cleanupNumber(which_nbr) {
            if (which_nbr == 'low') {
                if (this.selected_column_data_type == 'integer' || this.selected_column_data_type == 'bigint') {
                    this.low_value = this.low_value.replace(/[^0-9\-?]/g, '');
                }
                else {
                    this.low_value = this.low_value.replace(/[^0-9.?\-?]/g, '');
                }
            }
            else if (which_nbr == 'high') {
                if (this.selected_column_data_type == 'integer' || this.selected_column_data_type == 'bigint') {
                    this.high_value = this.high_value.replace(/[^0-9\-?]/g, '');
                }
                else {
                    this.high_value = this.high_value.replace(/[^0-9.?\-?]/g, '');
                }
            }
            else if (which_nbr == 'single') {
                if (this.selected_column_data_type == 'integer' || this.selected_column_data_type == 'bigint') {
                    this.value = this.value.replace(/\D+/g, '');
                }
                else {
                    this.value = this.value.replace(/[^0-9.]/g, '');
                }
            }
        },
        addFilter() {
            if (app.explorer.query.unions.length > 0) {
                notify.danger("Filters are disabled when unions are applied.");
                return;
            }
            if(!app.isColumnSelected()) { return };
            var column = JSON.parse(JSON.stringify(app.explorer.selected_column));
            var prefix = JSON.parse(JSON.stringify(app.explorer.selected_prefix));

            if(((this.operator != "in" && this.operator != "not in" && this.operator != "between" && this.operator != "empty" && this.operator != "not empty" && this.date_type != "interval") && this.value == "") || 
            ((this.operator == "in" || this.operator == "not in") && ((this.in_type == 'string' && this.in_array.length == 0) || (this.in_type == 'column' && (this.in_column.table == undefined || this.in_column.column == undefined)) || this.operator == "")) || 
            ((this.operator == "between" && this.date_type == "manual") && (this.low_value == "" || this.high_value == "")) ||
            ((this.operator == "between" && this.date_type == "interval") && (this.date_interval.high.type == undefined || this.date_interval.high.direction == undefined || (this.date_interval.high.time == undefined || this.date_interval.high.time == "") || this.date_interval.low.type == undefined || this.date_interval.low.direction == undefined || (this.date_interval.low.time == undefined || this.date_interval.low.time == ""))) ||
            ((this.operator != "between" && this.date_type == "interval") && (this.date_interval.type == undefined || this.date_interval.direction == undefined || (this.date_interval.time == undefined || this.date_interval.time == "")))) {
                notify.danger("Complete all required fields, and check for date errors.");
                return;
            }

            if(this.operator == "in" || this.operator == "not in") {
                this.value  = { "type": this.in_type, "info": [] };
                if(this.in_type == 'string') {
                    this.value.info = this.in_array;
                    for (var i=0; i<this.value.info.length; i++) {
                        this.value.info[i] = this.value.info[i].trim();
                    };
                }
                else {
                    this.value.info = this.in_column;
                }
            } else if (this.operator == 'between') {
                if (this.selected_column_data_type == 'timestamp without time zone' || this.selected_column_data_type == 'date') { // date/time types
                    this.value  = { "type": this.date_type, "info": [] };
                    if(this.date_type == "manual") {
                        var low_val = parseInt(this.low_value.replace(/\D+/g, ''));
                        var high_val = parseInt(this.high_value.replace(/\D+/g, ''));
                        if (low_val >= high_val) {
                            notify.danger("Low Date must be before High Date");
                            return;
                        }
                    }
                    else {
                        this.high_value = JSON.stringify(this.date_interval.high);
                        this.low_value = JSON.stringify(this.date_interval.low);
                    }
                }
                else {  // numeric types 
                    this.value  = { "type": 'manual', "info": [] };
                    if (this.selected_column_data_type == 'integer' || this.selected_column_data_type == 'bigint') { // integer types
                        var low_val = parseInt(this.low_value);
                        var high_val = parseInt(this.high_value);
                    }
                    else { // float types
                        var low_val = parseFloat(this.low_value);
                        var high_val = parseFloat(this.high_value);
                    }
                    if (low_val >= high_val) {
                        notify.danger("Low Value must be less than High Value");
                        return;
                    }
                }

                var between_data = {
                    'high_val': this.high_value,
                    'low_val': this.low_value
                }
                this.value.info = between_data;
            } else if (this.operator == 'empty' || this.operator == 'not empty') {
                this.value = "";
            } else if (this.operator == "~") {
                try {
                    const regex_text = RegExp(this.value);
                    regex_text.exec('test');
                } catch (e) {
                    notify.danger("Your regular expression is invalid. Please fix it, then try again.");
                    return;
                }
            } else {
                if ((this.selected_column_data_type == 'timestamp without time zone' || this.selected_column_data_type == 'date') && this.date_type == "interval") { // date/time types
                    this.value  = { "type": this.date_type, "info": [] };
                    this.value.info = JSON.stringify(this.date_interval);
                }
                else {
                    this.value = this.value.trim();
                }
            }

            if (this.edit_mode == true) {
                app.removeFilter(this.selected_filter.index, false);
            }

            app.explorer.offset = 0;
            app.explorer.page_num = 1;

            app.explorer.query.filters.push({
                column: this.selected_column,
                data_type: this.selected_column_data_type,
                value: this.value,
                operator: this.operator,
                from: "custom",
                prefix: app.explorer.selected_prefix,
                alias: app.explorer.selected_alias,
                sql_definition: app.explorer.selected_sql_definition,
                is_aggregate: app.explorer.selected_is_aggregate
            });

            if (app.temp_selected_column != "" || app.edit_mode) {
                app.restoreColumnStateForFilter();
            }

            this.value = "";
            this.operator = "=";
            this.in_array = [];
            app.modals.custom_filter = false;
            app.edit_mode = false;
            app.explorer.sent_column = '';

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
        getInColumns(reset = true) {
            if(reset) {
                this.columns = [];
                this.in_column.column = "";
                this.in_column.schema = this.in_column.schema_table.split('.')[0];
                this.in_column.table = this.in_column.schema_table.split('.')[1];
            }

            fetch(`/internal-api/v1/studio/projects/${this.$root.control_id}/tables/${this.in_column.schema}/${this.in_column.table}/table-columns`)
                .then(response => response.json())
                .then(json => {
                    this.columns = json.data;
                })
        },
        addIn() {
            this.in_array.push(this.value);
            this.value = "";
        },
        editIn() {
            if (this.in_array.length == 0) {
                for (var i=0; i<this.selected_filter.value.info.length; i++) {
                    this.in_array.push(this.selected_filter.value.info[i]);
                }
            }
            this.value = "";
        },
        clearIn() {
            this.in_array = [];
        },
        removeValue(index) {
            this.in_array.splice(index, 1);
        },
        modalCloseCustom(event) {
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
            // You either clicked outside the modal, or the X Button, or the Cancel Button
            app.modals.custom_filter = false;
            if (app.temp_selected_column != "" || app.edit_mode) {
                app.restoreColumnStateForFilter();
            }
            app.edit_mode = false;
            this.value = "";
            this.low_value = "";
            this.high_value = "";
            this.operator = "=";
            this.in_array = [];
            this.in_column = {};
            this.in_type = "string";
            this.date_interval = {
                type: "days",
                direction: "-",
                high: {
                    type: "days",
                    direction: "+"
                },
                low: {
                    type: "days",
                    direction: "-"
                }
            };
            this.date_type = "manual";
            this.columns = [];
        }
    }
}

/** Vue.js Component
 *******************************************************************************
 * map-column
 *******************************************************************************
 * Renders a modal that displays mappings, mapping notes, conditional business
 * rules, and lookups.
 */
 modals.map_column = {
    template: '#map-modal-template',
    props: [,
        'open',
        'control_id',
        'modules',
        'destination_schema_id',
        'mapping_module_id',
        'table',
        'selected_column',
        'tables',
        'mappings'
    ],
    data: function() {
        return {
            destination_tables: [],
            destination_columns: [],
            options: [],
            mapping: {
                id: 0,
                destination_table_name: "",
                destination_column_name: "",
                is_other: false,
                condition: "",
                notes: "",
                module_fields: []
            },
            columnMappings: []
        }
    },
    watch: {
        open() {
            if(this.open != false) {
                app.modals.map_column = false;
            }
            else {
                this.resetMapping();
            }
        },
        selected_column() {
            this.columnMappings = this.mappings[this.table + "_" + this.selected_column];
            if(this.columnMappings == undefined)
                this.columnMappings = [];
        },
        mappings() {
            this.columnMappings = this.mappings[this.table + "_" + this.selected_column];
            if(this.columnMappings == undefined)
                this.columnMappings = [];
        }
    },
    methods: {
        resetMapping() {
            this.mapping = {
                id: 0,
                destination_table_name: "",
                destination_column_name: "",
                is_other: false,
                condition: "",
                notes: "",
                module_fields: []
            };
        },
        closeMapModal() {
            closeModal('#modal-map_column');
        },
        changeModule(module_id) {
            app.mapping.selected_mapping_module = module_id;
            this.resetMapping();
        },
        addMapping() {
            if(!app.isColumnSelected()) { return };

            if(!this.validateMapping()) {
                return;
            }

            let options = FetchHelper.buildJsonRequest({
                column: this.selected_column,
                module_id: this.mapping_module_id,
                destable: this.mapping.destination_table_name,
                descol: this.mapping.destination_column_name,
                notes: this.mapping.notes,
                conditions: this.mapping.condition,
                module_fields: this.mapping.module_fields
            });
            
            this.$root.ready = false;
            fetch(`/internal-api/v1/studio/projects/${this.control_id}/tables/${this.$parent.explorer.query.schema}/${this.table}/mappings`, options)
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    this.$root.ready = true;

                    notify.send('Mapping has been created successfully', 'success');
                    this.$root.mapping.selected_mapping_module = -1;
                    this.$root.getMappings();
                    this.$root.getComments();
                    this.resetMapping();
                })
                .catch(error => {
                    this.$root.ready = true;
                    ResponseHelper.handleErrorMessage(error, "Unable to add mapping at this time.");
                });
        },
        deleteMapping(id) {
            if(!app.isColumnSelected()) { return };

            if(!confirm("Do you really want to delete this mapping?")) {
                return false;
            }

            let options = FetchHelper.buildJsonRequest({
                column: this.selected_column,
            }, 'delete');

            this.$root.ready = false;

            fetch(`${baseUrl}/internal-api/v1/studio/projects/${this.control_id}/tables/${this.$parent.explorer.query.schema}/${this.$parent.explorer.query.table}/mappings/${id}`, options)
                .then(response => response.json())
                .then(resp => {
                    if(!app.checkForError(resp)){
                        return;
                    }
                    this.$root.ready = true;

                    if(resp.status == "ok") {
                        app.getMappings();
                    }
                });
        },
        toggleOther() {
            if(this.mapping.is_other) {
                this.mapping.is_other = false;
            }
            else {
                this.mapping.is_other = true;
            }
        },
        validateMapping() {
            var valid = true;
            if(this.mapping_module_id == 0 && (this.mapping.destination_table_name == "" || this.mapping.destination_column_name == "")) {
                valid = false;
            }
            
            if(valid == false) {
                alert("You submitted an invalid mapping. Please make sure all required fields are completed.");
                return false;
            }

            return true;
        },
        submitEdit() {
            if(!app.isColumnSelected()) { return };
            if(!this.validateMapping()) {
                return;
            }

            let options = FetchHelper.buildJsonRequest({
                column: this.selected_column,
                module_id: this.mapping_module_id,
                destable: this.mapping.destination_table_name,
                descol: this.mapping.destination_column_name,
                notes: this.mapping.notes,
                conditions: this.mapping.condition,
                module_fields: this.mapping.module_fields
            }, 'put');

            this.$root.ready = false;
            fetch(`/internal-api/v1/studio/projects/${this.control_id}/tables/${this.$parent.explorer.query.schema}/${this.table}/mappings/${this.mapping.id}`, options) // todo
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    this.$root.ready = true;

                    notify.send('Mapping has been updated successfully', 'success');
                    this.$root.mapping.selected_mapping_module = -1;
                    this.$root.getMappings();
                    this.$root.getComments();
                    this.resetMapping();
                })
                .catch(error => {
                    this.$root.ready = true;
                    ResponseHelper.handleErrorMessage(error, "Unable to add mapping at this time.");
                });
        },
        cancelEdit() {
            this.$root.mapping.selected_mapping_module = 0;
            app.getMappings();
            app.getComments();
            this.resetMapping();
        },
        getDestinationTables() {
            fetch(`${baseUrl}/internal-api/v1/studio/projects/${this.control_id}/destination-tables`)
                .then(response => response.json())
                .then(resp => {
                    this.destination_tables = resp.data;
                });
        },
        getDestinationTableColumns() {
            fetch(`${baseUrl}/internal-api/v1/studio/projects/${this.control_id}/destination-tables/${this.destination_schema_id}/${this.mapping.destination_table_name}/columns`)
                .then(response => response.json())
                .then(resp => {
                    this.destination_columns = [];
                    for(var i = 0; i < resp.data.length; i++) {
                        var label = resp.data[i].name;
                        if(resp.data[i].type && resp.data[i].length) {
                            label += ` ${resp.data[i].type} (${resp.data[i].length})`
                        }

                        this.destination_columns.push({
                            label: label,
                            value: resp.data[i].name
                        });
                    }
                });
        },
        editMapping(mapping) {
            this.$root.mapping.selected_mapping_module = mapping.mapping_module_id ?? -1;

            if(Object.keys(mapping.module_fields).length > 0) {
                var fields = [];
                for (field_data in mapping.module_fields) {
                    fields[field_data] = mapping.module_fields[field_data].value;
                }
            }

            this.mapping = Object.assign({}, mapping);

            if(this.mapping.destination_table_name != null && this.mapping.destination_table_name != "") {
                this.getDestinationTableColumns();
            }

            this.mapping.module_fields = fields;
        },
        hideShowArrows() {
            BytespreeUiHelper.hideShowArrows("all_mappings", "mapping_");
        }
    },
    updated() {
        this.$nextTick().then(this.hideShowArrows());
    },
    mounted() {
        this.getDestinationTables();
        notyfHelper.showUrlMessage();  
    }
}

modals.query = {
    template: '#query-modal-template',
    methods: {}
}

modals.change_column_preference = {
    template: '#table-modal-template',
    props: [ "columns", "open", "custom_id", "prefix", "joins" ],
    data: function() {
        return {
            active_columns: [],
            column_preferences: [],
            column_preferences_changed: false,
            column_search: '',
            timeout: null
        }
    },
    watch: {
        open() {
            if(this.open != false) {
                if (this.$root.explorer.query.unions.length > 0) {
                    notify.danger('Column preferences cannot be changed when using unions');
                    this.modalCloseColumnPreferences();
                    return;
                }

                this.initializeColumnPreferences();
                this.openModal();
            } 
            else {
                $(document).off("mousedown", "#dmiux_body", this.modalCloseColumnPreferences);
                app.closeModal();
            }
        }
    },
    methods: {
        initializeColumnPreferences() {
            this.column_search = '';
            this.column_preferences = JSON.parse(JSON.stringify(this.columns));
            this.column_preferences = this.column_preferences.filter(column => {
                column.uuid = this.uuid();
                return column;
            });
            this.active_columns = this.column_preferences;
        },
        refreshColumns() {
            if (this.column_search !== '') {
                this.applySearch(this.column_search);
            }
            else {
                this.active_columns = this.column_preferences;
            }
        },
        openModal() {
            openModal('#modal-table_controls');
            $(document).on("mousedown", "#dmiux_body", this.modalCloseColumnPreferences);
        },
        searchColumns(event) {
            if (this.timeout !== null) {
                clearTimeout(this.timeout);
            }
            let text = event.target.value;
            this.timeout = setTimeout(() => {
                this.applySearch(text);
            }, 500);
        },
        applySearch(text) {
            let search = text.toLowerCase();
            this.active_columns = this.column_preferences.filter(column => {
                if (column.alias === '' && column.target_column_name.toLowerCase().includes(search)) {
                    return column;
                }
                else if (column.alias.toLowerCase().includes(search) || column.editing == true) {
                    return column;
                }
            });
            this.$forceUpdate();
        },
        changeColumnPreferences() {
            var checkboxValues = [];
            var aliases = {};
            var alias_list = [];
            var column_list = [];
            var target_column_list = [];

            for (var i=0; i<this.column_preferences.length; i++) {
                this.clearSortingAndSelection(i);
                if(this.column_preferences[i].added == true) {
                    if(this.column_preferences[i].column_name == "" || this.column_preferences[i].sql_definition == "") {
                        notify.danger("A custom column is not valid.  Ensure the name and definition are both provided.");
                        return;
                    }

                    this.changeCustomTranformationsFilters(i);
                    this.column_preferences[i].target_column_name = this.column_preferences[i].column_name;
                    has_custom = true;
                }

                if (this.column_preferences[i].checked === true) {
                    this.column_preferences[i].new_column = false;
                    this.column_preferences[i].editing = false;
                    if (alias_list.includes(this.column_preferences[i].alias)) {
                        notify.danger(`Alias "${this.column_preferences[i].alias}" should only be used once.`);
                        return;
                    }
                    else if ((this.$root.explorer.viewing_type == 'Join' && target_column_list.includes(this.column_preferences[i].alias)) || column_list.includes(this.column_preferences[i].alias)) {
                        notify.danger(`Alias "${this.column_preferences[i].alias}" cannot be equal to existing column name.`);
                        return;
                    }
                    else if (column_list.includes(this.column_preferences[i].column_name) && this.column_preferences[i].added == true) {
                        notify.danger(`Custom column "${this.column_preferences[i].column_name}" cannot be equal to existing column name.`);
                        return;
                    }
                    else {
                        if (typeof(this.column_preferences[i].alias) != 'undefined' && this.column_preferences[i].alias.trim() != "") {
                            alias_list.push(this.column_preferences[i].alias);
                        }
                        column_list.push(this.column_preferences[i].column_name);
                        target_column_list.push(this.column_preferences[i].target_column_name);
                    }
                }
                else if (this.column_preferences[i].checked === false)
                {
                    if(this.column_preferences[i].column_name == this.$root.explorer.selected_column) {
                        this.$root.explorer.selected_column = "";
                        this.$root.$refs.records.column = "";
                    }
                }
                if (typeof(this.column_preferences[i].alias) != 'undefined' && this.column_preferences[i].alias.toLowerCase() === "count(*)") {
                    notify.danger('Column alias "count(*)" is not allowed.');
                    return;
                }
                if (this.column_preferences[i].column_name === 'count(*)') {
                    if (this.column_preferences[i].alias.trim() === "") {
                        this.column_preferences[i].alias = 'count__records';
                    }
                }
                if (this.column_preferences[i].checked) {
                    checkboxValues.push(this.column_preferences[i].column_name);
                }
                if(this.column_preferences[i].alias != "") {
                    aliases[this.column_preferences[i].column_name] = this.column_preferences[i].alias;
                    this.$root.updateAlias(this.column_preferences[i].prefix, this.column_preferences[i].column_name, this.column_preferences[i].alias);
                }
            }

            if (checkboxValues.length == 0) {
                alert ("You must choose at least one column to change your Column Preference!");
                return;
            }
            this.column_preferences_changed = true;
            this.$root.explorer.query.columns = this.column_preferences;
            this.$root.refreshColumns();
            this.$root.getRecords({
                success : () => {
                    notify.success(`Your column preferences have been applied`);
                    this.$root.modals.change_column_preference = false;
                    this.$root.explorer.pending_count = true;
                    this.$root.getRecords(null, true);
                },
                failure : () => {
                    notify.danger(`Your column preferences could not be applied`);
                }
            }, false);
        },
        edit(index) {
            this.active_columns[index].editing = true;
            this.active_columns[index].old_alias = this.active_columns[index].alias;
            this.active_columns[index].old_column_name = this.active_columns[index].column_name;
            this.active_columns[index].old_sql_definition = this.active_columns[index].sql_definition;
            this.active_columns[index].old_is_aggregate = this.active_columns[index].is_aggregate;

            if (this.active_columns[index].alias === '' && this.active_columns[index].added != true) {
                this.active_columns[index].alias = this.active_columns[index].target_column_name;
            }

            this.$forceUpdate();
            this.$nextTick(function () {
                this.$refs["column_input_" + index][0].focus();
            })
        },
        save(index) {
            this.active_columns[index].new_column = false;
            this.active_columns[index].editing = false;
            this.$forceUpdate();
        },
        cancel(index) {
            if (this.active_columns[index].alias != this.active_columns[index].old_alias
            || this.active_columns[index].column_name != this.active_columns[index].old_column_name
            || this.active_columns[index].sql_definition != this.active_columns[index].old_sql_definition
            || this.active_columns[index].is_aggregate != this.active_columns[index].old_is_aggregate) {
                if (!confirm("Are you sure you want to discard the changes made to this column?")) {
                    return;
                }
            }
            this.active_columns[index].alias = this.active_columns[index].old_alias;
            this.active_columns[index].column_name = this.active_columns[index].old_column_name;
            this.active_columns[index].sql_definition = this.active_columns[index].old_sql_definition;
            this.active_columns[index].is_aggregate = this.active_columns[index].old_is_aggregate;
            this.active_columns[index].editing = false;
            this.$forceUpdate();
        },
        checkAll(prefix = null) {
            this.active_columns = this.active_columns.filter(function(preference, index) {
                if(index < 400 && (preference.prefix == prefix || prefix == null)) {
                    preference.checked = true;
                }
                return preference;
            });
        },
        uncheckAll(prefix = null) {
            this.active_columns = this.active_columns.filter(function(preference) {
                if(preference.prefix == prefix || prefix == null) {
                    preference.checked = false;
                }
                return preference;
            });
        },
        isChecked(prefix = null) {
            var check = this.active_columns.filter(function(preference) {
                if(preference.checked == true && (preference.prefix == prefix || prefix == null))
                    return preference;
            });

            if(check.length > 0)
                return true
            else
                return false;
        },
        groupSelected() {
            let checked = this.column_preferences.filter(function(preference) {
                if(preference.checked)
                    return preference;
            });
            let unchecked = this.column_preferences.filter(function(preference) {
                if(preference.checked == false)
                    return preference;
            });
            this.column_preferences = [];
            checked.forEach((preference) => {
                this.column_preferences.push(preference);
            });
            unchecked.forEach((preference) => {
                this.column_preferences.push(preference);
            });
            this.refreshColumns();
        },
        resetPreferences() { 
            for (var i=0; i<this.column_preferences.length; i++) {
                this.clearSortingAndSelection(i, true);
                if(this.column_preferences[i].added == true) {
                    this.clearCustomSortingAndSelection(i);
                }
            }

            this.column_preferences = [];
            this.active_columns = [];
            this.$root.explorer.send_columns = false;
            this.column_preferences_changed = false;

            for (var i=0; i<this.columns.length; i++) {
                if(this.columns[i].added == true) {
                    this.clearCustomTransformsFilters(i, true);
                }
            }

            app.ready = false;
            app.getProjectTableColumns(() => {
                app.getRecords();
            }, true);
            this.$root.modals.change_column_preference = false;
        },
        clearSortingAndSelection(index, is_reset = false) {
            if (this.column_preferences[index].checked === false || (is_reset && this.column_preferences[index].prefix == "aggregate")) {
                if (this.column_preferences[index].prefix + '_' + this.column_preferences[index].column_name == this.$root.$refs.records.column) {
                    this.$root.$refs.records.column = '';
                }
                var unstructured_data_column_name = this.column_preferences[index].prefix + '."' + this.column_preferences[index].column_name + '"';
                if (this.$root.$refs.records.column.substring(0, unstructured_data_column_name.length) == unstructured_data_column_name) {
                    this.$root.$refs.records.column = '';
                }
                // clear sort if column is now hidden
                if (this.column_preferences[index].prefix == this.$root.explorer.query.order.prefix
                && this.column_preferences[index].column_name == this.$root.explorer.query.order.order_column) {
                    this.$root.explorer.query.order.prefix = "";
                    this.$root.explorer.query.order.order_column = "";
                }
                // clear column selection if column is now hidden
                if (this.column_preferences[index].prefix == this.$root.explorer.selected_prefix
                && this.column_preferences[index].column_name == this.$root.explorer.selected_column) {
                    this.$root.explorer.selected_alias = "";
                    this.$root.explorer.selected_prefix = "";
                    this.$root.explorer.selected_column = "";
                }
            }
        },
        clearCustomSortingAndSelection(index) {
            if (this.column_preferences[index].prefix + '_' + this.column_preferences[index].column_name == this.$root.$refs.records.column) {
                this.$root.$refs.records.column = '';
            }
            var unstructured_data_column_name = this.column_preferences[index].prefix + '."' + this.column_preferences[index].column_name + '"';
            if (this.$root.$refs.records.column.substring(0, unstructured_data_column_name.length) == unstructured_data_column_name) {
                this.$root.$refs.records.column = '';
            }
            // clear sort if column is now hidden
            if (this.column_preferences[index].prefix == this.$root.explorer.query.order.prefix
            && this.column_preferences[index].column_name == this.$root.explorer.query.order.order_column) {
                this.$root.explorer.query.order.prefix = "";
                this.$root.explorer.query.order.order_column = "";
            }
            // clear column selection if column is now hidden
            if (this.column_preferences[index].prefix == this.$root.explorer.selected_prefix
            && this.column_preferences[index].column_name == this.$root.explorer.selected_column) {
                this.$root.explorer.selected_alias = "";
                this.$root.explorer.selected_prefix = "";
                this.$root.explorer.selected_column = "";
                this.$root.explorer.selected_is_aggregate = false;
            }
        },
        changeCustomTranformationsFilters(index) {
            if(this.column_preferences[index].old_column_name == "")
                this.column_preferences[index].old_column_name = this.column_preferences[index].column_name;

            if(this.column_preferences[index].old_sql_definition == "")
                this.column_preferences[index].old_sql_definition = this.column_preferences[index].sql_definition;

            var column = this.column_preferences[index];

            if(column.old_column_name != column.column_name || column.sql_definition != column.old_sql_definition) {
                app.explorer.query.filters = app.explorer.query.filters.filter((filter) => {
                    if(filter.column == column.old_column_name)
                    {
                        filter.column = column.column_name;
                        filter.sql_definition = column.sql_definition;
                        return filter;
                    }
                    else {
                        return filter;
                    }
                });

                app.explorer.query.joins = app.explorer.query.joins.filter((join) => {
                    if(join.custom_column_name == column.old_column_name) {
                        join.custom_column_name = column.column_name;
                        join.source_column = column.sql_definition;
                        join.source_target_column = column.sql_definition;
                        return join;
                    } else {
                        return join;
                    }
                });

                if(this.$root.explorer.selected_column == column.old_column_name) {
                    this.$root.explorer.selected_column = column.column_name;
                    this.$root.$refs.records.column = column.prefix + "_" + column.column_name;
                    this.$root.explorer.selected_sql_definition = column.sql_definition;
                }
            }

            if(column.old_column_name != column.column_name) {
                var transformation = app.explorer.query.transformations["custom_" + column.old_column_name];
                if(transformation != [] || transformation != undefined) {
                    Vue.set(app.explorer.query.transformations, "custom_" + column.column_name, transformation);
                    Vue.delete(app.explorer.query.transformations, "custom_" + column.old_column_name);
                }

                if(app.explorer.query.order.order_column == column.old_column_name && app.explorer.query.order.prefix == "custom") {
                    app.explorer.query.order.order_column = column.column_name;
                }
            }

            this.column_preferences[index].old_sql_definition = this.column_preferences[index].sql_definition;
            this.column_preferences[index].old_column_name = this.column_preferences[index].column_name;
        },
        clearCustomTransformsFilters(index, ignore_check = false) {
            var column = this.columns[index];

            if(column != undefined) {
                var joins = this.$root.explorer.query.joins.filter((join) => {
                    if(join.custom_column_name == column.old_column_name) {
                        return join;
                    }
                });

                if(joins.length > 0 && ! ignore_check) {
                    notify.danger("Unable to remove custom column because it is being used in a join");
                    return false;
                } else if (joins.length > 0) {
                    this.$root.explorer.query.joins = this.$root.explorer.query.joins.filter((join) => {
                        if(join.custom_column_name != column.old_column_name) {
                            return join;
                        }
                    });

                    if(this.$root.explorer.query.joins.length == 0) {
                        this.$root.explorer.viewing_type = "Standard";
                    }
                }

                this.$root.explorer.query.filters = this.$root.explorer.query.filters.filter((filter) => {
                    if(filter.column != column.column_name) {
                        return filter;
                    }
                });
                
                Vue.delete(this.$root.explorer.query.transformations, "custom_" + column.column_name);
            }

            return true;
        },
        customColumnText(column) {
            var text = column.sql_definition + " as " + column.column_name;

            if(column.is_aggregate == true)
                text = text + " (aggregate field)"

            return text;
        },
        addCustomColumn() {
            if(this.visible_column_count >= 400) {
                notify.danger("You'll need to hide another column before you can add a custom column.");
                return;
            }
            var column = {
                "uuid": this.uuid(),
                "column_name": "",
                "sql_definition": "",
                "added": true,
                "checked": true,
                "prefix": "custom",
                "target_column_name": "",
                "alias": "",
                "editing": true,
                "new_column": true,
                "old_alias": "",
                "old_column_name": "",
                "old_sql_definition": "",
                "is_aggregate": false
            };
            let lastCheckedIndex = 0;
            this.column_preferences.forEach((col, index) => {
                if (col.checked) {
                    lastCheckedIndex = index;
                }
            });
            let newIndex = 0;
            if (lastCheckedIndex == this.column_preferences.length - 1) {
                this.column_preferences.push(column);
                newIndex = this.column_preferences.length - 1;
            }
            else {
                this.column_preferences.splice(lastCheckedIndex+1, 0, column);
                newIndex = lastCheckedIndex;
            }
            this.refreshColumns();
            this.$nextTick(() => {
                var element = document.getElementById(column.uuid + "_id");
                element.scrollIntoView({ block: 'center' });
            })
        },
        deleteCustom(index) {
            if(!confirm("Are you sure you want to delete this custom column?")) {
                return;
            }
            let column = this.active_columns[index];
            for(var i=0; i < this.column_preferences.length; i++) {
                if (this.column_preferences[i].uuid == column.uuid) {
                    if (this.clearCustomTransformsFilters(i)) {
                        this.clearCustomSortingAndSelection(i);
                        this.column_preferences.splice(i, 1);
                        break;
                    }
                }
            }
            delete column;
            this.refreshColumns();
        },
        countTitle(checked) {
            if(checked == false && this.visible_column_count >= 400)
                return "You'll need to hide another column before you can show this one.";
            else 
                return "";
        },
        highlighter(code) {
            // js highlight example
            return Prism.highlight(code, Prism.languages.sql, "sql");
        },
        modalCloseColumnPreferences(event) {
            if (event !== undefined) {
                event.stopPropagation();
                var clicked_element = event.target;
                if (clicked_element.closest(".dmiux_popup__window")) {
                    // You clicked inside the modal
                    if (clicked_element.id != "x-button" && !(clicked_element.classList.contains("dmiux_popup__cancel")))
                        return;
                }
            }
            // You either clicked outside the modal, or the X Button, or the Cancel Button
            this.column_preferences = [];
            this.active_columns = [];
            this.$root.modals.change_column_preference = false;
            app.explorer.query = app.explorer.valid_query;
        },
        moved(evt) {
            if (evt.oldIndex == evt.newIndex) return;
            this.column_preferences.splice(evt.oldIndex, 1);
            this.column_preferences.splice(evt.newIndex, 0, this.active_columns[evt.newIndex]);
            this.refreshColumns();
        },
        uuid() {
            return ([1e7]+-1e3+-4e3+-8e3+-1e11).replace(/[018]/g, c =>
                (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16)
            );
        }
    },
    computed: {
        visible_column_count() {
            var columns = this.column_preferences.filter((column) => {
                if(column.checked == true) {
                    return column;
                }
            });

            if(columns == null || columns == undefined) 
                return 0;
            else
                return columns.length;
            
        }
    }
}

modals.switch_view = {
    template: '#switch-view-template',
    props: [ 'open', 'view_mode', 'name', 'type', 'switch_view_type', 'view_detail'],
    data: function() {
        return {
            view_name: '',
            view_type: 'normal',
            view_frequency: '',
            view_message: '',
            view_schedule: {
                hour: 0,
                month: 1,
                month_day: 1,
                week_day: 0
            },
            month_days: [ "1", "2", "3", "4", "5", "6", "7", "8", "9", "10", "11", "12", "13", "14", "15", "16", "17", "18", "19", "20", "21", "22", "23", "24", "25", "26", "27", "28" ],
            week_days: {
                "0": "Sunday",
                "1": "Monday",
                "2": "Tuesday",
                "3": "Wednesday",
                "4": "Thursday",
                "5": "Friday",
                "6": "Saturday"
            },
            hours: {
                "0": "12 AM",
                "1": "1 AM",
                "2": "2 AM",
                "3": "3 AM",
                "4": "4 AM",
                "5": "5 AM",
                "6": "6 AM",
                "7": "7 AM",
                "8": "8 AM",
                "9": "9 AM",
                "10": "10 AM",
                "11": "11 AM",
                "12": "12 PM",
                "13": "1 PM",
                "14": "2 PM",
                "15": "3 PM",
                "16": "4 PM",
                "17": "5 PM",
                "18": "6 PM",
                "19": "7 PM",
                "20": "8 PM",
                "21": "9 PM",
                "22": "10 PM",
                "23": "11 PM"
            },
            months: {
                "1": "January",
                "2": "February",
                "3": "March",
                "4": "April",
                "5": "May",
                "6": "June",
                "7": "July",
                "8": "August",
                "9": "September",
                "10": "October",
                "11": "November",
                "12": "December"
            },
            datetime: ''
        }
    },
    watch: {
        open() {
            if(this.open == true) {
                openModal('#modal-switch_view', (event) => true);
                $(document).on("mousedown", "#dmiux_body", this.modalCloseSwitchView);
                $(document).on("keydown", this.modalCloseSwitchView);
            } 
        }
    },
    created() {
        setInterval(() => {
          this.runClock();
        }, 1000)
    },
    methods: {
        modalCloseSwitchView(event) {
            event.stopPropagation();
            if (this.$root.ready == false && this.$parent.modals.switch_view == true) {
                // Page is loading and modal is open, so it is applying changes
                return;
            }
            else if(event.key != undefined) {
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
            this.$parent.modals.switch_view = false;
            this.view_frequency = '';
            this.view_schedule.hour = 0;
            this.view_schedule.month = 1;
            this.view_schedule.month_day = 1;
            this.view_schedule.week_day = 0;
            this.view_name = '';
            this.view_message = '';
            $(document).off("mousedown", "#dmiux_body", this.modalCloseSwitchView);
            $(document).off("keydown", this.modalCloseSwitchView);
            closeModal('#modal-switch-view');
        },
        switchView(){
            this.$root.ready = false;

            var data = {};
            data.view_frequency = this.view_frequency;
            data.view_schedule = this.view_schedule;
            data.view_message = this.view_message;

            let options = FetchHelper.buildJsonRequest(data, 'put');

            fetch(`/internal-api/v1/studio/projects/${this.$root.control_id}/views/${this.$root.explorer.view.id}/switch`, options)
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    this.$root.ready = true;
                    notify.success("View has been switched successfully.");
                    closeModal('#modal-switch-view');
                    this.$parent.modals.switch_view = false;
                    this.$parent.viewTypeSwitched(this.$root.explorer.view.id, json.data);
                })
                .catch((error) => {
                    this.$root.ready = true;
                    closeModal('#modal-switch-view');
                    this.$parent.modals.switch_view = false;
                    ResponseHelper.handleErrorMessage(error, "Unable to switch view types at this time.");
                });
        },
        runClock() {
            this.datetime = DateHelper.getUTCFormattedDate();
        }
    }
};

modals.publish_view = {
    template: '#publish-view-template',
    props: [ 'open', 'view_mode', 'name', 'type', 'schedule', 'rename_view_name' ],
    data: function() {
        return {
            view_name: '',
            view_type: 'normal',
            view_frequency: '',
            view_schedule: {},
            view_message: "",
            view_schedule_defaults: {
                hour: 0,
                month: 1,
                month_day: 1,
                week_day: 0
            },
            month_days: [ "1", "2", "3", "4", "5", "6", "7", "8", "9", "10", "11", "12", "13", "14", "15", "16", "17", "18", "19", "20", "21", "22", "23", "24", "25", "26", "27", "28" ],
            week_days: {
                "0": "Sunday",
                "1": "Monday",
                "2": "Tuesday",
                "3": "Wednesday",
                "4": "Thursday",
                "5": "Friday",
                "6": "Saturday"
            },
            hours: {
                "0": "12 AM",
                "1": "1 AM",
                "2": "2 AM",
                "3": "3 AM",
                "4": "4 AM",
                "5": "5 AM",
                "6": "6 AM",
                "7": "7 AM",
                "8": "8 AM",
                "9": "9 AM",
                "10": "10 AM",
                "11": "11 AM",
                "12": "12 PM",
                "13": "1 PM",
                "14": "2 PM",
                "15": "3 PM",
                "16": "4 PM",
                "17": "5 PM",
                "18": "6 PM",
                "19": "7 PM",
                "20": "8 PM",
                "21": "9 PM",
                "22": "10 PM",
                "23": "11 PM"
            },
            months: {
                "1": "January",
                "2": "February",
                "3": "March",
                "4": "April",
                "5": "May",
                "6": "June",
                "7": "July",
                "8": "August",
                "9": "September",
                "10": "October",
                "11": "November",
                "12": "December"
            },
            datetime: ''
        }
    },
    watch: {
        open() {
            if(this.open == true) {
                ModalHelper.open('modal-publish_view', this.modalClosePublishView);

                if (this.view_mode == 'save') {
                    this.view_name = this.name;
                    this.view_type = this.type;
                    if(Object.keys(this.schedule).length == 0 && this.schedule.frequency == '') {
                        this.view_frequency = '';
                        this.view_schedule = this.view_schedule_defaults;
                    }else{
                        this.view_frequency = this.schedule.frequency;
                        this.view_schedule = this.schedule;
                    }
                } else {
                    this.view_name = '';
                    this.view_type = 'normal';
                    this.view_frequency = '';
                    this.view_schedule = this.view_schedule_defaults;
                }
            } 
        }
    },
    computed:{
        rename_value:{
            get: function() {
                return  this.$parent.rename_view_name;
            },
            set: function(value) {
                return this.$parent.rename_view_name = value;
            },
        }
    },
    created() {
        setInterval(() => {
          this.runClock();
        }, 1000)
    },
    methods: {
        modalClosePublishView(event) {
            if(! ModalHelper.shouldClose(event, { closeButtonId:  'x-button', cancelButtonId: 'dmiux_popup__cancel'})) {
                return;
            }

            if(this.view_message != '') {
                if(! confirm("Do you want to cancel saving? Canceling will clear your changes in this modal.")) {
                    return;
                }
            }

            // You clicked either on the X Button, the Cancel Button , or outside the modal - the modal will close
            this.$parent.modals.publish_view = false;
            app.rename_view_status = false;
            this.reset();
            ModalHelper.close('modal-publish_view', this.modalClosePublishView);
        },
        reset() {
            this.view_name = '';
            this.view_message = '';
            this.view_type = 'normal';
            this.view_frequency = '';
            this.view_schedule.hour = 0;
            this.view_schedule.month = 1;
            this.view_schedule.month_day = 1;
            this.view_schedule.week_day = 0;
        },
        createView() {
            if (this.view_name == "") {
                notify.danger("You must enter a name for your view.");
                return;
            }

            var pattern = /^[a-z][a-z0-9_]*$/;
            if (pattern.test(this.view_name) === false) {
                notify.danger('Invalid character(s) in view name. Name must contain only letters, numbers, and underscores and must start with a letter.');
                return;
            }

            if (this.view_mode == 'save') {
                this.$root.saveView(this.view_frequency, this.view_schedule, this.view_message);
            } else {
                this.$root.publishView(this.view_name, this.view_type, this.view_frequency, this.view_schedule, this.view_message);
            }

            this.$parent.modals.publish_view = false;
            this.reset();
            ModalHelper.close('modal-publish_view', this.modalClosePublishView);
        },
        cleanupName: function () {
            var name = "";
            if(app.rename_view_status == true){
                name = this.rename_value;
            }else{
                name = this.view_name;
            }
            var firstChar = name.charAt(0);
            var regex = new RegExp(/[a-z]/i);
            while(regex.test(firstChar) == false && name.length > 0) {
                name = name.replace(firstChar, '');
                firstChar = name.charAt(0);
            }
            name = name.toLowerCase();
            name = name.trim();
            name = name.replace(" ", "");
            name = name.replace(/\W/g, '');
            name = name.substring(0, 63);
            if(app.rename_view_status == true){
                this.rename_value =  name ;
            }else{
                this.view_name =  name;
            }
           
        },
        updateName: function(){
            if(this.rename_value == ""){
                notify.danger("You must enter a name for your view.");
                return;
            }
            var view_id = app.explorer.view.id;
            var pattern = /^[a-z][a-z0-9_]*$/;
            if (pattern.test(this.rename_value) === false) {
                notify.danger('Invalid character(s) in view name. Name must contain only letters, numbers, and underscores and must start with a letter.');
                return;
            }
            var query = {};
            query.view_name = this.rename_value;
            query.view_id = view_id;
            query.view_name_old = app.explorer.view.view_name;
            query.schema = app.explorer.view.view_schema;
            query.view_type = app.explorer.view.view_type;

            let options = FetchHelper.buildJsonRequest(query, 'put');

            fetch(`/internal-api/v1/studio/projects/${this.$root.control_id}/views/${view_id}/rename`, options)
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    notify.success("View has been renamed.");
                    var url = `/studio/projects/${this.$root.control_id}/tables/${app.explorer.view.view_schema}/${this.rename_value}`;
                    window.location.href = url;
                })
                .catch((error) => {
                    ResponseHelper.handleErrorMessage(error, "Rename view has failed.");
                });
        },
        runClock() {
            this.datetime = DateHelper.getUTCFormattedDate();
        }
    }
};

modals.publish_mssql = {
    template: '#publish-mssql-template',
    props: [ 'open', 'table', 'destination_id', 'class_name' ],
    components: {
        'publisher-scheduling': publisher_scheduling
    },
    data: function() {
        return {
            servers: [],
            databases: [],
            tables: [],
            options: {
                server_id: 0,
                target_database: '',
                target_create_database: '',
                using_new_database: false,
                target_table: '',
                target_create_table: '',
                using_new_table: true,
                truncate_on_publish: false,
                append_timestamp: false,
                using_custom_columns: false
            },
            schedule: {
                frequency: 'daily',
                hour: 0,
                month: 1,
                month_day: 1,
                week_day: 0
            },
            publish_type: 'one_time',
            id: -1
        }
    },
    computed: {
        canSubmit() {
            if (this.options.server_id == 0) {
                return false;
            }

            // Verify database
            if (this.options.using_new_database) {
                if (this.databases.includes(this.options.target_create_database) && (this.$root.explorer.publisher.id == -1 || this.options.orig_target_create_database != this.options.target_create_database)){
                    return false;
                }

                if (this.options.target_create_database == '' || this.options.target_create_database.length > 124) {
                    return false;
                }
            }

            // Verify tables
            if (this.options.using_new_table) {
                if (this.tables.includes(this.options.target_create_table)) {
                    return false;
                }

                if (this.options.target_create_table == '' || this.options.target_create_table.length > 124) {
                    return false;
                }
            }

            return true;
        },
        timestamped_table_name() {
            var d = new Date();
            var ds = d.getFullYear() + '' + 
                (d.getMonth() + 1 + '').padStart(2, '0')  +
                (d.getDate() + '').padStart(2, '0') + 
                (d.getHours() + '').padStart(2, '0') +
                (d.getMinutes() + '').padStart(2, '0') +
                (d.getSeconds() + '').padStart(2, '0');
            return this.options.target_create_table + '_' + ds;
        }
    },
    watch: {
        open() {
            if(this.open == true) {
                this.getMssqlServers();
                if(this.$root.explorer.publisher.id != -1) {
                    this.options = JSON.parse(JSON.stringify(this.$root.explorer.publisher.destination_options));
                    this.options.orig_target_create_database = this.options.target_create_database;
                    this.schedule = JSON.parse(JSON.stringify(this.$root.explorer.publisher.schedule));
                    this.publish_type = "scheduled";
                    this.getDatabases();
                    if(this.options.using_new_database == false) {
                        this.getTables();
                    }
                }
                this.options.using_custom_columns = this.$root.explorer.query.columns.filter((col) => col.added && col.prefix == 'custom').length > 0;
                openModal('#modal-publish_mssql');
                $(document).on("mousedown", "#dmiux_body", this.modalClose);
                $(document).on("keydown", this.modalClose);
            } 
        },
        target_create_table() {
            this.options.target_create_table = this.cleanupName(this.options.target_create_table);
        },
        target_create_database() {
            this.options.target_create_database = this.cleanupName(this.options.target_create_database);
        },
    },
    methods: {
        cleanupName(input) {
            return input.trim()
                .replace(/\s/g, '_').substring(0, 124);
        },
        getMssqlServers() {
            this.$root.ready = false;
            fetch(`/internal-api/v1/studio/projects/${this.$root.control_id}/mssql`)
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    this.$root.ready = true;
                    this.servers = json.data;
                })
                .catch((error) => {
                    this.$root.ready = true;
                    ResponseHelper.handleErrorMessage(error, 'Microsoft SQL servers could not be loaded.');
                });
        },
        getDatabases() {
            this.$root.ready = false;
            fetch(`/internal-api/v1/studio/projects/${this.$root.control_id}/mssql/${this.options.server_id}/databases`)
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    this.$root.ready = true;
                    this.databases = json.data;
                })
                .catch((error) => {
                    this.$root.ready = true;
                    ResponseHelper.handleErrorMessage(error, 'Databases could not be loaded for this Microsoft SQL Server.');
                });
        },
        getTables() {
            this.$root.ready = false;
            fetch(`/internal-api/v1/studio/projects/${this.$root.control_id}/mssql/${this.options.server_id}/databases/${this.options.target_database}/tables`)
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    this.$root.ready = true;
                    this.tables = json.data;
                })
                .catch((error) => {
                    this.$root.ready = true;
                    ResponseHelper.handleErrorMessage(error, 'Tables could not be loaded for this database.');
                });
        },
        serverSelected() {
            if (this.options.server_id == 0) {
                this.options.target_database = '';
                this.options.target_table = '';
                this.databases = [];
                this.tables    = [];
                return;
            }
            
            this.options.target_database    = '';
            this.options.using_new_database = true;
            this.getDatabases();
        },
        databaseSelected() {
            if (this.options.target_database == '') {
                this.options.using_new_database = true;
                this.options.using_new_table    = true;
                this.options.target_table       = '';
                this.tables             = [];
                return;
            }

            if (this.options.using_new_table) {
                this.options.target_table = '';
            }

            this.options.target_table    = '';
            this.options.using_new_table = true;
            this.options.using_new_database = false;

            this.getTables();
        },
        tableSelected() {
            if (this.options.target_table == '') {
                this.options.using_new_table = true;
                return;
            }

            this.options.append_timestamp = false;

            this.options.using_new_table = false;
        },
        publish() {
            if (this.publish_type != 'one_time' && this.options.using_new_table) {
                this.options.append_timestamp = true;
            }

            var post_vars = {
                id: this.$root.explorer.publisher.id,
                server_id: this.options.server_id,
                using_new_database: this.options.using_new_database,
                target_database: this.options.target_database,
                target_create_database: this.options.target_create_database,
                using_new_table: this.options.using_new_table,
                target_table: this.options.target_table,
                target_create_table: this.options.target_create_table,
                append_timestamp: this.options.append_timestamp,
                truncate_on_publish: this.options.truncate_on_publish,

                publish_type: this.publish_type,
                publish_schedule: this.schedule,
                
                query: this.$root.explorer.valid_query
            };

            if(this.options.column_mappings != undefined) {
                post_vars.column_mappings = this.options.column_mappings;
            }

            let options = FetchHelper.buildJsonRequest(post_vars);

            this.$root.ready = false;

            fetch(`/internal-api/v1/studio/projects/${this.$root.control_id}/tables/${this.$root.explorer.query.schema}/${this.table}/publishers/mssql`, options)
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    this.$root.ready = true;
                    if (json.data.redirect) {
                        if(this.$root.explorer.validation_queries.saved_query != this.$root.explorer.validation_queries.current_query) {
                            if(! confirm('You are about to leave the page to map columns. This will remove any changes you have made. Do you wish to continue?')) {
                                return;
                            }
                        }

                        this.$root.updateSavedQuery();
                        window.location = json.data.location;
                    } else {
                        this.$root.updateSavedQuery();
                        this.$root.explorer.publisher.destination_options = JSON.parse(JSON.stringify(this.options));
                        this.$root.explorer.publisher.schedule = JSON.parse(JSON.stringify(this.schedule));
                        if(this.publish_type == 'one_time') {
                            notify.success("Task queued. We'll notify you when it's complete.");
                        } else {
                            var word = this.$root.explorer.publisher.id == -1 ? 'created' : 'updated';
                            notify.success(`Mssql schedule has been ${word} successfully.`);
                        }
                    }

                    this.modalClose();
                })
                .catch((error) => {
                    this.$root.ready = true;
                    ResponseHelper.handleErrorMessage(error, 'An error occurred when attempting to publish.');
                });
        },
        schedule_changed(schedule) {
            this.schedule = schedule;
        },
        publish_type_changed(new_type) {
            this.publish_type = new_type;
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
                        if (clicked_element.id != "x-button" && !(clicked_element.classList.contains("dmiux_popup__cancel")))
                            return;
                    }

                    if(clicked_element.id == 'explorer-loader-backdrop'){
                        return;
                    }
                }
            }
            // You clicked either on the X Button, the Cancel Button , or outside the modal - the modal will close
            this.$parent.modals.publish_mssql = false;

            this.publish_type = 'one_time';
            this.schedule.frequency = 'daily';
            this.schedule.hour = 0;
            this.schedule.month = 1;
            this.schedule.month_day = 1;
            this.schedule.week_day = 0;

            this.options.server_id = 0;
            this.options.target_table = '';
            this.options.target_create_table = '';
            this.options.target_database = '';
            this.options.target_create_database = '';
            this.options.using_new_database = false;
            this.options.using_new_table = true;
            this.options.append_timestamp = false;
            this.options.using_custom_columns = false;
            this.options.truncate_on_publish = false;
            
            $(document).off("mousedown", "#dmiux_body", this.modalClose);
            $(document).off("keydown", this.modalClose);
            closeModal('#modal-publish-mssql');
        }
    }
};

modals.publish_sftp = {
    template: '#publish-sftp-template',
    props: [ 'open', 'table', 'destination_id', 'class_name' ],
    components: {
        'publisher-scheduling': publisher_scheduling
    },
    data: function() {
        return {
            sftp_sites: [],
            options: {
                site_id: 0,
                path: '',
                append_timestamp: false,
                overwrite_existing: false
            },
            publish_type: 'one_time',
            schedule: {
                frequency: 'daily',
                hour: 0,
                month: 1,
                month_day: 1,
                week_day: 0
            },
            id: -1
        }
    },
    computed: {
        canSubmit() {
            if (this.options.site_id == 0) {
                return false;
            }

            if (this.publish_type == "scheduled") {
                if(this.options.overwrite_existing == false && this.options.append_timestamp == false) {
                    return false;
                }
            }

            return true;
        }
    },
    watch: {
        open() {
            if(this.open == true) {
                this.getSFTPSites();
                if(this.$root.explorer.publisher.id != -1) {
                    this.options = JSON.parse(JSON.stringify(this.$root.explorer.publisher.destination_options));
                    this.schedule = JSON.parse(JSON.stringify(this.$root.explorer.publisher.schedule));
                    this.publish_type = "scheduled";
                }
                openModal('#modal-publish_sftp');
                $(document).on("mousedown", "#dmiux_body", this.modalClosePublishSFTP);
                $(document).on("keydown", this.modalClosePublishSFTP);
            } 
        }
    },
    methods: {
        getSFTPSites() {
            this.$root.ready = false;
            fetch(`/internal-api/v1/admin/sftp`)
                .then(response => response.json())
                .then(json => {
                    this.$root.ready = true;
                    this.sftp_sites = json.data;
                });
        },
        publish() {
            if(this.options.site_id == 0) {
                notify.danger("Please choose a sftp site and enter a path.");
                return;
            }
            
            if (this.publish_type == "scheduled") {
                if(this.options.overwrite_existing == false && this.options.append_timestamp == false) {
                    notify.danger("For scheduled jobs, you need to overwrite or append a timestamp.");
                    return;
                }
            }

            this.$root.ready = false;

            var destination_options = {
                'site_id': this.options.site_id,
                'path': this.options.path,
                'append_timestamp': this.options.append_timestamp,
                'overwrite_existing': this.options.overwrite_existing,
                'query': this.$root.explorer.valid_query
            }

            var post_vars = {
                'id': this.$root.explorer.publisher.id,
                'publish_type': this.publish_type,
                'publish_schedule': this.schedule,
                'class_name': this.class_name,
                'destination_id': this.destination_id,
                'destination_options': destination_options
            }

            let options = FetchHelper.buildJsonRequest(post_vars);

            fetch(`/internal-api/v1/studio/projects/${this.$root.control_id}/tables/${this.$root.explorer.query.schema}/${this.table}/publishers/sftp`, options)
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    this.$root.ready = true;
                    if(this.$root.explorer.publisher.id != -1) {
                        this.$root.explorer.publisher.destination_options = destination_options;
                        this.$root.explorer.publisher.schedule = JSON.parse(JSON.stringify(this.schedule));
                    }

                    notify.success(json.message);
                    this.$root.updateSavedQuery();
                    this.modalClosePublishSFTP();
                })
                .catch((error) => {
                    this.$root.ready = true;
                    ResponseHelper.handleErrorMessage(error, 'An error occurred when attempting to publish.');
                });
        },
        cleanPath() {
            this.options.path = this.options.path.replace(/^\/+/, '');
        },
        schedule_changed(schedule) {
            this.schedule = schedule;
        },
        publish_type_changed(new_type) {
            this.publish_type = new_type;
        },
        modalClosePublishSFTP(event) {
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
                        if (clicked_element.id != "x-button" && !(clicked_element.classList.contains("dmiux_popup__cancel")))
                            return;
                    }
                }
            }
            // You clicked either on the X Button, the Cancel Button , or outside the modal - the modal will close
            this.$parent.modals.publish_sftp = false;
            this.options.site_id = 0;
            this.options.path = '';
            this.options.append_timestamp = false;
            this.options.overwrite_existing = false;

            this.publish_type = 'one_time';
            this.schedule.frequency = 'daily';
            this.schedule.hour = 0;
            this.schedule.month = 1;
            this.schedule.month_day = 1;
            this.schedule.week_day = 0;
            
            $(document).off("mousedown", "#dmiux_body", this.modalClosePublishView);
            $(document).off("keydown", this.modalClosePublishView);
            closeModal('#modal-publish-sftp');
        }
    }
};

modals.publish_snapshot = {
    template: '#publish-snapshot-template',
    props: [ 'open', 'table', 'destination_id', 'class_name' ],
    components: {
        "chars": chars,
        'publisher-scheduling': publisher_scheduling
    },
    data: function() {
        return {
            name: "",
            description: "",
            options: {
                append_timestamp: false
            },
            publish_type: 'one_time',
            schedule: {
                frequency: 'daily',
                hour: 0,
                month: 1,
                month_day: 1,
                week_day: 0
            },
            id: -1
        }
    },
    watch: {
        name() {
            this.cleanupName();
        },
        open() {
            if(this.open == true) {
                openModal('#modal-publish_snapshot');
                $(document).on("mousedown", "#dmiux_body", this.modalClosePublishSnapshot);
                $(document).on("keydown", this.modalClosePublishSnapshot);

                if(this.$root.explorer.publisher.id != -1) {
                    this.id = this.$root.explorer.publisher.id;
                    this.options = {... this.$root.explorer.publisher.destination_options};
                    this.publish_type = "scheduled";
                    this.schedule = {... this.$root.explorer.publisher.schedule};
                    this.name = this.options.name;
                    this.description = this.options.description;
                }
            } 
        }
    },
    methods: {
        cleanupName() {
            var firstChar = this.name.charAt(0);
            var regex = new RegExp(/[a-z]/i);
            while(regex.test(firstChar) == false && this.name.length > 0) {
                this.name = this.name.replace(firstChar, '');
                firstChar = this.name.charAt(0);
            }
            this.name = this.name.toLowerCase();
            this.name = this.name.trim();
            this.name = this.name.replace(" ", "");
            this.name = this.name.replace(/\W/g, '');
            this.name = this.name.substring(0, 40); 
        },
        publish() {
            if(!this.name) {
                notify.danger("Please enter in a name for the snapshot.")
                return;
            }
            else if(this.description > 500) {
                notify.danger("Description must be less then 500 characters.")
                return;
            }

            this.cleanupName();

            let options = FetchHelper.buildJsonRequest({
                id: this.id,
                publish_schedule: this.schedule,
                publish_type: this.publish_type,
                query: this.$root.explorer.valid_query,
                name: this.name,
                description: this.description,
                append_timestamp: this.options.append_timestamp
            });

            this.$root.ready = false;
            fetch(`/internal-api/v1/studio/projects/${this.$root.control_id}/tables/${this.$root.explorer.valid_query.schema}/${this.$root.explorer.valid_query.table}/publishers/snapshot`, options)
                .then(FetchHelper.handleJsonResponse)
                .then(json => {                        
                    this.$root.ready = true;
                    if(this.$root.explorer.publisher.id != -1) {
                        this.options.name = this.name;
                        this.options.description = this.description;
                        this.$root.explorer.publisher.destination_options = {... this.options};
                        this.$root.explorer.publisher.schedule = {... this.schedule};
                    }

                    this.modalClosePublishSnapshot();
                    this.$root.updateSavedQuery();
                    if(this.publish_type == 'one_time') {
                        notify.success("Task queued. We'll notify you when it's complete.");
                    } else {
                        notify.success("Snapshot schedule has been created successfully.");
                    }
                })
                .catch((error) => {
                    this.$root.ready = true;
                    ResponseHelper.handleErrorMessage(error, "Failed to publish snapshot");
                });
        },
        schedule_changed(schedule) {
            this.schedule = schedule;
        },
        publish_type_changed(new_type) {
            this.publish_type = new_type;
        },
        modalClosePublishSnapshot(event) {
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
                        if (clicked_element.id != "x-button" && !(clicked_element.classList.contains("dmiux_popup__cancel")))
                            return;
                    }
                }
            }
            // You clicked either on the X Button, the Cancel Button , or outside the modal - the modal will close
            this.$parent.modals.publish_snapshot = false;

            this.reset();

            $(document).off("mousedown", "#dmiux_body", this.modalClosePublishSnapshot);
            $(document).off("keydown", this.modalClosePublishSnapshot);
            closeModal('#modal-publish_snapshot');
        },
        formatCount(count) {
            return parseFloat(count).toFixed().toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        },
        descriptionChanged(value) {
            this.description = value;
        },
        nameChanged(value) {
            this.name = value
        },
        reset() {
            this.options.append_timestamp = false;
            this.name = '';
            this.description = '';

            this.publish_type = 'one_time';
            this.schedule.hour = 0;
            this.schedule.month = 1;
            this.schedule.month_day = 1;
            this.schedule.week_day = 0;
            this.schedule.frequency = 'daily';
        }
    }
};

modals.publish_csv = {
    template: '#publish-csv-template',
    props: [ 'open', 'table', 'destination_id', 'class_name', 'publisher' ],
    components: {
        'publisher-scheduling': publisher_scheduling
    },
    data: function() {
        return {
            // CSV specific data
            options: {
                users: [],
                message: "",
                limit: "",
                append_timestamp: true,
            },
            // Generic publisher data
            publish_type: 'one_time',
            schedule: {
                frequency: 'daily',
                hour: 0,
                month: 1,
                month_day: 1,
                week_day: 0
            },
            id: -1
        }
    },
    watch: {
        open() {
            if(! this.open) {
                return;
            }

            openModal('#modal-publish_csv');
            $(document).on("mousedown", "#dmiux_body", this.modalClose);
            $(document).on("keydown", this.modalClose);

            if(this.$root.explorer.publisher.id != -1) {
                this.id = this.$root.explorer.publisher.id;
                this.options = {... this.$root.explorer.publisher.destination_options};
                this.publish_type = "scheduled";
                this.schedule = {... this.$root.explorer.publisher.schedule};

                // If the limit is 0 (unlimited), show it as blank to not cause any confusion to the end user
                if (this.options.limit == 0) {
                    this.options.limit = '';
                }
            }
        }
    },
    computed: {
        actualUsers() {
            return this.$root.users.filter(user => user['name'] != null);
        }
    },
    methods: {
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
                        if (clicked_element.id != "x-button" && !(clicked_element.classList.contains("dmiux_popup__cancel")))
                            return;
                    }

                    if(clicked_element.id == 'explorer-loader-backdrop'){
                        return;
                    }
                }
            }

            // You clicked either on the X Button, the Cancel Button , or outside the modal - the modal will close
            this.$parent.modals.publish_csv = false;

            this.reset();
            
            $(document).off("mousedown", "#dmiux_body", this.modalClose);
            $(document).off("keydown", this.modalClose);
            closeModal('#modal-publish_csv');
        },
        schedule_changed(schedule) {
            this.schedule = schedule;
        },
        publish_type_changed(new_type) {
            this.publish_type = new_type;
        },
        publish() {
            if(this.options.users.length == 0) {
                notify.danger("Please select at least one user to send this CSV to.");
                return;
            }

            if((this.options.limit != "" && this.options.limit != null) && this.options.limit < 1){
                notify.danger("Please enter a limit greater than zero or none at all.");
                return;
            }
            
            this.options.message = this.options.message == null ? '' : this.options.message;

            let options = FetchHelper.buildJsonRequest({
                id: this.id,
                append_timestamp: this.options.append_timestamp,
                limit: this.options.limit,
                message: this.options.message.substring(0, 300),
                publish_schedule: this.schedule,
                publish_type: this.publish_type,
                query: this.$root.explorer.valid_query,
                users: this.options.users
            });

            app.ready = false;

            fetch(`/internal-api/v1/studio/projects/${this.$root.control_id}/tables/${this.$root.explorer.valid_query.schema}/${this.$root.explorer.valid_query.table}/publishers/csv`, options)
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    this.$root.ready = true;
                    if (this.id > 0) {
                        // Update our local copy
                        this.$root.explorer.publisher.destination_options = { ... this.options};
                        this.$root.explorer.publisher.schedule = JSON.parse(JSON.stringify(this.schedule));
                    }

                    this.modalClose();
                    this.$root.updateSavedQuery();

                    if(this.publish_type == 'publish_once') {
                        notify.success("Task queued. We'll notify you when it's complete.");
                    } else {
                        var word = this.id == 0 ? 'created' : 'updated';
                        notify.success(`Csv schedule has been ${word} successfully.`);
                    }
                })
                .catch((error) => {
                    this.$root.ready = true;
                    ResponseHelper.handleErrorMessage(error, 'An error occurred when attempting to publish.');
                });
        },
        reset() {
            this.options.append_timestamp = true;
            this.options.limit = '';
            this.options.message = '';
            this.options.users = [];
            this.publish_type = 'one_time';
            this.schedule.hour = 0;
            this.schedule.month = 1;
            this.schedule.month_day = 1;
            this.schedule.week_day = 0;
            this.schedule.frequency = 'daily';
        }
    }
};

modals.saved_queries = {
    template: '#saved-query-template',
    props: [ 'open', 'table' ],
    data: function() {
        return {
            saved_query: {
                "id": -1,
                "name": "",
                "description": ""
            }
        }
    },
    watch: {
        open() {
            if(this.open == true) {
                this.saved_query = JSON.parse(JSON.stringify(this.$root.explorer.saved_query));
                openModal('#modal-add_saved_query');
                $(document).on("mousedown", "#dmiux_body", this.modalCloseSavedQuery);
                $(document).on("keydown", this.modalCloseSavedQuery);
            } 
        }
    },
    methods: {
        cleanupName() {
            var firstChar = this.saved_query.name.charAt(0);
            var regex = new RegExp(/[a-z]/i);
            while(regex.test(firstChar) == false && this.saved_query.name.length > 0) {
                this.saved_query.name = this.saved_query.name.replace(firstChar, '');
                firstChar = this.saved_query.name.charAt(0);
            }
            this.saved_query.name = this.saved_query.name.toLowerCase();
            this.saved_query.name = this.saved_query.name.trim();
            this.saved_query.name = this.saved_query.name.replace(" ", "");
            this.saved_query.name = this.saved_query.name.replace(/\W/g, '');
            this.saved_query.name = this.saved_query.name.substring(0, 40); 
        },
        save() {
            if(this.saved_query.name == "") {
                notify.danger("Please enter in a name for the saved query.")
                return;
            }
            else if(this.saved_query.description > 500) {
                notify.danger("Description must be less then 500 characters.")
                return;
            }

            this.cleanupName();
            var options = FetchHelper.buildJsonRequest({
                'name': this.saved_query.name,
                'description': this.saved_query.description,
                'query': this.$root.explorer.valid_query
            });

            var method_name = "create";

            if(this.saved_query.id != -1) {
                var url = `${baseUrl}/internal-api/v1/studio/projects/${app.control_id}/tables/${this.$root.explorer.valid_query.schema}/${this.$root.explorer.valid_query.table}/saved-queries/${this.saved_query.id}`;
                options.method = 'put';
            } else {
                var url = `${baseUrl}/internal-api/v1/studio/projects/${app.control_id}/tables/${this.$root.explorer.valid_query.schema}/${this.$root.explorer.valid_query.table}/saved-queries`;
                options.method = 'post';
            }

            app.ready = false;
            fetch(url, options)
                .then(response => {
                    app.ready = true;
                    return response;
                })
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    if(!app.checkForError(json)) {
                        return;
                    }
                        
                    notify.success(json.message);
                    this.$root.updateSavedQuery();
                    this.modalCloseSavedQuery();
                })
                .catch((error) => {
                    ResponseHelper.handleErrorMessage(error, "Failed to create saved query");
                });
        },
        modalCloseSavedQuery(event) {
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
                        if (clicked_element.id != "x-button" && !(clicked_element.classList.contains("dmiux_popup__cancel")))
                            return;
                    }
                }
            }
            // You clicked either on the X Button, the Cancel Button , or outside the modal - the modal will close
            this.$parent.modals.saved_queries = false;
            this.saved_query.id = -1;
            this.saved_query.name = "";
            this.saved_query.description = "";
            
            $(document).off("mousedown", "#dmiux_body", this.modalCloseSavedQuery);
            $(document).off("keydown", this.modalCloseSavedQuery);
            closeModal('#modal-add_saved_query');
        }
    }
};

modals.custom_sort = {
    template: '#custom-sort-template',
    props: [ 'open' ],
    data() {
        return {
            edit_mode: false,
            sort_expression: '',
            closeConfig: {
                closeButtonId:  'button-close_custom-sort',
                cancelButtonId: 'button-cancel_custom-sort',
            }
        }
    },
    watch: {
        open() {
            this.sort_expression = this.$root.explorer.query.order.custom_expression;
            if(this.open == true) {
                ModalHelper.open('modal-custom_sort', this.modalClose);
            } else {
                ModalHelper.close('modal-custom_sort', this.modalClose);
            }
        }
    },
    methods: {
        apply() {
            this.$root.explorer.query.order.custom_expression = this.sort_expression;
            this.$root.ready = false;
            this.$root.getRecords({ 
                success: () => {
                    this.$root.modals.custom_sort = false;
                    notify.success("Your custom sort expression has been applied.")
                },
                failure: () => {
                    notify.danger("Your custom sort expression could not be applied and may be invalid.");
                }
            });
        },
        highlighter(code) {
            return Prism.highlight(code, Prism.languages.sql, "sql");
        },
        discardChanges() {
            const message = "You have unsaved changes. All changes will discarded. Continue?";
            if (this.sort_expression !== '' && this.$root.explorer.query.order.custom_expression != this.sort_expression) {
                if (confirm(message)) {
                    this.comment = "";
                    return true;
                }
                return false;
            }
            return true;
        },
        modalClose(event) {
            if(ModalHelper.shouldClose(event, this.closeConfig) && this.discardChanges()) {
                this.$root.modals.custom_sort = false;
            }
        }
    }
}

var ribbon = {
    template: '#component-ribbon',
    props: [
        'control_id',
        'modules',
        'mappings',
        'selected_column',
        'selected_column_data_type',
        'selected_column_unstructured',
        'comments',
        'flags',
        'attachments',
        'added_columns',
        'mobile',
        'user',
        'users',
        'table',
        'view_mode'
    ],
    data: function() {
        return {
            destinationSchemaTables: [],
            destinationSchemaColumns: [],
            applyFilters: true,
            columnComments: []
        }
    },
    methods: {
        removeFlag() {
            if(app.explorer.selected_prefix != app.explorer.query.prefix) {
                alert("You cannot add flags to joined or custom columns.");
                return;
            };
            
            this.$root.ready = false;

            let options = FetchHelper.buildJsonRequest({
                column: this.selected_column
            }, 'delete');

            fetch(`${baseUrl}/internal-api/v1/studio/projects/${this.control_id}/tables/${this.$parent.explorer.query.schema}/${this.$parent.explorer.query.table}/flags`, options)
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    app.ready = true;
                    notify.success(`Flag dismissed from ${this.selected_column}.`);
                    app.closeModal();
                    app.getFlags();
                    app.getComments();
                })
                .catch((error) => {
                    app.ready = true;
                    ResponseHelper.handleErrorMessage(error, 'Flag could not be dismissed.');
                });
        },
        addFlag() {
            if(!app.isColumnSelected()) { return };
            if(app.explorer.selected_prefix != app.explorer.query.prefix) { alert("You cannot add flags to joined or custom columns."); return; };
            openModal('#modal-add_edit_flag', (event) => {
                event.stopPropagation();
                var clicked_element = event.target;
                if (clicked_element.closest(".tribute-container")) {
                    return true;
                }

                return false;
            });
        },
        addComment() {
            if(!app.isColumnSelected()) { return };
            if(app.explorer.selected_prefix != app.explorer.query.prefix) { alert("You cannot add comments to joined or custom columns."); return; };
            this.$root.modals.add_comment = true;
        },
        copyColumn() {
            if(!app.isColumnSelected()) { return };
            app.modals.copy_column = true;
        },
        addTransformation() {
            if (app.explorer.query.unions.length > 0) {
                notify.danger("Transformations are disabled when unions are applied.");
                return;
            }
            if(!app.isColumnSelected()) { return };
            if(app.explorer.selected_column_data_type == "boolean") { alert("You cannot add a transformation on a boolean value"); return; };
            openModal('#modal-add_transformation', function() { return true; } );
            app.$refs.transformation_modal.setOperators();
            app.$refs.transformation_modal.addCloseEvents();
        },
        viewAllMappings() {
            if(!app.isColumnSelected()) { return };
            if(app.explorer.selected_prefix != app.explorer.query.prefix) { alert("Mappings do not exist on joined or custom columns."); return; };
            app.modals.map_column = true;
            this.addMapping(0);
            openModal('#modal-map_column');
        },
        addMapping(module_id) {
            if(!app.isColumnSelected()) { return };
            if(app.explorer.selected_prefix != app.explorer.query.prefix) { alert("You cannot add mappings to joined or custom columns."); return; };
            app.modals.map_column = true;
            app.mapping.selected_mapping_module = module_id;
            openModal('#modal-map_column');
        },
        clearFilters() {
            app.explorer.query.filters = [];
            app.getRecords();
        },
        addCustomFilter() {
            if (app.explorer.query.unions.length > 0) {
                notify.danger("Filters are disabled when unions are applied.");
                return;
            }
            if(!app.isColumnSelected()) { return };
            app.modals.custom_filter = true;
        }
    }
}

var table_summary = {
    template: "#component-table-summary",
    props: [
        'control_id',
        'table',
        'schema',
        'records',
        'record_counts',
        'type',
        'active_users',
        'filters',
        'viewing_type',
        'view_mode',
        'view',
        'publishing_destinations',
        'pending_count',
        'mobile',
        'notes'
    ],
    data: function() {
        return {
            rename_view_name:'',
            modals: {
                publish_sftp: false,
                publish_snapshot: false,
                publish_view: false,
                publish_mssql: false,
                publish_csv: false,
                saved_queries: false
            },
            supported_publishers: {
                'publish_sftp': 'Sftp',
                'publish_mssql': 'Mssql',
                'publish_snapshot':'Snapshot',
                'publish_csv': 'Csv',
                'publish_view': 'View',
            },
            dview: {
                view_name: '',
                view_type: 'normal',
                switch_view_type : '',
                view_definition: {
                    view_schedule: {
                        frequency: '',
                        hour: 0,
                        month: 1,
                        month_day: 1,
                        week_day: 0
                    }
                }
            },
            destination_id: 0,
            destination_class_name: "",
            current_view_type:"",
            pollingForStatus: null,
            view_history: {
                records: [],
                shown: false,
                page: 1,
                total_pages: 0,
                users: []
            }
        }
    },
    components: {
        "publish-view": modals.publish_view,
        "publish-sftp": modals.publish_sftp,
        "publish-snapshot": modals.publish_snapshot,
        "switch-view": modals.switch_view,
        "publish-mssql": modals.publish_mssql,
        "publish-csv": modals.publish_csv,
        "saved-queries": modals.saved_queries
    },
    methods: {
        reset() {
            this.view_history.shown = false;
        },
        formatCount(count) {
            return parseFloat(count).toFixed().toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        },
        formatAge(date) {
            let history_date = new Date(date);
            let today = new Date();
            let diff = today - history_date;

            let seconds = Math.round(diff / 1000);
            let minutes = 0;
            let hours = 0;
            let days = 0;
            let text = '';
            if (seconds < 60) {
                text = `${seconds} ${StringHelper.pluralize(seconds, 'second')} ago`
            }
            if (seconds >= 60) {
                minutes = Math.round(diff / 60000);
                text = `${minutes} ${StringHelper.pluralize(minutes, 'minute')} ago`
            }
            if (minutes >= 60) {
                hours = Math.round(diff / 3600000);
                text = `${hours} ${StringHelper.pluralize(hours, 'hour')} ago`
            }
            if (hours > 24) {
                days = Math.round(hours / 24);
                text = `${days} ${StringHelper.pluralize(days, 'day')} ago`
            }
            if (days > 7) {
                return history_date.toLocaleDateString() + ' at ' + history_date.toLocaleTimeString([], { timeStyle: "short" });
            } else {
                return text;
            }
        },
        pluralize(number, string, suffix) {
            return StringHelper.pluralize(number, string, suffix);
        },
        formatString(string) {
            if(string == undefined && string == null)
                return "";

            if(string.length < 15)
                return string;
            else 
                return string.substr(0, 15) + "..."; 
        },
        formatValue(value) {
            if(value == undefined && value == null)
                return "";
            if (typeof value == 'string' || typeof value == 'number') {
                value = value.toString();
                if(value.length < 15)
                    return value;
                else 
                    return value.substr(0, 15) + "...";  
            }
            else {
                if(value.type == "string") {
                    var newVal = [];
                    for (var i=0; i<value.info.length; i++) {
                        newVal[i] = value.info[i];
                        if (newVal[i].length > 14)
                            newVal[i] = newVal[i].substr(0, 15) + "...";
                    }
                    return newVal;
                }
                else if(value.type == "column") {
                    var newVal = { "column": value.info.column, "table": value.info.table };
                    if(newVal.column.length > 15)
                        newVal.column = newVal.column.substr(0, 15) + "...";

                    if(newVal.table.length > 15)
                        newVal.table = newVal.table.substr(0, 15) + "...";  

                    return "(SELECT " + newVal.column + " FROM " + newVal.table + ")";
                }
                else if(value.type == "interval") {
                    try {
                        if (typeof value.info == 'string') {
                            var interval_val = JSON.parse(value.info); 
                            return "now() " + interval_val.direction + " (" + interval_val.time + " " + interval_val.type + ")";
                        }
                        else {
                            var high_val = JSON.parse(value.info.high_val); 
                            var low_val = JSON.parse(value.info.low_val);
                            return "now() " + low_val.direction + " (" + low_val.time + " " + low_val.type + ") and now() " + high_val.direction + " (" + high_val.time + " " + high_val.type + ")";
                        }
                    }
                    catch(e) {
                        return "";
                    }
                }
                else if(value.type == "manual") {
                    return value.info.low_val.substr(0, 15) + "..." + " and " + value.info.high_val.substr(0, 15) + "...";
                }
                else {
                    var newVal = [];
                    for (var i=0; i<value.length; i++) {
                        newVal[i] = value[i];
                        if (newVal[i].length > 14)
                            newVal[i] = newVal[i].substr(0, 15) + "...";
                    }
                    return newVal; 
                }
            }
        },
        changeGroupBy() {
            var check = this.$root.explorer.query.filters.filter((filter) => {
                if(filter.prefix == "aggregate" || filter.is_aggregate == true)
                    return filter;
            });

            if(check.length > 0) {
                notify.danger("You have filters on aggregate columns.  Please remove filters on aggregate columns before ungrouping.");
                return;
            }

            for(const [key, value] of Object.entries(this.$root.explorer.query.transformations)) {
                if (Array.isArray(value) && value.length > 0) {
                    if(value[0].is_aggregate == true || value[0].prefix == "aggregate") {
                        notify.danger("You have transformations on aggregate columns.  Please remove transformations on aggregate columns before ungrouping.");
                        return;
                    }
                }
            }

            this.$root.explorer.query.is_grouped = !this.$root.explorer.query.is_grouped;
            this.$root.getRecords();
        },
        publishView() {
            if (this.view_mode == 'save') {
                this.dview.view_name = this.view.view_name;
                this.dview.view_type = this.view.view_type;

                if (this.view.schedule != undefined) {
                    this.dview.view_definition.view_schedule = this.view.schedule;
                } else {
                    this.dview.view_definition.view_schedule = {
                        frequency: '',
                        hour: 0,
                        month: 1,
                        month_day: 1,
                        week_day: 0
                    };
                }
            }
            this.modals.publish_view = true; 
        },
        publish(id, class_name) {
            this.destination_id = id;
            this.destination_class_name = class_name;

            // Find our supported publisher, and if it's found, load up our modal
            var index = Object.keys(this.supported_publishers).find(key => this.supported_publishers[key] === class_name);

            if (index != undefined) {
                this.modals[index] = true;
            }
        },
        refreshView() {
            this.$root.ready = false;
            // Note:  the 'control_id' is really the project_id
            fetch(`/internal-api/v1/studio/projects/${this.control_id}/views/${this.view.id}/refresh`, { method: 'put' })
                .then(response => response.json())
                .then(json => {
                    this.$root.ready = true;
                    notify.success("View has been refreshed.");
                })
                .catch((error) => {
                    this.$root.ready = true;
                    ResponseHelper.handleErrorMessage(error, "The view cannot be refeshed right now.");
                });
        },
        recalculate() {
            this.$root.ready = false;
            fetch(`${baseUrl}/Tables/recalculate/${this.control_id}/${encodeURIComponent(this.table)}/${encodeURIComponent(this.schema)}`)
            .then(response => response.json())
            .then(json => {
                if (json.status == "ok") {
                    app.explorer.record_count = json.data.total;
                    app.explorer.type = json.data.total_type;
                    this.$root.ready = true;
                }
            });  
        },
        editView() {
            this.$root.ready = false;
            app.explorer.origin_query = app.explorer.query;
            var view = app.explorer.view.view_definition;
            app.explorer.prefix_list = [];

            if (view.joins.length > 0) {
                this.$root.explorer.viewing_type = "Join";

                view.joins.map(join => {
                    if (join.uuid == undefined) {
                        join.uuid = this.uuid();
                    }
                });
            }
            else {
                this.$root.explorer.viewing_type = "Standard";
            }

            app.explorer.query = view;
            
            app.explorer.query.is_grouped = view.is_grouped;
            
            if (app.explorer.query.transformations.length == 0) {
                app.explorer.query.transformations = {};
            }

            app.clearSelectedColumn();
            this.$root.$refs.toolbar.selected_table = view.table;
            window.history.pushState(
                '',
                view.table,
                `/studio/projects/${this.control_id}/tables/${view.schema}/${view.table}`
            );
            app.explorer.view_mode = 'save';
            app.rebuildTable();
        },
        deleteView(type, ignore_warning = false) {
            if(ignore_warning == false) {
                if(!confirm("Are you sure you want to delete this view? This cannot be undone.")) {
                    return;
                }
            }

            this.$root.ready = false;
            fetch(`/internal-api/v1/studio/projects/${this.control_id}/views/${this.view.id}?ignore_warning=${ignore_warning}`, { method: 'delete' })
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    this.$root.ready = true;
                    if(ignore_warning == false) {
                        if(json.data == "warning") {
                            if(! confirm(json.message)) {
                                return;
                            } else {
                                this.deleteView(type, true);
                                return;
                            }
                        }
                    }

                    this.$root.updateSavedQuery();
                    window.location = `/studio/projects/${this.control_id}?message=${encodeURI("The view was deleted.")}&message_type=${encodeURI("success")}`
                })
                .catch((error) => {
                    this.$root.ready = true;
                    ResponseHelper.handleErrorMessage(error, "The view failed to delete.");
                });
        },
        saveView() {
            app.saveView();
        },
        switchView(){
            var current_view_type = "";
            if(app.explorer.view.view_type == 'materialized') {
                current_view_type = "materialized";
            } else {
                current_view_type = "normal";
            }

            if(current_view_type != "materialized") {
                //if view switch is from normal to materialized then modal opens and ask for frequency
                this.dview.switch_view_type = 'materialized';
            } else {
                //if view switch is from materialized to normal
                this.dview.switch_view_type = 'normal';
            }

            openModal("#modal-switch_view");
            this.modals.switch_view = true; 
        },
        viewTypeSwitched(view_id, new_view_type) {
            this.$root.explorer.view.view_type = new_view_type;
            this.$root.explorer.selected_view.table_type = new_view_type == 'normal' ? 'View' : 'Materialized View';
            this.$root.checkIfStudioView();
            this.loadViewHistoryDetails(view_id, () => {
                this.$root.ready = true;
            });
        },
        seePreviousVersion(action = "") {
            if(action == "filter") {
                this.view_history.page = 1;
            } else if(action == "page_up") {
                if(this.view_history.page + 1 > this.view_history.total_pages) {
                    return
                }

                this.view_history.page++;
            } else if(action == "page_down") {
                if(this.view_history.page - 1 < 1) {
                    return
                }

                this.view_history.page--;
            }

            this.$root.ready = false;
            fetch(`/internal-api/v1/studio/projects/${this.control_id}/views/${this.view.id}/history?page=${this.view_history.page - 1}`)
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    this.$root.ready = true;
                    this.view_history.shown = true;
                    this.view_history.records = json.data.view_history;
                    this.view_history.users = json.data.users;
                    this.view_history.total_pages = json.data.pages;
                })
                .catch((error) => {
                    this.$root.ready = true;
                    ResponseHelper.handleErrorMessage(error, "Unable to retrieve view history.");
                });
        },
        showSummary() {
            if(Object.keys(this.$root.explorer.origin_query).length > 0) {
                notify.danger("You are currently previewing a view's previous version. Please cancel your preview before leaving.");
                return;
            }
            
            this.view_history.page = 1;
            this.view_history.shown = false;
        },
        loadViewHistoryDetails(id, callback) {
            this.$root.ready = false;
            fetch(`/internal-api/v1/studio/projects/${this.control_id}/views/${this.$root.explorer.view.id}/history/${id}`)
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    let query = json.data.view_definition_json;         
                    callback(query);
                })
                .catch((error) => {
                    this.$root.ready = true;
                    notify.danger("Could not load view history details. Preview or restoration failed.");
                });
        },
        previewView(id) {
            if(Object.keys(this.$root.explorer.origin_query).length > 0) {
                notify.danger('Please cancel current preview before previewing another version.');
                return;
            }

            this.$root.ready = false;
            this.loadViewHistoryDetails(id, (query) => {
                this.$root.explorer.origin_query = this.$root.explorer.query;
                this.$root.explorer.query = query;
                
                if (query.joins.length == 0) {
                    this.$root.explorer.viewing_type = "Standard";
                } else {
                    this.$root.explorer.viewing_type = "Join";
                }

                this.$root.getProjectTableColumns(() => {
                    this.$root.getRecords({ 
                        failure: () => {
                            this.$root.explorer.query = this.$root.explorer.origin_query;
                            this.$root.explorer.origin_query = {};
                            notify.danger("Previewing this version has failed. This could be due to changes made to the dependent table(s).");
                        }
                    });
                });
                
            })
        },
        restoreView(id, view_type, view_message) {
            this.$root.ready = false;
            this.loadViewHistoryDetails(id, (query) => {
                query.view_type = view_type;
                query.view_message = view_message;

                let options = FetchHelper.buildJsonRequest(query, 'put');

                this.$root.ready = false;
                fetch(`/internal-api/v1/studio/projects/${this.control_id}/views/${this.$root.explorer.view.id}`, options)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.$root.ready = true;
                        this.$root.updateSavedQuery();
                        notify.success("Version has been restored.");
                        location.reload();
                    })
                    .catch((error) => {
                        this.$root.ready = true;
                        notify.danger("Restoring this version has failed. This could be due to changes made to the dependent table(s).");
                    });
            });
        },
        renameView() {
            app.rename_view_status = true;
            this.modals.publish_view = true;
            this.rename_view_name = app.explorer.view.view_name; 
        },
        addSavedQuery() {
            this.modals.saved_queries = true;
        },
        checkForViewPublisher(callback) {
            this.$root.ready = false;
            fetch(`/internal-api/v1/studio/projects/${this.$root.control_id}/tables/${this.$root.explorer.view.view_schema}/${this.$root.explorer.view.view_name}/publishers/check`)
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    this.$root.ready = true;
                    if(json.data == true) {
                        if(!confirm(`${this.$root.explorer.view.view_name} has a scheduled publish job. Any edits made to this view will likely break the publish job. Do you wish to continue?`)) {
                            return;
                        }
                    }

                    callback();
                })
                .catch((error) => {
                    this.$root.ready = true;
                    ResponseHelper.handleErrorMessage(error, "Unable to retrieve publishing data.");
                }); 
        },
        editNote(note_id) {
            this.$root.$root.modals.table_notes = true;
            this.$root.$root.table_note_id = note_id;
            this.$root.$root.show_note_id = note_id;
        },

        deleteNote(id) {
            if(! confirm("Are you sure you want to delete this note? This cannot be undone.")) {
                return;
            }

            fetch(`${baseUrl}/internal-api/v1/studio/projects/${this.control_id}/tables/${this.$root.explorer.query.schema}/${this.$root.explorer.query.table}/notes/${id}`, {method: 'delete'})
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    notify.success(json.message);
                    this.$root.getTableNotes();
                })
                .catch((error) => {
                    ResponseHelper.handleErrorMessage(error, 'There was a problem deleting the note.');
                });
        },

        setNoteExpanded(note_id, expanded) {
            for (i in this.$root.table_notes) {
                if(this.$root.table_notes[i].id == note_id) {
                    this.$root.table_notes[i].is_expanded = expanded;
                }
            }
        },
        uuid() {
            return ([1e7]+-1e3+-4e3+-8e3+-1e11).replace(/[018]/g, c =>
                (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16)
            );
        },
        async isNotesOverflowing(note_id, overflowing) {
            await this.$nextTick();

            var el = document.getElementById("table-summary-notes_body");
            if(el != undefined) {
                if(overflowing) {
                    return true;
                }

                var is_overflowing = el.clientWidth < el.scrollWidth || el.clientHeight < el.scrollHeight;
                this.$root.table_notes.map(function(note) {
                    if(note.id == note_id) {
                        note.is_overflowing = is_overflowing;
                    }
                    return note;
                });
            }
        },
        getColumnName(filter) {
            let column_name = '';
            if (this.viewing_type == 'Join') {
                column_name = filter.prefix + '_';
                column_name += filter.column;
            } else {
                if (filter.alias == null || filter.alias == '') {
                    column_name = filter.column;
                } else {
                    column_name = filter.alias;
                }
            }
            return column_name;
        }
    }
}

var records = {
    template: '#component-records',
    components: {
        'table-summary': table_summary,
        'unstructured-stage': unstructured_stage,
    },
    props: [
        'table',
        'schema',
        'control_id',
        'record_counts',
        'type',
        'selected_column',
        'selected_column_index',
        'selected_prefix',
        'active_users',
        'flags',
        'comments',
        'transformations',
        'mappings',
        'attachments',
        'records', 
        'columns',
        'page_amt',
        'viewing_type',
        'filters',
        'mobile',
        'pivoted',
        'view_mode',
        'view',
        'pending_count',
        'notes'
    ],
    data: function() {
        return {
            column: "",
            tooltip_title: ""
        }
    },
    watch: {
        table() {
            this.column = "";
        },
        schema() {
            this.column = "";
        }
    },
    methods: {
        changeSelectedColumnValues(alias, type, sql_definition, prefix, name, index, is_aggregate, unstructured) {
            app.explorer.selected_alias = alias;
            app.explorer.selected_sql_definition = sql_definition;
            app.explorer.selected_is_aggregate = is_aggregate;
            app.explorer.selected_column_data_type = type;
            app.explorer.selected_prefix = prefix;
            app.explorer.selected_column = name;
            app.explorer.selected_column_index = index;
            app.explorer.selected_column_unstructured = unstructured;
        },
        setHoverCol(column) {
            this.tooltip_title = "Type - " + column.data_type;
            if (column.character_maximum_length != null) {
                this.tooltip_title = this.tooltip_title + ", Length - " + column.character_maximum_length;
            }
            if (column.numeric_precision != null) {
                this.tooltip_title = this.tooltip_title + ", Precision - " + column.numeric_precision;
            }
        },
        formatString(string) {
            if(string == undefined && string == null)
                return "";

            if(string.length < 15)
                return string;
            else 
                return string.substr(0, 15) + "..."; 
        },
        formatValue(value) {
            if(value == undefined && value == null)
                return "";

            if (typeof value == 'string') {
                value = value;
                if(value.length < 15)
                    return value;
                else 
                    return value.substr(0, 15) + "...";  
            }
            else {
                if(value.type == "string") {
                    var newVal = [];
                    for (var i=0; i<value.info.length; i++) {
                        newVal[i] = value.info[i];
                        if (newVal[i].length > 14)
                            newVal[i] = newVal[i].substr(0, 15) + "...";
                    }
                    return newVal;
                }
                else if(value.type == "column") {
                    var newVal = { "column": value.info.column, "table": value.info.table };
                    if(newVal.column.length > 15)
                        newVal.column = newVal.column.substr(0, 15) + "...";

                    if(newVal.table.length > 15)
                        newVal.table = newVal.table.substr(0, 15) + "...";  

                    return "(SELECT " + newVal.column + " FROM " + newVal.table + ")";
                }
                else if(value.type == "interval") {
                    try {
                        if (typeof value.info == 'string') {
                            var interval_val = JSON.parse(value.info); 
                            return "now() " + interval_val.direction + " (" + interval_val.time + " " + interval_val.type + ")";
                        }
                        else {
                            var high_val = JSON.parse(value.info.high_val); 
                            var low_val = JSON.parse(value.info.low_val);
                            return "now() " + low_val.direction + " (" + low_val.time + " " + low_val.type + ") and now() " + high_val.direction + " (" + high_val.time + " " + high_val.type + ")";
                        }
                    }
                    catch(e) {
                        return "";
                    }
                }
                else if(value.type == "manual") {
                    return value.info.low_val.substr(0, 15) + "..." + " and " + value.info.high_val.substr(0, 15) + "...";
                }
                else {
                    var newVal = [];
                    for (var i=0; i<value.length; i++) {
                        newVal[i] = value[i];
                        if (newVal[i].length > 14)
                            newVal[i] = newVal[i].substr(0, 15) + "...";
                    }
                    return newVal; 
                }
            }
        },
        isNumberColumn(column_name)
        {
            var type = app.explorer.query.columns.filter(function(filter) { 
                if(filter.target_column_name == column_name || filter.alias == column_name) {
                    return filter;
                }
            });

            if(type.length > 0)
                type = type[0];
            else 
                return false;

            if(type.data_type == "int" || 
               type.data_type == "integer" || 
               type.data_type == "bigint" || 
               type.data_type == "float" || 
               type.data_type == "serial" || 
               type.data_type == "bigserial" || 
               type.data_type == "numeric" ||
               type.data_type == "decimal") {
                return true;
            }
            else {
                return false;
            }
        },
        isJson(str, column) {

            try {
                if(Number.isInteger(str) || !isNaN(str) || str == '' || str == null){
                    return false;
                }

                var check = JSON.parse(str);
                if (check && typeof check === "object" && (column.data_type == 'json' || column.data_type == 'jsonb')) {
                    return true;
                }

                return false;
            }
            catch (e) {
                try {
                    check = JSON.parse(JSON.stringify(str));

                    if (check && typeof check === "object" && (column.data_type == 'json' || column.data_type == 'jsonb')) {
                        return true;
                    }
                }
                catch (e2) {
                    return false;
                }

                return false;
            }
        },
        hideShowArrows() {
            var element = document.getElementById("datatable_scroll");

            if(element != null) {
                var scrollWidth = element.scrollWidth;
                var scrollLeft = element.scrollLeft;
                var outerWidth = $("#datatable_scroll").outerWidth();

                if(scrollWidth - scrollLeft == Math.round(outerWidth)) {
                    $(".records-right").removeClass("dmiux_data-table__arrow_visible");
                }
                else if (scrollLeft == 0) {
                    $(".records-left").removeClass("dmiux_data-table__arrow_visible");
                    $(".records-right").addClass("dmiux_data-table__arrow_visible");
                }
                else {
                    $(".records-left").addClass("dmiux_data-table__arrow_visible");
                    $(".records-right").addClass("dmiux_data-table__arrow_visible");
                }
            }
        },
        sortData(column, prefix, alias, sql_definition) {
            if (this.$root.explorer.query.order.custom_expression !== undefined && this.$root.explorer.query.order.custom_expression !== '') {
                if (!confirm('You have a custom expression defined. Would you like to clear it and sort by the selected column instead?')) {
                    return;
                }
                this.$root.explorer.query.order.custom_expression = '';
            }
            this.$root.explorer.query.order.order_column = column;
            this.$root.explorer.query.order.prefix = prefix;
            this.$root.explorer.query.order.alias = alias;
            this.$root.explorer.query.order.sql_definition = sql_definition;

            if(this.$root.explorer.query.order.order_type == "asc")
                this.$root.explorer.query.order.order_type = "desc";
            else 
                this.$root.explorer.query.order.order_type = "asc";

            app.getRecords(null, false);
        },
        isSortColumn(column) {
            let clicked_column = column.prefix + '.' + column.column_name;
            let current_column = this.$root.explorer.query.order.prefix + '.' + this.$root.explorer.query.order.order_column;
            if ((this.$root.explorer.query.order.custom_expression == undefined || this.$root.explorer.query.order.custom_expression == '')
               && (clicked_column == current_column)) {
                return true;   
            }
            return false;
        },
        searchPageNum(event) {
            var page = Number(event.target.value);

            if(page < 1 || page > app.explorer.page_amt || page == '')
            {
                app.explorer.page_num = 1;
                notify.send(
                    'You submitted an invalid page number.',
                    'danger'
                );
                return;
            }
            app.explorer.page_num = page;
            app.getRecords();
        },
        pageBack() {
            if(app.explorer.page_num == 1) {
                return;
            }

            app.explorer.page_num = app.explorer.page_num - 1;
            app.getRecords();
        },
        pageNext() {
            if(app.explorer.page_amt == app.explorer.page_num){
                return;
            }

            app.explorer.page_num = app.explorer.page_num + 1;
            app.getRecords();
        },
        formatCount(count) {
            return parseFloat(count).toFixed().toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        },
        get_type(type) {
            var search_type = { like: "like", notlike: "not like", equals: "=", notequals: "!=", in: "in", notin: "not in"};
            return search_type[type];
        },
        checkWhere(col, for_icon = false) {
            var matches = this.filters.filter(function(filter) { 
                var filter_column = '';
                if (app.explorer.viewing_type == 'Join' && filter.prefix != "custom") {
                    filter_column = filter.prefix + "_" + filter.column;
                }
                else {
                    filter_column = filter.column;
                }
                if(filter_column == col || filter.alias == col) {
                    return filter;
                }
            });
            if (matches.length > 0 || (! for_icon && this.$root.explorer.sent_column.replace(' ', '') == col.replace(' ', '').replace(/'/g, '')))
                return true;
            else
                return false;
        },
        getColumnPrefix(column_name) {
            for(var i=0; i < app.explorer.prefix_list.length; i++) {
                var prefix = app.explorer.prefix_list[i];
                var index = column_name.indexOf(prefix + "_");
                if (index == -1) {
                    index = column_name.indexOf(prefix + ".");
                }
                if (index == 0 && prefix !== '') {
                    return prefix;
                }
            }
            if (app.explorer.viewing_type != 'Join') {
                return app.explorer.query.prefix;
            }
            return '';
        },
        getColumnName(prefix, column_name) {
            var index = column_name.indexOf(prefix);
            if (index == 0 && prefix !== '') {
                return column_name.substring(prefix.length+1);
            }
            else {
                return column_name;
            }
        },
        getColumnDataType(prefix, column_name) {
            for (var i=0; i < app.explorer.query.columns.length; i++) {
                if (app.explorer.query.columns[i].prefix == prefix && app.explorer.query.columns[i].column_name == column_name) {
                    return app.explorer.query.columns[i].data_type;
                }
            }
            return "";
        },
        getColumnMappingTitle(table_name, column_name) {
            let module_name = this.mappings[table_name + '_' + column_name][0].module_name;
            if (module_name == null) {
                module_name = '';
            }
            else {
                module_name = module_name + ' ';
            }
            let destination_table_name = this.mappings[table_name + '_' + column_name][0].destination_table_name;
            if (destination_table_name == null) {
                destination_table_name = '';
            }
            let title = 'Mapped to ' + module_name + destination_table_name;
            return title;
        },
        hasNoRecords() {
            if (this.$root.ready !== true) {
                return false;
            }

            if (! ['', 'edit'].includes(this.view_mode)) {
                return false;
            }

            return this.records.length == 0;
        }
    }
}

var toolbar = {
    template: '#component-toolbar',
    props: [
        'breadcrumbs',
        'buttons',
        'tables',
        'table_name',
        'control_id',
        'record_counts',
        'modified',
        'viewing_type',
        'completed',
        'mobile',
        'schema'
    ],
    data: function() {
        return {
            saved_value: ""
        }
    },
    methods: {
        selectTable: function (event) {
            if(this.$root.explorer.validation_queries.saved_query != this.$root.explorer.validation_queries.current_query) {
                if(! confirm("The changes you have made have not been saved or published and will be discarded. Do you still want to switch tables?")) {
                    return;
                }
            }

            var catalog = event.target.value.split(".")[0];
            var table_name = event.target.value.split(".")[1];
            var table_arr = this.tables.filter(function(table){ 
                if(table.table_name == table_name && table.table_catalog == catalog){
                    return table;
                }
            });

            if(table_arr.length > 0){
                var schema = table_arr[0].table_schema;
                if(table_name == this.table_name && schema == this.schema)
                {
                    notify.info("You cannot select the table you are on.");
                    return;
                }

                this.$root.ready = false;

                // // Kick the vue data changes out of the flow, allowing the above .ready to propagate and be acted on
                window.setTimeout(() => {
                    app.explorer.query.table = table_name;
                    app.explorer.query.prefix = table_name;
                    app.explorer.query.schema = schema;
                    window.history.pushState(
                        '',
                        table_name,
                        `/studio/projects/${this.control_id}/tables/${schema}/${table_name}`
                    );
                
                    app.rebuild();
                }, 0);
            }
            else{
                notify.send("The table you requested does not exist in this project.", 'info'); 
            }
        },
        clearBox(event)
        {
            if(this.saved_value == "") {
                this.saved_value = event.target.value;
                document.getElementById("table_dropdown").value = "";
            }
            else {
                document.getElementById("table_dropdown").value = this.saved_value;
                this.saved_value = "";
            }
        },
        refreshSample(){
            app.getRecords();
        },
        pivotExplorer() {
            app.pivot_explorer();
        },
        openJoinModal() {
            if (this.$root.explorer.query.unions.length > 0) {
                notify.danger("Joins disabled while unions are applied.")
                return;
            }
            app.modals.join_manager = true;
        },
        openUnionModal() {
            app.modals.union_manager = true;
        },
        changeGroupBy() {
            if (this.$root.explorer.query.unions.length > 0) {
                notify.danger("Grouping is disabled while unions are applied.")
                return;
            }

            var check = this.$root.explorer.query.filters.filter((filter) => {
                if(filter.prefix == "aggregate" || filter.is_aggregate == true)
                    return filter;
            });

            if(check.length > 0) {
                notify.danger("You have filters on aggregate columns.  Please remove filters on aggregate columns before ungrouping.");
                return;
            }

            for(const [key, value] of Object.entries(this.$root.explorer.query.transformations)) {
                if (Array.isArray(value) && value.length > 0) {
                    if(value[0].is_aggregate == true || value[0].prefix == "aggregate") {
                        notify.danger("You have transformations on aggregate columns.  Please remove transformations on aggregate columns before ungrouping.");
                        return;
                    }
                }
            }

            this.$root.explorer.query.is_grouped = !this.$root.explorer.query.is_grouped;
            this.$root.getRecords();
        },
        refreshTable() {
            this.$root.ready = false;
            this.$root.getActiveUsers();
            this.$root.rebuildTable(false);

            // Check to see if our view type changed
            fetch(`${baseUrl}/internal-api/v1/studio/projects/${this.control_id}/tables?get_types=true`)
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    let current_view = json.data.tables.filter((tbl) => {
                        if(tbl.table_name == this.$root.explorer.query.table && tbl.table_schema == this.$root.explorer.query.schema) 
                            return tbl;
                    })[0];

                    if (current_view === undefined) {
                        return;
                    }

                    if (['View', 'Materialized View'].includes(current_view.table_type)) {
                        this.$root.explorer.view.view_type = current_view.table_type == 'Materialized View' ? 'materialized' : 'normal';
                    }
                })
                .catch((error) => {
                    // Fail silently
                });

        }
    },
    computed:{
        fully_qualified_table_name :{
            get: function () {
                return this.$root.explorer.query.schema + '.' + this.$root.explorer.query.table;
            },
            set: function () {
            }
        }
    }
}

/** Vue.js Component
 *******************************************************************************
 * union-manager
 *******************************************************************************
 * Renders a modal that allows users to specify unions
 */
 modals.union_manager = {
    template: '#union-modal-template',
    props: [ 'open', 'control_id', 'tables', 'table', 'columns', 'viewing_type' ],
    data: function() {
        return {
            editing_uuid: null,
            is_initialized: false,
            union_all: false,
            union_template: {
                columns: [],
                is_editing: false,
                is_saved: false,
                schema: null,
                schema_table: '',
                table: null,
                uuid: null,
            },
            unions: [],
        }
    },
    watch: {
        open() {
            if(this.open == true) {
                for (const [key, transformations] of Object.entries(app.explorer.query.transformations)) {
                    if (transformations.length > 0) {
                        notify.danger("Unions are disabled when column transformations are used.");
                        this.modalClose();
                        return;
                    }
                }

                if (app.explorer.query.filters.length > 0) {
                    notify.danger("Unions are disabled while filters are applied.");
                    this.modalClose();
                    return;
                }

                if (app.explorer.query.joins.length > 0) {
                    notify.danger("Unions are disabled when joins are applied.");
                    this.modalClose();
                    return;
                }

                if (app.explorer.query.is_grouped) {
                    notify.danger("Unions are disabled when grouping is applied.");
                    this.modalClose();
                    return;
                }

                this.is_initialized = false;
                this.union_all = false;
                this.editing_uuid = null;
                this.loadUnionsFromQuery();

                openModal('#modal-union', (event) => {
                    if(event != undefined) {
                        event.stopPropagation();
                    }

                    // End of code to run before closing
                    app.modals.union_manager = false;
                    return false;
                });
                $(document).on("mousedown", "#dmiux_body", this.modalClose);
                $(document).off('keydown', closeModalOnEscape);
                $(document).on("keydown", this.modalClose);
                this.is_initialized = true;
            }
        },
        union_all() {
            if (! this.is_initialized) {
                return;
            }

            this.$root.explorer.query.union_all = this.union_all;

            if (this.$root.explorer.query.unions != undefined && this.$root.explorer.query.unions.length > 0) {
                this.$root.getRecords();
            }
        }
    },
    methods: {
        modalClose (event) {
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
                        if (clicked_element.id != "button-close_union_manager" && clicked_element.id != "button-cancel_union_manager")
                            return;
                    } else if(clicked_element.closest(".notyf")) {
                        return;
                    }
                }
            }
            // You either clicked outside the modal, or the X Button, or the Cancel Button - modal will close

            // End of code to run before closing
            app.modals.union_manager = false;
            $(document).off("mousedown", "#dmiux_body", this.modalClose);
            $(document).off("keydown", this.modalClose);
            $(document).on('keydown', closeModalOnEscape);
            closeModal('#modal-union');
            this.is_initialized = false;
        },
        add () {
            if (this.editing_uuid !== null) {
                notify.danger("Please save or cancel your current edit before adding a new union.");
                return;
            }

            let union = Object.assign({}, this.union_template);

            union.is_editing = true;

            union.uuid = this.uuid();

            this.unions.push(union);

            this.editing_uuid = union.uuid;
        },
        remove (union) {
            this.unions = this.unions.filter(function (u) {
                return u.uuid !== union.uuid;
            });

            if (union.uuid === this.editing_uuid) {
                this.editing_uuid = null;
            }

            this.$root.explorer.query.unions = this.unions.filter((u) => u.is_saved)
                .map(function (u) {
                    return {
                        schema: u.schema,
                        table: u.table,
                        columns: u.columns,
                    };
                });

            if (union.table === null || union.table === '') {
                // Don't run an extra query if nothing changed
                return;
            }

            this.$root.getRecords();
        },
        edit (union) {
            if (this.editing_uuid !== null) {
                notify.danger("Please save or cancel your current edit before editing another union.");
                return;
            }

            union.is_editing = true;
            union.is_saved = false;
        },
        save (union) {
            if (union.schema_table == '' || union.schema_table == null) {
                return;
            }

            [union.schema, union.table] = union.schema_table.split('.');
            
            let options = FetchHelper.buildJsonRequest({
                schema: union.schema,
                table: union.table,
                columns: this.getCurrentColumns(),
            });

            this.$root.ready = false;

            fetch(`/internal-api/v1/studio/projects/${this.$root.control_id}/tables/${this.$root.explorer.query.schema}/${this.table}/unions/test`, options)
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    union.is_editing = false;
                    union.is_saved = true;
                    union.columns = json.data.columns;
                    this.editing_uuid = null;
                    this.$root.ready = true;

                    this.$root.explorer.query.unions = this.unions.filter((u) => u.is_saved)
                        .map(function (u) {
                            return {
                                schema: u.schema,
                                table: u.table,
                                columns: u.columns,
                            };
                        });

                    this.$root.explorer.query.union_all = this.union_all;

                    this.$nextTick(() => {
                        this.$root.getRecords();
                    });
                })
                .catch((error) => {
                    this.$root.ready = true;
                    ResponseHelper.handleErrorMessage(error, "Failed to apply union");
                });
        },
        uuid() {
            return ([1e7]+-1e3+-4e3+-8e3+-1e11).replace(/[018]/g, c =>
                (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16)
            );
        },
        getCurrentColumns() {
            return this.$root.explorer.query.columns.filter((col) => col.checked)
                .map(function (col) {
                    return {
                        data_type: col.data_type,
                        column_name: col.column_name
                    };
                });
        },
        loadUnionsFromQuery() {
            let unions_from_query = JSON.parse(JSON.stringify(this.$root.explorer.query.unions));
            let self = this;

            this.unions = unions_from_query.map(function(union_from_query) {
                let union = Object.assign({}, self.union_template);
                union.is_editing = false;
                union.is_saved = true;
                union.uuid = self.uuid();
                union.table = union_from_query.table;
                union.schema = union_from_query.schema;
                union.schema_table = union.schema + '.' + union.table;
                return union;
            });

            this.union_all = this.$root.explorer.query.union_all === true && this.unions.length > 0;
        }
    }
}

/** End components */

function toggle_unstructured(e) {
    var status = $(e).parent().find(".unstructured-stage").css('display');
    if(status == 'none') {
        $(e).text("Hide unstructured data");
        $(e).parent().find(".unstructured-stage").show();
    }
    else {
        $(e).text("Show unstructured data");
        $(e).parent().find(".unstructured-stage").hide();
    }
}

$(document).on("hover", '[data-toggle="tooltip"]', function () {
    $('[data-toggle="tooltip"]').tooltip();
});

$(document).ready(function() {
    CommentsAtMentionsList(document.getElementsByClassName("note-editable"));
});

function CommentsAtMentionsList(inp) {
    var arr = [];
    fetch(`${baseUrl}/internal-api/v1/studio/projects/${app.control_id}/users`)
        .then(response => response.json())
        .then(resp => {
            arr = resp.data.filter(x => x.dmi_user_id);
            var user = resp.data;
            if(user.includes("DMI") && user != "DMIDEVELOPER")
            {
                $("#input-assigned_user").val(user);
            }
        });

    $.fn.extend({
        placeCursorAtEnd: function () {

            if (this.length === 0) {
                throw new Error("Cannot manipulate an element if there is no element!");
            }
            var el = this[0];
            var range = document.createRange();
            var sel = window.getSelection();
            var childLength = el.childNodes.length;
            if (childLength > 0) {
                var lastNode = el.childNodes[childLength - 1];
                var lastNodeChildren = lastNode.childNodes.length;
                range.setStart(lastNode, lastNodeChildren);
                range.collapse(true);
                sel.removeAllRanges();
                sel.addRange(range);
            }
            return this;
        }
    });

    var currentFocus;

    function addActiveClassAtMentions(x) {
      if (!x) return false;

      removeActive(x);
      if (currentFocus >= x.length) currentFocus = 0;
      if (currentFocus < 0) currentFocus = (x.length - 1);

      x[currentFocus].classList.add("autocomplete-active");
    }

    function removeActive(x) {
      for (var i = 0; i < x.length; i++) {
        x[i].classList.remove("autocomplete-active");
      }
    }

    function closeAllLists(elmnt) {
      var x = document.getElementsByClassName("autocomplete-items");

      for (var i = 0; i < x.length; i++) {
        if (elmnt != x[i] && elmnt != inp) {
            x[i].parentNode.removeChild(x[i]);
        }
      }
    }

    document.addEventListener("click", function (e) {
        closeAllLists(e.target);
    });
}

function setTableStyles() {
    var check = window.matchMedia("(max-width: 1200px)")
    if(check.matches) {
        app.mobile = true;
    }
    else {
        app.mobile = false;
    }

    var width = $('#datatable_scroll').width();
    var element = document.getElementById('arrow_fix_right');
    if(element != null)
        element.style.left = width - 66 + "px";
}

$(window).resize(function() {
    setTableStyles();
});
