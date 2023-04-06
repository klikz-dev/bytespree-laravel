<script type="text/x-template" id="component-widget-links">
    <div class="dmiux_grid-cont dmiux_grid-cont_fw" id="links">
        <button v-if="$parent.checkPerms('link_write') === true" type="button" class="dmiux_button float-right mb-1" @click="modals.link_hyperlink = true">Add an Attachment</button>
        <br>
        <br>
        <div v-if="links.length == 0" class="alert alert-info"><b>Hiya!</b> This project doesn't have any attachments yet.</div>
        <div v-else class="dmiux_grid-cont_fw dmiux_data-table dmiux_data-table__cont">
            <table id="widget-links-table" class="dmiux_data-table__table">
                <thead>
                    <tr>
                        <th></th>
                        <th>Name</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody id="table_body-hyperlinks">
                    <tr v-for="link in links">
                        <td>
                            <div v-if="$parent.checkPerms('link_write') === true" class="dmiux_data-table__actions">
                                <div class="dmiux_actionswrap dmiux_actionswrap--bin" data-toggle="tooltip" title="Delete attachment" data-original-title="Unlink" @click="unlink_hyperlink(link.id)"></div>
                            </div>
                        </td>
                        <td>
                            <a href="javascript:void(0)" @click="clickLink(link)">{{ link.name }}</a>
                        </td>
                        <td>
                            {{ link.description }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <link-hyperlink :max_size="max_size" :open="modals.link_hyperlink"></link-hyperlink>
    </div>
</script>

<script>
    var modal_add_hyperlink = Vue.component('link-hyperlink', {
        template: '#hyperlink-modal-template',
        props: [ 'max_size', 'open' ],
        data() {
            return {
                type: '',
                url: '',
                name: '',
                desc: '',
                error_msg: '',
                file_name: ''
            }
        },
        watch: {
            open() {
                if(this.open == true) {
                    this.modalOpen();
                    openModal("#modal-link_hyperlink");
                } else {
                    closeModal('#modal-link_hyperlink"');
                }
            }
        },
        computed: {
            truncatedFileName() {
                // Middle-truncate the filename if its length is > 30, return original if not
                if(this.file_name.length <= 30) {
                    return this.file_name;
                }

                return this.file_name.slice(0, 20) + '....' + this.file_name.split('.').pop();
            }
        },
        methods: {
            clearData() {
                this.type = '';
                this.url = '';
                this.name = '';
                this.desc = '';
                this.clearFile();
            },
            clearFile() {
                this.error_msg = '';
                this.file_name = '';
                if (document.getElementById('fileToUpload')){
                    document.getElementById('fileToUpload').value = "";
                }
            },
            onFileChange(e) {
                this.file_name = e.target.files[0].name;
            },
            async uploadFile() {
                this.$root.loading(true);

                let token = await this.getUploadToken();

                if (token == null) {
                    app.ready = true;
                    notify.danger('There was a problem initializing your upload.');
                    return;
                }

                let uploadData = new FormData();
                uploadData.append('upload_token', token);
                uploadData.append('file', document.querySelector('#fileToUpload').files[0]);

                let options = {
                    method: 'POST',
                    body: uploadData
                }

                fetch(`${file_upload_url}/upload`, options)
                    .then(response => {
                        if (response.status != 201) {
                            throw 'Your file could not be uploaded.';
                        }

                        return response.json();
                    })
                    .then(resp => {
                        let transfer_token = resp.transfer_token;

                        let options = FetchHelper.buildJsonRequest({
                            transfer_token: transfer_token
                        });

                        fetch(`/internal-api/v1/studio/projects/${this.$root.project_id}/files`, options)
                            .then(FetchHelper.handleJsonResponse)
                            .then(resp => {
                                this.$root.loading(false);
                                this.$parent.getLinks();
                                this.modalClose();
                            })
                            .catch((err) => {
                                this.$root.loading(false);
                                ResponseHelper.handleErrorMessage(err, 'Your file could not be uploaded.');
                            });
                    })
                    .catch((err) => {
                        this.$root.loading(false);
                        ResponseHelper.handleErrorMessage(err, 'Your file could not be uploaded.');
                    });
            },
            modalOpen() {
                $(document).on("mousedown", "#dmiux_body", this.modalClose);
                $(document).on("keydown", this.modalClose);
            },
            modalClose(event) {
                if(event != undefined) {
                    event.stopPropagation();
                    if(event.key != undefined) {
                        if(event.key != 'Escape') // not escape
                            return;
                    }
                    else {
                        var clicked_element = event.target;
                        if (clicked_element.closest(".dmiux_popup__window")) {
                            // You clicked inside the modal
                            if (clicked_element.id != "x-button" && !(clicked_element.classList.contains("dmiux_popup__cancel")))
                                return;
                        }
                    }
                }

                // You clicked outside the modal
                $(document).off("mousedown", "#dmiux_body", this.modalClose);
                $(document).off("keydown", this.modalClose);

                // This is where your code to clear the file should go
                this.clearData();

                this.$parent.modals.link_hyperlink = false;
            },
            submit() {
                if(this.url == '' || this.name == '') {
                    notify.danger("Please provide the URL and a name.");
                    return;
                }

                let options = FetchHelper.buildJsonRequest({
                    "url": this.url,
                    "type": this.type,
                    "name": this.name,
                    "description": this.desc
                });

                this.$root.loading(true);
                fetch(`/internal-api/v1/studio/projects/${this.$root.project_id}/links`, options)
                    .then(response => {
                        this.$root.loading(false);
                        return response;
                    })
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.$parent.getLinks();
                        this.modalClose();
                    })
                    .catch((error) => {
                        ResponseHelper.handleErrorMessage(error, error.json.message);
                    });
            },
            async getUploadToken() {
                try {
                    let response = await fetch(`/internal-api/v1/uploads`, { method: 'post' });
                    if (! response.ok) {
                        throw new Error('Looks like an invalid response came in.');
                    }
                    json = await response.json();
                    return json.data.token;
                } catch(err) {
                    return null;
                }
            },
        }
    })

    var widget_links = Vue.component('widget-links', {
        template: '#component-widget-links',
        data() {
            return {
                links: [],
                max_size: max_size,
                modals: {
                    link_hyperlink: false
                }
            }
        },
        components: {
            'modal_add_hyperlink': modal_add_hyperlink
        },
        methods: {
            getLinks(show_loader = true) {
                if (show_loader) {
                    this.$root.loading(true);
                }

                fetch(`/internal-api/v1/studio/projects/${this.$root.project_id}/links`)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.links = json.data;
                        $("#widget-links-table").DataTable().destroy();
                    })
                    .then(() => {
                        $("#widget-links-table").DataTable({
                            stateSave: true
                        });
                        if (show_loader) {
                            this.$root.loading(false);
                        }

                        setTimeout(() => {
                            this.getLinks(false);
                        }, 15000);
                    })
                    .catch((error) => {
                        ResponseHelper.handleErrorMessage(error, "Attachments could not be retrieved.");
                        if (show_loader) {
                            this.$root.loading(false);
                        }
                    });
            },
            unlink_hyperlink(id) {
                if(!confirm("Are you sure you want to remove this attachment?")) {
                    return false;
                }

                fetch(`/internal-api/v1/studio/projects/${this.$root.project_id}/links/${id}`, { method: 'delete' })
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.$root.loading(false);
                        notify.success("Link has been deleted.");
                        this.getLinks();
                    })
                    .catch((error) => {
                        this.$root.loading(false);
                        ResponseHelper.handleErrorMessage(error, error.json.message);
                    });
            },
            clickLink(link) {
                if(link.type == 'link') {
                    if(! confirm("This may be a link to a page outside of Bytespree that has not been validated. Do you want to continue?")) {
                        return;
                    }

                    window.open(link.url, '_blank');
                } else {
                    window.open(`${link.url}?download`, '_blank');
                }
            }
        },
        mounted() {
            this.$root.pageLoad();
            this.getLinks();
        }
    });
</script>