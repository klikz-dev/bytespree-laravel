<script type="text/x-template" id="component-widget-snapshots">
    <div class="dmiux_grid-cont dmiux_grid-cont_fw" id="snapshots">
        <div v-if="snapshots.length == 0" class="alert alert-info"><b>Hiya!</b> This project doesn't have any snapshots yet.</div>
        <div v-else class="dmiux_data-table dmiux_data-table__cont">
            <table id="widget-snapshots-table" class="dmiux_data-table__table">
                <thead>
                    <tr>
                        <th></th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Author</th>
                        <th class="text-right">Count of Records</th>
                        <th class="text-right">Table Size</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="snapshot in snapshots">
                        <td>
                            <div class="dmiux_grid-row">
                                <div v-if="$parent.checkPerms('project_manage') === true || $root.currentUser.user_handle == snapshot.user_id" class="dmiux_grid-col dmiux_data-table__actions">
                                    <div class="dmiux_actionswrap dmiux_actionswrap--bin"
                                        title="Delete "
                                        @click="deleteSnapshot(snapshot.id)">
                                    </div>
                                </div>
                                <div class="dmiux_grid-col pl-4 ml-1">
                                        <a v-bind:href="'/studio/projects/' + $root.project_id + '/tables/' + snapshot.schema + '/' + snapshot.name">Open</a>
                                </div>
                            </div>
                        </td>
                        <td>{{ snapshot.name }}</td>
                        <td><span :title="snapshot.description">{{ snapshot.description.substring(0, 50) }}<span v-if="snapshot.description.length > 50">...</span></span></td>
                        <td>{{ snapshot.user.name }}</td>
                        <td class="text-right">{{ formatCount(snapshot.num_records) }}</td>
                        <td class="text-right">{{ snapshot.total_size }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</script>

<script>
    var widget_snapshots = Vue.component('widget-snapshots', {
        template: '#component-widget-snapshots',
        data: function() {
            return {
                snapshots: []
            }
        },
        methods: {
            getSnapshots() {
                this.$root.loading(true);
                fetch(`/internal-api/v1/studio/projects/${this.$root.project_id}/tables?get_types=false`)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        if (json.status == "ok") {
                            this.snapshots = json.data.snapshots;
                        } else {
                            alert('The Server is unavailable at this time. You will be redirected back.')
                            window.location.href = `${baseUrl}/Studio`;
                        }

                        this.rebuildDatable();
                    })
                    .catch((error) => {
                        this.$root.loading(false);
                        ResponseHelper.handleErrorMessage(error, "Failed to get tables");
                    });
            },
            deleteSnapshot: function(id, ignore_warning = false) {
                if(ignore_warning == false) {
                    if(!confirm("Are you sure you want to delete this snapshot? This cannot be undone.")) {
                        return;
                    }
                }

                this.$root.loading(true);
                fetch(`/internal-api/v1/studio/projects/${this.$root.project_id}/snapshots/${id}?ignore_warning=${ignore_warning}`, { method: 'delete' })
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.$root.loading(false);
                        if(ignore_warning == false) {
                            if(json.data == "warning") {
                                if(!confirm(json.message)) {
                                    return;
                                } else {
                                    this.deleteSnapshot(id, true);
                                    return;
                                }
                            }
                        }

                        notify.success("Snapshot deleted.");
                        this.getSnapshots();
                    })
                    .catch((error) => {
                        this.$root.loading(false);
                        ResponseHelper.handleErrorMessage(error, "Failed to delete snapshot.");
                    });
            },
            rebuildDatable() {
                $("#widget-snapshots-table").DataTable().destroy();

                this.$nextTick(function () {
                    var options = {
                        "order": [[ 1, "asc" ]],
                        "bPaginate": false,
                        "bInfo" : false
                    };
                    $("#widget-snapshots-table").DataTable(options);
                    this.$root.loading(false);
                });
            },
            formatCount(count) {
                return parseFloat(count).toFixed().toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            }
        },
        mounted() {
            this.$root.pageLoad();
            this.getSnapshots();
        }
    });
</script>