import api from './api'

const employeeService = {

    /*
    |--------------------------------------------------------------------------
    | Employee List
    |--------------------------------------------------------------------------
    */

    getEmployees(params = {})
    {
        return api.get(
            '/staff/employees',
            {
                params,
            }
        )
    },


    /*
    |--------------------------------------------------------------------------
    | Single Employee
    |--------------------------------------------------------------------------
    */

    getEmployee(id)
    {
        return api.get(
            `/staff/employees/${id}`
        )
    },


    /*
    |--------------------------------------------------------------------------
    | Employee Roles
    |--------------------------------------------------------------------------
    */

    getRoles()
    {
        return api.get(
            '/staff/roles'
        )
    },


    /*
    |--------------------------------------------------------------------------
    | Create Employee
    |--------------------------------------------------------------------------
    */

    createEmployee(data)
    {
        return api.post(
            '/staff/employees',
            data
        )
    },


    /*
    |--------------------------------------------------------------------------
    | Update Employee
    |--------------------------------------------------------------------------
    */

    updateEmployee(id, data)
    {
        return api.put(
            `/staff/employees/${id}`,
            data
        )
    },


    /*
    |--------------------------------------------------------------------------
    | Delete Employee
    |--------------------------------------------------------------------------
    */

    deleteEmployee(id)
    {
        return api.delete(
            `/staff/employees/${id}`
        )
    },


    /*
    |--------------------------------------------------------------------------
    | Update Working Status
    |--------------------------------------------------------------------------
    */

    updateStatus(id, status)
    {
        return api.patch(
            `/staff/employees/${id}/status`,
            {
                current_status: status,
            }
        )
    },


    /*
    |--------------------------------------------------------------------------
    | Block / Unblock Account
    |--------------------------------------------------------------------------
    */

    updateAccountStatus(id, isActive)
    {
        return api.patch(
            `/staff/employees/${id}/account-status`,
            {
                is_active: isActive,
            }
        )
    },


    /*
    |--------------------------------------------------------------------------
    | Old Endpoint Compatibility
    |--------------------------------------------------------------------------
    */

    toggleActive(id)
    {
        return api.patch(
            `/staff/employees/${id}/toggle-active`
        )
    },

}

export default employeeService