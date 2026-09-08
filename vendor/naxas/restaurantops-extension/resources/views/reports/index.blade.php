@include('Naxas.RestaurantOps::_partials.ops-style')
@php
    $money = fn ($value) => currency_format((float)$value);
    $date = fn ($value) => $value ? make_carbon($value)->format('M d, Y g:i A') : '-';
@endphp

<div class="rops-shell">
    <div class="rops-toolbar mb-3">
        <div class="rops-title">
            <h1>Restaurant reports</h1>
            <p>{{ $isGlobal ? 'All branches' : ($activeLocation?->location_name ?? 'Selected branch') }} · {{ $from->format('M d, Y') }} to {{ $to->format('M d, Y') }}</p>
        </div>
        <form method="get" class="d-flex gap-2 align-items-end">
            <div>
                <label class="form-label mb-1">From</label>
                <input type="date" name="from" value="{{ $from->toDateString() }}" class="form-control">
            </div>
            <div>
                <label class="form-label mb-1">To</label>
                <input type="date" name="to" value="{{ $to->toDateString() }}" class="form-control">
            </div>
            <button class="btn btn-primary" type="submit">Apply</button>
        </form>
    </div>

    <div class="rops-grid rops-grid-4 mb-3">
        <div class="rops-stat"><span>Net sales</span><strong>{{ $money($totals['sales']) }}</strong></div>
        <div class="rops-stat"><span>Completed orders</span><strong>{{ number_format($totals['orders']) }}</strong></div>
        <div class="rops-stat"><span>Average ticket</span><strong>{{ $money($totals['average_ticket']) }}</strong></div>
        <div class="rops-stat"><span>Payments received</span><strong>{{ $money($totals['payments']) }}</strong></div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="rops-card">
                <div class="rops-card-header"><strong>Branch Sales</strong><span class="rops-muted">{{ $branchSales->count() }} branches</span></div>
                <div class="table-responsive">
                    <table class="rops-table">
                        <thead><tr><th>Branch</th><th class="text-end">Orders</th><th class="text-end">Sales</th><th class="text-end">Avg</th></tr></thead>
                        <tbody>
                        @forelse($branchSales as $row)
                            <tr><td>{{ $row->branch_name }}</td><td class="text-end">{{ number_format((int)$row->order_count) }}</td><td class="text-end">{{ $money($row->sales_total) }}</td><td class="text-end">{{ $money($row->average_ticket) }}</td></tr>
                        @empty
                            <tr><td colspan="4" class="text-center rops-muted py-4">No completed sales in this period.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="rops-card">
                <div class="rops-card-header"><strong>Tender Reporting</strong><span class="rops-muted">Cash, bKash, Nagad, Card</span></div>
                <div class="table-responsive">
                    <table class="rops-table">
                        <thead><tr><th>Tender</th><th class="text-end">Count</th><th class="text-end">Orders</th><th class="text-end">Amount</th></tr></thead>
                        <tbody>
                        @forelse($paymentSummary as $row)
                            <tr>
                                <td><strong>{{ $row->method_label }}</strong></td>
                                <td class="text-end">{{ number_format((int)$row->tender_count) }}</td>
                                <td class="text-end">{{ number_format((int)$row->payment_count) }}</td>
                                <td class="text-end">{{ $money($row->amount_total) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center rops-muted py-4">No paid tenders in this period.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="rops-card">
                <div class="rops-card-header"><strong>Shift Summary</strong><span class="rops-muted">Latest 25 shifts</span></div>
                <div class="table-responsive">
                    <table class="rops-table">
                        <thead><tr><th>Shift</th><th>Staff</th><th>Status</th><th class="text-end">Paid sales</th><th>Opened</th></tr></thead>
                        <tbody>
                        @forelse($shiftSummary as $row)
                            <tr>
                                <td>#{{ $row->id }}<br><span class="rops-muted">{{ $row->branch_name }}</span></td>
                                <td>{{ $row->staff_name }}</td>
                                <td><span class="rops-pill {{ $row->status === 'open' ? 'is-open' : '' }}">{{ str($row->status)->replace('_', ' ')->title() }}</span></td>
                                <td class="text-end">{{ $money($row->paid_total) }}<br><span class="rops-muted">{{ number_format((int)$row->payment_count) }} payments</span></td>
                                <td>{{ $date($row->opened_at) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center rops-muted py-4">No shifts opened in this period.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="rops-card">
                <div class="rops-card-header"><strong>Dine-in / Pickup / Delivery Summary</strong><span class="rops-muted">Official orders</span></div>
                <div class="table-responsive">
                    <table class="rops-table">
                        <thead><tr><th>Service</th><th class="text-end">Orders</th><th class="text-end">Sales</th><th class="text-end">Avg</th></tr></thead>
                        <tbody>
                        @foreach($serviceSummary as $row)
                            <tr><td>{{ $row['service'] }}</td><td class="text-end">{{ number_format($row['order_count']) }}</td><td class="text-end">{{ $money($row['sales_total']) }}</td><td class="text-end">{{ $money($row['average_ticket']) }}</td></tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
