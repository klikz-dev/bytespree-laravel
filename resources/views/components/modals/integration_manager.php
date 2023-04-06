<script type="text/x-template" id="integration-manager-modal-template">
	<!-- Integration Manager Modal -->
	<div class="dmiux_popup" id="modal-integration-manager">
		<div class="dmiux_popup__window dmiux_popup__window_lg">
			<div class="dmiux_popup__head">
				<h4 class="dmiux_popup__title">Run Integrations</h4>
				<button type="button" class="dmiux_popup__close"></button>
			</div>
			<div class="dmiux_popup__cont">
				<table class="dmiux_data-table__table">
					<thead>
						<tr>
							<th></th>
							<th>Table</th>
						</tr>
					</thead>
					<tbody>
						<tr v-for="(job, index) in jobs" class="dmiux_input">
							<td>
								<div class="dmiux_data-table__actions" v-if="job.is_running == false">
									<div v-if="job.color != 'build_red'" @click="runIntegration(job.name, job.partner_integration_id, index)" class="dmiux_actionswrap dmiux_actionswrap--play"></div>
									<div v-else title="Table could not be built and cannot be run at the moment" class="dmiux_actionswrap dmiux_actionswrap--play-disabled"></div>
								</div>
								<div v-else>
									<i class="fas fa-spinner fa-pulse"></i>
								</div>
							</td>
							<td>{{ job.name }}</td>
						</tr>
					</tbody>
				</table>
			</div>
			<div class="dmiux_popup__foot">
				<div class="dmiux_grid-row">
                    <div class="dmiux_grid-col"></div>
                    <div class="dmiux_grid-col dmiux_grid-col_auto">
						<button class="dmiux_button dmiux_button_secondary dmiux_popup__close_popup" data-dismiss="modal" type="button">Close</button>
                    </div>
                </div>
			</div>
		</div>
	</div>
</script>
