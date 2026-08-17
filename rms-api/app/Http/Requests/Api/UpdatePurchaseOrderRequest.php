<?php

namespace App\Http\Requests\Api;

use App\Models\PurchaseOrder;
use App\Models\RawMaterial;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;


class UpdatePurchaseOrderRequest extends FormRequest
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
    | Prepare Input
    |--------------------------------------------------------------------------
    */

    protected function prepareForValidation(): void
    {
        $data = [];


        /*
        |--------------------------------------------------------------------------
        | Delivery Date Compatibility
        |--------------------------------------------------------------------------
        |
        | delivery_date is compared against order_date. If only delivery_date
        | is submitted, inject the existing order_date for validation only.
        |
        */

        if (
            $this->exists('delivery_date')
            &&
            !$this->exists('order_date')
        ) {
            $purchaseOrder =
                $this->getPurchaseOrder();


            if ($purchaseOrder) {
                $data['order_date'] =
                    $purchaseOrder->order_date;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Notes
        |--------------------------------------------------------------------------
        */

        if ($this->exists('notes')) {
            $notes =
                trim(
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
        | Purchase Items
        |--------------------------------------------------------------------------
        |
        | RawMaterial is authoritative for item_name and unit.
        | These fields are accepted only for frontend compatibility.
        |
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
                                    'item_name',
                                    $item
                                )
                            ) {
                                $itemName =
                                    trim(
                                        (string) (
                                            $item['item_name']
                                            ?? ''
                                        )
                                    );


                                $item['item_name'] =
                                    $itemName !== ''
                                        ? $itemName
                                        : null;
                            }


                            if (
                                array_key_exists(
                                    'unit',
                                    $item
                                )
                            ) {
                                $unit =
                                    strtolower(
                                        trim(
                                            (string) (
                                                $item['unit']
                                                ?? ''
                                            )
                                        )
                                    );


                                $item['unit'] =
                                    $unit !== ''
                                        ? $unit
                                        : null;
                            }


                            return $item;
                        }
                    )
                    ->values()
                    ->all();
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
            | Supplier
            |--------------------------------------------------------------------------
            */

            'supplier_id' => [
                'bail',
                'sometimes',
                'required',
                'integer',

                Rule::exists(
                    'suppliers',
                    'id'
                )
                    ->whereNull(
                        'deleted_at'
                    ),
            ],


            /*
            |--------------------------------------------------------------------------
            | Order Information
            |--------------------------------------------------------------------------
            */

            'order_date' => [
                'bail',
                'sometimes',
                'required',
                'date',
            ],


            'delivery_date' => [
                'bail',
                'sometimes',
                'nullable',
                'date',
                'after_or_equal:order_date',
            ],


            /*
            |--------------------------------------------------------------------------
            | Protected Fields
            |--------------------------------------------------------------------------
            |
            | Purchase status and payment ledger information must be changed
            | through their dedicated flows/services.
            |
            */

            'status' => [
                'prohibited',
            ],


            'paid_amount' => [
                'prohibited',
            ],


            'due_amount' => [
                'prohibited',
            ],


            'payment_method' => [
                'prohibited',
            ],


            'payment_date' => [
                'prohibited',
            ],


            'transaction_reference' => [
                'prohibited',
            ],


            'payment_notes' => [
                'prohibited',
            ],


            /*
            |--------------------------------------------------------------------------
            | Amounts
            |--------------------------------------------------------------------------
            */

            'tax' => [
                'bail',
                'sometimes',
                'nullable',
                'numeric',
                'decimal:0,2',
                'min:0',
                'max:999999999999.99',
            ],


            'service_charge' => [
                'bail',
                'sometimes',
                'nullable',
                'numeric',
                'decimal:0,2',
                'min:0',
                'max:999999999999.99',
            ],


            /*
            |--------------------------------------------------------------------------
            | Notes
            |--------------------------------------------------------------------------
            */

            'notes' => [
                'bail',
                'sometimes',
                'nullable',
                'string',
                'max:2000',
            ],


            /*
            |--------------------------------------------------------------------------
            | Purchase Items
            |--------------------------------------------------------------------------
            */

            'items' => [
                'bail',
                'sometimes',
                'required',
                'array',
                'min:1',
                'max:200',
            ],


            /*
            |--------------------------------------------------------------------------
            | Raw Material
            |--------------------------------------------------------------------------
            */

            'items.*.raw_material_id' => [
                'bail',
                'required',
                'integer',
                'distinct',

                Rule::exists(
                    'raw_materials',
                    'id'
                )
                    ->where(
                        static function (
                            $query
                        ): void {

                            $query
                                ->whereNull(
                                    'deleted_at'
                                )
                                ->where(
                                    'is_active',
                                    true
                                );
                        }
                    ),
            ],


            /*
            |--------------------------------------------------------------------------
            | Item Name Compatibility Field
            |--------------------------------------------------------------------------
            |
            | PurchaseOrderService uses RawMaterial material_name as source
            | of truth. This field is not authoritative.
            |
            */

            'items.*.item_name' => [
                'bail',
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],


            /*
            |--------------------------------------------------------------------------
            | Unit Compatibility Field
            |--------------------------------------------------------------------------
            |
            | PurchaseOrderService uses RawMaterial base_unit as source of
            | truth. If supplied, it still must match that base unit.
            |
            */

            'items.*.unit' => [
                'bail',
                'sometimes',
                'nullable',
                'string',
                'max:30',

                Rule::in(
                    RawMaterial::allowedUnits()
                ),
            ],


            /*
            |--------------------------------------------------------------------------
            | Quantity
            |--------------------------------------------------------------------------
            |
            | Inventory quantities use 4-decimal precision.
            |
            */

            'items.*.quantity' => [
                'bail',
                'required',
                'numeric',
                'decimal:0,4',
                'gt:0',
                'max:9999999999.9999',
            ],


            /*
            |--------------------------------------------------------------------------
            | Unit Price
            |--------------------------------------------------------------------------
            |
            | Purchase money uses 2-decimal precision.
            |
            */

            'items.*.unit_price' => [
                'bail',
                'required',
                'numeric',
                'decimal:0,2',
                'min:0',
                'max:999999999999.99',
            ],
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Additional Validation
    |--------------------------------------------------------------------------
    */

    public function withValidator(
        Validator $validator
    ): void {

        $validator->after(
            function (
                Validator $validator
            ): void {

                $purchaseOrder =
                    $this->getPurchaseOrder();


                /*
                |--------------------------------------------------------------------------
                | Purchase Order Missing
                |--------------------------------------------------------------------------
                */

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
                | Prevent Item Editing After Receiving Starts
                |--------------------------------------------------------------------------
                |
                | PurchaseOrderService remains authoritative. This provides
                | earlier API feedback before the service transaction.
                |
                */

                if (
                    $this->exists(
                        'items'
                    )
                    &&
                    $purchaseOrder
                        ->items()
                        ->where(
                            'received_quantity',
                            '>',
                            0
                        )
                        ->exists()
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'items',
                            'Purchase order items cannot be changed because receiving has already started.'
                        );


                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | Nothing Else To Validate If Items Were Not Submitted
                |--------------------------------------------------------------------------
                */

                if (
                    !$this->exists(
                        'items'
                    )
                ) {
                    return;
                }


                $items =
                    $this->input(
                        'items',
                        []
                    );


                if (
                    !is_array($items)
                    ||
                    $items === []
                ) {
                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | Collect Raw Material IDs
                |--------------------------------------------------------------------------
                */

                $rawMaterialIds =
                    collect($items)
                        ->filter(
                            static fn (
                                mixed $item
                            ): bool =>
                                is_array(
                                    $item
                                )
                        )
                        ->pluck(
                            'raw_material_id'
                        )
                        ->filter(
                            static fn (
                                mixed $id
                            ): bool =>
                                filter_var(
                                    $id,
                                    FILTER_VALIDATE_INT
                                ) !== false
                        )
                        ->map(
                            static fn (
                                mixed $id
                            ): int =>
                                (int) $id
                        )
                        ->unique()
                        ->values();


                if (
                    $rawMaterialIds
                        ->isEmpty()
                ) {
                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | Load Active Raw Materials
                |--------------------------------------------------------------------------
                */

                $rawMaterials =
                    RawMaterial::query()
                        ->whereIn(
                            'id',
                            $rawMaterialIds
                        )
                        ->whereNull(
                            'deleted_at'
                        )
                        ->where(
                            'is_active',
                            true
                        )
                        ->get()
                        ->keyBy(
                            'id'
                        );


                /*
                |--------------------------------------------------------------------------
                | Validate Submitted Unit Against RawMaterial Base Unit
                |--------------------------------------------------------------------------
                */

                foreach (
                    $items
                    as
                    $index => $item
                ) {

                    if (!is_array($item)) {
                        continue;
                    }


                    $rawMaterialId =
                        filter_var(
                            $item[
                                'raw_material_id'
                            ]
                            ?? null,
                            FILTER_VALIDATE_INT
                        );


                    if (
                        $rawMaterialId === false
                        ||
                        $rawMaterialId === null
                    ) {
                        continue;
                    }


                    $rawMaterial =
                        $rawMaterials->get(
                            (int) $rawMaterialId
                        );


                    if (!$rawMaterial) {
                        continue;
                    }


                    if (
                        !array_key_exists(
                            'unit',
                            $item
                        )
                        ||
                        $item['unit'] === null
                        ||
                        trim(
                            (string) $item['unit']
                        ) === ''
                    ) {
                        continue;
                    }


                    $submittedUnit =
                        strtolower(
                            trim(
                                (string) $item['unit']
                            )
                        );


                    $rawMaterialUnit =
                        strtolower(
                            trim(
                                (string) $rawMaterial
                                    ->base_unit
                            )
                        );


                    if (
                        $submittedUnit
                        !==
                        $rawMaterialUnit
                    ) {
                        $validator
                            ->errors()
                            ->add(
                                "items.{$index}.unit",
                                "The unit for \"{$rawMaterial->material_name}\" must be {$rawMaterial->base_unit}."
                            );
                    }
                }
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Resolve Purchase Order
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
            &&
            (int) $purchaseOrder > 0
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
    | Validation Messages
    |--------------------------------------------------------------------------
    */

    public function messages(): array
    {
        return [

            /*
            |--------------------------------------------------------------------------
            | Supplier
            |--------------------------------------------------------------------------
            */

            'supplier_id.required' =>
                'Please select a supplier.',


            'supplier_id.integer' =>
                'The selected supplier is invalid.',


            'supplier_id.exists' =>
                'The selected supplier was not found, is deleted, or is unavailable.',


            /*
            |--------------------------------------------------------------------------
            | Order Date
            |--------------------------------------------------------------------------
            */

            'order_date.required' =>
                'Purchase order date is required.',


            'order_date.date' =>
                'Purchase order date must be a valid date.',


            /*
            |--------------------------------------------------------------------------
            | Delivery Date
            |--------------------------------------------------------------------------
            */

            'delivery_date.date' =>
                'Delivery date must be a valid date.',


            'delivery_date.after_or_equal' =>
                'Delivery date cannot be earlier than the order date.',


            /*
            |--------------------------------------------------------------------------
            | Protected Fields
            |--------------------------------------------------------------------------
            */

            'status.prohibited' =>
                'Purchase order status cannot be changed from this endpoint.',


            'paid_amount.prohibited' =>
                'Paid amount cannot be edited directly. Please use the purchase payment API.',


            'due_amount.prohibited' =>
                'Due amount cannot be edited directly. It is calculated from the payment ledger.',


            'payment_method.prohibited' =>
                'Payment method cannot be edited directly. Please use the purchase payment API.',


            'payment_date.prohibited' =>
                'Payment date cannot be edited from the purchase order update endpoint.',


            'transaction_reference.prohibited' =>
                'Payment transaction reference cannot be edited from the purchase order update endpoint.',


            'payment_notes.prohibited' =>
                'Payment notes cannot be edited from the purchase order update endpoint.',


            /*
            |--------------------------------------------------------------------------
            | Tax
            |--------------------------------------------------------------------------
            */

            'tax.numeric' =>
                'Tax must be numeric.',


            'tax.decimal' =>
                'Tax may contain up to 2 decimal places.',


            'tax.min' =>
                'Tax cannot be negative.',


            /*
            |--------------------------------------------------------------------------
            | Service Charge
            |--------------------------------------------------------------------------
            */

            'service_charge.numeric' =>
                'Service charge must be numeric.',


            'service_charge.decimal' =>
                'Service charge may contain up to 2 decimal places.',


            'service_charge.min' =>
                'Service charge cannot be negative.',


            /*
            |--------------------------------------------------------------------------
            | Items
            |--------------------------------------------------------------------------
            */

            'items.required' =>
                'At least one purchase item is required.',


            'items.array' =>
                'Purchase items must be provided as a valid list.',


            'items.min' =>
                'At least one purchase item is required.',


            'items.max' =>
                'A purchase order cannot contain more than 200 items.',


            /*
            |--------------------------------------------------------------------------
            | Raw Material
            |--------------------------------------------------------------------------
            */

            'items.*.raw_material_id.required' =>
                'Please select a raw material for every item.',


            'items.*.raw_material_id.integer' =>
                'The selected raw material is invalid.',


            'items.*.raw_material_id.distinct' =>
                'The same raw material cannot be added more than once.',


            'items.*.raw_material_id.exists' =>
                'The selected raw material is unavailable, inactive or deleted.',


            /*
            |--------------------------------------------------------------------------
            | Item Name
            |--------------------------------------------------------------------------
            */

            'items.*.item_name.string' =>
                'Item name must be valid text.',


            'items.*.item_name.max' =>
                'Item name cannot exceed 255 characters.',


            /*
            |--------------------------------------------------------------------------
            | Unit
            |--------------------------------------------------------------------------
            */

            'items.*.unit.string' =>
                'Item unit must be valid text.',


            'items.*.unit.in' =>
                'The selected item unit is invalid.',


            /*
            |--------------------------------------------------------------------------
            | Quantity
            |--------------------------------------------------------------------------
            */

            'items.*.quantity.required' =>
                'Ordered quantity is required.',


            'items.*.quantity.numeric' =>
                'Ordered quantity must be numeric.',


            'items.*.quantity.decimal' =>
                'Ordered quantity may contain up to 4 decimal places.',


            'items.*.quantity.gt' =>
                'Ordered quantity must be greater than zero.',


            'items.*.quantity.max' =>
                'Ordered quantity exceeds the maximum allowed value.',


            /*
            |--------------------------------------------------------------------------
            | Unit Price
            |--------------------------------------------------------------------------
            */

            'items.*.unit_price.required' =>
                'Unit price is required.',


            'items.*.unit_price.numeric' =>
                'Unit price must be numeric.',


            'items.*.unit_price.decimal' =>
                'Unit price may contain up to 2 decimal places.',


            'items.*.unit_price.min' =>
                'Unit price cannot be negative.',


            /*
            |--------------------------------------------------------------------------
            | Notes
            |--------------------------------------------------------------------------
            */

            'notes.string' =>
                'Notes must be valid text.',


            'notes.max' =>
                'Notes cannot exceed 2000 characters.',
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Attribute Names
    |--------------------------------------------------------------------------
    */

    public function attributes(): array
    {
        return [

            'supplier_id' =>
                'supplier',


            'order_date' =>
                'order date',


            'delivery_date' =>
                'delivery date',


            'status' =>
                'purchase order status',


            'paid_amount' =>
                'paid amount',


            'due_amount' =>
                'due amount',


            'payment_method' =>
                'payment method',


            'payment_date' =>
                'payment date',


            'transaction_reference' =>
                'transaction reference',


            'payment_notes' =>
                'payment notes',


            'tax' =>
                'tax',


            'service_charge' =>
                'service charge',


            'notes' =>
                'purchase order notes',


            'items.*.raw_material_id' =>
                'raw material',


            'items.*.item_name' =>
                'item name',


            'items.*.unit' =>
                'item unit',


            'items.*.quantity' =>
                'ordered quantity',


            'items.*.unit_price' =>
                'unit price',
        ];
    }
}