<script type="text/x-template" id="unstructured-stage">
<div class="unstructured-stage" style="display: none;">
    <table class="dmiux_data-table__table">
        <tr v-for="(val2, col) in returnJson(val)" v-if="val2 != null && val2 !== '' && val2 != undefined">
            <span class="hidden">{{ col = setCol(selected_column, col, column_name, val2, sql_definition) }}</span>
            <td>
                <span v-if="flags[table_name + '_' + col[1]] != null"
                      class="flag text-lg text-danger"
                      data-toggle="tooltip"
                      data-placement="bottom"
                      :title="flags[table_name + '_' + col[1]].assigned_user">
                    <span class="fas fa-flag"></span>
                </span>

                <span v-if="comments[table_name + '_' + col[1]] != null"
                      class="comment text-lg"
                      data-toggle="tooltip"
                      data-placement="bottom"
                      :title="comments[table_name + '_' + col[1]][0].comment_text">
                    <span class="fas fa-comment" style="color: #374C68;"></span>
                </span>

                <span v-if="mappings[table_name + '_' + col[1]] != null"
                      class="mapping text-lg"
                      data-toggle="tooltip"
                      data-placement="bottom"
                      :title="$root.$refs.records.getColumnMappingTitle(table_name, col[1])">
                    <span class="fas fa-map-marker-alt" style="color: #7F8FA5;"></span>
                </span>

                <span v-if="attachments[table_name + '_' + col[1]] != null"
                        class="files text-lg"
                        data-toggle="tooltip"
                        data-placement="bottom"
                        :title="attachments[table_name + '_' + col[1]][0].file_name">
                    <span class="fas fa-file" style="color: #acb0c5;"></span>
                </span>

            </td>
            <td class="fill-circle">
                <center>
                    <div class="dmiux_radio">
                        <input type="radio" @change="$root.$refs.records.changeSelectedColumnValues('', getType(val2), getSqlDef(sql_definition, col[0], val2), prefix, col[1], selected_column_index, false, true, col[3])" v-model="$root.$refs.records.column" :value="col[1]" class="dmiux_radio__input">
                        <div class="dmiux_radio__check"></div>
                        <template v-if="checkWhere(prefix + '.' + col[1])">
                            <div class="dmiux_radio__label dmiux_column-filtered"> {{ col[0] }}</div>
                        </template>
                        <template v-else>
                            <div class="dmiux_radio__label"> {{ col[0] }}</div>
                        </template>
                    </div>
                </center>
            </td>
            <td class="object-value">
                <span v-if="isJson(val2) == false">
                    <span v-if="checkWhere(prefix + '.' + col[1])">
                        <pre class="dmiux_column-filtered">{{ val2 }}</pre>
                    </span>
                    <pre v-else>{{ val2 }}</pre>
                </span>
                <span v-else>
                    <unstructured-stage :val="val2"
                                        :flags="flags"
                                        :comments="comments"
                                        :mappings="mappings"
                                        :attachments="attachments"
                                        :selected_column="col[1]"
                                        :selected_column_index="selected_column_index"
                                        :prefix="prefix"
                                        :sql_definition="getSqlDef(sql_definition, col[0], val2)"
                                        :table_name="table_name"
                                        :column_name="column_name">
                    </unstructured-stage>
                </span>
            </td>
        </tr>
    </table>
</div>
</script>
