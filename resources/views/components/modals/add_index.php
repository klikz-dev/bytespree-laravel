<script type="text/x-template" id="index-modal-template">	
	<div class="dmiux_popup" id="modal-add_index" role="dialog" tabindex="-1">
		<div class="dmiux_popup__window" role="document">
			<div class="dmiux_popup__head">
				<h4 class="dmiux_popup__title">Add Index</h4>
				<button type="button" class="dmiux_popup__close"></button>
			</div>
            <div class="dmiux_popup__cont">
                <label for="input-selected_column">Indexed Column</label>
                <div class="dmiux_select">
                    <select id="input-selected_column" class="dmiux_select__select" v-model="selected_column">
                        <option v-for="column in columns" v-if="!indexes.includes(column.column_name)" :value="column.column_name">{{ column.column_name }}</option>
                    </select>
                    <div class="dmiux_select__arrow"></div>
                </div>
			</div>
			<div class="dmiux_popup__foot">
				<div class="dmiux_grid-row">
                    <div class="dmiux_grid-col"></div>
                    <div class="dmiux_grid-col dmiux_grid-col_auto">
                        <button class="dmiux_button dmiux_button_secondary dmiux_popup__close_popup" type="button">Cancel</button>
                    </div>
                    <div class="dmiux_grid-col dmiux_grid-col_auto pl-0">
						<button class="dmiux_button" type="button" @click="add();">Add</button>
                    </div>
                </div>
			</div>
		</div>
	</div>
</script>

<script>
    var addIndex = {
        template: '#index-modal-template',
        props: [ 'control_id' ],
        data() {
            return {
                selected_column: "",
                columns: [],
                indexes: []
            }
        },
        methods: {
            add() {
                if(this.selected_column == "") {
                    notify.danger("Please select a column");
                    return;
                }
                
                let options = FetchHelper.buildJsonRequest({
                    table: this.$root.$refs.databaseManager.tableName,
                    column: btoa(this.selected_column)
                });
                
                this.$root.loading(true);
                fetch(`/internal-api/v1/data-lakes/${this.control_id}/tables/index`, options)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.$root.loading(false);
                        notify.success("Your index is being created but may take a while.");
                        this.indexes.push(this.selected_column);
                        this.selected_column = "";
                        setTimeout(this.checkTask, 15000);
                        closeModal('#modal-add_index');
                    })
                    .catch((error) => {
                        this.$root.loading(false);
                        ResponseHelper.handleErrorMessage(error, "Unable to add index at this time.");
                    });
            },
            checkTask() {
                fetch(`/internal-api/v1/jenkins/check/addIndex`)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        if(json.data == "FAILURE") {
                            notify.danger("Your add index job has failed.")
                        } else if(json.data != 'SUCCESS') {
                            setTimeout(this.checkTask, 15000);
                        } else {
                            this.$root.getTables(true);
                        }

                        this.$root.loading(false);
                    });
            }
        }
    }
</script>