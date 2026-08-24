@include('Naxas.RestaurantOps::_partials.ops-style')
<div class="container-fluid py-3 rops-shell">
    <div class="rops-toolbar">
        <div class="rops-title"><h1>@lang('Naxas.RestaurantOps::default.navigation.active_shift')</h1><p>{{ now()->format('l, M j') }}</p></div>
        <a class="btn btn-light" href="{{ route('naxas.restaurantops.pos') }}">POS</a>
    </div>
    <div class="rops-card">
        <div class="rops-card-body">
            @if($shift)
                <div class="rops-grid rops-grid-3 mb-3">
                    <div class="rops-stat"><span>Shift</span><strong>#{{ $shift->getKey() }}</strong></div>
                    <div class="rops-stat"><span>Status</span><strong>{{ str($shift->status->value)->replace('_',' ')->title() }}</strong></div>
                    <div class="rops-stat"><span>Opened</span><strong>{{ optional($shift->opened_at)->format('H:i') }}</strong></div>
                </div>
                <a class="btn btn-primary" href="{{ route('naxas.restaurantops.shifts.show', $shift) }}">View Shift</a>
            @else
                <div class="rops-muted mb-3">No active shift.</div>
                @if($canOpen)
                    <a class="btn btn-primary" href="{{ route('naxas.restaurantops.shifts.open') }}">@lang('Naxas.RestaurantOps::default.shifts.open')</a>
                @endif
            @endif
        </div>
    </div>
</div>
