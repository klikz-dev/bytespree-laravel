<script type="text/x-template" id="add-microsoft-server-modal-template">
    <!-- Server Modal -->
    <div class="dmiux_popup" id="modal-add-microsoft-server" role="dialog" tabindex="-1">
        <div class="dmiux_popup__window" role="document">
            <div class="dmiux_popup__head">
                <h4 class="dmiux_popup__title">
                    <template v-if="editing == false">Add Microsoft SQL Server</template>
                    <template v-else>Edit - {{ server.hostname.substring(0, 20) }}<span v-if="server.hostname.length > 20">...</span></template>
                </h4>
                <button type="button" class="dmiux_popup__close" @click="cancel()"></button>
            </div>
            <form id="form-add_microsoft_server" autocomplete="off" onSubmit="event.preventDefault()">
                <div class="dmiux_popup__cont">
                    <div class="dmiux_input">
                        <label class="dmiux_popup__label" for="input-add_ms_server_hostname">Hostname</label>
                        <input type="text" class="dmiux_input__input" id="input-add_ms_server_hostname" v-model="server.hostname" maxlength="1024">
                        <small>Required</small>
                    </div>
                    <div class="dmiux_input">
                        <label class="dmiux_popup__label" for="input-add_ms_server_username">Username</label>
                        <input type="text" class="dmiux_input__input" id="input-add_ms_server_username" v-model="server.username" maxlength="115">
                        <small>Required</small>
                    </div>
                    <div class="dmiux_input">
                        <label class="dmiux_popup__label" for="input-add_ms_server_password">Password</label>
                        <input :placeholder="(editing == true) ? 'Enter password to update' : ''" type="password" class="dmiux_input__input" id="input-add_ms_server_password" v-model="server.password" maxlength="128" autocomplete="new-password">
                        <small v-if="editing == false">Required</small>
                    </div>
                    <div class="dmiux_input">
                        <label class="dmiux_popup__label" for="input-add_server_port">Port</label>
                        <input type="text" class="dmiux_input__input" id="input-add_server_port" v-model="server.port" maxlength="5" pattern="[0-9]{1,5}" @keyup="portFilter()">
                        <small>Required - default is 1433</small>
                    </div>
                </div>
                <div class="dmiux_popup__foot">
                    <div class="dmiux_grid-row">
                        <div class="dmiux_grid-col"></div>
                        <div class="dmiux_grid-col dmiux_grid-col_auto">
                            <button class="dmiux_button dmiux_button_secondary dmiux_popup__close_popup" type="button" @click="cancel();">Cancel</button>
                        </div>
                        <div class="dmiux_grid-col dmiux_grid-col_auto pl-0">
                            <button :disabled="!is_valid" class="dmiux_button" type="submit" @click="save()">Submit</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</script>

<script>
    var add_microsoft_sql_server = Vue.component('add-microsoft-server', {
        template: '#add-microsoft-server-modal-template',
        props: [ "server", "editing" ],
        data: function() {
            return {
                is_valid: true
            }
        },
        methods: {
            cancel() {
                this.$root.getServers();
            },
            portFilter() {
                this.server.port = this.server.port.replace(/\D/g, '');
            },
            save() {
                if (this.server.hostname == "" || this.server.port == "" || this.server.username == "" || (this.editing == false && this.server.password == "")) {
                    if (this.editing) {
                        notify.danger("Hostname, port, and username fields are required");
                    } else {
                        notify.danger("Hostname, port, username, and password fields are required");
                    }

                    return;
                }
                
                this.$root.loading(true);

                let options = FetchHelper.buildJsonRequest({
                    hostname: this.server.hostname,
                    username: this.server.username,
                    password: this.server.password,
                    port: this.server.port
                }, this.editing ? 'PUT' : 'POST');

                if (this.editing) {
                    fetch(`/internal-api/v1/admin/mssql/${this.server.id}`, options)
                        .then(FetchHelper.handleJsonResponse)
                        .then(json => {
                            this.$root.loading(false);
                            notify.success("Server has been updated successfully");
                            this.$root.getServers();
                            $('.dmiux_popup__close_popup').trigger('click');
                        })
                        .catch((error) => {
                            this.$root.loading(false);
                            ResponseHelper.handleErrorMessage(error, "Unable to update this server.");
                        });
                } else {
                    fetch("/internal-api/v1/admin/mssql", options)
                        .then(FetchHelper.handleJsonResponse)
                        .then(json => {
                            this.$root.loading(false);
                            notify.success("Server has been created successfully");
                            this.$root.getServers();
                            $('.dmiux_popup__close_popup').trigger('click');
                        })
                        .catch((error) => {
                            this.$root.loading(false);
                            ResponseHelper.handleErrorMessage(error, "There was a problem adding your server.");
                        });
                }
            }
        }
    });
</script>