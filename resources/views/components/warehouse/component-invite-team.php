<script type="text/x-template" id="component-invite-team">
    <div class="card text-left">
        <form @submit.prevent="sendInvitationEmail()">
            <div class="dmiux_popup__cont">
                <div class="dmiux_radio">
                    <input type="radio" v-model="inviteUser" name="inviteUser" class="dmiux_radio__input" value="email" @change="changeFunction()" checked>
                    <div class="dmiux_radio__check"></div>
                    <div class="dmiux_radio__label">Invite user by email</div>
                </div>
                <div class="dmiux_radio mb-4 pb-4">
                    <input type="radio" v-model="inviteUser" name="inviteUser" class="dmiux_radio__input" value="handle" @change="changeFunction()">
                    <div class="dmiux_radio__check"></div>
                    <div class="dmiux_radio__label">Invite user by Bytespree handle</div>
                </div>
                <div v-if="inviteUser == 'email'">
                    <span v-for="(email, index) in emails">
                        <div class="dmiux_grid-row mt-2">
                            <div class="dmiux_grid-col">
                                <input type="email"
                                        id="user_email" 
                                        v-model="emails[index].email" 
                                        placeholder="Enter User Email" 
                                        class="dmiux_input__input"
                                        required>
                            </div>
                            <div class="dmiux_grid-col dmiux_grid-col_auto">
                                <button @click.prevent="removeEmail(index)" data-toggle="tooltip" title="Remove this email" type="button" class="dmiux_button red-background mt-0"><i class="fa fa-minus"></i></button>
                            </div>
                        </div>
                    </span>
                    <button @click.prevent="addEmail()" class="dmiux_button mt-2"><i class="fa fa-plus"></i> Add Email </button>
                </div>
                <div v-else>
                    <span v-for="(handle, index) in handles">
                        <div class="dmiux_grid-row mt-2">
                            <div class="dmiux_grid-col">
                                <input type="text"
                                        id="user_handle" 
                                        v-model="handles[index].handle" 
                                        placeholder="Enter Bytespree Handle" 
                                        class="dmiux_input__input"
                                        pattern="^[a-z0-9]+$"
                                        minlength="4"
                                        maxlength="20"
                                        @input="cleanupHandle(index)"
                                        required>
                            </div>
                            <div class="dmiux_grid-col dmiux_grid-col_auto">
                                <button @click.prevent="removeHandle(index)" data-toggle="tooltip" title="Remove this handle" type="button" class="dmiux_button red-background mt-0"><i class="fa fa-minus"></i></button>
                            </div>
                        </div>
                    </span>
                    <button @click.prevent="addHandle()" class="dmiux_button mt-2"><i class="fa fa-plus"></i> Add Handle </button>
                </div>
                <p id="err_msg"></p>
            </div>
            <div class="dmiux_popup__foot">
                <div class="dmiux_grid-row">
                    <div class="dmiux_grid-col"></div>
                    <div class="dmiux_grid-col dmiux_grid-col_auto">
                        <button class="dmiux_button" type="submit">Invite</button>
                    </div>
                </div>
            </div>
        </form>
	</div>
</script>