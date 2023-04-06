team_channel.bind('running-jobs', function(data) {
    header_icons.setJobCount(data);
});

user_channel.bind('notifications', function(data) {
    header_icons.setNotificationCount(data);

    notifications.getUserNotifications();    
});

const header_icons = new Vue({
    name: "HeaderIcons",
    el: '#header-icons',
    data: {
        job_count: 0,
        notification_count: 0
    },
    methods: {
        openRunningJobs() {
            if(this.job_count > 0) {
                window.location.href = baseUrl + "/jobs";
            }
        },
        openNotifications() {
            if(this.notification_count > 0) {
                openModal("#modal-notifications");
            }
        },
        callInitial() {
            fetch(baseUrl + `/internal-api/v1/me/stats`)
                .then(FetchHelper.handleJsonResponse)
                .then(json => {
                    this.setJobCount(json.data.jobs_running);
                    this.setNotificationCount(json.data.notifications);
                })
        },
        getJobTooltip() {
            if (this.job_count == 0) {
                return '';
            }
            else {
                let word1 = 'jobs';
                let word2 = 'are';
                if (this.job_count == 1) {
                    word1 = 'job';
                    word2 = 'is';
                }
                return `${this.job_count} ${word1} ${word2} currently running`
            }
        },
        setJobCount(count) {
            if (this.job_count > 0) {
                $('#connector-syncs').tooltipster('destroy');
            }
            this.job_count = count;
            this.$emit('job-count-updated', count);
            this.$nextTick(() => {
                if (this.job_count > 0) {
                    $('#connector-syncs').tooltipster();
                }
            });
        },
        setNotificationCount(count) {
            this.notification_count = count;

            if(this.notification_count == 0) {
                $('#notification-bell').tooltipster('content', `No new notifications`);
            } else {
                let word = this.notification_count == 1 ? 'notification' : 'notifications';
                let num = this.notification_count > 100 ? '100+' : this.notification_count;
                $('#notification-bell').tooltipster('content', `You have ${num} ${word}`);
            }
        }
    },
    mounted() {
        $('#notification-bell').tooltipster();
        this.callInitial();
    }
});