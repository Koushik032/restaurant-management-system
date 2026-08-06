<template>

<section class="staff-header-card">


    <div class="staff-header-content">


        <!-- Left Content -->

        <div class="staff-header-text">

            <h2>

                <i class="bi bi-person-badge"></i>

                Staff Management

            </h2>

            <p>
                Manage employees, attendance, shifts, breaks and work schedules
            </p>

        </div>


        <!-- Actions -->

        <div class="staff-header-actions">

            <button
                type="button"
                class="staff-refresh-btn"
                title="Refresh current section"
                @click="refresh"
            >

                <i class="bi bi-arrow-clockwise"></i>

                Refresh

            </button>

        </div>


    </div>


</section>

</template>


<script setup>

const emit = defineEmits([
    'refresh',
])


function refresh()
{
    emit('refresh')
}

</script>