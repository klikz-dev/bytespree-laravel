<script type="text/x-template" id="counts-modal-template">
    <!-- Counts Modal -->
    <div class="dmiux_popup" id="modal-counts" role="dialog" tabindex="-1">
        <div class="dmiux_popup__window dmiux_popup__window_lg" role="document">
            <div class="dmiux_popup__head">
                <h4 class="dmiux_popup__title">Counts for <mark v-if="$root.explorer.selected_alias == ''"><template v-if="viewing_type=='Join'">{{ selected_prefix }}_</template>{{ selected_column }}</mark><mark v-else>{{ $root.explorer.selected_alias }}</mark></h4>
                <button type="button" id="x-button" class="dmiux_popup__close" @click="modalCloseCounts"></button>
            </div>
            <div class="dmiux_popup__cont dmiux_popup_cont_nav" id="interactive-pane-counts">
                <div class="dmiux_popup__tabs">
                    <a :class="(quantity == 25) ? 'active' : ''" class="nav-link" href="#" @click="getCounts(25);">Top 25</a>
                    <a :class="(quantity == 100) ? 'active' : ''" class="nav-link" href="#" @click="getCounts(100);">Top 100</a>
                    <a :class="(quantity == 250) ? 'active' : ''" class="nav-link" href="#" @click="getCounts(250);">Top 250</a>
                </div>
                <div class="dmiux_data-table dmiux_data-table__cont count-scroll">
                    <table class="dmiux_data-table__table">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th></th>
                                <th>Value</th>
                                <th class="text-right">Count</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(val, index) in counts">
                                <td>{{ index + 1 }}</td>
                                <td></td>
                                <td>
                                    <a href="#"
                                       class="dmiux_popup__close_popup"
                                       @click="addFilter(val);">
                                        <i v-if="isEmpty(val)">(empty)</i>
                                        <!-- Concat value if longer than 100 chars -->
                                        <pre v-else-if="val.value.length > 100 " toggle="tooltip" :title='val.value' class="dmiux_pre-newlines">{{ val.value.substring(0,100) + "..." }}</pre>
                                        <!-- Check if boolean and set to True/False -->
                                        <span v-else-if="$root.explorer.selected_column_data_type == 'boolean' && val.value.toLowerCase() == 't'" >True</span>
                                        <span v-else-if="$root.explorer.selected_column_data_type == 'boolean' && val.value.toLowerCase() == 'f'" >False</span>
                                        <!-- Normal value output -->
                                        <pre v-else class="dmiux_pre-newlines">{{ val.value }}</pre>
                                    </a>
                                </td>
                                <td class="text-right">{{ new Intl.NumberFormat().format(val['qty']) }}</td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="dmiux_popup__foot">
                <div class="dmiux_grid-row">
                    <div v-if="filters.length != 0" class="dmiux_grid-col dmiux_grid-col_auto pr-0">
                        <div class="dmiux_checkbox mt-2">
                            <input @click="changeFilterStatus(quantity)" :checked="applyFilters" type="checkbox" class="dmiux_checkbox__input">
                            <div class="dmiux_checkbox__check"></div>
                            <div class="dmiux_checkbox__label">Apply filters <template v-if="edit_mode == true">(doesn't include filter being edited)</template></div>
                        </div>
                    </div>
                    <div class="dmiux_grid-col"></div>
                    <div class="dmiux_grid-col dmiux_grid-col_auto">
                        <button id="cancel-button-counts" class="dmiux_button dmiux_button_secondary dmiux_popup__close_popup dmiux_popup__cancel" @click="modalCloseCounts" type="button">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>
