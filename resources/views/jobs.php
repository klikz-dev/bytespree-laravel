<?php echo view("components/head"); ?>
<?php echo view("components/component-toolbar"); ?>
<div id="app">
    <toolbar
        :buttons="toolbar.buttons"
        :breadcrumbs="toolbar.breadcrumbs">
    </toolbar>
    <div class="dmiux_content dmiux_grid-cont dmiux_grid-cont_fw">
        <div class="dmiux_data-table dmiux_data-table__cont mt-2">
            <div v-if="log != ''" class="dmiux_block mb-0">
                <label class="mb-2 w-100">
                    Build Log 
                    <span class="float-right">
                        <button type="button" @click="clearOutput()" class="dmiux_query-flags__remove-all dmiux_clear-all"><i class="dmiux_clear-all__icon dmiux_clear-all__icon_remove"></i></button>
                    </span>
                </label>
                <pre>{{ log }}</pre>
            </div>
            <table v-else-if="jobs.length > 0" id="jobs" class="dmiux_data-table__table">
                <thead>
                    <tr>
                        <th></th>
                        <th>Job Type</th>
                        <th>Connector</th>
                        <th>Database</th>
                        <th>Table</th>
                        <th>Started At</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="job in jobs" class="dmiux_input">
                        <td :class="current_user.is_admin ? 'pl-1' : 'pl-4'" class="table-icons pl-1">
                            <div class="dmiux_data-table__actions">
                                <!-- This functionality will be revisted later -->
                                <!-- <div v-if="current_user.is_admin" class="dmiux_actionswrap dmiux_actionswrap--stop" @click="stopJob(job.id)" data-toggle="tooltip" title="Stop Job"></div> -->
                                <div v-if="checkPerms('view_logs', job.database_id)" class="dmiux_actionswrap dmiux_actionswrap--page ml-3" @click="getJobOutput(job.id)" data-toggle="tooltip" title="View Running Output"></div>
                            </div>
                        </td>
                        <td>{{ job.job_type }}</td>
                        <td>{{ job.connector }}</td>
                        <td>{{ job.database }}</td>
                        <td>{{ job.table_name }}</td>
                        <td>{{ convertToLocal(job.started_at) }}</td>
                    </tr>
                </tbody>
            </table>
            <div v-else class="alert alert-info mt-2">There are no running jobs at the moment.</div>
        </div>
    </div>
</div>
<script>
    team_channel.bind('running-jobs', function(data) {
        app.getJobs();
    });

    var toolbar = Vue.component('toolbar', {
        template: '#component-toolbar',
        props: [ 'breadcrumbs', 'buttons', 'record_counts' ],
        methods: {
        }
    });

    var app = new Vue({
        el: '#app',
        data: {
            toolbar: {
                "breadcrumbs": [
                    {   
                        title: "Running Jobs",
                        location: "/Jobs"
                    }
                ],
                "buttons": []
            },
            current_user: [],
            permissions: [],
            jobs: [],
            log: ""
        },
        components: {
            'toolbar': toolbar
        },
        methods: {
            loading(status) {
                if(status === true) {
                    $(".loader").show();
                } else {
                    $(".loader").hide();
                }
            },
            getCurrentUser() {          
                fetch(`${baseUrl}/internal-api/v1/me`)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.current_user = json.data;
                    });         
            },
            getPermissions() {
                fetch(`${baseUrl}/internal-api/v1/me/permissions?product=datalake`)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.permissions = json.data;
                    });
            },
            checkPerms(perm_name, product_child_id) {
                var result = false;
                if (this.current_user.is_admin === true) {
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
            getJobs() {
                this.loading(true);
                fetch(`${baseUrl}/internal-api/v1/jobs`)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.jobs = json.data;
                        $('#jobs').DataTable().destroy();
                    })
                    .then(() => {
                        $('#jobs').DataTable();
                        this.loading(false);
                    });
            },
            stopJob(build_id) {
                this.loading(true);
                fetch(`${baseUrl}/internal-api/v1/jobs/${build_id}/stop`)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        notify.success("Job has been stopped");
                        this.getJobs();
                        this.loading(false);
                    })
                    .catch((error) => {
                        this.loading(false);
                        if(error.json != null) { 
                            ResponseHelper.handleErrorMessage(error, error.json.message); 
                        } else {
                            ResponseHelper.handleErrorMessage(error, "Unable to stop job. This could be because it has already finished."); 
                        }
                    });
            },
            getJobOutput(build_id) {
                this.loading(true);
                fetch(`${baseUrl}/internal-api/v1/jobs/${build_id}/output`)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        $('#jobs').DataTable().destroy();
                        this.log = json.data;
                        this.loading(false);
                    })
                    .catch((error) => {
                        ResponseHelper.handleErrorMessage(error, "Unable to get job output");  
                        this.loading(false);
                    });
            },
            clearOutput() {
                this.log = "";
                this.getJobs();
            },
            convertToLocal(timestamp) {
                return DateHelper.formatLocaleCarbonDate(timestamp);
            }
        },
        mounted() {
            this.getCurrentUser();
            this.getPermissions();
            this.getJobs();
        }
    })
</script>
<?php echo view("components/foot"); ?>
