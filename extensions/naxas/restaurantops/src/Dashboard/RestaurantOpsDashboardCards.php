<?php

declare(strict_types=1);

namespace Naxas\RestaurantOps\Dashboard;

use Igniter\Admin\DashboardWidgets\Statistics;
use Igniter\System\Models\Settings;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Naxas\RestaurantOps\Contracts\LocationContextContract;
use Naxas\RestaurantOps\Pos\PosOrderStatus;
use Naxas\RestaurantOps\Support\RawSql;

final class RestaurantOpsDashboardCards
{
    public function registerCards(): void
    {
        Statistics::registerCards(fn (): array => [
            'rops_today_sales' => [
                'label' => 'lang:Naxas.RestaurantOps::default.dashboard.today_sales',
                'icon' => ' text-success fa fa-4x fa-cash-register',
                'valueFrom' => $this->getValue(...),
            ],
            'rops_active_dine_in' => [
                'label' => 'lang:Naxas.RestaurantOps::default.dashboard.active_dine_in',
                'icon' => ' text-info fa fa-4x fa-utensils',
                'valueFrom' => $this->getValue(...),
            ],
            'rops_unpaid_orders' => [
                'label' => 'lang:Naxas.RestaurantOps::default.dashboard.unpaid_orders',
                'icon' => ' text-danger fa fa-4x fa-receipt',
                'valueFrom' => $this->getValue(...),
            ],
            'rops_paid_cash_today' => [
                'label' => 'lang:Naxas.RestaurantOps::default.dashboard.paid_cash_today',
                'icon' => ' text-warning fa fa-4x fa-money-bill',
                'valueFrom' => $this->getValue(...),
            ],
            'rops_paid_bkash_today' => [
                'label' => 'lang:Naxas.RestaurantOps::default.dashboard.paid_bkash_today',
                'icon' => ' text-primary fa fa-4x fa-mobile-screen',
                'valueFrom' => $this->getValue(...),
            ],
            'rops_paid_nagad_today' => [
                'label' => 'lang:Naxas.RestaurantOps::default.dashboard.paid_nagad_today',
                'icon' => ' text-primary fa fa-4x fa-mobile-screen-button',
                'valueFrom' => $this->getValue(...),
            ],
            'rops_paid_card_today' => [
                'label' => 'lang:Naxas.RestaurantOps::default.dashboard.paid_card_today',
                'icon' => ' text-info fa fa-4x fa-credit-card',
                'valueFrom' => $this->getValue(...),
            ],
        ]);
    }

    public function getValue(string $code, mixed $start, mixed $end, callable $callback): string|int
    {
        return match ($code) {
            'rops_today_sales' => $this->todaySales(),
            'rops_active_dine_in' => $this->activeDineIn(),
            'rops_unpaid_orders' => $this->unpaidOrders(),
            'rops_paid_cash_today' => $this->paidTenderToday('cash'),
            'rops_paid_bkash_today' => $this->paidTenderToday('bkash'),
            'rops_paid_nagad_today' => $this->paidTenderToday('nagad'),
            'rops_paid_card_today' => $this->paidTenderToday('card'),
            default => 0,
        };
    }

    private function todaySales(): string
    {
        $statuses = array_filter(array_map('intval', (array) Settings::get('completed_order_status')));
        $query = DB::table('orders')
            ->whereIn('status_id', $statuses !== [] ? $statuses : [5])
            ->whereDate('order_date', now()->toDateString());

        return currency_format($this->scopeLocation($query, 'location_id')->sum('order_total'));
    }

    private function activeDineIn(): int
    {
        $query = DB::table('naxas_restaurant_ops_pos_orders')
            ->where('service_type', 'dine_in')
            ->whereIn('status', [
                PosOrderStatus::DRAFT,
                PosOrderStatus::HELD,
                PosOrderStatus::ACTIVE,
                PosOrderStatus::KITCHEN_PENDING,
                PosOrderStatus::PAYMENT_PENDING,
            ]);

        return $this->scopeLocation($query, 'location_id')->count();
    }

    private function unpaidOrders(): int
    {
        $query = DB::table('naxas_restaurant_ops_pos_orders')
            ->whereIn('status', [
                PosOrderStatus::ACTIVE,
                PosOrderStatus::KITCHEN_PENDING,
                PosOrderStatus::PAYMENT_PENDING,
                PosOrderStatus::PAYMENT_FAILED,
            ])
            ->where(function (Builder $query): void {
                $query->where('outstanding_total', '>', 0)
                    ->orWhere(function (Builder $query): void {
                        $query->where('order_total', '>', 0)
                            ->whereNull('outstanding_total');
                    });
            });

        return $this->scopeLocation($query, 'location_id')->count();
    }

    private function paidTenderToday(string $tender): string
    {
        $query = DB::table('naxas_restaurant_ops_pos_payment_tenders as tenders')
            ->join('naxas_restaurant_ops_pos_payments as payments', 'payments.id', '=', 'tenders.pos_payment_id')
            ->where('payments.status', 'paid')
            ->where('tenders.status', 'applied')
            ->whereDate('payments.paid_at', now()->toDateString());

        match ($tender) {
            'cash' => $query->where('tenders.method', 'cash'),
            'card' => $query->where('tenders.method', 'card'),
            'bkash' => $query->whereIn(DB::raw(RawSql::qualifyAliases('LOWER(COALESCE(tenders.provider_code, ""))', ['tenders'])), ['bkash', 'b-kash']),
            'nagad' => $query->where(DB::raw(RawSql::qualifyAliases('LOWER(COALESCE(tenders.provider_code, ""))', ['tenders'])), 'nagad'),
            default => null,
        };

        return currency_format($this->scopeLocation($query, 'payments.location_id')->sum('tenders.amount_applied'));
    }

    private function scopeLocation(Builder $query, string $column): Builder
    {
        $locations = app(LocationContextContract::class);

        if ($locations->isGlobal()) {
            return $query;
        }

        if ($locationId = $locations->currentId()) {
            return $query->where($column, $locationId);
        }

        $ids = $locations->authorizedLocations()->pluck('location_id')->filter()->values()->all();

        return $ids !== [] ? $query->whereIn($column, $ids) : $query->whereRaw('1 = 0');
    }
}
