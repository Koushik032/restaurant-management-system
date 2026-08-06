<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Invoice {{ $order->order_number }}
    </title>

    <style>
        /*
        |--------------------------------------------------------------------------
        | Page Configuration
        |--------------------------------------------------------------------------
        */

        @page {
            margin: 24px 28px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;

            color: #1f2937;
            background: #ffffff;

            font-family:
                DejaVu Sans,
                sans-serif;

            font-size: 11px;
            line-height: 1.5;
        }

        .invoice-page {
            width: 100%;
        }

        /*
        |--------------------------------------------------------------------------
        | Common Helpers
        |--------------------------------------------------------------------------
        */

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .text-muted {
            color: #64748b;
        }

        .font-bold {
            font-weight: 700;
        }

        .no-margin {
            margin: 0;
        }

        /*
        |--------------------------------------------------------------------------
        | Invoice Header
        |--------------------------------------------------------------------------
        */

        .invoice-header {
            width: 100%;

            margin-bottom: 20px;
            padding-bottom: 16px;

            border-bottom: 2px solid #ea580c;
        }

        .invoice-header-table {
            width: 100%;

            border-collapse: collapse;
        }

        .invoice-header-table td {
            padding: 0;
            vertical-align: top;
        }

        .brand-column {
            width: 62%;
        }

        .invoice-title-column {
            width: 38%;

            text-align: right;
        }

        .brand-name {
            margin: 0;

            color: #0f172a;

            font-size: 22px;
            font-weight: 700;
            line-height: 1.25;
        }

        .brand-subtitle {
            margin: 4px 0 0;

            color: #ea580c;

            font-size: 10px;
            font-weight: 700;

            text-transform: uppercase;
            letter-spacing: 0.7px;
        }

        .brand-information {
            margin-top: 8px;

            color: #475569;

            font-size: 10px;
            line-height: 1.6;
        }

        .brand-information p {
            margin: 0;
        }

        .invoice-title {
            margin: 0;

            color: #ea580c;

            font-size: 28px;
            font-weight: 700;
            line-height: 1;

            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .invoice-number {
            margin-top: 8px;

            color: #0f172a;

            font-size: 12px;
            font-weight: 700;
        }

        .invoice-generated-date {
            margin-top: 5px;

            color: #64748b;

            font-size: 9px;
        }

        /*
        |--------------------------------------------------------------------------
        | Information Section
        |--------------------------------------------------------------------------
        */

        .information-table {
            width: 100%;

            margin-bottom: 20px;

            border-collapse: separate;
            border-spacing: 10px 0;
        }

        .information-table td {
            width: 50%;

            padding: 0;

            vertical-align: top;
        }

        .information-table td:first-child {
            padding-left: 0;
        }

        .information-table td:last-child {
            padding-right: 0;
        }

        .information-card {
            min-height: 148px;

            padding: 14px;

            border: 1px solid #e2e8f0;
            border-radius: 8px;

            background: #f8fafc;
        }

        .information-card-title {
            margin: 0 0 11px;
            padding-bottom: 7px;

            border-bottom: 1px solid #e2e8f0;

            color: #0f172a;

            font-size: 11px;
            font-weight: 700;

            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .information-row {
            width: 100%;

            margin-bottom: 7px;

            border-collapse: collapse;
        }

        .information-row:last-child {
            margin-bottom: 0;
        }

        .information-row td {
            padding: 0;

            border: 0;

            vertical-align: top;
        }

        .information-label {
            width: 38%;

            color: #64748b;

            font-size: 9px;
        }

        .information-value {
            width: 62%;

            color: #0f172a;

            font-size: 10px;
            font-weight: 700;

            text-align: right;
            overflow-wrap: anywhere;
        }

        /*
        |--------------------------------------------------------------------------
        | Section Heading
        |--------------------------------------------------------------------------
        */

        .section-heading {
            width: 100%;

            margin: 0 0 9px;
            padding-bottom: 7px;

            border-bottom: 1px solid #cbd5e1;

            color: #0f172a;

            font-size: 12px;
            font-weight: 700;

            text-transform: uppercase;
            letter-spacing: 0.45px;
        }
    </style>
</head>

<body>
    <main class="invoice-page">
        <!-- ==============================================================
             Invoice Header
        =============================================================== -->

        <header class="invoice-header">
            <table class="invoice-header-table">
                <tr>
                    <!-- ==================================================
                         Restaurant Information
                    =================================================== -->

                    <td class="brand-column">
                        <h1 class="brand-name">
                            {{ config('app.name', 'Restaurant Management System') }}
                        </h1>

                        <p class="brand-subtitle">
                            Customer Invoice
                        </p>

                        <div class="brand-information">
                            <p>
                                Restaurant address will appear here
                            </p>

                            <p>
                                Phone: 01XXXXXXXXX
                            </p>

                            <p>
                                Email: restaurant@example.com
                            </p>
                        </div>
                    </td>

                    <!-- ==================================================
                         Invoice Identity
                    =================================================== -->

                    <td class="invoice-title-column">
                        <h2 class="invoice-title">
                            Invoice
                        </h2>

                        <div class="invoice-number">
                            {{ $order->order_number }}
                        </div>

                        <div class="invoice-generated-date">
                            Generated:
                            {{ $generatedAt->format('d M Y, h:i A') }}
                        </div>
                    </td>
                </tr>
            </table>
        </header>

        <!-- ==============================================================
             Customer and Order Information
        =============================================================== -->

        <table class="information-table">
            <tr>
                <!-- ======================================================
                     Customer Information
                ======================================================= -->

                <td>
                    <section class="information-card">
                        <h3 class="information-card-title">
                            Customer Information
                        </h3>

                        <table class="information-row">
                            <tr>
                                <td class="information-label">
                                    Customer Name
                                </td>

                                <td class="information-value">
                                    {{ $customer['name'] ?? 'Walk-in Customer' }}
                                </td>
                            </tr>
                        </table>

                        <table class="information-row">
                            <tr>
                                <td class="information-label">
                                    Phone Number
                                </td>

                                <td class="information-value">
                                    {{ $customer['phone'] ?? 'Not provided' }}
                                </td>
                            </tr>
                        </table>

                        <table class="information-row">
                            <tr>
                                <td class="information-label">
                                    Email Address
                                </td>

                                <td class="information-value">
                                    {{ $customer['email'] ?? 'Not provided' }}
                                </td>
                            </tr>
                        </table>
                    </section>
                </td>

                <!-- ======================================================
                     Order Information
                ======================================================= -->

                <td>
                    <section class="information-card">
                        <h3 class="information-card-title">
                            Order Information
                        </h3>

                        <table class="information-row">
                            <tr>
                                <td class="information-label">
                                    Order ID
                                </td>

                                <td class="information-value">
                                    {{ $order->order_number }}
                                </td>
                            </tr>
                        </table>

                        <table class="information-row">
                            <tr>
                                <td class="information-label">
                                    Order Date
                                </td>

                                <td class="information-value">
                                    {{ $order->created_at?->format('d M Y') ?? '—' }}
                                </td>
                            </tr>
                        </table>

                        <table class="information-row">
                            <tr>
                                <td class="information-label">
                                    Order Time
                                </td>

                                <td class="information-value">
                                    {{ $order->created_at?->format('h:i A') ?? '—' }}
                                </td>
                            </tr>
                        </table>

                        <table class="information-row">
                            <tr>
                                <td class="information-label">
                                    Primary Table
                                </td>

                                <td class="information-value">
                                    {{
                                        $primaryTable?->table_name
                                        ?? 'No Table'
                                    }}
                                </td>
                            </tr>
                        </table>

                        @if (!empty($mergedTables))
                            <table class="information-row">
                                <tr>
                                    <td class="information-label">
                                        Merged Tables
                                    </td>

                                    <td class="information-value">
                                        {{ implode(', ', $mergedTables) }}
                                    </td>
                                </tr>
                            </table>
                        @endif

                        <table class="information-row">
                            <tr>
                                <td class="information-label">
                                    Order Status
                                </td>

                                <td class="information-value">
                                    {{
                                        ucwords(
                                            str_replace(
                                                '_',
                                                ' ',
                                                $order->status
                                            )
                                        )
                                    }}
                                </td>
                            </tr>
                        </table>
                    </section>
                </td>
            </tr>
        </table>

        <!-- ==============================================================
             Ordered Items Section
        =============================================================== -->

        <h3 class="section-heading">
            Ordered Menu Items
        </h3>
        <!-- ==============================================================
     Ordered Items Table
=============================================================== -->

<table
    style="
        width:100%;
        border-collapse:collapse;
        margin-bottom:18px;
    "
>
    <thead>
        <tr
            style="
                background:#ea580c;
                color:#ffffff;
            "
        >
            <th
                style="
                    padding:10px;
                    width:5%;
                    border:1px solid #d6d6d6;
                    text-align:center;
                "
            >
                #
            </th>

            <th
                style="
                    padding:10px;
                    width:39%;
                    border:1px solid #d6d6d6;
                    text-align:left;
                "
            >
                Menu Item
            </th>

            <th
                style="
                    padding:10px;
                    width:10%;
                    border:1px solid #d6d6d6;
                    text-align:center;
                "
            >
                Qty
            </th>

            <th
                style="
                    padding:10px;
                    width:16%;
                    border:1px solid #d6d6d6;
                    text-align:right;
                "
            >
                Unit Price
            </th>

            <th
                style="
                    padding:10px;
                    width:14%;
                    border:1px solid #d6d6d6;
                    text-align:right;
                "
            >
                Add-on
            </th>

            <th
                style="
                    padding:10px;
                    width:16%;
                    border:1px solid #d6d6d6;
                    text-align:right;
                "
            >
                Total
            </th>
        </tr>
    </thead>

    <tbody>

@foreach($items as $item)

<tr>

    <td
        style="
            padding:10px;
            border:1px solid #e5e7eb;
            text-align:center;
            vertical-align:top;
        "
    >
        {{ $loop->iteration }}
    </td>

    <td
        style="
            padding:10px;
            border:1px solid #e5e7eb;
            vertical-align:top;
        "
    >

        <div
            style="
                font-size:11px;
                font-weight:700;
                color:#111827;
            "
        >
            {{ $item['name'] }}
        </div>

        @if($item['variant'])

        <div
            style="
                margin-top:4px;
                color:#2563eb;
                font-size:9px;
            "
        >
            Variant :
            {{ $item['variant'] }}
        </div>

        @endif

        @if(!empty($item['addons']))

        <div
            style="
                margin-top:6px;
                font-size:9px;
                color:#475569;
            "
        >
            <strong>
                Add-ons
            </strong>

            <ul
                style="
                    margin:4px 0 0 15px;
                    padding:0;
                "
            >

            @foreach($item['addons'] as $addon)

                <li
                    style="
                        margin-bottom:3px;
                    "
                >
                    {{ $addon['name'] }}

                    ×

                    {{ $addon['quantity'] }}

                    (

                    ৳{{ number_format($addon['total_price'],2) }}

                    )
                </li>

            @endforeach

            </ul>

        </div>

        @endif

        @if($item['kitchen_note'])

        <div
            style="
                margin-top:7px;
                color:#dc2626;
                font-size:9px;
            "
        >
            <strong>
                Kitchen Note :
            </strong>

            {{ $item['kitchen_note'] }}
        </div>

        @endif

    </td>

    <td
        style="
            padding:10px;
            border:1px solid #e5e7eb;
            text-align:center;
            vertical-align:top;
        "
    >
        {{ $item['quantity'] }}
    </td>

    <td
        style="
            padding:10px;
            border:1px solid #e5e7eb;
            text-align:right;
            vertical-align:top;
        "
    >
        ৳{{ number_format($item['unit_price'],2) }}
    </td>

    <td
        style="
            padding:10px;
            border:1px solid #e5e7eb;
            text-align:right;
            vertical-align:top;
        "
    >
        ৳{{ number_format($item['addon_total'],2) }}
    </td>

    <td
        style="
            padding:10px;
            border:1px solid #e5e7eb;
            text-align:right;
            vertical-align:top;
            font-weight:700;
        "
    >
        ৳{{ number_format($item['line_total'],2) }}
    </td>

</tr>

@endforeach

    </tbody>
</table>

<!-- ==============================================================
     Financial Summary
=============================================================== -->
<!-- ==============================================================
     Financial and Payment Summary
=============================================================== -->

<table
    style="
        width: 100%;
        margin-bottom: 18px;
        border-collapse: collapse;
    "
>
    <tr>
        <!-- ======================================================
             Payment Information
        ======================================================= -->

        <td
            style="
                width: 52%;
                padding-right: 10px;
                vertical-align: top;
            "
        >
            <div
                style="
                    min-height: 205px;
                    padding: 14px;
                    border: 1px solid #e2e8f0;
                    border-radius: 8px;
                    background: #f8fafc;
                "
            >
                <h3
                    style="
                        margin: 0 0 12px;
                        padding-bottom: 7px;
                        border-bottom: 1px solid #e2e8f0;
                        color: #0f172a;
                        font-size: 11px;
                        font-weight: 700;
                        text-transform: uppercase;
                        letter-spacing: 0.45px;
                    "
                >
                    Payment Information
                </h3>

                <table
                    style="
                        width: 100%;
                        border-collapse: collapse;
                    "
                >
                    <tr>
                    
                    </tr>

                    <tr>
                        
                    </tr>

                    <tr>
                        
                    </tr>

                    <tr>
                        <td
                            style="
                                padding: 5px 0;
                                color: #64748b;
                                font-size: 9px;
                            "
                        >
                            Generated By
                        </td>

                        <td
                            style="
                                padding: 5px 0;
                                color: #0f172a;
                                font-size: 10px;
                                font-weight: 700;
                                text-align: right;
                            "
                        >
                            {{
                                $generatedBy?->username
                                ?: $generatedBy?->name
                                ?: 'System'
                            }}
                        </td>
                    </tr>
                </table>

                @if(isset($payments) && $payments->isNotEmpty()))
                    <div
                        style="
                            margin-top: 12px;
                            padding-top: 10px;
                            border-top: 1px solid #e2e8f0;
                        "
                    >
                        <div
                            style="
                                margin-bottom: 7px;
                                color: #0f172a;
                                font-size: 9px;
                                font-weight: 700;
                                text-transform: uppercase;
                                letter-spacing: 0.4px;
                            "
                        >
                            Payment History
                        </div>

                        @foreach(($payments ?? collect()) as $payment)
                            <table
                                style="
                                    width: 100%;
                                    margin-bottom: 6px;
                                    border-collapse: collapse;
                                "
                            >
                                <tr>
                                    <td
                                        style="
                                            color: #475569;
                                            font-size: 9px;
                                        "
                                    >
                                        {{
                                            ucwords(
                                                str_replace(
                                                    '_',
                                                    ' ',
                                                    $payment->payment_method
                                                )
                                            )
                                        }}

                                        @if($payment->reference)
                                            <span style="color: #94a3b8;">
                                                · {{ $payment->reference }}
                                            </span>
                                        @endif
                                    </td>

                                    <td
                                        style="
                                            color: #0f172a;
                                            font-size: 9px;
                                            font-weight: 700;
                                            text-align: right;
                                        "
                                    >
                                        ৳{{ number_format((float) $payment->amount, 2) }}
                                    </td>
                                </tr>

                                <tr>
                                    <td
                                        colspan="2"
                                        style="
                                            padding-top: 2px;
                                            color: #94a3b8;
                                            font-size: 8px;
                                        "
                                    >
                                        {{
                                            $payment->created_at?->format(
                                                'd M Y, h:i A'
                                            )
                                            ?? '—'
                                        }}

                                        @if($payment->receiver)
                                            · Received by
                                            {{
                                                $payment->receiver->username
                                                ?: $payment->receiver->name
                                            }}
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        @endforeach
                    </div>
                @else
                    <div
                        style="
                            margin-top: 12px;
                            padding: 9px;
                            border: 1px dashed #cbd5e1;
                            border-radius: 6px;
                            color: #64748b;
                            font-size: 9px;
                            text-align: center;
                        "
                    >
                        No payment history available.
                    </div>
                @endif
            </div>
        </td>

        <!-- ======================================================
             Financial Summary
        ======================================================= -->

        <td
            style="
                width: 48%;
                padding-left: 10px;
                vertical-align: top;
            "
        >
            <div
                style="
                    overflow: hidden;
                    border: 1px solid #e2e8f0;
                    border-radius: 8px;
                    background: #ffffff;
                "
            >
                <div
                    style="
                        padding: 11px 14px;
                        background: #0f172a;
                        color: #ffffff;
                        font-size: 11px;
                        font-weight: 700;
                        text-transform: uppercase;
                        letter-spacing: 0.45px;
                    "
                >
                    Financial Summary
                </div>

                <table
                    style="
                        width: 100%;
                        border-collapse: collapse;
                    "
                >
                    <tr>
                        <td
                            style="
                                padding: 9px 14px;
                                border-bottom: 1px solid #f1f5f9;
                                color: #64748b;
                                font-size: 10px;
                            "
                        >
                            Subtotal
                        </td>

                        <td
                            style="
                                padding: 9px 14px;
                                border-bottom: 1px solid #f1f5f9;
                                color: #0f172a;
                                font-size: 10px;
                                font-weight: 700;
                                text-align: right;
                            "
                        >
                            ৳{{ number_format($financial['subtotal'], 2) }}
                        </td>
                    </tr>

                    <tr>
                        <td
                            style="
                                padding: 9px 14px;
                                border-bottom: 1px solid #f1f5f9;
                                color: #64748b;
                                font-size: 10px;
                            "
                        >
                            Discount
                        </td>

                        <td
                            style="
                                padding: 9px 14px;
                                border-bottom: 1px solid #f1f5f9;
                                color: #dc2626;
                                font-size: 10px;
                                font-weight: 700;
                                text-align: right;
                            "
                        >
                            − ৳{{ number_format($financial['discount'], 2) }}
                        </td>
                    </tr>

                    <tr>
                        <td
                            style="
                                padding: 9px 14px;
                                border-bottom: 1px solid #f1f5f9;
                                color: #64748b;
                                font-size: 10px;
                            "
                        >
                            Tax
                        </td>

                        <td
                            style="
                                padding: 9px 14px;
                                border-bottom: 1px solid #f1f5f9;
                                color: #0f172a;
                                font-size: 10px;
                                font-weight: 700;
                                text-align: right;
                            "
                        >
                            ৳{{ number_format($financial['tax'], 2) }}
                        </td>
                    </tr>

                    <tr>
                        <td
                            style="
                                padding: 9px 14px;
                                border-bottom: 1px solid #f1f5f9;
                                color: #64748b;
                                font-size: 10px;
                            "
                        >
                            Service Charge
                        </td>

                        <td
                            style="
                                padding: 9px 14px;
                                border-bottom: 1px solid #f1f5f9;
                                color: #0f172a;
                                font-size: 10px;
                                font-weight: 700;
                                text-align: right;
                            "
                        >
                            ৳{{ number_format($financial['service_charge'], 2) }}
                        </td>
                    </tr>

                    <tr>
                        <td
                            style="
                                padding: 12px 14px;
                                background: #fff7ed;
                                color: #9a3412;
                                font-size: 11px;
                                font-weight: 700;
                            "
                        >
                            Grand Total
                        </td>

                        <td
                            style="
                                padding: 12px 14px;
                                background: #fff7ed;
                                color: #c2410c;
                                font-size: 15px;
                                font-weight: 700;
                                text-align: right;
                            "
                        >
                            ৳{{ number_format($financial['total'], 2) }}
                        </td>
                    </tr>

                    <tr>
                        <td
                            style="
                                padding: 9px 14px;
                                border-top: 1px solid #fed7aa;
                                color: #15803d;
                                font-size: 10px;
                                font-weight: 700;
                            "
                        >
                            Paid Amount
                        </td>

                        <td
                            style="
                                padding: 9px 14px;
                                border-top: 1px solid #fed7aa;
                                color: #15803d;
                                font-size: 10px;
                                font-weight: 700;
                                text-align: right;
                            "
                        >
                            ৳{{ number_format($financial['paid'], 2) }}
                        </td>
                    </tr>

                    <tr>
                        <td
                            style="
                                padding: 9px 14px;
                                color: #b91c1c;
                                font-size: 10px;
                                font-weight: 700;
                            "
                        >
                            Due Amount
                        </td>

                        <td
                            style="
                                padding: 9px 14px;
                                color: #b91c1c;
                                font-size: 10px;
                                font-weight: 700;
                                text-align: right;
                            "
                        >
                            ৳{{ number_format($financial['due'], 2) }}
                        </td>
                    </tr>
                </table>
            </div>
        </td>
    </tr>
</table>

<!-- ==============================================================
     Notes and Footer Section
=============================================================== -->
<!-- ==============================================================
     Notes Section
=============================================================== -->

@if(
    $order->order_note ||
    $order->kitchen_note
)
    <table
        style="
            width: 100%;
            margin-bottom: 18px;
            border-collapse: collapse;
        "
    >
        <tr>
            @if($order->order_note)
                <td
                    style="
                        width: 50%;
                        padding-right: 8px;
                        vertical-align: top;
                    "
                >
                    <div
                        style="
                            min-height: 85px;
                            padding: 12px;
                            border: 1px solid #e2e8f0;
                            border-radius: 8px;
                            background: #f8fafc;
                        "
                    >
                        <div
                            style="
                                margin-bottom: 6px;
                                color: #0f172a;
                                font-size: 9px;
                                font-weight: 700;
                                text-transform: uppercase;
                                letter-spacing: 0.4px;
                            "
                        >
                            Order Note
                        </div>

                        <div
                            style="
                                color: #475569;
                                font-size: 9px;
                                line-height: 1.6;
                            "
                        >
                            {{ $order->order_note }}
                        </div>
                    </div>
                </td>
            @endif

            @if($order->kitchen_note)
                <td
                    style="
                        width: 50%;
                        padding-left: 8px;
                        vertical-align: top;
                    "
                >
                    <div
                        style="
                            min-height: 85px;
                            padding: 12px;
                            border: 1px solid #fed7aa;
                            border-radius: 8px;
                            background: #fff7ed;
                        "
                    >
                        <div
                            style="
                                margin-bottom: 6px;
                                color: #9a3412;
                                font-size: 9px;
                                font-weight: 700;
                                text-transform: uppercase;
                                letter-spacing: 0.4px;
                            "
                        >
                            Kitchen Note
                        </div>

                        <div
                            style="
                                color: #9a3412;
                                font-size: 9px;
                                line-height: 1.6;
                            "
                        >
                            {{ $order->kitchen_note }}
                        </div>
                    </div>
                </td>
            @endif
        </tr>
    </table>
@endif

<!-- ==============================================================
     Signature Section
=============================================================== -->

<table
    style="
        width: 100%;
        margin-top: 24px;
        border-collapse: collapse;
    "
>
    <tr>
        <td
            style="
                width: 33.33%;
                padding-right: 18px;
                vertical-align: bottom;
            "
        >
            <div
                style="
                    height: 36px;
                    border-bottom: 1px solid #94a3b8;
                "
            ></div>

            <div
                style="
                    margin-top: 6px;
                    color: #64748b;
                    font-size: 9px;
                    text-align: center;
                "
            >
                Customer Signature
            </div>
        </td>

        <td
            style="
                width: 33.33%;
                padding: 0 9px;
                vertical-align: bottom;
            "
        >
            <div
                style="
                    height: 36px;
                    border-bottom: 1px solid #94a3b8;
                "
            ></div>

            <div
                style="
                    margin-top: 6px;
                    color: #64748b;
                    font-size: 9px;
                    text-align: center;
                "
            >
                Cashier Signature
            </div>
        </td>

        <td
            style="
                width: 33.33%;
                padding-left: 18px;
                vertical-align: bottom;
            "
        >
            <div
                style="
                    height: 36px;
                    border-bottom: 1px solid #94a3b8;
                "
            ></div>

            <div
                style="
                    margin-top: 6px;
                    color: #64748b;
                    font-size: 9px;
                    text-align: center;
                "
            >
                Authorized Signature
            </div>
        </td>
    </tr>
</table>

<!-- ==============================================================
     Thank You Message
=============================================================== -->

<div
    style="
        margin-top: 24px;
        padding: 14px;
        border: 1px solid #fed7aa;
        border-radius: 8px;
        background: #fff7ed;
        text-align: center;
    "
>
    <div
        style="
            color: #9a3412;
            font-size: 13px;
            font-weight: 700;
        "
    >
        Thank you for your order!
    </div>

    <div
        style="
            margin-top: 4px;
            color: #64748b;
            font-size: 9px;
            line-height: 1.6;
        "
    >
        Please keep this invoice for your records.
    </div>
</div>

<!-- ==============================================================
     Footer
=============================================================== -->

<footer
    style="
        margin-top: 18px;
        padding-top: 10px;
        border-top: 1px solid #e2e8f0;
        color: #94a3b8;
        font-size: 8px;
        text-align: center;
        line-height: 1.6;
    "
>
    <div>
        Invoice:
        {{ $order->order_number }}
    </div>

    <div>
        Generated on
        {{ $generatedAt->format('d M Y, h:i A') }}
    </div>

    <div>
        {{ config('app.name', 'Restaurant Management System') }}
    </div>
</footer>

    </main>
</body>
</html>