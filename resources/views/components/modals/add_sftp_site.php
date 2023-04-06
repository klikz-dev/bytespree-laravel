<script type="text/x-template" id="add-sftp-modal-template">
    <!-- Server Modal -->
    <div class="dmiux_popup" id="modal-add-sftp" role="dialog" tabindex="-1">
        <div class="dmiux_popup__window" role="document">
            <div class="dmiux_popup__head">
                <h4 class="dmiux_popup__title">
                    <template v-if="editing == false">Add SFTP Site</template>
                    <template v-else>Edit - {{ sftp.hostname.substring(0, 20) }}<span v-if="sftp.hostname.length > 20">...</span></template>
                </h4>
                <button type="button" class="dmiux_popup__close" @click="cancelEdit();"></button>
            </div>
            <form id="form-add_sftp" autocomplete="off">
                <div class="dmiux_popup__cont">
                    <div class="dmiux_input">
                        <label class="dmiux_popup__label" for="input-add_sftp_default_path">Default Path</label>
                        <input type="text" class="dmiux_input__input" id="input-add_sftp_default_path" v-model="sftp.default_path">
                    </div>
                    <div class="dmiux_input">
                        <label class="dmiux_popup__label" for="input-add_sftp_hostname">Hostname</label>
                        <input type="text" class="dmiux_input__input" id="input-add_sftp_hostname" v-model="sftp.hostname">
                        <small>Required</small>
                    </div>
                    <div class="dmiux_input">
                        <label class="dmiux_popup__label" for="input-add_sftp_username">Username</label>
                        <input type="text" class="dmiux_input__input" id="input-add_sftp_username" v-model="sftp.username">
                        <small>Required</small>
                    </div>
                    <div class="dmiux_input">
                        <label class="dmiux_popup__label" for="input-add_sftp_password">Password</label>
                        <input :placeholder="(editing == true) ? 'Enter password to update' : ''" type="password" class="dmiux_input__input" id="input-add_sftp_password" v-model="sftp.password" autocomplete="new-password">
                        <small v-if="editing == false">Required</small>
                    </div>
                    <div class="dmiux_input">
                        <label class="dmiux_popup__label" for="input-add_sftp_port">Port</label>
                        <input type="number" class="dmiux_input__input" id="input-add_sftp_port" v-model="sftp.port">
                        <small>Required - default is 22</small>
                    </div>
                </div>
                <div class="dmiux_popup__foot">
                    <div class="dmiux_grid-row">
                        <div class="dmiux_grid-col"></div>
                        <div class="dmiux_grid-col dmiux_grid-col_auto">
                            <button class="dmiux_button dmiux_button_secondary dmiux_popup__close_popup" type="button" @click="cancelEdit();">Cancel</button>
                        </div>
                        <div class="dmiux_grid-col dmiux_grid-col_auto pl-0">
                            <button :disabled="!is_valid" class="dmiux_button" type="button" @click="submitSite()">Submit</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</script>

<script>
    var add_sftp = Vue.component('add-sftp', {
        template: '#add-sftp-modal-template',
        props: [ "sftp", "editing" ],
        data: function() {
            return {
                is_valid: true
            }
        },
        methods: {
            cancelEdit() {
                this.$root.getSites();
            },
            submitSite() {
                this.$root.loading(true);

                if (this.sftp.hostname == "" || this.sftp.port == "" || this.sftp.username == "" || (this.editing == false && this.sftp.password == ""))
                {
                    if (this.editing)
                        notify.danger("Host, port, and username fields are required");
                    else
                        notify.danger("Host, port, username, and password fields are required");

                    this.$root.loading(false);
                    return;
                }

                var options = FetchHelper.buildJsonRequest({
                    default_path: this.sftp.default_path,
                    hostname:     this.sftp.hostname,
                    username:     this.sftp.username,
                    password:     this.sftp.password,
                    port:         this.sftp.port
                }, this.editing ? 'PUT' : 'POST');

                if (this.editing)
                {
                    fetch(`/internal-api/v1/admin/sftp/${this.sftp.id}`, options)
                        .then(FetchHelper.handleJsonResponse)
                        .then(json => {
                            this.$root.loading(false);
                            this.$root.getSites(function () {
                                notify.success("SFTP site updated successfully.")
                            });
                            $('.dmiux_popup__close_popup').trigger('click');
                        })
                        .catch((error) => {
                            this.$root.loading(false);
                            ResponseHelper.handleErrorMessage(error, 'An error occurred.');
                        });
                }
                else
                {
                    fetch("/internal-api/v1/admin/sftp", options)
                        .then(FetchHelper.handleJsonResponse)
                        .then(json => {
                            this.$root.loading(false);

                            this.$root.getSites(function() {
                                notify.success("SFTP site added successfully.")
                            });
                            $('.dmiux_popup__close_popup').trigger('click');
                        })
                        .catch((error) => {
                            this.$root.loading(false);
                            ResponseHelper.handleErrorMessage(error, 'An error occurred.');
                        });
                }
            }
        }
    });
</script>