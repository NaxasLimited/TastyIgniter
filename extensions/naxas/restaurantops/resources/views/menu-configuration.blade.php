@include('Naxas.RestaurantOps::_partials.ops-style')
<div class="container-fluid py-3 rops-shell">
    <div class="rops-toolbar">
        <div class="rops-title">
            <h1>{{ $menu->menu_name }}</h1>
            <p>#{{ $menu->getKey() }} · {{ currency_format($menu->menu_price) }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-light" href="{{ route('naxas.restaurantops.menu-operations.index') }}">Back</a>
            <a class="btn btn-primary" href="{{ admin_url('menus/edit/'.$menu->getKey()) }}">{{ lang('Naxas.RestaurantOps::default.menu_configuration.official_menu') }}</a>
        </div>
    </div>

    <div class="rops-grid rops-grid-4 mb-3">
        @foreach (['variants' => $variants->count(), 'modifier_groups' => $groups->count(), 'shared_options' => $sharedGroups->count(), 'combo' => $combo ? 1 : 0] as $section => $count)
            <div class="rops-stat">
                <span>{{ lang('Naxas.RestaurantOps::default.menu_configuration.'.$section) }}</span>
                <strong>{{ $count }}</strong>
            </div>
        @endforeach
    </div>

    <div class="row g-3">
        <section class="col-lg-7">
            <div class="rops-card">
                <div class="rops-card-header"><span>Variants</span></div>
                <div class="rops-card-body border-bottom">
                    <form id="rops-variant-form" class="row g-2 align-items-end">
                        <input type="hidden" name="id">
                        <input type="hidden" name="version">
                        <div class="col-md-3">
                            <label class="form-label">Name</label>
                            <input name="name" class="form-control" placeholder="250ml" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Code</label>
                            <input name="code" class="form-control" placeholder="250ml" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Price type</label>
                            <select name="price_mode" class="form-control">
                                <option value="absolute">Final price</option>
                                <option value="adjustment">Add-on price</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Price</label>
                            <input name="price_value" class="form-control" inputmode="decimal" placeholder="0.00" required>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">Order</label>
                            <input name="display_order" class="form-control" type="number" min="0" value="0">
                        </div>
                        <div class="col-md-2">
                            <label class="d-flex align-items-center gap-2 mb-2">
                                <input name="is_default" type="checkbox" value="1"> Default
                            </label>
                            <label class="d-flex align-items-center gap-2">
                                <input name="is_active" type="checkbox" value="1" checked> Active
                            </label>
                        </div>
                        <div class="col-12 d-flex gap-2">
                            <button class="btn btn-primary" type="submit">Save variant</button>
                            <button id="rops-variant-reset" class="btn btn-light" type="button">Clear</button>
                        </div>
                    </form>
                    <div id="rops-variant-alert" class="rops-toast mt-3"></div>
                </div>
                <div class="table-responsive">
                    <table class="rops-table">
                        <thead><tr><th>Name</th><th>Mode</th><th>Value</th><th>Status</th><th></th></tr></thead>
                        <tbody id="rops-variant-list">
                            @forelse($variants as $variant)
                                <tr>
                                    <td><strong>{{ $variant->name }}</strong><span class="d-block rops-muted">{{ $variant->code }}</span></td>
                                    <td>{{ str($variant->price_mode)->title() }}</td>
                                    <td>{{ currency_format($variant->price_value) }}</td>
                                    <td><span class="rops-pill {{ $variant->is_active ? 'is-open' : 'is-closed' }}">{{ $variant->is_default ? 'Default' : ($variant->is_active ? 'Active' : 'Hidden') }}</span></td>
                                    <td class="text-end">
                                        <button class="btn btn-light btn-sm" type="button"
                                            data-edit-variant
                                            data-id="{{ $variant->getKey() }}"
                                            data-version="{{ $variant->version }}"
                                            data-name="{{ e($variant->name) }}"
                                            data-code="{{ e($variant->code) }}"
                                            data-price-mode="{{ $variant->price_mode }}"
                                            data-price-value="{{ $variant->price_value }}"
                                            data-display-order="{{ $variant->display_order }}"
                                            data-is-default="{{ $variant->is_default ? 1 : 0 }}"
                                            data-is-active="{{ $variant->is_active ? 1 : 0 }}"
                                        >Edit</button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center rops-muted py-4">No operational variants.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
        <section class="col-lg-5">
            <div class="rops-card mb-3">
                <div class="rops-card-header">
                    <span>Options & Add-ons</span>
                    <button id="rops-sync-official-options" class="btn btn-light btn-sm" type="button">Sync official options</button>
                </div>
                <div class="rops-card-body">
                    <p class="rops-muted mb-3">Create required choices like sugar/ice, or optional add-ons. Stock qty is optional and uses the selected branch.</p>
                    <form id="rops-option-form" class="mb-3">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label">Group name</label>
                                <input name="name" class="form-control" placeholder="Sugar level / Add-ons" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Type</label>
                                <select name="display_type" class="form-control">
                                    <option value="radio">Single choice</option>
                                    <option value="checkbox">Multiple choice</option>
                                    <option value="quantity">Quantity add-on</option>
                                    <option value="select">Dropdown</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="d-flex align-items-center gap-2 mt-4">
                                    <input name="is_required" type="checkbox" value="1"> Required
                                </label>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Min</label>
                                <input name="min_selected" class="form-control" type="number" min="0" value="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Max</label>
                                <input name="max_selected" class="form-control" type="number" min="0" value="1">
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong>Choices</strong>
                                <button id="rops-option-add-row" class="btn btn-light btn-sm" type="button">+ Add choice</button>
                            </div>
                            <div id="rops-option-values" class="d-grid gap-2"></div>
                        </div>
                        <div class="d-flex gap-2 mt-3">
                            <button class="btn btn-primary" type="submit">Save option group</button>
                            <button id="rops-option-reset" class="btn btn-light" type="button">Clear</button>
                        </div>
                    </form>

                    <div class="border-top pt-3">
                        <strong class="d-block mb-2">Current option groups</strong>
                    @forelse($officialOptions as $option)
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <div>
                                <strong>{{ $option->option?->option_name }}</strong>
                                <span class="d-block rops-muted">
                                    {{ str($option->option?->display_type)->title() }}
                                    · {{ $option->is_required ? 'Required' : 'Optional' }}
                                    · {{ $option->menu_option_values->count() }} choice(s)
                                </span>
                            </div>
                            <span class="rops-muted">Order {{ $option->priority }}</span>
                        </div>
                    @empty
                        <div class="rops-muted">No attached modifier groups.</div>
                    @endforelse
                    </div>
                </div>
            </div>
            <div class="rops-card">
                <div class="rops-card-header"><span>POS Readiness</span></div>
                <div class="rops-card-body">
                    <div class="rops-grid rops-grid-2">
                        <div class="rops-stat"><span>Menu</span><strong>{{ $menu->menu_status ? 'Enabled' : 'Disabled' }}</strong></div>
                        <div class="rops-stat"><span>Default variant</span><strong>{{ $variants->firstWhere('is_default', true) ? 'Ready' : 'Missing' }}</strong></div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
<script>
(() => {
    const form = document.getElementById('rops-variant-form');
    const optionForm = document.getElementById('rops-option-form');
    const optionValues = document.getElementById('rops-option-values');
    const alertBox = document.getElementById('rops-variant-alert');
    const show = (message, ok = true) => {
        alertBox.textContent = message;
        alertBox.className = 'rops-toast mt-3 ' + (ok ? 'is-ok' : 'is-error');
        alertBox.style.display = 'block';
    };
    const reset = () => {
        form.reset();
        form.elements.id.value = '';
        form.elements.version.value = '';
        form.elements.price_mode.value = 'absolute';
        form.elements.display_order.value = '0';
        form.elements.is_active.checked = true;
    };
    const optionRow = (name = '', price = '0.00', stock = '', isDefault = false) => {
        const wrapper = document.createElement('div');
        wrapper.className = 'row g-2 align-items-end rops-option-value-row';
        wrapper.innerHTML = `
            <div class="col-md-4">
                <label class="form-label">Choice</label>
                <input data-option-value-name class="form-control" placeholder="250ml / No ice" value="${name}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Price</label>
                <input data-option-value-price class="form-control" inputmode="decimal" placeholder="0.00" value="${price}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Stock qty</label>
                <input data-option-value-stock class="form-control" type="number" min="0" placeholder="Optional" value="${stock}">
            </div>
            <div class="col-md-1">
                <label class="d-flex align-items-center gap-2 mb-2">
                    <input data-option-value-default type="checkbox" ${isDefault ? 'checked' : ''}> Default
                </label>
            </div>
            <div class="col-md-1">
                <button class="btn btn-light w-100" type="button" data-remove-option-row>×</button>
            </div>
        `;

        return wrapper;
    };
    const resetOptionForm = () => {
        optionForm.reset();
        optionForm.elements.display_type.value = 'radio';
        optionForm.elements.min_selected.value = '0';
        optionForm.elements.max_selected.value = '1';
        optionValues.innerHTML = '';
        optionValues.appendChild(optionRow('', '0.00', '', true));
        optionValues.appendChild(optionRow('', '0.00', '', false));
    };

    document.getElementById('rops-variant-reset')?.addEventListener('click', reset);
    document.getElementById('rops-option-reset')?.addEventListener('click', resetOptionForm);
    document.getElementById('rops-option-add-row')?.addEventListener('click', () => optionValues.appendChild(optionRow()));
    optionValues?.addEventListener('click', event => {
        if (event.target.closest('[data-remove-option-row]') && optionValues.children.length > 1) {
            event.target.closest('.rops-option-value-row').remove();
        }
    });
    optionForm?.addEventListener('submit', async event => {
        event.preventDefault();
        const values = [...optionValues.querySelectorAll('.rops-option-value-row')]
            .map(row => ({
                name: row.querySelector('[data-option-value-name]').value.trim(),
                price: row.querySelector('[data-option-value-price]').value.trim() || '0',
                stock_qty: row.querySelector('[data-option-value-stock]').value.trim() || null,
                is_default: row.querySelector('[data-option-value-default]').checked,
            }))
            .filter(row => row.name);
        if (!values.length) {
            show('Add at least one choice.', false);
            return;
        }
        try {
            const response = await fetch('{{ route('naxas.restaurantops.menu-operations.options.store', $menu) }}', {
                method: 'POST',
                headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                body: JSON.stringify({
                    name: optionForm.elements.name.value.trim(),
                    display_type: optionForm.elements.display_type.value,
                    is_required: optionForm.elements.is_required.checked,
                    min_selected: Number(optionForm.elements.min_selected.value || 0),
                    max_selected: Number(optionForm.elements.max_selected.value || 0),
                    priority: 0,
                    values,
                }),
            });
            const payload = await response.json();
            if (!response.ok) throw new Error(payload.message || payload.error?.message || 'Option group could not be saved.');
            show('Option group saved for POS. Refreshing...');
            window.location.reload();
        } catch (error) {
            show(error.message, false);
        }
    });
    document.getElementById('rops-sync-official-options')?.addEventListener('click', async () => {
        try {
            const response = await fetch('{{ route('naxas.restaurantops.menu-operations.official-options.sync', $menu) }}', {
                method: 'POST',
                headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                body: JSON.stringify({}),
            });
            const payload = await response.json();
            if (!response.ok) throw new Error(payload.message || payload.error?.message || 'Official options could not be synced.');
            show('Synced ' + payload.data.groups + ' group(s) and ' + payload.data.modifiers + ' option value(s). Refreshing...');
            window.location.reload();
        } catch (error) {
            show(error.message, false);
        }
    });
    document.querySelectorAll('[data-edit-variant]').forEach(button => button.addEventListener('click', () => {
        form.elements.id.value = button.dataset.id;
        form.elements.version.value = button.dataset.version;
        form.elements.name.value = button.dataset.name;
        form.elements.code.value = button.dataset.code;
        form.elements.price_mode.value = button.dataset.priceMode;
        form.elements.price_value.value = button.dataset.priceValue;
        form.elements.display_order.value = button.dataset.displayOrder || 0;
        form.elements.is_default.checked = button.dataset.isDefault === '1';
        form.elements.is_active.checked = button.dataset.isActive === '1';
        form.scrollIntoView({behavior: 'smooth', block: 'center'});
    }));

    form?.addEventListener('submit', async event => {
        event.preventDefault();
        const payload = {
            id: form.elements.id.value || null,
            version: form.elements.version.value || null,
            name: form.elements.name.value.trim(),
            code: form.elements.code.value.trim(),
            kitchen_name: '',
            price_mode: form.elements.price_mode.value,
            price_value: form.elements.price_value.value.trim(),
            is_default: form.elements.is_default.checked,
            is_active: form.elements.is_active.checked,
            display_order: Number(form.elements.display_order.value || 0),
        };
        try {
            const response = await fetch('{{ route('naxas.restaurantops.menu-operations.variants.store', $menu) }}', {
                method: 'POST',
                headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                body: JSON.stringify(payload),
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || data.error?.message || 'Variant could not be saved.');
            show('Variant saved. Refreshing list...');
            window.location.reload();
        } catch (error) {
            show(error.message, false);
        }
    });
    resetOptionForm();
})();
</script>
