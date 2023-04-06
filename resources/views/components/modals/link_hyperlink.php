<script type="text/x-template" id="hyperlink-modal-template">
    <!-- Search Modal -->
    <div class="dmiux_popup" id="modal-link_hyperlink" role="dialog" tabindex="-1">
        <div class="dmiux_popup__window dmiux_popup__window_lg" style="width: 90% !important;" role="document">
            <div class="dmiux_popup__head">
                <h4 class="dmiux_popup__title">Add an Attachment</h4>
                <button @click="modalClose()" type="button" class="dmiux_popup__close" id="x-button"></button>
            </div>
            <form id="form-link_hyperlink" autocomplete="off">
                <div class="dmiux_popup__cont">
                    <div class="dmiux_block">
                        <div class="dmiux_select">
                            <select class="dmiux_select__select dmiux_select__select_pholder" v-model="type">
                                <option selected disabled value="">Select attachment type</option>
                                <option value="link">Link</option>
                                <option value="file">File</option>
                            </select>
                            <div class="dmiux_select__arrow"></div>
                        </div>
                    </div>
                    <div v-if="type == 'link'">
                        <h4 class="text-center">Add a Link</h4>
                        <div class="dmiux_input">
                            <label for="input-program_file">URL</label>
                            <input type="text"
                                    class="dmiux_input__input"
                                    v-model="url"
                                    id="input-program_file">
                        </div>
                        <div class="dmiux_input">
                            <label for="input-program_library">Name</label>
                            <input id="input-program_library" type="text" class="dmiux_input__input" v-model="name">
                        </div>
                        <div class="dmiux_input">
                            <label for="input-link_description">Description</label>
                            <textarea v-model="desc" class="dmiux_input__input" id="input-link_description" rows="3"></textarea>
                        </div>
                    </div>
                    <div v-else-if="type == 'file'">
                        <h4 class="text-center">Add a File</h4>
                        <div v-if="error_msg" class="alert alert-danger" role="alert">{{ error_msg }}</div>
                        <form id="fileUploadForm" method="post" enctype="multipart/form-data">
                            <div class="dmiux_grid-row mb-2">
                                <div class="dmiux_grid-col dmiux_grid-col_auto">
                                    <div class="dmiux_button button_nom dmiux_button_secondary inline-block" @click="clearFile()">Choose File
                                        <input type="file" @input="onFileChange" name="fileToUpload" id="fileToUpload"/>
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-muted">{{ truncatedFileName }}</small>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <p>Maximum file size = {{ max_size }}</p>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="dmiux_popup__foot">
                    <div class="dmiux_grid-row">
                        <div class="dmiux_grid-col"></div>
                        <div class="dmiux_grid-col dmiux_grid-col_auto">
                            <button @click="modalClose()" class="dmiux_button dmiux_button_secondary dmiux_popup__close_popup dmiux_popup__cancel" type="button" id="cancel-button-link-hyperlink">Cancel</button>
                        </div>
                        <div v-if="type == 'link'" class="dmiux_grid-col dmiux_grid-col_auto pl-0">
                            <button class="dmiux_button" type="button" @click="submit()">Save</button>
                        </div>
                        <div v-else-if="type == 'file'" class="dmiux_grid-col dmiux_grid-col_auto pl-0">
                            <button class="dmiux_button" type="submit" form="fileUploadForm" @click.prevent="uploadFile()">Upload</button>
                        </div>
                    </div>    
                </div>
            </form>
        </div>
    </div>
</script>
