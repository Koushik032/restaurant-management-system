import api from './api'


/*
|--------------------------------------------------------------------------
| API Endpoint
|--------------------------------------------------------------------------
*/

const EXPENSE_BASE_URL =
  '/expenses'



const expenseService = {


  /*
  |--------------------------------------------------------------------------
  | Get Expense List
  |--------------------------------------------------------------------------
  */

  async getExpenses(
    params = {},
  ) {

    const response =
      await api.get(
        EXPENSE_BASE_URL,
        {
          params,
        },
      )


    return {

      success:
        Boolean(
          response
            ?.data
            ?.success,
        ),


      message:
        response
          ?.data
          ?.message
        ||
        'Expenses loaded successfully.',


      data:
        response
          ?.data
          ?.data
        ||
        [],


      meta:
        response
          ?.data
          ?.meta
        ||
        {},

    }

  },




  /*
  |--------------------------------------------------------------------------
  | Get Expense Summary
  |--------------------------------------------------------------------------
  */

  async getSummary(
    params = {},
  ) {


    const response =
      await api.get(
        `${EXPENSE_BASE_URL}/summary`,
        {
          params,
        },
      )


    return {

      success:
        Boolean(
          response
            ?.data
            ?.success,
        ),


      data:
        response
          ?.data
          ?.data
        ||
        {},

    }

  },




  /*
  |--------------------------------------------------------------------------
  | Get Dropdown Options
  |--------------------------------------------------------------------------
  */

  async getOptions() {


    const response =
      await api.get(
        `${EXPENSE_BASE_URL}/options`,
      )


    return {

      success:
        Boolean(
          response
            ?.data
            ?.success,
        ),


      data:
        response
          ?.data
          ?.data
        ||
        {
          categories: [],
          payment_methods: [],
        },

    }

  },




  /*
  |--------------------------------------------------------------------------
  | Get Single Expense
  |--------------------------------------------------------------------------
  */

  async getExpense(
    id,
  ) {


    const response =
      await api.get(
        `${EXPENSE_BASE_URL}/${id}`,
      )


    return {

      success:
        Boolean(
          response
            ?.data
            ?.success,
        ),


      data:
        response
          ?.data
          ?.data
        ||
        null,

    }

  },




  /*
  |--------------------------------------------------------------------------
  | Create Expense
  |--------------------------------------------------------------------------
  */

  async createExpense(
    payload,
  ) {


    const response =
      await api.post(
        EXPENSE_BASE_URL,
        payload,
      )


    return {

      success:
        Boolean(
          response
            ?.data
            ?.success,
        ),


      message:
        response
          ?.data
          ?.message
        ||
        'Expense created successfully.',


      data:
        response
          ?.data
          ?.data
        ||
        null,

    }

  },




  /*
  |--------------------------------------------------------------------------
  | Update Expense
  |--------------------------------------------------------------------------
  */

  async updateExpense(
    id,
    payload,
  ) {


    const response =
      await api.put(
        `${EXPENSE_BASE_URL}/${id}`,
        payload,
      )


    return {

      success:
        Boolean(
          response
            ?.data
            ?.success,
        ),


      message:
        response
          ?.data
          ?.message
        ||
        'Expense updated successfully.',


      data:
        response
          ?.data
          ?.data
        ||
        null,

    }

  },




  /*
  |--------------------------------------------------------------------------
  | Delete Expense
  |--------------------------------------------------------------------------
  */

  async deleteExpense(
    id,
  ) {


    const response =
      await api.delete(
        `${EXPENSE_BASE_URL}/${id}`,
      )


    return {

      success:
        Boolean(
          response
            ?.data
            ?.success,
        ),


      message:
        response
          ?.data
          ?.message
        ||
        'Expense deleted successfully.',

    }

  },




  /*
  |--------------------------------------------------------------------------
  | Error Handler
  |--------------------------------------------------------------------------
  */

  getErrorMessage(
    error,
    fallback =
      'Something went wrong.',
  ) {


    return (

      error
        ?.response
        ?.data
        ?.message

      ||

      error
        ?.response
        ?.data
        ?.errors

      ||

      fallback

    )

  },


}



export default expenseService