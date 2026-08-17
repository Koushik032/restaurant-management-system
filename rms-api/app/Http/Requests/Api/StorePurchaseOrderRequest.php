<?php

namespace App\Http\Requests\Api;

use App\Models\PurchaseOrderPayment;
use App\Models\RawMaterial;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;


class StorePurchaseOrderRequest extends FormRequest
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
        | Payment Method
        |--------------------------------------------------------------------------
        */

        if ($this->exists('payment_method')) {
            $paymentMethod = strtolower(
                trim(
                    (string) $this->input(
                        'payment_method'
                    )
                )
            );

            $data['payment_method'] =
                $paymentMethod !== ''
                    ? $paymentMethod
                    : null;
        }


        /*
        |--------------------------------------------------------------------------
        | Initial Payment Date
        |--------------------------------------------------------------------------
        */

        if ($this->exists('payment_date')) {
            $paymentDate = trim(
                (string) $this->input(
                    'payment_date'
                )
            );

            $data['payment_date'] =
                $paymentDate !== ''
                    ? $paymentDate
                    : null;
        }


        /*
        |--------------------------------------------------------------------------
        | Initial Payment Transaction Reference
        |--------------------------------------------------------------------------
        */

        if ($this->exists('transaction_reference')) {
            $reference = trim(
                (string) $this->input(
                    'transaction_reference'
                )
            );

            $data['transaction_reference'] =
                $reference !== ''
                    ? $reference
                    : null;
        }


        /*
        |--------------------------------------------------------------------------
        | Initial Payment Notes
        |--------------------------------------------------------------------------
        */

        if ($this->exists('payment_notes')) {
            $paymentNotes = trim(
                (string) $this->input(
                    'payment_notes'
                )
            );

            $data['payment_notes'] =
                $paymentNotes !== ''
                    ? $paymentNotes
                    : null;
        }


        /*
        |--------------------------------------------------------------------------
        | Purchase Order Notes
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
        | Purchase Items
        |--------------------------------------------------------------------------
        |
        | RawMaterial is authoritative for item_name and unit.
        |
        | item_name / unit are accepted only for frontend compatibility.
        | The service replaces them with the RawMaterial snapshot.
        |
        */

        $items = $this->input(
            'items'
        );

        if (is_array($items)) {
            $data['items'] = collect($items)
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
                            $itemName = trim(
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
                            $unit = strtolower(
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
            | Order Date
            |--------------------------------------------------------------------------
            */

            'order_date' => [
                'bail',
                'required',
                'date',
            ],


            /*
            |--------------------------------------------------------------------------
            | Delivery Date
            |--------------------------------------------------------------------------
            */

            'delivery_date' => [
                'bail',
                'nullable',
                'date',
                'after_or_equal:order_date',
            ],


            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            |
            | New purchase orders always start as ordered.
            |
            | partially_received / received:
            | PurchaseReceiveService
            |
            | cancelled:
            | dedicated status flow
            |
            */

            'status' => [
                'prohibited',
            ],


            /*
            |--------------------------------------------------------------------------
            | Tax
            |--------------------------------------------------------------------------
            */

            'tax' => [
                'bail',
                'nullable',
                'numeric',
                'decimal:0,2',
                'min:0',
                'max:999999999999.99',
            ],


            /*
            |--------------------------------------------------------------------------
            | Service Charge
            |--------------------------------------------------------------------------
            */

            'service_charge' => [
                'bail',
                'nullable',
                'numeric',
                'decimal:0,2',
                'min:0',
                'max:999999999999.99',
            ],


            /*
            |--------------------------------------------------------------------------
            | Initial / Advance Payment
            |--------------------------------------------------------------------------
            |
            | Purchase order money uses 2-decimal precision.
            |
            */

            'paid_amount' => [
                'bail',
                'nullable',
                'numeric',
                'decimal:0,2',
                'min:0',
                'max:999999999999.99',
            ],


            'payment_method' => [
                'bail',
                'nullable',
                'string',
                'max:50',

                Rule::in(
                    PurchaseOrderPayment::paymentMethods()
                ),
            ],


            'payment_date' => [
                'bail',
                'nullable',
                'date_format:Y-m-d',
            ],


            'transaction_reference' => [
                'bail',
                'nullable',
                'string',
                'max:255',
            ],


            'payment_notes' => [
                'bail',
                'nullable',
                'string',
                'max:2000',
            ],


            /*
            |--------------------------------------------------------------------------
            | Purchase Order Notes
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
            | Purchase Items
            |--------------------------------------------------------------------------
            */

            'items' => [
                'bail',
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
            | Not authoritative. PurchaseOrderService uses RawMaterial name.
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
            | Not authoritative. If supplied, it must be valid and must match
            | the selected RawMaterial base unit.
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
            | Inventory quantity uses 4-decimal precision.
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
            | Purchase order money uses 2-decimal precision.
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

                /*
                |--------------------------------------------------------------------------
                | Initial Payment Validation
                |--------------------------------------------------------------------------
                */

                $paidAmount =
                    $this->input(
                        'paid_amount'
                    );


                if (
                    $paidAmount !== null
                    &&
                    $paidAmount !== ''
                    &&
                    is_numeric(
                        $paidAmount
                    )
                ) {
                    $numericPaidAmount =
                        round(
                            (float) $paidAmount,
                            2
                        );


                    if (
                        $numericPaidAmount > 0
                        &&
                        empty(
                            $this->input(
                                'payment_method'
                            )
                        )
                    ) {
                        $validator
                            ->errors()
                            ->add(
                                'payment_method',
                                'Payment method is required when an advance payment is provided.'
                            );
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | Purchase Items
                |--------------------------------------------------------------------------
                */

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
                | Validate Submitted Unit Against Base Unit
                |--------------------------------------------------------------------------
                |
                | Unit is optional because PurchaseOrderService gets it from
                | RawMaterial. If frontend submits it, mismatch is rejected.
                |
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
            | Status
            |--------------------------------------------------------------------------
            */

            'status.prohibited' =>
                'Purchase order status cannot be selected when creating a purchase order.',


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
            | Initial Payment
            |--------------------------------------------------------------------------
            */

            'paid_amount.numeric' =>
                'Paid amount must be numeric.',

            'paid_amount.decimal' =>
                'Paid amount may contain up to 2 decimal places.',

            'paid_amount.min' =>
                'Paid amount cannot be negative.',

            'payment_method.string' =>
                'Payment method must be valid text.',

            'payment_method.in' =>
                'The selected payment method is invalid.',

            'payment_date.date_format' =>
                'Payment date must be in YYYY-MM-DD format.',

            'transaction_reference.max' =>
                'Transaction reference cannot exceed 255 characters.',

            'payment_notes.max' =>
                'Payment notes cannot exceed 2000 characters.',


            /*
            |--------------------------------------------------------------------------
            | Notes
            |--------------------------------------------------------------------------
            */

            'notes.string' =>
                'Notes must be valid text.',

            'notes.max' =>
                'Notes cannot exceed 2000 characters.',


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

            'tax' =>
                'tax',

            'service_charge' =>
                'service charge',

            'paid_amount' =>
                'advance paid amount',

            'payment_method' =>
                'payment method',

            'payment_date' =>
                'payment date',

            'transaction_reference' =>
                'transaction reference',

            'payment_notes' =>
                'payment notes',

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