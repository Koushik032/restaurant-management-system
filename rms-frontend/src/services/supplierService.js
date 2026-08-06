import api from './api'



const supplierService = {



    /*
    |--------------------------------------------------------------------------
    | Get Supplier List
    |--------------------------------------------------------------------------
    */


    async getSuppliers(
        params = {}
    ){


        const response =

            await api.get(

                '/suppliers',

                {
                    params
                }

            )


        return response.data


    },







    /*
    |--------------------------------------------------------------------------
    | Create Supplier
    |--------------------------------------------------------------------------
    */


    async createSupplier(
        data
    ){


        const response =

            await api.post(

                '/suppliers',

                data

            )


        return response.data


    },








    /*
    |--------------------------------------------------------------------------
    | Update Supplier
    |--------------------------------------------------------------------------
    */


    async updateSupplier(
        id,
        data
    ){


        const response =

            await api.put(

                `/suppliers/${id}`,

                data

            )


        return response.data


    },








    /*
    |--------------------------------------------------------------------------
    | Delete Supplier
    |--------------------------------------------------------------------------
    */


    async deleteSupplier(
        id
    ){


        const response =

            await api.delete(

                `/suppliers/${id}`

            )


        return response.data


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



export default supplierService