<script type="text/x-template" id="publish-view-template">
    <!-- Search Modal -->
    <div class="dmiux_popup" id="modal-publish_view" role="dialog" tabindex="-1">
        <div class="dmiux_popup__window dmiux_popup__window_md" role="document">
            <div class="dmiux_popup__head">
                <h4 class="dmiux_popup__title modal-title-overflow_text" v-if="$root.rename_view_status || view_mode == 'save'">
                    <template v-if="$root.rename_view_status">Rename</template> 
                    <template v-else>Update</template>
                    {{ $root.explorer.view.view_name }}
                </h4>
                <h4 class="dmiux_popup__title" v-else>Publish a View</h4>
                <button id="x-button" type="button" class="dmiux_popup__close" @click="modalClosePublishView($event)"></button>
            </div>
            <form id="form-publish_view" autocomplete="off" onSubmit="event.preventDefault()">
                <div class="dmiux_popup__cont hide-scroll">
                    <template v-if="$root.rename_view_status == false && view_mode != 'save'">
                        <div class="dmiux_grid-row">
                            <div class="dmiux_grid-col_2"></div>
                            <div class="dmiux_grid-col_4">
                                <div class="dmiux_radio">
                                    <input v-model="view_type" value="normal" type="radio" name="normal_view" class="dmiux_radio__input">
                                    <div class="dmiux_radio__check"></div>
                                    <div class="dmiux_radio__label">Normal</div>                        
                                </div>                
                            </div>
                            <div class="dmiux_grid-col_4">
                                <div class="dmiux_radio">
                                    <input v-model="view_type" value="materialized" type="radio" name="materialized_view" class="dmiux_radio__input">
                                    <div class="dmiux_radio__check"></div>
                                    <div class="dmiux_radio__label">Materialized</div>                        
                                </div>              
                            </div>
                            <div class="dmiux_grid-col_2"></div>
                        </div>
                    </template>
                    <template v-if="view_mode != 'save'">
                        <div class="dmiux_grid-col_2"></div>
                        <div class="dmiux_input">
                            <label class="dmiux_popup__label" for="input-view_name">Name</label>
                            <input v-if="$root.rename_view_status == false" @input="cleanupName()" type="text" class="dmiux_input__input" id="input-view_name" v-model="view_name" :disabled="view_mode == 'save'">
                            <input v-else @input="cleanupName()" type="text" class="dmiux_input__input" id="input-view_name" v-model="rename_value" :disabled="view_mode == 'save'">
                            <small>View names may only contain lowercase characters, underscores, and numbers and must begin with a letter.</small>
                        </div>
                        <div class="dmiux_grid-col_2"></div>
                        <div class="dmiux_input">
                            <label class="dmiux_popup__label" for="input-view_message">Describe the view</label>
                            <textarea v-model="view_message" type="textarea" class="dmiux_input__input min-height-vh-15" id="input-view_message"></textarea>
                        </div>
                    </template>
                    <template v-if="$root.rename_view_status == false">
                        <div v-if="view_type == 'materialized'">
                            <hr v-if="view_mode != 'save'" />
                            <div class="dmiux_grid-row">
                                <div class="dmiux_grid-col_12">
                                    <div class="mx-2 mb-1 alert alert-info">
                                        All times are in UTC. Current UTC Date/Time is: {{ datetime }}
                                    </div>
                                </div>
                            </div>
                            <div class="dmiux_grid-row">
                                <div class="dmiux_grid-col dmiux_grid-col_12">
                                    <label class="dmiux_popup__label" for="publish-view_select_frequency">Frequency</label>
                                    <div class="dmiux_select">
                                        <select class="dmiux_select__select" v-model="view_frequency" id="publish-view_select_frequency">
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
                                    <label class="dmiux_popup__label" for="publish-view_select_month">Month</label>
                                    <div class="dmiux_select">
                                        <select class="dmiux_select__select" v-model="view_schedule.month" id="publish-view_select_month">
                                            <option v-for="(label, value) in months" :value="value">{{ label }}</option>
                                        </select>
                                        <div class="dmiux_select__arrow"></div>
                                    </div>
                                </div>
                                <div v-if="view_frequency == 'monthly' || view_frequency == 'annually'" class="dmiux_grid-col" :class="view_frequency == 'annually' ? 'dmiux_grid-col_4' : 'dmiux_grid-col_6'">
                                    <label class="dmiux_popup__label" for="publish-view_select_day_of_month">Day of month</label>
                                    <div class="dmiux_select">
                                        <select class="dmiux_select__select" id="publish-view_select_day_of_month" v-model="view_schedule.month_day">
                                            <option v-for="day in month_days" :value="day">{{ day }}</option>
                                        </select>
                                        <div class="dmiux_select__arrow"></div>
                                    </div>
                                </div>
                                <div v-if="view_frequency == 'weekly'" class="dmiux_grid-col dmiux_grid-col_6">
                                    <label class="dmiux_popup__label" for="publish-view_select_day_of_week">Day of week</label>
                                    <div class="dmiux_select">
                                        <select class="dmiux_select__select" id="publish-view_select_day_of_week" v-model="view_schedule.week_day">
                                            <option v-for="(label, value) in week_days" :value="value">{{ label }}</option>
                                        </select>
                                        <div class="dmiux_select__arrow"></div>
                                    </div>
                                </div>
                                <div v-if="view_frequency != ''" class="dmiux_grid-col dmiux_grid-col_4">
                                    <label class="dmiux_popup__label" for="publish-view_select_hour">Hour</label>
                                    <div class="dmiux_select">
                                        <select class="dmiux_select__select" id="publish-view_select_hour" v-model="view_schedule.hour">
                                            <option v-for="(label, value) in hours" :value="value">{{ label }}</option>
                                        </select>
                                        <div class="dmiux_select__arrow"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                    <div v-if="view_mode == 'save'" class="dmiux_input">
                        <label class="dmiux_popup__label" for="input-view_message">Describe what was changed</label>
                        <textarea v-model="view_message" type="textarea" class="dmiux_input__input min-height-vh-15" id="input-view_message"></textarea>
                    </div>
                </div>
                <div class="dmiux_popup__foot">
                    <div class="dmiux_grid-row">
                        <div class="dmiux_grid-col"></div>
                        <div class="dmiux_grid-col dmiux_grid-col_auto">
                        <button id="cancel-button-publish-view" class="dmiux_button dmiux_button_secondary dmiux_popup__cancel" @click="modalClosePublishView($event)" type="button">Cancel</button>
                        </div>
                        <div v-if="$root.rename_view_status == false" class="dmiux_grid-col dmiux_grid-col_auto pl-0">
                            <button v-if="view_mode == 'save'" class="dmiux_button dmiux_button_primary" type="button" @click="$parent.checkForViewPublisher(createView)">
                                Update
                            </button>
                            <button v-else class="dmiux_button dmiux_button_primary" type="button" @click="createView()">
                                Create
                            </button>
                        </div>
                        <div v-else class="dmiux_grid-col dmiux_grid-col_auto pl-0">
                            <button class="dmiux_button dmiux_button_primary" type="button"  @click="$parent.checkForViewPublisher(updateName)">
                                Rename
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</script>