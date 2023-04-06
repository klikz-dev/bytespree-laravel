var toolbar = Vue.component('toolbar', {
    template: '#component-toolbar',
    props: [
        'breadcrumbs',
        'buttons',
        'tables',
        'current_user',
        'record_counts'
    ],
    methods: {
        selectTable: function (event) {
            this.$root.loading(true);
            var catalog = event.target.value.split(".")[0];
            var table_name = event.target.value.split(".")[1];
            var table_arr = app.tables.filter(function(table){ 
                if(table.table_name == table_name && table.table_catalog == catalog){
                    return table;
                }
            });

            if(table_arr.length > 0){
                var schema = table_arr[0].table_schema;
                window.location = `/studio/projects/${this.$root.project_id}/tables/${schema}/${table_name}`;
            } else {
                notify.send("The table you requested does not exist in this project.", 'info'); 
                this.$root.loading(false);           
            }
           
        },
        getTableMappings: function ()
        {
            this.$root.setCookie("downloadStarted", 0, 100);
            this.$root.checkDownloadCookie();
            window.location.href = `/studio/projects/${this.$root.project_id}/download-mappings`;
        },
        getSchema: function () 
        {
            fetch(`/internal-api/v1/studio/projects/${this.$root.project_id}/export?type=csv`)
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    notify.success("Your export has started. You will be notified by email when it's complete.");
                })
                .catch((error) => {
                    ResponseHelper.handleErrorMessage(error, "Failed to start export");
                });
        }    
    }
});

const router = new VueRouter({
    routes: [
        {
            path: '/',
            redirect: '/tables'
        },
        {
            path: '/tables',
            name: 'tables',
            component: widget_tables, 
            props: true
        },
        {
            path: '/activity',
            name: 'activity',
            component: widget_activity, 
            props: true
        },
        {
            path: '/flags',
            name: 'flags',
            component: widget_flags, 
            props: true
        },
        {
            path: '/attachments',
            name: 'attachments',
            component: widget_links, 
            props: true
        },
        {
            path: '/snapshots',
            name: 'snapshots',
            component: widget_snapshots, 
            props: true
        },
        {
            path: '/queries',
            name: 'queries',
            component: widget_queries, 
            props: true
        },
        {
            path: '/publishers',
            name: 'publishers_root',
            component: widget_publishers, 
            props: true,
            children: [
                { 
                    path: '',
                    name: 'publishers',
                    component: publishers,
                    props: true
                },
                { 
                    path: 'logs/:publisher_id', 
                    name: 'logs',
                    component: publisher_logs,
                    props: true
                }
            ]
        },
        {
            path: '/users',
            name: 'users',
            component: widget_users, 
            props: true
        },
        {
            path: '/settings',
            name: 'settings',
            component: widget_settings, 
            props: true
        }
    ]
})

var app = new Vue({
    el: '#app',
    name: 'StudioProject',
    data: {
        ready: false,
        customers: [],
        toolbar: {
            "breadcrumbs": [],
            "buttons": []
        },
        currentUser : {
            "is_admin" : false,
            "name": "",
            "user_handle": ""
        },  
        project_id: project_id,
        destination_schema_id: destination_schema_id,
        tables: [],
        tab_tables: [],      
        projects: [],
        manage_tables: [],
        replace_table_name: "",
        columns: [],
        completed: completed,
        not_found: false,
        permissions: [],
        rule_data: [],
        file_name: '',
        project_detail: {
            name: ""
        }
    },
    router,
    components: {
        'toolbar': toolbar
    },
    methods: {
        checkPerms: function (perm_name) {
            var result = false;
            if (this.currentUser.is_admin === true) {
                result = true;
            }
            else {
                for(var i=0; i < this.permissions.length; i++) {
                    if (this.permissions[i].product_child_id == this.project_id) {
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
        pageLoad()
        {
            this.ready = false;
        },
        loading: function(status) {
            if(status === true) {
                $(".loader").show();
            }
            else {
                $(".loader").hide();
                this.ready = true;
            }
        },
        getBreadcrumbs() {
            this.loading(true);
            fetch(`/internal-api/v1/crumbs`)
                .then(response => response.json())
                .then(json => {
                    this.toolbar.breadcrumbs = json.data;
                });
        },
        getCurrentUser() {
            fetch(`/internal-api/v1/me`)
                .then(response => response.json())
                .then(json => {
                    this.currentUser = json.data;
                })
        },
        getTables() {
            fetch(`/internal-api/v1/studio/projects/${this.project_id}/tables?get_types=false`)
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    if (json.status == "ok") {
                        this.tables = json.data.tables;
                    } else {
                        alert('The Server is unavailable at this time. You will be redirected back.')
                        window.location.href = `/studio`;
                    }
                })
                .catch((error) => {
                    ResponseHelper.handleErrorMessage(error, "Failed to get tables");
                });
        },
        checkForError(json) {
            if(json.status == "error") {
                alert(json.message);
                this.loading(false);
                return false;
            }

            return true;
        },
        getCookie(name) {
            var i, x, y, ARRcookies = document.cookie.split(";");
            for (i = 0; i < ARRcookies.length; i++) {
                x = ARRcookies[i].substr(0, ARRcookies[i].indexOf("="));
                y = ARRcookies[i].substr(ARRcookies[i].indexOf("=") + 1);
                x = x.replace(/^\s+|\s+$/g, "");
                if (x == name) {
                    return y ? decodeURI(unescape(y.replace(/\+/g, ' '))) : y; //;//unescape(decodeURI(y));
                }
            }
        },
        setCookie(name, value, expiracy) {
            var exdate = new Date();
            exdate.setTime(exdate.getTime() + expiracy * 1000);
            var c_value = escape(value) + ((expiracy == null) ? "" : "; expires=" + exdate.toUTCString());
            document.cookie = name + "=" + c_value + '; path=/';
        },
        checkDownloadCookie() {
            if (this.getCookie("downloadStarted") == 1) {
                this.setCookie("downloadStarted", "false", 100);
                this.loading(false);
            }
            else {
                this.loading(true);
                setTimeout(this.checkDownloadCookie, 1000);
            }
        },
        getAllPermissions() {
            fetch('/internal-api/v1/me/permissions?product=studio')
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    this.permissions = json.data;
                });
        },
        getProjectDetails(){
            fetch(`/internal-api/v1/studio/projects/${this.project_id}/details`)
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    this.project_detail = json.data;
                }); 
        }
    },
    mounted() { 
        if(from_download_link) {
            window.open(`${window.location.href.replace(location.hash, '')}?download`, '_blank');
            window.history.pushState(
                '',
                document.title,
                `/studio/projects/${this.project_id}`
            );
            this.$router.push("/attachments");
        }

        this.getCurrentUser();
        this.getBreadcrumbs();
        this.getTables(false);
        this.getAllPermissions();
        this.getProjectDetails();
        notyfHelper.showUrlMessage();
    }
})