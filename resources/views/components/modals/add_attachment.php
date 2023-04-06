<script type="text/x-template" id="attachment-modal-template">    
    <!-- File Modal -->
    <div class="dmiux_popup" id="modal-add_attachment" role="dialog" tabindex="-1">
        <div class="dmiux_popup__window dmiux_popup__window_lg" role="document">
            <div class="dmiux_popup__head">
                <h4 class="dmiux_popup__title">Add File for <mark v-if="$root.explorer.selected_alias == ''">{{ selected_column }}</mark><mark v-else>{{ $root.explorer.selected_alias }}</mark></h4>
                <button type="button" class="dmiux_popup__close"></button>
            </div>
            <div class="dmiux_popup__cont" style="min-height: 0px;">
                <div class="alert alert-danger hidden mt-2" id="file-error" v-html="error_msg"></div>
                <form id="fileUploadForm" method="post" enctype="multipart/form-data">
                    <div class="dmiux_grid-row">
                        <div class="dmiux_grid-col dmiux_grid-col_auto">
                            <div class="dmiux_button button_nom dmiux_button_secondary">Choose File
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 12 16"><path fill="currentColor" d="M3 5v4c0 1.7 1.3 3 3 3s3-1.3 3-3V4.5C9 2 7 0 4.5 0S0 2 0 4.5V10c0 3.3 2.7 6 6 6s6-2.7 6-6V4h-2v6c0 2.2-1.8 4-4 4s-4-1.8-4-4V4.5C2 3.1 3.1 2 4.5 2S7 3.1 7 4.5V9c0 .6-.4 1-1 1s-1-.4-1-1V5H3z"></path></svg>
                                <input type="file" name="fileToUpload" id="fileToUpload" />
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="dmiux_popup__foot">
                <div class="dmiux_grid-row">
                    <div class="dmiux_grid-col dmiux_grid-col_auto"><span class="float-left">Max File Size is {{ max_upload_size }}</span></div>
                    <div class="dmiux_grid-col"></div>
                    <div class="dmiux_grid-col dmiux_grid-col_auto">
                        <button class="dmiux_button dmiux_button_secondary dmiux_popup__close_popup" type="button">Cancel</button>
                    </div>
                    <div class="dmiux_grid-col dmiux_grid-col_auto pl-0">
                        <button class="dmiux_button" type="button" @click="submitAttachment();">
                            Upload
                        </button>
                    </div>
                </div>
            </div>
            <div class="dmiux_popup__cont" style="max-height: 200px; overflow: scroll;">
                <div class="dmiux_data-table dmiux_data-table__cont">
                    <table class="dmiux_data-table__table"> 
                        <thead>
                            <tr>
                                <th></th>
                                <th>Attachment Name</th>
                                <th>Submitter</th>                                
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="attachment in columnAttachments" class="dmiux_eventr">
                                <td class="dmiux_td--icon dmiux_data-table__actions">
                                <div class="dmiux_actionswrap dmiux_actionswrap--bin" data-toggle="tooltip" title="Delete" @click="deleteAttachment(attachment['id']);"></div>
                                </td>
                                <td><a :href="'/studio/projects/' + attachment['project_id'] + '/attachment/' + attachment['id']" >{{ attachment['file_name'] }}</a></td>
                                <td>{{ attachment['user_id'] }}</td>                                
                            </tr>
                        </tbody>
                    </table>
                    <br>
                </div>
            </div>
        </div>
    </div>
</script>