<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ExpenseService
{

    /*
    |--------------------------------------------------------------------------
    | Expense List
    |--------------------------------------------------------------------------
    */

    public function getExpenses(
        array $filters = []
    ): LengthAwarePaginator {

        $query =
            Expense::query()
                ->with([
                    'category',
                    'creator',
                ]);


        /*
        |--------------------------------------------------------------------------
        | Apply Filters
        |--------------------------------------------------------------------------
        */


        $query
            ->dateRange(
                $filters['date_from'] ?? null,
                $filters['date_to'] ?? null
            )


            ->category(
                $filters['category_id'] ?? null
            )


            ->paymentMethod(
                $filters['payment_method'] ?? null
            )


            ->search(
                $filters['search'] ?? null
            );



        /*
        |--------------------------------------------------------------------------
        | Sorting
        |--------------------------------------------------------------------------
        */


        $sortBy =
            $filters['sort_by']
            ??
            'expense_date';


        $sortDirection =
            $filters['sort_direction']
            ??
            'desc';



        $query->orderBy(
            $sortBy,
            $sortDirection
        );



        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */


        return $query->paginate(
            (int) (
                $filters['per_page']
                ??
                10
            )
        );
    }



    /*
    |--------------------------------------------------------------------------
    | Expense Summary
    |--------------------------------------------------------------------------
    */

    public function getSummary(
        array $filters = []
    ): array {


        $query =
            Expense::query();



        $query
            ->dateRange(
                $filters['date_from'] ?? null,
                $filters['date_to'] ?? null
            )


            ->category(
                $filters['category_id'] ?? null
            )


            ->paymentMethod(
                $filters['payment_method'] ?? null
            );



        $totalAmount =
            $query->sum(
                'amount'
            );


        $totalRecords =
            $query->count();



        $averageAmount =
            $totalRecords > 0
                ? $totalAmount / $totalRecords
                : 0;



        return [

            'total_amount' =>
                (float)
                $totalAmount,


            'total_amount_formatted' =>
                $this->money(
                    $totalAmount
                ),



            'total_records' =>
                $totalRecords,



            'average_amount' =>
                round(
                    $averageAmount,
                    2
                ),



            'average_amount_formatted' =>
                $this->money(
                    $averageAmount
                ),

        ];
    }



    /*
    |--------------------------------------------------------------------------
    | Create Expense
    |--------------------------------------------------------------------------
    */

    public function createExpense(
        array $data,
        User $user
    ): Expense {


        return DB::transaction(
            function () use (
                $data,
                $user
            ) {


                return Expense::create([

                    'expense_category_id' =>
                        $data['expense_category_id'],


                    'expense_date' =>
                        $data['expense_date'],


                    'amount' =>
                        $data['amount'],


                    'payment_method' =>
                        $data['payment_method'],


                    'notes' =>
                        $data['notes'] ?? null,


                    'created_by' =>
                        $user->id,


                    'updated_by' =>
                        $user->id,

                ]);

            }
        );

    }



    /*
    |--------------------------------------------------------------------------
    | Update Expense
    |--------------------------------------------------------------------------
    */

    public function updateExpense(
    Expense $expense,
    array $data,
    User $user
): Expense {


    return DB::transaction(

        function () use (
            $expense,
            $data,
            $user
        ) {


            $expense->update([


                'expense_category_id' =>
                    $data['expense_category_id']
                    ??
                    $expense->expense_category_id,


                'expense_date' =>
                    $data['expense_date']
                    ??
                    $expense->expense_date,


                'amount' =>
                    $data['amount']
                    ??
                    $expense->amount,


                'payment_method' =>
                    $data['payment_method']
                    ??
                    $expense->payment_method,


                'notes' =>
                    $data['notes']
                    ??
                    $expense->notes,



                /*
                |--------------------------------------------------------------------------
                | Only updater changes
                |--------------------------------------------------------------------------
                */

                'updated_by' =>
                    $user->id,


            ]);



            return $expense->fresh();


        }

    );

}



    /*
    |--------------------------------------------------------------------------
    | Delete Expense
    |--------------------------------------------------------------------------
    */

    public function deleteExpense(
        Expense $expense
    ): void {


        DB::transaction(
            function () use (
                $expense
            ) {

                $expense->delete();

            }
        );

    }



    /*
    |--------------------------------------------------------------------------
    | Export Query
    |--------------------------------------------------------------------------
    |
    | Excel export-এর জন্য same filtered query.
    |
    */

    public function exportQuery(
        array $filters = []
    ) {


        return Expense::query()

            ->with([
                'category',
                'creator',
            ])

            ->dateRange(
                $filters['date_from'] ?? null,
                $filters['date_to'] ?? null
            )

            ->category(
                $filters['category_id'] ?? null
            )

            ->paymentMethod(
                $filters['payment_method'] ?? null
            )

            ->orderBy(
                'expense_date',
                'desc'
            );

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