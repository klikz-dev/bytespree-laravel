<?php
    echo view("components/head");
    echo view("components/compare/component-compare");

    echo view("components/compare/component-toolbar");
?>
<div id="app">
    <toolbar :buttons="toolbar.buttons"
             :breadcrumbs="toolbar.breadcrumbs">
    </toolbar>
    <div class="dmiux_content">
        <div class="dmiux_table_container">
            <compare ref="compare"
                     :databases="databases.user_databases">
            </compare>
        </div>
    </div>
</div>
<script src="/assets/js/compare.js?#{release}#"></script>
<?php echo view("components/foot"); ?>