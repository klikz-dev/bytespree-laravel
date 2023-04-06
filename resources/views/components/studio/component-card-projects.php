<script type="text/x-template" id="component-card-projects">
    <div class="dmiux_grid-cont dmiux_grid-cont_fw">
        <div v-show="$root.loaded == true">
            <div v-if="projects.length != 0" class="dmiux_data-table">
                <div class="dmiux_data-table__cont">
                    <table id="projects-table" class="dmiux_data-table__table">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Project</th>
                                <th>Database(s)</th>
                                <th>Members</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(project, index) in projects" class="cursor-p">
                                <td>
                                    <div class="dmiux_data-table__actions">
                                        <div @click="editProject(project)" title="Edit" class="dmiux_actionswrap dmiux_actionswrap--edit"></div>
                                    </div>
                                </td>
                                <td class="cursor-p" @click="openProject(project.id)">
                                    <div class="project-display_name" :title="project.display_name"><strong>{{ project.display_name }}</strong></div>
                                    <div><small class="text-muted">Schema: {{ project.name }}</small></div>
                                    <div class="wrap-description"><small>{{ project.description }}</small></div>
                                </td>
                                <td class="cursor-p" @click="openProject(project.id)">
                                    <img v-if="project.primary_database.integration_id != 0" class="project-list-img"
                                        draggable="false" 
                                        :src="'/connectors/' + project.primary_database.integration_id + '/logo?v3.0.1'" />
                                    <img v-else 
                                        draggable="false"
                                        src="<?php echo config('services.dmiux.url') ?>/img/applications-database.png"  class="project-list-img-empty" /> 
                                        {{ project.primary_database.database }}<span v-if="project.foreign_databases.length > 0">, {{ project.foreign_databases[0].database }}</span><a class="tooltip-pretty-but-truncated" :title="databaseNames(project.foreign_databases)" v-if="project.foreign_databases.length > 1"> +{{ project.foreign_databases.length - 1 }}</a>
                                    </td>
                                <td class="cursor-p" @click="openProject(project.id)">
                                    <div class="dmiux_badge-primary p-1" :class="index < 1 ? 'mr-2' : 'mr-1'" v-for="(member, index) in $parent.getActiveProjectMembers(project.members, 4)">
                                        <small>{{ member.name }}</small>
                                    </div>
                                    <a v-if="project.members.length > 4" href="javascript:void(0)" @click.stop="showProjectUsers(project)">
                                        +{{ project.members.length - 4 }}
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div v-else class="alert alert-info">There are no Studio projects to display.</div>
        </div>
    </div>
</script>