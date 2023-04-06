var baseUrl = "";

var toolbar = Vue.component('toolbar', {
    template: '#component-toolbar',
    props: [ 'breadcrumbs', 'buttons', 'control_id', 'record_counts' ],
    methods: {
        exportToExcel() {
            if (app.$refs.compare.database_left == "" || app.$refs.compare.database_right == "" || app.$refs.compare.table_left == "" || app.$refs.compare.table_right == "") {
                notify.danger("All fields must be populated!");
                return;
            }

            app.setCookie("downloadStarted", 0, 100);
            app.checkDownloadCookie();
            let jsonString = JSON.stringify({
                "database_left": app.$refs.compare.database_left,
                "table_name_left": app.$refs.compare.table_left.table_name,
                "table_schema_left": app.$refs.compare.table_left.table_schema,
                "database_right": app.$refs.compare.database_right,
                "table_name_right": app.$refs.compare.table_right.table_name,
                "table_schema_right": app.$refs.compare.table_right.table_schema,
                "show_all_col_definitions": app.$refs.compare.show_all_col_definitions,
                "ignore_case_differences": app.$refs.compare.ignore_case_differences,
                "ignore_position_differences": app.$refs.compare.ignore_position_differences
            });
            let base64string = btoa(jsonString);
            window.location.href = "/data-lake/compare/csv?body=" + base64string;
        }
    }
});

var compare = Vue.component('compare', {
    name: 'CompareComponent',
    template: '#compare-template',
    props: [ 'databases' ],
    data() {
        return { 
            tables_left: [],
            tables_right: [],
            table_left: "",
            table_left_selected: "",
            table_right: "",
            table_right_selected: "",
            database_left: "",
            database_right: "",
            database_left_name: "",
            database_right_name: "",
            show_table: false,
            all_columns: [],
            show_all_col_definitions : false,
            ignore_case_differences: false,
            ignore_position_differences: false,
            dbHelper: DatabaseHelper
        }
    },
    watch: {
        ignore_case_differences() {
            this.navigate();
        },
        ignore_position_differences() {
            this.navigate();
        },
        show_all_col_definitions() {
            this.$nextTick(() => {
                this.destroyTooltips();
                if (!this.show_all_col_definitions) {
                    this.createTooltips();
                }
            });
        },
        show_table() {
            if (! this.show_table) {
                $("#export-btn").addClass('hidden');
            }
        },
        database_left() {
            this.show_table = false;
            this.table_left_selected = "";
        },
        database_right() {
            this.show_table = false;
            this.table_right_selected = "";
        }
    },
    methods: {
        compare() {
            this.table_left = this.table_left_selected;
            this.table_right = this.table_right_selected;
            this.navigate();
        },
        navigate() {
            if (this.database_left == "" || this.database_right == "" || this.table_left == "" || this.table_right == "") {
                notify.danger("All fields must be populated!");
                this.show_table = false;
                return;
            }
            
            this.$root.loading(true);
            let options = FetchHelper.buildJsonRequest({
                "database_left": this.database_left,
                "table_name_left": this.table_left.table_name,
                "table_schema_left": this.table_left.table_schema,
                "database_right": this.database_right,
                "table_name_right": this.table_right.table_name,
                "table_schema_right": this.table_right.table_schema,
                "show_all_col_definitions": this.show_all_col_definitions,
                "ignore_case_differences": this.ignore_case_differences,
                "ignore_position_differences": this.ignore_position_differences
            });

            this.destroyTooltips();
            fetch(baseUrl + '/internal-api/v1/data-lakes/compare', options)
                .then(response => {
                    this.$root.loading(false);
                    return response;
                })
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    this.all_columns = json.data.all_columns
                    this.show_table = true
                    if (Object.keys(this.all_columns).length > 0) {
                        $("#export-btn").removeClass('hidden');
                    }
                    $("#compare-table").removeClass('hidden');
                })
                .then(() => {
                    this.createTooltips();
                })
                .catch((error) => {
                    if(error.json == null)
                        ResponseHelper.handleErrorMessage(error, "An error occurred while attempting to compare");
                    else
                        ResponseHelper.handleErrorMessage(error, error.json.message);
                });
        },
        atob(str) {
            return atob(str);
        },
        get_tables(el, side) {
            var database = el.target.value;

            if (database == undefined || database == '') {
                if (side == 'left') {
                    this.tables_left = [];
                    this.table_left = "";
                    $("#input-left_table").attr("disabled", true);
                } else {
                    this.tables_right = [];
                    this.table_right = "";
                    $("#input-right_table").attr("disabled", true);
                }
                return;
            }

            if (side === 'left') {
                this.database_left_name = this.databases.find(db => db.id == database).database;
            } else {
                this.database_right_name = this.databases.find(db => db.id == database).database;
            }

            fetch(baseUrl + `/internal-api/v1/data-lakes/compare/${database}/tables`)
                .then(response => response.json())
                .then(json => {
                    if(json) {
                        if(side == "left") {
                            this.tables_left = json.data;
                            this.table_left = "";
                            $("#input-left_table").attr("disabled", false);
                            $("#input-left_table").css("color", "#1D2125");
                        }
                        else {
                            this.tables_right = json.data;
                            this.table_right = "";
                            $("#input-right_table").attr("disabled", false);
                            $("#input-right_table").css("color", "#1D2125");
                        }
                    }
                });
        },
        enable_right_database() {
            if (this.table_left_selected == "") {
                this.table_left = '';
                this.show_table = false;
                return;
            }
            $("#input-right_database").attr("disabled", false);
            $("#input-right_database").css("color", "#1D2125");
        },
        destroyTooltips() {
            $(".tooltip-pretty").each((index, element) => {
                if ($(element).hasClass('tooltipstered')) {
                    $(element).tooltipster('destroy');
                }
            });
        },
        checkToEnableDisplay() {
            if (this.table_right_selected == '') {
                this.table_right = '';
                this.show_table = false;
                return;
            }

            if (this.database_left == "" || this.database_right == "" || this.table_left_selected == "" || this.table_right_selected == "") {
                this.show_table = false;
            }
        },
        createTooltips() {
            $(".tooltip-pretty").tooltipster({
                'restoration': 'previous'
            });
        },
    }
});

var app = new Vue({
    el: '#app',
    name: 'Compare',
    data: {
        toolbar: {
            "breadcrumbs": [],
            "buttons": []
        },
        databases: []
    },
    components: {
        'toolbar': toolbar
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
            this.loading(true);
            fetch(baseUrl + "/internal-api/v1/crumbs")
                .then(response => response.json())
                .then(json => {
                    this.toolbar.breadcrumbs = json.data;
                });
        },
        getDatabases() {
            this.loading(true);
            fetch(baseUrl + "/internal-api/v1/data-lakes")
                .then(response => response.json())
                .then(json => {
                    this.databases = json.data;
                    this.loading(false);
                });
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
        checkDownloadCookie () {
            if (this.getCookie("downloadStarted") == 1) {
                this.setCookie("downloadStarted", "false", 100);
                this.loading(false);
            } else {
                this.loading(true);
                setTimeout(this.checkDownloadCookie, 1000);
            }
        }
    },
    mounted() {
        this.getBreadcrumbs();
        this.getDatabases();
        this.loading(false);
    }
})

$("#input-left_table").attr("disabled", true);
$("#input-left_table").css("color", "#C3C3C3");
$("#input-right_database").attr("disabled", true);
$("#input-right_database").css("color", "#C3C3C3");
$("#input-right_table").attr("disabled", true);
$("#input-right_table").css("color", "#C3C3C3");