<script type="text/x-template" id="switch-view-template">
    <!-- Search Modal -->
    <div class="dmiux_popup" id="modal-switch_view" role="dialog" tabindex="-1">
        <div class="dmiux_popup__window dmiux_popup__window_md" role="document">
            <div class="dmiux_popup__head">
                <h4 class="dmiux_popup__title">Switch to {{switch_view_type}}</h4>
                <button id="x-button" type="button" class="dmiux_popup__close" @click="modalCloseSwitchView($event)"></button>
            </div>
            <div class="dmiux_popup__cont hide-scroll">
                <div class="dmiux_input">
                    <h6  v-if="view_detail">Name: {{view_detail.view_name}}</h6>
                </div>
                <div v-if="switch_view_type == 'materialized'" class="dmiux_grid-row">
                    <div class="dmiux_grid-col_12">
                        <div class="mt-3 mx-2 alert alert-info">
                            All times are in UTC. Current UTC Date/Time is: {{ datetime }}
                        </div>
                    </div>
                </div>
                <template v-if="switch_view_type == 'materialized'">
                    <div class="dmiux_grid-row">
                        <div class="dmiux_grid-col dmiux_grid-col_12">
                            <label class="dmiux_popup__label" for="switch-view-frequency">Frequency</label>
                            <div class="dmiux_select">
                                <select class="dmiux_select__select" id="switch-view-frequency" v-model="view_frequency">
                                    <option value="">No Schedule</option>
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
                        <div v-if="view_frequency == 'annually'" class="dmiux_grid-col dmiux_grid-col_4">
                            <label class="dmiux_popup__label" for="switch-view-month">Month</label>
                            <div class="dmiux_select">
                                <select class="dmiux_select__select" v-model="view_schedule.month" id="switch-view-month">
                                    <option v-for="(label, value) in months" :value="value">{{ label }}</option>
                                </select>
                                <div class="dmiux_select__arrow"></div>
                            </div>
                        </div>
                        <div v-if="view_frequency == 'monthly' || view_frequency == 'annually'" class="dmiux_grid-col" :class="view_frequency == 'annually' ? 'dmiux_grid-col_4' : 'dmiux_grid-col_6'">
                            <label class="dmiux_popup__label" for="switch-view-day">Day of Month</label>
                            <div class="dmiux_select">
                                <select class="dmiux_select__select" v-model="view_schedule.month_day" id="switch-view-day">
                                    <option v-for="day in month_days" :value="day">{{ day }}</option>
                                </select>
                                <div class="dmiux_select__arrow"></div>
                            </div>
                        </div>
                        <div v-if="view_frequency == 'weekly'" class="dmiux_grid-col dmiux_grid-col_6">
                            <label class="dmiux_popup__label" for="switch-view-week">Day of Week</label>
                            <div class="dmiux_select">
                                <select class="dmiux_select__select" v-model="view_schedule.week_day" id="switch-view-week">
                                    <option v-for="(label, value) in week_days" :value="value">{{ label }}</option>
                                </select>
                                <div class="dmiux_select__arrow"></div>
                            </div>
                        </div>
                        <div v-if="view_frequency != ''" class="dmiux_grid-col dmiux_grid-col_4">
                            <label class="dmiux_popup__label" for="switch-view-hour">Hour</label>
                            <div class="dmiux_select">
                                <select class="dmiux_select__select" v-model="view_schedule.hour" id="switch-view-hour">
                                    <option v-for="(label, value) in hours" :value="value">{{ label }}</option>
                                </select>
                                <div class="dmiux_select__arrow"></div>
                            </div>
                        </div>
                    </div>
                </template>
                <div class="dmiux_grid-row">
                    <div class="dmiux_grid-col dmiux_grid-col_12">
                        <label class="dmiux_popup__label" for="input-view_message">Describe what was changed</label>
                        <textarea v-model="view_message" type="textarea" class="dmiux_input__input min-height-vh-15" id="input-view_message"></textarea>
                    </div>
                </div>
            </div>
            <div class="dmiux_popup__foot">
                <div class="dmiux_grid-row">
                    <div class="dmiux_grid-col"></div>
                    <div class="dmiux_grid-col dmiux_grid-col_auto">
                    <button id="cancel-button-switch-view" class="dmiux_button dmiux_button_secondary dmiux_popup__cancel" @click="modalCloseSwitchView($event)" type="button">Cancel</button>
                    </div>
                    <div class="dmiux_grid-col dmiux_grid-col_auto">
                        <button class="dmiux_button dmiux_button_primary" type="button" @click="switchView()">Switch</button>
                    </div>
                </div>	
            </div>
        </div>
    </div>
</script>