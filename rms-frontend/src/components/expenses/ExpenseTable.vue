<template>

<section class="expense-table-card">


<!-- ==================================================
     Loading
=================================================== -->

<div
v-if="loading"
class="expense-table-loading"
>

<div class="spinner-border"></div>

<p>
Loading expenses...
</p>

</div>





<!-- ==================================================
     Empty
=================================================== -->

<div
v-else-if="expenses.length === 0"
class="expense-empty-state"
>

<i class="bi bi-wallet2"></i>


<h4>
No expenses found
</h4>


<p>
Try changing your filters.
</p>


</div>





<!-- ==================================================
     Table
=================================================== -->

<div
v-else
class="table-responsive"
>


<table class="expense-table">


<thead>

<tr>

<th>
SL
</th>


<th>
Expense Date
</th>


<th>
Category
</th>


<th>
Amount
</th>


<th>
Paid By
</th>


<th>
Payment Mode
</th>


<th>
Notes
</th>


<th>
Action
</th>


</tr>

</thead>





<tbody>


<tr
v-for="(expense,index) in expenses"
:key="expense.id"
>


<td>

{{ serialNumber(index) }}

</td>




<td>

<div class="expense-date">

<strong>
{{ expense.expense_date_label }}
</strong>


<span>
{{ expense.expense_day }}
</span>

</div>

</td>





<td>

<span class="category-badge">

{{ expense.category_name }}

</span>

</td>





<td>

<strong class="amount">

{{ expense.amount_formatted }}

</strong>

</td>





<td>

{{ expense.paid_by_name }}

</td>





<td>

<span class="payment-badge">

{{ expense.payment_method_label }}

</span>

</td>





<td class="notes-column">

{{ expense.notes || '-' }}

</td>





<td>


<div class="expense-actions">


<button

type="button"

class="action-btn edit"

title="Edit"

@click="editExpense(expense)"

>

<i class="bi bi-pencil"></i>

</button>





<button
type="button"
class="action-btn delete"
title="Delete"
@click="deleteExpense(expense)"
>

<i class="bi bi-trash"></i>

</button>


</div>


</td>


</tr>


</tbody>






<!-- ==================================================
     Total Row
=================================================== -->


<tfoot>

<tr>


<td
colspan="3"
class="total-label"
>

Total Amount

</td>



<td
colspan="5"
class="total-amount"
>


{{ totalAmount }}


</td>


</tr>


</tfoot>



</table>


</div>





<!-- ==================================================
     Pagination
=================================================== -->

<div
v-if="meta.last_page > 1"
class="expense-pagination"
>


<button
:disabled="meta.current_page === 1"
@click="changePage(meta.current_page - 1)"
>

Previous

</button>




<span>

Page
{{ meta.current_page }}

of

{{ meta.last_page }}

</span>




<button
:disabled="meta.current_page === meta.last_page"
@click="changePage(meta.current_page + 1)"
>

Next

</button>


</div>



</section>

</template>





<script setup>


import {
computed
} from 'vue'



const props = defineProps({

expenses:{
type:Array,
default:()=>[]
},


loading:{
type:Boolean,
default:false
},


meta:{
type:Object,
default:()=>({})
}

})



const emit = defineEmits([

'delete',

'edit',

'page-change'

])





/*
|--------------------------------------------------------------------------
| Serial Number
|--------------------------------------------------------------------------
*/


function serialNumber(index){

return (

(
props.meta.current_page - 1
)
*
props.meta.per_page

)

+
index
+
1

}


function editExpense(expense){

    emit(
        'edit',
        expense
    )

}



/*
|--------------------------------------------------------------------------
| Total Amount
|--------------------------------------------------------------------------
*/


const totalAmount = computed(()=>{


const total =

props.expenses.reduce(

(sum,item)=>{

return sum + Number(
item.amount || 0
)

},

0

)


return (

'৳ '

+
total.toLocaleString(
'en-US',
{
minimumFractionDigits:2
}
)

)


})







/*
|--------------------------------------------------------------------------
| Delete
|--------------------------------------------------------------------------
*/


function deleteExpense(
expense
){

emit(
'delete',
expense
)

}





/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/


function changePage(
page
){

emit(
'page-change',
page
)

}


</script>