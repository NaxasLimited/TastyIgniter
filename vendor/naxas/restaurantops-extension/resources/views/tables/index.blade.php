@include('Naxas.RestaurantOps::_partials.ops-style')

<div class="rops-shell">
    <div class="rops-toolbar">
        <div class="rops-title">
            <h1>Floors & Tables</h1>
            <p>{{ $activeLocation?->location_name ?? 'Select a branch before adding tables.' }}</p>
        </div>
        <a class="btn btn-outline-primary" href="{{ route('naxas.restaurantops.location-context.select', ['redirect' => request()->fullUrl()]) }}">Switch branch</a>
    </div>

    @unless($activeLocation)
        <div class="rops-card">
            <div class="rops-card-body">
                <strong>No branch selected.</strong>
                <p class="rops-muted mb-3">Tables belong to one branch, so choose a concrete branch first.</p>
                <a class="btn btn-primary" href="{{ route('naxas.restaurantops.location-context.select', ['redirect' => request()->fullUrl()]) }}">Choose branch</a>
            </div>
        </div>
    @else
        <div id="rops-table-alert" class="rops-toast"></div>

        <div class="rops-grid rops-grid-2 mb-3">
            <section class="rops-card">
                <div class="rops-card-header">
                    <span>Add floor</span>
                </div>
                <div class="rops-card-body">
                    <form id="rops-floor-form" class="rops-grid">
                        <input class="form-control" name="name" placeholder="Floor name, e.g. Ground Floor" required>
                        <input class="form-control" name="code" placeholder="Code, e.g. GF" required>
                        <input class="form-control" name="sort_order" type="number" min="0" value="0" placeholder="Sort order">
                        <button class="btn btn-primary" type="submit">Add floor</button>
                    </form>
                </div>
            </section>

            <section class="rops-card">
                <div class="rops-card-header">
                    <span>Add table</span>
                </div>
                <div class="rops-card-body">
                    <form id="rops-table-form" class="rops-grid">
                        <select class="form-control" name="floor_id" required {{ $floors->isEmpty() ? 'disabled' : '' }}>
                            <option value="">Select floor</option>
                            @foreach($floors as $floor)
                                <option value="{{ $floor->getKey() }}">{{ $floor->name }}</option>
                            @endforeach
                        </select>
                        <div class="rops-grid rops-grid-2">
                            <input class="form-control" name="name" placeholder="Table name, e.g. Table 1" required>
                            <input class="form-control" name="code" placeholder="Code, e.g. T1" required>
                        </div>
                        <div class="rops-grid rops-grid-2">
                            <input class="form-control" name="table_number" placeholder="Table no., e.g. 1">
                            <input class="form-control" name="capacity" type="number" min="1" value="2" placeholder="Capacity">
                        </div>
                        <button class="btn btn-success" type="submit" {{ $floors->isEmpty() ? 'disabled' : '' }}>Add table</button>
                        @if($floors->isEmpty())
                            <small class="rops-muted">Create a floor first, then add tables.</small>
                        @endif
                    </form>
                </div>
            </section>
        </div>

        <section class="rops-card mb-3">
            <div class="rops-card-header">
                <span>Floors</span>
                <span class="rops-pill">{{ $floors->count() }}</span>
            </div>
            <div class="table-responsive">
                <table class="rops-table">
                    <thead><tr><th>Name</th><th>Code</th><th>Active</th></tr></thead>
                    <tbody>
                    @forelse($floors as $floor)
                        <tr><td>{{ $floor->name }}</td><td>{{ $floor->code }}</td><td>{{ $floor->is_active ? 'Yes' : 'No' }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="rops-muted">No floors yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rops-card">
            <div class="rops-card-header">
                <span>Tables</span>
                <span class="rops-pill">{{ $tables->count() }}</span>
            </div>
            <div class="table-responsive">
                <table class="rops-table">
                    <thead><tr><th>Floor</th><th>Table</th><th>Capacity</th><th>Status</th><th>Position</th></tr></thead>
                    <tbody>
                    @forelse($tables as $table)
                        <tr>
                            <td>{{ $table->floor->name ?? '' }}</td>
                            <td><strong>{{ $table->name }}</strong><div class="rops-muted">{{ $table->code }}</div></td>
                            <td>{{ $table->capacity }}</td>
                            <td>{{ str($table->status)->replace('_', ' ')->title() }}</td>
                            <td>{{ $table->position_x }}, {{ $table->position_y }} ({{ $table->width }} x {{ $table->height }})</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="rops-muted">No tables yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endunless
</div>

@if($activeLocation)
<script>
(() => {
    const token = '{{ csrf_token() }}';
    const alertBox = document.getElementById('rops-table-alert');
    const show = (message, ok = true) => {
        alertBox.textContent = message;
        alertBox.className = 'rops-toast ' + (ok ? 'is-ok' : 'is-error');
        alertBox.style.display = 'block';
    };
    const post = async (url, data) => {
        const response = await fetch(url, {
            method: 'POST',
            headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token},
            body: JSON.stringify(data),
        });
        const payload = await response.json();
        if (!response.ok) throw new Error(payload.error?.message || 'Request failed.');
        return payload.data;
    };
    const formData = form => Object.fromEntries(new FormData(form).entries());
    document.getElementById('rops-floor-form')?.addEventListener('submit', async event => {
        event.preventDefault();
        try {
            await post('{{ route('naxas.restaurantops.floors.store') }}', formData(event.target));
            show('Floor added.');
            window.location.reload();
        } catch (error) { show(error.message, false); }
    });
    document.getElementById('rops-table-form')?.addEventListener('submit', async event => {
        event.preventDefault();
        try {
            const data = formData(event.target);
            if (!data.table_number) data.table_number = data.code;
            data.capacity = Number(data.capacity || 1);
            await post('{{ route('naxas.restaurantops.tables.store') }}', data);
            show('Table added.');
            window.location.reload();
        } catch (error) { show(error.message, false); }
    });
})();
</script>
@endif
