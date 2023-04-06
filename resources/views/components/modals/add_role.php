<script type="text/x-template" id="role-modal-template">
    <div class="dmiux_popup" id="modal-add_role" role="dialog" tabindex="-1">
        <div class="dmiux_popup__window dmiux_popup__window_lg" role="document">
            <div class="dmiux_popup__head">
                <h4 class="dmiux_popup__title">Add Role</h4>
                <button type="button" class="dmiux_popup__close"></button>
            </div>
            <form autocomplete="off" id="form-roles-add" onSubmit="event.preventDefault()">
                <div class="dmiux_popup__cont">
                    <label for="select-my_product">Applications</label>
                    <div class="dmiux_select">
                        <select class="dmiux_select__select" name="select-my_product" id="select-my_product" v-model="product_id">
                            <option disabled value="0">Choose an Application</option>
                            <option v-for="product in products" v-if="$root.isProductEnabled(product.id) || $root.isCurrentUserInternal()" :value="product.id">{{ product.display_name }}</option>
                        </select>
                        <div class="dmiux_select__arrow"></div>
                    </div>
                    <br>
                    <div class="dmiux_input">
                        <label for="input-role_name">Please enter new role name</label>
                        <input type="text" @input="cleanupRoleName()" v-model="role_name" class="dmiux_input__input" id="input-role_name">
                    </div>
                </div>
                <div class="dmiux_popup__foot">
                    <div class="dmiux_grid-row">
                        <div class="dmiux_grid-col"></div>
                        <div class="dmiux_grid-col dmiux_grid-col_auto">
                            <button class="dmiux_button dmiux_button_secondary dmiux_popup__close_popup" data-dismiss="modal" type="button">Close</button>
                        </div>
                        <div class="dmiux_grid-col dmiux_grid-col_auto pl-0">
                            <button class="dmiux_button" type="button" @click="addRole(role_name, product_id)">Add</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</script>

<script>
    var addRole = Vue.component('addRole', {
        template: "#role-modal-template",
        props: [ "products" ],
        data: function () {
            return {
                role_name: "",
                product_id: 0
            }
        },
        methods: {
            addRole(role_name, product_id) {
                if(role_name == "" || product_id == 0){
                    notify.danger('All fields are required');
                    return;
                }
                this.$root.loading(true);

                let options = FetchHelper.buildJsonRequest({
                    product_id: product_id,
                    role_name: role_name
                });

                fetch(`/internal-api/v1/roles`, options)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        notify.success('New role has been added.')
                        this.$root.getRolePerms();
                        this.$root.loading(false);

                        this.role_name = "";
                        this.product_id = 0;

                        $('.dmiux_popup__close_popup').trigger('click');        
                    })
                    .catch((error) => {
                        this.$root.loading(false);
                        ResponseHelper.handleErrorMessage(error, 'Could not create role.');
                    });
            },
            cleanupRoleName: function () {
                this.role_name = this.role_name.substring(0, 200);
            }
        }
    });
</script>