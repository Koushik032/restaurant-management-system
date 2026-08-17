import api from './api'



const reportService = {



    /*
    |--------------------------------------------------------------------------
    | Dashboard Summary
    |--------------------------------------------------------------------------
    */


    async getSummary(
        params = {}
    ){

        const response =
            await api.get(
                '/billing/summary',
                {
                    params
                }
            )


        return response.data

    },







    /*
    |--------------------------------------------------------------------------
    | Orders Report
    |--------------------------------------------------------------------------
    */


    async getOrderReport(
        params = {}
    ){

        const response =

            await api.get(

                '/reports/orders',

                {
                    params
                }

            )


        return response.data

    },







    /*
    |--------------------------------------------------------------------------
    | Sales Report
    |--------------------------------------------------------------------------
    */


    async getSalesReport(
        params = {}
    ){

        const response =

            await api.get(

                '/billing/settlements',

                {
                    params
                }

            )


        return response.data

    },







    /*
    |--------------------------------------------------------------------------
    | Payment Report
    |--------------------------------------------------------------------------
    */


    async getPaymentReport(
        params = {}
    ){

        const response =

            await api.get(

                '/billing/payment-modes',

                {
                    params
                }

            )


        return response.data

    },








    /*
    |--------------------------------------------------------------------------
    | Expense Report
    |--------------------------------------------------------------------------
    */


    async getExpenseReport(
        params = {}
    ){

        const response =

            await api.get(

                '/expenses',

                {
                    params
                }

            )


        return response.data

    },







    /*
    |--------------------------------------------------------------------------
    | Expense Summary
    |--------------------------------------------------------------------------
    */


    async getExpenseSummary(
        params = {}
    ){

        const response =

            await api.get(

                '/expenses/summary',

                {
                    params
                }

            )


        return response.data

    },








    /*
    |--------------------------------------------------------------------------
    | Purchase Report
    |--------------------------------------------------------------------------
    */


    async getPurchaseReport(
        params = {}
    ){

        const response =

            await api.get(

                '/purchase-orders',

                {
                    params
                }

            )


        return response.data

    },








    /*
    |--------------------------------------------------------------------------
    | Purchase Payment History
    |--------------------------------------------------------------------------
    */


    async getPurchasePayments(
        id
    ){

        const response =

            await api.get(

                `/purchase-orders/${id}/payments`

            )


        return response.data

    },








    /*
    |--------------------------------------------------------------------------
    | Warehouse Stock
    |--------------------------------------------------------------------------
    */


    async getWarehouseStock(
        params = {}
    ){

        const response =

            await api.get(

                '/inventory/warehouse-stocks',

                {
                    params
                }

            )


        return response.data

    },








    /*
    |--------------------------------------------------------------------------
    | Restaurant Stock
    |--------------------------------------------------------------------------
    */


    async getRestaurantStock(
        params = {}
    ){

        const response =

            await api.get(

                '/inventory/restaurant-stocks',

                {
                    params
                }

            )


        return response.data

    },








    /*
    |--------------------------------------------------------------------------
    | Stock Transfers
    |--------------------------------------------------------------------------
    */


    async getStockTransfers(
        params = {}
    ){

        const response =

            await api.get(

                '/inventory/stock-transfers',

                {
                    params
                }

            )


        return response.data

    },








    /*
    |--------------------------------------------------------------------------
    | Attendance Report
    |--------------------------------------------------------------------------
    */


    async getAttendanceReport(
    params = {}
) {

    const response =
        await api.get(
            '/reports/attendance',
            {
                params
            }
        )

    return response.data
},








    /*
    |--------------------------------------------------------------------------
    | Salary Report
    |--------------------------------------------------------------------------
    */


    async getSalaryReport(
        params = {}
    ){

        const response =

            await api.get(

                '/admin/salaries',

                {
                    params
                }

            )


        return response.data

    },








    /*
    |--------------------------------------------------------------------------
    | Export CSV
    |--------------------------------------------------------------------------
    */


    async exportCSV(
        type,
        params = {}
    ){


        return await api.get(

            `/reports/${type}/export/csv`,

            {

                params,

                responseType:'blob'

            }

        )


    },








    /*
    |--------------------------------------------------------------------------
    | Export PDF
    |--------------------------------------------------------------------------
    */


    async exportPDF(
        type,
        params = {}
    ){


        return await api.get(

            `/reports/${type}/export/pdf`,

            {

                params,

                responseType:'blob'

            }

        )


    },








    /*
    |--------------------------------------------------------------------------
    | Download File Helper
    |--------------------------------------------------------------------------
    */


    downloadFile(
        response,
        filename
    ){



        const blob =

            new Blob(

                [

                    response.data

                ]

            )



        const url =

            window.URL.createObjectURL(

                blob

            )





        const link =

            document.createElement(

                'a'

            )





        link.href = url



        link.download = filename





        document.body.appendChild(

            link

        )



        link.click()





        link.remove()





        window.URL.revokeObjectURL(

            url

        )


    }




}



export default reportService