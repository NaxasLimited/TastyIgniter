@include('Naxas.RestaurantOps::_partials.ops-style')
@php($location = app(\Naxas\RestaurantOps\Contracts\LocationContextContract::class)->current())

<div class="rops-shell rops-pos-full">
    <div class="rops-pos-topbar">
        <div class="rops-pos-nav">
            <button id="rops-reset-order" class="rops-pos-create" type="button" {{ $shift ? '' : 'disabled' }}>+ Create</button>
            <div class="rops-pos-service">
                <input id="svc-dine" name="service_type" value="dine_in" type="radio" checked>
                <label for="svc-dine" data-open-service-orders="dine_in"><span class="rops-pos-icon">□</span>Dine in <span class="rops-count-badge">{{ $serviceCounts['dine_in'] ?? 0 }}</span></label>
            </div>
            <div class="rops-pos-service">
                <input id="svc-collect" name="service_type" value="collection" type="radio">
                <label for="svc-collect" data-open-service-orders="collection"><span class="rops-pos-icon">▣</span>Pickup <span class="rops-count-badge">{{ $serviceCounts['collection'] ?? 0 }}</span></label>
            </div>
            <div class="rops-pos-service">
                <input id="svc-delivery" name="service_type" value="delivery" type="radio">
                <label for="svc-delivery" data-open-service-orders="delivery"><span class="rops-pos-icon">◇</span>Delivery <span class="rops-count-badge">{{ $serviceCounts['delivery'] ?? 0 }}</span></label>
            </div>
        </div>

        <div class="rops-pos-brand">
            <span class="rops-pos-brand-mark">OE</span>
            <span>OTTOMAN EXPRESS</span>
        </div>

        <div class="rops-pos-actions">
            <a class="rops-pos-link" href="{{ route('naxas.restaurantops.location-context.select', ['redirect' => request()->fullUrl()]) }}">{{ $location?->location_name ?? 'Select branch' }}</a>
            <a class="rops-pos-link" href="{{ route('naxas.restaurantops.shifts.mine') }}">My Shift</a>
            <a class="rops-pos-link" href="{{ route('naxas.restaurantops.orders.active') }}">Orders</a>
            <span class="rops-pill {{ $shift ? 'is-open' : 'is-closed' }}">{{ $shift ? 'Shift #'.$shift->getKey().' open' : 'Shift required' }}</span>
            <button id="rops-fullscreen-toggle" class="rops-pos-link rops-pos-fullscreen-button" type="button">Exit full screen</button>
        </div>
    </div>

    <div id="rops-pos-alert" class="rops-toast mx-3 mt-3"></div>

    <div class="rops-pos-main">
        <section class="rops-pos-catalog">
            <div class="rops-pos-search">
                <input id="rops-menu-search" class="form-control" placeholder="Search by category or name">
            </div>

            <div class="rops-pos-browser">
                <aside class="rops-category-rail" id="rops-category-rail">
                    <button class="rops-category-button is-active" type="button" data-category-id="all">All Items</button>
                    @foreach($categories as $category)
                        <button class="rops-category-button" type="button" data-category-id="{{ $category->getKey() }}">
                            {{ $category->name }}
                        </button>
                    @endforeach
                    <button class="rops-category-button" type="button" data-category-id="uncategorized">Uncategorized</button>
                </aside>

                <div class="rops-pos-item-grid" id="rops-menu-grid">
                    @foreach($menus as $menu)
                        @php($variant = $menu->restaurant_ops_variants->firstWhere('is_default', true) ?: $menu->restaurant_ops_variants->first())
                        @php($ready = $menu->restaurant_ops_metadata && $variant)
                        @php($categoryIds = $menu->categories->pluck('category_id')->map(fn($id) => (string)$id)->implode(',') ?: 'uncategorized')
                        @php($thumb = rescue(fn() => $menu->getThumb(['width' => 460, 'height' => 240]), null, false))
                        <button
                            class="rops-pos-item rops-menu-tile"
                            type="button"
                            data-menu-name="{{ e(strtolower($menu->menu_name)) }}"
                            data-category-ids="{{ $categoryIds }}"
                            data-menu-id="{{ $menu->getKey() }}"
                            data-variant-id="{{ $variant?->getKey() }}"
                            {{ $shift && $ready ? '' : 'disabled' }}
                        >
                            <span class="rops-pos-photo">
                                @if($thumb)
                                    <img src="{{ $thumb }}" alt="">
                                @else
                                    <span>{{ strtoupper(substr($menu->menu_name, 0, 1)) }}</span>
                                @endif
                            </span>
                            <span class="rops-pos-item-body">
                                <span>
                                    <span class="rops-pos-item-title">{{ $menu->menu_name }}</span>
                                    <span class="d-block rops-muted">#{{ $menu->getKey() }}</span>
                                </span>
                                <span class="rops-pos-item-meta">
                                    <span class="rops-pos-item-price">{{ currency_format($menu->menu_price) }}</span>
                                    @if($menu->menu_options_count || $menu->restaurant_ops_variants->count() > 1)
                                        <span class="rops-pos-chip">Options</span>
                                    @elseif(!$ready)
                                        <span class="rops-pos-chip">Config needed</span>
                                    @endif
                                </span>
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>
        </section>

        <aside class="rops-pos-cart">
            <div class="rops-card-header">
                <span>Current order</span>
                <span id="rops-order-status" class="rops-pill">No order</span>
            </div>

            <div class="rops-pos-cart-tools">
                <button id="rops-open-customer" class="rops-pos-cart-tool" type="button">+ Customer info</button>
                <button id="rops-open-service" class="rops-pos-cart-tool rops-selected-tool" type="button">▣ <span id="rops-service-label">Dine in</span> ▾</button>
                <button id="rops-open-table" class="rops-pos-cart-tool" type="button">+ Table no.</button>
                <button id="rops-open-waiter" class="rops-pos-cart-tool" type="button">+ Waiter</button>
                <button id="rops-open-guests" class="rops-pos-cart-tool" type="button">Guests: 1</button>
            </div>

            <div class="rops-card-body border-bottom">
                <div class="row g-2">
                    <div class="col-md-6"><input id="rops-guest-name" class="form-control" placeholder="Guest name" {{ $shift ? '' : 'disabled' }}></div>
                    <div class="col-md-6"><input id="rops-guest-phone" class="form-control" placeholder="Phone" {{ $shift ? '' : 'disabled' }}></div>
                    <div class="col-12"><textarea id="rops-order-note" class="form-control" rows="2" placeholder="Order note" {{ $shift ? '' : 'disabled' }}></textarea></div>
                </div>
            </div>

            <div id="rops-order-items" class="rops-pos-cart-list">
                <div class="rops-muted text-center py-5">Create an order, then tap menu items.</div>
            </div>

            <div class="rops-pos-cart-summary">
                <div class="rops-pos-total-line"><span>Sub-total</span><span id="rops-order-subtotal">0.00</span></div>
                <div class="rops-pos-total-line"><strong>Total</strong><strong id="rops-order-total">0.00</strong></div>
                <div class="d-flex gap-3 mb-3">
                    <button id="rops-hold-order" class="btn btn-link text-danger p-0" type="button" disabled>+ Hold</button>
                    <button class="btn btn-link text-danger p-0" type="button" disabled>+ Discount</button>
                    <button id="rops-instructions-order" class="btn btn-link text-danger p-0" type="button" {{ $shift ? '' : 'disabled' }}>+ Instructions</button>
                </div>
                <div class="rops-pos-bottom-actions">
                    <button id="rops-payment-order" class="btn btn-info text-white" type="button" disabled>Pay</button>
                    <button id="rops-submit-order" class="btn btn-success" type="button" {{ $shift ? '' : 'disabled' }}>Create</button>
                </div>
                <div class="mt-2">
                    <button id="rops-kitchen-order" class="btn btn-warning w-100" type="button" disabled>Send to Kitchen</button>
                </div>
            </div>
        </aside>
    </div>
</div>

<div id="rops-service-orders-modal" class="rops-pos-modal" aria-hidden="true">
    <div class="rops-pos-modal-backdrop" data-close-service-orders></div>
    <div class="rops-pos-modal-panel rops-orders-panel" role="dialog" aria-modal="true">
        <div class="rops-pos-modal-header">
            <div>
                <div id="rops-service-orders-title" class="rops-pos-modal-title">Orders</div>
                <div id="rops-service-orders-subtitle" class="rops-muted"></div>
            </div>
            <button class="rops-pos-modal-close" type="button" data-close-service-orders>×</button>
        </div>
        <div id="rops-service-orders-list" class="rops-service-orders-list"></div>
    </div>
</div>

<div id="rops-service-modal" class="rops-pos-modal" aria-hidden="true">
    <div class="rops-pos-modal-backdrop" data-close-service></div>
    <div class="rops-pos-modal-panel rops-service-panel" role="dialog" aria-modal="true" aria-labelledby="rops-service-title">
        <div class="rops-pos-modal-header">
            <div id="rops-service-title" class="rops-pos-modal-title">Select order service type</div>
            <button class="rops-pos-modal-close" type="button" data-close-service>×</button>
        </div>
        <div class="rops-service-modal-body">
            <div class="rops-service-section-title">Dine in</div>
            <button class="rops-service-choice is-active" type="button" data-service-choice="dine_in">
                <span></span> Current dine in
            </button>
            <div class="rops-service-section-title">Pickup</div>
            <button class="rops-service-choice" type="button" data-service-choice="collection">
                <span></span> Current pickup
            </button>
            <div class="rops-service-section-title">Delivery</div>
            <button class="rops-service-choice" type="button" data-service-choice="delivery">
                <span></span> Current delivery
            </button>
        </div>
    </div>
</div>

<div id="rops-table-modal" class="rops-pos-modal" aria-hidden="true">
    <div class="rops-pos-modal-backdrop" data-close-table></div>
    <div class="rops-pos-modal-panel rops-select-panel" role="dialog" aria-modal="true">
        <div class="rops-pos-modal-header">
            <div class="rops-pos-modal-title">Select table</div>
            <button class="rops-pos-modal-close" type="button" data-close-table>×</button>
        </div>
        <div class="rops-select-list" id="rops-table-list"></div>
    </div>
</div>

<div id="rops-waiter-modal" class="rops-pos-modal" aria-hidden="true">
    <div class="rops-pos-modal-backdrop" data-close-waiter></div>
    <div class="rops-pos-modal-panel rops-select-panel" role="dialog" aria-modal="true">
        <div class="rops-pos-modal-header">
            <div class="rops-pos-modal-title">Select waiter</div>
            <button class="rops-pos-modal-close" type="button" data-close-waiter>×</button>
        </div>
        <div class="rops-select-list" id="rops-waiter-list"></div>
    </div>
</div>

<div id="rops-guests-modal" class="rops-pos-modal" aria-hidden="true">
    <div class="rops-pos-modal-backdrop" data-close-guests></div>
    <div class="rops-pos-modal-panel rops-guests-panel" role="dialog" aria-modal="true">
        <div class="rops-pos-modal-header">
            <div class="rops-pos-modal-title">Guest count</div>
            <button class="rops-pos-modal-close" type="button" data-close-guests>×</button>
        </div>
        <div class="rops-guests-body">
            <button id="rops-guests-minus" type="button">−</button>
            <strong id="rops-guests-value">1</strong>
            <button id="rops-guests-plus" type="button">+</button>
        </div>
        <div class="rops-customer-actions">
            <button type="button" data-close-guests>Close</button>
            <button id="rops-guests-ok" class="is-ready" type="button">OK ↵</button>
        </div>
    </div>
</div>

<div id="rops-customer-modal" class="rops-pos-modal" aria-hidden="true">
    <div class="rops-pos-modal-backdrop" data-close-customer></div>
    <div class="rops-pos-modal-panel rops-customer-panel" role="dialog" aria-modal="true" aria-labelledby="rops-customer-title">
        <div class="rops-pos-modal-header">
            <div id="rops-customer-title" class="rops-pos-modal-title">Add customer info</div>
            <button class="rops-pos-modal-close" type="button" data-close-customer>×</button>
        </div>
        <div class="rops-customer-body" id="rops-customer-entry">
            <label class="rops-field-label">Mobile number</label>
            <label class="rops-phone-entry">
                <span>+880</span>
                <input id="rops-customer-phone" inputmode="tel" autocomplete="tel" placeholder="Enter phone number">
            </label>
            <label class="rops-field-label">Customer name</label>
            <input id="rops-customer-name" class="rops-customer-text" autocomplete="name" placeholder="Enter customer name">
        </div>
        <div class="rops-customer-body rops-customer-summary" id="rops-customer-summary" hidden>
            <div class="rops-customer-summary-top">
                <div>
                    <strong id="rops-customer-summary-name">Customer</strong>
                    <span id="rops-customer-summary-phone"></span>
                </div>
                <button id="rops-customer-edit" type="button">✎ Edit</button>
            </div>
            <div class="rops-customer-summary-bottom">
                <button id="rops-customer-remove" type="button">Remove</button>
                <span><strong>0 Points</strong> <a href="#">Use</a></span>
            </div>
        </div>
        <div class="rops-customer-actions">
            <button type="button" data-close-customer>Close</button>
            <button id="rops-customer-next" type="button">Next ↵</button>
        </div>
    </div>
</div>

<div id="rops-option-modal" class="rops-pos-modal" aria-hidden="true">
    <div class="rops-pos-modal-backdrop" data-close-options></div>
    <div class="rops-pos-modal-panel" role="dialog" aria-modal="true" aria-labelledby="rops-option-title">
        <div class="rops-pos-modal-header">
            <div>
                <div id="rops-option-title" class="rops-pos-modal-title">Item options</div>
                <div id="rops-option-price" class="rops-muted"></div>
            </div>
            <button class="rops-pos-modal-close" type="button" data-close-options>×</button>
        </div>
        <div id="rops-option-groups" class="rops-pos-option-body"></div>
        <div class="rops-pos-option-footer">
            <button class="btn btn-light" type="button" data-close-options>Cancel</button>
            <button id="rops-option-add" class="btn btn-info text-white" type="button">Add to order</button>
        </div>
    </div>
</div>

<script>
(() => {
    const token = '{{ csrf_token() }}';
    const menuConfigurations = @json($menuConfigurations);
    const tableOptions = @json($tables);
    const waiterOptions = @json($waiters);
    let serviceOrders = @json($serviceOrders);
    let currentOrder = null;
    let activeCategory = 'all';
    let selectedService = 'dine_in';
    let selectedTableId = null;
    let selectedWaiterId = null;
    let selectedGuestCount = 1;
    let activeOptionTile = null;
    let activeVariantId = null;
    let kioskMode = true;
    const alertBox = document.getElementById('rops-pos-alert');
    const itemsBox = document.getElementById('rops-order-items');
    const statusBox = document.getElementById('rops-order-status');
    const subtotalBox = document.getElementById('rops-order-subtotal');
    const totalBox = document.getElementById('rops-order-total');
    const optionModal = document.getElementById('rops-option-modal');
    const optionTitle = document.getElementById('rops-option-title');
    const optionPrice = document.getElementById('rops-option-price');
    const optionGroups = document.getElementById('rops-option-groups');
    const fullscreenButton = document.getElementById('rops-fullscreen-toggle');
    const customerModal = document.getElementById('rops-customer-modal');
    const customerPhone = document.getElementById('rops-customer-phone');
    const customerName = document.getElementById('rops-customer-name');
    const customerNext = document.getElementById('rops-customer-next');
    const customerEntry = document.getElementById('rops-customer-entry');
    const customerSummary = document.getElementById('rops-customer-summary');
    const customerSummaryName = document.getElementById('rops-customer-summary-name');
    const customerSummaryPhone = document.getElementById('rops-customer-summary-phone');
    const serviceModal = document.getElementById('rops-service-modal');
    const serviceLabel = document.getElementById('rops-service-label');
    const serviceOrdersModal = document.getElementById('rops-service-orders-modal');
    const serviceOrdersTitle = document.getElementById('rops-service-orders-title');
    const serviceOrdersSubtitle = document.getElementById('rops-service-orders-subtitle');
    const serviceOrdersList = document.getElementById('rops-service-orders-list');
    const tableModal = document.getElementById('rops-table-modal');
    const waiterModal = document.getElementById('rops-waiter-modal');
    const tableButton = document.getElementById('rops-open-table');
    const waiterButton = document.getElementById('rops-open-waiter');
    const guestsButton = document.getElementById('rops-open-guests');
    const guestsModal = document.getElementById('rops-guests-modal');
    const guestsValue = document.getElementById('rops-guests-value');
    const buttons = {
        hold: document.getElementById('rops-hold-order'),
        submit: document.getElementById('rops-submit-order'),
        kitchen: document.getElementById('rops-kitchen-order'),
        payment: document.getElementById('rops-payment-order'),
        instructions: document.getElementById('rops-instructions-order'),
    };
    const key = () => window.crypto && crypto.randomUUID ? crypto.randomUUID() : String(Date.now()) + Math.random();
    const service = () => selectedService;
    const money = value => Number(value || 0).toFixed(2);
    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
    const tableLabel = id => tableOptions.find(table => Number(table.id) === Number(id))?.label || '+ Table no.';
    const waiterLabel = id => waiterOptions.find(waiter => Number(waiter.id) === Number(id))?.name || '+ Waiter';
    const renderAssignments = () => {
        const required = selectedService === 'dine_in' ? ' *' : '';
        tableButton.textContent = selectedTableId ? tableLabel(selectedTableId) : '+ Table no.' + required;
        waiterButton.textContent = selectedWaiterId ? waiterLabel(selectedWaiterId) : '+ Waiter' + required;
        guestsButton.textContent = 'Guests: ' + selectedGuestCount + required;
        tableButton.classList.toggle('rops-selected-tool', Boolean(selectedTableId));
        waiterButton.classList.toggle('rops-selected-tool', Boolean(selectedWaiterId));
        guestsButton.classList.toggle('rops-selected-tool', selectedGuestCount > 1);
    };
    const show = (message, ok = true) => {
        alertBox.textContent = message;
        alertBox.className = 'rops-toast mx-3 mt-3 ' + (ok ? 'is-ok' : 'is-error');
        alertBox.style.display = 'block';
    };
    const updateKioskButton = () => {
        fullscreenButton.textContent = kioskMode ? 'Exit full screen' : 'Full screen';
        document.body.classList.toggle('rops-pos-kiosk-mode', kioskMode);
    };
    const enterKioskMode = () => {
        kioskMode = true;
        updateKioskButton();
    };
    const exitKioskMode = () => {
        kioskMode = false;
        updateKioskButton();
    };
    const api = async (url, method = 'GET', body = null) => {
        const response = await fetch(url, {
            method,
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'Idempotency-Key': key(),
            },
            body: body ? JSON.stringify(body) : null,
        });
        const payload = await response.json();
        if (!response.ok) throw new Error(payload.error?.message || 'Request failed.');
        return payload.data;
    };
    const renderOrder = order => {
        currentOrder = order;
        selectedService = order.service_type || selectedService;
        renderService();
        statusBox.textContent = '#' + order.id + ' · ' + String(order.status).replaceAll('_', ' ');
        subtotalBox.textContent = money(order.subtotal || order.order_total);
        totalBox.textContent = money(order.order_total);
        document.getElementById('rops-guest-name').value = order.guest_name || '';
        document.getElementById('rops-guest-phone').value = order.guest_phone || '';
        document.getElementById('rops-order-note').value = order.order_note || '';
        selectedWaiterId = order.waiter_id ? Number(order.waiter_id) : selectedWaiterId;
        selectedTableId = order.table_id || order.assigned_table_id || tableOptions.find(table => Number(table.session_order_id) === Number(order.id))?.id || selectedTableId;
        selectedGuestCount = Math.max(1, Number(order.guest_count || order.session_guest_count || selectedGuestCount || 1));
        renderAssignments();
        const visibleItems = (order.items || []).filter(item => !['removed', 'voided'].includes(item.status));
        upsertServiceOrder(order, visibleItems);
        itemsBox.innerHTML = visibleItems.length
            ? visibleItems.map(item => {
                const name = escapeHtml(item.configuration_payload.menu_name || 'Menu item');
                const modifiers = (item.configuration_payload.modifiers || []).flatMap(group => group.modifiers || []);
                const modifierText = modifiers.length
                    ? `<span class="d-block rops-cart-modifiers">${modifiers.map(modifier => escapeHtml((modifier.quantity > 1 ? modifier.quantity + 'x ' : '') + modifier.name)).join(', ')}</span>`
                    : '';
                const noteText = item.item_note ? `<span class="d-block rops-cart-note">${escapeHtml(item.item_note)}</span>` : '';
                const editable = ['draft', 'held'].includes(order.status);
                return `<div class="rops-pos-cart-row">
                    <div class="rops-cart-main">
                        <span class="rops-cart-qty">${item.quantity}x</span>
                        <span class="rops-cart-copy"><strong>${name}</strong><span class="d-block rops-muted">Ready to send</span>${modifierText}${noteText}</span>
                    </div>
                    <div class="rops-cart-controls">
                        <button class="rops-cart-control" type="button" data-item-action="decrease" data-item-id="${item.id}" ${editable ? '' : 'disabled'}>-</button>
                        <span>${item.quantity}</span>
                        <button class="rops-cart-control" type="button" data-item-action="increase" data-item-id="${item.id}" ${editable ? '' : 'disabled'}>+</button>
                    </div>
                    <div class="rops-cart-price">
                        <strong>${money(item.line_total)}</strong>
                        <button class="rops-cart-link" type="button" data-item-action="note" data-item-id="${item.id}" ${editable ? '' : 'disabled'}>Note</button>
                        <button class="rops-cart-link is-danger" type="button" data-item-action="remove" data-item-id="${item.id}" ${editable ? '' : 'disabled'}>Remove</button>
                    </div>
                </div>`;
            }).join('')
            : '<div class="rops-muted text-center py-5">No items selected.</div>';
        buttons.hold.disabled = !['draft'].includes(order.status);
        buttons.submit.disabled = !(visibleItems.length && ['draft', 'held'].includes(order.status));
        buttons.kitchen.disabled = order.status !== 'active';
        buttons.payment.disabled = order.status !== 'kitchen_pending' && order.status !== 'active';
        buttons.instructions.disabled = !['draft', 'held'].includes(order.status);
    };
    const resetOrder = () => {
        currentOrder = null;
        statusBox.textContent = 'New ticket';
        subtotalBox.textContent = '0.00';
        totalBox.textContent = '0.00';
        itemsBox.innerHTML = '<div class="rops-muted text-center py-5">Tap menu items to start a new order.</div>';
        document.getElementById('rops-guest-name').value = '';
        document.getElementById('rops-guest-phone').value = '';
        document.getElementById('rops-order-note').value = '';
        selectedTableId = null;
        selectedWaiterId = null;
        selectedGuestCount = 1;
        renderAssignments();
        buttons.hold.disabled = true;
        buttons.submit.disabled = false;
        buttons.kitchen.disabled = true;
        buttons.payment.disabled = true;
        buttons.instructions.disabled = false;
        show('Ready for a new order.');
    };
    const orderBaseUrl = '{{ admin_url('restaurant-ops/pos/orders') }}';
    const refresh = async id => renderOrder(await api(orderBaseUrl + '/' + id));
    const serviceText = value => ({dine_in: 'Dine in', collection: 'Pickup', delivery: 'Delivery'}[value] || 'Dine in');
    const openStatuses = ['draft', 'held', 'active', 'kitchen_pending', 'payment_pending'];
    const updateServiceBadges = () => {
        ['dine_in', 'collection', 'delivery'].forEach(type => {
            const badge = document.querySelector(`[data-open-service-orders="${type}"] .rops-count-badge`);
            if (badge) badge.textContent = serviceOrders.filter(order => order.service_type === type).length;
        });
    };
    const upsertServiceOrder = (order, visibleItems = []) => {
        const existingIndex = serviceOrders.findIndex(row => Number(row.id) === Number(order.id));
        const existing = existingIndex >= 0 ? serviceOrders[existingIndex] : {};
        if (!openStatuses.includes(order.status)) {
            if (existingIndex >= 0) serviceOrders.splice(existingIndex, 1);
            updateServiceBadges();
            return;
        }

        const summary = {
            id: order.id,
            service_type: order.service_type,
            status: order.status,
            total: money(order.order_total),
            item_count: visibleItems.reduce((sum, item) => sum + Number(item.quantity || 0), 0),
            guest_name: order.guest_name,
            guest_phone: order.guest_phone,
            guest_count: order.guest_count,
            table_id: order.table_id || order.assigned_table_id || selectedTableId,
            waiter_id: order.waiter_id || selectedWaiterId,
            created_at: existing.created_at || '',
        };
        if (existingIndex >= 0) serviceOrders[existingIndex] = {...serviceOrders[existingIndex], ...summary};
        else serviceOrders.unshift(summary);
        updateServiceBadges();
    };
    const serviceCustomerText = order => order.guest_name || order.guest_phone || (Number(order.guest_count || 0) > 0 ? order.guest_count + ' guest(s)' : 'Walk-in');
    const closeServiceOrders = () => {
        serviceOrdersModal.classList.remove('is-open');
        serviceOrdersModal.setAttribute('aria-hidden', 'true');
    };
    const serviceOrderCard = order => {
        const status = String(order.status || '').replaceAll('_', ' ');
        const canKitchen = order.status === 'active';
        const canPay = ['active', 'kitchen_pending', 'payment_pending'].includes(order.status);
        return `<article class="rops-order-card">
            <div class="rops-order-card-main">
                <strong>#${order.id} · ${escapeHtml(status)}</strong>
                <div class="rops-order-card-meta">
                    <span>${escapeHtml(serviceCustomerText(order))}</span>
                    <span>${Number(order.item_count || 0)} item(s)</span>
                    <span>${money(order.total)}</span>
                    <span>${escapeHtml(order.created_at || '')}</span>
                </div>
            </div>
            <div class="rops-order-card-actions">
                <button class="is-primary" type="button" data-service-order-action="continue" data-order-id="${order.id}">Continue</button>
                ${canKitchen ? `<button type="button" data-service-order-action="kitchen" data-order-id="${order.id}">Send kitchen</button>` : ''}
                ${canPay ? `<button class="is-success" type="button" data-service-order-action="pay" data-order-id="${order.id}">Pay</button>` : ''}
            </div>
        </article>`;
    };
    const openServiceOrders = serviceType => {
        const rows = serviceOrders.filter(order => order.service_type === serviceType);
        serviceOrdersTitle.textContent = serviceText(serviceType) + ' orders';
        serviceOrdersSubtitle.textContent = rows.length + ' incomplete order(s)';
        serviceOrdersList.innerHTML = rows.length
            ? rows.map(serviceOrderCard).join('')
            : '<div class="rops-muted text-center py-5">No ' + serviceText(serviceType).toLowerCase() + ' orders waiting.</div>';
        serviceOrdersModal.classList.add('is-open');
        serviceOrdersModal.setAttribute('aria-hidden', 'false');
    };
    const renderService = () => {
        serviceLabel.textContent = serviceText(selectedService);
        document.querySelectorAll('[data-service-choice]').forEach(button => {
            const active = button.dataset.serviceChoice === selectedService;
            button.classList.toggle('is-active', active);
            button.innerHTML = `<span></span> Current ${serviceText(button.dataset.serviceChoice).toLowerCase()}`;
        });
        const topInput = document.querySelector(`input[name="service_type"][value="${selectedService}"]`);
        if (topInput) topInput.checked = true;
    };
    const openServiceModal = () => {
        renderService();
        serviceModal.classList.add('is-open');
        serviceModal.setAttribute('aria-hidden', 'false');
    };
    const closeServiceModal = () => {
        serviceModal.classList.remove('is-open');
        serviceModal.setAttribute('aria-hidden', 'true');
    };
    const selectService = value => {
        if (currentOrder && currentOrder.items && currentOrder.items.length) {
            show('Reset the current ticket before changing order type.', false);
            closeServiceModal();
            return;
        }
        selectedService = value;
        if (selectedService !== 'dine_in') {
            selectedTableId = null;
            selectedGuestCount = 1;
        }
        renderService();
        renderAssignments();
        resetOrder();
        closeServiceModal();
    };
    const saveAssignments = async () => {
        renderAssignments();
        if (!currentOrder) return;
        try {
            const order = await api(orderBaseUrl + '/' + currentOrder.id, 'PATCH', {
                version: currentOrder.version,
                table_id: selectedTableId,
                waiter_id: selectedWaiterId,
                guest_count: selectedGuestCount,
                guest_phone: document.getElementById('rops-guest-phone').value,
                guest_name: document.getElementById('rops-guest-name').value,
                order_note: document.getElementById('rops-order-note').value,
            });
            renderOrder(order);
            show('Order assignment updated.');
        } catch (error) { show(error.message, false); }
    };
    const renderTableChoices = () => {
        document.getElementById('rops-table-list').innerHTML = tableOptions.length
            ? tableOptions.map(table => {
                const occupiedByOther = table.session_order_id && (!currentOrder || Number(table.session_order_id) !== Number(currentOrder.id));
                return `<button class="rops-select-choice ${Number(selectedTableId) === Number(table.id) ? 'is-active' : ''}" type="button" data-table-id="${table.id}" ${occupiedByOther ? 'disabled' : ''}>
                    <span>${escapeHtml(table.label)}</span><small>${occupiedByOther ? 'Occupied' : table.status}</small>
                </button>`;
            }).join('')
            : '<div class="rops-muted text-center py-4">No active tables found.<br><a href="{{ route('naxas.restaurantops.tables.index') }}">Add tables</a></div>';
    };
    const renderWaiterChoices = () => {
        document.getElementById('rops-waiter-list').innerHTML = waiterOptions.length
            ? waiterOptions.map(waiter => `<button class="rops-select-choice ${Number(selectedWaiterId) === Number(waiter.id) ? 'is-active' : ''}" type="button" data-waiter-id="${waiter.id}">
                <span>${escapeHtml(waiter.name)}</span><small>Waiter</small>
            </button>`).join('')
            : '<div class="rops-muted text-center py-4">No waiter staff found.</div>';
    };
    const openTableModal = () => {
        if (selectedService !== 'dine_in') {
            show('Table can be assigned only for dine-in orders.', false);
            return;
        }
        renderTableChoices();
        tableModal.classList.add('is-open');
        tableModal.setAttribute('aria-hidden', 'false');
    };
    const closeTableModal = () => {
        tableModal.classList.remove('is-open');
        tableModal.setAttribute('aria-hidden', 'true');
    };
    const openWaiterModal = () => {
        renderWaiterChoices();
        waiterModal.classList.add('is-open');
        waiterModal.setAttribute('aria-hidden', 'false');
    };
    const closeWaiterModal = () => {
        waiterModal.classList.remove('is-open');
        waiterModal.setAttribute('aria-hidden', 'true');
    };
    const openGuestsModal = () => {
        guestsValue.textContent = selectedGuestCount;
        guestsModal.classList.add('is-open');
        guestsModal.setAttribute('aria-hidden', 'false');
    };
    const closeGuestsModal = () => {
        guestsModal.classList.remove('is-open');
        guestsModal.setAttribute('aria-hidden', 'true');
    };
    const setGuestCount = count => {
        selectedGuestCount = Math.max(1, Math.min(99, Number(count || 1)));
        guestsValue.textContent = selectedGuestCount;
        renderAssignments();
    };
    const saveGuestCount = async () => {
        closeGuestsModal();
        await saveAssignments();
    };
    const ensureDineInReady = () => {
        if (selectedService !== 'dine_in') return true;
        if (!selectedTableId) {
            show('Select a table before creating a dine-in order.', false);
            openTableModal();
            return false;
        }
        if (!selectedWaiterId) {
            show('Select a waiter before creating a dine-in order.', false);
            openWaiterModal();
            return false;
        }
        if (!selectedGuestCount || selectedGuestCount < 1) {
            show('Set guest count before creating a dine-in order.', false);
            openGuestsModal();
            return false;
        }
        return true;
    };
    const setCustomerStep = step => {
        const summary = step === 'summary';
        customerEntry.hidden = summary;
        customerSummary.hidden = !summary;
        document.getElementById('rops-customer-title').textContent = summary ? 'Customer info' : 'Add customer info';
        customerNext.textContent = summary ? 'OK ↵' : 'Next ↵';
    };
    const openCustomerModal = () => {
        const currentPhone = document.getElementById('rops-guest-phone').value.replace(/^\+?880/, '');
        const currentName = document.getElementById('rops-guest-name').value;
        customerPhone.value = currentPhone;
        customerName.value = currentName;
        setCustomerStep(currentPhone || currentName ? 'summary' : 'entry');
        customerSummaryName.textContent = currentName || 'Guest customer';
        customerSummaryPhone.textContent = currentPhone ? '+880' + currentPhone : '';
        customerModal.classList.add('is-open');
        customerModal.setAttribute('aria-hidden', 'false');
        setTimeout(() => (customerEntry.hidden ? customerNext : customerPhone).focus(), 50);
        customerNext.classList.toggle('is-ready', customerPhone.value.trim().length > 0);
    };
    const closeCustomerModal = () => {
        customerModal.classList.remove('is-open');
        customerModal.setAttribute('aria-hidden', 'true');
    };
    const normalizedCustomerPhone = () => {
        const local = customerPhone.value.replace(/[^\d]/g, '').replace(/^0+/, '');
        return local ? '+880' + local : '';
    };
    const previewCustomerInfo = () => {
        const phone = normalizedCustomerPhone();
        if (!phone) {
            customerPhone.focus();
            return;
        }
        customerSummaryName.textContent = customerName.value.trim() || 'Guest customer';
        customerSummaryPhone.textContent = phone;
        setCustomerStep('summary');
        customerNext.classList.add('is-ready');
    };
    const saveCustomerInfo = async () => {
        const phone = normalizedCustomerPhone();
        if (!phone) {
            customerPhone.focus();
            return;
        }
        document.getElementById('rops-guest-phone').value = phone;
        document.getElementById('rops-guest-name').value = customerName.value.trim();
        closeCustomerModal();
        if (!currentOrder) {
            show('Customer info added.');
            return;
        }
        try {
            const order = await api(orderBaseUrl + '/' + currentOrder.id, 'PATCH', {
                version: currentOrder.version,
                guest_phone: phone,
                guest_name: customerName.value.trim(),
                order_note: document.getElementById('rops-order-note').value,
            });
            renderOrder(order);
            show('Customer info updated.');
        } catch (error) { show(error.message, false); }
    };
    const createOrder = async () => {
        if (!ensureDineInReady()) return;
        try {
            const data = await api('{{ route('naxas.restaurantops.pos.orders.store') }}', 'POST', {
                service_type: service(),
                table_id: selectedTableId,
                waiter_id: selectedWaiterId,
                guest_count: selectedGuestCount,
                guest_name: document.getElementById('rops-guest-name').value,
                guest_phone: document.getElementById('rops-guest-phone').value,
                order_note: document.getElementById('rops-order-note').value,
                delivery_address: service() === 'delivery' ? {address_1: 'POS counter'} : null,
            });
            await refresh(data.id);
            if (!createOrder.silent) show('Order created.');
        } catch (error) { show(error.message, false); }
    };
    createOrder.silent = false;
    document.getElementById('rops-reset-order')?.addEventListener('click', resetOrder);
    document.querySelectorAll('[data-open-service-orders]').forEach(label => label.addEventListener('click', event => {
        event.preventDefault();
        openServiceOrders(label.dataset.openServiceOrders);
    }));
    document.querySelectorAll('[data-close-service-orders]').forEach(button => button.addEventListener('click', closeServiceOrders));
    serviceOrdersList?.addEventListener('click', async event => {
        const button = event.target.closest('[data-service-order-action]');
        if (!button) return;
        const id = button.dataset.orderId;
        try {
            if (button.dataset.serviceOrderAction === 'continue') {
                await refresh(id);
                closeServiceOrders();
                show('Order loaded.');
                return;
            }

            let order = await api(orderBaseUrl + '/' + id);
            if (button.dataset.serviceOrderAction === 'kitchen') {
                order = await api(orderBaseUrl + '/' + id + '/request-kitchen', 'POST', {version: order.version});
                renderOrder(order);
                closeServiceOrders();
                show('Sent to kitchen.');
                return;
            }

            if (button.dataset.serviceOrderAction === 'pay') {
                if (order.status !== 'payment_pending') {
                    order = await api(orderBaseUrl + '/' + id + '/lock-payment', 'POST', {version: order.version});
                }
                renderOrder(order);
                window.location.href = orderBaseUrl + '/' + order.id + '/payment';
            }
        } catch (error) { show(error.message, false); }
    });
    document.getElementById('rops-open-service')?.addEventListener('click', openServiceModal);
    document.querySelectorAll('[data-close-service]').forEach(button => button.addEventListener('click', closeServiceModal));
    document.querySelectorAll('[data-service-choice]').forEach(button => button.addEventListener('click', () => selectService(button.dataset.serviceChoice)));
    document.getElementById('rops-open-table')?.addEventListener('click', openTableModal);
    document.querySelectorAll('[data-close-table]').forEach(button => button.addEventListener('click', closeTableModal));
    document.getElementById('rops-table-list')?.addEventListener('click', async event => {
        const button = event.target.closest('[data-table-id]');
        if (!button || button.disabled) return;
        selectedTableId = Number(button.dataset.tableId);
        closeTableModal();
        await saveAssignments();
    });
    document.getElementById('rops-open-waiter')?.addEventListener('click', openWaiterModal);
    document.querySelectorAll('[data-close-waiter]').forEach(button => button.addEventListener('click', closeWaiterModal));
    document.getElementById('rops-waiter-list')?.addEventListener('click', async event => {
        const button = event.target.closest('[data-waiter-id]');
        if (!button) return;
        selectedWaiterId = Number(button.dataset.waiterId);
        closeWaiterModal();
        await saveAssignments();
    });
    document.getElementById('rops-open-guests')?.addEventListener('click', openGuestsModal);
    document.querySelectorAll('[data-close-guests]').forEach(button => button.addEventListener('click', closeGuestsModal));
    document.getElementById('rops-guests-minus')?.addEventListener('click', () => setGuestCount(selectedGuestCount - 1));
    document.getElementById('rops-guests-plus')?.addEventListener('click', () => setGuestCount(selectedGuestCount + 1));
    document.getElementById('rops-guests-ok')?.addEventListener('click', saveGuestCount);
    document.getElementById('rops-open-customer')?.addEventListener('click', openCustomerModal);
    document.querySelectorAll('[data-close-customer]').forEach(button => button.addEventListener('click', closeCustomerModal));
    customerPhone?.addEventListener('input', () => customerNext.classList.toggle('is-ready', customerPhone.value.trim().length > 0));
    customerPhone?.addEventListener('keydown', event => {
        if (event.key === 'Enter') previewCustomerInfo();
    });
    customerName?.addEventListener('keydown', event => {
        if (event.key === 'Enter') previewCustomerInfo();
    });
    document.getElementById('rops-customer-edit')?.addEventListener('click', () => {
        setCustomerStep('entry');
        setTimeout(() => customerName.focus(), 50);
    });
    document.getElementById('rops-customer-remove')?.addEventListener('click', () => {
        customerPhone.value = '';
        customerName.value = '';
        document.getElementById('rops-guest-phone').value = '';
        document.getElementById('rops-guest-name').value = '';
        setCustomerStep('entry');
        customerNext.classList.remove('is-ready');
        customerPhone.focus();
    });
    customerNext?.addEventListener('click', () => {
        if (customerSummary.hidden) previewCustomerInfo();
        else saveCustomerInfo();
    });

    const buildSelections = () => {
        const selections = [];
        const groups = optionGroups.querySelectorAll('[data-group-id]');
        for (const group of groups) {
            const groupId = Number(group.dataset.groupId);
            const required = group.dataset.required === '1';
            const min = Number(group.dataset.min || 0);
            const max = Number(group.dataset.max || 0);
            const selected = [...group.querySelectorAll('input[data-modifier-id]:checked')].map(input => {
                const quantityInput = group.querySelector(`[data-quantity-for="${input.dataset.modifierId}"]`);
                return {modifier_id: Number(input.dataset.modifierId), quantity: Math.max(1, Number(quantityInput?.value || 1))};
            });
            if (required && selected.length < Math.max(1, min)) {
                throw new Error('Please select ' + group.dataset.groupName + '.');
            }
            if (max && selected.length > max) {
                throw new Error(group.dataset.groupName + ' allows maximum ' + max + ' option(s).');
            }
            if (selected.length) selections.push({group_id: groupId, modifiers: selected});
        }
        return selections;
    };

    const selectedVariantId = () => {
        const checked = optionGroups.querySelector('input[name="rops-variant"]:checked');

        return checked ? Number(checked.value) : activeVariantId;
    };

    const closeOptions = () => {
        optionModal.classList.remove('is-open');
        optionModal.setAttribute('aria-hidden', 'true');
        activeOptionTile = null;
        activeVariantId = null;
    };

    const optionControl = (group, modifier) => {
        const inputType = group.type === 'single' ? 'radio' : 'checkbox';
        const inputName = 'rops-option-' + group.id;
        const price = modifier.price ? ' +' + money(modifier.price) : '';
        const quantity = modifier.allowQuantity || group.allowQuantity
            ? `<input class="rops-option-qty" type="number" min="1" max="${modifier.maxQuantity}" value="1" data-quantity-for="${modifier.id}">`
            : '';
        return `<label class="rops-option-choice">
            <input type="${inputType}" name="${inputName}" data-modifier-id="${modifier.id}" ${modifier.isDefault ? 'checked' : ''}>
            <span><strong>${escapeHtml(modifier.name)}</strong><small>${price}</small></span>
            ${quantity}
        </label>`;
    };

    const openOptions = tile => {
        const menuId = Number(tile.dataset.menuId);
        const config = menuConfigurations[menuId];
        const variants = config?.variants || [];
        const groups = config?.groups || [];
        if (!variants.length && !groups.length) return false;
        if (variants.length <= 1 && !groups.length) return false;
        activeOptionTile = tile;
        activeVariantId = Number((variants.find(variant => variant.isDefault) || variants[0] || {}).id || tile.dataset.variantId);
        optionTitle.textContent = tile.querySelector('.rops-pos-item-title').textContent;
        optionPrice.textContent = tile.querySelector('.rops-pos-item-price').textContent;
        const variantMarkup = variants.length > 1 ? `
            <section class="rops-option-group" data-variant-section="1">
                <div class="rops-option-group-head">
                    <strong>Size / Variant</strong>
                    <span>Required</span>
                </div>
                <div class="rops-option-choices">
                    ${variants.map(variant => {
                        const priceText = variant.priceMode === 'absolute'
                            ? money(variant.priceValue)
                            : (Number(variant.priceValue) ? (Number(variant.priceValue) > 0 ? '+' : '') + money(variant.priceValue) : 'Base');
                        return `<label class="rops-option-choice">
                            <input type="radio" name="rops-variant" value="${variant.id}" ${Number(variant.id) === Number(activeVariantId) ? 'checked' : ''}>
                            <span><strong>${escapeHtml(variant.name)}</strong><small>${escapeHtml(priceText)}</small></span>
                        </label>`;
                    }).join('')}
                </div>
            </section>` : '';
        optionGroups.innerHTML = variantMarkup + groups.map(group => `
            <section class="rops-option-group" data-group-id="${group.id}" data-group-name="${escapeHtml(group.name)}" data-required="${group.required ? 1 : 0}" data-min="${group.min}" data-max="${group.max}">
                <div class="rops-option-group-head">
                    <strong>${escapeHtml(group.name)}</strong>
                    <span>${group.required ? 'Required' : 'Optional'}${group.max ? ' · max ' + group.max : ''}</span>
                </div>
                <div class="rops-option-choices">${group.modifiers.map(modifier => optionControl(group, modifier)).join('')}</div>
            </section>
        `).join('');
        optionModal.classList.add('is-open');
        optionModal.setAttribute('aria-hidden', 'false');
        return true;
    };

    const addMenuItem = async (tile, modifierSelections = [], variantId = null) => {
        if (!currentOrder) {
            if (!ensureDineInReady()) return;
            try {
                createOrder.silent = true;
                await createOrder();
            } finally {
                createOrder.silent = false;
            }
        }
        if (!currentOrder) return;
        await api(orderBaseUrl + '/' + currentOrder.id + '/items', 'POST', {
            version: currentOrder.version,
            menu_id: Number(tile.dataset.menuId),
            variant_id: Number(variantId || tile.dataset.variantId),
            quantity: 1,
            modifier_selections: modifierSelections,
            combo_selections: [],
            item_note: '',
        });
        await refresh(currentOrder.id);
        show(tile.querySelector('.rops-pos-item-title').textContent + ' added.');
    };

    document.querySelectorAll('.rops-menu-tile').forEach(tile => tile.addEventListener('click', async () => {
        try {
            if (openOptions(tile)) return;
            await addMenuItem(tile);
        } catch (error) { show(error.message, false); }
    }));
    const action = async (path, label) => {
        if (!currentOrder) return;
        try {
            const data = await api(orderBaseUrl + '/' + currentOrder.id + path, 'POST', {version: currentOrder.version});
            renderOrder(data);
            show(label);
        } catch (error) { show(error.message, false); }
    };
    buttons.hold?.addEventListener('click', () => action('/hold', 'Order held.'));
    buttons.submit?.addEventListener('click', async () => {
        if (!ensureDineInReady()) return;
        if (!currentOrder) {
            await createOrder();
            return;
        }
        await action('/confirm', 'Order created.');
    });
    buttons.kitchen?.addEventListener('click', () => {
        if (!ensureDineInReady()) return;
        action('/request-kitchen', 'Sent to kitchen.');
    });
    buttons.payment?.addEventListener('click', async () => {
        if (!currentOrder) return;
        if (!ensureDineInReady()) return;
        try {
            const order = currentOrder.status === 'payment_pending'
                ? currentOrder
                : await api(orderBaseUrl + '/' + currentOrder.id + '/lock-payment', 'POST', {version: currentOrder.version});
            renderOrder(order);
            window.location.href = orderBaseUrl + '/' + order.id + '/payment';
        } catch (error) { show(error.message, false); }
    });
    buttons.instructions?.addEventListener('click', () => {
        document.getElementById('rops-order-note').focus();
        show('Add order instructions in the note field.');
    });
    const updateCartItem = async (item, data) => {
        if (!currentOrder) return;
        const order = await api(orderBaseUrl + '/' + currentOrder.id + '/items/' + item.id, 'PATCH', {version: currentOrder.version, ...data});
        renderOrder(order);
    };
    const removeCartItem = async item => {
        if (!currentOrder) return;
        const order = await api(orderBaseUrl + '/' + currentOrder.id + '/items/' + item.id, 'DELETE', {version: currentOrder.version, reason: 'Removed from POS cart'});
        renderOrder(order);
    };
    itemsBox.addEventListener('click', async event => {
        const button = event.target.closest('[data-item-action]');
        if (!button || !currentOrder) return;
        const item = (currentOrder.items || []).find(row => Number(row.id) === Number(button.dataset.itemId));
        if (!item) return;
        try {
            if (button.dataset.itemAction === 'increase') {
                await updateCartItem(item, {quantity: Number(item.quantity) + 1, item_note: item.item_note || ''});
                show('Quantity updated.');
            } else if (button.dataset.itemAction === 'decrease') {
                if (Number(item.quantity) <= 1) {
                    await removeCartItem(item);
                    show('Item removed.');
                } else {
                    await updateCartItem(item, {quantity: Number(item.quantity) - 1, item_note: item.item_note || ''});
                    show('Quantity updated.');
                }
            } else if (button.dataset.itemAction === 'note') {
                const note = window.prompt('Item note', item.item_note || '');
                if (note !== null) {
                    await updateCartItem(item, {quantity: Number(item.quantity), item_note: note});
                    show('Item note updated.');
                }
            } else if (button.dataset.itemAction === 'remove') {
                if (window.confirm('Remove this item from the cart?')) {
                    await removeCartItem(item);
                    show('Item removed.');
                }
            }
        } catch (error) { show(error.message, false); }
    });
    document.querySelectorAll('[data-close-options]').forEach(button => button.addEventListener('click', closeOptions));
    document.getElementById('rops-option-add')?.addEventListener('click', async () => {
        if (!activeOptionTile) return;
        try {
            await addMenuItem(activeOptionTile, buildSelections(), selectedVariantId());
            closeOptions();
        } catch (error) { show(error.message, false); }
    });

    document.querySelectorAll('input[name="service_type"]').forEach(input => input.addEventListener('change', event => {
        if (currentOrder && currentOrder.items && currentOrder.items.length) {
            event.target.checked = false;
            document.querySelector(`input[name="service_type"][value="${selectedService}"]`).checked = true;
            show('Reset the current ticket before changing order type.', false);
            return;
        }
        selectService(event.target.value);
    }));

    const applyFilters = () => {
        const term = document.getElementById('rops-menu-search').value.trim().toLowerCase();
        document.querySelectorAll('.rops-menu-tile').forEach(tile => {
            const categories = (tile.dataset.categoryIds || '').split(',');
            const categoryMatch = activeCategory === 'all' || categories.includes(activeCategory);
            const textMatch = !term || tile.dataset.menuName.includes(term);
            tile.style.display = categoryMatch && textMatch ? '' : 'none';
        });
    };
    document.querySelectorAll('.rops-category-button').forEach(button => button.addEventListener('click', () => {
        activeCategory = button.dataset.categoryId;
        document.querySelectorAll('.rops-category-button').forEach(item => item.classList.toggle('is-active', item === button));
        applyFilters();
    }));
    document.getElementById('rops-menu-search')?.addEventListener('input', applyFilters);
    fullscreenButton?.addEventListener('click', () => {
        if (kioskMode) exitKioskMode();
        else enterKioskMode();
    });
    renderService();
    renderAssignments();
    updateKioskButton();
})();
</script>
