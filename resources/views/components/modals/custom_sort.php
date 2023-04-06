<script type="text/x-template" id="custom-sort-template">
    <!-- Custom Filter Modal -->
    <div class="dmiux_popup" id="modal-custom_sort" role="dialog" tabindex="-1">
        <div class="dmiux_popup__window dmiux_popup__window_lg" role="document">
            <div class="dmiux_popup__head">
                <h4 class="dmiux_popup__title"><span v-if="edit_mode == true">Edit </span>Custom Sort Expression</h4>
                <button type="button" id="button-close_custom-sort" class="dmiux_popup__close" @click="modalClose"></button>
            </div>
            <div class="dmiux_popup__cont">
                <div class="dmiux_grid-row">
                    <div class="dmiux_grid-col dmiux_grid-col_05"></div>
                    <div class="dmiux_grid-col">
                        <div class="dmiux_input-group">
                            <div class="dmiux_input-group-prepend">
                                <div class="dmiux_input-group-text code_editor">Order By</div>
                            </div>
                            <div class="dmiux_input__input">
                                <prism-editor id="ps-sort_expression" class="code_editor" :highlight="highlighter" v-model="sort_expression" placeholder="Enter sort expression"></prism-editor>
                            </div>
                        </div>
                    </div>
                    <div class="dmiux_grid-col dmiux_grid-col_05"></div>
                </div>
            </div>
            <div class="dmiux_popup__foot">
                <div class="dmiux_grid-row">
                    <div class="dmiux_grid-col"></div>
                    <div class="dmiux_grid-col dmiux_grid-col_auto">
                        <button id="button-cancel_custom-sort" class="dmiux_button dmiux_button_secondary dmiux_popup__close_popup" @click="modalClose" type="button">Cancel</button>
                    </div>
                    <div class="dmiux_grid-col dmiux_grid-col_auto pl-0">
                        <button id="button-apply_custom-sort" class="dmiux_button" type="button" @click="apply">Apply Sort</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>
