<script type="text/x-template" id="add-database-modal-template">
    <div class="dmiux_popup" id="modal-add_foreign_database">
        <div class="dmiux_popup__window" role="document">
            <div class="dmiux_popup__head">
                <h4 class="dmiux_popup__title">Connect a Database</h4>
                <button id="x-button" type="button" class="dmiux_popup__close"></button>
            </div>
            <form id="form-add_foreign_database" autocomplete="off" onsubmit="event.preventDefault()">
                <div class="dmiux_popup__cont">
                    <label for="schema" class="dmiux_popup__label">Schema Name</label>
                    <div class="dmiux_input mt-2">
                        <input type="text" id="schema" @input="cleanupName()" v-model="schema_name" value="" class="dmiux_input__input">
                    </div>
                    <label for="databases" class="dmiux_popup__label">Database</label>
                    <div class="dmiux_select mt-2">
                        <select id="databases" class="dmiux_select__select" v-model="foreign_database_id">
                            <option value="0">Choose a Database</option>
                            <option v-for="database in databases" :value="database.id">{{ database.database }}</option>
                        </select>
                        <div class="dmiux_select__arrow"></div>
                    </div>
                </div>
                <div class="dmiux_popup__foot">
                    <div class="dmiux_grid-row">
                        <div class="dmiux_grid-col"></div>
                        <div class="dmiux_grid-col dmiux_grid-col_auto">
                            <button id="cancel-button-add-foreign-database" class="dmiux_button dmiux_button_secondary dmiux_popup__close_popup dmiux_popup__cancel" type="button">Cancel</button>
                        </div>
                        <div class="dmiux_grid-col dmiux_grid-col_auto">
                            <button class="dmiux_button" type="button" @click="connect();">Connect</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</script>

<script>
    var addDatabase = {
        template: '#add-database-modal-template',
        props: [ 'control_id' ],
        data() {
            return {
                foreign_database_id: 0,
                schema_name: "",
                databases: []
            }
        },
        methods: {
            cleanupName: function () {
                var firstChar = this.schema_name.charAt(0);
                var regex = new RegExp(/[a-z]/i);
                while(regex.test(firstChar) == false && this.schema_name.length > 0) {
                    this.schema_name = this.schema_name.replace(firstChar, '');
                    firstChar = this.schema_name.charAt(0);
                }
                this.schema_name = this.schema_name.toLowerCase();
                this.schema_name = this.schema_name.trim();
                this.schema_name = this.schema_name.replace(" ", "");
                this.schema_name = this.schema_name.replace(/\W/g, '');
                this.schema_name = this.schema_name.substring(0, 63);
            },
            connect() {
                if(this.foreign_database_id == 0 || this.schema_name == "") {
                    notify.danger("Please enter in all required values.");
                    return;
                }
                this.$root.loading(true);

                let options = FetchHelper.buildJsonRequest({
                    "schema_name": this.schema_name,
                    "foreign_database_id": this.foreign_database_id
                });
                
                fetch(`/internal-api/v1/data-lakes/${this.control_id}/foreign-databases`, options)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.$root.loading(false);
                        notify.success("Database connected");
                        this.$root.getForeignTables();
                        this.getDatabases();
                        this.closeConnectModal();
                    })
                    .catch((error) => {
                        this.$root.loading(false);
                        ResponseHelper.handleErrorMessage(error, 'Foreign database could not be created.');
                    });
            },
            closeConnectModal(event = null) {
                if(event) {
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

                $(document).off("mousedown", "#dmiux_body", this.closeConnectModal);
                $(document).on('keydown', closeModalOnEscape);
                $(document).off("keydown", this.closeConnectModal);
                this.schema_name = "";
                this.foreign_database_id = 0;
                closeModal("#modal-add_foreign_database");
            },
            getDatabases() {
                this.$root.loading(true);

                fetch(`/internal-api/v1/data-lakes/${this.control_id}/foreign-databases/unused`)
                    .then(response => response.json())
                    .then(json => {
                        this.$root.loading(false);
                        if (json.status == "error") {
                            notify.danger(json.message);
                        }
                        else {
                            this.databases = json.data
                        }
                    });
            }
        },
        mounted() {
            this.getDatabases();
        }
    }
</script>