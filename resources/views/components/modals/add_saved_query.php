<script type="text/x-template" id="saved-query-template">
    <div class="dmiux_popup" id="modal-add_saved_query" role="dialog" tabindex="-1">
        <div class="dmiux_popup__window dmiux_popup__window_lg" role="document">
            <div class="dmiux_popup__head">
                <h4 class="dmiux_popup__title"><template v-if="saved_query.id != -1">Update</template><template v-else>Save</template> this Query</h4>
                <button id="x-button" type="button" class="dmiux_popup__close"></button>
            </div>
            <form id="form-add_saved_query" autocomplete="off" onSubmit="event.preventDefault()">
                <div class="dmiux_popup__cont">
                    <div class="dmiux_input">
                        <label for="input-name-saved_query" class="dmiux_popup__label">Name</label>
                        <input @input="cleanupName()" type="text" class="dmiux_input__input" id="input-name-saved_query" v-model="saved_query.name" />
                    </div>
                    <div class="dmiux_input">
                        <label class="dmiux_popup__label" for="input-description">Description</label>
                        <textarea maxlength="500" type="text" class="dmiux_input__input" id="input-description-add-saved-query" v-model="saved_query.description"></textarea>
                    </div>
                </div>
                <div class="dmiux_popup__foot">
                    <div class="dmiux_grid-row">
                        <div class="dmiux_grid-col"></div>
                        <div class="dmiux_grid-col dmiux_grid-col_auto">
                            <button id="cancel-button-add-saved-query" class="dmiux_button dmiux_button_secondary dmiux_popup__close_popup dmiux_popup__cancel" type="button">Cancel</button>
                        </div>
                        <div class="dmiux_grid-col dmiux_grid-col_auto pl-0">
                            <button class="dmiux_button" type="button" @click="save();">Submit</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</script>
