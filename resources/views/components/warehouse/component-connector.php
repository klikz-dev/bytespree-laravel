<script type="text/x-template" id="wizard-connector-template">
    <!-- Integration Settings Modal -->
    <div class="dmiux_input mt-2 mb-1">
        <div class="grid-col grid-col_xs-12">
            <div class="mt025 grid-row">
                <div class="row">
                    <div v-for="teamconnector in teamconnectors" class="col-md-6">
                        <div class="text-center">
                            <div class="card dmiux_card-with-hdr__container mx-2">
                                <a href="#" @click="upload(teamconnector.id)">
                                    <div class="dmiux_card_header">{{ teamconnector.name }}</div>
                                    <div class="card-body vertical-center">
                                        <img :src="'/connectors/' + teamconnector.id  + '/logo?source=external&v3.0.1'" alt="Avatar" class="img-fluid"/>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>   
            </div>
        </div>
    </div>
</script>
