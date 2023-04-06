<script type="text/x-template" id="invite-user-modal-template">
	<!-- Invite User Modal -->
	<div class="dmiux_popup" id="modal-invite-user">
		<div class="dmiux_popup__window">
			<div class="dmiux_popup__head">
				<h4 class="dmiux_popup__title">Invite New User</h4>
				<button type="button" class="dmiux_popup__close"></button>
			</div>
            <form id="form-invite_user" @submit.prevent="checkInvited(true)" autocomplete="off">
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
                            <button class="dmiux_button dmiux_button_secondary dmiux_popup__close_popup" data-dismiss="modal" type="button">Close</button>
                        </div>
                        <div class="dmiux_grid-col dmiux_grid-col_auto pl-0">
                            <button class="dmiux_button" type="submit">Invite</button>
                        </div>
                    </div>
                </div>
            </form>
		</div>
	</div>
</script>

<script>
    var inviteUserModal = Vue.component('invite-user-modal', {
        template: '#invite-user-modal-template',
        data: function() {
            return {
                inviteUser: "email",
                emails: [],
                handles: [],
                array_send: [],
                results: [],
            }
        },
        methods: {
            changeFunction: function() {
                this.emails = [];
                this.handles = [];
            },
            initFields: function() {
                this.inviteUser = "email";
                this.emails = [];
                this.handles = [];
            },
            addEmail: function() {
                this.emails.push({email:""});
                this.checkInvited(false);
            },
            addHandle: function() {
                this.handles.push({handle:""});
                this.checkInvited(false);
            },
            checkInvited: function(submitted) {
                if (this.inviteUser == "email") {
                    if (this.emails.length == 0) {
                        notify.danger('Please enter at least one email to invite to the team.');
                        return;
                    }

                    var starting_length = this.emails.length;
                    this.emails = this.emails.filter((email) => {
                        return this.emailFilterLogic(email, submitted)
                    });

                    var dupe_check_length = this.emails.length;
                    this.emails = this.emails.map(email => email.email)
                        .map((email, i, final) => final.indexOf(email) === i && i)
                        .filter(obj => this.emails[obj])
                        .map(email => this.emails[email]);

                    if(dupe_check_length != this.emails.length) {
                        notify.danger("Duplicate emails were detected and automatically removed.");
                    }
                    
                    if (submitted && this.emails.length == starting_length) {
                        this.sendInvitationEmail();
                    }
                }
                else if (this.inviteUser == "handle") {
                    if (this.handles.length == 0) {
                        notify.danger('Please enter at least one handle to invite to the team.');
                        return;
                    }

                    var starting_length = this.handles.length;
                    this.handles = this.handles.filter((handle) => {
                        var check_existing = this.$root.users.filter((user) => {
                            if(user.user_handle == handle.handle) {
                                return user;
                            }
                        });

                        var check_existing_deleted = this.$root.deleted_users.filter((deleted_user) => {
                            if(deleted_user.user_handle == handle.handle) {
                                return deleted_user;
                            }
                        });
                        
                        if(check_existing.length > 0) {
                            if(check_existing[0].is_pending == true) {
                                notify.danger(`User ${handle.handle} has already been invited`);
                            } else {
                                notify.danger(`User ${handle.handle} is already on the team`);
                            }

                            return;
                        }

                        if(check_existing_deleted.length > 0) {
                            notify.danger(`User ${handle.handle} was previously on this team. Please contact support to readd them.`);
                            return;
                        }

                        if(submitted == false || handle.handle != '') {
                            return handle;
                        }
                    });

                    var dupe_check_length = this.handles.length;
                    this.handles = this.handles.map(handle => handle.handle)
                        .map((handle, i, final) => final.indexOf(handle) === i && i)
                        .filter(obj => this.handles[obj])
                        .map(handle => this.handles[handle]);

                    if(dupe_check_length != this.handles.length) {
                        notify.danger("Duplicate handles were detected and automatically removed.");
                    }
                    
                    if (submitted && this.handles.length == starting_length) {
                        let options = FetchHelper.buildJsonRequest({
                           handles: this.handles
                        });

                        fetch(`${baseUrl}/Users/checkHandles`, options)
                            .then(FetchHelper.handleJsonResponse)
                            .then(json => {
                                var starting_length = this.handles.length;
                                this.handles = this.handles.filter((handle) => {
                                    var email = json.data.map((success) => {
                                        if(success.handle == handle.handle) {
                                            return {
                                                email: success.email_address,
                                                handle: success.handle
                                            };
                                        }
                                    });

                                    return this.emailFilterLogic(email[0], submitted);
                                });

                                if(starting_length == this.handles.length) {
                                    this.sendInvitationEmail();
                                }
                            })
                            .catch((error) => {
                                if(error.json != null) {
                                    this.handles = this.handles.filter((handle) => {
                                        if(error.json.data.failed.includes(handle.handle)) {
                                            notify.danger(`User ${handle.handle} does not exist as a user`);
                                            return;
                                        }

                                        var email = error.json.data.success.map((success) => {
                                            if(success.handle == handle.handle) {
                                                return {
                                                    email: success.email_address,
                                                    handle: success.handle
                                                };
                                            }
                                        });

                                        return this.emailFilterLogic(email[0], submitted);
                                    });
                                }
                            });
                    }
                }
            },
            emailFilterLogic(email, submitted) {
                var notify_name = email.email;
                if('handle' in email) {
                    notify_name = email.handle;
                }

                var check_existing = this.$root.users.filter((user) => {
                    if(user.email == email.email) {
                        return user;
                    }
                });

                var check_existing_deleted = this.$root.deleted_users.filter((deleted_user) => {
                    if(deleted_user.email == email.email) {
                        return deleted_user;
                    }
                });
                
                if(check_existing.length > 0) {
                    if(check_existing[0].is_pending == true) {
                        notify.danger(`User ${notify_name} has already been invited`);
                    } else {
                        notify.danger(`User ${notify_name} is already on the team`);
                    }

                    return;
                }

                if(check_existing_deleted.length > 0) {
                    notify.danger(`User ${notify_name} was previously on this team. Please contact support to readd them.`);
                    return;
                }

                if(submitted == false || email.email != '') {
                    return email;
                }
            },
            removeEmail: function(index) {
                this.emails.splice(index, 1);
            },
            removeHandle: function(index) {
                this.handles.splice(index, 1);
            },
            sendInvitationEmail: function() {
                this.$parent.loading(true);
                this.array_send = [];
                if (this.inviteUser == "email") {
                    for (var i=0; i<this.emails.length; i++) {
                        this.array_send.push(this.emails[i].email);
                    }
                }
                else {
                    for (var i=0; i<this.handles.length; i++) {
                        this.array_send.push(this.handles[i].handle);
                    }
                }

                let options = FetchHelper.buildJsonRequest({
                    invites: this.array_send,
                    type: this.inviteUser
                });

                this.initFields();
                fetch(`${baseUrl}/internal-api/v1/admin/users/invite`, options)
                .then(response => response.json())
                .then(json => {
                    this.results = json.message;
                    if(json.status == "error") {
                        for (var i=0; i<this.results.length; i++) {
                            var msg = this.results[i].message + " for " + this.results[i].invitee;
                            if (this.results[i].status == "error") {
                                notify.danger(msg);
                            }
                            else {
                                notify.success(msg);
                            }
                        }
                    }
                    else {
                        notify.success('Your invitation(s) were sent.');
                    }
                    this.$parent.loading(false);
                    this.closeInviteUserModal();
                });
            },
            closeInviteUserModal: function() {
                $('.dmiux_popup__close_popup').trigger('click');
                app.getUsers();
            },
            cleanupHandle: function(index) {
                this.handles[index].handle = this.handles[index].handle.toLowerCase();
                this.handles[index].handle = this.handles[index].handle.trim();
                this.handles[index].handle = this.handles[index].handle.replace(" ", "");

                var pattern = /[^a-z0-9]/g;
                var result = this.handles[index].handle.match(pattern);
                if (result != "") {
                    this.handles[index].handle = this.handles[index].handle.replace(result, "");
                }

            }
        }
    });
</script>