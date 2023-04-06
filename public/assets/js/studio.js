var baseUrl = "";
Vue.component('multiselect', window.VueMultiselect.default);
/** Vue.js Component
 *******************************************************************************
 * card-projects
 *******************************************************************************
 * Renders the listing of available databases on card format
 */
var cardProjects = Vue.component('card-projects', {
    template: '#component-card-projects',
    props: [ 'projects' ],
    name: "ProjectCards",
    data: function() {
        return {
            file: '',
            action: '',
            library: '',
            project: {}
        }
    },
    methods: {
        /** Method to Open Project in Blueprint (bp) **/
        openProject: function (control_id) {
            app.loading(true);
            window.location.href = baseUrl + "/studio/projects/" + control_id;
        },
        editProject: function (project) {
            app.selected_project = JSON.parse(JSON.stringify(project));
            app.editing = true;
            openModal("#modal-add_project");
        },
        databaseNames: function (databases) 
        {
            let dbs = databases.map((db) => db.database);

            dbs.shift(); // Remove the first item as we're already displaying it

            return dbs.join(', ');
        },
        showProjectUsers(project) {
            this.$parent.selected_project = project;
            openModal("#modal-project_users");
        }
    },
});

var projectUsers = Vue.component('project-users', {
    template: '#project-users-modal-template',
    props: [ 'selected_project' ],
    name: "ProjectUsers",
    computed: {
        users() {
            return this.$parent.getActiveProjectMembers(this.selected_project.members);
        }
    },
    methods: {
        closeModal() {
            closeModal('#modal-project_users');
        }
    }
});

var addProject = Vue.component('add-project', {
    template: '#add-project-modal-template',
    props: [ 'selected_project', 'editing' ],
    name: "AddProject",
    data: function() {
        return {
            project: {
                name: "",
                display_name: "",
                description: "",
                partner_integration_id: 0,
                foreign_databases: []
            },
            databases: [],
            fetchedSchema: false
        }
    },
    watch: {
        selected_project() {
            if(this.editing == true) {
                this.project = this.selected_project;

                this.databases = this.$root.databases.user_databases.filter((db) => {
                    if(db.id != this.project.partner_integration_id && !this.project.foreign_databases.map((f_db) => { return f_db.database }).includes(db.database)) {
                        return db;
                    }
                });

                this.databases = this.project.foreign_databases.concat(this.databases);
            }
            else {
                this.databases = this.$root.databases.user_databases;
            }
        }
    },
    methods: {
        resetSchema: function() {
            if (this.editing) {
                return;
            }

            this.project.name = '';
        },
        generateSchemaName: function() {
            if (this.editing ) {
                return;
            }

            this.project.name = '';

            var schemaName = this.project.display_name.trim();

            if(schemaName.length < 1){
                this.project.name = '';
                return;
            }

            let options = FetchHelper.buildJsonRequest({
                display_name: schemaName
            });

            fetch(baseUrl + "/internal-api/v1/studio/projects/suggest-schema", options)
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    this.project.name = json.data.suggested_name;
                });

            return;
        },
        cleanupDescription: function () {
            this.project.description = this.project.description.substring(0, 200);
        },
        addProject: function () {

            if(this.project.display_name == "" || this.project.name.length == 0)
            {
                notify.send("Name cannot be empty.", 'danger');
                return;
            }

            if (this.project.partner_integration_id == 0 || this.project.partner_integration_id == "") {
               notify.send('Please select at least one database.', 'danger'); 
               return;
            }

            let options = FetchHelper.buildJsonRequest({
                description: this.project.description,
                display_name: this.project.display_name,
                foreign_databases: this.project.foreign_databases.map((db) => db.id).filter((id) => id != this.project.partner_integration_id),
                name: this.project.name,
                partner_integration_id: this.project.partner_integration_id
            });

            app.loading(true);
            fetch(baseUrl + "/internal-api/v1/studio/projects", options)
                .then(response => {
                    app.loading(false);
                    return response;
                })
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    window.location.href = `${baseUrl}/studio/projects/${json.data.project_id}`
                })
                .catch((error) => {
                    if(error.json == null)
                        ResponseHelper.handleErrorMessage(error, "An error occurred while adding this project.");
                    else
                        ResponseHelper.handleErrorMessage(error, error.json.message);
                });
        },
        editProject: function () {
            let options = FetchHelper.buildJsonRequest({
                description: this.project.description,
                display_name: this.project.display_name,
                foreign_databases: this.project.foreign_databases.map((db) => db.id).filter((id) => id != this.project.partner_integration_id)
            }, 'put');
            
            app.loading(true);
            fetch(`${baseUrl}/internal-api/v1/studio/projects/${this.project.id}`, options)
                .then(response => {
                    app.loading(false);
                    return response;
                })
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    this.clearProject();
                    $('.dmiux_popup__close_popup').trigger('click');
                    app.getProjects();
                })
                .catch((error) => {
                    if(error.json == null) 
                        ResponseHelper.handleErrorMessage(error, "An error occurred while editing this project.");
                    else 
                        ResponseHelper.handleErrorMessage(error, error.json.message);
                });
        },
        foreignDatabaseHandling()
        {
            if(this.editing == true)
                return; 
                
            if(this.project.foreign_databases.length == 0) { 
                this.project.partner_integration_id = 0 
            }
            else if (this.project.foreign_databases.length == 1) {
                this.project.partner_integration_id = this.project.foreign_databases[0].id;
            }
            else {
                var main_removed = true;
                for(var index = 0; index < this.project.foreign_databases.length; index++)
                {
                    if(this.project.foreign_databases[index].id == this.project.partner_integration_id)
                        main_removed = false;
                }

                if(main_removed) 
                    this.project.partner_integration_id = this.project.foreign_databases[0].id;
            }
        },
        inDatabase(db) {
            var check = this.project.foreign_databases.filter((fdb) => {
                if(fdb.id == db.id) {
                    return fdb;
                }
            });

            if(check.length > 0) 
                return true;
            else 
                return false;
        },
        clearProject() {
            this.project = {
                name: "",
                description: "",
                partner_integration_id: 0,
                foreign_databases: [],
                primary_database: ''
            };
            this.databases = this.$root.databases.user_databases;
        }
    },
    computed: {
        canSubmitProject(){
            return this.project.name.length > 0;
        }
    },
    directives: {
        'click-outside': {
            bind: function(el, binding, vNode) {
                // Provided expression must evaluate to a function.
                if (typeof binding.value !== 'function') {
                    const compName = vNode.context.name
                let warn = `[Vue-click-outside:] provided expression '${binding.expression}' is not a function, but has to be`
                if (compName) { warn += `Found in component '${compName}'` }
                
                console.warn(warn)
                }
                // Define Handler and cache it on the element
                const bubble = binding.modifiers.bubble
                const handler = (e) => {
                if (bubble || (!el.contains(e.target) && el !== e.target)) {
                    binding.value(e)
                }
                }
                el.__vueClickOutside__ = handler

                // add Event Listeners
                document.addEventListener('click', handler)
			},
            unbind: function(el, binding) {
                // Remove Event Listeners
                document.removeEventListener('click', el.__vueClickOutside__)
                el.__vueClickOutside__ = null

            }
        }
    }
});

/** Vue.js Component
 *******************************************************************************
 * toolbar
 *******************************************************************************
 * Renders the toolbar in which the application controls and breadcrumbs are
 * displayed.
 */
var toolbar = Vue.component('toolbar', {
    template: '#component-toolbar',
    props: [ 'breadcrumbs', 'buttons', 'tags', 'record_counts', 'current_user', 'servers' ],
    name: "Toolbar",
    methods: {
        filterProjects: function (event) {
            app.getProjectsFilter(event.target.value);
        },
        compare: function () {
            window.location.href = baseUrl + "/data-lake/compare"
        },
        createProject: function ()
        {
            app.editing = false;
            app.selected_project = {
                id: 0
            };
            openModal("#modal-add_project");
        }
    }
});

/** Vue.js Application
 *******************************************************************************
 * app
 *******************************************************************************
 * Controls the UX for the data warehouse application.
 */
var app = new Vue({
    el: '#app',
    name: 'Studio',
    data: {
        toolbar: {
            "breadcrumbs": [],
            "buttons": []
        },
        currentUser: {
            "is_admin": false,
            "name": ""
        },
        projects: [],
        unfiltered_projects: [],
        selected_project: [],
        editing: false,
        databases: {
            user_databases: [],
            other_databases: []
        },
        servers: [],
        tags: [],
        tag: "",
        permissions: [],
        warehouse_permissions: [],
        user_permissions: {
            name: []
        },
        flash_error : flash_error,
        loaded : false
    },
    components: {
        'addProject': addProject,
        'projectUsers': projectUsers
    },
    methods: {
        /** Vue.js Method
          *******************************************************************************
          * checkPerms
          * Params:
          * * perm_name		string 		A particular permission identifier.
          * * control_id    integer 	A database identifier.
          * * product       string      What product to search for permissions
          *******************************************************************************
          * Checks role permissions to see if a particular function of the UX should be 
          * accessible by the current user.
          */
        checkPerms: function (perm_name, project_id, product = "studio") {
            var result = false;
            if (this.currentUser.is_admin === true) {
                result = true;
            }
            else {
                if(product == "warehouse") {
                    for(var i=0; i < this.warehouse_permissions.length; i++) {
                        if (this.warehouse_permissions[i].product_child_id == project_id) {
                            for (var j=0; j < this.warehouse_permissions[i].name.length; j++) {
                                if (this.warehouse_permissions[i].name[j] == perm_name) {
                                    result = true;
                                }
                            }
                        }
                    }
                }
                else {
                    for(var i=0; i < this.permissions.length; i++) {
                        if (this.permissions[i].product_child_id == project_id) {
                            for (var j=0; j < this.permissions[i].name.length; j++) {
                                if (this.permissions[i].name[j] == perm_name) {
                                    result = true;
                                }
                            }
                        }
                    }
                }
            }
            return result;
        },
        movePage(url) {
            window.location.href = baseUrl + url
        },
        /** Vue.js Method
          *******************************************************************************
          * checkUserPerms
          * Params:
          * * perm_name		string 		A particular permission identifier.
          *******************************************************************************
          * Checks user permissions to see if a particular function of the UX should be 
          * accessible by the current user.
          */
        checkUserPerms: function (perm_name) {
            var result = false;
            if (this.currentUser.is_admin === true) {
                result = true;
            }
            else {
                for(var i=0; i < this.user_permissions.name.length; i++) {
                    if (this.user_permissions.name[i] == perm_name) {
                        result = true;
                        break;
                    }
                }
            }
            return result;
        },
        /** Vue.js Method
          *******************************************************************************
          * loading
          * Params:
          * * status			boolean		Whether to start or end loading. 		
          *******************************************************************************
          * Displays an overlay and an animated progress bar to indicate that the UX is
          * currently unavailable.
          */
        loading: function (status) {
            if (status === true) {
                $(".loader").show();
            }
            else {
                $(".loader").hide();
            }
        },
        /** Vue.js Method
        *******************************************************************************
        * getCurrentUser
        *******************************************************************************
        * Retrieves information about the current user. Data is stored in local storage 
        * until 15 minutes (in milliseconds) have elapsed, at which point the data is 
        * fetched again.
        */
        getCurrentUser: function () {          
            fetch(baseUrl + "/internal-api/v1/me")
                .then(response => response.json())
                .then(json => {
                    if (!app.checkForError(json)) {
                        return;
                    }
                    this.currentUser = json.data;
                    this.getProjects();
                    this.getDatabases();
                    this.getTags();
                });         
        },
        /** Vue.js Method
        *******************************************************************************
        * getBreadcrumbs
        *******************************************************************************
        * Retrieves breadcrumb details for the current UX. Breadcrumbs are initially set 
        * from local storage and subsequently fetched from server.
        */
        getBreadcrumbs: function () {
            if (localStorage.getItem("breadcrumbs" != undefined)) {
                this.toolbar.breadcrumbs = JSON.parse(localStorage.getItem("breadcrumbs"));
            }

            fetch(baseUrl + "/internal-api/v1/crumbs")
                .then(response => response.json())
                .then(json => {
                    if (!app.checkForError(json)) {
                        return;
                    }
                    this.toolbar.breadcrumbs = json.data;
                    localStorage.setItem("breadcrumbs", JSON.stringify(json.data));
                });
        },
        /** Vue.js Method
          *******************************************************************************
          * getPermissions
          *******************************************************************************
          * Retrieves permissions for the current user
          */
        getAllPermissions: function() {
            fetch(baseUrl + '/internal-api/v1/me/permissions?product=studio')
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    if (!app.checkForError(json)) {
                        return;
                    }
                    this.permissions = json.data;
                });

            fetch(baseUrl + '/internal-api/v1/me/permissions?product=datalake')
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    if (!app.checkForError(json)) {
                        return;
                    }
                    this.warehouse_permissions = json.data;
                });
        },
        /** Vue.js Method
         *******************************************************************************
         * getAllUserPermissions
         *******************************************************************************
         * Retrieves user permissions for the current user
         */
        getAllUserPermissions: function() {
            fetch(baseUrl + '/internal-api/v1/me/permissions')
                .then(response => response.json())
                .then(json => {
                    if (!app.checkForError(json)) {
                        return;
                    }
                    this.user_permissions = json.data;
                });
        },
         /** Vue.js Method
          *******************************************************************************
          * getProjects
          *******************************************************************************
          * Retrieves a listing of all projects available to the current user.
          */
        getProjects: function () {
            if (localStorage.getItem("projects-" + this.currentUser["name"] != undefined)) {
                this.projects = JSON.parse(localStorage.getItem("projects-" + this.currentUser["name"]));
                this.unfiltered_projects = JSON.parse(localStorage.getItem("projects-" + this.currentUser["name"]));
            }
            
            fetch(baseUrl + "/internal-api/v1/studio/projects")
                .then(response => response.json())
                .then(json => {
                    if (!app.checkForError(json)) {
                        return;
                    }
                    $(".tooltip-pretty-but-truncated").tooltipster('destroy');
                    this.projects = this.prepareProjects(json.data);
                    this.unfiltered_projects = this.prepareProjects(json.data);
                    localStorage.setItem("projects-" + this.currentUser["name"], JSON.stringify(json));
                    this.loaded = true;
                    this.loading(false);
                })
                .then(() => {
                    $(".tooltip-pretty").tooltipster({
                        'restoration': 'previous'
                    });
                    $(".tooltip-pretty-but-truncated").tooltipster({
                        theme: 'tooltipster-truncated',
                        restoration: 'previous'
                    });
                });
        },
        /** Vue.js Method
          *******************************************************************************
          * getDatabases
          *******************************************************************************
          * Retrieves a listing of all databases available to the current user.
          */
         getDatabases: function () {
            if (localStorage.getItem("databases-" + this.currentUser["name"] != undefined)) {
                this.databases = JSON.parse(localStorage.getItem("databases-" + this.currentUser["name"]));
            }

            fetch(baseUrl + "/internal-api/v1/data-lakes")
                .then(response => response.json())
                .then(json => {
                    if (!app.checkForError(json)) {
                        return;
                    }
                    this.databases = json.data;
                    localStorage.setItem("databases-" + this.currentUser["name"], JSON.stringify(json));
                });
        },
        /** Vue.js Method
         *******************************************************************************
         * getTags
         *******************************************************************************
         * Retrieves tags for the current user's databases
         */
         getTags: function () {
            fetch(baseUrl + "/internal-api/v1/tags")
                .then(response => response.json())
                .then(json => {
                    this.tags = json.data;
                });
        },
        tag_project_total(id)
        {
            let filtered_project = this.$root.unfiltered_projects.filter(function(project){
                return project.tags.filter(function(tag) {
                    return tag.id == id;
                    }).length > 0;
            });

            return filtered_project.length;
        },
        /** Vue.js Method
        *******************************************************************************
        * getProjectsFilter
        * Params:
        * * tag			string		A tag name.
        *******************************************************************************
        * Retrieves a listing of all projects available to the current user for a
        * given tag.
        */
        getProjectsFilter: function (tag) {
            this.loading(true);
            this.tag = tag;
            if (this.tag == "") {
                this.getProjects();
            }
            else {
                fetch(baseUrl + "/internal-api/v1/studio/projects?tag=" + this.tag)
                    .then(response => response.json())
                    .then(json => {
                        if (!app.checkForError(json)) {
                            return;
                        }
                        this.projects = this.prepareProjects(json.data);
                        this.loading(false);
                    });
            }
        },
        checkForError: function (json) {
            if (json.status == "error") {
                this.loading(false);
                notify.send(json.message, "danger");
                return false;
            }
            return true;
        },
        getActiveProjectMembers(members, limit = null) {
            if (typeof(members) !== 'object') {
                return [];
            }

            if (limit != null) {
                members = members.slice(0, limit);
            }

            return members;
        },
        prepareProjects(projects) {
            return projects.map(project => {
                project.members = project.members.filter(member => {
                    return member.name != null; // Remove invited users from the display
                });
                return project;
            });
        },
        getServers() {
            this.loading(true);
            fetch(baseUrl + "/internal-api/v1/servers")
                .then(response => response.json())
                .then(json => {
                    
                    this.servers = json.data;
                });
        }
    },
    mounted: function () {
        this.getCurrentUser();
        this.getServers();
        this.getBreadcrumbs();
        this.getAllPermissions();
        this.getAllUserPermissions();
        notyfHelper.showUrlMessage();
    }
});