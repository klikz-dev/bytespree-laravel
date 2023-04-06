<script type="text/x-template" id="component-widget-flags">
    <div class="dmiux_grid-cont dmiux_grid-cont_fw" id="flags">
        <div v-if="flags.length == 0" class="alert alert-info"><b>Hooray!</b> No flags were found in this project, and that's a good thing.</div>
        <div v-else class="dmiux_data-table dmiux_data-table__cont">
            <table id="widget-flags-table" class="dmiux_data-table__table">
                <thead>
                    <tr>
                        <th></th>
                        <th>Table &amp; Column</th>
                        <th>Flag Reason</th>
                        <th>Assigned To</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="flag in flags">
                        <td>
                            <div v-if="$parent.checkPerms('flag_write') === true" class="dmiux_data-table__actions">
                                <div class="dmiux_actionswrap dmiux_actionswrap--bin"
                                     data-toggle="tooltip"
                                     title="Delete Flag"
                                     data-original-title="Dismiss"
                                     @click="dismissFlag(flag.id, flag.schema_name, flag.table_name)">
                                </div>
                            </div>
                        </td>
                        <td>
                        <a v-bind:href="`/studio/projects/${flag.project_id}/tables/${flag.schema_name}/${flag.table_name}?column=${encodeURIComponent(flag.column_name)}`">{{ flag.table_name }}.{{ flag.column_name }}</a>
                        </td>
                        <td v-html="flag.flag_reason"></td>
                        <td>{{ flag.user.name }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</script>

<script>
    var widget_flags = Vue.component('widget-flags', {
        template: '#component-widget-flags',
        data: function() {
            return {
                flags: []
            }
        },
        methods: {
            btoa: function(str) {
                return btoa(str);
            },
            getFlags() {
                this.$root.loading(true);
                fetch(`/internal-api/v1/studio/projects/${this.$root.project_id}/flags`)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.flags = json.data;
                        $("#widget-flags-table").DataTable().destroy();
                    })
                    .then(() => {
                        $("#widget-flags-table").DataTable();
                        this.$root.loading(false);
                    })
                    .catch((error) => {
                        ResponseHelper.handleErrorMessage(error, 'Flags could not be retrieved.');
                        this.$root.loading(false);
                    });
            },
            dismissFlag(id, schema, table) {
                if(! confirm("Are you sure you want to dismiss this flag?")) {
                    return false;
                }

                fetch(`/internal-api/v1/studio/projects/${this.$root.project_id}/tables/${schema}/${table}/flags/${id}`, { method: 'delete' })
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        notify.success(`Flag dismissed from ${table}.`);
                        this.getFlags();
                    })
                    .catch((error) => {
                        ResponseHelper.handleErrorMessage(error, 'Flag could not be dismissed.');
                    });
            }
        },
        mounted() {
            this.$root.pageLoad();
            this.getFlags();
        }
    });
</script>