<script type="text/x-template" id="transformation-modal-template">
    <div class="dmiux_popup" id="modal-add_transformation" role="dialog" tabindex="-1">
        <div class="dmiux_popup__window dmiux_popup__window_lg" role="document">
            <div class="dmiux_popup__head">
                <h4 class="dmiux_popup__title">Column Transformations for <mark v-if="selected_alias == ''"><template v-if="viewing_type=='Join'">{{ selected_prefix }}_</template>{{ selected_column }}</mark><mark v-else>{{ selected_alias }}</mark></h4>
                <button id="x-button" type="button" class="dmiux_popup__close" @click="modalCloseTransformations"></button>
            </div>
            <form id="form-add_transformation" autocomplete="off" onSubmit="event.preventDefault()">
                <div class="dmiux_popup__cont">
                    <draggable v-model="transformations" :preventOnFilter="false" :filter="'.dmiux_input__input'">
                        <div v-for="(transformation, index) in transformations" v-if="transformation.status != 'deleted'" class="card p-2 mb-2">
                            <template v-if="transformation.status == 'done'">
                                <div class="dmiux_grid-row mb-2">
                                    <div class="dmiux_grid-col dmiux_grid-col_10">
                                        <template v-if="transformation.transformation_type != 'ConditionalLogic'">
                                            <template v-for="field in transformation.transformation">
                                                <label v-html="replaceLabel(field.label)"></label>
                                                <b>{{ field.value }}</b>
                                            </template>
                                        </template>  
                                        <template v-else>
                                            <template v-for="fields in transformation.transformation">
                                                <template v-for="field in fields">
                                                    <label v-html="replaceLabel(field.label)"></label>
                                                    <b>{{ field.value }}</b>
                                                </template>
                                            </template>
                                        </template>
                                    </div>
                                    <div class="dmiux_grid-col dmiux_grid-col_1">
                                        <button type="button" tabindex="-1" title="Edit transformation" class="dmiux_account__button dmiux_account__button_edit transformation_buttons transformation_button_edit" @click="editTransformation(index)"></button>
                                        <div title="Move transformation" class="cursor-p dmiux_account__button dmiux_account__button_move ui-sortable-handle transformation_buttons transformation_button_sortable"></div>
                                        <button title="Delete transformation" type="button" tabindex="-1" class="dmiux_account__button dmiux_account__button_remove transformation_buttons transformation_button_remove" @click="removeTransformation(index)"></button>
                                    </div>
                                </div>
                            </template>
                            <template v-else-if="transformation.status == 'add' || transformation.status == 'edit'">
                                <div class="dmiux_grid-row" :class="transformation.transformation_type+'_type'">
                                    <div class="dmiux_grid-col dmiux_grid-col_10">
                                        <div class="dmiux_select">
                                            <select class="dmiux_select__select" v-model="transformation.transformation_type" @change="changeType(index, $event)">
                                                <option v-for="(value, label) in types" :value="value">{{ label }}</option>
                                            </select>
                                            <div class="dmiux_select__arrow"></div>
                                        </div>
                                    </div>
                                    <div class="dmiux_grid-col dmiux_grid-col_1">
                                        <button v-if="transformation.status == 'edit'" type="button" tabindex="-1" title="Save edit" class="dmiux_account__button dmiux_account__button_edit transformation_buttons transformation_button_edit" @click="editTransformation(index)"></button>
                                        <div title="Move transformation" class="cursor-p dmiux_account__button dmiux_account__button_move ui-sortable-handle transformation_buttons transformation_button_sortable"></div>
                                        <button title="Delete transformation" type="button" tabindex="-1" class="dmiux_account__button dmiux_account__button_remove transformation_buttons transformation_button_remove" @click="removeTransformation(index)"></button>
                                    </div>
                                </div>
                                <!-- If its nested if else -->
                                <draggable  v-model="transformations[index].transformation"
                                            :move="checkMove" 
                                            v-if="transformation.transformation_type == 'ConditionalLogic'"
                                            > 
                                    <div v-if="transformation.transformation_type == 'ConditionalLogic'" :class="index+'_transformations  else-trans-row'" v-for="(fields, index1) in transformation.transformation">
                                        <div class="trans-column" v-for="(field,index_field) in fields" :class="field.type == 'select' ? 'dmiux_grid-col_5'  : ''">
                                            <div :class="index_field+'_label'">
                                                <label v-if="field.label != ''"  :for="replaceLabel(field.label.replace('&nbsp;', ''))+index+'_'+index1" v-html="replaceLabel(field.label.replace('&nbsp;', ''))"></label>
                                            </div>
                                            <div v-if="field.type == 'select'" class="dmiux_select" :class="index_field+'_select'">
                                                <select class="dmiux_select__select" v-model="field.value" :id="replaceLabel(field.label.replace('&nbsp;', ''))+index+'_'+index1">
                                                    <template v-if="field.select_values == 'operators'">
                                                        <option v-for="(value, label) in operators" :value="value">{{ label }}</option>
                                                    </template>
                                                    <template v-else>
                                                        <option v-for="(value, label) in field.select_values" :value="value">{{ label }}</option>
                                                    </template>
                                                </select>
                                                <div class="dmiux_select__arrow"></div>
                                            </div>
                                            <div :class="index_field+'_input'">
                                                <input v-if="field.type == 'input' && field.type != ''" class="dmiux_input__input " type="text" v-model="field.value" :id="replaceLabel(field.label.replace('&nbsp;', ''))+index+'_'+index1" />
                                            </div>

                                                <div v-if="field.type == 'drag' && index1 != 0" title="Move transformation" class="cursor-p dmiux_account__button dmiux_account__button_move ui-sortable-handle transformation_buttons" ></div>

                                                <button v-if="field.type == 'remove' && index1 != 0 && index_field.includes('else_field_') !== true" title="Delete transformation" type="button" tabindex="-1" class="dmiux_account__button dmiux_account__button_remove transformation_buttons transformation_button_remove_custom" @click="removeConditionalTranformation(index,index1,'elseif')"></button> 

                                                <button v-if="field.type == 'remove' && index1 != 0 && index_field.includes('else_field_') === true" title="Delete transformation" type="button" tabindex="-1" class="dmiux_account__button dmiux_account__button_remove transformation_buttons transformation_button_remove_custom_else" @click="removeConditionalTranformation(index,index1,'else')"></button> 

                                        </div>
                                    </div>
                                </draggable>
                                <!-- If its not nested if else -->
                                <div v-if="transformation.transformation_type != 'ConditionalLogic'">
                                    <template v-for="(field, index1) in transformation.transformation">
                                        <label :for="replaceLabel(field.label.replace('&nbsp;', ''))+index+'_'+index1" v-html="replaceLabel(field.label.replace('&nbsp;', ''))"></label>
                                        <input v-if="field.type == 'input'" class="dmiux_input__input" type="text" v-model="field.value" :id="replaceLabel(field.label.replace('&nbsp;', ''))+index+'_'+index1"/>
                                        <div v-if="field.type == 'select'" class="dmiux_select">
                                            <select class="dmiux_select__select" v-model="field.value" :id="replaceLabel(field.label.replace('&nbsp;', ''))+index+'_'+index1">
                                                <template v-if="field.select_values == 'operators'">
                                                    <option v-for="(value, label) in operators" :value="value">{{ label }}</option>
                                                </template>
                                                <template v-else>
                                                    <option v-for="(value, label) in field.select_values" :value="value">{{ label }}</option>
                                                </template>
                                            </select>
                                            <div class="dmiux_select__arrow"></div>
                                        </div> 
                                    </template>
                                </div>
                                <!-- else if else buttons -->
                                <template v-if="transformation.transformation_type == 'ConditionalLogic'">
                                    <div class="dmiux_else-btns-div">
                                        <button class="dmiux_button_add-else-if" @click="addElseIf(index,'elseif')">+Add ElseIf</button>
                                        <button v-if="! transformation.conditional_else_added" class="dmiux_button_add-else" :id="index+'_else_btn'" @click="addElseIf(index,'else')">+Add Else</button>
                                    </div>
                                </template>
                            </template>
                        </div>
                    </draggable>
                </div>
                <div class="dmiux_popup__foot">
                    <div class="dmiux_grid-row">
                        <div class="dmiux_grid-col dmiux_grid-col_auto pl-0">
                            <button @click="addTransformation()" class="dmiux_button add_transformation_button">{{transformation_button}}</button>
                        </div>
                        <div class="dmiux_grid-col"></div>
                        <div class="dmiux_grid-col dmiux_grid-col_auto">
                            <button id="cancel-button-add-transformation" class="dmiux_button dmiux_button_secondary dmiux_popup__cancel" @click="modalCloseTransformations" type="button">Cancel</button>
                        </div>
                        <div class="dmiux_grid-col dmiux_grid-col_auto pl-0">
                            <button class="dmiux_button" type="button" @click="applyTransformations()">Apply Transformations</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</script>