<div id="notifications-modal-template">
    <!-- Notifications Modal -->
    <div class="dmiux_popup" id="modal-notifications" role="dialog" tabindex="-1">
        <div class="dmiux_popup__window dmiux_popup__window_md" role="document">
            <div class="dmiux_popup__head">
                <h4 class="dmiux_popup__title">Notifications</h4>
                <button type="button" class="dmiux_popup__close"></button>
            </div>
            <div class="dmiux_popup__cont dmiux_popup_cont_nav" id="interactive-pane-counts">
                <div v-for="(notification, index) in notifications" class="card p-4 m-2 mt-4" v-if="! notification.is_dismissed">
                    <div class="dmiux_grid-row">
                        <div class="dmiux_grid-col dmiux_grid-col_auto">
                            <div class="p-1 mb-2 dmiux_badge-primary"
                                 v-if="notification.domain != null && notification.domain != ''"><small>{{ notification.domain }}</small></div>
                        </div>
                        <div class="dmiux_grid-col"><small>{{ notification.created_at_formatted }}</small></div>
                        <div class="dmiux_grid-col dmiux_grid-col_auto">
                            <span @click="dismissNotification(notification.id, index)" class="tooltip_pretty fas fa-times cursor-p" title="Dismiss notification"></span>
                        </div>
                    </div>
                    <p>
                        <span v-if="! notification.is_read"
                              @click="markRead(notification.id, index)"
                              :class="notification.type + '-color'"
                              class="unread-circle"></span> 
                        <span v-if="notification.hyperlink != null && notification.hyperlink != ''"
                              @click="setLink(notification.hyperlink, notification.id, index)"
                              class="tooltip_pretty cursor-p"
                              :title="notification.hyperlink">
                            <u><b>{{ notification.subject.substring(0, 33) }}<span v-if="notification.subject.length > 33">...</span></b></u>
                        </span>
                        <span v-else><b>{{ notification.subject.substring(0, 33) }}<span v-if="notification.subject.length > 33">...</span></b></span>
                    </p>
                    <div class="dmiux_grid-row">
                        <div class="dmiux_grid-col dmiux_grid-col_auto">
                            <p class="message-height" v-html="getNotificationMessage(notification.message, notification)"></p>                    
                        </div>
                    </div>
                    <div class="dmiux_grid-row">
                        <div class="dmiux_grid-col dmiux_grid-col_auto">
                            <span v-if="notification.message.length > 140" class="text-primary cursor-p" @click="setShowFull(notification)">Show <span v-if="notification.show_full != true">More</span><span v-else>Less</span></span>                    
                        </div>
                        <div class="dmiux_grid-col"></div>
                        <div class="dmiux_grid-col dmiux_grid-col_auto">
                            <span v-if="! notification.is_read" class="text-primary cursor-p" @click="markRead(notification.id, index)">Mark as Read</a></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="dmiux_popup__foot">
                <div class="dmiux_grid-row">
                    <div class="dmiux_grid-col dmiux_grid-col_auto">
                        <button type="button"
                                v-if="$root.is_unread == true"
                                class="dmiux_button dmiux_button_secondary"
                                @click="markAllRead()">Mark All as Read
                        </button>
                    </div>
                    <div class="dmiux_grid-col"></div>
                    <div class="dmiux_grid-col dmiux_grid-col_auto">
                        <button class="dmiux_button dmiux_button_secondary dmiux_popup__close_popup" type="button">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    var notifications = new Vue({
        el: '#notifications-modal-template',
        name: 'Notifications',
        data: {
            notifications: [],
            is_unread: false,
            tooltip_rendered: false,
            last_id: 0
        },
        mounted() {
            this.getUserNotifications();

            setInterval(function() {
                this.getUserNotifications(this.last_id);
            }.bind(this), 15000);
        },
        methods: {
            getUserNotifications(last_id = 0) {
                fetch(`/internal-api/v1/notifications?last_id=${last_id}`)
                    .then((response) => {
                        return response.json();
                    }).then((json) => {
                        if(json.status == "ok") {
                            if(last_id != 0) {
                                this.notifications = json.data.notifications.concat(this.notifications);
                            }
                            else {
                                this.notifications = json.data.notifications;
                            }
                            this.is_unread = false;
                            for(var i = 0; i < this.notifications.length; i++) {
                                var formated_date = this.notifications[i].created_at.replace(/-/g, '/');
                                this.notifications[i].created_at_formatted = formatLocaleDateTimeString(formated_date);
                                if(this.notifications[i].is_read == false) {
                                    this.is_unread = true;
                                }
                            }

                            this.last_id = json.data.last_id ?? 0;
                        }
                    });
            },
            markRead(id, index) {
                notifications.notifications[index].is_read = true;

                
                fetch(`/internal-api/v1/notifications/read?id=${id}`)
                    .then(function(response) {
                        return response.json();
                    }).then(function(json) {
                        notifications.getUserNotifications();
                    });
           },
           markAllRead() {
                notifications.is_unread = false;
                for(var index = 0; index < notifications.notifications.length; index++) {
                    if(! notifications.notifications[index].is_read) {
                        notifications.notifications[index].is_read = true;
                    }
                }

                fetch(`/internal-api/v1/notifications/read?all`)
                    .then(function(response) {
                        return response.json();
                    }).then(function(json) {
                        notifications.getUserNotifications();
                    });
           },
           dismissNotification(id, index) {
                this.notifications[index].is_dismissed = true;
                fetch(`/internal-api/v1/notifications/dismiss?id=${id}`)
                    .then(function(response) {
                        return response.json();
                    }).then(function(json) {
                        notifications.getUserNotifications();
                    });
           },
           getNotificationMessage(message, notification) {
                if(message.length > 140 && notification.show_full != true)
                    return message.substring(0, 140) + "...";
                else 
                    return message;
           },
           setShowFull(notification) {
                notification.show_full = !notification.show_full;
           },
           setLink(link, id, index) {
                notifications.markRead(id, index);
                window.location.href = link;
           }
        }
    });
</script>
