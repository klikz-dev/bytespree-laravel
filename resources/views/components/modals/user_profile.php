<!-- Update password Modal -->
<div class="dmiux_popup" id="modal-user_profile" role="dialog" tabindex="-1">
    <div class="dmiux_popup__window dmiux_popup__window_md" role="document">
        <div class="dmiux_popup__head">
            <h4 class="dmiux_popup__title">My Account</h4>
            <button type="button" class="dmiux_popup__close" @click="resetChanges()"></button>
        </div>
        <form id="form-user_profile" onSubmit="event.preventDefault()" autocomplete="off">
            <div class="dmiux_popup__cont">
                <div v-if="message != ''" :class="message_class" class="alert">{{ message }}</div>
                <div class="dmiux_grid-row">
                    <div class="dmiux_grid-col dmiux_grid-col_6">
                        <div class="dmiux_input">
                            <label class="dmiux_popup__label" for="input-first_name">First Name</label>
                            <input @input="name_changed = true" class="dmiux_input__input" id="input-first_name" v-model="first_name" maxlength="200">
                        </div>                
                    </div>
                    <div class="dmiux_grid-col dmiux_grid-col_6">
                        <div class="dmiux_input">
                            <label class="dmiux_popup__label" for="input-last_name">Last Name</label>
                            <input @input="name_changed = true" class="dmiux_input__input" id="input-last_name" v-model="last_name" maxlength="200">
                        </div>                
                    </div>
                </div>
                <div v-if="! is_sso" class="dmiux_grid-row">
                    <div class="dmiux_grid-col dmiux_grid-col_6">
                        <div class="dmiux_input">
                            <label class="dmiux_popup__label" for="input-current_password">Current Password</label>
                            <input type="password" class="dmiux_input__input" id="input-current_password" v-model="current_password" autocomplete="current-password">
                        </div>                
                    </div>
                    <div class="dmiux_grid-col dmiux_grid-col_6">
                        <div class="dmiux_input">
                            <label class="dmiux_popup__label" for="input-new_password">New Password</label>
                            <input @blur="verifyPasswordStrength()" type="password" class="dmiux_input__input" id="input-new_password" v-model="new_password" autocomplete="new-password">
                        </div>                
                    </div>
                </div>
                <div v-if="! is_sso" class="dmiux_grid-row">
                    <div class="dmiux_grid-col dmiux_grid-col_12">
                        <div class="dmiux_input">
                            <label class="dmiux_popup__label" for="input-email">Email Address</label>
                            <input @blur="verifyEmail()" type="email" class="dmiux_input__input" id="input-email" v-model="email" autocomplete="username">
                        </div>                
                    </div>
                </div>
                <div class="dmiux_grid-row">
                    <div class="dmiux_grid-col dmiux_grid-col_12">
                        <div class="dmiux_input">
                            <label class="dmiux_popup__label" for="input-phone">Phone Number</label>
                            <input @keyup="verifyPhone()" type="tel" class="dmiux_input__input" id="input-phone" v-model="phone">
                        </div>                
                    </div>
                </div>
                <div v-if="! is_sso" class="dmiux_grid-row ml-0">
                    <div class="dmiux_grid-row">
                        <div class="dmiux_grid-col dmiux_grid-col_auto">
                            <div class="dmiux_checkbox">
                                <input @change="verifyDfa()" v-model="dfa_enabled" type="checkbox" class="dmiux_checkbox__input">
                                <div class="dmiux_checkbox__check"></div>
                                <div class="dmiux_checkbox__label">Use Dual Factor Authentication?</div>
                            </div>              
                        </div>
                    </div>
                    <div v-if="dfa_enabled == true" class="dmiux_grid-row">
                        <div class="dmiux_grid-col dmiux_grid-col_auto">
                            <label class="dmiux_popup__label">Select Your Preference</label>           
                        </div>
                        <div class="dmiux_grid-col dmiux_grid-col_auto">
                            <div class="dmiux_radio">
                                <input v-model="dfa_preference" value="email" type="radio" name="radio-email_preference" class="dmiux_radio__input">
                                <div class="dmiux_radio__check"></div>
                                <div class="dmiux_radio__label">Email</div>                        
                            </div>                
                        </div>
                        <div class="dmiux_grid-col dmiux_grid-col_auto">
                            <div class="dmiux_radio">
                                <input v-model="dfa_preference" value="mobile" type="radio" name="radio-email_preference" class="dmiux_radio__input">
                                <div class="dmiux_radio__check"></div>
                                <div class="dmiux_radio__label">Mobile</div>                        
                            </div>              
                        </div>
                    </div>
                </div>
                <div class="dmiux_grid-row">
                    <div class="dmiux_grid-col dmiux_grid-col_auto">
                        <div class="dmiux_checkbox">
                            <input @change="verifyTeam(); updateTeamMessage();" v-model="has_default_team" type="checkbox" class="dmiux_checkbox__input">
                            <div class="dmiux_checkbox__check"></div>
                            <div class="dmiux_checkbox__label">Set Default Team?</div>
                        </div>              
                    </div>
                </div>
                <div v-if="has_default_team == true" class="dmiux_grid-row">
                    <div class="dmiux_grid-col dmiux_grid-col_auto">
                        <label class="dmiux_popup__label" for="select-preferred_team">Select Your Preferred Team</label>           
                    </div>
                    <div class="dmiux_select dmiux_grid-col dmiux_grid-col_auto">
                        <select @change="updateTeamMessage()" class="dmiux_select__select" v-model="team_preference" id="select-preferred_team">
                            <option value="">Choose a Team</option>
                            <option v-for="team in teams" :value="team" :selected="team == team_preference">{{ team }}</option>
                        </select>
                        <div class="dmiux_select__arrow mr-2"></div>
                    </div>
                </div>
            </div>
            <div class="dmiux_popup__foot">
                <div class="dmiux_grid-row">
                    <div class="dmiux_grid-col"></div>
                    <div class="dmiux_grid-col dmiux_grid-col_auto">
                        <button class="dmiux_button dmiux_button_secondary" type="button" @click="resetChanges()">Cancel</button>
                    </div>
                    <div class="dmiux_grid-col dmiux_grid-col_auto pl-0">
                        <button class="dmiux_button" type="button" @click="updateUser()">Update</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<script>
    var update_password = new Vue({
        el: "#modal-user_profile",
        name: 'UserProfile',
        data: {
            current_password: "",
            new_password: "",
            message_class: "",
            has_default_team: false,
            team_preference: '<?php echo session()->get("team_preference"); ?>',
            previous_team_preference: "",
            dfa_enabled: false,
            old_dfa_enabled: null,
            dfa_preference: '<?php echo session()->get("dfa_preference"); ?>',
            previous_dfa_preference: '<?php echo session()->get("dfa_preference"); ?>',
            dfa_email: false,
            dfa_mobile: false,
            first_name: '<?php echo session()->get("first_name"); ?>',
            last_name: '<?php echo session()->get("last_name"); ?>',
            email: '<?php echo session()->get("email"); ?>',
            phone: '<?php echo session()->get("phone"); ?>',
            password_changed: false,
            dfa_changed: false,
            name_changed: false,
            email_changed: false,
            mobile_changed: false,
            team_preference_changed: false,
            message: "",
            password_strong: false,
            teams: [],
            is_sso: <?php echo session()->get('sso_provider_id') > 0 ? 'true' : 'false'; ?>,
        },
        watch: {
            dfa_preference() {
                this.dfa_changed = true;
            },
            team_preference() {
                this.team_preference_changed = true;
            }
        },
        methods: {
            verifyPasswordStrength() {
                this.password_changed = false;
                this.password_strong = true;

                if(this.new_password.trim() == "") {
                    return
                }

                this.message = "";
                if(this.new_password.length < 8) {
                    if(this.new_password != "")
                    {
                        this.message = "Your password must be at least 8 characters in length.";
                        this.message_class = 'alert-danger'
                        this.password_strong = false;
                    }
                }

                if(this.new_password.length >= 32) {
                    this.message = "Your password must be no longer than 32 characters.";
                    this.message_class = 'alert-danger'
                    this.password_strong = false;
                }

                var hasNumber = /\d/;
                if(! hasNumber.test(this.new_password)) {
                    if(this.new_password != "")
                    {
                        this.message = "Your password must contain at least one number.";
                        this.message_class = 'alert-danger'
                        this.password_strong = false;
                    }
                }

                if(! this.password_strong) {
                    return
                }

                this.password_changed = true;
            },
            verifyEmail() {
                this.message = "";
                var regex = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
                if(regex.test(String(this.email).toLowerCase())) {
                    this.email_changed = true;
                    return true;
                }
                else if (this.email != "") {
                    this.email_changed = false;
                    this.message = "Your email is not valid.";
                    this.message_class = 'alert-danger'
                    return false;
                }
                else {
                    this.email_changed = false;
                    this.message = "Email is a required field.";
                    this.message_class = 'alert-danger'
                    return false;
                }
            },
            verifyPhone() {
                this.message = "";
                this.phone = this.phone.replace(/\s/g, "");

                var regex = /^\d+$/;
                if(regex.test(String(this.phone))) {
                    this.phone_changed = true;
                    return true;
                }
                else if(this.phone != "") {
                    this.phone_changed = false;
                    this.message = "Your phone number is not valid. (Numbers only)";
                    this.message_class = 'alert-danger';
                    this.phone_changed = true;
                    return false;
                }
                else {
                    if(this.dfa_preference == "mobile") {
                        this.message = "A phone number is required to use mobile dual factor authentication.";
                        this.message_class = 'alert-danger'
                        this.dfa_preference = "email";
                        return false;
                    }

                    this.phone_changed = true;
                    return true;
                } 
            },
            verifyDfa() {
                if(this.dfa_enabled != true) {
                    this.dfa_preference = "";
                }
                else {
                    this.dfa_preference = "email";
                }
            },
            setDfaEnabled() {
                if(this.old_dfa_enabled == null)
                {
                    if(this.previous_dfa_preference != '')
                        this.old_dfa_enabled = true;
                    else
                        this.old_dfa_enabled = false;
                }
                

                if(this.dfa_preference == "") {
                    this.dfa_enabled = false;
                }
                else {
                    this.dfa_enabled = true;
                }
            },
            verifyTeam() {
                if(this.has_default_team != true) {
                    this.team_preference = "";
                }
            },
            updateTeamMessage() {
                if(this.message != "")
                {
                    this.message = "";
                    this.message_class = "";
                }
                
            },
            setTeamPreferenceEnabled() {
                if (this.team_preference == "") {
                    this.has_default_team = false;
                }
                else {
                    this.has_default_team = true;
                    this.previous_team_preference = this.team_preference;
                }
            },
            getTeams() {
                fetch(`/internal-api/v1/me/teams`)
                    .then(response => response.json())
                    .then(resp => {
                        if(resp.status == "ok") {
                            for (var i=0; i<resp.data.length; i++) {
                                this.teams.push(resp.data[i].domain);
                            }
                        }
                    });
            },
            updateUser() {
                this.message = "";

                if(this.password_changed == true) {
                    this.verifyPasswordStrength();

                    if(this.current_password.trim() == "") {
                        this.message = "Current password required to change password.";
                        this.message_class = "alert-danger";
                        return;
                    }

                    if(this.password_strong != true) {
                        this.message_class = "alert-danger";
                        return;
                    }
                }

                if(this.email_changed == true) {
                    if(this.verifyEmail() != true) {
                        this.message_class = "alert-danger";
                        return;
                    }
                }

                if(this.phone_changed == true) {
                    if(this.verifyPhone() != true) {
                        this.message_class = "alert-danger";
                        return;
                    }
                }
                else
                {
                    this.phone = '<?php echo session()->get("phone"); ?>'
                }

                if (this.has_default_team == true && (this.team_preference == "" || this.team_preference == null)) {
                    this.message = "You checked default team but did not choose a team.";
                    this.message_class = "alert-danger";
                    return false;
                }

                if(this.dfa_preference == "mobile" && this.phone == "") {
                    this.message = "A phone number is required to use mobile dual factor authentication.";
                    this.message_class = "alert-danger";
                    this.dfa_preference = "email";
                    return false;
                }

                this.previous_dfa_preference = this.dfa_preference;


                const options = FetchHelper.buildJsonRequest({
                    current_password: this.current_password,
                    password: this.new_password,
                    dfa_preference: this.dfa_preference,
                    team_preference: this.team_preference,
                    first_name: this.first_name,
                    last_name: this.last_name,
                    email: this.email,
                    phone: this.phone,
                    password_changed: this.password_changed,
                    dfa_changed: this.dfa_changed,
                    team_preference_changed: this.team_preference_changed,
                    name_changed: this.name_changed,
                    email_changed: this.email_changed,
                    phone_changed: this.phone_changed,
                }, 'put');

                fetch(`/internal-api/v1/me`, options)
                    .then(response => response.json())
                    .then(resp => {
                        if(resp.status == "ok") {
                            this.current_password = "";
                            this.new_password = "";
                            this.password_changed = false;
                            this.dfa_changed = false;
                            this.team_preference_changed = false;
                            this.name_changed = false;
                            this.email_changed = false;
                            this.phone_changed = false;
                            closeModal('#modal-user_profile');
                        }

                        for(var index = 0; index < resp.data.length; index++)
                        {
                            if(resp.data[index].status == "ok")
                                notify.success(resp.data[index].message);
                            else
                                notify.danger(resp.data[index].message);
                        }
                    });
            },
            resetChanges() {
                this.message = '';
                this.message_class = '';

                if (this.team_preference_changed) {
                    if ((this.previous_team_preference == "") || (this.previous_team_preference == null)) {
                        this.has_default_team = false;
                    }
                    else {
                        this.has_default_team = true;
                    }
                    this.team_preference = this.previous_team_preference;
                    this.team_preference_changed = false;
                }
                if (this.dfa_changed)
                {
                    if (this.dfa_preference != this.previous_dfa_preference)
                    {
                        this.dfa_preference = this.previous_dfa_preference;
                        if (this.dfa_preference == "")
                        {
                            this.dfa_enabled = false;
                        }
                    }
                }
                if(this.old_dfa_enabled != null)
                {
                    this.dfa_enabled = this.old_dfa_enabled;
                    this.old_dfa_enabled = null;
                }
                if(this.phone_changed)
                {
                    this.phone = '<?php echo session()->get("phone"); ?>';
                }
                
                this.email = '<?php echo session()->get("email"); ?>';

                this.current_password = "";
                this.new_password = "";

                if(this.name_changed)
                {
                    this.first_name = '<?php echo session()->get("first_name"); ?>';
                    this.last_name = '<?php echo session()->get("last_name"); ?>';
                }

                closeModal('#modal-user_profile');
            }
        },
        mounted()
        {
            this.setDfaEnabled();
            this.getTeams();
            this.setTeamPreferenceEnabled();
        }
    })
</script>