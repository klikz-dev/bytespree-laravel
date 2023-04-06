<?php echo view("components/head"); ?>
<?php echo view("components/map/component-toolbar"); ?>
<?php echo view("components/map/component-map-table"); ?>
<div id="app">
    <toolbar :buttons="toolbar.buttons"
             :breadcrumbs="toolbar.breadcrumbs">
    </toolbar>
    <div class="dmiux_content">
        <div class="dmiux_table_container">
            <map-table :mappings="mappings" :selected_table="selected_table" :date="date" :project_id="project_id" :column_headers="column_headers"></map-table>
        </div>
    </div>
</div>
<script>
    var project_id = <?php echo $project_id; ?>;
    var table = <?php echo "'" . $table . "'"; ?>;
    var schema = <?php echo "'" . $schema . "'"; ?>;
</script>
<script src="/assets/js/map.js?#{release}#"></script>
<?php echo view("components/foot"); ?>