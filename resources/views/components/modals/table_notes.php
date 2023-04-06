<script type="text/x-template" id="table-notes-template">	
	<!-- Table Notes Modal -->
	<div class="dmiux_popup" id="modal-table_notes" role="dialog" tabindex="-1">
		<div class="dmiux_popup__window dmiux_popup__window_lg" role="document">
			<div class="dmiux_popup__head">
				<h4 v-if="showing_note_form != true" class="dmiux_popup__title">Table Notes</h4>
				<h4 v-else-if="is_editing" class="dmiux_popup__title">Editing a Note</h4>
				<h4 v-else class="dmiux_popup__title">Adding a Note</h4>
				<button type="button" class="dmiux_popup__close" id="x-button" @click="modalClose($event)"></button>
			</div>
			<div v-if="showing_note_form" class="dmiux_popup__cont">
				<div class="dmiux_popup__tab visible" id="notes">
					<div class="dmiux_input">
                        <label for="input-note">Note</label>
                        <textarea v-model="current_note.note" id="input-note" class="dmiux_input__input" rows="8" placeholder="Enter your table note here"></textarea>
                    </div>
                </div>
			</div>
            <div v-if="showing_note_form != true" class="dmiux_popup__cont">
				<div v-if="notes.length == 0" class="alert alert-info my-4">
					No notes have been added yet.
				</div>
				<div v-else class="dmiux_data-table dmiux_data-table__cont">
					<table class="dmiux_data-table__table"> 
						<thead>
							<tr>
								<th></th>
								<th class="pl-3">Author</th>
								<th class="pl-3" width="80%">Note</th>
								<th class="pl-3">Date</th>
							</tr>
						</thead>
						<tbody>
							<tr v-for="note in notes">
                                <td class="dmiux_td--icon dmiux_data-table__actions align-top pl-1">
								    <div class="dmiux_actionswrap dmiux_actionswrap--edit" data-toggle="tooltip" title="Edit" @click="noteForm(note['id']);"></div>
								    <div class="dmiux_actionswrap dmiux_actionswrap--bin" data-toggle="tooltip" title="Delete" @click="deleteNote(note['id']);"></div>
								</td>
								<td v-html="note['user']['name']" class="align-top"></td>
								<td class="table-notes-modal-note-column align-top">{{ note['note'] }}</td>
								<td class="align-top">{{ note['updated_at'] }}</td>
							</tr>
						</tbody>
					</table>
					<br>
				</div>
            </div>


			<div v-if="showing_note_form" class="dmiux_popup__foot">
				<div class="dmiux_grid-row">
                    <div class="dmiux_grid-col"></div>
                    <div class="dmiux_grid-col dmiux_grid-col_auto">
                        <button class="dmiux_button dmiux_button_secondary" type="button" @click="cancelForm()">Cancel</button>
                    </div>
                    <div class="dmiux_grid-col dmiux_grid-col_auto pl-0">
						<button class="dmiux_button" type="button" @click="saveNote();">Save</button>
                    </div>
                </div>
			</div>
			<div v-else class="dmiux_popup__foot">
				<div class="dmiux_grid-row">
                    <div class="dmiux_grid-col"></div>
                    <div class="dmiux_grid-col dmiux_grid-col_auto">
						<button class="dmiux_button" type="button" @click="noteForm();">Add a Note</button>
                    </div>
                    <div class="dmiux_grid-col dmiux_grid-col_auto pl-0">
                        <button class="dmiux_button dmiux_button_secondary dmiux_popup__cancel" type="button" @click="modalClose($event)" id="cancel-button-table-notes">Close</button>
                    </div>
                </div>
			</div>
		</div>
	</div>
</script>