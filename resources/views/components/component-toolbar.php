<script type="text/x-template" id="component-toolbar">
    <div class="dmiux_headline toolbar_holder" style="margin-bottom: 10px;">
        <div class="dmiux_grid-row dmiux_grid-row_nog dmiux_grid-row_aic">
            <?php echo view("components/breadcrumbs"); ?>
            <div class="dmiux_grid-col dmiux_grid-col_md-12">
                <div class="dmiux_mt100"></div>
            </div>
            <div class="dmiux_grid-col dmiux_grid-col_auto">
                <div v-if="buttons.length > 0" class="dmiux_actions">
                    <div class="dmiux_actions__row dmiux_grid-row">
                        <button v-for="button in buttons"
                                class="dmiux_btn"
                                v-bind:class="button.class"
                                v-bind:data-target="button.target"
                                v-bind:data-toggle="button.toggle"
                                v-bind:onclick="button.onclick"
                                type="button">
                            <span v-html="button.text"></span>
                        </button>
                    </div>
                </div>
                <div v-else class="dmiux_actions">
                    
                </div>
            </div>
        </div>
    </div>
</script>
