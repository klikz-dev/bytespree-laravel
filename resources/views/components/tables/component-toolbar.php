<script type="text/x-template" id="component-toolbar">
    <div class="studio-heading-style">
        <div v-if="!mobile" class="dmiux_headline" style="margin-bottom: 0px;">
            <div class="dmiux_grid-row dmiux_grid-row_nog dmiux_grid-row_aic">
                <?php echo view("components/breadcrumbs"); ?>
                <div class="dmiux_grid-col dmiux_grid-col_md-12" v-if="$root.explorer.selected_view != undefined && ['View', 'Materialized View'].includes($root.explorer.selected_view.table_type)" >
                    <div class="p-0 mb-0" role="group">
                        <h3 class="dmiux_title">&nbsp;({{$root.explorer.selected_view.table_type}})</h3>
                    </div>
                </div>
                <div class="dmiux_grid-col dmiux_grid-col_md-12">
                    <div class="p-0 mb-0" role="group">
                        <?php echo view("components/tables/toolbar-buttons"); ?>
                    </div>
                </div>
            </div>
        </div>
        <div v-else>
            <div class="dmiux_headline p-2" style="margin-bottom: 0px;">
                <div class="dmiux_grid-row dmiux_grid-row_nog dmiux_grid-row_aic">
                    <?php echo view("components/breadcrumbs"); ?>
                    <h3 class="dmiux_title" v-if="$root.explorer.selected_view != undefined && ['View', 'Materialized View'].includes($root.explorer.selected_view.table_type)">&nbsp;({{$root.explorer.selected_view.table_type}})</h3>
                </div>
            </div>
            <div class="dmiux_headline p-0 mb-0" role="group">
                <?php echo view("components/tables/toolbar-buttons"); ?>
            </div>
        </div>
    </div>
</script>