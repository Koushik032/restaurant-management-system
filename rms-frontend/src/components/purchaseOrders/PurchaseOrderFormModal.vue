<template>


<div

v-if="show"

class="po-form-overlay"

>


<div class="po-form-modal">





<!-- Header -->

<div class="po-form-header">


<h3>

<i class="bi bi-cart-plus"></i>


{{ order ? 'Edit Purchase Order' : 'Add Purchase Order' }}


</h3>





<button

type="button"

class="close-btn"

@click="close"

>


<i class="bi bi-x-lg"></i>


</button>



</div>









<form

@submit.prevent="submitForm"

>



<div class="po-form-body">







<!-- Supplier -->


<div class="form-group">


<label>

Supplier *

</label>



<select

v-model="form.supplier_id"

>


<option value="">

Select Supplier

</option>



<option

v-for="supplier in suppliers"

:key="supplier.id"

:value="supplier.id"

>


{{ supplier.supplier_name }}


</option>



</select>



</div>










<!-- Date -->


<div class="form-grid">



<div class="form-group">


<label>

Order Date *

</label>



<input

type="date"

v-model="form.order_date"

/>


</div>







<div class="form-group">


<label>

Delivery Date

</label>



<input

type="date"

v-model="form.delivery_date"

/>


</div>




</div>









<!-- Status -->


<div class="form-group">


<label>

Status

</label>



<select

v-model="form.status"

>


<option value="ordered">

Ordered

</option>



<option value="partially_received">

Partially Received

</option>



<option value="received">

Received

</option>



<option value="cancelled">

Cancelled

</option>



</select>



</div>









<!-- Items -->


<div class="items-section">


<div class="items-header">


<h4>

Items

</h4>



<button

type="button"

class="add-item-btn"

@click="addItem"

>


<i class="bi bi-plus"></i>

Add Item


</button>



</div>







<div

v-for="(item,index) in form.items"

:key="index"

class="item-row"

>




<input

v-model="item.item_name"

placeholder="Item name"

/>





<input

v-model="item.unit"

placeholder="Unit"

/>





<input

type="number"

min="0"

v-model.number="item.quantity"

placeholder="Qty"

/>





<input

type="number"

min="0"

v-model.number="item.unit_price"

placeholder="Price"

/>





<input

:value="itemTotal(item)"

readonly

placeholder="Total"

/>






<button

type="button"

class="remove-item"

@click="removeItem(index)"

>


<i class="bi bi-trash"></i>


</button>




</div>





</div>









<!-- Charges -->


<div class="form-grid">



<div class="form-group">


<label>

Tax

</label>



<input

type="number"

min="0"

v-model.number="form.tax"

/>


</div>








<div class="form-group">


<label>

Service Charge

</label>



<input

type="number"

min="0"

v-model.number="form.service_charge"

/>


</div>



</div>









<!-- Payment -->


<div class="form-grid">



<div class="form-group">


<label>

Paid Amount

</label>



<input

type="number"

min="0"

v-model.number="form.paid_amount"

/>


</div>








<div class="form-group">


<label>

Payment Method

</label>



<select

v-model="form.payment_method"

>


<option value="cash">

Cash

</option>



<option value="card">

Card

</option>



<option value="bkash">

Bkash

</option>



<option value="nagad">

Nagad

</option>



<option value="bank_transfer">

Bank Transfer

</option>



</select>



</div>



</div>









<!-- Notes -->


<div class="form-group">


<label>

Notes

</label>



<textarea

v-model="form.notes"

rows="3"

></textarea>



</div>







</div>









<!-- Footer -->


<div class="po-form-footer">



<button

type="button"

class="cancel-btn"

@click="close"

>


Cancel

</button>







<button

type="submit"

class="save-btn"

:disabled="loading"

>


{{ loading ? 'Saving...' : 'Save Order' }}


</button>



</div>







</form>






</div>


</div>



</template>









<script setup>


import {

reactive,

watch

} from 'vue'







const props = defineProps({



show:{


type:Boolean,

default:false


},





order:{


type:Object,

default:null


},





suppliers:{


type:Array,

default:()=>[]


},





loading:{


type:Boolean,

default:false


}



})









const emit = defineEmits([


'close',


'submit'


])











const emptyItem = ()=>({


item_name:'',

unit:'',

quantity:1,

unit_price:0


})









const form = reactive({



supplier_id:'',

order_date:'',

delivery_date:'',

status:'ordered',

tax:0,

service_charge:0,

paid_amount:0,

payment_method:'cash',

notes:'',

items:[

emptyItem()

]



})









watch(

()=>props.order,


(order)=>{


if(order){



form.supplier_id =

order.supplier?.id ?? ''





form.order_date =

order.order_date

?.substring(0,10)

??

''






form.delivery_date =

order.delivery_date ?? ''





form.status =

order.status ?? 'ordered'






form.tax =

Number(order.tax ?? 0)





form.service_charge =

Number(order.service_charge ?? 0)





form.paid_amount =

Number(order.paid_amount ?? 0)





form.payment_method =

order.payment_method ?? 'cash'





form.notes =

order.notes ?? ''






form.items =

(order.items ?? [])

.map(item=>({


item_name:item.item_name,


unit:item.unit,


quantity:Number(item.quantity),


unit_price:Number(item.unit_price)



}))





if(form.items.length===0){


form.items.push(emptyItem())


}





}



else{


resetForm()


}



},

{

immediate:true

}

)









function addItem(){


form.items.push(

emptyItem()

)


}









function removeItem(index){


if(form.items.length > 1){


form.items.splice(index,1)


}


}











function itemTotal(item){


return Number(item.quantity || 0)

*

Number(item.unit_price || 0)



}











function submitForm(){



console.log(

'FORM SUBMIT DATA:',

form

)





if(!form.supplier_id){


alert('Please select supplier')


return

}





if(!form.order_date){


alert('Please select order date')


return

}





emit(

'submit',

JSON.parse(

JSON.stringify(form)

)

)



}









function close(){


emit('close')


}









function resetForm(){


Object.assign(form,{


supplier_id:'',


order_date:'',


delivery_date:'',


status:'ordered',


tax:0,


service_charge:0,


paid_amount:0,


payment_method:'cash',


notes:'',


items:[emptyItem()]


})


}







</script>