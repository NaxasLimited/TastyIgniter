@include('Naxas.RestaurantOps::_partials.ops-style')
<div class="container-fluid py-3 rops-shell">
    <div class="rops-toolbar">
        <div class="rops-title"><h1>@lang('Naxas.RestaurantOps::default.shifts.open')</h1><p>{{ now()->format('l, M j, H:i') }}</p></div>
    </div>
    @if($activeShift)
        <div class="rops-card rops-card-body">
            <span class="rops-pill is-open">@lang('Naxas.RestaurantOps::default.shifts.already_open') #{{ $activeShift->id }}</span>
            <div class="mt-3"><a class="btn btn-primary" href="{{ route('naxas.restaurantops.shifts.show', $activeShift) }}">View Shift</a></div>
        </div>
    @else
        <form class="rops-card rops-card-body" method="post" action="{{ route('naxas.restaurantops.shifts.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">@lang('Naxas.RestaurantOps::default.shifts.opening_cash')</label><input required pattern="\d+(\.\d{1,4})?" name="opening_cash" class="form-control form-control-lg" inputmode="decimal" autofocus></div>
                <div class="col-md-6"><label class="form-label">@lang('Naxas.RestaurantOps::default.shifts.terminal')</label><input name="terminal_code" maxlength="64" class="form-control form-control-lg"></div>
                <div class="col-12"><label class="form-label">@lang('Naxas.RestaurantOps::default.shifts.note')</label><textarea name="opening_note" class="form-control" rows="3"></textarea></div>
                <div class="col-12"><button class="btn btn-primary btn-lg">@lang('Naxas.RestaurantOps::default.shifts.open')</button></div>
            </div>
        </form>
    @endif
</div>
