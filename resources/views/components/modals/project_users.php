<script type="text/x-template" id="project-users-modal-template">
    <div class="dmiux_popup" id="modal-project_users" ref="project_users_modal" role="dialog" tabindex="-1">
        <div class="dmiux_popup__window dmiux_popup__window_md" role="document">
            <div class="dmiux_popup__head">
                <h4 class="dmiux_popup__title display-inline_flex">
                    Users in <span class="modal-title-overflow_text pl-1">{{ selected_project.display_name }}</span>
                </h4>
                <button type="button" class="dmiux_popup__close"></button>
            </div>
            <div class="dmiux_popup__cont">
                <table class="dmiux_data-table__table">
                    <tbody>
                        <tr v-for="member in users">
                            <td>{{ member.name }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="dmiux_popup__foot">
                <div class="dmiux_grid-row">
                    <div class="dmiux_grid-col"></div>
                    <div class="dmiux_grid-col dmiux_grid-col_auto pl-0">
                        <button class="dmiux_button" type="button" @click="closeModal();">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>