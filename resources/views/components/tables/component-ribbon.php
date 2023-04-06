<script type="text/x-template" id="component-ribbon">
    <div>
        <div class="dmiux_headline p-0 mb-0" role="group">
            <div class="dmiux_grid-row dmiux_grid-row_jcc dmiux_toolbar">

                <div v-if="$root.isSelectedColumnAggregate() == false && $parent.checkPerms('flag_write') && view_mode != 'save' && $root.explorer.publisher.id == -1"
                     @click="flags[table + '_' + selected_column] ? removeFlag() : addFlag()"
                     class="dmiux_grid-col dmiux_grid-col_auto">
                    <button type="button"
                            title="Toggle flag"
                            class="dmiux_toolbar_item"
                            data-tooltip="Flag/Unflag">
                        <img src="/assets/images/icons/icons8-flag-filled-50.png"
                             class="dmiux_toolbar_item_icon"
                             draggable="false" />
                    </button>
                </div>

                <div v-if="$root.isSelectedColumnAggregate() == false && $parent.checkPerms('comment_write') && view_mode != 'save' && $root.explorer.publisher.id == -1"
                     @click="addComment();"
                     class="dmiux_grid-col dmiux_grid-col_auto">  
                    <button type="button"
                            title="View and add comments"
                            class="dmiux_toolbar_item"
                            data-tooltip="Comments">
                        <img src="/assets/images/icons/icons8-chat-bubble-50.png"
                             class="dmiux_toolbar_item_icon"
                             draggable="false" />
                    </button>
                </div>

                <div v-if="$root.isSelectedColumnAggregate() == false && $parent.checkPerms('map_write') && $parent.destination_schema_id > 0 && view_mode != 'save' && $root.explorer.publisher.id == -1"
                     class="dmiux_grid-col dmiux_grid-col_auto dmiux_main-nav__item">  
                    <button :class="mobile ? 'mobile-menu-button' : 'menu-button'"
                            type="button"
                            title="View and manage mapping instructions"
                            class="dmiux_toolbar_item"
                            data-tooltip="Map Field">
                        <img src="/assets/images/icons/icons8-marker-50.png"
                             class="dmiux_toolbar_item_icon"
                             draggable="false" />
                    </button>
                    <div class="dmiux_main-nav__dropdown">
                        <a class="dmiux_main-nav__sublink"
                           @click="addMapping(-1);"
                           href="JavaScript:void(0)">
                            Add Table &amp; Column Mapping
                        </a>
                        <a class="dmiux_main-nav__sublink"
                           v-for="module in modules"
                           @click="addMapping(module.id);"
                           href="JavaScript:void(0)">
                            Add {{ module.name }} Mapping
                        </a>
                        <a class="dmiux_main-nav__sublink divider" role="separator"></a>
                        <a class="dmiux_main-nav__sublink" @click="viewAllMappings();" href="JavaScript:void(0)">View All Mappings</a>
                    </div>
                </div>

                <div v-if="$root.isSelectedColumnAggregate() == false && $parent.checkPerms('comment_write') && view_mode != 'save' && $root.explorer.publisher.id == -1"
                     @click="$root.modals.add_attachment = true"
                     class="dmiux_grid-col dmiux_grid-col_auto">  
                    <button type="button"
                            title="View and manage attachments"
                            data-tooltip="Attachments"
                            class="dmiux_toolbar_item">
                        <img src="/assets/images/icons/icons8-attach-50.png"
                             class="dmiux_toolbar_item_icon"
                             draggable="false" />
                    </button>
                </div>

                <div v-if="$root.canAddTransformationToColumn()"
                     @click="addTransformation();"
                     class="dmiux_grid-col dmiux_grid-col_auto">  
                    <button type="button"
                            title="View and manage transformations"
                            data-tooltip="Transformation"
                            class="dmiux_toolbar_item">
                        <img src="/assets/images/icons/icons8-metamorphose-80.png"
                             class="dmiux_toolbar_item_icon"
                             draggable="false" />
                    </button>
                </div>

                <div v-if="selected_column_unstructured == true && $root.isSelectedColumnAggregate() == false" @click="copyColumn();"
                     class="dmiux_grid-col dmiux_grid-col_auto">  
                    <button type="button"
                            title="Copy selected column to a new column"
                            data-tooltip="CopyColumn"
                            class="dmiux_toolbar_item">
                        <img src="/assets/images/icons/icons8-copy-50.png"
                             class="dmiux_toolbar_item_icon"
                             draggable="false" />
                    </button>
                </div>

                <div v-if="$root.isSelectedColumnAggregate() == false || ($root.isSelectedColumnAggregate() && $root.explorer.query.is_grouped == true)"
                     :class="mobile ? 'tooltip_dmi' : ''"
                     class="dmiux_grid-col dmiux_grid-col_auto dmiux_main-nav__item">  
                    <button :class="mobile ? 'mobile-menu-button' : 'menu-button'"
                            class="dmiux_toolbar_item"
                            type="button"
                            title="Get counts of most popular values">
                        <img src="/assets/images/icons/icons8-counter-50.png"
                             class="dmiux_toolbar_item_icon"
                             draggable="false" />
                    </button>
                    <div class="dmiux_main-nav__dropdown">
                        <a class="dmiux_main-nav__sublink" @click="$root.modals.counts = 25" href="JavaScript:void(0)">Get top 25</a>
                        <a class="dmiux_main-nav__sublink" @click="$root.modals.counts = 100" href="JavaScript:void(0)">Get top 100</a>
                        <a class="dmiux_main-nav__sublink" @click="$root.modals.counts = 250" href="JavaScript:void(0)">Get top 250</a>
                    </div>
                </div>

                <div v-if="$root.isSelectedColumnAggregate() == false || ($root.isSelectedColumnAggregate() && $root.explorer.query.is_grouped == true)"
                     @click="$root.modals.longest = true"
                     class="dmiux_grid-col dmiux_grid-col_auto">  
                    <button type="button"
                            title="Get the ten longest entries in the selected column"
                            data-tooltip="Get Longest"
                            class="dmiux_toolbar_item">
                        <img src="/assets/images/icons/icons8-ruler-50.png"
                             class="dmiux_toolbar_item_icon"
                             draggable="false" />
                    </button>
                </div>

                <div v-if="$root.isSelectedColumnAggregate() == false || ($root.isSelectedColumnAggregate() && $root.explorer.query.is_grouped == true)"
                     @click="addCustomFilter();"
                     class="dmiux_grid-col dmiux_grid-col_auto">  
                    <button aria-haspopup="true"
                            aria-expanded="false"
                            title="Apply a filter"
                            class="dmiux_toolbar_item">
                        <img src="/assets/images/icons/icons8-filter-50.png"
                             class="dmiux_toolbar_item_icon"
                             draggable="false" />
                    </button>
                </div>

                <div v-if="$root.isSelectedColumnAggregate() && $root.explorer.query.is_grouped == false"
                     class="dmiux_grid-col dmiux_grid-col_auto dmiux_query-summary__item pl-1 mt-1 mb-1 mute_text">  
                     You selected an aggregate column.  You must group results to use these functions.
                </div>

            </div>
        </div>
    </div>
</script>