<?php

declare(strict_types=1);

namespace Naxas\RestaurantOps\Http\Controllers\Reservations;

use Igniter\Flame\Exception\FlashException;
use Igniter\Reservation\Models\Reservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Naxas\RestaurantOps\Contracts\LocationContextContract;
use Naxas\RestaurantOps\Http\Controllers\AdminPageController;
use Naxas\RestaurantOps\Models\PosOrder;
use Naxas\RestaurantOps\Pos\Contracts\PosOrderServiceContract;
use Throwable;

final class ReservationPosOrders extends AdminPageController
{
    public function __construct(private readonly PosOrderServiceContract $orders)
    {
        parent::__construct();
    }

    public function open(string $reservation): RedirectResponse
    {
        $reservationModel = Reservation::with('tables')->findOrFail($reservation);
        $locationId = app(LocationContextContract::class)->currentId();

        if ((int) $reservationModel->location_id !== (int) $locationId) {
            flash()->error('Select the reservation branch before opening POS order.');

            return redirect()->back();
        }

        if ($existing = $this->existingOrder($reservationModel)) {
            flash()->success('Existing POS order opened.');

            return redirect()->route('naxas.restaurantops.pos', ['pos_order_id' => $existing->getKey()]);
        }

        try {
            $order = $this->orders->createDraft($this->user(), [
                'service_type' => 'dine_in',
                'table_id' => (int) request('table_id'),
                'waiter_id' => (int) request('waiter_id'),
                'guest_count' => max(1, (int) request('guest_count', $reservationModel->guest_num ?: 1)),
                'customer_id' => $reservationModel->customer_id,
                'guest_name' => trim($reservationModel->customer_name) ?: null,
                'guest_phone' => $reservationModel->telephone ?: null,
                'guest_email' => $reservationModel->email ?: null,
                'order_note' => trim((string) $reservationModel->comment) ?: null,
            ], 'reservation-pos-'.Str::uuid()->toString());

            if (Schema::hasColumn('naxas_restaurant_ops_pos_orders', 'reservation_id')) {
                $order->forceFill(['reservation_id' => $reservationModel->getKey()])->save();
            }

            flash()->success('Reservation opened in POS.');

            return redirect()->route('naxas.restaurantops.pos', ['pos_order_id' => $order->getKey()]);
        } catch (Throwable $exception) {
            throw new FlashException($exception->getMessage());
        }
    }

    private function existingOrder(Reservation $reservation): ?PosOrder
    {
        if (! Schema::hasColumn('naxas_restaurant_ops_pos_orders', 'reservation_id')) {
            return null;
        }

        return PosOrder::query()
            ->where('reservation_id', $reservation->getKey())
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->latest('id')
            ->first();
    }

    private function user(): mixed
    {
        return app('admin.auth')->user();
    }
}
