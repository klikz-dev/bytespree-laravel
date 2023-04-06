<!-- Request Access Modal -->
<script type="text/x-template" id="request-access-modal-template">
    <div class="dmiux_popup" id="modal-request_access" role="dialog" tabindex="1">
        <div class="dmiux_popup__window dmiux_popup__window_md" role="document">
            <div class="dmiux_popup__head">
                <h4 class="dmiux_popup__title">Request Access to <span :title="selectedDatabase.database" class="database-title tooltip-pretty">{{ selectedDatabase.database }}</span></h4>
                <button type="button" class="dmiux_popup__close" @click="modalClose($event)"></button>
            </div>
            <div class="dmiux_popup__cont">
                <p class="text-break">You are requesting access to the <strong>{{ selectedDatabase.database }}</strong> database. 
                   You will not be able to access the database until the request is granted.</p>
                <div class="dmiux_input">
                    <textarea ref="reason" id="textareaReason" v-model="reason" class="dmiux_input__input"></textarea>
                </div>
            </div>
            <div class="dmiux_popup__foot">
                <div class="dmiux_grid-row">
                    <div class="dmiux_grid-col"></div>
                    <div class="dmiux_grid-col dmiux_grid-col_auto">
                        <button class="dmiux_button dmiux_button_secondary dmiux_popup__cancel" type="button" @click="modalClose($event)">Cancel</button>
                    </div>
                    <div class="dmiux_grid-col dmiux_grid-col_auto pl-0">
                        <button class="dmiux_button" type="button" @click="sendRequest()">Send Request</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>