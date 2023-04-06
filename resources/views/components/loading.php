<div id="loading">
    <div id="explorer-loader-backdrop" class="loader"></div>
    <div id="dmiux_overlay" class="dmiux_overlay"></div>
</div>
<script>
    var loading = new Vue({
        el: "#loading",
        name: 'Loader',
        methods: {
            start: function() {
                $(".loader").show();
            },
            stop: function() {
                $(".loader").hide();
            }
        }
    });
</script>