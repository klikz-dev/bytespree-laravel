<script type="text/x-template" id="copy-column-modal-template">
    <div class="dmiux_popup" id="modal-copy_column" role="dialog" tabindex="-1">
        <div class="dmiux_popup__window dmiux_popup__window_md" role="document">
            <div class="dmiux_popup__head">
                <h4 class="dmiux_popup__title">Copy to New Column </h4>
                <button id="x-button" type="button" class="dmiux_popup__close" @click="modalCloseCopy($event)"></button>
            </div>
            <div class="dmiux_popup__cont">
                <div class="dmiux_input">
                    <label class="dmiux_popup__label" for="input-new_column_name">Copy <mark v-if="selected_alias == ''"><template v-if="viewing_type=='Join'">{{ selected_prefix }}_</template>{{ selected_column }}</mark><mark v-else>{{ selected_alias }}</mark> to</label>
                    <input type="text" @keyup="cleanupName($event)" class="dmiux_input__input" id="input-new_column_name" :value="new_column.column_name" placeholder="New Column Name">
                </div>
                <div class="dmiux_grid-row">
                    <div class="dmiux_grid-col">
                        <label class="dmiux_popup__label" for="input-new_column_data_type">Data Type of New Column</label>
                        <div class="dmiux_select">
                            <select class="dmiux_select__select" v-model="new_column.data_type" id="input-new_column_data_type">
                                <option value="varchar">VARCHAR</option>
                                <option value="bigint">INTEGER</option>
                                <option value="boolean">BOOLEAN</option>
                                <option value="numeric">NUMERIC</option>
                                <option value="date">DATE</option>
                                <option value="timestamp">TIMESTAMP</option>
                                <option v-if="selected_column_data_type == 'json'" value="jsonb">JSON</option>
                            </select>
                            <div class="dmiux_select__arrow"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="dmiux_popup__foot">
                <div class="dmiux_grid-row">
                    <div class="dmiux_grid-col"></div>
                    <div class="dmiux_grid-col dmiux_grid-col_auto">
                        <button id="cancel-button-copy-column" class="dmiux_button dmiux_button_secondary dmiux_popup__cancel" @click="modalCloseCopy($event)" type="button">Cancel</button>
                    </div>
                    <div class="dmiux_grid-col dmiux_grid-col_auto pl-0">
                        <button class="dmiux_button" type="button" @click="copyColumn()">Copy Column</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>