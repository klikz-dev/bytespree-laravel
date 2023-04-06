<script type="text/x-template" id="column-modal-template">
    <!-- Column Modal -->
    <div class="dmiux_popup" id="modal-column" role="dialog" tabindex="-1">
        <div class="dmiux_popup__window dmiux_popup__window_lg" role="document">
            <div class="dmiux_popup__head">
                <h4 class="dmiux_popup__title">Search Columns</h4>
                <button type="button" class="dmiux_popup__close"></button>
            </div>
            <form id="form-search_columns" autocomplete="off" @submit="searchColumns($event);">
                <div class="dmiux_popup__cont">
                    <label for="column-name">Column name <span class="text-danger">*</span></label>
                    <div style="display: flex; align-items: stretch !important;" class="dmiux_buttons_wrapper dmiux_input">
                        <input v-model="term" style="grid-row: 1; height: 32px;" id="column-name" class="dmiux_input__input" />
                        <button style="margin-left: 5px; margin-top: 0px;" class="dmiux_button" @click="searchColumns($event)" type="button">Search</button>
                    </div>
                    <small>You can use %searchTerm% if you'd like to search for column names that contain a certain substring. E.g.: %old% would return "told" or "behold"</small>
                    <div class="dmiux_data-table dmiux_data-table__cont" v-if="columns.length > 0">
                        <table class="dmiux_data-table__table">
                            <thead>
                                <tr>
                                    <th>Table Name</th>
                                    <th>Column Name</th>
                                    <th>Sample 1</th>
                                    <th>Sample 2</th>
                                    <th>Sample 3</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(val, index) in columns">
                                    <td><a :href="'/studio/projects/' + $root.project_id + '/tables/' + val['table_schema'] + '/' + val['table_name'] + '/?column=' + encodeURI(val['column_name'])">{{ val['table_name'] }}</a></td>
                                    <td><a :href="'/studio/projects/' + $root.project_id + '/tables/' + val['table_schema'] + '/' + val['table_name'] + '/?column=' + encodeURI(val['column_name'])">{{ val['column_name'] }}</a></td>
                                    <td>{{ val['1'] }}</td>
                                    <td>{{ val['2'] }}</td>
                                    <td>{{ val['3'] }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-if="not_found == true" class="alert alert-warning">
                        We did not find any results for the column name you provided.
                    </div>
                </div>
                <div class="dmiux_popup__foot">
                    <div class="dmiux_grid-row">
                        <div class="dmiux_grid-col"></div>
                        <div class="dmiux_grid-col dmiux_grid-col_auto">
                            <button class="dmiux_button dmiux_button_secondary dmiux_popup__close_popup" type="button">Cancel</button>
                        </div>
                    </div>    
                </div>
            </form>
        </div>
    </div>
</script>
<script>
    var column_search = Vue.component('column-search', {
        template: '#column-modal-template',
        props: [ 'columns', 'not_found' ],
        data() {
            return {
                term: ''
            }
        },
        methods: {
            searchColumns: function(e) {
                e.preventDefault();
                if(!this.term) {
                    notify.danger("A search term is required.");
                    return;
                }

                $(".loader").show();
                fetch(`/internal-api/v1/studio/projects/${this.$root.project_id}/search-columns?term=${btoa(this.term)}`)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        $(".loader").hide();
                        if(json.data.found == false) {
                            this.$root.not_found = true;
                            this.$root.columns = [];
                            return;
                        }
                        this.$root.not_found = false;
                        this.$root.columns = json.data.columns;
                    })
                    .catch((error) => {
                        $(".loader").hide();
                        ResponseHelper.handleErrorMessage(error, "There was a problem searching for columns.");
                    });
            },
            btoa: function(str) {
                return btoa(str);
            }
        }
    });
</script>