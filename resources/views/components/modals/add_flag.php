<script type="text/x-template" id="flag-modal-template">
    <!-- Flags Modal -->
    <div class="dmiux_popup" id="modal-add_edit_flag" role="dialog" tabindex="-1">
        <div class="dmiux_popup__window dmiux_popup__window_lg" role="document">
            <div class="dmiux_popup__head">
                <h4 class="dmiux_popup__title">Add Flag for <mark v-if="$root.explorer.selected_alias == ''">{{ column_name }}</mark><mark v-else>{{ $root.explorer.selected_alias }}</mark></h4>
                <button type="button" class="dmiux_popup__close"></button>
            </div>
            <div class="dmiux_popup__cont">
                <br>
                <label for="input-assigned_user">Assigned User</label>
                <div class="dmiux_select">
                    <select v-model="assigned_user" id="input-assigned_user" class="dmiux_select__select">
                        <option v-for="(user, index) in actualUsers" :value="user.user_handle">
                        {{ user.name }}
                        </option>
                    </select>
                    <div class="dmiux_select__arrow"></div>
                </div>
                <br>
                <div class="dmiux_input">
                    <label for="input-flag_reason">Flag Reason</label>
                    <textarea id="input-flag_reason" v-model="reason" class="dmiux_input__input atmention" rows="4"></textarea>
                </div>
                <br>
            </div>
            <div class="dmiux_popup__foot">
                <div class="dmiux_grid-row">
                    <div class="dmiux_grid-col"></div>
                    <div class="dmiux_grid-col dmiux_grid-col_auto">
                        <button class="dmiux_button dmiux_button_secondary dmiux_popup__close_popup" type="button">Cancel</button>
                    </div>
                    <div class="dmiux_grid-col dmiux_grid-col_auto pl-0">
                        <button class="dmiux_button" type="button" @click="addFlag()">Add Flag</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>