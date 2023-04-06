<!-- Join Team Modal -->
<div class="dmiux_popup" id="modal-join_team" role="dialog" tabindex="1">
    <div class="dmiux_popup__window" role="document">
        <div class="dmiux_popup__head">
            <h4 class="dmiux_popup__title">Accept Team Invitation</h4>
            <button type="button" class="dmiux_popup__close" @click="clearValues()"></button>
        </div>
        <div class="dmiux_popup__cont">
            <div class="dmiux_input">
                <input style="grid-row: 1" type="text" class="dmiux_input__input" placeholder="Enter invitation code" v-model="invitation_code" autocomplete="off" />           
            </div>
            <div v-if="has_email_changed" class="dmiux_input pt-2">
                <input type="text" class="dmiux_input__input" placeholder="Email change code" v-model="email_code" autocomplete="off" />           
            </div>
        </div>
        <div class="dmiux_popup__foot">
            <div class="dmiux_grid-row">
                <div class="dmiux_grid-col"></div>
                <div class="dmiux_grid-col dmiux_grid-col_auto">
                    <button class="dmiux_button dmiux_button_secondary dmiux_popup__close_popup" type="button" @click="clearValues()">Cancel</button>
                </div>
                <div class="dmiux_grid-col dmiux_grid-col_auto pl-0">
                    <button class="dmiux_button" type="button" @click="joinTeam()">Join</button>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    var join_team = new Vue({
        el: '#modal-join_team',
        name: 'JoinTeam',
        data: {
            invitation_code : '',
            has_email_changed: false,
            email_code: ''
        },
        watch: {
            invitation_code() {
                this.has_email_changed = false;
                this.email_code = '';
            }
        },
        methods: {
            joinTeam() {
                if(this.invitation_code == '') {
                    alert('You must provide an invitation code.');
                    return;
                }
                
                let options = FetchHelper.buildJsonRequest({
                    invitation_code: this.invitation_code,
                    email_code: this.email_code
                }, 'PUT');

                fetch(`/internal-api/v1/me/join`, options)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        $('.dmiux_popup__close_popup').trigger('click');
                        notify.success(json.message);

                        var team = json.data.team;
                        var teams = json.data.teams;

                        var html = "";
                        for(var index = 0; index < teams.length; index++)
                        {
                            if(team == teams[index]["domain"])
                                html += '<a href="<?php echo rtrim(config("orchestration.url"), "/"); ?>/app/team/' + teams[index]['domain'] + '" class="dmiux_app-nav__link dmiux_app-nav__link_active">' + teams[index]['domain'] + '</a>'
                            else 
                                html += '<a href="<?php echo rtrim(config("orchestration.url"), "/"); ?>/app/team/' + teams[index]['domain'] + '" class="dmiux_app-nav__link">' + teams[index]['domain'] + '</a>'
                        }
                        document.getElementById("user_teams").innerHTML = html;
                        this.invitation_code = '';
                    })
                    .catch((error) => {
                        if(error.json != null) {
                            if(typeof error.json.data === 'object' && error.json.data !== null && error.json.data.email_changed === true) {
                                this.has_email_changed = true;
                            }

                            notify.danger(error.json.message);
                        } else {
                            ResponseHelper.handleErrorMessage(error, 'An error occurred. Unable to join team at the moment.');
                        }
                    });
            },
            clearValues()
            {
                this.invitation_code = '';
            }
        }
    })
</script>
