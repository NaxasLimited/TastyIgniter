@php
    $posOrder = \Illuminate\Support\Facades\DB::table('naxas_restaurant_ops_pos_orders as pos')
        ->leftJoin('admin_users as waiter', 'waiter.user_id', '=', 'pos.waiter_id')
        ->leftJoin('admin_users as cashier', 'cashier.user_id', '=', 'pos.cashier_id')
        ->where('pos.order_id', $formModel->order_id)
        ->selectRaw('pos.*, COALESCE(NULLIF(waiter.name, ""), waiter.username) as waiter_name, COALESCE(NULLIF(cashier.name, ""), cashier.username) as cashier_name')
        ->latest('pos.id')
        ->first();

    $payment = null;
    $receipt = null;
    $tenders = collect();
    $tableSession = null;
    $shift = null;

    if ($posOrder) {
        $payment = \Illuminate\Support\Facades\DB::table('naxas_restaurant_ops_pos_payments as payment')
            ->leftJoin('admin_users as cashier', 'cashier.user_id', '=', 'payment.cashier_staff_id')
            ->where('payment.pos_order_id', $posOrder->id)
            ->where('payment.status', 'paid')
            ->selectRaw('payment.*, COALESCE(NULLIF(cashier.name, ""), cashier.username, CONCAT("Staff #", payment.cashier_staff_id)) as cashier_staff_name')
            ->latest('payment.id')
            ->first();

        if ($payment) {
            $receipt = \Illuminate\Support\Facades\DB::table('naxas_restaurant_ops_pos_receipts')
                ->where('pos_payment_id', $payment->id)
                ->first();

            $tenders = \Illuminate\Support\Facades\DB::table('naxas_restaurant_ops_pos_payment_tenders')
                ->where('pos_payment_id', $payment->id)
                ->where('status', 'applied')
                ->orderBy('id')
                ->get();
        }

        $tableSession = \Illuminate\Support\Facades\DB::table('naxas_restaurant_ops_table_sessions as session')
            ->leftJoin('naxas_restaurant_ops_tables as table', function ($join) {
                $join->on('table.id', '=', \Illuminate\Support\Facades\DB::raw('COALESCE(session.active_table_id, session.table_id)'));
            })
            ->leftJoin('naxas_restaurant_ops_floors as floor', 'floor.id', '=', 'table.floor_id')
            ->leftJoin('admin_users as opener', 'opener.user_id', '=', 'session.opened_by')
            ->where(function ($query) use ($formModel, $posOrder) {
                $query->where('session.official_order_id', $formModel->order_id)
                    ->orWhere('session.pos_order_id', $posOrder->id);
            })
            ->selectRaw('session.*, table.name as table_name, table.table_number, floor.name as floor_name, COALESCE(NULLIF(opener.name, ""), opener.username) as opened_by_name')
            ->latest('session.id')
            ->first();

        $shift = \Illuminate\Support\Facades\DB::table('naxas_restaurant_ops_cashier_shifts as shift')
            ->leftJoin('admin_users as staff', 'staff.user_id', '=', 'shift.staff_id')
            ->where('shift.id', $payment->cashier_shift_id ?? $posOrder->shift_id)
            ->selectRaw('shift.*, COALESCE(NULLIF(staff.name, ""), staff.username, CONCAT("Staff #", shift.staff_id)) as staff_name')
            ->first();
    }

    $fmtDate = fn ($value) => $value ? make_carbon($value, false)?->format('M d, Y g:i A') : '-';
    $fmtMoney = fn ($value) => $value === null ? '-' : currency_format((float)$value);
    $label = fn ($value) => str((string)$value)->replace('_', ' ')->title();
    $tenderLabel = function ($tender) use ($label) {
        $provider = trim((string)($tender->provider_code ?? ''));

        return $provider !== '' ? $label($provider) : $label($tender->method);
    };
@endphp

@if($posOrder)
    <div class="card shadow-sm mt-3">
        <div class="card-header d-flex align-items-center justify-content-between">
            <strong>POS integration</strong>
            <span class="badge bg-light text-dark">POS #{{ $posOrder->id }}</span>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <h6 class="text-muted text-uppercase small mb-2">Order context</h6>
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td class="text-muted">Service</td><td class="text-end">{{ $label($posOrder->service_type) }}</td></tr>
                        <tr><td class="text-muted">POS status</td><td class="text-end">{{ $label($posOrder->status) }}</td></tr>
                        <tr><td class="text-muted">Guest count</td><td class="text-end">{{ $posOrder->guest_count ?: '-' }}</td></tr>
                        <tr><td class="text-muted">Waiter</td><td class="text-end">{{ $posOrder->waiter_name ?: '-' }}</td></tr>
                    </table>
                </div>

                <div class="col-md-4">
                    <h6 class="text-muted text-uppercase small mb-2">Table</h6>
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td class="text-muted">Table</td><td class="text-end">{{ $tableSession ? trim(($tableSession->floor_name ? $tableSession->floor_name.' - ' : '').($tableSession->table_number ?: $tableSession->table_name)) : '-' }}</td></tr>
                        <tr><td class="text-muted">Session</td><td class="text-end">{{ $tableSession ? '#'.$tableSession->id.' '.$label($tableSession->status) : '-' }}</td></tr>
                        <tr><td class="text-muted">Opened by</td><td class="text-end">{{ $tableSession->opened_by_name ?? '-' }}</td></tr>
                        <tr><td class="text-muted">Opened</td><td class="text-end">{{ $fmtDate($tableSession->opened_at ?? null) }}</td></tr>
                    </table>
                </div>

                <div class="col-md-4">
                    <h6 class="text-muted text-uppercase small mb-2">Receipt & cashier</h6>
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted">Receipt</td>
                            <td class="text-end">
                                @if($receipt)
                                    <a href="{{ route('naxas.restaurantops.pos.receipt.show', $posOrder->id) }}" target="_blank">{{ $receipt->receipt_number }}</a>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        <tr><td class="text-muted">Cashier</td><td class="text-end">{{ $payment->cashier_staff_name ?? $posOrder->cashier_name ?? '-' }}</td></tr>
                        <tr><td class="text-muted">Paid at</td><td class="text-end">{{ $fmtDate($payment->paid_at ?? null) }}</td></tr>
                        <tr><td class="text-muted">Paid total</td><td class="text-end">{{ $fmtMoney($payment->paid_total ?? null) }}</td></tr>
                    </table>
                </div>
            </div>

            <hr>

            <div class="row g-3">
                <div class="col-md-6">
                    <h6 class="text-muted text-uppercase small mb-2">Tender details</h6>
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Method</th><th class="text-end">Received</th><th class="text-end">Applied</th><th class="text-end">Change</th></tr></thead>
                        <tbody>
                        @forelse($tenders as $tender)
                            <tr>
                                <td>{{ $tenderLabel($tender) }}</td>
                                <td class="text-end">{{ $fmtMoney($tender->amount_received) }}</td>
                                <td class="text-end">{{ $fmtMoney($tender->amount_applied) }}</td>
                                <td class="text-end">{{ $fmtMoney($tender->change_amount) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">No paid tender recorded.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="col-md-6">
                    <h6 class="text-muted text-uppercase small mb-2">Shift</h6>
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td class="text-muted">Shift</td><td class="text-end">{{ $shift ? '#'.$shift->id : '-' }}</td></tr>
                        <tr><td class="text-muted">Staff</td><td class="text-end">{{ $shift->staff_name ?? '-' }}</td></tr>
                        <tr><td class="text-muted">Status</td><td class="text-end">{{ $shift ? $label($shift->status) : '-' }}</td></tr>
                        <tr><td class="text-muted">Opened</td><td class="text-end">{{ $fmtDate($shift->opened_at ?? null) }}</td></tr>
                        <tr><td class="text-muted">Expected cash</td><td class="text-end">{{ $fmtMoney($shift->expected_cash ?? null) }}</td></tr>
                        <tr><td class="text-muted">Variance</td><td class="text-end">{{ $fmtMoney($shift->variance ?? null) }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endif
