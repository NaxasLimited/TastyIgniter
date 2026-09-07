<?php

declare(strict_types=1);

namespace Naxas\RestaurantOps\Http\Controllers\Kitchen;

use Illuminate\Support\Facades\DB;
use Naxas\RestaurantOps\Contracts\LocationContextContract;
use Naxas\RestaurantOps\Http\Controllers\AdminPageController;
use Naxas\RestaurantOps\Models\PosOrder;
use Naxas\RestaurantOps\Models\PosOrderEvent;
use Naxas\RestaurantOps\Models\PosOrderItem;
use Naxas\RestaurantOps\Pos\Exceptions\PosException;
use Naxas\RestaurantOps\Pos\PosOrderStatus;
use Symfony\Component\HttpFoundation\Response;

final class KitchenTickets extends AdminPageController
{
    private const VISIBLE_ITEM_STATUSES = ['kitchen_pending', 'preparing', 'ready'];

    public function index(): Response
    {
        return response($this->renderAdminPage(
            'Naxas.RestaurantOps::kitchen.index',
            ['tickets' => $this->tickets(), 'activeLocation' => app(LocationContextContract::class)->current()],
            lang('Naxas.RestaurantOps::default.navigation.kitchen'),
            'restaurant-ops-kitchen',
        ));
    }

    public function feed(): Response
    {
        return response()->json(['data' => $this->tickets()]);
    }

    public function updateItem(string $itemId): Response
    {
        $target = (string)request()->input('status');
        $permission = match ($target) {
            'preparing' => 'Restaurant.Kitchen.Ticket.Prepare',
            'ready' => 'Restaurant.Kitchen.Ticket.Ready',
            'completed' => 'Restaurant.Kitchen.Ticket.Complete',
            default => 'Restaurant.Kitchen.Ticket.Accept',
        };

        if (! $this->user()->hasPermission($permission)) {
            throw PosException::forbidden('kitchen_permission_denied', 'You are not allowed to update this ticket.');
        }

        try {
            DB::transaction(function () use ($itemId, $target): void {
                $item = PosOrderItem::query()->lockForUpdate()->findOrFail($itemId);
                $order = PosOrder::query()->lockForUpdate()->findOrFail($item->pos_order_id);
                $this->assertBranch($order);
                $current = (string)$item->status;
                if ($current === 'unsent' && $order->status === PosOrderStatus::KITCHEN_PENDING) {
                    $current = 'kitchen_pending';
                    $item->forceFill(['kitchen_sent_quantity' => $item->quantity]);
                }
                $this->assertTransition($current, $target);

                $item->forceFill([
                    'status' => $target,
                    'version' => $item->version + 1,
                ])->save();

                $this->recordEvent($order, 'kitchen_item_'.$target, [
                    'item_id' => $item->getKey(),
                    'menu_id' => $item->menu_id,
                    'quantity' => $item->quantity,
                ]);

                if ($target === 'completed' && !$order->items()->whereIn('status', self::VISIBLE_ITEM_STATUSES)->exists()) {
                    $order->forceFill([
                        'status' => PosOrderStatus::PAYMENT_PENDING,
                        'version' => $order->version + 1,
                    ])->save();
                    $this->recordEvent($order, 'kitchen_order_completed');
                }
            }, 3);

            return response()->json(['data' => ['tickets' => $this->tickets()]]);
        } catch (PosException $e) {
            return response()->json(['error' => ['code' => $e->errorCode, 'message' => $e->getMessage()]], $e->status);
        }
    }

    private function tickets(): array
    {
        $locationId = app(LocationContextContract::class)->currentId();
        if (!$locationId) {
            return [];
        }

        $orders = PosOrder::query()
            ->with('items')
            ->select('naxas_restaurant_ops_pos_orders.*')
            ->selectRaw('official.order_id as official_order_id')
            ->selectRaw('COALESCE(NULLIF(waiter.name, ""), waiter.username) as waiter_name')
            ->selectRaw('session.guest_count as session_guest_count, table_info.table_number, table_info.name as table_name, floor.name as floor_name')
            ->leftJoin('orders as official', 'official.order_id', '=', 'naxas_restaurant_ops_pos_orders.order_id')
            ->leftJoin('admin_users as waiter', 'waiter.user_id', '=', 'naxas_restaurant_ops_pos_orders.waiter_id')
            ->leftJoin('naxas_restaurant_ops_table_sessions as session', 'session.id', '=', 'naxas_restaurant_ops_pos_orders.table_session_id')
            ->leftJoin('naxas_restaurant_ops_tables as table_info', function ($join): void {
                $join->on('table_info.id', '=', DB::raw('COALESCE(session.active_table_id, session.table_id)'));
            })
            ->leftJoin('naxas_restaurant_ops_floors as floor', 'floor.id', '=', 'table_info.floor_id')
            ->where('naxas_restaurant_ops_pos_orders.location_id', $locationId)
            ->whereIn('naxas_restaurant_ops_pos_orders.status', [PosOrderStatus::KITCHEN_PENDING, PosOrderStatus::PAYMENT_PENDING])
            ->whereExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('naxas_restaurant_ops_pos_order_items as item')
                    ->whereColumn('item.pos_order_id', 'naxas_restaurant_ops_pos_orders.id')
                    ->where(function ($query): void {
                        $query->whereIn('item.status', self::VISIBLE_ITEM_STATUSES)
                            ->orWhere(function ($query): void {
                                $query->where('naxas_restaurant_ops_pos_orders.status', PosOrderStatus::KITCHEN_PENDING)
                                    ->where('item.status', 'unsent');
                            });
                    });
            })
            ->orderBy('naxas_restaurant_ops_pos_orders.kitchen_ready_at')
            ->orderBy('naxas_restaurant_ops_pos_orders.id')
            ->get();

        return $orders->map(function (PosOrder $order): array {
            $items = $order->items
                ->filter(fn (PosOrderItem $item): bool => in_array((string)$item->status, self::VISIBLE_ITEM_STATUSES, true)
                    || ($order->status === PosOrderStatus::KITCHEN_PENDING && $item->status === 'unsent'))
                ->values()
                ->map(fn (PosOrderItem $item): array => $this->decorateItem($item, (string)$order->status))
                ->all();

            return [
                'id' => $order->getKey(),
                'officialOrderId' => $order->official_order_id ?: $order->order_id,
                'status' => $order->status,
                'serviceType' => $order->service_type,
                'serviceLabel' => $this->label((string)$order->service_type),
                'tableLabel' => $this->tableLabel($order),
                'waiterName' => $order->waiter_name,
                'guestCount' => $order->guest_count ?: $order->session_guest_count,
                'guestName' => $order->guest_name,
                'orderNote' => $order->order_note,
                'sentAt' => optional($order->kitchen_ready_at ?: $order->updated_at)->format('M d, g:i A'),
                'age' => optional($order->kitchen_ready_at ?: $order->updated_at)->diffForHumans(),
                'items' => $items,
            ];
        })->all();
    }

    private function decorateItem(PosOrderItem $item, string $orderStatus): array
    {
        $config = (array)$item->configuration_payload;
        $variant = (array)($config['variant'] ?? []);
        $status = (string)$item->status;
        if ($status === 'unsent' && $orderStatus === PosOrderStatus::KITCHEN_PENDING) {
            $status = 'kitchen_pending';
        }
        $modifiers = collect($config['modifiers'] ?? [])
            ->flatMap(function (array $group): array {
                return collect($group['modifiers'] ?? [])->map(function (array $modifier) use ($group): array {
                    return [
                        'group' => $group['kitchen_name'] ?? $group['name'] ?? 'Option',
                        'name' => $modifier['kitchen_name'] ?? $modifier['name'] ?? '',
                        'quantity' => $modifier['quantity'] ?? 1,
                    ];
                })->all();
            })
            ->filter(fn (array $modifier): bool => trim((string)$modifier['name']) !== '')
            ->values()
            ->all();

        return [
            'id' => $item->getKey(),
            'quantity' => $item->kitchen_sent_quantity ?: $item->quantity,
            'status' => $status,
            'statusLabel' => $this->label($status),
            'name' => $variant['kitchen_name'] ?? $config['kitchen_name'] ?? $config['menu_name'] ?? 'Menu item',
            'menuName' => $config['menu_name'] ?? null,
            'variantName' => $variant['name'] ?? null,
            'note' => $item->item_note ?: ($config['item_note'] ?? null),
            'modifiers' => $modifiers,
        ];
    }

    private function assertBranch(PosOrder $order): void
    {
        if ((int)$order->location_id !== (int)app(LocationContextContract::class)->currentId()) {
            throw PosException::forbidden('kitchen_location_forbidden', 'Cross-branch kitchen access is prohibited.');
        }
    }

    private function assertTransition(string $current, string $target): void
    {
        $allowed = [
            'kitchen_pending' => ['preparing', 'ready', 'completed'],
            'preparing' => ['ready', 'completed'],
            'ready' => ['completed'],
        ];

        if (!in_array($target, $allowed[$current] ?? [], true)) {
            throw PosException::conflict('kitchen_status_invalid', 'This kitchen ticket cannot move to that status.');
        }
    }

    private function recordEvent(PosOrder $order, string $type, array $payload = []): void
    {
        PosOrderEvent::create([
            'pos_order_id' => $order->getKey(),
            'event_type' => $type,
            'actor_id' => $this->user()->getAuthIdentifier(),
            'location_id' => $order->location_id,
            'payload' => $payload ?: null,
            'occurred_at' => now(),
        ]);
    }

    private function tableLabel(PosOrder $order): ?string
    {
        $table = trim((string)($order->table_number ?: $order->table_name));
        if ($table === '') {
            return null;
        }

        return trim(($order->floor_name ? $order->floor_name.' - ' : '').$table);
    }

    private function label(string $value): string
    {
        return str($value)->replace('_', ' ')->title()->toString();
    }

    private function user(): mixed
    {
        return app('admin.auth')->user();
    }
}
