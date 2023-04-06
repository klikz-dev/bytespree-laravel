<script type="text/x-template" id="component-add-connector">
    <div class="dmiux_grid-row">
        <div v-for="teamconnector in teamconnectors" v-if="teamconnector.visible == true && teamconnector.installed == false" class="dmiux_grid-col_xs-12 dmiux_grid-col_sm-6 dmiux_grid-col_md-4 dmiux_grid-col_4">
            <div class="text-center">
                <div class="card dmiux_card-with-hdr__container mx-2">
                    <a href="#" @click="upload(teamconnector.id)">
                        <div class="dmiux_card_header">{{ teamconnector.name }}</div>
                        <div class="card-body vertical-center">
                            <img :src="'/connectors/' + teamconnector.id + '/logo?source=external&v3.0.1'" alt="Avatar" class="img-fluid"/>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</script>
