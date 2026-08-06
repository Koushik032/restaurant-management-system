import api from './api'

const attendanceService = {

    /*
    |--------------------------------------------------------------------------
    | Attendance List
    |--------------------------------------------------------------------------
    */

    getAttendances(params = {})
    {
        return api.get(
            '/staff/attendances',
            {
                params,
            }
        )
    },


    /*
    |--------------------------------------------------------------------------
    | Single Attendance
    |--------------------------------------------------------------------------
    */

    getAttendance(id)
    {
        return api.get(
            `/staff/attendances/${id}`
        )
    },


    /*
    |--------------------------------------------------------------------------
    | Manual Attendance Sync
    |--------------------------------------------------------------------------
    */

    syncAttendance(attendanceDate)
    {
        return api.post(
            '/staff/attendances/sync',
            {
                attendance_date:
                    attendanceDate,
            }
        )
    },


    /*
    |--------------------------------------------------------------------------
    | Employee Options
    |--------------------------------------------------------------------------
    |
    | Active manager, waiter এবং chef list।
    |
    */

    getEmployees()
    {
        return api.get(
            '/staff/shift-schedules/employees'
        )
    },

}

export default attendanceService