var toolbar = Vue.component('toolbar', {
    template: '#component-toolbar',
    props: [ 'breadcrumbs', 'buttons', 'project_id', 'record_counts' ]
});

var map_table = Vue.component('map-table', {
    template: '#map-template',
    props: [ 'mappings', 'selected_table', 'date', 'project_id', 'column_headers' ],
    methods: {
        downloadMap: function () {
            this.$root.setCookie("downloadStarted", 0, 100);
            this.$root.checkDownloadCookie();
            window.location.href = "/studio/projects/" + this.$root.project_id + "/tables/" + this.$root.schema + "/" + this.$root.selected_table + "/map/download";
        },
        updateProgrammed: function(map_index) {
            var map_id = this.mappings[map_index].id;
            var prog_value = this.mappings[map_index].is_programmed;

            let options = FetchHelper.buildJsonRequest({
                "is_programmed": prog_value
            }, 'PUT');

            fetch(`/internal-api/v1/studio/projects/${this.$root.project_id}/tables/${this.$root.schema}/${this.$root.selected_table}/mappings/${map_id}/programming`, options)
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    this.$root.getMappings();
                })
                .catch((error) => {
                    ResponseHelper.handleErrorMessage(error, "An error occurred while updating mapping");
                });
        },
        goBack: function(link) {
            window.location.href = link;
        },
        hideColumn: function (event) {
            dtTable.column($(event.target).val()).visible($(event.target).prop('checked'));
        }
    }
});

var app = new Vue({
    el: '#app',
    data: {
        toolbar: {
            "breadcrumbs": [],
            "buttons": []
        },
        mappings: [],
        column_list: [],
        column_headers: [
            {column_name: "Is Programmed"},
            {column_name: "Position"}, 
            {column_name: "Source Table"}, 
            {column_name: "Source Column"}, 
            {column_name: "Destination Table"}, 
            {column_name: "Destination Column"},
            {column_name: "Module Name"},
            {column_name: "Module Values"},
            {column_name: "Date Mapped"},
            {column_name: "Conditions"},
            {column_name: "Notes"},
            {column_name: "Comments"}
        ],
        project_id: project_id,
        selected_table: table,
        schema: schema,
        date: new Date().getFullYear().toString(),
        dtTable: null
    },
    components: {
        'toolbar': toolbar
    },
    methods: {
        loading: function(status) {
            if(status === true) {
                $(".loader").show();
            } else {
                $(".loader").hide();
            }
        },
        getBreadcrumbs: function() {
            this.loading(true);
            fetch("/internal-api/v1/crumbs")
                .then(response => response.json())
                .then(json => {
                    this.toolbar.breadcrumbs = json.data;
                });
        },
        getMappings: function() {
            fetch(`/internal-api/v1/studio/projects/${this.project_id}/tables/${this.schema}/${this.selected_table}/full-mappings`)
                .then(response => response.json())
                .then(json => {
                    if(json.length == 0){   
                        var msg = document.createElement("DIV");
                        msg.innerHTML = "<b>Oops!</b> No fields are mapped yet for " + this.selected_table;
                        document.getElementById("hidedisplay").appendChild(msg); 
                        msg.className = "alert alert-info";
                    } else {
                        this.mappings = json.data;
                        for (var i=0; i< this.mappings.length; i++) {
                            this.mappings[i].comment_text = this.mappings[i].comment_text.replace("<p>", "").replace("</p>", "");
                            var formated_date = this.mappings[i].created_at.replace(/-/g, '/');
                            this.mappings[i].created_at_formatted = formatLocaleDateString(formated_date);
                        }
                        $("#dmiux_data-table").DataTable().destroy();
                    }
                })
                .then(() => {
                    this.$nextTick(function() {
                        dtTable = $("#dmiux_data-table").DataTable({
                            "pagingType": "simple_numbers",
                            "pageLength": 50,
                            "searching": false
                        });
                        dtTable
                            .order([2, 'asc'])
                            .draw();
                    });
                });
        },
        getCookie: function(name) {
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
        setCookie: function(name, value, expiracy) {
            var exdate = new Date();
            exdate.setTime(exdate.getTime() + expiracy * 1000);
            var c_value = escape(value) + ((expiracy == null) ? "" : "; expires=" + exdate.toUTCString());
            document.cookie = name + "=" + c_value + '; path=/';
        },
        checkDownloadCookie: function () {
            if (this.getCookie("downloadStarted") == 1) {
                this.setCookie("downloadStarted", "false", 100); 
                this.loading(false);
            } else {
                this.loading(true);
                setTimeout(this.checkDownloadCookie, 1000);
            }
        }
    },
    mounted: function() {
        this.getBreadcrumbs();
        this.getMappings();
        this.loading(false);
    },
    created: function () {
        this.loading(true);
    }
});