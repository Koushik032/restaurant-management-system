<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OrderInvoiceController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Download Customer Invoice
    |--------------------------------------------------------------------------
    |
    | একটি নির্দিষ্ট order-এর complete customer invoice PDF তৈরি করে
    | browser-এ download response পাঠাবে।
    |
    */

    public function download(
        Request $request,
        Order $order
    ): Response {
        /*
        |--------------------------------------------------------------------------
        | Load Invoice Relationships
        |--------------------------------------------------------------------------
        |
        | Invoice-এর জন্য প্রয়োজনীয় customer, table, ordered items,
        | add-ons, payments এবং creator data একসঙ্গে load করা হচ্ছে।
        |
        */

        $order->load([
            'customer',

            'primaryTable',

            'tables',

            'items.variant',

            'items.addons',

            'payments.receiver',

            'creator',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Prepare Invoice Items
        |--------------------------------------------------------------------------
        |
        | প্রতিটি order item-এর unit price, quantity, add-on amount এবং
        | final line total clean array format-এ তৈরি করা হচ্ছে।
        |
        */

        $invoiceItems = $order->items
            ->map(
                function ($item): array {
                    $quantity = max(
                        1,
                        (int) $item->quantity
                    );

                    $unitPrice =
                        (float) $item->unit_price;

                    $baseTotal =
                        $unitPrice * $quantity;

                    $addonTotal =
                        (float) $item->addon_total;

                    $lineTotal =
                        (float) $item->line_total;

                    /*
                    |--------------------------------------------------------------------------
                    | Fallback Line Total
                    |--------------------------------------------------------------------------
                    |
                    | Database line_total null বা zero হলে base price এবং
                    | add-on total থেকে line total calculate হবে।
                    |
                    */

                    if (
                        $lineTotal <= 0
                        && (
                            $baseTotal > 0
                            || $addonTotal > 0
                        )
                    ) {
                        $lineTotal =
                            $baseTotal + $addonTotal;
                    }

                    return [
                        'id' =>
                            (int) $item->id,

                        'name' =>
                            $item->item_name
                            ?: 'Menu Item',

                        'variant' =>
                            $item->variant_name,

                        'quantity' =>
                            $quantity,

                        'unit_price' =>
                            $unitPrice,

                        'base_total' =>
                            $baseTotal,

                        'addon_total' =>
                            $addonTotal,

                        'line_total' =>
                            $lineTotal,

                        'kitchen_note' =>
                            $item->kitchen_note,

                        /*
                        |--------------------------------------------------------------------------
                        | Selected Add-ons
                        |--------------------------------------------------------------------------
                        */

                        'addons' =>
                            $item->addons
                                ->map(
                                    static function (
                                        $addon
                                    ): array {
                                        return [
                                            'name' =>
                                                $addon
                                                    ->addon_name,

                                            'quantity' =>
                                                max(
                                                    1,
                                                    (int)
                                                        $addon
                                                            ->quantity
                                                ),

                                            'unit_price' =>
                                                (float)
                                                    $addon
                                                        ->unit_price,

                                            'total_price' =>
                                                (float)
                                                    $addon
                                                        ->total_price,
                                        ];
                                    }
                                )
                                ->values()
                                ->all(),
                    ];
                }
            )
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Resolve Primary and Merged Tables
        |--------------------------------------------------------------------------
        */

        $mergedTables = $order->tables
            ->filter(
                static fn ($table): bool =>
                    ! (bool) (
                        $table->pivot?->is_primary
                        ?? false
                    )
            )
            ->pluck('table_name')
            ->filter()
            ->values()
            ->all();

        /*
        |--------------------------------------------------------------------------
        | Resolve Customer Information
        |--------------------------------------------------------------------------
        |
        | প্রথমে order snapshot data ব্যবহার হবে। Snapshot না থাকলে
        | related customer record থেকে fallback নেওয়া হবে।
        |
        */

        $customer = [
            'name' =>
                $order->customer_name
                ?: $order->customer?->name
                ?: 'Walk-in Customer',

            'phone' =>
                $order->customer_phone
                ?: $order->customer?->phone,

            'email' =>
                $order->customer_email
                ?: $order->customer?->email,
        ];

        /*
        |--------------------------------------------------------------------------
        | Invoice Financial Summary
        |--------------------------------------------------------------------------
        */

        $financialSummary = [
            'subtotal' =>
                (float) $order->subtotal,

            'discount' =>
                (float) $order->discount_amount,

            'tax' =>
                (float) $order->tax_amount,

            'service_charge' =>
                (float) $order->service_charge,

            'total' =>
                (float) $order->total_amount,

            'paid' =>
                (float) $order->paid_amount,

            'due' =>
                (float) $order->due_amount,
        ];

        /*
        |--------------------------------------------------------------------------
        | Prepare Invoice View Data
        |--------------------------------------------------------------------------
        */

        $invoiceData = [
            'order' =>
                $order,

            'customer' =>
                $customer,

            'items' =>
                $invoiceItems,

            'primaryTable' =>
                $order->primaryTable,

            'mergedTables' =>
                $mergedTables,

            'financial' =>
                $financialSummary,

            'payments' =>
                $order->payments,

            'generatedBy' =>
                $request->user(),

            'generatedAt' =>
                now(),
        ];

        /*
        |--------------------------------------------------------------------------
        | Build Safe Invoice Filename
        |--------------------------------------------------------------------------
        */

        $safeOrderNumber = preg_replace(
            '/[^A-Za-z0-9\-_]/',
            '-',
            (string) $order->order_number
        );

        $fileName =
            'invoice-'
            . (
                $safeOrderNumber
                ?: $order->id
            )
            . '.pdf';

        /*
        |--------------------------------------------------------------------------
        | Generate and Download PDF
        |--------------------------------------------------------------------------
        |
        | A4 paper এবং portrait orientation ব্যবহার করা হচ্ছে।
        |
        */

        /*
        |--------------------------------------------------------------------------
        | Generate Invoice PDF
        |--------------------------------------------------------------------------
        |
        | Blade path:
        | resources/views/order-invoice.blade.php
        |
        */

        $pdf = Pdf::loadView(
            'order-invoice',
            $invoiceData
        )->setPaper(
            'a4',
            'portrait'
        );
        return $pdf->download(
            $fileName
        );
    }
}