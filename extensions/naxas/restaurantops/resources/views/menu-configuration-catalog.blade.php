@include('Naxas.RestaurantOps::_partials.ops-style')
<div class="container-fluid py-3 rops-shell">
    <div class="rops-toolbar">
        <div class="rops-title">
            <h1>{{ lang('Naxas.RestaurantOps::default.menu_configuration.catalog_title') }}</h1>
            <p>{{ $menus->total() }} menu items</p>
        </div>
        <a class="btn btn-light" href="{{ admin_url('menus') }}">Official Menu Items</a>
    </div>

    <div class="rops-card">
        <div class="rops-card-header">
            <span>Menu Items</span>
            <input id="rops-menu-filter" class="form-control" style="max-width: 340px;" placeholder="Search item">
        </div>
        <div class="table-responsive">
            <table class="rops-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Operations</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="rops-menu-list">
                    @foreach ($menus as $menu)
                        @php($hasMetadata = (bool) $menu->restaurant_ops_metadata)
                        @php($variantCount = $menu->restaurant_ops_variants->count())
                        <tr data-menu-name="{{ e(strtolower($menu->menu_name)) }}">
                            <td><strong>{{ $menu->menu_name }}</strong><span class="d-block rops-muted">#{{ $menu->getKey() }}</span></td>
                            <td>{{ currency_format($menu->menu_price) }}</td>
                            <td><span class="rops-pill {{ $menu->menu_status ? 'is-open' : 'is-closed' }}">{{ $menu->menu_status ? 'Enabled' : 'Disabled' }}</span></td>
                            <td>
                                <span class="rops-pill {{ $hasMetadata ? 'is-open' : '' }}">{{ $hasMetadata ? 'Configured' : 'Base only' }}</span>
                                <span class="rops-pill">{{ $variantCount }} variants</span>
                            </td>
                            <td class="text-end"><a class="btn btn-primary btn-sm" href="{{ route('naxas.restaurantops.menu-operations.show', $menu) }}">Open</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $menus->links() }}</div>
</div>
<script>
document.getElementById('rops-menu-filter')?.addEventListener('input', event => {
    const term = event.target.value.trim().toLowerCase();
    document.querySelectorAll('#rops-menu-list tr').forEach(row => row.style.display = row.dataset.menuName.includes(term) ? '' : 'none');
});
</script>
