<script type="text/x-template" id="delete-confirmation-modal-template">
    <div class="dmiux_popup" id="modal-delete_confirmation" role="dialog" tabindex="-1">
        <div class="dmiux_popup__window dmiux_popup__window_md" role="document">
            <div class="dmiux_popup__head">
                <h4 class="dmiux_popup__title">Delete {{ subject }}</h4>
                <button type="button" class="dmiux_popup__close"></button>
            </div>
            <div class="dmiux_popup__cont">
                <div v-if="data.length > 0" class="alert alert-danger mb-0">
                    <h5>The impacts of removing this {{ subject }} are listed below</h5>
                    <p><b>The following {{ type }} will be deleted:</b></p>
                    <ul>
                        <li v-for="value in data">{{ value.name }}: {{ value.count }}</li>
                    </ul>
                </div>
                <div v-else class="alert alert-success mb-0">
                    <h5>Nothing is currently using this {{ subject }} so there are no consequences for deleting it</h5>
                </div>
            </div>
            <div class="dmiux_popup__foot">
                <div class="dmiux_grid-row">
                    <div class="dmiux_grid-col"></div>
                    <div class="dmiux_grid-col dmiux_grid-col_auto">
                        <button class="dmiux_button dmiux_button_secondary dmiux_popup__close_popup" type="button">Cancel</button>
                    </div>
                    <div class="dmiux_grid-col dmiux_grid-col_auto pl-0">
                        <button class="dmiux_button" type="button" @click="confirm()">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>

<script>
     var delete_confirmation = Vue.component('delete-confirmation', {
        template: '#delete-confirmation-modal-template',
        props: [ 'subject', 'type', 'controller', 'method', 'callback' ],
        data: function () {
            return {
                id: 0,
                data: []
            }
        },
        methods: {
            confirm() {
                var check = prompt(`If you delete this ${this.subject}, you will not be able to get it back. Type 'DELETE' to continue.`)
                if(check == null || check.toLowerCase() != "delete") {
                    return;
                }

                var options = {
                    method: 'delete',
                    headers: {
                        'Content-type': 'application/x-www-form-urlencoded; charset=UTF-8'
                    }
                };

                this.$root.loading(true);
                fetch(`${this.controller}/${this.method}/${this.id}`, options)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.$root.loading(false)
                        this.callback();
                        notify.success(`${this.subject} has been deleted.`);
                        closeModal('#modal-delete_confirmation');
                    })
                    .catch((error) => {
                        this.$root.loading(false)
                        ResponseHelper.handleErrorMessage(error, `${this.subject} could not be deleted.`);
                    });
            }
        }
    });
</script>
