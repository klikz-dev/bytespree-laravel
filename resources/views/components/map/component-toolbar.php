<script type="text/x-template" id="component-toolbar">
    <div class="dmiux_headline toolbar_holder" style="margin-bottom: 10px;">
        <div class="dmiux_grid-row dmiux_grid-row_nog dmiux_grid-row_aic">
            <?php echo view("components/breadcrumbs"); ?>
            <div v-if="buttons.length > 0" class="dmiux_buttons_wrapper">
                <button v-for="button in buttons"
                        class="dmiux_btn"
                        v-bind:class="button.class"
                        v-bind:data-target="button.target"
                        v-bind:data-toggle="button.toggle"
                        type="button">
                        {{ button.text }}
                </button>
            </div>
        </div>
    </div>
</script>
