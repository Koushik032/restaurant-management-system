<template>

<section class="staff-management-page">

    <!-- Common Header -->

    <StaffPageHeader
        @refresh="refreshCurrentSection"
    />


    <!-- Common Tabs -->

    <StaffTabs
        v-model="activeTab"
    />


    <!-- Employees -->

    <EmployeeSection
        v-if="activeTab === 'employees'"
        ref="employeeSectionRef"
    />


    <!-- Attendance -->

    <AttendanceSection
        v-else-if="activeTab === 'attendance'"
        ref="attendanceSectionRef"
    />


    <!-- Shift Schedule -->

    <ShiftScheduleSection
        v-else-if="activeTab === 'shift_schedule'"
        ref="shiftScheduleSectionRef"
    />

</section>

</template>


<script setup>

import {
    ref,
} from 'vue'

import StaffPageHeader
    from '@/components/staff/StaffPageHeader.vue'

import StaffTabs
    from '@/components/staff/StaffTabs.vue'

import EmployeeSection
    from '@/components/staff/employees/EmployeeSection.vue'

import AttendanceSection
    from '@/components/staff/attendance/AttendanceSection.vue'

import ShiftScheduleSection
    from '@/components/staff/shift-schedules/ShiftScheduleSection.vue'


const activeTab =
    ref('employees')

const employeeSectionRef =
    ref(null)

const attendanceSectionRef =
    ref(null)

const shiftScheduleSectionRef =
    ref(null)


function refreshCurrentSection()
{
    /*
    |--------------------------------------------------------------------------
    | Employee Refresh
    |--------------------------------------------------------------------------
    */

    if (
        activeTab.value === 'employees'
        &&
        employeeSectionRef.value
    ) {
        employeeSectionRef.value
            .refreshEmployees()

        return
    }


    /*
    |--------------------------------------------------------------------------
    | Attendance Refresh
    |--------------------------------------------------------------------------
    */

    if (
        activeTab.value === 'attendance'
        &&
        attendanceSectionRef.value
    ) {
        attendanceSectionRef.value
            .refreshAttendances()

        return
    }


    /*
    |--------------------------------------------------------------------------
    | Shift Schedule Refresh
    |--------------------------------------------------------------------------
    */

    if (
        activeTab.value === 'shift_schedule'
        &&
        shiftScheduleSectionRef.value
    ) {
        shiftScheduleSectionRef.value
            .refreshShiftSchedules()
    }
}

</script>


<style>

@import '@/assets/css/staff/staff-header.css';

@import '@/assets/css/staff/staff-tabs.css';

@import '@/assets/css/staff/staff-responsive.css';

</style>