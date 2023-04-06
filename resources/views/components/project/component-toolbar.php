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
                        <div class="dmiux_actions__col dmiux_grid-col dmiux_grid-col_auto dmiux_main-nav__item">
                            <button class="dmiux_main-nav__link dmiux_button dmiux_button_secondary"
                                    style="line-height: 32px;">Export&nbsp;&nbsp;
                                <span class="fas fa-download"></span>
                                <span class="dmiux_menu__arrow">
                                    <svg style="margin-left: 5px; margin-top: 5px;" viewbox="0 0 24 24">
                                        <polyline points="6 9 12 15 18 9"></polyline>
                                    </svg>
                                </span>
                            </button>
                            <div class="dmiux_main-nav__dropdown">
                                <a class="dmiux_main-nav__sublink"
                                   @click="getTableMappings()"
                                   href="JavaScript:void(0)" v-if="$root.project_detail.destination_schema_id">Export Maps</a>
                                <a class="dmiux_main-nav__sublink"
                                   @click="getSchema()"
                                   href="JavaScript:void(0)">Export Schema</a>
                            </div>
                        </div>
                        <div class="dmiux_actions__col dmiux_grid-col dmiux_grid-col_auto">      
                            <button data-popup-open="#modal-column" type="button"
                                    class="dmiux_button dmiux_button_secondary">
                                    Search Columns&nbsp;&nbsp;
                                <span class="fas fa-search"></span>
                            </button>
                        </div>
                        <div class="dmiux_actions__col dmiux_grid-col dmiux_grid-col_auto dmiux_input">      
                            <input list="table_list" class="dmiux_input__input grid-row1" @change="selectTable($event)" placeholder="Choose a Table" />
                            <datalist id="table_list">
                                <option v-for="table in tables"
                                        v-if="table.exists != false && table.synchronized != false"
                                        v-bind:value="table.table_catalog + '.' + table.table_name" >
                                    {{ table.table_catalog }}.{{ table.table_name }}
                                </option>
                            </datalist>
                        </div>
                    </div>
                </div>     
            </div>  
        </div>
    </div>
</script>
