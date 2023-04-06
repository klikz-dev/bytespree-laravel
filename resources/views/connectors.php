<?php echo view("components/head"); ?>
<?php echo view("components/component-toolbar"); ?>
<?php echo view("components/modals/connector", array('error' => ' ' )); ?>
<?php echo view("components/modals/update_connector"); ?>
<div id="app">
    <toolbar
        :buttons="toolbar.buttons"
        :breadcrumbs="toolbar.breadcrumbs">
    </toolbar>
    <div class="dmiux_content">
        <?php echo view('components/admin/menu', ['selected' => 'connectors']); ?>
        <div class="dmiux_grid-cont dmiux_grid-cont_fw dmiux_data-table dmiux_data-table__cont">
            <table v-if="connectors.length > 0" id="integrations-data" class="dmiux_data-table__table">
                <thead>
                    <tr>
                        <th></th>
                        <th>Name</th>
                        <th class="text-right">Version</th>
                        <!--<th class="text-right">Monthly Cost</th>-->
                        <th>Enabled</th>
                        <th>Uses Table Controls</th>
                        <th>Uses Webhooks</th>
                        <th>Last Modified</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="connector in connectors" :class="isUpdateNeeded(connector.name, connector.version) ? 'bg-light-green' : ''">
                        <td>
                            <div class="dmiux_data-table__actions">
                                <div class="dmiux_actionswrap dmiux_actionswrap--bin" @click="removeConnector(connector)" data-toggle="tooltip" title="Remove Connector"></div>
                            </div>
                        </td>
                        <td>{{ connector.name }} <a href="#" v-if="isUpdateNeeded(connector.name, connector.version)" @click="updateConnector(connector)">[an update is available]</a></td>
                        <td class="text-right">{{ connector.version }}</td>
                        <!--todo: restore with custom pricing feature
                        <td class="text-right">$200</td>-->
                        <td v-if="connector.is_active == true || connector.is_active == 'true'">Yes</td>
                        <td v-else>No</td>
                        <td v-if="connector.use_tables == true || connector.use_tables == 'true'">Yes</td>
                        <td v-else>No</td>
                        <td v-if="connector.use_hooks == true || connector.use_hooks == 'true'">Yes</td>
                        <td v-else>No</td>
                        <td>{{ connector.updated_at_formatted }}</td>
                    </tr>
                </tbody>
            </table>
            <div v-else class="alert alert-info mt-2">There are no connectors yet. <a href="javascript:void(0)" @click="addConnector();">Add a connector</a>.</div>
        </div>
    </div>
    <connector :open="modal_add_connector" :teamconnectors="teamconnectors"></connector>
    <update-connector :selected_connector="selected_connector" :orchestration_connector="orchestration_connector"></update-connector>
</div>
<script>
    var toolbar = Vue.component('toolbar', {
        template: '#component-toolbar',
        props: [ 'breadcrumbs', 'buttons'],
        methods: {
        }
    });

    var app = new Vue({
        el: '#app',
        data: {
            toolbar: {
                "breadcrumbs": [],
                "buttons": [
                    {
                        "onclick": "app.addConnector();",
                        "text": "Add Connector&nbsp; <span class=\"fas fa-plus\"></span>",
                        "class": "dmiux_button dmiux_button_secondary"
                    }
                ]
            },
            connectors : [],
            teamconnectors: [],
            currentUser : {
                "is_admin" : false
            },
            selected_connector: [],
            modal_add_connector: false,
            orchestration_connector: []
        },
        components: {
            'toolbar': toolbar,
            'connector': connector,
            'update-connector': updateConnector
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
            getBreadcrumbs() {
                fetch(`${baseUrl}/internal-api/v1/crumbs`)
                    .then(response => response.json())
                    .then(json => {
                        this.toolbar.breadcrumbs = json.data;
                    });
            },
            getCurrentUser() {
                fetch(`${baseUrl}/internal-api/v1/me`)
                    .then(response => response.json())
                    .then(json => {
                        this.currentUser = json.data;
                    })
            },
            getConnectors() {
                this.loading(true);
                fetch(`${baseUrl}/internal-api/v1/connectors`)
                    .then(response => response.json())
                    .then(json => {
                        this.connectors = json.data;
                        for (var i=0; i<this.connectors.length; i++) {
                            this.connectors[i].updated_at_formatted = DateHelper.formatLocaleCarbonDate(this.connectors[i].updated_at);
                        }
                        this.loading(false);
                        $('.dmiux_popup__close_popup').trigger('click');
                    });
            },
            getTeamConnectors() {
                fetch(`/internal-api/v1/admin/connectors/available`)
                    .then(response => response.json())
                    .then(resp => {
                        if(resp.status == "ok") {
                            this.teamconnectors = resp.data;
                        }
                    });
            },
            addConnector() {
                this.modal_add_connector = true;
            },
            updateConnector(connector) {
                var orchestration_connector = this.getOrchestrationConnector(connector.name);
                this.selected_connector = connector;
                this.orchestration_connector = orchestration_connector;
                openModal("#modal-update-connector");
            },
            removeConnector(connector) {
                if(connector.partner_integration_count > 0) {
                    alert('This connector is currently in use and cannot be removed.');
                    return;
                }

                if (confirm('Are you sure you want to remove this connector?')) {
                    this.loading(true);
                    fetch(`${baseUrl}/internal-api/v1/admin/connectors/${connector.id}`, {method: 'delete'})
                    .then(response => {
                        this.loading(false);
                        return response;
                    })
                    .then(response => response.json())
                    .then(json => {
                        if(json.status == 'error'){
                            notify.danger(json.message);
                            return;
                        }

                        if(json.status == 'ok'){
                            notify.success(json.message);
                            this.getConnectors();
                        }
                    });
                }
            },
            isUpdateNeeded(name, version) {
                var connector = this.teamconnectors.filter(function(connector) {
                    if(connector.name == name && version_compare(connector.version, version))
                        return connector;
                });

                if(connector.length > 0)
                    return true;
                else 
                    return false;
            },
            getOrchestrationConnector(name) {
                var connector = this.teamconnectors.filter(function(connector) {
                    if(connector.name == name)
                        return connector;
                });

                if(connector.length > 0)
                    return connector[0];
                else 
                    return {};
            }
        },
        mounted: function() {
            this.getBreadcrumbs();
            this.getConnectors();
            this.getTeamConnectors();
            this.getCurrentUser();
        }
    });
</script>
<?php echo view("components/foot"); ?>
