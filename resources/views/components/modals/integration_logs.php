<script type="text/x-template" id="integration-logs-modal-template">
    <!-- Integration Logs Modal -->
    <div class="dmiux_popup" id="modal-integration-logs">
        <div class="dmiux_popup__window dmiux_popup__window_lg" role="document">
            <div class="dmiux_popup__head">
                <h4 class="dmiux_popup__title">Integration Logs</h4>
                <button id="x-button" type="button" class="dmiux_popup__close"></button>
            </div>
            <div class="dmiux_popup__cont">
                <div class="dmiux_grid-row mb-2">
                    <div class="dmiux_grid-col" :class="tables.length > 0 ? 'dmiux_grid-col_4' : 'dmiux_grid-col_6'">
                        <label for="status_filter">Filter by status</label>
                        <div class="dmiux_select">
                            <select @change="setFilters()"
                                    class="dmiux_select__select"
                                    v-model="filters.status"
                                    id="status_filter">
                                    <option value="">None</option>
                                    <option value="SUCCESS">Success</option>
                                    <option value="FAILURE">Failure</option>
                                    <option value="ABORTED">Aborted</option>
                            </select>
                            <div class="dmiux_select__arrow"></div>
                        </div>
                    </div>
                    <div class="dmiux_grid-col" :class="tables.length > 0 ? 'dmiux_grid-col_4' : 'dmiux_grid-col_6'">
                        <label for="type_filter">Filter by type</label>
                        <div class="dmiux_select">
                            <select @change="setFilters()"
                                    class="dmiux_select__select"
                                    v-model="filters.type"
                                    id="type_filter">
                                    <option value="">None</option>
                                    <option value="sync">Sync</option>
                                    <option value="build">Build</option>
                                    <option value="test">Test</option>
                            </select>
                            <div class="dmiux_select__arrow"></div>
                        </div>
                    </div>
                    <div v-if="tables.length > 0" class="dmiux_grid-col dmiux_grid-col_4">
                        <label for="table_filter">Filter by table</label>
                        <div class="dmiux_select">
                            <select @change="setFilters()"
                                    class="dmiux_select__select"
                                    v-model="filters.table"
                                    id="table_filter">
                                    <option value="">None</option>
                                    <option v-for="table in tables" :value="table.name">{{ table.name }}</option>
                            </select>
                            <div class="dmiux_select__arrow"></div>
                        </div>
                    </div>
                </div>
                <div v-if="integration_logs.length == 0" style="width: 100%;" class="alert alert-info" role="alert">
                    No logs found.
                </div>
                <div v-else>
                    <div class="dmiux_grid-row">
                        <div class="dmiux_grid-col">
                            <div class="dmiux_data-table dmiux_data-table__cont">
                                <table id="integration-logs-table" class="dmiux_data-table__table mb-2"> 
                                    <thead>
                                        <tr>
                                            <th>Table</th>
                                            <th>Type</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(log, index) in integration_logs" :class="log.status == 'FAILURE' ? 'bg-danger text-light' : ''">
                                            <td>{{ log.job_name }}</td>
                                            <td>{{ log.job_type }}</td>
                                            <td><span class="hidden">{{ log.build_timestamp }}</span>{{ log.build_timestamp_formatted }}</td>
                                            <td :class="[ log.result == 'SUCCESS' ? 'text-success' : '', log.result == 'FAILURE' && log.job_type == 'sync' ? 'text-danger' : '', log.result == 'FAILURE' && log.job_type == 'test' ? 'text-warning' : '' ]"><b>{{ log.result }}</b></td>
                                            <td>
                                                <a href="javascript:void(0)" @click="getLogText(index)">view</a>
                                                <a href="javascript:void(0)" title="Send log to email" @click="sendLogEmail(index)">send</a>
                                                <a target="_blank" :href="$parent.baseUrl + '/data-lake/database-manager/' + control_id + '/logs/' + log.id + '/download'">download</a>                                            
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <br>
                            </div>
                        </div>
                    </div>	
                </div>
                <div v-if="console_text != ''" class="dmiux_grid-row" id="build_log">
                    <div class="dmiux_grid-col">
                        <div class="dmiux_block mb-0">
                            <label class="mb-2 w-100">
                                Build Log 
                                <span class="float-right">
                                    <a href="javascript:void(0)" title="Send log to email" @click="sendLogEmail(current_index)">send</a>
                                    <a target="_blank" :href="$parent.baseUrl + '/data-lake/database-manager/' + control_id + '/logs/' + build_id + '/download'">download</a>
                                </span>
                            </label>
                            <pre class="height-245">{{ console_text }}</pre>
                        </div>
                    </div>
                </div>
            </div>
            <div class="dmiux_popup__foot">
                <div class="dmiux_grid-row">
                    <div class="dmiux_grid-col"></div>
                    <div class="dmiux_grid-col dmiux_grid-col_auto">
                        <button id="cancel-button-integration-logs" type="button" class="dmiux_button dmiux_button_secondary dmiux_popup__close_popup dmiux_popup__cancel">Close</button>
                    </div>
                </div>	
            </div>
        </div>
        </div>
    </div>
</script>
