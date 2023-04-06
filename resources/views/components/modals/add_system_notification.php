<script type="text/x-template" id="add-system_notitification-modal-template">
    <!-- Server Modal -->
    <div class="dmiux_popup" id="modal-add_system_notification" role="dialog" tabindex="-1">
        <div class="dmiux_popup__window dmiux_popup__window_xl" role="document">
            <div class="dmiux_popup__head">
                <h4 class="dmiux_popup__title">
                    <template v-if="this.$attrs.is_editing == false">Add a Notification</template>
                    <template v-else>Editing a Notification</template>
                </h4>
                <button type="button" class="dmiux_popup__close" @click="closeModal();"></button>
            </div>
            <form id="form-add_system_notification" autocomplete="off">
                <div class="dmiux_popup__cont">
                    <label class="dmiux_popup__label" for="input-add_rule_channel">Channel</label>
                    <div class="dmiux_select">
                        <select id="input-add_rule_channel" class="dmiux_select__select" v-model="channel_id" :disabled="this.$attrs.is_editing">
                            <option value="">Select a Channel</option>
                            <option :value="channel.id" :id="'channel_id_' + channel.id" v-for="channel in channels">{{ channel.name }}</option>
                        </select>
                        <div class="dmiux_select__arrow"></div>
                    </div>
                    <label class="dmiux_popup__label" for="input-add_rule_type">Type</label>
                    <div class="dmiux_select">
                        <select id="input-add_rule_type" class="dmiux_select__select" v-model="type_id" :disabled="this.$attrs.is_editing">
                            <option value="">Select a Type</option>
                            <option :value="type.id" :id="'type_id_' + type.id" v-for="type in types">{{ type.name }}</option>
                        </select>
                        <div class="dmiux_select__arrow"></div>
                    </div>

                    <template v-for="setting in settings">
                        <label class="dmiux_popup__label" :for="'input-add_rule_' + setting.key">
                            {{ setting.name }}
                            <span v-if="setting.is_required" class="text-danger">*</span>
                        </label>
                        
                        <template v-if="setting.input_type == 'select'">
                            <select :id="'input-add_rule_' + setting.key" class="dmiux_select__select" v-model="setting.value">
                                <option value="">Select an Option</option>
                                <option v-for="opt in setting.input_options" :value="opt">{{ opt }}</option>
                            </select>
                        </template>

                        <template v-else>
                            <div v-if="setting.is_secure" class="dmiux_input">
                                <input type="password" class="dmiux_input__input" :id="'input-add_rule_' + setting.key" :name="'input-add_rule_' + setting.key" v-model="setting.value" :placeholder="setting.input_placeholder">
                            </div>
                            <div v-else class="dmiux_input">
                                <input type="text" class="dmiux_input__input" :id="'input-add_rule_' + setting.key" :name="'input-add_rule_' + setting.key" v-model="setting.value" :placeholder="setting.input_placeholder">
                            </div>
                        </template>

                        <small v-if="setting.input_description" v-html="setting.input_description"></small>
                    </template>
                    <p v-if="this.$attrs.is_editing" class="pt-2"><small>
                        <strong>Note: </strong> Changing the channel or type of a notification is not allowed for historical reasons.
                        You should create a new notification and delete the old one if you need to change the channel or type.
                    </small></p>
                </div>
                <div class="dmiux_popup__foot">
                    <div class="dmiux_grid-row">
                        <div class="dmiux_grid-col"></div>
                        <div class="dmiux_grid-col dmiux_grid-col_auto">
                            <button class="dmiux_button dmiux_button_secondary dmiux_popup__close_popup" type="button" @click="closeModal();">Cancel</button>
                        </div>
                        <div class="dmiux_grid-col dmiux_grid-col_auto pl-0">
                            <button :disabled="processing" class="dmiux_button" type="button" @click="submit()"">Submit</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</script>

<script>
    var add_system_notification = Vue.component('add-system-notification', {
        template: '#add-system_notitification-modal-template',
        props: [ "channels", "types", "current_notification" ],
        watch: {
            type_id() {
                if (this.type_id == '') {
                    this.settings = [];
                    return;
                }

                let selected_type = this.types.filter(type => this.type_id == type.id);

                selected_type = selected_type.pop();

                this.settings = selected_type.settings.map((setting) => {
                    setting.value = this.getSettingValue(setting);
                    return setting;
                });
            },
            current_notification() {
                if (this.current_notification == null || this.is_editing == false ){
                    this.channel_id = '';
                    this.type_id = '';
                    return;
                }

                this.channel_id = this.current_notification.channel_id;
                this.type_id = this.current_notification.type_id;
            }
        },
        data: function() {
            return {
                is_valid: true,
                channel_id: '',
                type_id: '',
                settings: [],
                processing: false
            }
        },
        methods: {
            getSettingValue(setting) {
                if (this.current_notification == null) {
                    return setting.input_default ?? '';
                }

                try {
                    return this.current_notification.settings[setting.key];
                } catch (e) {
                    return '';
                }
            },
            closeModal() {
                this.reset();
                $('.dmiux_popup__close_popup').trigger('click');
            },
            submit() {
                if (this.channel_id == '') {
                    notify.danger("Please select a channel");
                    return false;
                }

                if (this.type_id == '') {
                    notify.danger("Please select a notification type");
                    return false;
                }

                if (! this.validateSettings()) {
                    return false;
                }

                var settings_to_send = {};

                this.settings.forEach(function(setting) {
                    settings_to_send[setting.key] = setting.value;
                });

                if (this.current_notification == null) {
                    var options = FetchHelper.buildJsonRequest({
                        channel_id: this.channel_id,
                        type_id: this.type_id,
                        settings: settings_to_send,
                        id: this.current_notification == null ? null : this.current_notification.id
                    });
                    var url = baseUrl + '/internal-api/v1/admin/system-notifications/subscriptions';
                } else {
                    var options = FetchHelper.buildJsonRequest({
                        channel_id: this.channel_id,
                        type_id: this.type_id,
                        settings: settings_to_send,
                    }, 'put');
                    var url = baseUrl + '/internal-api/v1/admin/system-notifications/subscriptions/' + this.current_notification.id;
                }

                this.processing = true;

                fetch(url, options)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        notify.success(json.message);
                        this.processing = false;
                        this.$emit('notification-added');
                        this.closeModal();
                        this.reset();
                    })
                    .catch((error) => {
                        this.$emit('loading', false);
                        this.processing = false;
                        ResponseHelper.handleErrorMessage(error, 'An error occurred.');
                        return;
                    });
            },
            validateSettings() {
                try {
                    this.settings.forEach(function(setting) {
                        if (setting.is_required && setting.value.trim() == '') {
                            notify.danger("The setting '" + setting.name + "' is required.");
                            throw "Blank required setting";
                        }

                        setting.value = setting.value.trim();

                        if ([null, ''].includes(setting.value) && ! setting.is_required) {
                            return true;
                        }

                        if (! [null, ''].includes(setting.input_validation)) {
                            let regex = new RegExp(setting.input_validation);

                            if (! regex.test(setting.value)) {
                                notify.danger("Invalid value for " + setting.name);
                                throw "Validation with regular expression failed.";
                            }
                        }
                    });

                    return true;
                } catch (e) {
                    return false;
                }
            },
            reset() {
                this.type_id = '';
                this.channel_id = '';
            }
        }
    });
</script>