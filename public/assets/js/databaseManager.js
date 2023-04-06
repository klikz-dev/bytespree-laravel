var baseUrl = "";

var toolbar = Vue.component('toolbar', {
    template: '#component-toolbar',
    props: [ 'breadcrumbs', 'buttons' ]
});

const router = new VueRouter({
    routes: [
        {
            path: '/tables/:tableName/:tableSchema/:index',
            name: 'tables_root',
            component: tables, 
            props: true,
            children: [
                { 
                    path: '',
                    name: 'tables',
                    component: table_details,
                    props: true
                },
                { 
                    path: 'logs/:table_id', 
                    name: 'logs',
                    component: table_logs,
                    props: true
                },
                { 
                    path: 'details/:table_id/:log_id', 
                    name: 'details',
                    props: true,
                    component: log_details 
                }
            ]
        },
        {
            path: '/views/:viewName/:index',
            name: 'views',
            component: views,
            props: true
        },
        {
            path: '/foreign_tables/:tableName/:tableSchema/:index',
            name: 'foreign_tables',
            component: foreign_tables,
            props: true
        }
    ]
})

var app = new Vue({
    el: '#app',
    data: {
        ready: false,
        toolbar: {
            "breadcrumbs": [],
            "buttons": [
                {
                    "text": "+ New Table",
                    "class": "dmiux_button add-table-database",
                    "onclick": "app.addTable()",
                }
            ]
        },
        currentUser: {
            "is_admin": false,
            "name": ""
        },
        control_id: control_id,
        tables: [],
        views: [],
        foreign_tables: [],
        foreign_schemas: [],
        pollingForViews: null,
        pollingForViewName: null,
        pollingForSchema: null,
        tab: "tables",
        loadedItems: {
            getViews: false,
            getTables: false,
            getForeignTables: false,
        }
    },
    router,
    components: {
        'databaseManager': databaseManager,
        'toolbar': toolbar,
        'addTable': addTable,
        'addView': addView,
        'addIndex': addIndex,
        'addDatabase': addDatabase
    },
    watch: {
        $route(to, from) {
            if(to.name == "foreign_tables") {
                if(to.params.selectedTable == undefined) {
                    to.params.selectedTable = this.$refs.databaseManager.selectedTable;
                }
                this.$refs.databaseManager.getTableDetails(to.params.tableName, to.params.tableSchema, to.params.index, 'foreign');
            }
            else if(to.name == "views") {
                if(to.params.selectedView == undefined) {
                    to.params.selectedView = this.$refs.databaseManager.selectedView;
                }
                this.$refs.databaseManager.getViewDetails(to.params.viewName, to.params.index);
            }
        }
    },
    methods: {
        addTable() {
            this.$refs.addTable.modalOpen();
        },
        addForeignDatabase() {
            $(document).on("mousedown", "#dmiux_body", this.$refs.addDatabase.closeConnectModal);
            $(document).off('keydown', closeModalOnEscape);
            $(document).on("keydown", this.$refs.addDatabase.closeConnectModal);
            openModal('#modal-add_foreign_database');
        },
        addView() {
            app.$refs.manageView.sql = "";
            app.$refs.manageView.name = "";
            app.$refs.manageView.type = "normal";
            app.$refs.manageView.frequency = "daily";
            app.$refs.manageView.schedule = {
                "month_day": 1,
                "week_day": 0,
                "hour": 0,
                "month": 1
            };
            app.$refs.manageView.history_guid = "";
            app.$refs.manageView.editing = false;
            app.$refs.manageView.build_on = "schedule";
            app.$refs.manageView.upstream_build_id = "";
            app.$refs.manageView.downstream_views = [];
            app.$refs.manageView.dependent_views = [];
            app.$refs.manageView.foreign_dependent_views = [];
            app.$refs.manageView.orig_name = '';
            app.$refs.manageView.orig_type = '';

            $(document).on("mousedown", "#dmiux_body", app.$refs.manageView.modalClose);
            $(document).on('keydown', app.$refs.manageView.modalClose);

            openModal('#modal-add_view');
        },
        checkForError(json) {
            this.loading(false);
            if (json.status == "error") {
                notify.send(json.message, "danger");
                return false;
            }
            return true;
        },
        loading(status) {
            if (status === true) {
                $(".loader").show();
            } else {
                $(".loader").hide();
            }
            this.ready = status;
        },
        getCurrentUser() {          
            fetch(`/internal-api/v1/me`)
                .then(response => response.json())
                .then(json => {
                    if (!app.checkForError(json)) {
                        return;
                    }
                    this.currentUser = json.data;
                });         
        },
        getBreadcrumbs() {
            fetch(`/internal-api/v1/crumbs`)
                .then(response => response.json())
                .then(json => {
                    if (!app.checkForError(json)) {
                        return;
                    }
                    this.toolbar.breadcrumbs = json.data;
                });
        },
        getTables(refresh = false) {
            this.loading(true);
            this.destroyTooltips();
            this.run("getTables");
            fetch(`/internal-api/v1/data-lakes/${this.control_id}/tables`)
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    this.tables = json.data;
                    if(refresh == true) {
                        this.$refs.databaseManager.getTableDetails(this.$refs.databaseManager.tableName, this.$refs.databaseManager.tableSchema, this.$refs.databaseManager.index, 'normal');
                    }
                })
                .then(() => {
                    this.complete("getTables");
                    this.processRoute();
                    this.createTooltips();
                })
                .catch((error) => {
                    ResponseHelper.handleErrorMessage(error, "Unable to get tables for this database.");
                });
        },
        getViews(when_done) {
            this.loading(true);
            this.destroyTooltips();
            this.run("getViews");
            fetch(`/internal-api/v1/data-lakes/${this.control_id}/views`)
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    this.views = json.data;
                })
                .then(() => {
                    this.$emit('getViewsFinished', this.views);
                    if (when_done !== undefined) {
                        when_done();
                    }
                    this.complete("getViews");
                    this.processRoute();
                    this.createTooltips();
                })
                .catch((error) => {
                    ResponseHelper.handleErrorMessage(error, "Unable to get views for this database.");
                });
        },
        getForeignTables(call_back) {
            this.loading(true);
            this.destroyTooltips();
            this.run("getForeignTables");
            fetch(`/internal-api/v1/data-lakes/${this.control_id}/foreign-databases/tables`)
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    if(json.data.tables != undefined) {
                        this.foreign_tables = json.data.tables;
                        this.foreign_schemas = json.data.schemas;
                    }
                })
                .then(() => {
                    this.complete("getForeignTables");
                    this.processRoute();
                    this.createTooltips();
                })
                .then(() => {
                    if (call_back != undefined) {
                        call_back();
                    }
                })
                .catch((error) => {
                    ResponseHelper.handleErrorMessage(error, "Unable to get foreign tables for this database.");
                });
        },
        switchToTables(ignore_clear = false) {
            this.clearPath(ignore_clear);
            this.tab = "tables";
            this.toolbar.buttons[0].text = "+ New Table";
            this.toolbar.buttons[0].onclick = "app.addTable()";
        },
        switchToViews(ignore_clear = false) {
            this.clearPath(ignore_clear);
            this.tab = "views";
            this.toolbar.buttons[0].text = "+ New View";
            this.toolbar.buttons[0].onclick = "app.addView()";
        },
        switchToForeignTables(ignore_clear = false) {
            this.clearPath(ignore_clear);
            this.$refs.databaseManager.clearSelectedTable();
            this.tab = "foreign_tables";
            this.toolbar.buttons[0].text = "+ Connect a Database";
            this.toolbar.buttons[0].onclick = "app.addForeignDatabase()";
        },
        clearPath(ignore_clear = false) {
            if(this.$route.fullPath != "/" && ignore_clear == false) {
                this.$router.push("/");
            }
        },
        processRoute() {
            if(this.allItemsLoaded()) {
                if(this.$route.name == "views") {
                    if(this.views[this.$route.params.index] != undefined && this.views[this.$route.params.index].view_schema + "." + this.views[this.$route.params.index].view_name == this.$route.params.viewName) {
                        this.switchToViews(true);
                        this.$refs.databaseManager.active_schema_view = this.$route.params.viewName.split('.')[0];
                        this.$route.params.selectedView = this.$refs.databaseManager.selectedView;
                        this.$refs.databaseManager.getViewDetails(this.$route.params.viewName, this.$route.params.index);
                    }
                    else {
                        notify.info("View does not exist it may have been deleted or renamed");
                        this.clearPath();
                    }
                }
                else if(this.$route.name == "foreign_tables") {
                    this.switchToForeignTables(true);
                    this.$refs.databaseManager.active_schema_foreign = this.$route.params.tableSchema;
                    this.$route.params.selectedTable = this.$refs.databaseManager.selectedTable;
                    this.$refs.databaseManager.getTableDetails(this.$route.params.tableName, this.$route.params.tableSchema, this.$route.params.index, 'foreign');
                }
            }
        },
        showView(schema, new_view_name) {
            // Find the view's index
            var index = this.views.findIndex((view) => view.view_name == new_view_name && view.view_schema == schema);

            if (index === -1) {
                this.processRoute();
                return;
            }

            var view = this.views[index];

            // Set our new route
            this.$refs.databaseManager.$router.push({
                name: 'views',
                params: {
                    viewName: view.view_schema + '.' + view.view_name,
                    index: index
                }}).catch(error => {
                    // Ignore navigation duplicated errors
                    if (error.name != "NavigationDuplicated") {
                      throw error;
                    }
                  });

            // Handle our new route
            this.processRoute();
        },
        openModalOnLoad(){
            if (window.location.href.indexOf("open_add_modal") > -1) {
                this.$nextTick().then(this.addTable());
            }
        },
        run(name) {
            if (this.loadedItems[name] != undefined) {
                this.loadedItems[name] = false;
            } else {
                console.error(`Route ${name} is not defined in the loadedItems object.`);
            }
        },
        complete(name) {
            if (this.loadedItems[name] != undefined) {
                this.loadedItems[name] = true;
                if (this.allItemsLoaded()) {
                    this.loading(false);
                }
            } else {
                console.error(`Route ${name} is not defined in the loadedItems object.`);
            }
        },
        destroyTooltips() {
            if (this.allItemsLoaded()) {
                this.createTooltips();
                $(".tooltip-pretty").tooltipster('destroy');
            }
        },
        createTooltips() {
            if (this.allItemsLoaded()) {
                $(".tooltip-pretty").tooltipster({
                    'restoration': 'previous'
                });
            }
        },
        allItemsLoaded() {
            return Object.values(this.loadedItems).filter((item) => item === false).length === 0;
        }
    },
    mounted() {
        if(from_download_link) {
            window.open(`${window.location.href.replace(location.hash, '')}?download`, '_blank');
            window.history.pushState(
                '',
                document.title,
                `/data-lake/database-manager/${this.control_id}`
            );
        }

        this.getBreadcrumbs();
        this.getCurrentUser();
        this.getTables();
        this.getForeignTables();
        this.getViews();
        notyfHelper.showUrlMessage();
    },
    updated(){
        this.openModalOnLoad();
    },
    beforeDestroy () {
        clearInterval(this.pollingForViews);
    }
});