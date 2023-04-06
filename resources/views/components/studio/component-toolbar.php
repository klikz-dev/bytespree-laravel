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
                <div v-else class="dmiux_actions">
                    <div class="dmiux_actions__row dmiux_grid-row">
                        <div class="dmiux_actions__col dmiux_grid-col dmiux_grid-col_auto">
                            <button v-if="($parent.databases.user_databases.length > 0) && ($parent.projects.length > 0 || $root.tag != '') && ($parent.currentUser.is_admin == true || $parent.checkUserPerms('studio_create') == true)"
                                    @click="createProject()" type="button"
                                    class="dmiux_button">
                                    Create Project&nbsp;&nbsp;
                                    <span class="fas fa-plus"></span>
                            </button>
                        </div>
                        <div class="dmiux_actions__col dmiux_grid-col dmiux_grid-col_auto">
                            <div class="dmiux_actions__sep"></div>
                        </div>
                        <div v-if="tags.length > 0" class="dmiux_actions__col dmiux_grid-col dmiux_grid-col_auto dmiux_select">
                            <select style="grid-row: 1" class="dmiux_select__select" @change="filterProjects($event)" id="select-tag_list">
                                <option value="">Choose a tag</option>
                                <option v-for="tag in tags" :value="tag.id" >{{ tag.name }} ({{ $parent.tag_project_total(tag.id) }})</option>
                            </select>
                            <div class="dmiux_select__arrow"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>

