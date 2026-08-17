<?php

namespace App\Http\Requests\Api;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseOrderPayment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;


class ReceivePurchaseOrderRequest extends FormRequest
{
    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    */

    public function authorize(): bool
    {
        return $this->user()?->hasAnyPermission([
            'inventory.manage',
            'suppliers.manage',
        ]) ?? false;
    }


    /*
    |--------------------------------------------------------------------------
    | Prepare For Validation
    |--------------------------------------------------------------------------
    */

    protected function prepareForValidation(): void
    {
        $data = [];


        /*
        |--------------------------------------------------------------------------
        | General Notes
        |--------------------------------------------------------------------------
        */

        if ($this->exists('notes')) {

            $notes = trim(
                (string) $this->input(
                    'notes'
                )
            );


            $data['notes'] =
                $notes !== ''
                    ? $notes
                    : null;
        }


        /*
        |--------------------------------------------------------------------------
        | Receive Items
        |--------------------------------------------------------------------------
        */

        $items =
            $this->input(
                'items'
            );


        if (is_array($items)) {

            $data['items'] =
                collect($items)
                    ->map(
                        static function (
                            mixed $item
                        ): mixed {

                            if (!is_array($item)) {
                                return $item;
                            }


                            if (
                                array_key_exists(
                                    'notes',
                                    $item
                                )
                            ) {

                                $notes = trim(
                                    (string) (
                                        $item['notes']
                                        ?? ''
                                    )
                                );


                                $item['notes'] =
                                    $notes !== ''
                                        ? $notes
                                        : null;
                            }


                            return $item;
                        }
                    )
                    ->values()
                    ->all();
        }


        /*
        |--------------------------------------------------------------------------
        | Optional Supplier Payment
        |--------------------------------------------------------------------------
        */

        $payment =
            $this->input(
                'payment'
            );


        if (is_array($payment)) {

            /*
            |--------------------------------------------------------------------------
            | Payment Method
            |--------------------------------------------------------------------------
            */

            if (
                array_key_exists(
                    'payment_method',
                    $payment
                )
            ) {

                $method =
                    strtolower(
                        trim(
                            (string) (
                                $payment[
                                    'payment_method'
                                ]
                                ?? ''
                            )
                        )
                    );


                $payment['payment_method'] =
                    $method !== ''
                        ? $method
                        : null;
            }


            /*
            |--------------------------------------------------------------------------
            | Payment Reference
            |--------------------------------------------------------------------------
            */

            if (
                array_key_exists(
                    'transaction_reference',
                    $payment
                )
            ) {

                $reference =
                    trim(
                        (string) (
                            $payment[
                                'transaction_reference'
                            ]
                            ?? ''
                        )
                    );


                $payment[
                    'transaction_reference'
                ] =
                    $reference !== ''
                        ? $reference
                        : null;
            }


            /*
            |--------------------------------------------------------------------------
            | Payment Notes
            |--------------------------------------------------------------------------
            */

            if (
                array_key_exists(
                    'notes',
                    $payment
                )
            ) {

                $paymentNotes =
                    trim(
                        (string) (
                            $payment['notes']
                            ?? ''
                        )
                    );


                $payment['notes'] =
                    $paymentNotes !== ''
                        ? $paymentNotes
                        : null;
            }


            $data['payment'] =
                $payment;
        }


        if ($data !== []) {
            $this->merge(
                $data
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    */

    public function rules(): array
    {
        return [

            /*
            |--------------------------------------------------------------------------
            | Receive Notes
            |--------------------------------------------------------------------------
            */

            'notes' => [
                'bail',
                'nullable',
                'string',
                'max:2000',
            ],


            /*
            |--------------------------------------------------------------------------
            | Receive Items
            |--------------------------------------------------------------------------
            */

            'items' => [
                'bail',
                'required',
                'array',
                'min:1',
                'max:200',
            ],


            'items.*.purchase_order_item_id' => [
                'bail',
                'required',
                'integer',
                'distinct',

                Rule::exists(
                    'purchase_order_items',
                    'id'
                ),
            ],


            /*
            |--------------------------------------------------------------------------
            | Receive Quantity
            |--------------------------------------------------------------------------
            |
            | Inventory quantity uses 4-decimal precision.
            |
            */

            'items.*.receive_quantity' => [
                'bail',
                'required',
                'numeric',
                'decimal:0,4',
                'gt:0',
                'max:9999999999.9999',
            ],


            /*
            |--------------------------------------------------------------------------
            | Receive Unit Cost
            |--------------------------------------------------------------------------
            */

            'items.*.unit_cost' => [
                'bail',
                'nullable',
                'numeric',
                'decimal:0,4',
                'min:0',
                'max:9999999999.9999',
            ],


            'items.*.notes' => [
                'bail',
                'nullable',
                'string',
                'max:2000',
            ],


            /*
            |--------------------------------------------------------------------------
            | Optional Supplier Payment
            |--------------------------------------------------------------------------
            */

            'payment' => [
                'bail',
                'nullable',
                'array',
            ],


            'payment.amount' => [
                'bail',
                'required_with:payment',
                'numeric',
                'decimal:0,2',
                'gt:0',
                'max:999999999999.99',
            ],


            'payment.payment_method' => [
                'bail',
                'required_with:payment.amount',
                'string',
                'max:50',

                Rule::in(
                    PurchaseOrderPayment::paymentMethods()
                ),
            ],


            'payment.payment_date' => [
                'bail',
                'nullable',
                'date',
            ],


            'payment.transaction_reference' => [
                'bail',
                'nullable',
                'string',
                'max:255',
            ],


            'payment.notes' => [
                'bail',
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Additional Business Validation
    |--------------------------------------------------------------------------
    */

    public function withValidator(
        Validator $validator
    ): void {

        $validator->after(
            function (
                Validator $validator
            ): void {

                if (
                    $validator
                        ->errors()
                        ->has('items')
                ) {
                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | Purchase Order
                |--------------------------------------------------------------------------
                */

                $purchaseOrder =
                    $this->getPurchaseOrder();


                if (!$purchaseOrder) {

                    $validator
                        ->errors()
                        ->add(
                            'purchase_order',
                            'Purchase order was not found.'
                        );


                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | Receive Status
                |--------------------------------------------------------------------------
                */

                if (
                    !in_array(
                        $purchaseOrder->status,
                        [
                            PurchaseOrder::STATUS_ORDERED,
                            PurchaseOrder::STATUS_PARTIAL,
                        ],
                        true
                    )
                ) {

                    $message =
                        match (
                            $purchaseOrder->status
                        ) {

                            PurchaseOrder::STATUS_RECEIVED =>
                                'This purchase order has already been fully received.',


                            PurchaseOrder::STATUS_CANCELLED =>
                                'A cancelled purchase order cannot be received.',


                            default =>
                                'This purchase order is not available for receiving.',
                        };


                    $validator
                        ->errors()
                        ->add(
                            'purchase_order',
                            $message
                        );


                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | Submitted Items
                |--------------------------------------------------------------------------
                */

                $submittedItems =
                    $this->input(
                        'items',
                        []
                    );


                if (
                    !is_array(
                        $submittedItems
                    )
                    ||
                    $submittedItems === []
                ) {
                    return;
                }


                $submittedIds =
                    collect(
                        $submittedItems
                    )
                        ->filter(
                            static fn (
                                mixed $item
                            ): bool =>
                                is_array($item)
                                &&
                                isset(
                                    $item[
                                        'purchase_order_item_id'
                                    ]
                                )
                        )
                        ->pluck(
                            'purchase_order_item_id'
                        )
                        ->map(
                            static fn (
                                mixed $id
                            ): int =>
                                (int) $id
                        )
                        ->unique()
                        ->values();


                if ($submittedIds->isEmpty()) {
                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | Purchase Order Item Ownership
                |--------------------------------------------------------------------------
                */

                $purchaseItems =
                    PurchaseOrderItem::query()
                        ->where(
                            'purchase_order_id',
                            $purchaseOrder->id
                        )
                        ->whereIn(
                            'id',
                            $submittedIds->all()
                        )
                        ->with(
                            'rawMaterial'
                        )
                        ->get()
                        ->keyBy(
                            'id'
                        );


                foreach (
                    $submittedItems
                    as
                    $index => $submittedItem
                ) {

                    if (
                        !is_array(
                            $submittedItem
                        )
                        ||
                        !isset(
                            $submittedItem[
                                'purchase_order_item_id'
                            ]
                        )
                    ) {
                        continue;
                    }


                    $purchaseItemId =
                        (int) $submittedItem[
                            'purchase_order_item_id'
                        ];


                    /** @var PurchaseOrderItem|null $purchaseItem */
                    $purchaseItem =
                        $purchaseItems->get(
                            $purchaseItemId
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | Wrong Purchase Order Protection
                    |--------------------------------------------------------------------------
                    */

                    if (!$purchaseItem) {

                        $validator
                            ->errors()
                            ->add(
                                "items.{$index}.purchase_order_item_id",
                                'The selected item does not belong to this purchase order.'
                            );


                        continue;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Raw Material Protection
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $purchaseItem
                            ->raw_material_id
                        ===
                        null
                    ) {

                        $validator
                            ->errors()
                            ->add(
                                "items.{$index}.purchase_order_item_id",
                                'This purchase order item is not linked to a raw material.'
                            );


                        continue;
                    }


                    if (
                        !$purchaseItem
                            ->rawMaterial
                    ) {

                        $validator
                            ->errors()
                            ->add(
                                "items.{$index}.purchase_order_item_id",
                                'The raw material linked to this purchase order item was not found.'
                            );


                        continue;
                    }


                    if (
                        !(bool) $purchaseItem
                            ->rawMaterial
                            ->is_active
                    ) {

                        $validator
                            ->errors()
                            ->add(
                                "items.{$index}.purchase_order_item_id",
                                'The raw material linked to this purchase order item is inactive.'
                            );


                        continue;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Quantity Validation
                    |--------------------------------------------------------------------------
                    */

                    if (
                        !array_key_exists(
                            'receive_quantity',
                            $submittedItem
                        )
                        ||
                        !is_numeric(
                            $submittedItem[
                                'receive_quantity'
                            ]
                        )
                    ) {
                        continue;
                    }


                    $receiveQuantity =
                        round(
                            (float) $submittedItem[
                                'receive_quantity'
                            ],
                            4
                        );


                    $orderedQuantity =
                        round(
                            (float) $purchaseItem
                                ->quantity,
                            4
                        );


                    $receivedQuantity =
                        round(
                            (float) $purchaseItem
                                ->received_quantity,
                            4
                        );


                    $remainingQuantity =
                        max(
                            0,
                            round(
                                $orderedQuantity
                                -
                                $receivedQuantity,
                                4
                            )
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | Already Fully Received
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $remainingQuantity
                        <=
                        0
                    ) {

                        $validator
                            ->errors()
                            ->add(
                                "items.{$index}.receive_quantity",
                                "The item \"{$purchaseItem->item_name}\" has already been fully received."
                            );


                        continue;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Over Receive Protection
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $receiveQuantity
                        >
                        $remainingQuantity
                    ) {

                        $validator
                            ->errors()
                            ->add(
                                "items.{$index}.receive_quantity",
                                "Receive quantity cannot exceed the remaining quantity of {$this->formatQuantity($remainingQuantity)} {$purchaseItem->unit}."
                            );
                    }
                }
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Validation Messages
    |--------------------------------------------------------------------------
    */

    public function messages(): array
    {
        return [

            'items.required' =>
                'At least one purchase order item is required for receiving.',


            'items.min' =>
                'At least one purchase order item is required for receiving.',


            'items.max' =>
                'A maximum of 200 items can be received in one request.',


            'items.*.purchase_order_item_id.distinct' =>
                'The same purchase order item cannot be received more than once in the same request.',


            'items.*.purchase_order_item_id.exists' =>
                'The selected purchase order item was not found.',


            'items.*.receive_quantity.required' =>
                'Receive quantity is required.',


            'items.*.receive_quantity.decimal' =>
                'Receive quantity can contain a maximum of 4 decimal places.',


            'items.*.receive_quantity.gt' =>
                'Receive quantity must be greater than zero.',


            'items.*.unit_cost.decimal' =>
                'Unit cost can contain a maximum of 4 decimal places.',


            'items.*.unit_cost.min' =>
                'Unit cost cannot be negative.',


            'payment.amount.required_with' =>
                'Payment amount is required when supplier payment is selected.',


            'payment.amount.decimal' =>
                'Payment amount can contain a maximum of 2 decimal places.',


            'payment.amount.gt' =>
                'Payment amount must be greater than zero.',


            'payment.payment_method.required_with' =>
                'Payment method is required when a payment amount is entered.',


            'payment.payment_method.in' =>
                'Please select a valid payment method.',


            'payment.payment_date.date' =>
                'Payment date must be a valid date.',
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Attributes
    |--------------------------------------------------------------------------
    */

    public function attributes(): array
    {
        return [

            'items.*.purchase_order_item_id' =>
                'purchase order item',


            'items.*.receive_quantity' =>
                'receive quantity',


            'items.*.unit_cost' =>
                'unit cost',


            'items.*.notes' =>
                'item notes',


            'notes' =>
                'receive notes',


            'payment.amount' =>
                'payment amount',


            'payment.payment_method' =>
                'payment method',


            'payment.payment_date' =>
                'payment date',


            'payment.transaction_reference' =>
                'transaction reference',


            'payment.notes' =>
                'payment notes',
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Purchase Order From Route
    |--------------------------------------------------------------------------
    */

    private function getPurchaseOrder(): ?PurchaseOrder
    {
        $purchaseOrder =
            $this->route(
                'purchaseOrder'
            )
            ??
            $this->route(
                'purchase_order'
            );


        if (
            $purchaseOrder
            instanceof
            PurchaseOrder
        ) {
            return $purchaseOrder;
        }


        if (
            is_numeric(
                $purchaseOrder
            )
        ) {

            return PurchaseOrder::query()
                ->find(
                    (int) $purchaseOrder
                );
        }


        return null;
    }


    /*
    |--------------------------------------------------------------------------
    | Quantity Formatter
    |--------------------------------------------------------------------------
    */

    private function formatQuantity(
        mixed $quantity
    ): string {

        $formatted =
            number_format(
                (float) $quantity,
                4,
                '.',
                ''
            );


        return rtrim(
            rtrim(
                $formatted,
                '0'
            ),
            '.'
        );
    }
}