<script type="text/x-template" id="component-widget-queries">
    <div class="dmiux_grid-cont dmiux_grid-cont_fw" id="queries">
        <div v-if="queries.length == 0" class="alert alert-info"><b>Hiya!</b> This project doesn't have any saved queries yet.</div>
        <div v-else class="dmiux_data-table dmiux_data-table__cont">
            <table id="widget-queries-table" class="dmiux_data-table__table">
                <thead>
                    <tr>
                        <th></th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Author</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="query in queries">
                        <td>
                            <div class="dmiux_grid-row">
                                <div v-if="$parent.checkPerms('project_manage') === true || $root.currentUser.user_handle == query.user_id" class="dmiux_grid-col dmiux_data-table__actions">
                                    <div class="dmiux_actionswrap dmiux_actionswrap--bin"
                                        title="Delete"
                                        @click="deleteQuery(query.id)">
                                    </div>
                                </div>
                                <div class="dmiux_grid-col pl-4 ml-1">
                                    <a v-bind:href="'/studio/projects/' + $root.project_id + '/tables/' + query.source_schema + '/' + query.source_table + '?saved_query_id=' + query.id">Open</a>
                                </div>
                            </div>
                        </td>
                        <td>{{ query.name }}</td>
                        <td><span :title="query.description">{{ query.description.substring(0, 50) }}<span v-if="query.description.length > 50">...</span></span></td>
                        <td>{{ query.user_id }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</script>

<script>
    var widget_queries = Vue.component('widget-queries', {
        template: '#component-widget-queries',
        data: function() {
            return {
                queries: []
            }
        },
        methods: {
            getQueries() {
                this.$root.loading(true);
                fetch(`/internal-api/v1/studio/projects/${this.$root.project_id}/saved-queries`)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.queries = json.data;
                        $("#widget-queries-table").DataTable().destroy();
                    })
                    .then(() => {
                        $("#widget-queries-table").DataTable();
                        this.$root.loading(false);
                    })
                    .catch((error) => {
                        this.$root.loading(false);
                        ResponseHelper.handleErrorMessage(error, "Saved Queries could not be retrieved.");
                    });
            },
            deleteQuery(id) {
                if (! confirm("Are you sure you want to delete this saved query?")) {
                    return;
                }

                this.$root.loading(true);
                fetch(`/internal-api/v1/studio/projects/${this.$root.project_id}/saved-queries/${id}`, { method: 'delete' })
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.$root.loading(false);
                        notify.success("Query deleted.");
                        this.getQueries();
                    })
                    .catch((error) => {
                        this.$root.loading(false);
                        ResponseHelper.handleErrorMessage(error, "Failed to delete query.");
                    });
            }
        },
        mounted() {
            this.$root.pageLoad();
            this.getQueries();
        }
    });
</script>