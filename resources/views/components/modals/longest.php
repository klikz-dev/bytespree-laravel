<script type="text/x-template" id="longest-modal-template">
    <!-- Longest_Ten Modal -->
    <div class="dmiux_popup" id="modal-longest" role="dialog" tabindex="-1">
        <div class="dmiux_popup__window dmiux_popup__window_lg" role="document">
            <div class="dmiux_popup__head">
                <h4 class="dmiux_popup__title">Ten Longest Values for <mark v-if="$root.explorer.selected_alias == ''"><template v-if="viewing_type=='Join'">{{ selected_prefix }}_</template>{{  selected_column }}</mark><mark v-else>{{ $root.explorer.selected_alias }}</mark></h4>
                <button type="button" id="x-button" class="dmiux_popup__close" @click="modalCloseLongest"></button>
            </div>
            <div class="dmiux_popup__cont" id="interactive-pane-counts" >
                <div class="dmiux_data-table dmiux_data-table__cont">
                    <table class="dmiux_data-table__table">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Value</th>
                                <th>Character Length</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(val, index) in longest">
                                <td>{{ index + 1 }}</td>
                                <td>
                                    <a href="#" 
                                       class="dmiux_popup__close_popup"
                                       @click="addFilter(val)"> 
                                        <i v-if="isEmpty(val)">(empty)</i>
                                        <!-- Check if boolean and set to True/False -->
                                        <span v-else-if="$root.explorer.selected_column_data_type == 'boolean' && val['value'].toLowerCase() == 't'" >True</span>
                                        <span v-else-if="$root.explorer.selected_column_data_type == 'boolean' && val['value'].toLowerCase() == 'f'" >False</span>
                                        <!-- Default value output -->
                                        <pre class="dmiux_pre-newlines" v-else>{{ val['value'] }}</pre>
                                    </a>
                                </td>
                                <td>{{ val['col_length'] }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="dmiux_popup__foot">
                <div class="dmiux_grid-row">
                    <div v-if="filters.length != 0" class="dmiux_grid-col dmiux_grid-col_auto pr-0">
                        <div class="dmiux_checkbox mt-2">
                            <input @click="changeFilterStatus()" :checked="applyFilters" type="checkbox" class="dmiux_checkbox__input">
                            <div class="dmiux_checkbox__check"></div>
                            <div class="dmiux_checkbox__label">Apply filters <template v-if="edit_mode == true">(doesn't include filter being edited)</template></div>
                        </div>
                    </div>
                    <div class="dmiux_grid-col"></div>
                    <div class="dmiux_grid-col dmiux_grid-col_auto">
                        <button id="cancel-button-longest" class="dmiux_button dmiux_button_secondary dmiux_popup__close_popup dmiux_popup__cancel" @click="modalCloseLongest" type="button">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>
