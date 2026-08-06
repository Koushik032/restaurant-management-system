<?php

namespace App\Http\Resources;


use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;



class PurchaseOrderItemResource extends JsonResource
{


    /**
     * Transform resource into array.
     */
    public function toArray(

        Request $request

    ): array {


        $pendingQuantity = max(

            0,

            $this->quantity
            -
            $this->received_quantity

        );



        return [



            /*
            |--------------------------------------------------------------------------
            | Basic Information
            |--------------------------------------------------------------------------
            */


            'id'=>

                (int) $this->id,





            'item_name'=>

                $this->item_name,





            'unit'=>

                $this->unit,







            'quantity'=>

                (float) $this->quantity,







            'received_quantity'=>

                (float) $this->received_quantity,







            'pending_quantity'=>

                (float) $pendingQuantity,









            /*
            |--------------------------------------------------------------------------
            | Receive Status
            |--------------------------------------------------------------------------
            */


            'receive_status'=>

                $this->receiveStatus(),







            /*
            |--------------------------------------------------------------------------
            | Price
            |--------------------------------------------------------------------------
            */


            'unit_price'=>

                (float) $this->unit_price,







            'unit_price_formatted'=>

                $this->money(

                    $this->unit_price

                ),







            'total_price'=>

                (float) $this->total_price,







            'total_price_formatted'=>

                $this->money(

                    $this->total_price

                ),



        ];


    }









    /*
    |--------------------------------------------------------------------------
    | Receive Status
    |--------------------------------------------------------------------------
    */


    private function receiveStatus(): string
    {


        if(
            $this->received_quantity <= 0
        ){

            return 'pending';

        }





        if(
            $this->received_quantity < $this->quantity
        ){

            return 'partial';

        }





        return 'received';



    }










    /*
    |--------------------------------------------------------------------------
    | Money Formatter
    |--------------------------------------------------------------------------
    */


    private function money(

        mixed $amount

    ): string {



        return '৳ '

            .

            number_format(

                (float) $amount,

                2

            );


    }



}