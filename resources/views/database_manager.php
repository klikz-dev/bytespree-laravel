<?php echo view("components/head"); ?>
<?php echo view("components/database_manager/component-database-manager"); ?>
<?php echo view("components/modals/add_table"); ?>
<?php echo view("components/modals/add_view"); ?>
<?php echo view("components/modals/add_index"); ?>
<?php echo view("components/modals/add_foreign_database"); ?>
<?php echo view("components/component-toolbar"); ?>
    <div id="app">
        <toolbar :buttons="toolbar.buttons"
                 :breadcrumbs="toolbar.breadcrumbs"
                 :current_user="currentUser">
        </toolbar>
        <div class="dmiux_content">
            <database-manager ref="databaseManager"
                              :tables.sync="tables"
                              :views.sync="views"
                              :foreign_tables.sync="foreign_tables">
            </database-manager>

            <add-view ref="manageView" :control_id.sync="control_id"></add-view>
            <add-index ref="manageIndex" :control_id.sync="control_id"></add-index>
            <add-database ref="addDatabase" :control_id.sync="control_id"></add-database>
        </div>

        <add-table ref="addTable"
                   :control_id="control_id"
                   :breadcrumbs="toolbar.breadcrumbs">
        </add-table>
    </div>
    <script>
        var control_id = <?php echo $database_id; ?>;
        var from_download_link = '<?php echo $from_download_link; ?>';
        var file_upload_url = '<?php echo $upload_url ?>';
    </script>
    <script src="/assets/js/databaseManager.js?#{release}#"></script>
<?php echo view("components/foot"); ?>
