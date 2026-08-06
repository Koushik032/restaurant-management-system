<template>

<section class="staff-tabs-card">


    <div class="staff-tabs">


        <!-- Employees -->

        <button
            type="button"
            class="staff-tab"
            :class="{
                active: modelValue === 'employees',
            }"
            @click="changeTab('employees')"
        >

            <i class="bi bi-people"></i>

            <span>
                Employees
            </span>

        </button>


        <!-- Attendance -->

        <button
            type="button"
            class="staff-tab"
            :class="{
                active: modelValue === 'attendance',
            }"
            @click="changeTab('attendance')"
        >

            <i class="bi bi-calendar-check"></i>

            <span>
                Attendance
            </span>

        </button>


        <!-- Shift Schedule -->

        <button
            type="button"
            class="staff-tab"
            :class="{
                active: modelValue === 'shift_schedule',
            }"
            @click="changeTab('shift_schedule')"
        >

            <i class="bi bi-clock-history"></i>

            <span>
                Shift Schedule
            </span>

        </button>


    </div>


</section>

</template>


<script setup>

defineProps({

    modelValue: {

        type: String,

        default: 'employees',

    },

})


const emit = defineEmits([
    'update:modelValue',
])


function changeTab(tab)
{
    emit(
        'update:modelValue',
        tab
    )
}

</script>