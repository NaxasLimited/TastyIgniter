@php
    $location = $receipt->location_snapshot ?: [];
    $cashier = $receipt->cashier_snapshot ?: [];
    $customer = $receipt->customer_snapshot ?: [];
    $totals = $receipt->totals_snapshot ?: [];
    $order = $payment->order;
    $currency = $payment->currency_code ?: config('restaurant-ops.payment.currency', 'BDT');
    $issuedAt = $receipt->issued_at->timezone(config('app.timezone'))->format('d M Y h:i A');
    $money = fn ($value) => number_format((float) $value, 2);
@endphp

<style>
    @page { size: 80mm auto; margin: 4mm; }
    body { background: #f5f5f5; }
    .rops-actions { margin: 16px auto; text-align: center; }
    .rops-actions button { min-height: 42px; padding: 0 18px; border: 0; border-radius: 6px; background: #111827; color: #fff; font-weight: 700; }
    .rops-receipt { width: 72mm; margin: 0 auto 24px; padding: 10px 8px; background: #fff; color: #111; font: 12px/1.35 "Courier New", ui-monospace, monospace; }
    .rops-receipt h1 { margin: 0; text-align: center; font-size: 18px; line-height: 1.1; text-transform: uppercase; }
    .rops-receipt .center { text-align: center; }
    .rops-receipt .muted { color: #444; }
    .rops-receipt .title { margin-top: 6px; text-align: center; font-weight: 700; letter-spacing: .5px; }
    .rops-receipt .rule { border-top: 1px dashed #111; margin: 7px 0; }
    .rops-receipt .row { display: flex; justify-content: space-between; gap: 10px; }
    .rops-receipt table { width: 100%; border-collapse: collapse; }
    .rops-receipt th, .rops-receipt td { padding: 2px 0; vertical-align: top; }
    .rops-receipt th { border-bottom: 1px dashed #111; font-weight: 700; text-align: left; }
    .rops-receipt .num { text-align: right; white-space: nowrap; }
    .rops-receipt .item-note { display: block; color: #444; font-size: 11px; }
    .rops-receipt .grand th, .rops-receipt .grand td { padding-top: 5px; font-size: 14px; border-top: 1px dashed #111; }
    @media print {
        body { background: #fff; }
        .admin-page-header, .navbar, .sidebar, .rops-actions { display: none !important; }
        .rops-receipt { width: 72mm; margin: 0; padding: 0; }
    }
</style>

<div class="rops-actions">
    <button type="button" onclick="window.print()">Print receipt</button>
</div>

<article class="rops-receipt">
    <h1>{{ $receipt->footer_snapshot['restaurant_name'] ?? 'Ottoman Express' }}</h1>
    <div class="center muted">
        {{ $location['name'] ?? 'Branch' }}<br>
        @if(!empty($location['address'])){{ $location['address'] }}<br>@endif
        @if(!empty($location['telephone']))Tel: {{ $location['telephone'] }}@endif
    </div>

    <div class="rule"></div>
    <div class="title">SALES RECEIPT</div>
    <div class="rule"></div>

    <div class="row"><span>Receipt</span><strong>{{ $receipt->receipt_number }}</strong></div>
    <div class="row"><span>POS Order</span><strong>#{{ $payment->pos_order_id }}</strong></div>
    <div class="row"><span>Official Order</span><strong>#{{ $receipt->official_order_id }}</strong></div>
    <div class="row"><span>Date/Time</span><strong>{{ $issuedAt }}</strong></div>
    <div class="row"><span>Service</span><strong>{{ str($order->service_type ?? 'collection')->replace('_', ' ')->title() }}</strong></div>
    <div class="row"><span>Cashier</span><strong>{{ $cashier['name'] ?? 'Staff' }}</strong></div>
    @if(!empty($customer['name']) || !empty($customer['phone']))
        <div class="row"><span>Customer</span><strong>{{ trim(($customer['name'] ?? '').' '.($customer['phone'] ?? '')) }}</strong></div>
    @endif
    @if($receipt->print_count > 1)
        <div class="row"><span>Print</span><strong>REPRINT #{{ $receipt->print_count }}</strong></div>
    @endif

    <div class="rule"></div>
    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th class="num">Qty</th>
                <th class="num">Price</th>
                <th class="num">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($receipt->item_snapshot as $item)
                <tr>
                    <td>
                        {{ $item['name'] ?? 'Menu item' }}
                        @if(!empty($item['variant']))<span class="item-note">{{ $item['variant'] }}</span>@endif
                        @if(!empty($item['note']))<span class="item-note">Note: {{ $item['note'] }}</span>@endif
                    </td>
                    <td class="num">{{ $item['quantity'] ?? 1 }}</td>
                    <td class="num">{{ $money($item['unit_price'] ?? 0) }}</td>
                    <td class="num">{{ $money($item['line_total'] ?? 0) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="rule"></div>
    <table>
        <tr><td>Subtotal</td><td class="num">{{ $money($totals['subtotal'] ?? 0) }}</td></tr>
        @if((float)($totals['discount'] ?? 0) > 0)
            <tr><td>Discount</td><td class="num">-{{ $money($totals['discount']) }}</td></tr>
        @endif
        @if((float)($totals['tax'] ?? 0) > 0)
            <tr><td>Tax/VAT</td><td class="num">{{ $money($totals['tax']) }}</td></tr>
        @endif
        @if((float)($totals['delivery'] ?? 0) > 0)
            <tr><td>Service/Delivery</td><td class="num">{{ $money($totals['delivery']) }}</td></tr>
        @endif
        <tr class="grand"><th>Grand Total</th><td class="num"><strong>{{ $money($totals['grand_total'] ?? 0) }} {{ $currency }}</strong></td></tr>
    </table>

    <div class="rule"></div>
    <table>
        @foreach($receipt->tender_snapshot as $tender)
            <tr>
                <td>
                    {{ strtoupper($tender['method'] ?? 'payment') }}
                    @if(!empty($tender['provider_code'])) ({{ $tender['provider_code'] }}) @endif
                    @if(!empty($tender['reference']))<span class="item-note">Ref: {{ $tender['reference'] }}</span>@endif
                </td>
                <td class="num">{{ $money($tender['amount_applied'] ?? 0) }}</td>
            </tr>
            @if(($tender['method'] ?? '') === 'cash')
                <tr><td>Cash Received</td><td class="num">{{ $money($tender['amount_received'] ?? 0) }}</td></tr>
                <tr><td>Change</td><td class="num">{{ $money($tender['change_amount'] ?? 0) }}</td></tr>
            @endif
        @endforeach
    </table>

    <div class="rule"></div>
    <div class="center">
        {{ $receipt->footer_snapshot['message'] ?? 'Thank you.' }}<br>
        <span class="muted">Powered by Restaurant POS</span>
    </div>
</article>
