<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    /*
    |--------------------------------------------------------------------------
    | Get Purchase Orders
    |--------------------------------------------------------------------------
    */

    public function getPurchaseOrders(
        array $filters = []
    ): LengthAwarePaginator {

        $query = PurchaseOrder::query()
            ->with([
                'supplier',
                'items',
                'orderedBy',
            ]);


        /*
        |--------------------------------------------------------------------------
        | Date Range Filter
        |--------------------------------------------------------------------------
        |
        | User যদি From Date / To Date filter ব্যবহার করে,
        | তখন normal order_date filter কাজ করবে।
        |
        */

        if (!empty($filters['date_from'])) {

            $query->whereDate(
                'order_date',
                '>=',
                $filters['date_from']
            );

        }


        if (!empty($filters['date_to'])) {

            $query->whereDate(
                'order_date',
                '<=',
                $filters['date_to']
            );

        }


        /*
        |--------------------------------------------------------------------------
        | Today's Active Orders
        |--------------------------------------------------------------------------
        |
        | কোনো date filter দেওয়া না থাকলে:
        |
        | order_date <= today
        | এবং
        | delivery_date >= today
        |
        | Delivery date null হলে শুধু order date-এর দিন দেখাবে।
        |
        */

        if (
            empty($filters['date_from'])
            &&
            empty($filters['date_to'])
        ) {

            $today = Carbon::today()
                ->toDateString();


            $query->whereDate(
                'order_date',
                '<=',
                $today
            );


            $query->where(
                function ($dateQuery) use ($today) {

                    $dateQuery
                        ->whereDate(
                            'delivery_date',
                            '>=',
                            $today
                        )

                        ->orWhere(
                            function ($nullDeliveryQuery) use ($today) {

                                $nullDeliveryQuery
                                    ->whereNull(
                                        'delivery_date'
                                    )

                                    ->whereDate(
                                        'order_date',
                                        '=',
                                        $today
                                    );

                            }
                        );

                }
            );

        }


        /*
        |--------------------------------------------------------------------------
        | Supplier Filter
        |--------------------------------------------------------------------------
        */

        if (!empty($filters['supplier_id'])) {

            $query->where(
                'supplier_id',
                $filters['supplier_id']
            );

        }


        /*
        |--------------------------------------------------------------------------
        | Status Filter
        |--------------------------------------------------------------------------
        */

        if (!empty($filters['status'])) {

            $query->where(
                'status',
                $filters['status']
            );

        }


        /*
        |--------------------------------------------------------------------------
        | Sorting
        |--------------------------------------------------------------------------
        */

        $query
            ->orderBy(
                'delivery_date',
                'asc'
            )
            ->orderBy(
                'order_date',
                'desc'
            );


        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        $perPage = (int) (
            $filters['per_page']
            ??
            10
        );


        $perPage = max(
            1,
            min(
                $perPage,
                100
            )
        );


        return $query
            ->paginate($perPage)
            ->withQueryString();
    }


    /*
    |--------------------------------------------------------------------------
    | Create Purchase Order
    |--------------------------------------------------------------------------
    */

    public function createPurchaseOrder(
        array $data,
        User $user
    ): PurchaseOrder {

        return DB::transaction(
            function () use (
                $data,
                $user
            ) {

                $subtotal = 0;

                foreach ($data['items'] as $item) {

                    $subtotal +=
                        (float) $item['quantity']
                        *
                        (float) $item['unit_price'];

                }


                $tax =
                    (float) (
                        $data['tax']
                        ??
                        0
                    );


                $serviceCharge =
                    (float) (
                        $data['service_charge']
                        ??
                        0
                    );


                $totalAmount =
                    $subtotal
                    +
                    $tax
                    +
                    $serviceCharge;


                $paidAmount =
                    (float) (
                        $data['paid_amount']
                        ??
                        0
                    );


                $paidAmount = min(
                    $paidAmount,
                    $totalAmount
                );


                $dueAmount = max(
                    0,
                    $totalAmount - $paidAmount
                );


                $purchaseOrder =
                    PurchaseOrder::create([

                        'supplier_id' =>
                            $data['supplier_id'],

                        'order_date' =>
                            $data['order_date'],

                        'delivery_date' =>
                            $data['delivery_date']
                            ??
                            null,

                        'status' =>
                            $data['status']
                            ??
                            PurchaseOrder::STATUS_ORDERED,

                        'subtotal' =>
                            $subtotal,

                        'tax' =>
                            $tax,

                        'service_charge' =>
                            $serviceCharge,

                        'total_amount' =>
                            $totalAmount,

                        'paid_amount' =>
                            $paidAmount,

                        'due_amount' =>
                            $dueAmount,

                        'payment_method' =>
                            $data['payment_method']
                            ??
                            null,

                        'ordered_by' =>
                            $user->id,

                        'notes' =>
                            $data['notes']
                            ??
                            null,

                        'created_by' =>
                            $user->id,

                        'updated_by' =>
                            $user->id,

                    ]);


                $this->saveItems(
                    $purchaseOrder,
                    $data['items']
                );


                return $purchaseOrder
                    ->fresh([
                        'supplier',
                        'items',
                        'orderedBy',
                    ]);

            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Update Purchase Order
    |--------------------------------------------------------------------------
    */

    public function updatePurchaseOrder(
        PurchaseOrder $purchaseOrder,
        array $data,
        User $user
    ): PurchaseOrder {

        return DB::transaction(
            function () use (
                $purchaseOrder,
                $data,
                $user
            ) {

                $purchaseOrder->update([

                    'supplier_id' =>
                        $data['supplier_id']
                        ??
                        $purchaseOrder->supplier_id,

                    'order_date' =>
                        $data['order_date']
                        ??
                        $purchaseOrder->order_date,

                    'delivery_date' =>
                        array_key_exists(
                            'delivery_date',
                            $data
                        )
                            ? $data['delivery_date']
                            : $purchaseOrder->delivery_date,

                    'status' =>
                        $data['status']
                        ??
                        $purchaseOrder->status,

                    'tax' =>
                        $data['tax']
                        ??
                        $purchaseOrder->tax,

                    'service_charge' =>
                        $data['service_charge']
                        ??
                        $purchaseOrder->service_charge,

                    'paid_amount' =>
                        $data['paid_amount']
                        ??
                        $purchaseOrder->paid_amount,

                    'payment_method' =>
                        array_key_exists(
                            'payment_method',
                            $data
                        )
                            ? $data['payment_method']
                            : $purchaseOrder->payment_method,

                    'notes' =>
                        array_key_exists(
                            'notes',
                            $data
                        )
                            ? $data['notes']
                            : $purchaseOrder->notes,

                    'updated_by' =>
                        $user->id,

                ]);


                if (isset($data['items'])) {

                    $purchaseOrder
                        ->items()
                        ->delete();


                    $this->saveItems(
                        $purchaseOrder,
                        $data['items']
                    );

                }


                $subtotal =
                    (float) $purchaseOrder
                        ->items()
                        ->sum('total_price');


                $totalAmount =
                    $subtotal
                    +
                    (float) $purchaseOrder->tax
                    +
                    (float) $purchaseOrder->service_charge;


                $paidAmount = min(
                    (float) $purchaseOrder->paid_amount,
                    $totalAmount
                );


                $purchaseOrder->update([

                    'subtotal' =>
                        $subtotal,

                    'total_amount' =>
                        $totalAmount,

                    'paid_amount' =>
                        $paidAmount,

                    'due_amount' =>
                        max(
                            0,
                            $totalAmount - $paidAmount
                        ),

                ]);


                return $purchaseOrder
                    ->fresh([
                        'supplier',
                        'items',
                        'orderedBy',
                    ]);

            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Save Purchase Order Items
    |--------------------------------------------------------------------------
    */

    private function saveItems(
        PurchaseOrder $purchaseOrder,
        array $items
    ): void {

        foreach ($items as $item) {

            $quantity =
                (float) $item['quantity'];


            $unitPrice =
                (float) $item['unit_price'];


            PurchaseOrderItem::create([

                'purchase_order_id' =>
                    $purchaseOrder->id,

                'item_name' =>
                    $item['item_name'],

                'unit' =>
                    $item['unit'],

                'quantity' =>
                    $quantity,

                'received_quantity' =>
                    (float) (
                        $item['received_quantity']
                        ??
                        0
                    ),

                'unit_price' =>
                    $unitPrice,

                'total_price' =>
                    $quantity * $unitPrice,

            ]);

        }
    }


    /*
    |--------------------------------------------------------------------------
    | Delete Purchase Order
    |--------------------------------------------------------------------------
    */

    public function deletePurchaseOrder(
        PurchaseOrder $purchaseOrder
    ): void {

        DB::transaction(
            function () use (
                $purchaseOrder
            ) {

                $purchaseOrder->delete();

            }
        );
    }
}