<?php echo view("components/head"); ?>
<?php echo view("components/component-toolbar"); ?>
<?php echo view("components/modals/add_system_notification"); ?>

<div id="app">
    <toolbar
        :buttons="toolbar.buttons"
        :breadcrumbs="toolbar.breadcrumbs">
    </toolbar>
    <div class="dmiux_content">
        <?php echo view('components/admin/menu', ['selected' => 'system-notifications']); ?>
        <div class="dmiux_grid-cont dmiux_grid-cont_fw dmiux_data-table dmiux_data-table__cont">
            <div v-if="currentUser.is_admin == true && system_notifications.length == 0 && is_loading == false" class="dmiux_grid-col dmiux_grid-col_12 order-lg-0 order-2 px-0 px-lg-2">
                <div class="alert alert-info">
                    No system notifications have been added yet. Would you like to <a href="#" @click="add()">add one</a>?
                </div>
            </div>
            <table v-if="currentUser.is_admin == true && system_notifications.length > 0" class="dmiux_data-table__table">
                <thead>
                    <tr>
                        <th></th> 
                        <th>Channel</th>
                        <th>Notification Type</th>
                        <th>Notification Details</th>
                        <th>Last Sent</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(notification_subscription, index) in system_notifications" class="dmiux_input">
                        <td style="width: 20px !important;">
                            <div class="dmiux_data-table__actions">
                                <div class="dmiux_actionswrap dmiux_actionswrap--edit" @click="edit(notification_subscription)"  data-toggle="tooltip" title="Edit this subscription"></div>
                                <div class="dmiux_actionswrap dmiux_actionswrap--bin"  @click="remove(notification_subscription.id)" data-toggle="tooltip" title="Delete this subscription"></div>
                            </div>
                        </td>
                        <td>{{ notification_subscription.channel.name }}</td>
                        <td>{{ notification_subscription.type.name }}</td>
                        <td>{{ notification_subscription.descriptor }}</td>
                        <td>{{ notification_subscription.most_recent_history ? convertToLocal(notification_subscription.most_recent_history.created_at) : null }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <add-system-notification ref="add"
              :is_editing="is_editing"
              :current_notification="current_notification"
              :channels="channels"
              :types="types"
              @notification-added="getSubscriptions"
              @loading="loading">
    </add-system-notification>
</div>
<script>
    var toolbar = Vue.component('toolbar', {
        template: '#component-toolbar',
        props: [ 'breadcrumbs', 'buttons', 'record_counts' ],
        methods: {
        }
    });

    var app = new Vue({
        el: '#app',
        name: "System Notifications",
        data: {
            toolbar: {
                "breadcrumbs": [],
                "buttons": [
                    {
                        "onclick": "app.add()",
                        "text": "Add Notification&nbsp; <span class=\"fas fa-plus\"></span>",
                        "class": "dmiux_button dmiux_button_secondary"
                    }
                ]
            },
            current_notification: null,
            channels: [],
            types: [],
            is_editing: false,
            is_loading: true,
            currentUser : {
                "is_admin" : false
            },
            system_notifications: []
        },
        components: {
            'toolbar': toolbar,
            'add_system_notification': add_system_notification
        },
        methods: {
            loading: function(status) {
                if(status === true) {
                    $(".loader").show();
                } else {
                    $(".loader").hide();
                }
            },
            getSubscriptions() {
                this.loading(true);
                fetch(baseUrl + "/internal-api/v1/admin/system-notifications/subscriptions")
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.loading(false);
                        this.system_notifications = json.data;
                        this.is_loading = false;
                    })
                    .catch((error) => {
                        this.loading(false);
                        ResponseHelper.handleErrorMessage(error, 'System notifications could not be loaded.');
                    });
            },
            getChannels() {
                this.loading(true);
                fetch(baseUrl + "/internal-api/v1/admin/system-notifications/channels")
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.loading(false);
                        this.channels = json.data;
                    })
                    .catch((error) => {
                        this.loading(false);
                        ResponseHelper.handleErrorMessage(error, 'Could not fetch available channels.');
                    });
            },
            getTypes() {
                this.loading(true);
                fetch(baseUrl + "/internal-api/v1/admin/system-notifications/types")
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.loading(false);
                        this.types = json.data;
                    })
                    .catch((error) => {
                        this.loading(false);
                        ResponseHelper.handleErrorMessage(error, 'Could not fetch available notification types.');
                    });
            },
            getCurrentUser: function() {
                fetch(baseUrl + "/internal-api/v1/me")
                    .then(response => response.json())
                    .then(json => {
                        this.currentUser = json.data;
                    })
            },
            getBreadcrumbs: function() {
                fetch(baseUrl + "/internal-api/v1/crumbs")
                    .then(response => response.json())
                    .then(json => {
                        this.toolbar.breadcrumbs = json.data;
                    });
            },
            add: function() {
                this.is_editing = false;
                this.current_notification = null;
                this.$refs.add.reset();
                this.modalOpen();
            },
            remove(subscription_id) {
                if (! confirm("Are you sure you want to delete this notification subscription? This cannot be undone")) {
                    return;
                }

                this.loading(true);

                fetch(baseUrl + `/internal-api/v1/admin/system-notifications/subscriptions/${subscription_id}`, FetchHelper.buildJsonRequest({}, 'delete'))
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        notify.success(json.message);
                        this.getSubscriptions();
                    })
                    .catch((error) => {
                        ResponseHelper.handleErrorMessage(error, 'An error occurred.');
                        this.getSubscriptions();
                    });
            },
            edit(subscription) {
                this.loading(true);
                fetch(baseUrl + `/internal-api/v1/admin/system-notifications/subscriptions/${subscription.id}`)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.current_notification = json.data;
                        this.is_editing = true;
                        this.$refs.add.is_editing = true;
                        this.modalOpen();
                        this.loading(false);
                    })
                    .catch((error) => {
                        this.loading(false);
                        ResponseHelper.handleErrorMessage(error, 'An error occurred.');
                    });
            },
            modalOpen() {
                $(document).on("mousedown", "#dmiux_body", this.modalClose);
                $(document).on("keydown", this.modalClose);
                openModal("#modal-add_system_notification");
            },
            modalClose(event) {
                event.stopPropagation();
                if(event.key != undefined) {
                    if(event.key != 'Escape') { // not escape
                        return;
                    }
                } else {
                    var clicked_element = event.target;
                    if (clicked_element.closest(".dmiux_popup__window")) {
                        // You clicked inside the modal
                        if (clicked_element . id != "x-button" && clicked_element . id != "cancel-button") {
                            return;
                        }
                    }
                }
                // You clicked outside the modal
                $(document).off("mousedown", "#dmiux_body", this.modalClose);
                $(document).off("keydown", this.modalClose);

                // execute any special logic to reset/clear modal
                closeModal('#modal-add_system_notification');
            },
            convertToLocal(timestamp) {
                if (timestamp == null) {
                    return '';
                }

                return DateHelper.convertToAndFormatLocaleDateTimeString(timestamp);
            }
        },
        mounted() {
            this.getCurrentUser();
            this.getBreadcrumbs();
            this.getSubscriptions();
            this.getTypes();
            this.getChannels();
        }
    })
</script>
<?php echo view("components/foot"); ?>
