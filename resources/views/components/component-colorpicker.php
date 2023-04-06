<script type="text/x-template" id="color-picker">
    <div class="dmiux_select">
        <div class="dmiux-colorpicker-dropdown dmiux_select__select" @click="toggleDropdown()">
            <span v-html="selector"></span>
            <ul class="dropdown" v-show="active">
                <li :id="'id-'+color.value.replace('#', '')" v-for="color in colors" @click="setColor(color.value, color.text)" :class="color.value == selectedColor ? 'selected' : ''"><span :style="{background: color.value}"></span> {{color.text}}</li>
            </ul>
        </div>
    </div>
</script>

<script>
    var colorPicker = Vue.component('colorPicker', {
        template: '#color-picker',
        props: [ 'colors', 'selectedColor', 'selectedColorName', 'active' ],
        data() {
            return {
                originalColor: '',
                originalColorName: ''
            };
        },
        computed: {
            selector() {
                if(!app.selectedColor) {
                    return 'Tag Color';
                }
                else {
                    return '<span style="background: ' + app.selectedColor + '"></span> ' + app.selectedColorName;
                }
            }
        },
        methods: {
            setColor(color, colorName) {
                app.selectedColor = color;
                app.selectedColorName = colorName;
            },
            toggleDropdown() {
                app.active = !app.active;
                this.originalColor = app.selectedColor;
                this.originalColorName = app.selectedColorName;
                if (app.active) {
                    document.addEventListener("keydown", this.handleKeys);
                    document.getElementById("dmiux_body").addEventListener("click", this.hideDropdown);
                    this.$nextTick(() => {
                        if (this.selectedColor != '') {
                            this.scrollIntoView();
                        }
                    });
                }
                else {
                    this.hideDropdown();
                }
            },
            getSelectedIndex() {
                for(var i=0; i<this.colors.length; i++) {
                    if (app.selectedColor == this.colors[i].value) {
                        return i;
                    }
                }
                return 0;
            },
            scrollIntoView() {
                var color_id = 'id-' + app.selectedColor.replace('#', '');
                var scrollingDiv = document.querySelector('.dmiux-colorpicker-dropdown .dropdown');
                if (scrollingDiv && document.getElementById(color_id)) {
                    scrollingDiv.scrollTop = document.getElementById(color_id).offsetTop;
                }
            },
            handleKeys(event) {
                var key = "";
                if(event.key != undefined) {
                    switch (event.key) {
                        case "Up" :
                        case "ArrowUp" :
                            key = "up";
                            break;
                        case "Down" :
                        case "ArrowDown" :
                            key = "down";
                            break;
                        case "Esc" :
                        case "Escape" :
                            key = "escape";
                            break;
                        case "Enter" :
                            key = "enter";
                            break;
                    }
                }
                if (key == "enter") {
                    this.hideDropdown();
                }
                else if (key == "escape") {
                    app.selectedColor = this.originalColor;
                    app.selectedColorName = this.originalColorName;
                    this.hideDropdown();
                }
                else {
                    if (app.selectedColor == '') {
                        if (key == "up") {
                            app.selectedColor = this.colors[this.colors.length-1].value;
                            app.selectedColorName = this.colors[this.colors.length-1].text;
                        }
                        else if (key == "down") {
                            app.selectedColor = this.colors[0].value;
                            app.selectedColorName = this.colors[0].text;
                        }
                    }
                    else {
                        var index = this.getSelectedIndex();
                        if (key == "up") {
                            if (index == 0)
                                index = this.colors.length - 1;
                            else
                                index-=1;
                        }
                        else if (key == "down") {
                            if (index == this.colors.length - 1)
                                index = 0;
                            else
                                index+=1;
                        }
                        app.selectedColor = this.colors[index].value;
                        app.selectedColorName = this.colors[index].text;
                    }
                    this.scrollIntoView();
                }
            },
            hideDropdown(event) {
                if (event != undefined) {
                    event.stopPropagation();
                    var clicked_element = event.target;
                    if (clicked_element.closest(".dmiux-colorpicker-dropdown")) {
                        return;
                    }
                }
                app.active = false;
                document.removeEventListener("keydown", this.handleKeys);
                document.getElementById("dmiux_body").removeEventListener("click", this.hideDropdown);
            }
        }
    });
</script>