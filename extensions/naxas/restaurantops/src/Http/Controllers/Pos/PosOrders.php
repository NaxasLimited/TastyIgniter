<?php

declare(strict_types=1);

namespace Naxas\RestaurantOps\Http\Controllers\Pos;

use Igniter\Cart\Models\Category;
use Igniter\Cart\Models\Menu;
use Igniter\User\Models\User;
use Illuminate\Support\Facades\DB;
use Naxas\RestaurantOps\Contracts\LocationContextContract;
use Naxas\RestaurantOps\Http\Controllers\AdminPageController;
use Naxas\RestaurantOps\Models\ItemVariant;
use Naxas\RestaurantOps\Models\PosOrder;
use Naxas\RestaurantOps\Models\PosOrderItem;
use Naxas\RestaurantOps\Models\RestaurantTable;
use Naxas\RestaurantOps\Pos\Contracts\PosOrderServiceContract;
use Naxas\RestaurantOps\Pos\Exceptions\PosException;
use Naxas\RestaurantOps\Shifts\Contracts\ShiftContextContract;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class PosOrders extends AdminPageController
{
    public function __construct(private readonly PosOrderServiceContract $orders)
    {
        parent::__construct();
    }

    public function screen(): Response
    {
        $user = $this->user();
        $locationId = app(LocationContextContract::class)->currentId();
        $shift = app(ShiftContextContract::class)->currentForStaff((int) $user->getAuthIdentifier());
        $held = $shift ? PosOrder::where('shift_id', $shift->getKey())->where('status', 'held')->latest()->limit(20)->get() : collect();
        $orders = $shift ? PosOrder::with('items')->where('shift_id', $shift->getKey())->whereIn('status', ['draft', 'held', 'active', 'kitchen_pending', 'payment_pending'])->latest()->limit(80)->get() : collect();
        $categories = Category::query()
            ->where('status', true)
            ->whereHas('menus', fn($query) => $query->where('menu_status', true))
            ->orderBy('priority')
            ->orderBy('name')
            ->get();
        $menus = Menu::query()
            ->with(['categories', 'media', 'restaurant_ops_metadata', 'restaurant_ops_variants'])
            ->withCount('menu_options')
            ->where('menu_status', true)
            ->orderBy('menu_priority')
            ->orderBy('menu_name')
            ->limit(60)
            ->get();
        $menuConfigurations = $this->menuConfigurations($menus->pluck('menu_id')->all());
        $tables = $locationId ? RestaurantTable::with(['floor', 'activeSession'])
            ->where('location_id', $locationId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('table_number')
            ->get()
            ->map(fn ($table) => ['id' => $table->getKey(), 'label' => trim(($table->floor?->name ? $table->floor->name.' - ' : '').($table->table_number ?: $table->name)), 'name' => $table->name, 'status' => $table->status, 'session_order_id' => $table->activeSession?->pos_order_id])
            ->values() : collect();
        $waiters = User::query()
            ->where('status', true)
            ->orderBy('name')
            ->get()
            ->filter(fn ($staff) => $staff->hasPermission('Restaurant.Waiter.Access') || $staff->hasPermission('Restaurant.POS.Access'))
            ->map(fn ($staff) => ['id' => $staff->getKey(), 'name' => $staff->name ?: $staff->username ?: ('Staff '.$staff->getKey())])
            ->values();
        $serviceOrders = $orders->map(fn ($order) => [
            'id' => $order->getKey(),
            'service_type' => $order->service_type,
            'status' => $order->status,
            'total' => number_format((float) $order->order_total, 2, '.', ''),
            'item_count' => $order->items->whereNotIn('status', ['removed', 'voided'])->sum('quantity'),
            'guest_name' => $order->guest_name,
            'guest_phone' => $order->guest_phone,
            'guest_count' => $order->guest_count,
            'table_session_id' => $order->table_session_id,
            'waiter_id' => $order->waiter_id,
            'created_at' => optional($order->created_at)->diffForHumans(),
        ])->values();
        $serviceCounts = [
            'dine_in' => $orders->where('service_type', 'dine_in')->count(),
            'collection' => $orders->where('service_type', 'collection')->count(),
            'delivery' => $orders->where('service_type', 'delivery')->count(),
        ];

        return response($this->renderAdminPage('Naxas.RestaurantOps::pos.index', compact('shift', 'held', 'orders', 'menus', 'categories', 'menuConfigurations', 'tables', 'waiters', 'serviceOrders', 'serviceCounts'), lang('Naxas.RestaurantOps::default.navigation.pos'), 'restaurant-ops-pos'));
    }

    public function index(): Response
    {
        $query = PosOrder::with('items')->where('location_id', app(LocationContextContract::class)->currentId())->latest();
        if (request()->filled('status')) {
            $query->where('status', request()->string('status'));
        }

        return response()->json(['data' => $query->paginate(30)]);
    }

    public function active(): Response
    {
        return $this->orderList('active', lang('Naxas.RestaurantOps::default.navigation.active_orders'));
    }

    public function held(): Response
    {
        return $this->orderList('held', lang('Naxas.RestaurantOps::default.navigation.held_orders'));
    }

    public function show(string $posOrderId): Response
    {
        $posOrder = PosOrder::query()->findOrFail($posOrderId);
        $this->resource($posOrder);

        return response()->json(['data' => $this->decorateOrder($posOrder->load(['items', 'approvals', 'events']))]);
    }

    public function store(): Response
    {
        return $this->respond(fn () => $this->orders->createDraft($this->user(), request()->all(), (string) request()->header('Idempotency-Key')), 201);
    }

    public function update(string $posOrderId): Response
    {
        $posOrder = PosOrder::query()->findOrFail($posOrderId);
        $this->resource($posOrder);

        return $this->respond(fn () => $this->orders->updateDetails($posOrder, $this->user(), request()->all(), $this->version()));
    }

    public function addItem(string $posOrderId): Response
    {
        $posOrder = PosOrder::query()->findOrFail($posOrderId);
        $this->resource($posOrder);

        return $this->respond(fn () => $this->orders->addItem($posOrder, $this->user(), request()->all(), $this->version(), (string) request()->header('Idempotency-Key')), 201);
    }

    public function updateItem(string $posOrderId, string $itemId): Response
    {
        $posOrder = PosOrder::query()->findOrFail($posOrderId);
        $this->resource($posOrder);
        $item = PosOrderItem::query()->where('pos_order_id', $posOrder->getKey())->findOrFail($itemId);

        return $this->respond(fn () => $this->orders->updateItem($posOrder, $item, $this->user(), request()->all(), $this->version()));
    }

    public function removeItem(string $posOrderId, string $itemId): Response
    {
        $posOrder = PosOrder::query()->findOrFail($posOrderId);
        $this->resource($posOrder);
        $item = PosOrderItem::query()->where('pos_order_id', $posOrder->getKey())->findOrFail($itemId);

        return $this->respond(fn () => $this->orders->removeItem($posOrder, $item, $this->user(), $this->version(), request()->input('reason')));
    }

    public function discount(string $posOrderId): Response
    {
        $posOrder = PosOrder::query()->findOrFail($posOrderId);
        $this->resource($posOrder);

        return response()->json(['error' => ['code' => 'pos_discount_approval_required', 'message' => 'Discount approval metadata is available; automatic application remains disabled by the conservative zero threshold.']], 409);
    }

    public function voidRequest(string $posOrderId): Response
    {
        $posOrder = PosOrder::query()->findOrFail($posOrderId);
        $this->resource($posOrder);

        return response()->json(['error' => ['code' => 'pos_void_approval_required', 'message' => 'Manager approval is required for kitchen-visible item voids.']], 409);
    }

    public function hold(string $posOrderId): Response
    {
        $posOrder = PosOrder::query()->findOrFail($posOrderId);

        return $this->action($posOrder, fn () => $this->orders->hold($posOrder, $this->user(), $this->version(), request()->input('reason')));
    }

    public function recall(string $posOrderId): Response
    {
        $posOrder = PosOrder::query()->findOrFail($posOrderId);

        return $this->action($posOrder, fn () => $this->orders->recall($posOrder, $this->user(), $this->version()));
    }

    public function confirm(string $posOrderId): Response
    {
        $posOrder = PosOrder::query()->findOrFail($posOrderId);

        return $this->action($posOrder, fn () => $this->orders->confirm($posOrder, $this->user(), $this->version()));
    }

    public function kitchen(string $posOrderId): Response
    {
        $posOrder = PosOrder::query()->findOrFail($posOrderId);

        return $this->action($posOrder, fn () => $this->orders->requestKitchen($posOrder, $this->user(), $this->version()));
    }

    public function payment(string $posOrderId): Response
    {
        $posOrder = PosOrder::query()->findOrFail($posOrderId);

        return $this->action($posOrder, fn () => $this->orders->lockForPayment($posOrder, $this->user(), $this->version()));
    }

    public function cancel(string $posOrderId): Response
    {
        $posOrder = PosOrder::query()->findOrFail($posOrderId);

        return $this->action($posOrder, fn () => $this->orders->cancel($posOrder, $this->user(), $this->version(), (string) request()->input('reason')));
    }

    private function action(PosOrder $order, callable $action): Response
    {
        $this->resource($order);

        return $this->respond($action);
    }

    private function resource(PosOrder $order): void
    {
        if ((int) $order->location_id !== (int) app(LocationContextContract::class)->currentId()) {
            throw PosException::forbidden('pos_location_forbidden', 'Cross-location POS access is prohibited.');
        }
    }

    private function version(): int
    {
        $value = filter_var(request()->input('version'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (! $value) {
            throw new PosException('pos_order_version_conflict', 'A valid order version is required.', 409);
        }

        return $value;
    }

    private function respond(callable $callback, int $status = 200): Response
    {
        try {
            $data = $callback();
            if ($data instanceof PosOrder) {
                $data = $this->decorateOrder($data);
            }

            return response()->json(['data' => $data], $status);
        } catch (PosException $e) {
            return response()->json(['error' => ['code' => $e->errorCode, 'message' => $e->getMessage()]], $e->status);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['error' => ['code' => 'pos_concurrency_conflict', 'message' => 'The POS operation could not be completed safely.']], 409);
        }
    }

    private function decorateOrder(PosOrder $order): PosOrder
    {
        $session = null;
        if ($order->table_session_id) {
            $session = DB::table('naxas_restaurant_ops_table_sessions as session')
                ->leftJoin('naxas_restaurant_ops_tables as table', 'table.id', '=', 'session.active_table_id')
                ->leftJoin('naxas_restaurant_ops_floors as floor', 'floor.id', '=', 'table.floor_id')
                ->where('session.id', $order->table_session_id)
                ->selectRaw('session.table_id, session.active_table_id, session.guest_count as session_guest_count, table.name, table.table_number, floor.name as floor_name')
                ->first();
        }

        $waiter = $order->waiter_id ? User::query()->find($order->waiter_id) : null;
        $tableId = $session ? ((int) ($session->active_table_id ?: $session->table_id)) : null;
        $tableName = $session ? trim((string) ($session->table_number ?: $session->name)) : '';
        $tableLabel = $session ? trim(($session->floor_name ? $session->floor_name.' - ' : '').$tableName) : null;

        $order->setAttribute('table_id', $tableId);
        $order->setAttribute('assigned_table_id', $tableId);
        $order->setAttribute('table_label', $tableLabel ?: null);
        $order->setAttribute('session_guest_count', $session?->session_guest_count);
        $order->setAttribute('waiter_name', $waiter?->name ?: $waiter?->username);
        $order->setAttribute('reportable_dine_in', $order->service_type !== 'dine_in' || ($tableId && $order->waiter_id && (int) $order->guest_count > 0));

        return $order;
    }

    private function user(): mixed
    {
        return app('admin.auth')->user();
    }

    private function menuConfigurations(array $menuIds): array
    {
        if (! $menuIds) {
            return [];
        }

        $configurations = [];
        ItemVariant::query()
            ->whereIn('menu_id', $menuIds)
            ->where('is_active', true)
            ->whereNull('archived_at')
            ->orderBy('display_order')
            ->orderBy('name')
            ->get()
            ->each(function (ItemVariant $variant) use (&$configurations): void {
                $menuId = (int) $variant->menu_id;
                $configurations[$menuId]['variants'][] = [
                    'id' => $variant->getKey(),
                    'name' => $variant->name,
                    'code' => $variant->code,
                    'priceMode' => $variant->price_mode,
                    'priceValue' => (float) $variant->price_value,
                    'isDefault' => (bool) $variant->is_default,
                ];
            });

        $rows = DB::table('naxas_restaurant_ops_menu_modifier_groups as attachments')
            ->join('naxas_restaurant_ops_modifier_groups as groups', 'attachments.modifier_group_id', '=', 'groups.id')
            ->join('menu_item_options as menu_options', function ($join): void {
                $join->on('menu_options.menu_id', '=', 'attachments.menu_id')
                    ->on('menu_options.option_id', '=', 'groups.option_id');
            })
            ->join('menu_item_option_values as menu_values', 'menu_values.menu_option_id', '=', 'menu_options.menu_option_id')
            ->join('menu_option_values as option_values', 'option_values.option_value_id', '=', 'menu_values.option_value_id')
            ->join('naxas_restaurant_ops_modifier_metadata as modifiers', 'modifiers.option_value_id', '=', 'option_values.option_value_id')
            ->whereIn('attachments.menu_id', $menuIds)
            ->where('attachments.is_active', true)
            ->where('groups.is_active', true)
            ->where('modifiers.is_active', true)
            ->whereNull('groups.archived_at')
            ->whereNull('modifiers.archived_at')
            ->orderBy('attachments.menu_id')
            ->orderBy('attachments.display_order')
            ->orderBy('groups.name')
            ->orderBy('menu_values.priority')
            ->orderBy('option_values.priority')
            ->get([
                'attachments.menu_id',
                'groups.id as group_id',
                'groups.name as group_name',
                'groups.selection_type',
                'groups.is_required',
                'groups.min_selections',
                'groups.max_selections',
                'groups.allow_quantity as group_allows_quantity',
                'modifiers.id as modifier_id',
                'modifiers.allow_quantity',
                'modifiers.max_quantity',
                'modifiers.is_default',
                'option_values.name as modifier_name',
                DB::raw('COALESCE(menu_values.override_price, modifiers.price_adjustment, option_values.price, 0) as price'),
            ]);

        foreach ($rows as $row) {
            $menuId = (int) $row->menu_id;
            $groupId = (int) $row->group_id;
            $configurations[$menuId]['groups'][$groupId] ??= [
                'id' => $groupId,
                'name' => $row->group_name,
                'type' => $row->selection_type,
                'required' => (bool) $row->is_required,
                'min' => (int) $row->min_selections,
                'max' => (int) $row->max_selections,
                'allowQuantity' => (bool) $row->group_allows_quantity,
                'modifiers' => [],
            ];
            $configurations[$menuId]['groups'][$groupId]['modifiers'][] = [
                'id' => (int) $row->modifier_id,
                'name' => $row->modifier_name,
                'price' => (float) $row->price,
                'allowQuantity' => (bool) $row->allow_quantity,
                'maxQuantity' => max(1, (int) $row->max_quantity),
                'isDefault' => (bool) $row->is_default,
            ];
        }

        foreach ($configurations as &$configuration) {
            $configuration['groups'] = array_values($configuration['groups'] ?? []);
        }

        return $configurations;
    }

    private function orderList(string $status, string $title): Response
    {
        $orders = PosOrder::with('items')
            ->where('location_id', app(LocationContextContract::class)->currentId())
            ->where('status', $status)
            ->latest()
            ->paginate(30);

        $menuItem = $status === 'held' ? 'restaurant-ops-pos-held' : 'restaurant-ops-pos-active';

        return response($this->renderAdminPage('Naxas.RestaurantOps::pos.orders', compact('orders', 'status', 'title'), $title, $menuItem));
    }
}
