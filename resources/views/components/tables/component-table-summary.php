<script type="text/x-template" id="component-table-summary">
    <div :class="view_history.shown ? 'dmiux_grid-col_45 dmiux_grid-col_lg-5' : 'dmiux_grid-col_25 dmiux_grid-col_lg-3'" class="'dmiux_grid-col px-0 pl-lg-2 dmiux_grid-col_md-12 pr-lg-2 pb-md-2 " id="table-summary-wrapper">
        <div class="dmiux_query-summary dmiux_grid-row p-1 m-0 mb-2 mb-lg-0 flex-lg-direction-column overflow-query_summary">
            <div class="dmiux_grid-row dmiux_removed_md m-0 dmiux_grid-col_12" id="table-summary-users">
                <div class="dmiux_grid-col dmiux_grid-col_auto">
                    <div class="pt-2 pb-2 dmiux_grid-row">
                        <template v-for='active_user in active_users'>
                            <div class="dmiux_grid-col dmiux_grid-col_auto tooltip_dmi_tables">
                                <div :data-tooltip="active_user.name">
                                    <img :src="active_user.gravatar" :alt="active_user.name" class="dmiux_editors__photo" />
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
            <template v-if="! view_history.shown">
                <div class="dmiux_grid-row m-0 dmiux_grid-col_lg px-lg-2 flex-lg-column dmiux_grid-col_12" id="table-summary-stats">
                    <div class="dmiux_grid-row">
                        <div class="dmiux_grid-col pl-3" :class="view_mode != 'edit' ? 'dmiux_grid-col_12' : 'dmiux_grid-col_8'">
                            <h3 class="dmiux_query-summary__title mb-2">Table Summary</h3>
                            <div class="dmiux_query-summary__item pl-lg-2 pl-1">
                                <template v-if="pending_count">
                                    <div class="dmiux_query-summary__indented my-2"><i class="fa fa-spin fa-spinner"></i> Fetching Count</div>
                                </template>
                                <template v-else>
                                    <div v-if="type == true" class="dmiux_query-summary__indented mt-lg-2 ml-lg-2 mr-lg-2" :class="view_mode != 'edit' ? 'my-2' : 'view-history_link'"><span class="dmiux_query-summary__record-count mr-1" data-toggle="tooltip" title="This record count is approximate">{{ formatCount(record_counts) }}</span>records <div>This is an estimated count <a href="#" @click="recalculate()">recalculate?</a></div></div>
                                    <div v-else class="dmiux_query-summary__indented mt-lg-2 ml-lg-2 mr-lg-2" :class="view_mode != 'edit' ? 'my-2' : 'view-history_link'"><span class="dmiux_query-summary__record-count mr-1">{{ formatCount(record_counts) }}</span>
                                        <template v-if="record_counts == 1"> record</template>
                                        <template v-else> records</template>
                                    </div>
                                </template>
                            </div>
                        </div>
                        <div v-if="view_mode == 'edit'" class="dmiux_grid-col dmiux_grid-col_4 view-history_link_padding">
                            <a class="view-history-title_text" href="javascript:void(0)" @click="seePreviousVersion()">View History</a>
                        </div>
                    </div>
                    <div id="table-summary-filters" class="dmiux_grid-col_auto flex-grow-1">
                        <div class="dmiux_grid-col dmiux_grid-col_sm-12 pl-1 px-sm-3 px-md-1 dmiux_grid-col_sm-12">
                            <h3 class="dmiux_query-summary__title mb-2">Filters</h3>
                            <div v-if="filters.length == 0 && ! $root.explorer.query.is_grouped" class="dmiux_query-summary__item pl-lg-2 pl-1">
                                <div class="dmiux_query-summary__indented m-lg-2 my-2">No filters set</div>
                            </div>
                            <div v-if="$root.explorer.query.unions.length > 0">
                                <div class="dmiux_query-summary__item pl-lg-2 pl-1">
                                    <div class="dmiux_query-summary__indented m-lg-2 my-2">Unioned with {{ $root.explorer.query.unions.length }} table{{ $root.explorer.query.unions.length > 1 ? 's' : '' }}</div>
                                </div>
                            </div>
                            <div v-if="$root.explorer.query.is_grouped" class="mb-2">
                                <div class="dmiux_grid-row">
                                    <div class="dmiux_grid-col">
                                        <div class="dmiux_query-summary__item hide-x-scroll mb-1 pl-lg-2 pl-md-1">
                                            <button @click="changeGroupBy()" type="button" class="dmiux_query-summary__clear" data-toggle="tooltip" title="Ungroup result set"></button>
                                            <div class="dmiux_query-summary__caption">
                                                Result set grouped
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-for="(filter, index) in filters" class="mb-2">
                                <div class="dmiux_grid-row">
                                    <div class="dmiux_grid-col">
                                        <div class="dmiux_query-summary__item hide-x-scroll mb-1 pl-lg-2 pl-md-1">
                                            <button @click="$root.removeFilter(index)" type="button" class="dmiux_query-summary__clear" data-toggle="tooltip" title="Remove Filter"></button>
                                            <button @click="$root.editFilter(index)" type="button" class="dmiux_actionswrap dmiux_actionswrap--edit-filter" data-toggle="tooltip" title="Edit Filter"></button>
                                            <div class="dmiux_query-summary__caption">
                                                {{ getColumnName(filter) }} {{ filter.operator }} <template v-if="filter.operator !== 'empty' && filter.operator !== 'not empty'">'{{ formatValue(filter.value) }}'</template>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="table-summary-notes" class="dmiux_grid-col_auto flex-grow-1 dmiux_removed_md">
                        <div class="dmiux_grid-col dmiux_grid-col_sm-12 pl-1 px-sm-3 px-md-1 dmiux_grid-col_sm-12">
                            <h3 class="dmiux_query-summary__title mb-0">
                                Table Notes
                                <span v-if="notes.length == 2" class="dmiux_query-summary__indented m-lg-2 my-2 small">
                                    <a href="javascript:void(0)" @click="$root.modals.table_notes = true"> + 1 other note</a>
                                </span>
                                <span v-else-if="notes.length > 1" class="dmiux_query-summary__indented m-lg-2 my-2 small">
                                    <a href="javascript:void(0)" @click="$root.modals.table_notes = true">+ {{ notes.length - 1 }} other notes</a>
                                </span>
                                <span v-else-if="notes.length == 1" class="dmiux_query-summary__indented m-lg-2 my-2 small">
                                    <a href="javascript:void(0)" @click="$root.modals.table_notes = true">Add a Note</a>
                                </span>
                            </h3>
                            <template v-if="$root.table_notes_fetched">
                                <div v-if="notes.length == 0" class="table-summary-notes-wrapper small p-2">
                                    <div>No table notes added yet. <a href="javascript:void(0)" @click="$root.modals.table_notes = true">Add one</a></div>
                                </div>
                                <template v-else>
                                    <div v-for="(note, index) in notes.slice(0, 1)" class="table-summary-notes-wrapper p-2">
                                        <div class="table-summary-notes-content small mx-2">
                                            <div id="table-summary-notes_body" class="table-summary-notes-body" :class="note.is_expanded ? '' : 'table-summary-notes-body-condensed'">{{ note.note }}</div>
                                            <div class="dmiux_grid-row m-0 mt-2 text-primary table-summary-notes-body-actions">
                                                <div class="dmiux_grid-col_6">
                                                    <template v-if="note.is_overflowing">
                                                        <a v-if="! note.is_expanded" href="javascript:void(0)" class="text-primary cursor-p" id="table-summary-notes-body-expand" @click="setNoteExpanded(note.id, true)">Expand</a>
                                                        <a v-else href="javascript:void(0)" class="text-primary cursor-p" id="table-summary-notes-body-collapse" @click="setNoteExpanded(note.id, false)">Collapse</a>
                                                    </template>
                                                </div>
                                                <div class="dmiux_grid-col_6 text-right">
                                                    <a href="javascript:void(0)" class="text-primary cursor-p" @click="editNote(note.id)">Edit</a>
                                                    <a href="javascript:void(0)" class="text-primary cursor-p" @click="deleteNote(note.id)">Delete</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </template>
                        </div>
                    </div>
                </div>
                <div class="dmiux_grid-row m-0 py-2 dmiux_removed_sm px-0 dmiux_grid-col_md-4 align-content-md-start flex-grow-1" id="table-summary-actions">
                    <div v-if="$root.explorer.publisher.id == -1" class="dmiux_grid-row m-0 flex-grow-1">
                        <template v-if="$root.checkPerms('table_write')">
                            <div class="dmiux_actions__col dmiux_grid-col_12 dmiux_main-nav__item mx-0">
                                <button class="dmiux_main-nav__link dmiux_button tbl-summ-btn dmiux-full-width-btn" @click="addSavedQuery()" :title="$root.explorer.saved_query.id == -1 ? 'Store this query as a Saved Query.' : 'Update this Saved Query.'">
                                    <template v-if="$root.explorer.saved_query.id == -1">Save</template><template v-else>Update</template> Query
                                </button>
                            </div>
                        </template>
                        <template v-if="$root.checkPerms('export_data') && view_mode != 'save'">
                            <div class="dmiux_actions__col dmiux_grid-col_12 dmiux_main-nav__item mx-0 mt-2 mt-lg-3 dropdown bytespree-dropdown">
                                <button class="dmiux_main-nav__link dmiux_button tbl-summ-btn dmiux-full-width-btn dropdown-toggle bytespree-dropdown-toggle" data-toggle="dropdown">
                                    Publish to...
                                    <span class="dmiux_menu__arrow">
                                        <svg class="svg-custom-style" viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                    </span>
                                </button>
                                <div class="dmiux_main-nav__dropdown tbl-summ-dropdown dmiux-full-width-btn-dropdown bytespree-dropdown-menu">
                                    <a v-for="destination in publishing_destinations" class="dmiux_main-nav__sublink" @click="publish(destination.id, destination.class_name)" href="javascript:void(0)" :title="'Publish to a ' + destination.name + '.'">{{ destination.name }}</a>
                                </div>
                            </div>
                        </template>
                        <template v-if="($root.checkPerms('table_write', true) == true) && (view_mode == 'edit' || view_mode == 'save')">
                            <div class="dmiux_actions__col dmiux_grid-col_12 dmiux_main-nav__item dropdown bytespree-dropdown mx-0 mt-2 mt-lg-3">
                                <button class="dmiux_main-nav__link dmiux_button tbl-summ-btn dmiux-full-width-btn dropdown-toggle bytespree-dropdown-toggle" data-toggle="dropdown">
                                    Manage View
                                    <span class="dmiux_menu__arrow">
                                        <svg style="margin-left: 5px; margin-top: 5px;" viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                    </span>
                                </button>
                                <div class="dmiux_main-nav__dropdown tbl-summ-dropdown dmiux-full-width-btn-dropdown bytespree-dropdown-menu">
                                        <a class="dmiux_main-nav__sublink" v-if="view_mode == 'edit'" @click="editView()" href="javascript:void(0)" title="Edit this view.">Edit View</a>
                                        <a class="dmiux_main-nav__sublink" v-if="view_mode == 'edit'" href="javascript:void(0)" title="Rename this view." @click="renameView()" >Rename View</a>
                                        <template v-if="($root.checkPerms('refresh_materialized_view', true) == true) && view_mode == 'edit' && view.view_type == 'materialized'">
                                            <a class="dmiux_main-nav__sublink" @click="refreshView()" href="javascript:void(0)" title="Refresh this Materialized View.">Refresh View</a>
                                        </template>
                                        <a class="dmiux_main-nav__sublink" v-if="view_mode == 'save'" @click="publishView()" href="javascript:void(0)" title="Save this view.">Save</a>
                                        <a v-if="view_mode == 'edit'" class="dmiux_main-nav__sublink" @click="deleteView(view.view_type)" href="javascript:void(0)" title="Deletes the current view.">Delete</a>
                                </div>
                            </div>
                            <div v-if="view_mode != 'save'" class="dmiux_actions__col dmiux_grid-col_12 dmiux_main-nav__item mx-0 dmiux_switch-btn-container mt-lg-3 mt-2">
                                <button class="dmiux_main-nav__link dmiux_switch_view_btn dmiux-full-width-btn" @click="switchView()">
                                    <span v-if="$root.explorer.view.view_type == 'materialized'">
                                        Switch To Normal
                                    </span>
                                    <span v-else>
                                        Switch To Materialized
                                    </span>
                                </button>
                            </div>
                        </template>
                    </div>
                    <div v-else class="dmiux_grid-row m-0">
                        <template v-if="$root.checkPerms('export_data') && $root.table_exists">
                            <div class="dmiux_actions__col dmiux_grid-col_12 dmiux_main-nav__item mx-0">
                                <button class="dmiux_main-nav__link dmiux_button tbl-summ-btn dmiux-full-width-btn" @click="publish($root.explorer.publisher.destination.id, $root.explorer.publisher.destination.class_name)">Update Publisher</button>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
            <template v-else>
                <div class="dmiux_grid-row view-history view-history-filter view-history_mobile">
                    <div class="dmiux_grid-col dmiux_grid-col_6">
                        <p class="view-history-content_title mb-0">View History</p>
                    </div>
                    <div class="dmiux_grid-col dmiux_grid-col_6">
                        <a @click="showSummary()" class="view-history-content_text float-right" href="#">Go Back</a>
                    </div>
                </div>
                <div class="dmiux_grid-row view-history-filter view-history_mobile">
                    <div class="dmiux_grid-col dmiux_grid-col_12">
                        <hr class="mt-1 mb-2" />
                    </div>
                </div>
                <div class="dmiux_grid-row view-history-filter view-history_mobile">
                    <div class="dmiux_grid-col dmiux_grid-col_12">
                        <p class="view-history-content_subtitle mb-0">Current Version</p>
                    </div>
                </div>
                <div class="view-history-filter view-history_row mt-2 w-100">
                    <img class="float-left table-summary-notes-photo" :src="view.profile_picture" :alt="view.user.name">
                    <div class="view-history_card small p-2 pb-1">
                        <div class="dmiux_grid-row mb-0">
                            <div class="dmiux_grid-col dmiux_grid-col_auto view-history-overflow_text pl-0 pr-1">
                                <b>{{ view.user.name }}</b>
                            </div>
                            <div class="dmiux_grid-col dmiux_grid-col_auto pl-0">
                                changed this view <span class="view-history-date_text"> - {{ formatAge(view.updated_at) }} &#8226; {{ view.view_type.charAt(0).toUpperCase() + view.view_type.slice(1) }}</span>
                            </div>
                        </div>
                        <div v-if="view.view_message != ''" class="dmiux_grid-row">
                            <div class="dmiux_grid-col_12">
                                <p class="view-history-content_text view-history-message_box pt-1 pb-1 pr-1 pl-0 mb-2">{{ view.view_message }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-if="view_history.records.length > 0" class="dmiux_grid-row view-history-filter view-history_mobile">
                    <div class="dmiux_grid-col dmiux_grid-col_12">
                        <p class="view-history-content_subtitle mb-0">Previous Versions</p>
                    </div>
                </div>
                <div v-if="view_history.records.length > 0" class="view-history-filter view-history_row mt-2 w-100">
                    <template v-for="history in view_history.records">
                        <img class="float-left table-summary-notes-photo" :src="history.profile_picture" :alt="history.user.name">
                        <div class="view-history_card small p-2 pb-1">
                            <div class="dmiux_grid-row mb-0">
                                <div class="dmiux_grid-col dmiux_grid-col_auto view-history-overflow_text pl-0 pr-1">
                                    <b>{{ history.user.name }}</b>
                                </div>
                                <div class="dmiux_grid-col dmiux_grid-col_auto pl-0">
                                    changed this view <span class="view-history-date_text"> - {{ formatAge(history.created_at) }} &#8226; {{ history.view_type.charAt(0).toUpperCase() + history.view_type.slice(1) }}</span>
                                </div>
                            </div>
                            <div v-if="history.view_message != ''" class="dmiux_grid-row">
                                <div class="dmiux_grid-col_12">
                                    <p class="view-history-content_text view-history-message_box pt-1 pb-1 pr-1 pl-0 mb-2">{{ history.view_message }}</p>
                                </div>
                            </div>
                            <div class="dmiux_grid-row table-summary-notes-body-actions_project">
                                <div class="dmiux_grid-col_12">
                                    <a @click="previewView(history.id)" class="view-history-content_text view-history-link mr-1" href="#"><span class="fas fa-eye"></span> View</a>
                                    <a @click="restoreView(history.id, history.view_type, history.view_message)" class="view-history-content_text view-history-link" href="#"><span class="fas fa-undo"></span> Restore</a>
                                </div>
                            </div>
                        </div>
                    </template>
                    <div v-if="view_history.records.length == 0" class="alert alert-warning mt-2 view-history_alert">
                        No history was found
                    </div>
                    <div v-else-if="view_history.total_pages > 1" class="dmiux_grid-row dataTables_paginate fg-buttonset ui-buttonset fg-buttonset-multi ui-buttonset-multi paging_simple_numbers view-history_mobile w-100 m-auto">
                        <div class="dmiux_grid-col_12 ml-2 mt-2">
                            <a @click="seePreviousVersion('page_down')" class="fg-button ui-button ui-state-default previous ui-state-disabled">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 7 11"><path fill="currentColor" d="M0 5.5L5.5588235 0 7 1.425926 2.8823529 5.5 7 9.574074 5.5588235 11z"></path></svg>
                                Back
                            </a>
                            <a @click="seePreviousVersion('page_up')" class="fg-button ui-button ui-state-default next ui-state-disabled">
                                Next
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 7 11"><path fill="currentColor" d="M7 5.5L1.4411765 11 0 9.574074 4.1176471 5.5 0 1.425926 1.4411765 0z"></path></svg>
                            </a>
                        </div>
                        <div class="dmiux_grid-col_12 ml-2">
                            <span class="dataTables_info">{{ view_history.page }} of {{ view_history.total_pages }} {{ pluralize(view_history.total_pages, 'Page', 's') }}</span>
                        </div>
                    </div>
                </div>
            </template>

            <publish-view :open="modals.publish_view"
                          :view_mode="view_mode"
                          :name="dview.view_name"
                          :type="dview.view_type"
                          :schedule="dview.view_definition.view_schedule"
                          :rename_view_name="rename_view_name">
            </publish-view>

            <publish-sftp :open="modals.publish_sftp"
                          :table="table"
                          :destination_id="destination_id"
                          :class_name="destination_class_name">
            </publish-sftp>
            <publish-mssql :open="modals.publish_mssql"
                          :table="table"
                          :destination_id="destination_id"
                          :class_name="destination_class_name">
            </publish-mssql>

            <publish-snapshot :open="modals.publish_snapshot"
                          :table="table"
                          :destination_id="destination_id"
                          :class_name="destination_class_name">
            </publish-snapshot>

            <publish-csv :open="modals.publish_csv"
                          :table="table"
                          :destination_id="destination_id"
                          :class_name="destination_class_name">
            </publish-csv>

            <saved-queries :open="modals.saved_queries"
                           :table="table">
            </saved-queries>

            <switch-view :open="modals.switch_view"
                          :view_mode="view_mode"
                          :name="dview.view_name"
                          :type="dview.view_type"
                          :switch_view_type="dview.switch_view_type"
                          :view_detail="view">
            </switch-view>
        </div>
    </div>
</script>