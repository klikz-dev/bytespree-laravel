<script type="text/x-template" id="publisher-scheduling">
    <!-- Scheduling -->
    <div>
        <template v-if="can_change_publish_type">
            <div class="dmiux_grid-row">
            <div class="dmiux_grid-col_2"></div>
            <div class="dmiux_grid-col_4">
                <div class="dmiux_radio">
                    <input v-model="current_publish_type" value="one_time" type="radio" :name="element_prefix + '-one_time'" class="dmiux_radio__input">
                    <div class="dmiux_radio__check"></div>
                    <div class="dmiux_radio__label">One Time</div>
                </div>
            </div>
            <div class="dmiux_grid-col_4">
                <div class="dmiux_radio">
                    <input v-model="current_publish_type" value="scheduled" type="radio" :name="element_prefix + '-scheduled'" class="dmiux_radio__input">
                    <div class="dmiux_radio__check"></div>
                    <div class="dmiux_radio__label">Scheduled</div>
                </div>
            </div>
            <div class="dmiux_grid-col_2"></div>
            </div>
        </template>
        <template v-if="publish_type == 'scheduled'">
            <hr />
            <div class="dmiux_grid-row">
                <div class="dmiux_grid-col dmiux_grid-col_12">
                    <label class="dmiux_popup__label mt-0" :for="element_prefix + '-publish-frequency'">Frequency</label>
                    <div class="dmiux_select">
                        <select :id="element_prefix + '-publish-frequency'" class="dmiux_select__select" v-model="current_schedule.frequency">
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
                <div v-if="current_schedule.frequency == 'annually'" class="dmiux_grid-col dmiux_grid-col_4">
                    <label class="dmiux_popup__label" :for="element_prefix + '-publish-schedule_month'">Month</label>
                    <div class="dmiux_select">
                        <select :id="element_prefix + '-publish-schedule_month'" class="dmiux_select__select" v-model="current_schedule.month">
                            <option v-for="(label, value) in months" :value="value">{{ label }}</option>
                        </select>
                        <div class="dmiux_select__arrow"></div>
                    </div>
                </div>
                <div v-if="current_schedule.frequency == 'monthly' || current_schedule.frequency == 'annually'" class="dmiux_grid-col" :class="current_schedule.frequency == 'annually' ? 'dmiux_grid-col_4' : 'dmiux_grid-col_6'">
                    <label class="dmiux_popup__label" :for="element_prefix + '-publish-schedule_month_day'">Day of Month</label>
                    <div class="dmiux_select">
                        <select :id="element_prefix + '-publish-schedule_month_day'" class="dmiux_select__select" v-model="current_schedule.month_day">
                            <option v-for="day in month_days" :value="day">{{ day }}</option>
                        </select>
                        <div class="dmiux_select__arrow"></div>
                    </div>
                </div>
                <div v-if="current_schedule.frequency == 'weekly'" class="dmiux_grid-col dmiux_grid-col_6">
                    <label class="dmiux_popup__label" :for="element_prefix + '-publish-schedule_week_day'">Day of Week</label>
                    <div class="dmiux_select">
                        <select :id="element_prefix + '-publish-schedule_week_day'" class="dmiux_select__select" v-model="current_schedule.week_day">
                            <option v-for="(label, value) in week_days" :value="value">{{ label }}</option>
                        </select>
                        <div class="dmiux_select__arrow"></div>
                    </div>
                </div>
                <div v-if="current_schedule.frequency != ''" class="dmiux_grid-col dmiux_grid-col_4">
                    <label class="dmiux_popup__label" :for="element_prefix + '-publish-schedule_hour'">Hour</label>
                    <div class="dmiux_select">
                        <select :id="element_prefix + '-publish-schedule_hour'" class="dmiux_select__select" v-model="current_schedule.hour">
                            <option v-for="(label, value) in hours" :value="value">{{ label }}</option>
                        </select>
                        <div class="dmiux_select__arrow"></div>
                    </div>
                </div>
            </div>
        </template>
    </div>
    <!-- End Scheduling -->
</script>
<script type="text/javascript">
publisher_scheduling = {
    props: ['element_prefix', 'schedule', 'publish_type', 'can_change_publish_type'],
    watch: {
        current_schedule: {
            handler() {
                this.$emit('update-schedule', this.current_schedule);
            },
            deep: true
        },
        current_publish_type() {
            this.$emit('update-publish-type', this.current_publish_type);
        },
        schedule: {
            handler() {
                this.setSchedule();
            },
            deep: true
        },
        publish_type() {
            this.setSchedule();
        }
    },
    data() {
        return {
            current_publish_type: 'one_time',
            current_schedule: '',
            default_schedule: {
                frequency: 'daily',
                hour: 0,
                month: 1,
                month_day: 1,
                week_day: 0
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
            }
        };
    },
    methods: {
        setSchedule() {
            if (typeof this.schedule == 'undefined' || Object.keys(this.schedule).length == 0){
                this.current_schedule = this.default_schedule;
            } else {
                this.current_schedule = this.schedule;
            }

            this.current_publish_type = this.publish_type;
        }
    },
    template: '#publisher-scheduling',
    name: 'publisher-scheduling'
};
</script>