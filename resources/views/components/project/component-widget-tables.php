<script type="text/x-template" id="component-widget-tables">
    <div class="dmiux_grid-cont dmiux_grid-cont_fw" id="tables">
        <div class="dmiux_grid-row table-list">
            <div :class="selected_table == null ? 'dmiux_grid-col_12' : 'dmiux_grid-col_8 dmiux_grid-col_lg-7 dmiux_grid-col_md-6'" class="dmiux_grid-col p-0 px-0 pl-lg-1 pr-lg-1 table-summary">
                <div v-if="tables.length == 0" class="alert alert-info"><span class="dmiux_fw700">Hiya!</span> This project doesn't have any tables yet.</div>
                <div v-else class="dmiux_grid-cont_fw dmiux_data-table dmiux_data-table__cont">
                    <table id="widget-tables-table" class="dmiux_data-table__table">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Table</th>
                                <th>Database</th>
                                <th>Type</th>
                                <th class="text-right">Records</th>
                                <th class="text-right">Size</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="table in tables" v-bind:id="'row-table-' + table.table_name" :class="selected_table != null && table.table_name == selected_table.table_name && table.table_schema == selected_table.table_schema ? 'table-row_selected' : ''" class="cursor-p" @click="setTable(table)">
                                <td @click.stop>
                                    <a v-if="table.exists != false && table.synchronized != false" v-bind:href="'/studio/projects/' + $root.project_id + '/tables/' + table.table_schema + '/' + table.table_name">Open</a>
                                </td>
                                <td :class="table.exists == false || table.synchronized == false ? 'text-danger' : ''">{{ table.table_name }} <a href="javascript:void(0)" title="This view is missing from the database server" @click="restoreView(table.table_name, table.exists)" v-if="table.exists == false || table.synchronized == false">(restore view)</a></td>
                                <td>{{ table.table_catalog }}</td>
                                <td>{{ table.table_type }}</td>
                                <td class="text-right">{{ formatCount(table.num_records) }}</td>
                                <td class="text-right">  <span class="hidden">{{ table.size_in_bytes_sort }}</span>{{ table.total_size }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div v-if="selected_table != null" class="dmiux_grid-col dmiux_grid-col_4 dmiux_grid-col_lg-5 dmiux_grid-col_md-6">
                <div class="table-summary dmiux_grid-row">
                    <div class="dmiux_grid-col dmiux_grid-col_12">
                        <div class="dmiux_grid-row pb-2 table-summary-header">
                            <div class="dmiux_grid-col dmiux_grid-col_9 table-summary-heading">
                                {{ selected_table.table_catalog }}.{{ selected_table.table_name }}
                            </div>
                            <div class="dmiux_grid-col dmiux_grid-col_3 text-right">
                                <a title="Open table" class="pretty-tooltip" :href="'/studio/projects/' + $root.project_id + '/tables/' + selected_table.table_schema + '/' + selected_table.table_name">
                                    <i class="fas fa-lg fa-table"></i>
                                </a>
                                <a title="Close sidebar" class="pretty-tooltip" href="javascript:void(0)" @click="selected_table = null">
                                    <i class="fas fa-lg fa-window-close"></i>
                                </a>
                            </div>
                        </div>
                        <div class="dmiux_grid-row">
                            <div class="dmiux_grid-col dmiux_grid-col_12 ">
                                <div class="table-summary-sub-heading table-summary-stats-heading">Table Details</div>
                                <div class="table-summary-stats-contents">
                                    <template v-for="(table_stat, index) in table_stats">
                                        <div class="dmiux_grid-row">
                                            <div class="dmiux_grid-col dmiux_grid-col_8 small">
                                                <div>{{ index.charAt(0).toUpperCase() + index.slice(1) }} Counts:</div>
                                            </div>
                                            <div class="dmiux_grid-col dmiux_grid-col_4 text-right small">
                                                <div>{{ table_stat }}</div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <div class="dmiux_grid-row">
                            <div class="dmiux_grid-col dmiux_grid-col_12 table-summary-sub-heading">
                                Notes
                            </div>
                        </div>
                        <div class="table-summary-notes p-2">
                            <template v-if="show_add_buttons">
                                <div class="dmiux_grid-row small">
                                    <div class="dmiux_grid-col dmiux_grid-col_12">
                                        <div class="dmiux_input" >
                                            <textarea v-model="new_note" id="table-summary-notes_input" class="dmiux_input__input"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="dmiux_grid-row table-summary-notes table-summary-notes-body-actions_project">
                                    <div class="dmiux_grid-col dmiux_grid-col_12" style="left:0px">
                                        <a class="mr-2" href="javascript:void(0)" @click="createNote()"><i class="fas fa-sm fa-save"></i> Save</a>
                                        <a class="mr-2" href="javascript:void(0)" @click="cancelAddNote()"><i class="fas fa-sm fa-ban"></i> Cancel</a>
                                    </div>
                                </div>
                            </template>
                            <template v-else>
                                <div class="dmiux_grid-row">
                                    <div class="dmiux_grid-col dmiux_grid-col_12">
                                        <div class="dmiux_input" >
                                            <div @click="addNote()" class="dmiux_input__input">Add a note...</div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <div v-for="(note, index) in notes" class="table-summary-notes table-summary-notes-wrapper_project small">
                            <img class="float-left table-summary-notes-photo" :src="note.profile_picture" :alt="note.user.name">
                            <div class="dmiux_grid-row table-summary-notes-title">
                                <div class="dmiux_grid-col dmiux_grid-col_auto pr-0 table-summary-notes-title_name">
                                    <span class="dmiux_fw600">{{ note.user.name }}</span>
                                </div>
                                <div class="dmiux_grid-col dmiux_grid-col_auto pl-1">
                                    - {{ formatAge(note.updated_at) }}
                                </div>
                            </div>
                            <div class="dmiux_grid-row table-summary-notes table-summary-notes-content" :class="note.is_overflowing && note_id == 0 ? '' : 'mb-2'">
                                <div class="dmiux_grid-col dmiux_grid-col_12">
                                    <div v-if="note_id == note.id" class="dmiux_input">
                                        <textarea :id="'table-summary-notes_input-' + note.id" class="dmiux_input__input" v-model="note.note"></textarea>
                                    </div>
                                    <div v-else :id="'table-summary-notes_body-' + note.id" class="table-summary-notes-body" :class="note.is_expanded ? '' : 'table-summary-notes-body-condensed'">{{ note.note }}</div>
                                </div>
                            </div>
                            <div v-if="note.is_overflowing && note_id == 0" class="dmiux_grid-row table-summary-notes table-summary-notes-content mb-2">
                                <div class="dmiux_grid-col dmiux_grid-col_12">
                                    <a v-if="! note.is_expanded" href="javascript:void(0);" @click="note.is_expanded = true; $forceUpdate();">show more</a>
                                    <a v-else href="javascript:void(0);" @click="note.is_expanded = false; $forceUpdate();">show less</a>
                                </div>
                            </div>
                            <div class="dmiux_grid-row table-summary-notes table-summary-notes-body-actions_project">
                                <div v-if="note_id == note.id" class="dmiux_grid-col dmiux_grid-col_12">
                                    <a class="mr-2" href="javascript:void(0)" @click="updateNote(note)"><i class="fas fa-sm fa-save"></i> Save</a>
                                    <a class="mr-2" href="javascript:void(0)" @click="cancelEditNode(note)"><i class="fas fa-sm fa-ban"></i> Cancel</a>
                                </div>
                                <div v-else class="dmiux_grid-col dmiux_grid-col_12">
                                    <a class="mr-2" href="javascript:void(0)" @click="editNote(note)"><i class="fas fa-sm fa-edit"></i> Edit</a>
                                    <a href="javascript:void(0)" @click="deleteNote(note.id)"><i class="fas fa-sm fa-trash"></i> Delete</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>

<script>
    var widget_tables = Vue.component('widget-tables', {
        template: '#component-widget-tables',
        data: function() {
            return {
                tables: [],
                selected_table: null,
                table_stats: [],
                notes: [],
                note_id: 0,
                show_add_buttons: false,
                origin_note: {},
                new_note: ''
            }
        },
        watch: {
            selected_table() {
                if(this.selected_table != null) {
                    this.getNotes();
                }
            }
        },
        methods: {
            addNote() {
                this.show_add_buttons = true;
                this.$nextTick(() => {
                    let element = document.getElementById('table-summary-notes_input');
                    if (element != undefined) {
                        element.focus();
                    }
                });
            },
            editNote(note) {
                this.note_id = note.id;
                this.origin_note = note.note;
            },
            cancelAddNote() {
                this.new_note = '';
                this.show_add_buttons = false
            },
            cancelEditNode(note) {
                this.note_id = 0;
                this.notes = this.notes.map((note) => {
                    note.note = this.origin_note;
                    return note;
                });
            },
            formatAge(date) {
                let noteDate = new Date(date);
                let today = new Date();
                let diff = today - noteDate;

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
                    return noteDate.toLocaleDateString() + ' at ' + noteDate.toLocaleTimeString([], { timeStyle: "short" });
                } else {
                    return text;
                }
            },
            formatCount(count) {
                var nbr = parseFloat(count);
                if (isNaN(nbr)) 
                    return '';
                else
                    return nbr.toFixed().toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            },
            getTables(get_types = false) {
                if(get_types == false) {
                    this.$root.loading(true);
                }
                fetch(`/internal-api/v1/studio/projects/${this.$root.project_id}/tables?get_types=${get_types}`)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        if (json.status == "ok") {
                            this.tables = json.data.tables;

                            if(get_types == false) {
                                this.getTables(true);
                            }
                        } else {
                            alert('The Server is unavailable at this time. You will be redirected back.')
                            window.location.href = `/studio`;
                        }

                        this.rebuildDatable();
                    })
                    .catch((error) => {
                        ResponseHelper.handleErrorMessage(error, "Failed to get tables");
                    });
            },
            getNotes() {
                this.$root.loading(true);
                fetch(`/internal-api/v1/studio/projects/${this.$root.project_id}/tables/${this.selected_table.table_schema}/${this.selected_table.table_name}/notes`)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.notes = Object.values(json.data);
                        this.notes = this.notes.map(function(note) {
                            note.is_expanded = false;
                            note.updated_at = DateHelper.formatLocaleCarbonDate(note.updated_at);
                            note.created_at = DateHelper.formatLocaleCarbonDate(note.created_at);
                            return note;
                        });

                        this.getTableStats();
                    })
                    .then(() => {
                        this.notes.forEach((note) => {
                            let el = document.getElementById(`table-summary-notes_body-${note.id}`);
                            if(el != undefined) {
                                note.is_overflowing = el.clientWidth < el.scrollWidth || el.clientHeight < el.scrollHeight;
                            } else {
                                note.is_overflowing = false;
                            }
                        });
                    })
                    .catch((error) => {
                        this.$root.loading(false);
                        ResponseHelper.handleErrorMessage(error, "Failed to get table notes");
                    });
            },
            createNote() {
                let options = FetchHelper.buildJsonRequest({
                    note: this.new_note
                });
    
                fetch(`/internal-api/v1/studio/projects/${this.$root.project_id}/tables/${this.selected_table.table_schema}/${this.selected_table.table_name}/notes`, options)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        notify.success('Note has been created successfully.');
                        this.getNotes();
                        this.cancelAddNote();
                    })
                    .catch((error) => {
                        ResponseHelper.handleErrorMessage(error, 'Table note was not saved.');
                    });
            },
            updateNote(note) {
                let options = FetchHelper.buildJsonRequest({
                    note: note.note
                }, 'put');
                
                fetch(`/internal-api/v1/studio/projects/${this.$root.project_id}/tables/${this.selected_table.table_schema}/${this.selected_table.table_name}/notes/${this.note_id}`, options)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        notify.success('Note has been updated successfully.');
                        this.getNotes();
                        this.note_id = 0;
                    })
                    .catch((error) => {
                        ResponseHelper.handleErrorMessage(error, 'Table note was not updated.');
                    });
            },
            deleteNote(id) {
                if(! confirm("Are you sure you want to delete this note? This cannot be undone.")) {
                    return;
                }

                fetch(`/internal-api/v1/studio/projects/${this.$root.project_id}/tables/${this.selected_table.table_schema}/${this.selected_table.table_name}/notes/${id}`, { method: 'delete' })
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        notify.success('Note has been deleted successfully.');
                        this.getNotes();
                    })
                    .catch((error) => {
                        ResponseHelper.handleErrorMessage(error, 'There was a problem deleting the note.');
                    });
            },
            getTableStats() {
                this.$root.loading(true);
                fetch(`/internal-api/v1/studio/projects/${this.$root.project_id}/tables/${this.selected_table.table_schema}/${this.selected_table.table_name}/stats`)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.$root.loading(false);
                        this.table_stats = json.data;
                    })
                    .catch((error) => {
                        this.$root.loading(false);
                        ResponseHelper.handleErrorMessage(error, 'Failed to get stats.');
                    });
            },
            restoreView(view_name, exists) {
                var fix_type = exists ? 'resync' : 'restore';
                
                let options = FetchHelper.buildJsonRequest({
                    view_name: view_name,
                    fix_type: fix_type
                }, 'post');

                this.$root.loading(true);
                fetch(`/internal-api/v1/studio/projects/${this.$root.project_id}/views/restore`, options)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.$root.loading(false);
                        notify.success("View has been restored successfully.");
                        this.getTables(true);
                    })
                    .catch(error => {
                        this.$root.loading(false);
                        ResponseHelper.handleErrorMessage(error, 'There was an issue restoring the view');
                    });
            },
            rebuildDatable() {
                $("#widget-tables-table").DataTable().destroy();

                this.$nextTick(function () {
                    var options = {
                        "order": [[ 1, "asc" ]],
                        "bPaginate": false,
                        "bInfo" : false
                    };
                    $("#widget-tables-table").DataTable(options);
                    this.$root.loading(false);
                });
            },
            setTable(table) {
                this.selected_table = table;
            }
        },
        mounted() {
            this.$root.pageLoad();
            this.getTables();
        }
    });
</script>