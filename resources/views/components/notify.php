<div id="notify"></div>
<script>
    var notify = new Vue({
        el: '#notify',
        name: 'Notify',
        methods: {
            prepare: function(message) {
                var splitarr = message.split(" ");
                var newstr = "";
                for(i = 0; i < splitarr.length; i++) {
                    if(splitarr[i].length > 20) {
                        splitarr[i] = splitarr[i].substring(0, 20) + "... ";
                    }
                    else {
                        splitarr[i] = splitarr[i] + " ";
                    }

                    newstr = String(newstr) + String(splitarr[i]);
                }

                newstr = newstr.trim();
                if(newstr.length > 120) {
                    newstr = newstr.substring(0, 120) + "...";
                }

                return newstr;
            },
            send: function(message, status) {
                message = this.prepare(message);
                notyf.open({
                    type: status,
                    message: message
                });
            },
            info: function(message) {
                message = this.prepare(message);
                notyf.open({
                    type: 'info',
                    message: message
                });
            },
            danger: function(message, sendToConsole = false) {
                message = this.prepare(message);
                notyf.open({
                    type: 'danger',
                    message: message
                });
                if (sendToConsole) console.error(`notify.danger: ${message}`);
            },
            success: function(message) {
                message = this.prepare(message);
                notyf.open({
                    type: 'success',
                    message: message
                });
            },
            warning: function(message) {
                message = this.prepare(message);
                notyf.open({
                    type: 'warning',
                    message: message
                });
            }
        }
    });
</script>