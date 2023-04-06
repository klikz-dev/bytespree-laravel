<script type="text/x-template" id="connector-settings-template">   
    <form id="form-connector_settings" autocomplete="off" onSubmit="event.preventDefault()">
        <div v-for="(setting,index) in settings" class="connector-settings" v-if="setting.is_private != true && setting.is_private != 't' && $parent.isSettingVisible(setting.visible_if, table)">
            <div class="dmiux_input">
                <template v-if="setting.data_type != 'boolean'">
                    <template v-if="setting.friendly_name && setting.friendly_name != ''">
                        <label :for="'cs_' + table + index">{{ setting.friendly_name }} <span v-if="$parent.isSettingRequired(setting, table)" class="text-danger">*</span></label>
                    </template>
                    <template v-else>
                        <label :for="'cs_' + table + index">{{ setting.name }} <span v-if="$parent.isSettingRequired(setting, table)" class="text-danger">*</span></label>
                    </template>
                </template>
                <span v-if="setting.is_secure && setting.id != 0">
                    <button v-if="table_index == -1" class="form-control btn btn-info" @click="$parent.showValue(index)">Show Value</button>
                    <button v-else class="form-control btn btn-info" @click="$parent.showTableValue(table_index, index)">Show Value</button>
                </span>
                <span v-else>
                    <input v-if="setting.data_type && setting.data_type == 'date'"
                            type="date"
                            class="dmiux_input__input"
                            v-model="setting.value"
                            :id="'cs_' + table + index"
                            @input="valueChanged(index, setting, table)" />
                    <div v-else-if="setting.data_type && setting.data_type == 'boolean'" 
                            class="dmiux_checkbox">
                        <input @change="valueChanged(index, setting, table)" :id="'cs_' + table + index" type="checkbox" class="dmiux_checkbox__input" v-model="setting.value" >
                        <div class="dmiux_checkbox__check"></div>
                        <div v-if="setting.friendly_name && setting.friendly_name != ''" 
                                class="dmiux_checkbox__label">{{ setting.friendly_name }} <span v-if="$parent.isSettingRequired(setting, table)" class="text-danger">*</span></div>
                        <div v-else class="dmiux_checkbox__label">{{ setting.name }} <span v-if="$parent.isSettingRequired(setting, table)" class="text-danger">*</span></div>
                    </div>
                    <div v-else-if="setting.data_type && setting.data_type == 'select'" 
                            class="dmiux_select">
                            <select v-model="setting.value" 
                                    class="dmiux_select__select"
                                    :id="'cs_' + table + index"
                                    @change="valueChanged(index, setting, table)">
                                <option :disabled="$parent.isSettingRequired(setting, table)" value="">Choose a value</option>
                                <option v-for="(option, key) in setting.options" :value="key">{{ option }}</option>
                            </select>
                            <div class="dmiux_select__arrow"></div>
                    </div>
                    <!-- These need to be arrays not objects -->
                    <multiselect v-else-if="setting.data_type && setting.data_type == 'multiselect' && typeof(setting.options) == 'object'" 
                                v-model="setting.value"
                                :id="'cs_' + table + index"
                                :multiple="true"
                                :options="setting.options" 
                                :show-labels="false" 
                                :close-on-select="false"
                                :clear-on-select="false"
                                :max="getProperty('max', setting.properties, setting.options.length + 1)"
                                @input="valueChanged(index, setting, table)">
                    </multiselect>
                    <input v-else
                            type="text"
                            :id="'cs_' + table + index"
                            class="dmiux_input__input"
                            v-model="setting.value"
                            maxlength="500"
                            @input="valueChanged(index, setting, table)">

                    <small class="text-muted"
                            v-if="setting.description && setting.description != ''">
                            {{ setting.description }}
                    </small>  
                </span>  	
            </div>
        </div> 
    </form>
</script>