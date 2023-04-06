<script type="text/x-template" id="component-widget-activity">
    <div class="dmiux_grid-cont dmiux_grid-cont_fw" id="activity">
        <div v-if="activity.length == 0" class="alert alert-info"><b>Hiya!</b> This project doesn't have any activity yet.</div>
        <template v-else>
            <div v-for="comment in activity">
                <div style="border: 1px solid #e0e0e0; border-radius: 4px" class="p-3 mb-3">
                    <button type="button"
                            @click="openTableInExplorer(comment.table_name, comment.column_name, comment.schema_name)"
                            class="float-right dmiux_button dmiux_button_secondary">
                        Open
                    </button>

                    <h3 class="dmiux_cards__heading">
                        <img class="float-left rounded-circle mr-2" :src="'https://s.gravatar.com/avatar/' + comment.usermd5 + '?s=35'">
                        <a href="javascript:void(0)">{{ comment.full_name }}</a>
                        <!-- Fix until we rework the activity page  -->
                        <template v-if="comment.column_name == ''">published on</template>
                        <template v-else-if="comment.activity_type == 'comment'">commented on</template>
                        <template v-else-if="comment.activity_type == 'flag'">flagged</template> 
                        {{ comment.table_name }}<template v-if="comment.column_name != ''">.{{ comment.column_name }}</template><br />
                        <span class="dmiux_subtitle" style="font-size: .8em;">{{ comment.created_at_formatted }}</span>
                    </h3>
                    <p class="lead">{{ comment.comment_text.substring(0,200) }}</p>
                </div>
            </div>
            <div class="dmiux_grid-row">
                <div class="dmiux_grid-col dmiux_grid-col_auto dmiux_grid-row dataTables_paginate fg-buttonset ui-buttonset fg-buttonset-multi ui-buttonset-multi paging_simple_numbers view-history_mobile m-auto">
                    <a @click="getActivity('page_down')" class="fg-button ui-button ui-state-default previous ui-state-disabled">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 7 11"><path fill="currentColor" d="M0 5.5L5.5588235 0 7 1.425926 2.8823529 5.5 7 9.574074 5.5588235 11z"></path></svg>
                        Back
                    </a>
                    <span class="dataTables_info mr-2">{{ page_details.page }} of {{ page_details.total_pages }} Pages</span>
                    <a @click="getActivity('page_up')" class="fg-button ui-button ui-state-default next ui-state-disabled">
                        Next
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 7 11"><path fill="currentColor" d="M7 5.5L1.4411765 11 0 9.574074 4.1176471 5.5 0 1.425926 1.4411765 0z"></path></svg>
                    </a>
                </div>
                <div class="dmiux_grid-col"></div>
                <div class="dmiux_grid-col dmiux_grid-col_auto pl-0">
                    <div class="dmiux_select">
                        <select @change="getActivity('filter')" v-model="page_details.limit" class="dmiux_select__select">
                            <option value="10">10 Results</option>
                            <option value="25">25 Results</option>
                            <option value="50">50 Results</option>
                            <option value="100">100 Results</option>
                        </select> 
                        <div class="dmiux_select__arrow"></div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</script>

<script>
    var widget_activity = Vue.component('widget-activity', {
        template: '#component-widget-activity',
        data: function() {
            return {
                activity: [],
                page_details: {
                    page: 1,
                    total_pages: 0,
                    limit: 10
                }
            }
        },
        methods: {
            openTableInExplorer: function(table, column, schema) {
                $(".loader").show();

                window.location = '/studio/projects/' + this.$root.project_id + '/tables/' + schema + '/' + table + '/?column=' + encodeURIComponent(column);
            },
            getActivity(action) {
                if(action == "filter") {
                    this.page_details.page = 1;
                }
                else if (action == "page_up") {
                    if(this.page_details.page + 1 > this.page_details.total_pages) {
                        return;
                    }

                    this.page_details.page++;
                } else if(action == "page_down") {
                    if(this.page_details.page - 1 < 1) {
                        return
                    }

                    this.page_details.page--;
                }

                this.$root.loading(true);
                fetch(`/internal-api/v1/studio/projects/${this.$root.project_id}/activity?page=${this.page_details.page}&limit=${this.page_details.limit}`)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.activity = json.data.activity;
                        this.page_details.total_pages = json.data.pages;
                        for(i = 0; i < this.activity.length; i++) 
                        {
                            this.activity[i].created_at_formatted = DateHelper.convertToAndFormatLocaleDateTimeString(this.activity[i].created_at);
                        }
                        this.$root.loading(false);
                    })
                    .catch((error) => {
                        ResponseHelper.handleErrorMessage(error, "Activity could not be retrieved.");
                        this.$root.loading(false);
                    });
            }
        },
        mounted() {
            this.$root.pageLoad();
            this.getActivity();
        }
    });
</script>