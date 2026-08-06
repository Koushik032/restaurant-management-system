import api from './api'



const purchaseOrderService = {



    /*
    |--------------------------------------------------------------------------
    | Get Purchase Order List
    |--------------------------------------------------------------------------
    */


    async getPurchaseOrders(

        params = {}

    ){


        const response = await api.get(

            '/purchase-orders',

            {

                params

            }

        )



        return response.data


    },









    /*
    |--------------------------------------------------------------------------
    | Get Single Purchase Order
    |--------------------------------------------------------------------------
    */


    async getPurchaseOrder(

        id

    ){


        const response = await api.get(

            `/purchase-orders/${id}`

        )



        return response.data


    },









    /*
    |--------------------------------------------------------------------------
    | Create Purchase Order
    |--------------------------------------------------------------------------
    */


    async createPurchaseOrder(

        data

    ){


        const response = await api.post(

            '/purchase-orders',

            data

        )



        return response.data


    },









    /*
    |--------------------------------------------------------------------------
    | Update Purchase Order
    |--------------------------------------------------------------------------
    */


    async updatePurchaseOrder(

        id,

        data

    ){


        const response = await api.put(

            `/purchase-orders/${id}`,

            data

        )



        return response.data


    },









    /*
    |--------------------------------------------------------------------------
    | Delete Purchase Order
    |--------------------------------------------------------------------------
    */


    async deletePurchaseOrder(

        id

    ){


        const response = await api.delete(

            `/purchase-orders/${id}`

        )



        return response.data


    },




async updatePurchaseOrderStatus(
    id,
    status
) {
    const response = await api.patch(
        `/purchase-orders/${id}/status`,
        {
            status,
        }
    )

    return response.data
},




    /*
    |--------------------------------------------------------------------------
    | Supplier Dropdown Options
    |--------------------------------------------------------------------------
    */


    async getSuppliers(){


        const response = await api.get(

            '/suppliers'

        )



        return response.data


    },









    /*
    |--------------------------------------------------------------------------
    | Purchase Order Status Options
    |--------------------------------------------------------------------------
    */


    getStatusOptions(){


        return [


            {

                value:'ordered',

                label:'Ordered'

            },



            {

                value:'partially_received',

                label:'Partially Received'

            },



            {

                value:'received',

                label:'Received'

            },



            {

                value:'cancelled',

                label:'Cancelled'

            }



        ]


    },









    /*
    |--------------------------------------------------------------------------
    | Payment Method Options
    |--------------------------------------------------------------------------
    */


    getPaymentMethods(){


        return [


            {

                value:'cash',

                label:'Cash'

            },


            {

                value:'card',

                label:'Card'

            },


            {

                value:'bkash',

                label:'Bkash'

            },


            {

                value:'nagad',

                label:'Nagad'

            },


            {

                value:'bank_transfer',

                label:'Bank Transfer'

            }


        ]


    },









    /*
    |--------------------------------------------------------------------------
    | Error Message Helper
    |--------------------------------------------------------------------------
    */


    getErrorMessage(

        error,

        fallback = 'Something went wrong.'

    ){



        return (


            error

                ?.response

                ?.data

                ?.message


            ||



            fallback



        )



    }





}



export default purchaseOrderService