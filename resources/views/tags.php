<?php echo view("components/head"); ?>
<?php echo view("components/component-toolbar"); ?>
<?php echo view("components/component-colorpicker"); ?>
<div id="app">
    <toolbar
        :buttons="toolbar.buttons"
        :breadcrumbs="toolbar.breadcrumbs">
    </toolbar>
    <div class="dmiux_content">
        <?php echo view('components/admin/menu', ['selected' => 'tags']); ?>
        <div v-if="currentUser.is_admin == true" id="table_tags" class="dmiux_grid-cont dmiux_grid-cont_fw dmiux_data-table dmiux_data-table__cont">
            <table v-if="tags.length > 0" class="dmiux_data-table__table">
                <thead>
                    <tr>
                        <th></th> 
                        <th>Tag</th>
                        <th>Last Modified</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(tag, index) in tags">
                        <td style="width: 20px !important; padding-right: 25px;">
                            <div class="dmiux_data-table__actions" v-if="tag.changed == null">
                                <div class="dmiux_actionswrap dmiux_actionswrap--edit" @click="add_tag(tag.id, index, tag.changed)" data-toggle="tooltip" title="Edit Tag"></div>
                                <div class="dmiux_actionswrap dmiux_actionswrap--bin" @click="remove_tag(tag.id)" data-toggle="tooltip" title="Delete Tag"></div>
                            </div>
                            <div class="dmiux_data-table__actions" v-else>
                                <div class="dmiux_actionswrap dmiux_actionswrap--cancel" @click="editingCancel()" data-toggle="tooltip" title="Stop Editing Tag"></div>
                                <div class="dmiux_actionswrap dmiux_actionswrap--save" @click="manage(tag)" data-toggle="tooltip" title="Save Tag"></div>
                            </div>
                        </td>
                        <td v-if="tag.name != '' && tag.changed == null"><span :style="'background-color:' + tag.color" class="dmiux_tag_circle"> </span> <span>{{ tag.name }}</span></td>
                        <td v-else>
                            <div class="row dmiux_input">
                                <div class="col-sm-4">
                                    <input class="dmiux_input__input" v-model="tag.name" placeholder="Tag Name" id="tag-add" @input="cleanupTagName(index)" autocomplete="off" />
                                </div>
                                <div class="col-sm-4">
                                    <color-picker
                                                :colors="colors"
                                                :selected-color-name="selectedColorName"
                                                :selected-color="selectedColor"
                                                :active="active">
                                    </color-picker>
                                </div>
                            </div>
                        </td>
                        <td>{{ tag.updated_at_formatted }}</td>
                    </tr>
                </tbody>
            </table>
            <div v-else class="alert alert-info mt-2">There are no tags yet. <a href="javascript:void(0)" @click="add_tag();">Add a tag</a>.</div>
        </div>
    </div>
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
        data: {
            toolbar: {
                "breadcrumbs": [],
                "buttons": [
                    {
                        "onclick": "app.add_tag()",
                        "text": "Add Tag&nbsp; <span class=\"fas fa-plus\"></span>",
                        "class": "dmiux_button dmiux_button_secondary"
                    }
                ]
            },
            tags: [],
            currentUser : {
                "is_admin" : false
            },
            colors: [ {"value": "#65bc55", "text": "Green" },
            {"value": "#f1d52f", "text": "Yellow" },
            {"value": "#e95b4c", "text": "Red" },
            {"value": "#c27bde", "text": "Purple" },
            {"value": "#127bbd", "text": "Blue" },
            {"value": "#fd7bca", "text": "Pink" },
            {"value": "#354662", "text": "Black" },
            {"value": "#b3bac5", "text": "Grey" },
            {"value": "#59e79b", "text": "Teal" },
            {"value": "#20c2de", "text": "Cyan" } ],
            locked: false,
            selectedColor: '',
            selectedColorName: '',
            active:false
        },
        components: {
            'toolbar': toolbar,
            'colorPicker': colorPicker
        },
        methods: {
            loading: function(status) {
                if(status === true) {
                    $(".loader").show();
                }
                else {
                    $(".loader").hide();
                }
            },
            getTags: function() {
                this.loading(true);
                fetch("/internal-api/v1/admin/tags")
                .then(response => response.json())
                .then(json => {
                    this.tags = json.data;

                    for(i = 0; i < this.tags.length; i++) 
                    {
                        this.tags[i].changed = null;
                        this.tags[i].updated_at_formatted = DateHelper.formatLocaleCarbonDate(this.tags[i].updated_at);
                    }
                    this.loading(false);
                    this.locked = false;
                })
            },
            getCurrentUser: function() {
                fetch("/internal-api/v1/me")
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
            add_tag: function(id = "", index = 0, changed = "") {
                var tag = new Object();

                if(this.locked == false) {
                    if(id != "" ) {
                        var tag_edit = this.tags.filter(function (tag) {
                            if(tag.id == id) {
                                tag.changed = "edit";
                            }
                            return tag;
                        });
                        this.tags = tag_edit;
                        tag.changed = "edit"
                        // Assign ColorPicker value when edit
                        var _self = this;
                        _self.selectedColor = _self.tags[index].color;
                        _self.colors.filter(function (color) {
                            if(color.value == _self.selectedColor) {
                                _self.selectedColorName = color.text;
                            }
                            return tag;
                        });
                        setTimeout(function() { document.getElementById("tag-add").focus(); }, 500);
                    }
                    else {
                        tag.color = "";
                        tag.name = "";
                        tag.changed = "add"
                        // Make ColorPicker Empty
                        this.selectedColor = '';
                        this.selectedColorName = '';
                        this.tags.push(tag);
                        setTimeout(function() { document.getElementById("tag-add").focus(); }, 500);
                    }
                    this.locked = true;
                }
            },
            remove_tag: function(id) {
                if(!confirm("Are you sure you want to delete this?")) {
                    return false;
                }

                fetch(`/internal-api/v1/admin/tags/${id}`, { method: 'delete' })
                    .then(response => response.json())
                    .then(json => {
                        if (json.status == "error") {
                            alert(json.message);
                            return;
                        }
                        app.getTags();
                    });
            },
            editingCancel: function() {
                this.getTags();
            },
            manage: function(tag) {
                let options = FetchHelper.buildJsonRequest({
                    color: this.selectedColor,
                    name:  tag.name
                }, tag.id ? 'PUT' : 'POST');

                this.loading(true);
                fetch(`/internal-api/v1/admin/tags${tag.id ? `/${tag.id}` : ''}`, options)
                    .then(FetchHelper.handleJsonResponse)
                    .then(json => {
                        this.loading(false);
                        this.getTags();
                    })
                    .catch((error) => {
                        this.loading(false);
                        ResponseHelper.handleErrorMessage(error, 'Could not manage tag.');
                    });
            },
            cleanupTagName: function(index=0){
                this.tags[index].name = this.tags[index].name.substring(0, 25);
            }
        },
        mounted: function() {
            this.getCurrentUser();
            this.getTags();
            this.getBreadcrumbs();
        }
    })
</script>
<?php echo view("components/foot"); ?>
