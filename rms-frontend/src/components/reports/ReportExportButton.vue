<template>

<div class="d-flex gap-2">


    <!-- Print -->

    <button

        class="btn btn-outline-dark"

        @click="printReport"

        :disabled="loading"

    >

        <i class="bi bi-printer me-1"></i>

        Print

    </button>







    <!-- PDF -->

    <button

        class="btn btn-danger"

        @click="downloadPdf"

        :disabled="loading"

    >


        <span v-if="loading && currentType==='pdf'">

            <span class="spinner-border spinner-border-sm me-1"></span>

            Exporting...

        </span>


        <span v-else>


            <i class="bi bi-file-earmark-pdf me-1"></i>

            PDF


        </span>


    </button>









    <!-- CSV -->


    <button

        class="btn btn-success"

        @click="downloadCsv"

        :disabled="loading"

    >



        <span v-if="loading && currentType==='csv'">


            <span class="spinner-border spinner-border-sm me-1"></span>


            Exporting...


        </span>




        <span v-else>


            <i class="bi bi-filetype-csv me-1"></i>


            CSV


        </span>


    </button>




</div>






<!-- Error -->

<div

    v-if="error"

    class="text-danger small mt-2"

>

    {{ error }}

</div>



</template>









<script setup>


import {

    ref

}

from 'vue'


import api

from '@/services/api'







const props = defineProps({


    type:{


        type:String,


        required:true


    },



    filters:{


        type:Object,


        default:()=>({})


    }



})








const loading = ref(false)


const currentType = ref(null)


const error = ref(null)









async function downloadPdf()

{


    await download(

        'pdf'

    )


}









async function downloadCsv()

{


    await download(

        'csv'

    )


}











async function download(format)

{


    if(loading.value)

        return





    loading.value=true


    currentType.value=format


    error.value=null






    try{


        const response =


            await api.get(



                `/reports/${props.type}/export/${format}`,



                {


                    params:props.filters,



                    responseType:'blob'


                }



            )









        /*
        |--------------------------------------------------------------------------
        | Check server error
        |--------------------------------------------------------------------------
        */


        if(

            response.data.type === 'application/json'

        ){


            const text =

                await response.data.text()



            const json =

                JSON.parse(text)



            throw new Error(

                json.message

                ||

                'Export failed'

            )


        }









        const blob =


            new Blob(

                [

                    response.data

                ],

                {

                    type:

                    response.headers['content-type']

                }

            )









        const url =


            window.URL.createObjectURL(

                blob

            )









        const link =


            document.createElement(

                'a'

            )







        link.href=url







        link.download =


            `${props.type}-report-${Date.now()}.${format}`







        document.body.appendChild(

            link

        )







        link.click()







        link.remove()







        window.URL.revokeObjectURL(

            url

        )



    }





    catch(err){



        console.error(

            'Export error:',

            err

        )



        error.value =


            err.message

            ||

            'Download failed'



    }





    finally{


        loading.value=false


        currentType.value=null


    }



}









function printReport()

{


    window.print()


}





</script>









<style scoped>


button{

    min-width:90px;

}



</style>