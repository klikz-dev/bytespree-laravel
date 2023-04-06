<script type="text/x-template" id="view-modal-template">
	<div class="dmiux_popup" id="modal-add_view">
		<div class="dmiux_popup__window dmiux_popup__window_lg" role="document">
            <div class="dmiux_popup__head">
                <h4 class="dmiux_popup__title"><template v-if="editing == true">Update</template><template v-else>Create</template> View</h4>
                <button type="button" id="x-button" class="dmiux_popup__close" @click="modalClose($event)"></button>
            </div>
            <form id="form-add_view" autocomplete="off" onSubmit="event.preventDefault()">
                <div class="dmiux_popup__cont">
                    <div class="dmiux_grid-row">
                        <div class="dmiux_grid-col dmiux_grid-col_6">
                            <label class="dmiux_popup__label" for="name">Name <span class="text-danger">*</span></label>
                            <div class="dmiux_input mt-2">
                                <input id="name"
                                    type="text"
                                    @input="cleanupName()" 
                                    v-model="name"
                                    class="dmiux_input__input" />
                            </div>
                        </div>
                        <div class="dmiux_grid-col dmiux_grid-col_6">
                            <label class="dmiux_popup__label" for="type">Type <span class="text-danger">*</span></label>
                            <div class="dmiux_select mt-2">
                                <select id="type" class="dmiux_select__select" v-model="type" @change="checkViewTypeSwitching($event)">
                                    <option value="normal">Normal</option>
                                    <option value="materialized">Materialized</option>
                                </select>
                                <div class="dmiux_select__arrow"></div>
                            </div>
                        </div>
                    </div>

                    <template v-if="type == 'materialized'">

                        <div class="dmiux_grid-row">
                            <div class="dmiux_grid-col dmiux_grid-col_12">
                                <label class="dmiux_popup__label" for="view-build_on">Build On  <span class="text-danger">*</span></label>
                                <div class="dmiux_select">
                                    <select class="dmiux_select__select" v-model="build_on" id="view-build_on">
                                        <option value="schedule">Schedule</option>
                                        <option value="chained">Other Build Completion</option>
                                    </select>
                                    <div class="dmiux_select__arrow"></div>
                                </div>
                            </div>
                        </div>

                        <template v-if="build_on == 'schedule'">

                            <div class="mt-3 alert alert-info">
                                All times are in UTC. Current UTC Date/Time is: {{ datetime }}
                            </div>

                            <div class="dmiux_grid-row">
                                <div class="dmiux_grid-col dmiux_grid-col_12">
                                    <label class="dmiux_popup__label" for="view-select_frequency">Frequency  <span class="text-danger">*</span></label>
                                    <div class="dmiux_select">
                                        <select class="dmiux_select__select" v-model="frequency" id="view-select_frequency">
                                            <option value="daily">Daily</option>
                                            <option value="weekly">Weekly</option>
                                            <option value="monthly">Monthly</option>
                                            <option value="annually">Annually</option>
                                        </select>
                                        <div class="dmiux_select__arrow"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="dmiux_grid-row">
                                <div v-if="frequency == 'annually'" class="dmiux_grid-col dmiux_grid-col_4">
                                    <label class="dmiux_popup__label" for="view-select_month">Month  <span class="text-danger">*</span></label>
                                    <div class="dmiux_select">
                                        <select class="dmiux_select__select" v-model="schedule.month" id="view-select_month">
                                            <option v-for="(label, value) in months" :value="value">{{ label }}</option>
                                        </select>
                                        <div class="dmiux_select__arrow"></div>
                                    </div>
                                </div>
                                <div v-if="frequency == 'monthly' || frequency == 'annually'" class="dmiux_grid-col" :class="frequency == 'annually' ? 'dmiux_grid-col_4' : 'dmiux_grid-col_6'">
                                    <label class="dmiux_popup__label" for="view-select_day_of_month">Day of Month  <span class="text-danger">*</span></label>
                                    <div class="dmiux_select">
                                        <select class="dmiux_select__select" id="view-select_day_of_month" v-model="schedule.month_day">
                                            <option v-for="day in month_days" :value="day">{{ day }}</option>
                                        </select>
                                        <div class="dmiux_select__arrow"></div>
                                    </div>
                                </div>
                                <div v-if="frequency == 'weekly'" class="dmiux_grid-col dmiux_grid-col_6">
                                    <label class="dmiux_popup__label" for="view-select_day_of_week">Day of Week  <span class="text-danger">*</span></label>
                                    <div class="dmiux_select">
                                        <select class="dmiux_select__select" v-model="schedule.week_day" id="view-select_day_of_week">
                                            <option v-for="(label, value) in week_days" :value="value">{{ label }}</option>
                                        </select>
                                        <div class="dmiux_select__arrow"></div>
                                    </div>
                                </div>
                                <div :class="[frequency == 'annually' ? 'dmiux_grid-col_4' : 'dmiux_grid-col_6', frequency == 'daily' ? 'dmiux_grid-col_12' : '']" class="dmiux_grid-col">
                                    <label class="dmiux_popup__label" for="view-select_hour">Hour  <span class="text-danger">*</span></label>
                                    <div class="dmiux_select">
                                        <select class="dmiux_select__select" v-model="schedule.hour" id="view-select_hour">
                                            <option v-for="(label, value) in hours" :value="value">{{ label }}</option>
                                        </select>
                                        <div class="dmiux_select__arrow"></div>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <template v-if="build_on == 'chained'">
                            <div class="dmiux_grid-row">
                                <div class="dmiux_grid-col dmiux_grid-col_12">
                                    <label class="dmiux_popup__label" for="view-upstream_build_id">Build after successful build of  <span class="text-danger">*</span></label>
                                    <div class="dmiux_select">
                                        <select class="dmiux_select__select" v-model="upstream_build_id" id="view-upstream_build_id">
                                            <option value="">Choose a view that triggers this view's build</option>
                                            <option v-for="build in material_views_filtered" :value="build.id">{{ build.view_name }}</option>
                                        </select>
                                        <div class="dmiux_select__arrow"></div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </template>

                    <div class="mt-3">
                        <label class="dmiux_popup__label">Definition query  <span class="text-danger">*</span></label>
                        <prism-editor class="dmiux_input__input sql-editor-sm" line-numbers placeholder="SELECT * FROM..." :highlight="highlighter" v-model="sql"></prism-editor>
                    </div>
                </div>
                <div class="dmiux_popup__foot">
                    <div class="dmiux_grid-row">
                        <div class="dmiux_grid-col"></div>
                        <div class="dmiux_grid-col dmiux_grid-col_auto pr-0">
                            <button id="cancel-button-add-view" class="dmiux_button dmiux_button_secondary dmiux_popup__close_popup dmiux_popup__cancel" @click="modalClose($event)" type="button">Cancel</button>
                        </div>
                        <div class="dmiux_grid-col dmiux_grid-col_auto">
                            <button class="dmiux_button" type="button" @click="createOrReplaceView()"><template v-if="editing == true">Update</template><template v-else>Create</template></button>
                        </div>
                    </div>
                </div>
            </form>
		</div>
	</div>
</script>

<script>
    var addView = {
        template: '#view-modal-template',
        props: [ 'control_id' ],
        data() {
            return {
                editing: false,
                recreate: false,
                name: "",
                orig_name: "",
                type: "normal",
                orig_type: "",
                frequency: "daily",
                history_guid: "",
                build_on: "schedule",
                material_views: [],
                available_builds: [],
                upstream_build_id: "",
                downstream_views: [],
                dependent_views: [],
                sql: "",
                schedule: {
                    "month_day": 1,
                    "week_day": 0,
                    "hour": 0,
                    "month": 1
                },
                month_days: [ "1", "2", "3", "4", "5", "6", "7", "8", "9", "10", "11", "12", "13", "14", "15", "16", "17", "18", "19", "20", "21", "22", "23", "24", "25", "26", "27", "28" ],
                week_days: {
                    "0": "Sunday",
                    "1": "Monday",
                    "2": "Tuesday",
                    "3": "Wednesday",
                    "4": "Thursday",
                    "5": "Friday",
                    "6": "Saturday"
                },
                hours: {
                    "0": "12 AM",
                    "1": "1 AM",
                    "2": "2 AM",
                    "3": "3 AM",
                    "4": "4 AM",
                    "5": "5 AM",
                    "6": "6 AM",
                    "7": "7 AM",
                    "8": "8 AM",
                    "9": "9 AM",
                    "10": "10 AM",
                    "11": "11 AM",
                    "12": "12 PM",
                    "13": "1 PM",
                    "14": "2 PM",
                    "15": "3 PM",
                    "16": "4 PM",
                    "17": "5 PM",
                    "18": "6 PM",
                    "19": "7 PM",
                    "20": "8 PM",
                    "21": "9 PM",
                    "22": "10 PM",
                    "23": "11 PM"
                },
                months: {
                    "1": "January",
                    "2": "February",
                    "3": "March",
                    "4": "April",
                    "5": "May",
                    "6": "June",
                    "7": "July",
                    "8": "August",
                    "9": "September",
                    "10": "October",
                    "11": "November",
                    "12": "December"
                },
                datetime: ""
            }
        },
        computed: {
            material_views_filtered: function(){
                return this.material_views.filter(view => view.view_name !== this.orig_name && view.view_schema == 'public');
            }
        },
        created() {
            setInterval(() => {
                this.runClock();
            }, 1000)
        },
        methods: {
            highlighter(code) {
                // js highlight example
                return Prism.highlight(code, Prism.languages.sql, "sql");
            },
            createOrReplaceView() {
                var view_name = this.name;
                
                if(!this.name) {
                    notify.danger("A view name is required.");
                    return;
                } else if(!this.sql) {
                    notify.danger("A view definition query is required.");
                    return;
                }

                let options = FetchHelper.buildJsonRequest({
                    "sql": this.sql,
                    "name": view_name,
                    "type": this.type,
                    "frequency": this.frequency,
                    "schedule": this.schedule,
                    "orig_name": this.orig_name,
                    "orig_type": this.orig_type,
                    "history_guid": this.history_guid,
                    "build_on": this.build_on,
                    "upstream_build_id": this.upstream_build_id,
                    "recreate": this.recreate 
                }, this.editing ? 'put' : 'post');

                this.$root.loading(true);
                fetch(`/internal-api/v1/data-lakes/${control_id}/views`, options)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.$root.loading(false);
                        this.$root.getViews(function() {
                            app.loading(false);
                            closeModal('#modal-add_view');

                            notify.success(json.message);

                            var schema = app.$refs.databaseManager.active_schema_view;

                            app.$refs.databaseManager.setActiveSchema(schema, 'view');

                            app.$refs.databaseManager.selectedView = {};
                            app.$nextTick(() => {
                                app.showView(schema, view_name);
                            });

                            this.sql = "";
                            this.name = "";
                        });
                    })
                    .catch((error) => {
                        this.$root.loading(false);
                        let word = this.editing ? 'updated' : 'created';
                        ResponseHelper.handleErrorMessage(error, "View could not be " + word + ".");
                    });
            },
            modalClose(event) {
                event.stopPropagation();
                if(this.$root.ready == true) {
                    return;
                } else if(event.key != undefined) {
                    if(event.key != 'Escape') // not escape
                        return;
                }
                else {
                    var clicked_element = event.target;
                    if (clicked_element.closest(".dmiux_popup__window")) {
                        // You clicked inside the modal
                        if (clicked_element.id != "x-button" && !(clicked_element.classList.contains("dmiux_popup__cancel")))
                            return;
                    }
                }

                this.recreate = false;

                // You clicked outside the modal
                $(document).off("mousedown", "#dmiux_body", app.$refs.manageView.modalClose);
                $(document).off("keydown", app.$refs.manageView.modalClose);
                closeModal('#modal-add_view');
            },
            cleanupName() {
                this.name = this.name.substring(0, 63);
                this.name = this.name.toLowerCase();
                this.name = this.name.trim();
                this.name = this.name.replace(" ", "");
                this.name = this.name.replace(/\W/g, '');
                var pattern = /^[a-z]/;
                if (pattern.test(this.name[0]) === false) {
                    this.name = this.name.slice(1);
                }
            },
            runClock() {
                this.datetime = this.getUTCFormattedDate(new Date());
            },
            getUTCFormattedDate(date) {
                var year = date.getUTCFullYear();
                var month = (1 + date.getUTCMonth()).toString().padStart(2, '0');
                var day = date.getUTCDate().toString().padStart(2, '0');
                var hour = date.getUTCHours().toString().padStart(2, '0');  
                var minute = date.getUTCMinutes().toString().padStart(2, '0');
                var second = date.getUTCSeconds().toString().padStart(2, '0');
                return month + '/' + day + '/' + year + '  ' + hour + ':' + minute + ':' + second; 
            },
            setMaterialViews(views) {
                this.material_views = views.filter(view => view['view_type'] == 'materialized');
            },
            checkViewTypeSwitching (event) {
                if(this.orig_type != 'materialized'){
                    return;
                }
                if (this.type != 'materialized'  && this.downstream_views.length > 0) {
                    var message = "Are you sure you want to change this materialized view? This will affect the builds of the following views:\n\n" + this.downstream_views.join(', ');

                    if (! confirm(message)) {
                        this.type = 'materialized';
                    }
                }
            }
        },
        mounted() {
            this.$parent.$on('getViewsFinished', this.setMaterialViews);
        }
    }
</script>