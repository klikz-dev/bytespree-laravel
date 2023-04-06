<?php
    echo view("components/head");
    
    // Load components
    echo view("components/tables/component-ribbon");
    echo view("components/tables/component-records");
    echo view("components/tables/component-table-summary");

    // Load Modals
    echo view("components/modals/add_flag");
    echo view("components/modals/add_comment");
    echo view("components/modals/custom_filter");
    echo view("components/modals/map_column");
    echo view("components/modals/counts");
    echo view("components/modals/longest");
    echo view("components/modals/table_modal");
    echo view("components/modals/add_attachment");
    echo view("components/modals/table_notes");
    echo view("components/modals/join_manager");
    echo view("components/modals/union_manager");
    echo view("components/modals/copy_column");
    echo view("components/modals/publish_view");
    echo view("components/modals/switch_view");
    echo view("components/modals/add_transformation");
    echo view("components/component-publisher-scheduling");
    echo view("components/modals/publish_sftp");
    echo view("components/modals/publish_mssql");
    echo view("components/modals/publish_csv");
    echo view("components/modals/publish_snapshot");
    echo view("components/modals/add_saved_query");
    echo view("components/modals/custom_sort");
    echo view("components/tables/component-toolbar");
    echo view("components/tables/component-unstructured-stage");
?>
<div id="app">
    <div class="toolbar_holder">
        <toolbar ref="toolbar"
                 :buttons="toolbar.buttons"
                 :modified="modified"
                 :breadcrumbs="toolbar.breadcrumbs"
                 :table_name="explorer.valid_query.table"
                 :tables="explorer.tables"
                 :control_id="control_id"
                 :completed="completed"
                 :viewing_type="explorer.viewing_type"
                 :mobile="mobile"
                 :schema="explorer.valid_query.schema">
        </toolbar>
        <ribbon :control_id="control_id"
                :modules="mapping.modules"
                :mappings="mapping.mappings"
                :selected_column="explorer.selected_column"
                :selected_column_data_type="explorer.selected_column_data_type"
                :selected_column_index="explorer.selected_column_index"
                :selected_column_unstructured="explorer.selected_column_unstructured"
                :comments="comments"
                :flags="flags"
                :attachments="attachments"
                :user="currentUser"
                :users="users"
                :table="explorer.valid_query.table"
                :mobile="mobile"
                :view_mode="explorer.view_mode">
        </ribbon>
    </div>
    <div class="dmiux_content">
        <div class="dmiux_table_container">
            <div id="dynamic-size">
                <records ref="records" 
                         :table="explorer.valid_query.table"
                         :schema="explorer.valid_query.schema"
                         :control_id="control_id"
                         :flags="flags"
                         :columns="explorer.valid_query.columns"
                         :comments="comments"
                         :mappings="mappings"
                         :attachments="attachments"
                         :transformations="explorer.valid_query.transformations"
                         :records="explorer.records"
                         :record_counts="explorer.record_count"
                         :type="explorer.type"
                         :selected_column="explorer.selected_column"
                         :selected_prefix="explorer.selected_prefix"
                         :selected_column_index="explorer.selected_column_index"
                         :active_users = "explorer.active_users"
                         :page_amt="explorer.page_amt"
                         :viewing_type="explorer.viewing_type"
                         :filters="explorer.valid_query.filters"
                         :mobile="mobile"
                         :pivoted="explorer.pivoted"
                         :view_mode="explorer.view_mode"
                         :view="explorer.view"
                         :pending_count="explorer.pending_count"
                         :notes="table_notes">
                </records>
            </div>
        </div>
    </div>
    <add-flag :users="users"
              :table_name="explorer.valid_query.table"
              :column_name="explorer.selected_column"
              :project_id="control_id"
              :curr_user="currentUser">
    </add-flag>
    <add-transformation ref="transformation_modal"
                        :table="explorer.valid_query.table"
                        :selected_column="explorer.selected_column"
                        :selected_prefix="explorer.selected_prefix"
                        :selected_alias="explorer.selected_alias"
                        :project_id="control_id"
                        :viewing_type="explorer.viewing_type"
                        :user="currentUser">
    </add-transformation>

    <copy-column :open="modals.copy_column"
                 :columns="explorer.valid_query.columns"
                 :table="explorer.valid_query.table"
                 :app_transformations="explorer.valid_query.transformations"
                 :selected_column="explorer.selected_column"
                 :selected_column_index="explorer.selected_column_index"
                 :selected_column_data_type="explorer.selected_column_data_type"
                 :selected_sql_definition="explorer.selected_sql_definition"
                 :selected_prefix="explorer.selected_prefix"
                 :selected_alias="explorer.selected_alias"
                 :project_id="control_id"
                 :viewing_type="explorer.viewing_type"
                 :user="currentUser">
    </copy-column>

    <custom-filter :selected_column="explorer.selected_column"
                   :selected_column_data_type="explorer.selected_column_data_type"
                   :selected_prefix="explorer.selected_prefix"
                   :viewing_type="explorer.viewing_type"
                   :selected_filter="selected_filter"
                   :edit_mode="edit_mode"
                   :open="modals.custom_filter"
                   :tables="explorer.tables">
    </custom-filter>
    <map-column :control_id="control_id"
                :open="modals.map_column"
                :modules="mapping.modules"
                :destination_schema_id="destination_schema_id"
                :mapping_module_id="mapping.selected_mapping_module"
                :table="explorer.valid_query.table"
                :selected_column="explorer.selected_column"
                :tables="explorer.tables"
                :mappings="mappings">
    </map-column>

    <counts :open="modals.counts"
            :control_id="control_id"
            :viewing_type="explorer.viewing_type"
            :selected_column="explorer.selected_column"
            :selected_prefix="explorer.selected_prefix"
            :counts="counts"
            :table="explorer.valid_query.table"
            :edit_mode="edit_mode"
            :filters="explorer.valid_query.filters"
            :selected_filter="selected_filter"
            :edit_mode="edit_mode">
    </counts>

    <longest :open="modals.longest"
             :control_id="control_id"
             :table="explorer.valid_query.table"
             :viewing_type="explorer.viewing_type"
             :selected_column="explorer.selected_column"
             :selected_prefix="explorer.selected_prefix"
             :selected_filter="selected_filter"
             :edit_mode="edit_mode"
             :filters="explorer.valid_query.filters">
    </longest>

    <change-column-preference :open="modals.change_column_preference" 
                              :columns="explorer.valid_query.columns"
                              :joins="explorer.valid_query.joins"
                              :prefix="explorer.valid_query.prefix"
                              :custom_id="active_custom_id">
    </change-column-preference>

    <add-attachment :open="modals.add_attachment"
                    :control_id="control_id"
                    :selected_column="explorer.selected_column"
                    :attachments="attachments"
                    :table="explorer.valid_query.table"
                    :max_upload_size="max_upload_size">
    </add-attachment>

    <table-notes :open="modals.table_notes"
                 :notes="table_notes"
                 :note_id="show_note_id">
    </table-notes>

    <join-manager :open="modals.join_manager"
                  :tables="explorer.tables"
                  :table="explorer.valid_query.table"
                  :columns="explorer.valid_query.columns"
                  :viewing_type.sync="explorer.viewing_type">
    </join-manager>

    <union-manager :open="modals.union_manager"
                  :tables="explorer.tables"
                  :table="explorer.valid_query.table"
                  :columns="explorer.valid_query.columns"
                  :viewing_type.sync="explorer.viewing_type">
    </union-manager>

    <add-comment :user="currentUser"
                 :selected_column="explorer.selected_column"
                 :control_id="control_id"
                 :table="explorer.valid_query.table"
                 :comments="comments"
                 :open="modals.add_comment">
    </add-comment>

    <custom-sort :open="modals.custom_sort">
    </custom-sort>
</div>

<script>
    var control_id = <?php echo $control_id; ?>;
    var project_name = '<?php echo $project_name; ?>';
    var table = <?php echo "'" . $table . "'"; ?>;
    var schema = <?php echo "'" . $schema . "'"; ?>;
    var completed = '<?php echo $completed; ?>';    
    var lastHeight = $(".toolbar_holder").css('height');
    var flashError = '<?php echo $flashError; ?>';
    var max_upload_size = '<?php echo $max_size; ?>';
    var sent_column = "<?php echo $sent_column; ?>";
    var baseUrl = "";
    var destination_schema_id = "<?php echo $destination_schema_id; ?>";
    var saved_query = <?php echo $saved_query; ?>;
    var publisher_data = <?php echo $publisher; ?>;
    var table_exists = <?php echo $table_exists ? 'true' : 'false'; ?>;
    var file_upload_url = '<?php echo $file_upload_url; ?>';
</script>

<script src="/assets/js/tables.js?#{release}#"></script>
<script src="/assets/js/tables.app.js?#{release}#"></script>
<?php echo view("components/foot"); ?>
