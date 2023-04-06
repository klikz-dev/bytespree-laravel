<script type="text/x-template" id="component-add-server">
    <div class="card text-left">
        <div class="dmiux_popup__cont">
            <div v-if="server.type == 'do'">
                <div class="dmiux_input">
                    <label class="dmiux_popup__label" for="add_server_name">Name</label>
                    <input type="text" @input="cleanupName()" class="dmiux_input__input" id="add_server_name" v-model="server.name">
                    <small>Must be alphanumeric with no spaces</small>
                </div>
                <template v-if="server.ips.length > 0">
                    <label class="dmiux_popup__label" for="manage_server_ips">Allowed IP Addresses <small>(Optional)</small> <small v-if="this.server.ips[0] != ''" @click="addIP()" class="float-right cursor-p link-color-blue">Add a New IP</small></label>
                    <div v-for="(ip, index) in server.ips" class="dmiux_input mt-1">
                        <input @blur="validateIP(index)" placeholder="255.255.255.255" type="text" size="15" class="dmiux_input__input" id="manage_server_ips" v-model="server.ips[index]">
                        <button @click="removeIP(index)" type="button" class="dmiux_new-search__remove"></button>
                    </div>
                </template>
                <template v-else>
                    <div class="dmiux_checkbox">
                        <input @change="addIP()" type="checkbox" class="dmiux_checkbox__input">
                        <div class="dmiux_checkbox__check"></div>
                        <div class="dmiux_checkbox__label">Allow access from external IP addresses</div>
                    </div>
                </template>
                <label class="dmiux_popup__label" for="input-add_server_configuration">Configuration</label>
                <div class="dmiux_select">
                    <select class="dmiux_select__select" id="input-add_server_configuration" v-model="server.server_provider_configuration_id">
                        <option disabled value="">Choose a configuration</option>
                        <option v-for="c in configurations" v-if="!server.group_hierarchy || c.group_hierarchy >= server.group_hierarchy" :value="c.id">{{ c.memory }}GB RAM | {{ c.storage }}GB SSD | {{ c.cpus}} vCPUs | {{ c.nodes - 1 }} STANDBY NODES | ${{ c.resale_price }}/month</option>
                    </select>
                    <div class="dmiux_select__arrow"></div>
                </div>
                <hr v-if="! editing" />
                <a v-if="! editing && team_details.custom_postgres_server" href="#" @click="change_type">Use Existing Server</a>
            </div>
            <div v-else>
                <div class="dmiux_input">
                    <label class="dmiux_popup__label" for="input-add_server_name">Name</label>
                    <input type="text" @input="cleanupName()" class="dmiux_input__input" id="input-add_server_name" v-model="server.name">
                    <small>Name must be alphanumeric with no spaces</small>
                </div>
                <div class="dmiux_input">
                    <label class="dmiux_popup__label" for="input-add_server_hostname">Hostname</label>
                    <input type="text" class="dmiux_input__input" id="input-add_server_hostname" v-model="server.hostname">
                </div>
                <div class="dmiux_input">
                    <label class="dmiux_popup__label" for="input-add_server_username">Username</label>
                    <input type="text" class="dmiux_input__input" id="input-add_server_username" v-model="server.username">
                </div>
                <div class="dmiux_input">
                    <label class="dmiux_popup__label" for="input-add_server_password">Password</label>
                    <input type="text" class="dmiux_input__input" id="input-add_server_password" v-model="server.password">
                </div>
                <div class="dmiux_input">
                    <label class="dmiux_popup__label" for="input-add_server_port">Port</label>
                    <input type="number" class="dmiux_input__input" id="input-add_server_port" v-model="server.port">
                </div>
                <div class="dmiux_input">
                    <label class="dmiux_popup__label" for="input-add_server_default_database">Default Database</label>
                    <input type="text" class="dmiux_input__input" id="input-add_server_default_database" v-model="server.default_database">
                    <small>This is typically "postgres"</small>
                </div>
                <div class="dmiux_input">
                    <label class="dmiux_popup__label" for="input-add_server_driver">Driver</label>
                    <div class="dmiux_select">
                        <select class="dmiux_select__select" id="input-add_server_driver" v-model="server.driver">
                            <option selected disabled value="">Select a driver</option>
                            <option value="postgre">PostgreSQL</option>
                        </select>
                        <div class="dmiux_select__arrow"></div>
                    </div>
                </div>
                <hr v-if="!editing" />
                <a v-if="!editing" href="#" @click="change_type">Use Cloud Server</a>
            </div>
        </div>
        <div class="dmiux_popup__foot">
            <div class="dmiux_grid-row">
                <div class="dmiux_grid-col"></div>
                <div class="dmiux_grid-col dmiux_grid-col_auto">
                    <button class="dmiux_button" type="button" @click="submit_server();">Submit</button>
                </div>
            </div>
        </div>
    </div>
</script>