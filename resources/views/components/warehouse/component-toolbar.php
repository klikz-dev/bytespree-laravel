<script type="text/x-template" id="component-toolbar">
    <div class="dmiux_headline toolbar_holder" style="margin-bottom: 10px;">
        <div class="dmiux_grid-row dmiux_grid-row_nog dmiux_grid-row_aic">
            <?php echo view("components/breadcrumbs"); ?>
            <div class="dmiux_grid-col dmiux_grid-col_md-12">
                <div class="dmiux_mt100"></div>
            </div>
            <div class="dmiux_grid-col dmiux_grid-col_auto">
                <div v-if="buttons.length > 0" class="dmiux_actions">
                    <button v-for="button in buttons"
                            class="dmiux_btn"
                            v-bind:class="button.class"
                            v-bind:data-target="button.target"
                            v-bind:data-toggle="button.toggle"
                            type="button">
                            {{ button.text }}
                    </button>
                </div>
                <div v-else-if="$root.database_count > 0" class="dmiux_actions">
                    <div class="dmiux_actions__row dmiux_grid-row">
                    <div :title="servers.length == 0 ? 'You must set up a server first.' : ''"
                             class="dmiux_actions__col dmiux_grid-col dmiux_grid-col_auto">
                            <nav class="dmiux_database-nav" v-if="servers.length > 0">
                                <div class="text-center">
                                    <button v-if="$parent.currentUser.is_admin == true || $parent.checkUserPerms('datalake_create') == true"
                                            type="button"
                                            class="dmiux_button dmiux_database-nav__toggle">
                                            New Database&nbsp;&nbsp;
                                            <span class="fas fa-chevron-down"></span>
                                    </button>
                                </div>
                                <div class="dmiux_database-nav__dropdown">
                                    <div class="dmiux_database-row dmiux_database-border-bottom" @click="create_empty_database();">
                                        <div class="dmiux_database-column dmiux_database-column-left" >
                                            <img src="<?php echo getenv('DMIUX_URL') ?>/img/empty-db.svg"
                                            class="text-center dmiux_database-column-empty-db" />
                                        </div>
                                        <div class="dmiux_database-column dmiux_database-column-right">
                                            <p class="dmiux_database-text-heading">Create an empty database</p>
                                            <p>You’ll be able to upload CSV files and other data</p>
                                        </div>
                                    </div>
                                    <div class="dmiux_database-row" @click="add_integration()">
                                        <div class="dmiux_database-column dmiux_database-column-left">
                                            <img src="<?php echo getenv('DMIUX_URL') ?>/img/connector-db.svg"
                                            class="text-center dmiux_database-column-connector" />
                                        </div>
                                        <div class="dmiux_database-column dmiux_database-column-right">
                                            <p class="dmiux_database-text-heading">Use a data connector</p>
                                            <p>Download data from cloud APIs and other database systems</p>
                                        </div>
                                    </div>
                                </div>
                            </nav>
                            <div class="text-center" v-else :disabled="servers.length == 0">
                                <button v-if="$parent.currentUser.is_admin == true || $parent.checkUserPerms('datalake_create') == true"
                                        type="button"
                                        :disabled="servers.length == 0"
                                        class="dmiux_button dmiux_database-nav__toggle">
                                        New Database&nbsp;&nbsp;
                                        <span class="fas fa-chevron-down"></span>
                                </button>
                            </div>
                        </div>
                        <div class="dmiux_actions__col dmiux_grid-col dmiux_grid-col_auto">
                            <button @click="compare()" type="button"
                                    class="dmiux_button dmiux_button_secondary d-none d-sm-block">
                                    Compare&nbsp;&nbsp;
                                    <span class="fas fa-eye"></span>
                            </button>
                        </div>
                        <div class="dmiux_actions__col dmiux_grid-col dmiux_grid-col_auto">
                            <div class="dmiux_actions__sep"></div>
                        </div>
                        <div  v-if="tags.length > 0" class="dmiux_actions__col dmiux_grid-col dmiux_grid-col_auto dmiux_select">
                            <select style="grid-row: 1" class="dmiux_select__select" v-model="filter">
                                <option v-if="filter == null" :value='null'>Filter databases</option>
                                <option v-else :value='null'>Show all databases</option>
                                <option v-for="tag in tags" :value="tag.id" >{{ tag.name }} ({{ $parent.tag_database_total(tag.id) }})</option>
                            </select>
                            <div class="dmiux_select__arrow"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>