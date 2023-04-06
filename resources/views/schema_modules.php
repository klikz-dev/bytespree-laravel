<?php echo view("components/head"); ?>
<?php echo view("components/component-toolbar"); ?>
<div id="app">
    <toolbar
        :buttons="toolbar.buttons"
        :breadcrumbs="toolbar.breadcrumbs">
    </toolbar>
    <div class="dmiux_content">
        <?php echo view('components/admin/menu', ['selected' => 'schemas']); ?>
        <div v-if="currentUser.is_admin == true" class="table-reponsive mt-2">
            <div class="row">
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="dmiux_block ml-2">
                        <div v-for="module in modules">
                            <div class="dmiux_checkbox mb-1">
                                <input class="dmiux_checkbox__input" type="checkbox" :value="module.id" v-model="schema_modules" />
                                <div class="dmiux_checkbox__check"></div>
                                <div class="dmiux_checkbox__label">{{ module.name }}</div>
                            </div>
                        </div>
                        <button @click="saveSchemaModules" type="button" class="dmiux_button">Save</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    var schema_id = <?php echo $schema_id; ?>;
    var toolbar = Vue.component('toolbar', {
        template: '#component-toolbar',
        props: [ 'breadcrumbs', 'buttons', 'record_counts' ],
        methods: {
        }
    });

    var app = new Vue({
        el: '#app',
        data: {
            schema_id: schema_id,
            toolbar: {
                "breadcrumbs": [],
                "buttons": [
                    {
                        "onclick": "window.location.href = '/admin/schemas/" + this.schema_id + "/tables';",
                        "text": "< Go Back to Schema Tables",
                        "class": "dmiux_button dmiux_button_secondary"
                    }
                ]
            },
            schema_modules: [],
            modules: [],
            currentUser : {
                "is_admin" : false
            }
        },
        components: {
            'toolbar': toolbar
        },
        methods: {
            loading: function(status) {
                if(status === true) {
                    $(".loader").show();
                }
                else {
                    $(".loader").hide();
                }
            },
            getModules: function() {
                this.loading(true);
                fetch("/internal-api/v1/explorer/modules")
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.loading(false);
                        this.modules = json.data;
                    })
                    .catch((error) => {
                        this.loading(false);
                        ResponseHelper.handleErrorMessage(error, `Failed to get modules.`);
                    });
            },
            getSchemaModules: function() {
                this.loading(true);
                fetch(`/internal-api/v1/admin/schema-modules/${this.schema_id}`)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.loading(false);
                        this.schema_modules = json.data;
                    })
                    .catch((error) => {
                        this.loading(false);
                        ResponseHelper.handleErrorMessage(error, `Failed to get schema modules.`);
                    });
            },
            getCurrentUser: function() {
                fetch("/internal-api/v1/me")
                    .then(response => response.json())
                    .then(json => {
                        this.currentUser = json.data;
                    })
            },
            getBreadcrumbs: function() {
                fetch(baseUrl + "/internal-api/v1/crumbs")
                    .then(response => response.json())
                    .then(json => {
                        this.toolbar.breadcrumbs = json.data;
                    });
            },
            saveSchemaModules: function() {
                let options = FetchHelper.buildJsonRequest({
                    modules: this.schema_modules
                }, 'PUT');

                this.loading(true);
                fetch(`/internal-api/v1/admin/schema-modules/${this.schema_id}`, options)
                    .then(response => response.json())
                    .then(json => {
                        this.loading(false);
                        this.getSchemaModules();
                        notify.success("Modules saved successfully!");
                    })
                    .catch((error) => {
                        this.loading(false);
                        ResponseHelper.handleErrorMessage(error, `Failed to save modules.`);
                    });
            }
        },
        mounted: function() {
            this.getBreadcrumbs();
            this.getCurrentUser();
            this.getModules();
            this.getSchemaModules();
        }
    })
</script>
<?php echo view("components/foot"); ?>