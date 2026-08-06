<template>

<section class="expense-filter-card">


<div class="expense-filter-grid">


<!-- From Date -->

<div>

<label>
From Date
</label>

<input
type="date"
v-model="localFilters.date_from"
/>

</div>




<!-- To Date -->

<div>

<label>
To Date
</label>

<input
type="date"
v-model="localFilters.date_to"
/>

</div>





<!-- Category -->

<div>

<label>
Category
</label>


<select
v-model="localFilters.category_id"
>

<option value="">
All Categories
</option>


<option
v-for="item in categories"
:key="item.id"
:value="item.id"
>

{{ item.name }}

</option>


</select>

</div>





<!-- Payment -->

<div>

<label>
Payment Method
</label>


<select
v-model="localFilters.payment_method"
>

<option value="">
All Methods
</option>


<option
v-for="item in paymentMethods"
:key="item.value"
:value="item.value"
>

{{ item.label }}

</option>


</select>

</div>


</div>





<div class="expense-filter-actions">


<button
class="btn-primary"
@click="apply"
>

<i class="bi bi-funnel"></i>

Apply

</button>




<button
class="btn-secondary"
@click="clear"
>

<i class="bi bi-x-circle"></i>

Clear

</button>




<button
class="btn-secondary"
@click="$emit('print')"
>

<i class="bi bi-printer"></i>

Print

</button>


</div>


</section>

</template>



<script setup>

import {
reactive,
watch
} from 'vue'


const props = defineProps({

filters:{
type:Object,
required:true
},


categories:{
type:Array,
default:()=>[]
},


paymentMethods:{
type:Array,
default:()=>[]
}

})



const emit = defineEmits([

'update:filters',
'apply',
'clear',
'print'

])



const localFilters =
reactive({

...props.filters

})



watch(

localFilters,

(value)=>{

emit(
'update:filters',
value
)

},

{
deep:true
}

)



function apply(){

emit('apply')

}



function clear(){

localFilters.date_from=''

localFilters.date_to=''

localFilters.category_id=''

localFilters.payment_method=''


emit('clear')

}


</script>