<?php

namespace App\Http\Requests\Api;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine whether the authenticated user
     * can create an order.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Prepare incoming data before validation.
     */
    protected function prepareForValidation(): void
    {
        $customerName = $this->input(
            'customer_name'
        );

        $customerPhone = $this->input(
            'customer_phone'
        );

        $customerEmail = $this->input(
            'customer_email'
        );

        $paymentMethod = $this->input(
            'payment_method'
        );

        $paymentReference = $this->input(
            'payment_reference'
        );

        $items = collect(
            $this->input('items', [])
        )
            ->map(
                static function (
                    mixed $item
                ): mixed {
                    if (!is_array($item)) {
                        return $item;
                    }

                    return [
                        ...$item,

                        'quantity' =>
                            isset($item['quantity'])
                                ? (int) $item['quantity']
                                : 1,

                        'addon_ids' =>
                            collect(
                                $item['addon_ids'] ?? []
                            )
                                ->filter(
                                    static fn (
                                        mixed $id
                                    ): bool =>
                                        $id !== null &&
                                        $id !== ''
                                )
                                ->map(
                                    static fn (
                                        mixed $id
                                    ): int =>
                                        (int) $id
                                )
                                ->unique()
                                ->values()
                                ->all(),

                        'kitchen_note' =>
                            isset(
                                $item['kitchen_note']
                            )
                                ? trim(
                                    (string)
                                    $item['kitchen_note']
                                )
                                : null,
                    ];
                }
            )
            ->values()
            ->all();

        $mergedTableIds = collect(
            $this->input(
                'merged_table_ids',
                []
            )
        )
            ->filter(
                static fn (
                    mixed $id
                ): bool =>
                    $id !== null &&
                    $id !== ''
            )
            ->map(
                static fn (
                    mixed $id
                ): int =>
                    (int) $id
            )
            ->unique()
            ->values()
            ->all();

        $paidAmount = $this->filled(
            'paid_amount'
        )
            ? round(
                max(
                    0,
                    (float) $this->input(
                        'paid_amount'
                    )
                ),
                2
            )
            : 0;

        $this->merge([
            'status' =>
                $this->filled('status')
                    ? $this->input('status')
                    : Order::STATUS_PENDING,

            'discount_amount' =>
                $this->filled(
                    'discount_amount'
                )
                    ? $this->input(
                        'discount_amount'
                    )
                    : 0,

            /*
             * Tax and service charge are fixed at zero
             * for now. Backend will calculate them again.
             */
            'tax_amount' => 0,
            'service_charge' => 0,

            /*
             * Payment information.
             */
            'paid_amount' =>
                $paidAmount,

            'payment_method' =>
                is_string($paymentMethod)
                    ? strtolower(
                        trim($paymentMethod)
                    )
                    : $paymentMethod,

            'payment_reference' =>
                is_string($paymentReference)
                    ? trim($paymentReference)
                    : $paymentReference,

            'customer_name' =>
                is_string($customerName)
                    ? trim($customerName)
                    : $customerName,

            'customer_phone' =>
                is_string($customerPhone)
                    ? trim($customerPhone)
                    : $customerPhone,

            'customer_email' =>
                is_string($customerEmail)
                    ? strtolower(
                        trim($customerEmail)
                    )
                    : $customerEmail,

            'merged_table_ids' =>
                $mergedTableIds,

            'items' => $items,
        ]);
    }

    /**
     * Validation rules for creating an order.
     */
    public function rules(): array
    {
        return [
            /*
            |--------------------------------------------------------------------------
            | Primary and merged tables
            |--------------------------------------------------------------------------
            */

            'restaurant_table_id' => [
                'required',
                'integer',
                'exists:restaurant_tables,id',
            ],

            'merged_table_ids' => [
                'nullable',
                'array',
            ],

            'merged_table_ids.*' => [
                'required',
                'integer',
                'distinct',
                'different:restaurant_table_id',
                'exists:restaurant_tables,id',
            ],

            /*
            |--------------------------------------------------------------------------
            | Customer
            |--------------------------------------------------------------------------
            */

            'customer_id' => [
                'nullable',
                'integer',
                'exists:customers,id',
            ],

            'customer_name' => [
                'nullable',
                'string',
                'max:150',
            ],

            'customer_phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'customer_email' => [
                'nullable',
                'email:rfc',
                'max:190',
            ],

            /*
            |--------------------------------------------------------------------------
            | Order information
            |--------------------------------------------------------------------------
            */

            'status' => [
                'required',
                'string',
                Rule::in([
                    Order::STATUS_PENDING,
                    Order::STATUS_PREPARING,
                    Order::STATUS_READY,
                    Order::STATUS_SERVED,
                ]),
            ],

            'discount_amount' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999999.99',
            ],

            'tax_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'service_charge' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'order_note' => [
                'nullable',
                'string',
                'max:2000',
            ],

            /*
            |--------------------------------------------------------------------------
            | Payment information
            |--------------------------------------------------------------------------
            */

            'paid_amount' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999999.99',
            ],

            'payment_method' => [
                Rule::requiredIf(
                    fn (): bool =>
                        (float) $this->input(
                            'paid_amount',
                            0
                        ) > 0
                ),
                'nullable',
                'string',
                Rule::in(
                    Order::allowedPaymentMethods()
                ),
            ],

            'payment_reference' => [
                'nullable',
                'string',
                'max:150',
            ],

            /*
            |--------------------------------------------------------------------------
            | Order items
            |--------------------------------------------------------------------------
            */

            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*.menu_item_id' => [
                'required',
                'integer',
                'exists:menu_items,id',
            ],

            'items.*.menu_item_variant_id' => [
                'nullable',
                'integer',
                'exists:menu_item_variants,id',
            ],

            'items.*.addon_ids' => [
                'nullable',
                'array',
            ],

            'items.*.addon_ids.*' => [
                'required',
                'integer',
                'distinct',
                'exists:add_ons,id',
            ],

            'items.*.quantity' => [
                'required',
                'integer',
                'min:1',
                'max:999',
            ],

            'items.*.kitchen_note' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    /**
     * Additional payment validation.
     */
    public function after(): array
    {
        return [
            function (
                Validator $validator
            ): void {
                if (
                    $validator->errors()
                        ->isNotEmpty()
                ) {
                    return;
                }

                $paidAmount = round(
                    (float) $this->input(
                        'paid_amount',
                        0
                    ),
                    2
                );

                if (
                    $paidAmount <= 0 &&
                    $this->filled(
                        'payment_method'
                    )
                ) {
                    $validator->errors()->add(
                        'payment_method',
                        'A payment method should only be selected when a payment amount is entered.'
                    );
                }
            },
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'restaurant_table_id.required' =>
                'Please select a table.',

            'restaurant_table_id.exists' =>
                'The selected table no longer exists.',

            'merged_table_ids.array' =>
                'The merged table selection is invalid.',

            'merged_table_ids.*.distinct' =>
                'The same merged table cannot be selected more than once.',

            'merged_table_ids.*.different' =>
                'The primary table cannot also be selected as a merged table.',

            'merged_table_ids.*.exists' =>
                'One or more selected merged tables no longer exist.',

            'customer_id.exists' =>
                'The selected customer no longer exists.',

            'customer_name.max' =>
                'The customer name cannot exceed 150 characters.',

            'customer_phone.max' =>
                'The customer phone number cannot exceed 30 characters.',

            'customer_email.email' =>
                'Please enter a valid customer email address.',

            'status.required' =>
                'Please select an order status.',

            'status.in' =>
                'The selected order status is invalid.',

            'discount_amount.numeric' =>
                'The discount must be a valid number.',

            'discount_amount.min' =>
                'The discount cannot be less than zero.',

            'paid_amount.numeric' =>
                'The paid amount must be a valid number.',

            'paid_amount.min' =>
                'The paid amount cannot be less than zero.',

            'paid_amount.max' =>
                'The paid amount is too large.',

            'payment_method.required' =>
                'Please select a payment method.',

            'payment_method.in' =>
                'The selected payment method is invalid.',

            'payment_reference.max' =>
                'The payment reference cannot exceed 150 characters.',

            'items.required' =>
                'Please add at least one menu item.',

            'items.array' =>
                'The order items must be a valid list.',

            'items.min' =>
                'Please add at least one menu item.',

            'items.*.menu_item_id.required' =>
                'Please select a menu item.',

            'items.*.menu_item_id.exists' =>
                'One or more selected menu items no longer exist.',

            'items.*.menu_item_variant_id.exists' =>
                'The selected variant no longer exists.',

            'items.*.addon_ids.array' =>
                'The selected add-ons are invalid.',

            'items.*.addon_ids.*.distinct' =>
                'The same add-on cannot be selected more than once in one item.',

            'items.*.addon_ids.*.exists' =>
                'One or more selected add-ons no longer exist.',

            'items.*.quantity.required' =>
                'Please enter the item quantity.',

            'items.*.quantity.integer' =>
                'The item quantity must be a whole number.',

            'items.*.quantity.min' =>
                'The item quantity must be at least 1.',

            'items.*.quantity.max' =>
                'The item quantity cannot exceed 999.',

            'items.*.kitchen_note.max' =>
                'The item note cannot exceed 1000 characters.',
        ];
    }

    /**
     * Friendly field names for validation errors.
     */
    public function attributes(): array
    {
        return [
            'restaurant_table_id' =>
                'primary table',

            'merged_table_ids' =>
                'merged tables',

            'customer_name' =>
                'customer name',

            'customer_phone' =>
                'customer phone',

            'customer_email' =>
                'customer email',

            'discount_amount' =>
                'discount',

            'paid_amount' =>
                'paid amount',

            'payment_method' =>
                'payment method',

            'payment_reference' =>
                'payment reference',

            'items.*.menu_item_id' =>
                'menu item',

            'items.*.menu_item_variant_id' =>
                'variant',

            'items.*.addon_ids' =>
                'add-ons',

            'items.*.quantity' =>
                'quantity',

            'items.*.kitchen_note' =>
                'item note',
        ];
    }
}