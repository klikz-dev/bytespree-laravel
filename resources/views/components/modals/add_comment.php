<script type="text/x-template" id="comment-modal-template">    
    <!-- Comments Modal -->
    <div class="dmiux_popup" id="modal-add_comment" role="dialog" tabindex="-1">
        <div class="dmiux_popup__window dmiux_popup__window_lg" role="document">
            <div class="dmiux_popup__head">
                <h4 class="dmiux_popup__title">Add Comment on <mark v-if="$root.explorer.selected_alias == ''">{{ selected_column }}</mark><mark v-else>{{ $root.explorer.selected_alias }}</mark></h4>
                <button id="button-close_add_comment" type="button" class="dmiux_popup__close"></button>
            </div>
            <div class="dmiux_popup__cont" style="min-height: 0px;">
                <br>
                <div class="dmiux_input">
                    <label for="input-comment">Comment</label>
                    <textarea class="form-control atmention" v-model="comment" rows="4" required id="input-comment"></textarea>
                </div>
            </div>
            <div class="dmiux_popup__foot">
                <div class="dmiux_grid-row">
                    <div class="dmiux_grid-col"></div>
                    <div class="dmiux_grid-col dmiux_grid-col_auto">
                        <button id="button-cancel_add_comment" class="dmiux_button dmiux_button_secondary dmiux_popup__close_popup" type="button">Cancel</button>
                    </div>
                    <div class="dmiux_grid-col dmiux_grid-col_auto pl-0">
                        <button class="dmiux_button" type="button" @click="addComment();">Save Comment</button>
                    </div>
                </div>
            </div>
            <div class="dmiux_popup__cont" id="interactive-pane-comments" style="max-height: 200px; overflow: scroll;">
                <div class="dmiux_data-table dmiux_data-table__cont">
                    <table class="dmiux_data-table__table"> 
                        <thead>
                            <tr>
                                <th>Who</th>
                                <th>What</th>
                                <th>When</th>
                            </tr>
                        </thead>
                        <tbody id="comments-pane">
                            <tr v-for="comment in columnComments">
                                <td class="align-top">{{ comment['user_id'] }}</td>
                                <td class="align-top"><pre id="comment_formating" v-html="comment['comment_text']"></pre></td>
                                <td class="align-top">{{ comment['created_at'] }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <br>
                </div>
            </div>
        </div>
    </div>
</script>