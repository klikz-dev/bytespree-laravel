<script type="text/x-template" id="component-widget-publishers">
    <router-view></router-view>
</script>

<script type="text/x-template" id="component-publishers">
    <div class="dmiux_grid-cont dmiux_grid-cont_fw" id="publishers">
        <div v-if="publishers.length == 0" class="alert alert-info"><b>Hiya!</b> This project doesn't have any publishers yet.</div>
        <div v-else class="dmiux_data-table dmiux_data-table__cont">
            <div class="dmiux_input pb-2">
                <input class="dmiux_input__input" @input="filterTable($event)" placeholder="Search Publishers" />
                <div class="dmiux_input__icon">
                    <svg height="16" viewBox="0 0 16 16" width="16" xmlns="http://www.w3.org/2000/svg">
                    <path d="M265.7,19.2298137 C266.6,18.0372671 267.1,16.6459627 267.1,15.0559006 C267.1,11.1801242 264,8 260.1,8 C256.2,8 253,11.1801242 253,15.0559006 C253,18.931677 256.2,22.1118012 260.1,22.1118012 C261.7,22.1118012 263.2,21.6149068 264.3,20.7204969 L267.3,23.7018634 C267.5,23.9006211 267.8,24 268,24 C268.2,24 268.5,23.9006211 268.7,23.7018634 C269.1,23.3043478 269.1,22.7080745 268.7,22.310559 L265.7,19.2298137 Z M260.05,20.1 C257.277451,20.1 255,17.9 255,15.1 C255,12.3 257.277451,10 260.05,10 C262.822549,10 265.1,12.3 265.1,15.1 C265.1,17.9 262.822549,20.1 260.05,20.1 Z" fill="currentColor" transform="translate(-253 -8)"></path></svg>
                </div>
            </div>
            <table id="widget-publishers-table" class="dmiux_data-table__table">
                <thead>
                    <tr>
                        <th></th>
                        <th>Status</th>
                        <th>Type</th>
                        <th>Table</th>
                        <th>Schema</th>
                        <th>Last Run</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="publisher in publishers">
                        <td>
                            <div class="dmiux_grid-row">
                                <div v-if="$root.checkPerms('project_manage') === true || $root.currentUser.user_handle == publisher.user_id" class="dmiux_grid-col dmiux_data-table__actions">
                                    <div class="dmiux_actionswrap dmiux_actionswrap--bin"
                                        title="Delete"
                                        @click="deletePublisher(publisher.id)">
                                    </div>
                                </div>
                                <div class="dmiux_grid-col pl-4 ml-1">
                                    <a class="pr-1" title="View the publisher query" v-bind:href="'/studio/projects/' + $root.project_id + '/tables/' + publisher.schema_name + '/' + publisher.table_name + '?publisher_id=' + publisher.id">Open</a>
                                    <router-link :to="{ name: 'logs', params: { publisher_id: publisher.id } }">Logs</router-link>
                                </div>
                            </div>
                        </td>
                        <td :class="[ publisher.status == 'SUCCESS' ? 'text-success' : '', publisher.status == 'FAILURE' ? 'text-danger' : '' ]">{{ publisher.status }}</td>
                        <td>{{ publisher.destination.name }}</td>
                        <td>{{ publisher.table_name }}</td>
                        <td>{{ publisher.schema_name }}</td>
                        <td>{{ convertAndFormat(publisher.last_ran) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</script>

<script type="text/x-template" id="component-publisher-logs">
    <div class="dmiux_grid-cont dmiux_grid-cont_fw" id="publishers-logs">
        <div class="dmiux_grid-row">
            <div class="dmiux_grid-col">
                <router-link class="text-decoration-none" :to="{ name: 'publishers', params: { } }">                  
                    <button id="back-from-logs" type="button" class="dmiux_button float-right m-1">Back</button>
                </router-link>
                <div class="dmiux_report__heading">{{ destination_name }}</div>
            </div>
        </div>
        <div v-if="logs.length == 0" class="alert alert-info"><b>Hiya!</b> This publisher doesn't have any logs yet.</div>
        <div v-else class="dmiux_data-table dmiux_data-table__cont">
            <table id="publisher-logs-table" class="dmiux_data-table__table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Build Started</th>
                        <th>Build Finished</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="log in logs">
                        <td>{{ log.type.charAt(0).toUpperCase() + log.type.slice(1) }}</td>
                        <td :class="[ log.schedule.status == 'SUCCESS' ? 'text-success' : '', log.schedule.status == 'FAILURE' ? 'text-danger' : '' ]">{{ log.schedule.status != 'false' ? log.schedule.status : 'PENDING' }}</td>
                        <td><span class="hidden">{{ log.publishing_started }}</span>{{ convertAndFormat(log.publishing_started) }}</td>
                        <td><span class="hidden">{{ log.publishing_finished }}</span>{{ convertAndFormat(log.publishing_finished) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</script>

<script>
    var widget_publishers = Vue.component('widget-publishers', {
        template: '#component-widget-publishers'
    });

    var publishers = Vue.component('publishers', {
        template: '#component-publishers',
        data: function() {
            return {
                publishers: []
            }
        },
        methods: {
            getPublishers() {
                this.$root.loading(true);
                fetch(`/internal-api/v1/studio/projects/${this.$root.project_id}/publishers`)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.publishers = json.data;
                        $("#widget-publishers-table").DataTable().destroy();
                    })
                    .then(() => {
                        //This uses the datatables dom options listed here https://datatables.net/reference/option/dom
                        $("#widget-publishers-table").DataTable({
                            "searching": true,
                            "dom": 'lrt<"pb-2">pi',
                            "order": [[ 5, "desc" ]]
                        });
                        this.$root.loading(false);
                    })
                    .catch((error) => {
                        ResponseHelper.handleErrorMessage(error, "Publishers could not be retrieved.");
                        this.$root.loading(false);
                    });
            },
            filterTable(event) {
                $("#widget-publishers-table").DataTable().search(event.target.value).draw();
            },
            deletePublisher(id) {
                if(!confirm("Are you sure you want to delete this publisher?")) {
                    return false;
                }

                this.$root.loading(true);
                fetch(`/internal-api/v1/studio/projects/${this.$root.project_id}/publishers/${id}`, { method: "DELETE" })
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.$root.loading(false);
                        notify.success("Publisher deleted.");
                        this.getPublishers();
                    })
                    .catch((error) => {
                        this.$root.loading(false);
                        ResponseHelper.handleErrorMessage(error, "Failed to delete publisher.");
                    });
            },
            convertAndFormat(timestamp) {
                if(timestamp == null) {
                    return "";
                } else {
                    return DateHelper.convertToAndFormatLocaleDateTimeString(timestamp);
                }
            }
        },
        mounted() {
            this.$root.pageLoad();
            this.getPublishers();
        }
    });

    var publisher_logs = Vue.component('publisher_logs', {
        template: '#component-publisher-logs',
        props: [ 'publisher_id' ],
        data: function() {
            return {
                destination_name: "",
                logs: []
            }
        },
        methods: {
            getLogs() {
                this.$root.loading(true);
                fetch(`/internal-api/v1/studio/projects/${this.$root.project_id}/publishers/${this.publisher_id}/logs`)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.logs = json.data.logs;
                        this.destination_name = json.data.name;
                        $("#publisher-logs-table").DataTable().destroy();
                    })
                    .then(() => {
                        $("#publisher-logs-table").DataTable({
                            "order": [[ 2, "desc" ]]
                        });
                        this.$root.loading(false);
                    })
                    .catch((error) => {
                        ResponseHelper.handleErrorMessage(error, "Logs could not be retrieved.");
                        this.$root.loading(false);
                    });
            },
            convertAndFormat(timestamp) {
                return DateHelper.convertToAndFormatLocaleDateTimeString(timestamp);
            }
        },
        mounted() {
            this.$root.pageLoad();
            this.getLogs();
        }
    });
</script>