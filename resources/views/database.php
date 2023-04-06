<?php
    echo view("components/head");
    echo view("components/database/component-toolbar");
    echo view("components/database/component-wizard");
    echo view("components/general/component-connector-settings");    
?>

    <div id="app">
        <toolbar :breadcrumbs="toolbar.breadcrumbs"></toolbar>
        <div class="dmiux_grid-cont px-3">

            <div class="dmiux_milestones position-sticky">
                <button type="button"
                        @click="validateForm('database-integration');"
                        :class="selected_tab == 'database-integration' ? 'dmiux_active' : ''"
                        class="dmiux_milestones__link">Choose a connector</button>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 13 24" class="dmiux_milestones__angle">
                    <path fill="#E7EAEE" d="M1.480718 23.905109L0 22.4243913l10.471837-10.4718368L0 1.4807177 1.480718 0l11.212195 11.2121957c.409449.4094488.409449 1.0712689 0 1.4807177L1.480718 23.905109z"/>
                </svg>
                <button type="button"
                        @click="validateForm('database-settings')"
                        :class="selected_tab == 'database-settings' ? 'dmiux_active' : ''"
                        class="dmiux_milestones__link">Enter settings</button>
                <template v-if="selected_integration.name != 'Basic'">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 13 24" class="dmiux_milestones__angle">
                        <path fill="#E7EAEE" d="M1.480718 23.905109L0 22.4243913l10.471837-10.4718368L0 1.4807177 1.480718 0l11.212195 11.2121957c.409449.4094488.409449 1.0712689 0 1.4807177L1.480718 23.905109z"/>
                    </svg>
                    <button type="button"
                            @click="validateForm('database-schedule')"
                            :class="selected_tab == 'database-schedule' ? 'dmiux_active' : ''"
                            class="dmiux_milestones__link">
                            Define a schedule
                    </button>
                </template>
            </div>

            <database-creation-wizard ref="wizard"
                :selected_integration="selected_integration"
                :integrations="integrations" 
                :servers="servers"
                :current_user="current_user"
                :is_selected="is_selected"
                :selected_tab="selected_tab"
                :schedule_types="schedule_types_computed"
                system_timezone="<?php echo $system_timezone; ?>">
            </database-creation-wizard>
        </div>
    </div>
    <script type="text/javascript">
        system_time_offset = <?php echo $system_time_offset ?? 0; ?>;
    </script>
    <script defer src="/assets/js/database.js?#{release}#"></script>
<?php echo view("components/foot"); ?>