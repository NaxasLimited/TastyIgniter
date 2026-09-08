@php
    use Igniter\User\Models\User;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Schema;
    use Naxas\RestaurantOps\Models\PosOrder;
    use Naxas\RestaurantOps\Models\RestaurantTable;
    use Naxas\RestaurantOps\Tables\TableSessionStatus;

    $reservation = $formModel;
    $existingPosOrder = Schema::hasColumn('naxas_restaurant_ops_pos_orders', 'reservation_id')
        ? PosOrder::query()
            ->where('reservation_id', $reservation->getKey())
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->latest('id')
            ->first()
        : null;

    $reservationTables = $reservation->tables ?: collect();
    $reservationTableNames = $reservationTables->pluck('name')->filter()->map(fn ($name) => strtolower(trim((string) $name)))->all();
    $reservationTableIds = $reservationTables->pluck('id')->map(fn ($id) => (int) $id)->all();

    $occupiedTableIds = DB::table('naxas_restaurant_ops_table_sessions')
        ->whereIn('status', [TableSessionStatus::OPEN, TableSessionStatus::BILLING])
        ->whereNotNull('active_table_id')
        ->pluck('active_table_id')
        ->map(fn ($id) => (int) $id)
        ->all();

    $tables = RestaurantTable::with('floor')
        ->where('location_id', $reservation->location_id)
        ->where('is_active', true)
        ->orderBy('sort_order')
        ->orderBy('table_number')
        ->get();

    $suggestedTable = $tables->first(function ($table) use ($reservationTableIds, $reservationTableNames) {
        if (in_array((int) $table->getKey(), $reservationTableIds, true)) {
            return true;
        }

        $names = array_filter([
            strtolower(trim((string) $table->name)),
            strtolower(trim((string) $table->table_number)),
            strtolower(trim((string) $table->code)),
        ]);

        return count(array_intersect($names, $reservationTableNames)) > 0;
    });

    $waiters = User::query()
        ->where('status', true)
        ->orderBy('name')
        ->get()
        ->filter(fn ($staff) => $staff->hasPermission('Restaurant.Waiter.Access') || $staff->hasPermission('Restaurant.POS.Access'));

    $suggestedWaiterId = (int) old('waiter_id', $reservation->assignee_id ?: app('admin.auth')->user()?->getAuthIdentifier());
@endphp

<div class="card shadow-sm">
    <div class="card-body">
        <div class="d-flex align-items-start justify-content-between gap-3">
            <div>
                <h5 class="mb-1">Reservation to POS</h5>
                <p class="text-muted mb-0">Open this reservation as a reportable dine-in POS order with table, waiter and guest count.</p>
            </div>
            @if($existingPosOrder)
                <a class="btn btn-primary" href="{{ route('naxas.restaurantops.pos', ['pos_order_id' => $existingPosOrder->getKey()]) }}">Open POS #{{ $existingPosOrder->getKey() }}</a>
            @endif
        </div>

        @if(!$existingPosOrder)
            <form class="mt-3" method="POST" action="{{ route('naxas.restaurantops.reservations.open-pos', $reservation->getKey()) }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label">Table</label>
                        <select class="form-select" name="table_id" required>
                            <option value="">Select table</option>
                            @foreach($tables as $table)
                                @php
                                    $tableId = (int) $table->getKey();
                                    $isOccupied = in_array($tableId, $occupiedTableIds, true);
                                    $label = trim(($table->floor?->name ? $table->floor->name.' / ' : '').($table->table_number ?: $table->name));
                                @endphp
                                <option value="{{ $tableId }}" @selected($suggestedTable && (int) $suggestedTable->getKey() === $tableId) @disabled($isOccupied)>
                                    {{ $label }}{{ $isOccupied ? ' - occupied' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @if($reservation->table_name)
                            <small class="text-muted">Reservation table: {{ $reservation->table_name }}</small>
                        @endif
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Waiter</label>
                        <select class="form-select" name="waiter_id" required>
                            <option value="">Select waiter</option>
                            @foreach($waiters as $waiter)
                                <option value="{{ $waiter->getKey() }}" @selected((int) $waiter->getKey() === $suggestedWaiterId)>
                                    {{ $waiter->name ?: $waiter->username }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Guests</label>
                        <input class="form-control" type="number" name="guest_count" min="1" max="999" value="{{ max(1, (int) $reservation->guest_num) }}" required>
                    </div>
                </div>
                <div class="mt-3 d-flex align-items-center gap-2">
                    <button class="btn btn-success" type="submit">Open POS order</button>
                    <span class="text-muted">{{ trim($reservation->customer_name) ?: 'Walk-in guest' }}{{ $reservation->telephone ? ' / '.$reservation->telephone : '' }}</span>
                </div>
            </form>
        @endif
    </div>
</div>
