<style>
    .rops-orders-board{display:grid;gap:12px}
    .rops-orders-card{background:#fff;border:1px solid #d8dee8;border-radius:8px;padding:14px 16px;box-shadow:0 6px 18px rgba(18,38,63,.06)}
    .rops-orders-row{display:grid;grid-template-columns:1.1fr .85fr .9fr .85fr auto;gap:14px;align-items:center}
    .rops-orders-title{font-size:16px;font-weight:800;color:#0d1b35;margin:0}
    .rops-orders-meta{font-size:12px;color:#5b667a;margin-top:4px}
    .rops-orders-pill{display:inline-flex;align-items:center;border-radius:999px;padding:4px 9px;font-size:12px;font-weight:700;background:#edf2f7;color:#31425f}
    .rops-orders-pill.is-paid{background:#dcfce7;color:#166534}
    .rops-orders-pill.is-waiting{background:#fef3c7;color:#92400e}
    .rops-orders-actions{display:flex;gap:8px;justify-content:flex-end;flex-wrap:wrap}
    .rops-orders-actions .btn{min-width:96px}
    @media (max-width: 992px){.rops-orders-row{grid-template-columns:1fr}.rops-orders-actions{justify-content:flex-start}}
</style>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-1">{{ $title }}</h1>
            <div class="text-muted">Integrated POS and official order view for the selected branch.</div>
        </div>
        <a class="btn btn-primary" href="{{ route('naxas.restaurantops.pos') }}">Open POS</a>
    </div>

    <div class="rops-orders-board">
        @forelse($orders as $order)
            @php
                $service = str($order->service_type)->replace('_', ' ')->title();
                $posStatus = str($order->status)->replace('_', ' ')->title();
                $table = trim(($order->floor_name ? $order->floor_name.' - ' : '').($order->table_number ?: $order->table_name));
                $paymentStatus = $order->payment_status ?: ($order->official_processed ? 'paid' : 'unpaid');
                $paymentClass = $paymentStatus === 'paid' ? 'is-paid' : 'is-waiting';
                $officialUrl = $order->official_order_id ? admin_url('orders/edit/'.$order->official_order_id) : null;
            @endphp
            <article class="rops-orders-card">
                <div class="rops-orders-row">
                    <div>
                        <h2 class="rops-orders-title">
                            POS #{{ $order->getKey() }}
                            @if($order->official_order_id)
                                <span class="text-muted">/ Official #{{ $order->official_order_id }}</span>
                            @endif
                        </h2>
                        <div class="rops-orders-meta">
                            {{ $service }} · {{ $order->items->whereNotIn('status', ['removed', 'voided'])->sum('quantity') }} item(s) · {{ optional($order->created_at)->format('M j, Y g:i A') }}
                        </div>
                    </div>
                    <div>
                        <span class="rops-orders-pill">{{ $posStatus }}</span>
                        @if($order->official_status_name)
                            <span class="rops-orders-pill mt-1">{{ $order->official_status_name }}</span>
                        @endif
                    </div>
                    <div>
                        <strong>{{ $table ?: 'No table' }}</strong>
                        <div class="rops-orders-meta">
                            {{ $order->waiter_name ? 'Waiter: '.$order->waiter_name : 'No waiter' }}
                            @if($order->guest_count || $order->session_guest_count)
                                · {{ $order->guest_count ?: $order->session_guest_count }} guest(s)
                            @endif
                        </div>
                    </div>
                    <div>
                        <strong>{{ currency_format($order->order_total) }}</strong>
                        <div><span class="rops-orders-pill {{ $paymentClass }}">{{ str($paymentStatus)->replace('_', ' ')->title() }}</span></div>
                        @if($order->receipt_number)
                            <div class="rops-orders-meta">Receipt: {{ $order->receipt_number }}</div>
                        @endif
                    </div>
                    <div class="rops-orders-actions">
                        @if($order->status === 'payment_pending')
                            <a class="btn btn-success" href="{{ route('naxas.restaurantops.pos.payments.page', $order) }}">Take payment</a>
                        @endif
                        @if($officialUrl)
                            <a class="btn btn-light" href="{{ $officialUrl }}">Official order</a>
                        @else
                            <a class="btn btn-light" href="{{ route('naxas.restaurantops.pos') }}">Continue POS</a>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <div class="rops-orders-card text-muted">No integrated orders are waiting for this branch.</div>
        @endforelse
    </div>

    <div class="mt-3">{{ $orders->links() }}</div>
</div>
