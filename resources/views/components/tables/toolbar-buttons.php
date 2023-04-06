<div class="dmiux_grid-row dmiux_grid-row_jce dmiux_toolbar">
    <div @click="refreshTable()"
            class="dmiux_grid-col dmiux_grid-col_auto">
        <button type="button"
                title="Refresh the current result set"
                class="dmiux_toolbar_item">
            <img src="/assets/images/icons/icons8-refresh-50.png"
                class="dmiux_toolbar_item_icon"
                draggable="false" />
        </button>
    </div>
    <div @click="$root.modals.custom_sort = true"
            class="dmiux_grid-col dmiux_grid-col_auto">
        <button type="button"
                title="Enter custom sort expression"
                class="dmiux_toolbar_item">
            <img src="/assets/images/icons/icons8-up-down-arrow-80.png"
                class="dmiux_toolbar_item_icon"
                draggable="false" />
        </button>
    </div>
     <div @click="changeGroupBy()"
            class="dmiux_grid-col dmiux_grid-col_auto">
        <button v-if="$root.explorer.query.is_grouped == true" 
                type="button"
                title="Ungroup result set"
                class="dmiux_toolbar_item">
            <img src="/assets/images/icons/icons8-ungroup-objects-80.png"
                class="dmiux_toolbar_item_icon"
                draggable="false" />
        </button>
        <button v-else
                type="button"
                title="Group result set together"
                class="dmiux_toolbar_item">
            <img src="/assets/images/icons/icons8-group-objects-80.png"
                class="dmiux_toolbar_item_icon"
                draggable="false" />
        </button>
    </div>
    <div @click="pivotExplorer();"
            class="dmiux_grid-col dmiux_grid-col_auto">
        <button type="button"
                title="Pivot the data display orientation"
                class="dmiux_toolbar_item">
            <img src="/assets/images/icons/icons8-rotation-50.png"
                class="dmiux_toolbar_item_icon"
                draggable="false" />
        </button>
    </div>
    <div @click="openJoinModal();"
            class="dmiux_grid-col dmiux_grid-col_auto">
        <button type="button"
                title="Join data together with another table"
                class="dmiux_toolbar_item">
            <img src="/assets/images/icons/icons8-query-inner-join-50.png"
                class="dmiux_toolbar_item_icon"
                draggable="false" />
        </button>
    </div>
    <div @click="openUnionModal();"
            class="dmiux_grid-col dmiux_grid-col_auto">
        <button type="button"
                title="Add unioned data to the result set"
                class="dmiux_toolbar_item">
            <img src="/assets/images/icons/icons8-unions.png"
                class="dmiux_toolbar_item_icon"
                draggable="false" />
        </button>
    </div>
    <a :href="'/studio/projects/' + control_id + '/tables/' + schema + '/' + table_name + '/map'"
            class="dmiux_grid-col dmiux_grid-col_auto">
        <button type="button"
                title="View a listing of all mappings"
                class="dmiux_toolbar_item">
            <img src="/assets/images/icons/icons8-map-50.png"
                class="dmiux_toolbar_item_icon"
                draggable="false" />
        </button>
    </a>
    <div class="dmiux_actions__col dmiux_grid-col dmiux_grid-col_auto dmiux_input no_hover_animate">      
        <input id="table_dropdown" autocomplete="off" list="table_list" class="dmiux_input__input grid-row1" @change="selectTable($event)" @mouseover="clearBox($event)" @mouseout="clearBox($event)" placeholder="Choose a Table" v-model="fully_qualified_table_name" />
        <datalist id="table_list">
            <option v-for="table in tables"
                    v-bind:value="table.table_catalog + '.' + table.table_name">
                {{ table.table_catalog }}.{{ table.table_name }}
            </option>
        </datalist>
    </div>
</div>