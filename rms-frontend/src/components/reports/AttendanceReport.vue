<template>

<section>

    <!-- Header -->

    <div
        class="d-flex justify-content-between align-items-center mb-3"
    >

        <div>

            <h5 class="mb-1">
                Attendance Report
            </h5>

            <small class="text-secondary">
                Employee attendance, working hour and overtime history
            </small>

        </div>


        <div>

            <ReportExportButton
                type="attendance"
                :filters="exportFilters"
            />

        </div>

    </div>


    <!-- Attendance Filters -->

    <div class="card mb-4">

        <div class="card-body">

            <div class="row g-3 align-items-end">

                <!-- Employee -->

                <div class="col-md-4">

                    <label class="form-label">
                        Employee
                    </label>

                    <select
                        v-model="localFilters.employee_id"
                        class="form-select"
                        :disabled="employeesLoading"
                    >

                        <option value="">
                            All Employees
                        </option>

                        <option
                            v-for="employee in employees"
                            :key="employee.id"
                            :value="String(employee.id)"
                        >

                            {{ employee.employee_name }}

                        </option>

                    </select>

                </div>


                <!-- Status -->

                <div class="col-md-4">

                    <label class="form-label">
                        Status
                    </label>

                    <select
                        v-model="localFilters.status"
                        class="form-select"
                    >

                        <option value="">
                            All Statuses
                        </option>

                        <option value="scheduled">
                            Scheduled
                        </option>

                        <option value="absent">
                            Absent
                        </option>

                        <option value="present">
                            Present
                        </option>

                        <option value="break">
                            On Break
                        </option>

                        <option value="completed">
                            Checked Out
                        </option>

                        <option value="leave">
                            Leave
                        </option>

                    </select>

                </div>


                <!-- Actions -->

                <div class="col-md-4">

                    <button
                        type="button"
                        class="btn btn-primary me-2"
                        :disabled="loading"
                        @click="applyFilter"
                    >

                        <i class="bi bi-search me-2"></i>

                        Apply

                    </button>


                    <button
                        type="button"
                        class="btn btn-outline-secondary"
                        :disabled="loading"
                        @click="resetFilter"
                    >

                        Reset

                    </button>

                </div>

            </div>

        </div>

    </div>


    <!-- Summary -->

    <div class="row g-3 mb-4">

        <!-- Total -->

        <div class="col-md-3">

            <div class="card summary-card">

                <div class="card-body">

                    <small>
                        Total Records
                    </small>

                    <h4>
                        {{ summary.total }}
                    </h4>

                </div>

            </div>

        </div>


        <!-- Present / Completed -->

        <div class="col-md-3">

            <div class="card summary-card">

                <div class="card-body">

                    <small>
                        Present / Completed
                    </small>

                    <h4>
                        {{ summary.present }}
                    </h4>

                </div>

            </div>

        </div>


        <!-- Absent -->

        <div class="col-md-3">

            <div class="card summary-card">

                <div class="card-body">

                    <small>
                        Absent
                    </small>

                    <h4>
                        {{ summary.absent }}
                    </h4>

                </div>

            </div>

        </div>


        <!-- Leave -->

        <div class="col-md-3">

            <div class="card summary-card">

                <div class="card-body">

                    <small>
                        Leave
                    </small>

                    <h4>
                        {{ summary.leave }}
                    </h4>

                </div>

            </div>

        </div>

    </div>


    <!-- Loading -->

    <div
        v-if="loading"
        class="text-center py-5"
    >

        <div
            class="spinner-border text-primary mb-2"
            role="status"
        ></div>

        <div>
            Loading attendance report...
        </div>

    </div>


    <!-- Error -->

    <div
        v-else-if="error"
        class="alert alert-danger"
    >

        <i class="bi bi-exclamation-circle me-2"></i>

        {{ error }}

    </div>


    <!-- Table -->

    <div
        v-else
        class="card"
    >

        <div class="card-body">

            <div class="table-responsive">

                <table
                    class="table table-hover align-middle mb-0"
                >

                    <thead class="table-light">

                        <tr>

                            <th>
                                SL
                            </th>

                            <th>
                                Employee
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Check In
                            </th>

                            <th>
                                Check Out
                            </th>

                            <th>
                                Hourly Rate
                            </th>

                            <th>
                                Worked Hour
                            </th>

                            <th>
                                Overtime
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        <tr
                            v-for="(
                                attendance,
                                index
                            ) in attendances"
                            :key="attendance.id"
                        >

                            <!-- SL -->

                            <td>

                                {{
                                    serialNumber(
                                        index
                                    )
                                }}

                            </td>


                            <!-- Employee -->

                            <td>

                                <div
                                    class="
                                        d-flex
                                        flex-column
                                    "
                                >

                                    <strong>

                                        {{
                                            employeeName(
                                                attendance
                                            )
                                        }}

                                    </strong>


                                    <small
                                        class="text-secondary"
                                    >

                                        {{
                                            attendance
                                                ?.employee
                                                ?.user
                                                ?.role
                                                ?.name
                                            ||
                                            ''
                                        }}

                                    </small>

                                </div>

                            </td>


                            <!-- Status -->

                            <td>

                                <span
                                    class="badge"
                                    :class="
                                        statusClass(
                                            attendance.status
                                        )
                                    "
                                >

                                    {{
                                        attendance.status_label
                                        ||
                                        attendance.status
                                        ||
                                        '-'
                                    }}

                                </span>

                            </td>


                            <!-- Check In -->

                            <td>

                                {{
                                    formatDateTime(
                                        attendance.check_in_at
                                    )
                                }}

                            </td>


                            <!-- Check Out -->

                            <td>

                                <div
                                    class="
                                        d-flex
                                        flex-column
                                    "
                                >

                                    <span>

                                        {{
                                            formatDateTime(
                                                attendance.check_out_at
                                            )
                                        }}

                                    </span>


                                    <small
                                        v-if="
                                            attendance
                                                .auto_checked_out
                                        "
                                        class="text-secondary"
                                    >

                                        <i
                                            class="bi bi-robot me-1"
                                        ></i>

                                        Auto checkout

                                    </small>

                                </div>

                            </td>


                            <!-- Hourly Rate -->

                            <td>

                                {{
                                    money(
                                        hourlyRate(
                                            attendance
                                        )
                                    )
                                }}

                            </td>


                            <!-- Worked -->

                            <td>

                                <strong>

                                    {{
                                        minutesToHour(
                                            attendance
                                                .worked_minutes
                                        )
                                    }}

                                </strong>

                            </td>


                            <!-- Overtime -->

                            <td>

                                <span
                                    :class="{
                                        'text-danger fw-bold':
                                            Number(
                                                attendance
                                                    .overtime_minutes
                                                ||
                                                0
                                            ) > 0
                                    }"
                                >

                                    {{
                                        minutesToHour(
                                            attendance
                                                .overtime_minutes
                                        )
                                    }}

                                </span>

                            </td>

                        </tr>


                        <!-- Empty -->

                        <tr
                            v-if="
                                attendances.length === 0
                            "
                        >

                            <td
                                colspan="9"
                                class="text-center py-5"
                            >

                                <i
                                    class="
                                        bi
                                        bi-calendar-x
                                        fs-3
                                        text-secondary
                                    "
                                ></i>

                                <div class="mt-2">

                                    No attendance found
                                    for the selected filters.

                                </div>

                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>


            <!-- Pagination -->

            <div
                v-if="meta.total > 0"
                class="
                    d-flex
                    justify-content-between
                    align-items-center
                    mt-3
                    pt-3
                    border-top
                "
            >

                <span class="text-secondary">

                    Showing
                    <strong>
                        {{ meta.from || 0 }}
                    </strong>

                    —
                    <strong>
                        {{ meta.to || 0 }}
                    </strong>

                    of
                    <strong>
                        {{ meta.total }}
                    </strong>

                </span>


                <div
                    class="
                        d-flex
                        align-items-center
                    "
                >

                    <button
                        type="button"
                        class="
                            btn
                            btn-sm
                            btn-outline-primary
                            me-2
                        "
                        :disabled="
                            meta.current_page <= 1
                            ||
                            loading
                        "
                        @click="
                            changePage(
                                meta.current_page - 1
                            )
                        "
                    >

                        Previous

                    </button>


                    <span class="mx-2">

                        Page
                        <strong>
                            {{ meta.current_page }}
                        </strong>

                        of

                        <strong>
                            {{ meta.last_page }}
                        </strong>

                    </span>


                    <button
                        type="button"
                        class="
                            btn
                            btn-sm
                            btn-outline-primary
                        "
                        :disabled="
                            meta.current_page >=
                            meta.last_page
                            ||
                            loading
                        "
                        @click="
                            changePage(
                                meta.current_page + 1
                            )
                        "
                    >

                        Next

                    </button>

                </div>

            </div>

        </div>

    </div>

</section>

</template>


<script setup>

import {
    computed,
    onMounted,
    ref,
    watch
} from 'vue'


import reportService
    from '@/services/reportService'


import ReportExportButton
    from '@/components/reports/ReportExportButton.vue'


/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props =
    defineProps({

        filters: {
            type: Object,
            default: () => ({})
        }

    })


/*
|--------------------------------------------------------------------------
| Attendance
|--------------------------------------------------------------------------
*/

const attendances =
    ref([])


/*
|--------------------------------------------------------------------------
| Employees
|--------------------------------------------------------------------------
*/

const employees =
    ref([])


const employeesLoading =
    ref(false)


/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/

const meta =
    ref({

        current_page: 1,

        last_page: 1,

        per_page: 10,

        total: 0,

        from: 0,

        to: 0,

    })


const page =
    ref(1)


/*
|--------------------------------------------------------------------------
| Summary
|--------------------------------------------------------------------------
*/

const summary =
    ref({

        total: 0,

        present: 0,

        absent: 0,

        leave: 0,

    })


/*
|--------------------------------------------------------------------------
| Loading / Error
|--------------------------------------------------------------------------
*/

const loading =
    ref(false)


const error =
    ref(null)


/*
|--------------------------------------------------------------------------
| Local Filters
|--------------------------------------------------------------------------
*/

const localFilters =
    ref({

        employee_id: '',

        status: '',

    })


/*
|--------------------------------------------------------------------------
| Export Filters
|--------------------------------------------------------------------------
*/

const exportFilters =
    computed(() => ({

        ...props.filters,

        employee_id:
            localFilters.value
                .employee_id
            ||
            undefined,

        status:
            localFilters.value
                .status
            ||
            undefined,

    }))


/*
|--------------------------------------------------------------------------
| Load Employee Options
|--------------------------------------------------------------------------
*/

async function loadEmployees()
{

    employeesLoading.value =
        true

    try {

        const response =
            await reportService
                .getAttendanceEmployees()


        employees.value =
            Array.isArray(
                response?.data
            )
                ? response.data
                : []

    }
    catch (err) {

        employees.value =
            []

        console.error(
            'Attendance employee options error:',
            err
        )

    }
    finally {

        employeesLoading.value =
            false

    }

}


/*
|--------------------------------------------------------------------------
| Load Attendance
|--------------------------------------------------------------------------
*/

async function loadAttendance()
{

    loading.value =
        true

    error.value =
        null


    try {

        const response =
            await reportService
                .getAttendanceReport({

                    ...props.filters,

                    employee_id:
                        localFilters.value
                            .employee_id
                        ||
                        undefined,

                    status:
                        localFilters.value
                            .status
                        ||
                        undefined,

                    page:
                        page.value,

                    per_page:
                        10,

                })


        /*
        |--------------------------------------------------------------------------
        | Data
        |--------------------------------------------------------------------------
        */

        attendances.value =
            Array.isArray(
                response?.data
            )
                ? response.data
                : []


        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        meta.value =
            response?.meta
            ??
            {

                current_page: 1,

                last_page: 1,

                per_page: 10,

                total: 0,

                from: 0,

                to: 0,

            }


        /*
        |--------------------------------------------------------------------------
        | Summary
        |--------------------------------------------------------------------------
        */

        const apiSummary =
            response?.summary
            ??
            null


        if (apiSummary) {

            summary.value = {

                total:
                    Number(
                        apiSummary.total
                        ??
                        meta.value.total
                        ??
                        0
                    ),

                present:
                    Number(
                        apiSummary.present
                        ??
                        0
                    ),

                absent:
                    Number(
                        apiSummary.absent
                        ??
                        0
                    ),

                leave:
                    Number(
                        apiSummary.leave
                        ??
                        0
                    ),

            }

        }
        else {

            /*
            |--------------------------------------------------------------------------
            | Fallback Summary
            |--------------------------------------------------------------------------
            */

            summary.value = {

                total:
                    Number(
                        meta.value.total
                        ||
                        0
                    ),

                present:
                    countAllStatus(
                        'present'
                    )
                    +
                    countAllStatus(
                        'completed'
                    ),

                absent:
                    countAllStatus(
                        'absent'
                    ),

                leave:
                    countAllStatus(
                        'leave'
                    ),

            }

        }

    }
    catch (err) {

        console.error(
            'Attendance report error:',
            err
        )


        attendances.value =
            []


        summary.value = {

            total: 0,

            present: 0,

            absent: 0,

            leave: 0,

        }


        error.value =

            err
                ?.response
                ?.data
                ?.message

            ||

            err?.message

            ||

            'Failed to load attendance report.'

    }
    finally {

        loading.value =
            false

    }

}


/*
|--------------------------------------------------------------------------
| Employee Name
|--------------------------------------------------------------------------
*/

function employeeName(
    attendance
) {

    return (

        attendance
            ?.employee
            ?.user
            ?.name

        ||

        attendance?.staff_name

        ||

        attendance?.employee_name

        ||

        '-'

    )

}


/*
|--------------------------------------------------------------------------
| Hourly Rate
|--------------------------------------------------------------------------
*/

function hourlyRate(
    attendance
) {

    return (

        attendance
            ?.salary_detail
            ?.hourly_rate

        ??

        attendance
            ?.employee
            ?.hourly_rate

        ??

        0

    )

}


/*
|--------------------------------------------------------------------------
| Money
|--------------------------------------------------------------------------
*/

function money(
    value
) {

    return (

        '৳ '
        +
        Number(
            value || 0
        ).toLocaleString(
            'en-BD',
            {

                minimumFractionDigits: 2,

                maximumFractionDigits: 2

            }
        )

    )

}


/*
|--------------------------------------------------------------------------
| Status Count Fallback
|--------------------------------------------------------------------------
*/

function countAllStatus(
    status
) {

    return attendances.value.filter(
        item =>
            item.status === status
    ).length

}


/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/

function changePage(
    value
) {

    const targetPage =
        Number(value)


    if (
        targetPage < 1
        ||
        targetPage >
            Number(
                meta.value.last_page
                ||
                1
            )
    ) {

        return

    }


    page.value =
        targetPage


    loadAttendance()

}


function serialNumber(
    index
) {

    return (

        (
            Number(
                meta.value.current_page
                ||
                1
            )
            -
            1
        )

        *

        Number(
            meta.value.per_page
            ||
            10
        )

        +

        index

        +

        1

    )

}


/*
|--------------------------------------------------------------------------
| Date
|--------------------------------------------------------------------------
*/

function formatDate(
    date
) {

    if (!date) {

        return '-'

    }


    return new Date(
        `${date}T00:00:00`
    ).toLocaleDateString(
        'en-BD'
    )

}


/*
|--------------------------------------------------------------------------
| Date Time
|--------------------------------------------------------------------------
*/

function formatDateTime(
    date
) {

    if (!date) {

        return '-'

    }


    return new Date(
        date
    ).toLocaleString(
        'en-BD'
    )

}


/*
|--------------------------------------------------------------------------
| Minutes → Hour
|--------------------------------------------------------------------------
*/

function minutesToHour(
    minutes
) {

    const totalMinutes =
        Number(
            minutes
            ||
            0
        )


    if (
        totalMinutes <= 0
    ) {

        return '0h'

    }


    const hours =
        Math.floor(
            totalMinutes / 60
        )


    const remainingMinutes =
        totalMinutes % 60


    if (
        remainingMinutes === 0
    ) {

        return `${hours}h`

    }


    return (
        `${hours}h ${remainingMinutes}m`
    )

}


/*
|--------------------------------------------------------------------------
| Status Class
|--------------------------------------------------------------------------
*/

function statusClass(
    status
) {

    return {

        'bg-success':
            status === 'present'
            ||
            status === 'completed',

        'bg-danger':
            status === 'absent',

        'bg-warning text-dark':
            status === 'leave',

        'bg-info':
            status === 'break',

        'bg-secondary':
            status === 'scheduled',

    }

}


/*
|--------------------------------------------------------------------------
| Apply Filter
|--------------------------------------------------------------------------
*/

function applyFilter()
{

    page.value =
        1

    loadAttendance()

}


/*
|--------------------------------------------------------------------------
| Reset Filter
|--------------------------------------------------------------------------
*/

function resetFilter()
{

    localFilters.value = {

        employee_id: '',

        status: '',

    }


    page.value =
        1


    loadAttendance()

}


/*
|--------------------------------------------------------------------------
| Parent Date Filter Watch
|--------------------------------------------------------------------------
*/

watch(

    () =>
        props.filters,

    () => {

        page.value =
            1

        loadAttendance()

    },

    {

        deep: true,

        immediate: true

    }

)


/*
|--------------------------------------------------------------------------
| Mounted
|--------------------------------------------------------------------------
*/

onMounted(
    async () => {

        await loadEmployees()

    }
)

</script>


<style scoped>

.summary-card {

    border-radius: 1rem;

}


.summary-card h4 {

    margin-top: 10px;

    font-weight: 700;

}


table {

    font-size: 0.9rem;

}


.badge {

    padding: 7px 12px;

    border-radius: 20px;

}

</style>