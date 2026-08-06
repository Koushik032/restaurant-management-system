import api from './api'

const shiftScheduleService = {

    /*
    |--------------------------------------------------------------------------
    | Daily Effective Schedule List
    |--------------------------------------------------------------------------
    */

    getSchedules(params = {})
    {
        return api.get(
            '/staff/shift-schedules',
            {
                params,
            }
        )
    },


    /*
    |--------------------------------------------------------------------------
    | Single Recurring Schedule
    |--------------------------------------------------------------------------
    */

    getSchedule(id, params = {})
    {
        return api.get(
            `/staff/shift-schedules/${id}`,
            {
                params,
            }
        )
    },


    /*
    |--------------------------------------------------------------------------
    | Available Employees
    |--------------------------------------------------------------------------
    */

    getEmployees()
    {
        return api.get(
            '/staff/shift-schedules/employees'
        )
    },


    /*
    |--------------------------------------------------------------------------
    | Create Recurring Schedule
    |--------------------------------------------------------------------------
    */

    createSchedule(data)
    {
        return api.post(
            '/staff/shift-schedules',
            data
        )
    },


    /*
    |--------------------------------------------------------------------------
    | Update Entire Recurring Schedule
    |--------------------------------------------------------------------------
    */

    updateSchedule(id, data)
    {
        return api.put(
            `/staff/shift-schedules/${id}`,
            data
        )
    },


    /*
    |--------------------------------------------------------------------------
    | Activate / Deactivate Entire Schedule
    |--------------------------------------------------------------------------
    */

    updateStatus(id, isActive)
    {
        return api.patch(
            `/staff/shift-schedules/${id}/status`,
            {
                is_active: isActive,
            }
        )
    },


    /*
    |--------------------------------------------------------------------------
    | Delete Entire Recurring Schedule
    |--------------------------------------------------------------------------
    */

    deleteSchedule(id)
    {
        return api.delete(
            `/staff/shift-schedules/${id}`
        )
    },


    /*
    |--------------------------------------------------------------------------
    | Override List
    |--------------------------------------------------------------------------
    */

    getOverrides(scheduleId)
    {
        return api.get(
            `/staff/shift-schedules/${scheduleId}/overrides`
        )
    },


    /*
    |--------------------------------------------------------------------------
    | Create One-Day Override
    |--------------------------------------------------------------------------
    */

    createOverride(scheduleId, data)
    {
        return api.post(
            `/staff/shift-schedules/${scheduleId}/overrides`,
            data
        )
    },


    /*
    |--------------------------------------------------------------------------
    | Update One-Day Override
    |--------------------------------------------------------------------------
    */

    updateOverride(overrideId, data)
    {
        return api.put(
            `/staff/shift-schedule-overrides/${overrideId}`,
            data
        )
    },


    /*
    |--------------------------------------------------------------------------
    | Remove Override / Restore Regular Schedule
    |--------------------------------------------------------------------------
    */

    deleteOverride(overrideId)
    {
        return api.delete(
            `/staff/shift-schedule-overrides/${overrideId}`
        )
    },

}

export default shiftScheduleService