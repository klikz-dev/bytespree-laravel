<script type="text/x-template" id="map-template">
    <div class="dmiux_grid-cont dmiux_grid-cont_fw" id="content">
        <div class="dmiux_grid-row dmiux_grid-row_jcc mb-4">
            <div class="dmiux_grid-col dmiux_grid-col_auto">
                <button class="dmiux_button dmiux_button_secondary" @click="goBack('/studio/projects/' + project_id + '/tables/' + $parent.schema + '/' + selected_table)"><span class="fas fa-chevron-left"></span> Back to record view</button>
            </div>
            <div class="dmiux_grid-col dmiux_grid-col_auto">
                <button class="dmiux_button" type="button" @click="downloadMap()"><span class="fas fa-download"></span> Download as CSV</button>
            </div>
        </div>
        <div class="dmiux_data-table" v-show="mappings != null && mappings.length > 0">
            <div class="dmiux_data-table__cont">    
                <button type="button" class="dmiux_data-table__arrow dmiux_data-table__arrow_left mapping_left"><i></i></button>
                <button type="button" class="dmiux_data-table__arrow dmiux_data-table__arrow_right mapping_right"><i></i></button>
                <div class="dmiux_data-table__settings">
                    <div v-for="(column, index) in column_headers" class="dmiux_checkbox">
                        <input type="checkbox" @change="hideColumn($event)" :value="index + 1" checked class="dmiux_checkbox__input">
                        <div class="dmiux_checkbox__check"></div>
                        <div class="dmiux_checkbox__label">{{ column.column_name }}</div>
                    </div>
                </div>
                <table id="dmiux_data-table" class="dmiux_data-table__table">
                    <thead>
                        <tr>
                            <th>
                                <div class="dmiux_data-table__actions">
                                    <button type="button" class="dmiux_data-table__cog"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path fill="#FFF" d="M13.3 5.2l1.1-2.1L13 1.7l-2.1 1.1c-.3-.2-.7-.3-1.1-.4L9 0H7l-.8 2.3c-.3.1-.7.2-1 .4L3.1 1.6 1.6 3.1l1.1 2.1c-.2.3-.3.7-.4 1L0 7v2l2.3.8c.1.4.3.7.4 1.1L1.6 13 3 14.4l2.1-1.1c.3.2.7.3 1.1.4L7 16h2l.8-2.3c.4-.1.7-.3 1.1-.4l2.1 1.1 1.4-1.4-1.1-2.1c.2-.3.3-.7.4-1.1L16 9V7l-2.3-.8c-.1-.3-.2-.7-.4-1zM8 11c-1.7 0-3-1.3-3-3s1.3-3 3-3 3 1.3 3 3-1.3 3-3 3z"/></svg></button>
                                </div>
                            </th>
                            <th v-for="(column, index) in column_headers">{{ column.column_name }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(map, index) in mappings" :class="map.is_deleted == true ? 'dmiux__input-deleted' : ''">
                            <td></td>
                            <td>
                                <div class="dmiux_checkbox">
                                    <input type="checkbox" v-model="map.is_programmed" @change="updateProgrammed(index)"class="dmiux_checkbox__input" />
                                    <div class="checkbox-center dmiux_checkbox__check"></div>
                                </div>
                            </td>
                            <td>{{ map.ordinal_position }}</td>
                            <td>{{ selected_table }}</td>
                            <td>{{ map['source_column_name'] }}</td>
                            <td v-if="map['destination_table_name'] != ''">{{ map['destination_table_name'] }}</td>
                            <td v-else></td>
                            <td v-if="map['destination_column_name'] != ''">{{ map['destination_column_name'] }}</td>
                            <td v-else></td>
                            <td>{{ map['module_name'] }}</td>
                            <td><span v-for="data in map['module_data']">{{ data.mapping_module_field_name }}: {{ data.value }}<br></span></td>
                            <td>{{ map['created_at_formatted'] }}</td>
                            <td>{{ map['condition'] }}</td>
                            <td>{{ map['notes'] }}</td>
                            <td v-html="map.comment_text"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="panel-footer">
                <small>Copyright &copy; {{ date }} Data Management Inc.</small>
            </div>
            <br>
        </div>
        <div id="hidedisplay"></div>
    </div>
</script>