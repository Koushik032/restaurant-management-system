import api from './api'

const salaryService = {

  /*
  |--------------------------------------------------------------------------
  | Salary Summary
  |--------------------------------------------------------------------------
  */

  getSalaries(params = {})
  {
    return api.get(
      '/admin/salaries',
      {
        params,
      }
    )
  },


  getEmployees()
  {
    return api.get(
      '/admin/salaries/employees'
    )
  },


  generateSalaries(data)
  {
    return api.post(
      '/admin/salaries/generate',
      data
    )
  },


  updateSalary(id, data)
  {
    return api.put(
      `/admin/salaries/${id}`,
      data
    )
  },


  updatePaymentStatus(
    id,
    paymentStatus
  ) {
    return api.patch(
      `/admin/salaries/${id}/payment-status`,
      {
        payment_status:
          paymentStatus,
      }
    )
  },


  deleteSalary(id)
  {
    return api.delete(
      `/admin/salaries/${id}`
    )
  },


  /*
  |--------------------------------------------------------------------------
  | Salary Details
  |--------------------------------------------------------------------------
  */

  getSalaryDetails(params = {})
  {
    return api.get(
      '/admin/salary-details',
      {
        params,
      }
    )
  },


  updateSalaryDetail(
    id,
    data
  ) {
    return api.patch(
      `/admin/salary-details/${id}`,
      data
    )
  },

}

export default salaryService