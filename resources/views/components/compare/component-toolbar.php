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
                            :class="button.class"
                            :data-target="button.target"
                            :data-toggle="button.toggle"
                            type="button">
                            {{ button.text }}
                    </button>
                </div>
                <div v-else class="dmiux_actions">
                    <div class="dmiux_actions__row dmiux_grid-row">
                        <div class="dmiux_actions__col dmiux_grid-col dmiux_grid-col_auto">
                            <button @click="exportToExcel()" type="button"
                                    id="export-btn" 
                                    class="dmiux_button dmiux_button_secondary hidden">
                                    Export to CSV&nbsp;&nbsp;
                                    <span class="fas fa-download"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>