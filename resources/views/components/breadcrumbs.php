<div class="dmiux_grid-col dmiux_grid-col_auto">
    <a v-if="breadcrumbs.length > 1" v-bind:href="breadcrumbs[breadcrumbs.length -2].location" class="dmiux_back">
        <i></i>
    </a>
</div>
<div v-for="(val,index) in breadcrumbs" class="h4 breadcrumbs dmiux_grid-col dmiux_grid-col_auto dmiux_title">
    <div v-if="index != Object.keys(breadcrumbs).length - 1 && val.isToolTip"
         data-toggle="tooltip"
        :class="(index > 0) ? 'breadcrumb_slash' : ''" 
        :title="val.unTrimmedTitle">
        <a class="breadcrumb_link" :href="val.location">{{ val.title }}</a>
    </div>
    <div v-else-if="index != Object.keys(breadcrumbs).length - 1" 
        class="breadcrumb_overflow" :class="{ 'dmi_': (breadcrumbs.length === 1), 'breadcrumb_overflow-2': (breadcrumbs.length === 2), 'breadcrumb_overflow-3': (breadcrumbs.length === 3), breadcrumb_slash: (index > 0) }" 
        :title="val.title">
        <a class="breadcrumb_link" :href="val.location">{{ val.title }}</a>
    </div>
    <div v-else-if="val.isToolTip" 
        data-toggle="tooltip" 
        :title="val.unTrimmedTitle" 
        :class="(index > 0) ? 'breadcrumb_slash' : ''">{{ val.title }}</div>
    <div v-else 
        class="breadcrumb_overflow" 
        :class="{ 'breadcrumb_overflow-1': (breadcrumbs.length === 1), 'breadcrumb_overflow-2': (breadcrumbs.length === 2), 'breadcrumb_overflow-3': (breadcrumbs.length === 3), breadcrumb_slash: (index > 0) }" 
        :title="val.title">{{ val.title }}</div>  
</div>