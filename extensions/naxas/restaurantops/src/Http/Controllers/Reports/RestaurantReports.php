<?php

declare(strict_types=1);

namespace Naxas\RestaurantOps\Http\Controllers\Reports;

use Carbon\CarbonImmutable;
use Igniter\System\Models\Settings;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Naxas\RestaurantOps\Contracts\LocationContextContract;
use Naxas\RestaurantOps\Http\Controllers\AdminPageController;

final class RestaurantReports extends AdminPageController
{
    private LocationContextContract $locations;

    public function __construct()
    {
        parent::__construct();

        $this->locations = app(LocationContextContract::class);
    }

    public function index(): string
    {
        [$from, $to] = $this->dateRange();
        $completedStatuses = $this->completedStatuses();

        $branchSales = $this->branchSales($from, $to, $completedStatuses);
        $serviceSummary = $this->serviceSummary($from, $to, $completedStatuses);
        $paymentSummary = $this->paymentSummary($from, $to);
        $shiftSummary = $this->shiftSummary($from, $to);

        $totals = [
            'sales' => (float)$branchSales->sum('sales_total'),
            'orders' => (int)$branchSales->sum('order_count'),
            'payments' => (float)$paymentSummary->sum('amount_total'),
            'payment_count' => (int)$paymentSummary->sum('payment_count'),
        ];
        $totals['average_ticket'] = $totals['orders'] > 0 ? $totals['sales'] / $totals['orders'] : 0;

        return $this->renderAdminPage('Naxas.RestaurantOps::reports.index', [
            'from' => $from,
            'to' => $to,
            'activeLocation' => $this->locations->current(),
            'isGlobal' => $this->locations->isGlobal(),
            'branchSales' => $branchSales,
            'serviceSummary' => $serviceSummary,
            'paymentSummary' => $paymentSummary,
            'shiftSummary' => $shiftSummary,
            'totals' => $totals,
        ], 'Restaurant Ops Reports', 'restaurant-ops-reports');
    }

    private function dateRange(): array
    {
        $from = $this->parseDate((string)request('from'), now()->subDays(30)->toDateString())->startOfDay();
        $to = $this->parseDate((string)request('to'), now()->toDateString())->endOfDay();

        if ($from->greaterThan($to)) {
            return [$to->startOfDay(), $from->endOfDay()];
        }

        return [$from, $to];
    }

    private function parseDate(string $value, string $fallback): CarbonImmutable
    {
        try {
            return CarbonImmutable::parse(trim($value) !== '' ? $value : $fallback);
        } catch (\Throwable) {
            return CarbonImmutable::parse($fallback);
        }
    }

    private function completedStatuses(): array
    {
        $statuses = array_filter(array_map('intval', (array)Settings::get('completed_order_status')));

        return $statuses !== [] ? $statuses : [5];
    }

    private function orderBase(CarbonImmutable $from, CarbonImmutable $to, array $completedStatuses): Builder
    {
        $query = DB::table('orders')
            ->whereIn('orders.status_id', $completedStatuses)
            ->whereBetween('orders.order_date', [$from->toDateString(), $to->toDateString()]);

        return $this->scopeLocation($query, 'orders.location_id');
    }

    private function branchSales(CarbonImmutable $from, CarbonImmutable $to, array $completedStatuses): Collection
    {
        return $this->orderBase($from, $to, $completedStatuses)
            ->leftJoin('locations', 'locations.location_id', '=', 'orders.location_id')
            ->selectRaw('orders.location_id, COALESCE(locations.location_name, CONCAT("Branch #", orders.location_id)) as branch_name')
            ->selectRaw('COUNT(*) as order_count, COALESCE(SUM(orders.order_total), 0) as sales_total, COALESCE(AVG(orders.order_total), 0) as average_ticket')
            ->groupBy('orders.location_id', 'locations.location_name')
            ->orderBy('branch_name')
            ->get();
    }

    private function serviceSummary(CarbonImmutable $from, CarbonImmutable $to, array $completedStatuses): Collection
    {
        $rows = $this->orderBase($from, $to, $completedStatuses)
            ->selectRaw('orders.order_type, COUNT(*) as order_count, COALESCE(SUM(orders.order_total), 0) as sales_total, COALESCE(AVG(orders.order_total), 0) as average_ticket')
            ->groupBy('orders.order_type')
            ->get()
            ->keyBy('order_type');

        return collect([
            'dine_in' => 'Dine-in',
            'collection' => 'Pickup',
            'delivery' => 'Delivery',
        ])->map(fn (string $label, string $type): array => [
            'service' => $label,
            'order_count' => (int)($rows[$type]->order_count ?? 0),
            'sales_total' => (float)($rows[$type]->sales_total ?? 0),
            'average_ticket' => (float)($rows[$type]->average_ticket ?? 0),
        ])->values();
    }

    private function paymentSummary(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        $categorySql = "CASE
            WHEN LOWER(tenders.method) = 'cash' THEN 'cash'
            WHEN LOWER(tenders.method) = 'card' THEN 'card'
            WHEN LOWER(COALESCE(tenders.provider_code, '')) IN ('bkash', 'b-kash') THEN 'bkash'
            WHEN LOWER(COALESCE(tenders.provider_code, '')) = 'nagad' THEN 'nagad'
            WHEN LOWER(COALESCE(tenders.provider_code, '')) = 'rocket' THEN 'other'
            ELSE 'other'
        END";

        $query = DB::table('naxas_restaurant_ops_pos_payment_tenders as tenders')
            ->join('naxas_restaurant_ops_pos_payments as payments', 'payments.id', '=', 'tenders.pos_payment_id')
            ->where('payments.status', 'paid')
            ->where('tenders.status', 'applied')
            ->whereBetween('payments.paid_at', [$from, $to])
            ->selectRaw($categorySql.' as tender_key')
            ->selectRaw('COUNT(*) as tender_count, COUNT(DISTINCT payments.id) as payment_count, COALESCE(SUM(tenders.amount_applied), 0) as amount_total')
            ->groupByRaw($categorySql)
            ->orderByDesc('amount_total');

        $rows = $this->scopeLocation($query, 'payments.location_id')->get()->keyBy('tender_key');

        return collect([
            'cash' => 'Cash',
            'bkash' => 'bKash',
            'nagad' => 'Nagad',
            'card' => 'Card',
            'other' => 'Other',
        ])->map(function (string $label, string $key) use ($rows): object {
            return (object)[
                'tender_key' => $key,
                'method_label' => $label,
                'tender_count' => (int)($rows[$key]->tender_count ?? 0),
                'payment_count' => (int)($rows[$key]->payment_count ?? 0),
                'amount_total' => (float)($rows[$key]->amount_total ?? 0),
            ];
        })->reject(function (object $row): bool {
            return $row->tender_key === 'other' && $row->tender_count === 0;
            });
    }

    private function shiftSummary(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        $paymentTotals = DB::table('naxas_restaurant_ops_pos_payments')
            ->selectRaw('cashier_shift_id, COUNT(*) as payment_count, COALESCE(SUM(paid_total), 0) as paid_total')
            ->where('status', 'paid')
            ->groupBy('cashier_shift_id');

        $query = DB::table('naxas_restaurant_ops_cashier_shifts as shifts')
            ->leftJoinSub($paymentTotals, 'shift_payments', 'shift_payments.cashier_shift_id', '=', 'shifts.id')
            ->leftJoin('locations', 'locations.location_id', '=', 'shifts.location_id')
            ->leftJoin('admin_users', 'admin_users.user_id', '=', 'shifts.staff_id')
            ->whereBetween('shifts.opened_at', [$from, $to])
            ->selectRaw('shifts.id, shifts.status, shifts.opened_at, shifts.submitted_at, shifts.approved_at, shifts.expected_cash, shifts.counted_cash, shifts.variance')
            ->selectRaw('COALESCE(locations.location_name, CONCAT("Branch #", shifts.location_id)) as branch_name')
            ->selectRaw('COALESCE(NULLIF(admin_users.name, ""), admin_users.username, CONCAT("Staff #", shifts.staff_id)) as staff_name')
            ->selectRaw('COALESCE(shift_payments.payment_count, 0) as payment_count, COALESCE(shift_payments.paid_total, 0) as paid_total')
            ->orderByDesc('shifts.opened_at')
            ->limit(25);

        return $this->scopeLocation($query, 'shifts.location_id')->get();
    }

    private function scopeLocation(Builder $query, string $column): Builder
    {
        if ($this->locations->isGlobal()) {
            return $query;
        }

        if ($locationId = $this->locations->currentId()) {
            return $query->where($column, $locationId);
        }

        $ids = $this->locations->authorizedLocations()->pluck('location_id')->filter()->values()->all();

        return $ids !== [] ? $query->whereIn($column, $ids) : $query->whereRaw('1 = 0');
    }

}
