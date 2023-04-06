<script type="text/x-template" id="chars">
<div class="dmiux_input">
    <label class="dmiux_popup__label" :for="id">{{ name }} <span v-if="required" class="text-danger">*</span></label>
    <template v-if="type == 'textarea'">
        <textarea @input="processInput($event.target.value)" :maxlength="limit" class="dmiux_input__input" :value="value" :id="id"></textarea>
    </template>
    <template v-else>
        <input type="text" @input="processInput($event.target.value)" :maxlength="limit" class="dmiux_input__input" :value="value" :id="id" />
    </template>
    <small v-if="additionalHelpText">{{ additionalHelpText }}</small>
    <br v-if="additionalHelpText" />
    <small>{{ remaining }} / {{ limit }} characters remaining</small>
</div>
</script>
<script>
    var chars = Vue.component('chars', {
        template: '#chars',
        name: 'CharsRemaining',
        props: [
            'id',
            'additionalHelpText',
            'required',
            'value',
            'name',
            'limit',
            'type'
        ],
        data() {
            return {
                remaining: 0,
                currentValue: null
            }
        },
        methods: {
            processInput(val) {
                this.currentValue = val;
                this.$emit('input', this.currentValue);
            }
        },
        watch: {
            value() {
                this.remaining = this.limit - this.value.length;
            }
        },
        mounted() {
            this.remaining = this.limit - this.value.length;
        }
    });
</script>