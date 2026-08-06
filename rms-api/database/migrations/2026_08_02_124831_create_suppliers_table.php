<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{


    public function up(): void
    {

        Schema::create(
            'suppliers',
            function(Blueprint $table){


                $table->id();



                /*
                |--------------------------------------------------------------------------
                | Supplier Information
                |--------------------------------------------------------------------------
                */


                $table->string(
                    'supplier_name'
                );


                $table->string(
                    'contact_person'
                )
                ->nullable();



                $table->string(
                    'email'
                )
                ->nullable();



                $table->string(
                    'phone',
                    30
                );



                $table->text(
                    'address'
                )
                ->nullable();



                $table->string(
                    'gstin',
                    50
                )
                ->nullable();



                /*
                |--------------------------------------------------------------------------
                | User Tracking
                |--------------------------------------------------------------------------
                */


                $table->foreignId(
                    'created_by'
                )
                ->nullable()
                ->constrained(
                    'users'
                )
                ->nullOnDelete();



                $table->foreignId(
                    'updated_by'
                )
                ->nullable()
                ->constrained(
                    'users'
                )
                ->nullOnDelete();



                /*
                |--------------------------------------------------------------------------
                | Timestamp
                |--------------------------------------------------------------------------
                */


                $table->timestamps();


                $table->softDeletes();



                /*
                |--------------------------------------------------------------------------
                | Index
                |--------------------------------------------------------------------------
                */

                $table->index(
                    'supplier_name'
                );


                $table->index(
                    'phone'
                );


            }
        );

    }



    public function down(): void
    {

        Schema::dropIfExists(
            'suppliers'
        );

    }

};