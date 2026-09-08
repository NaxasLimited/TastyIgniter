<style>
    .rops-kitchen-shell { padding: 14px; background: #f4f6f8; min-height: calc(100vh - 110px); }
    .rops-kitchen-topbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 12px; }
    .rops-kitchen-title h1 { margin: 0; font-size: 24px; font-weight: 800; color: #172033; }
    .rops-kitchen-title p { margin: 4px 0 0; color: #647084; }
    .rops-kitchen-actions { display: flex; align-items: center; gap: 8px; }
    .rops-kitchen-branch { border: 1px solid #cfd7e3; background: #fff; color: #172033; padding: 9px 12px; border-radius: 7px; font-weight: 700; }
    .rops-kitchen-refresh { border: 1px solid #2c99a8; background: #eafcff; color: #107282; padding: 9px 12px; border-radius: 7px; font-weight: 800; }
    .rops-kitchen-columns { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
    .rops-kitchen-column { min-height: 68vh; }
    .rops-kitchen-column-title { display: flex; justify-content: space-between; align-items: center; padding: 10px 12px; border-radius: 7px 7px 0 0; background: #25374d; color: #fff; font-weight: 800; }
    .rops-kitchen-count { min-width: 26px; text-align: center; border-radius: 999px; background: #fff; color: #25374d; padding: 2px 8px; }
    .rops-kitchen-stack { display: grid; gap: 10px; padding-top: 10px; }
    .rops-kitchen-ticket { background: #fff; border: 1px solid #d6dee9; border-radius: 8px; overflow: hidden; box-shadow: 0 8px 18px rgba(26, 37, 54, .06); }
    .rops-kitchen-ticket-head { display: grid; grid-template-columns: 1fr auto; gap: 10px; padding: 12px; border-bottom: 1px solid #e4e9f1; }
    .rops-kitchen-order { font-size: 17px; font-weight: 900; color: #172033; }
    .rops-kitchen-meta { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
    .rops-kitchen-pill { display: inline-flex; align-items: center; min-height: 24px; padding: 2px 8px; border-radius: 999px; background: #eef3f8; color: #38465a; font-size: 12px; font-weight: 800; }
    .rops-kitchen-service-dine_in { background: #e4f8f5; color: #08786f; }
    .rops-kitchen-service-collection { background: #fff4dd; color: #a86200; }
    .rops-kitchen-service-delivery { background: #eef0ff; color: #4855b8; }
    .rops-kitchen-age { text-align: right; color: #647084; font-size: 12px; font-weight: 700; }
    .rops-kitchen-items { display: grid; gap: 8px; padding: 12px; }
    .rops-kitchen-item { border: 1px solid #e4e9f1; border-radius: 7px; padding: 10px; background: #fbfcfe; }
    .rops-kitchen-item-main { display: grid; grid-template-columns: auto 1fr auto; gap: 10px; align-items: start; }
    .rops-kitchen-qty { min-width: 40px; border-radius: 6px; background: #172033; color: #fff; text-align: center; padding: 6px 8px; font-size: 18px; font-weight: 900; }
    .rops-kitchen-name { color: #172033; font-size: 16px; font-weight: 900; }
    .rops-kitchen-sub { color: #647084; font-size: 12px; margin-top: 3px; }
    .rops-kitchen-mods { margin: 8px 0 0 50px; padding: 0; list-style: none; color: #38465a; font-size: 13px; }
    .rops-kitchen-mods li { margin-top: 3px; }
    .rops-kitchen-note { margin: 8px 0 0 50px; border-left: 3px solid #ff6b2b; padding-left: 8px; color: #7a3d14; font-weight: 700; }
    .rops-kitchen-buttons { display: flex; gap: 6px; justify-content: flex-end; }
    .rops-kitchen-button { border: 0; border-radius: 6px; padding: 7px 10px; font-weight: 900; color: #fff; background: #2c99a8; }
    .rops-kitchen-button.ready { background: #15a765; }
    .rops-kitchen-button.complete { background: #25374d; }
    .rops-kitchen-empty { border: 1px dashed #c8d2df; border-radius: 8px; color: #6b7688; background: #fff; padding: 24px; text-align: center; font-weight: 700; }
    .rops-kitchen-alert { display: none; margin-bottom: 12px; border-radius: 7px; padding: 10px 12px; font-weight: 700; }
    .rops-kitchen-alert.show { display: block; }
    .rops-kitchen-alert.error { color: #a21414; background: #fff0f0; border: 1px solid #ffb6b6; }
    .rops-kitchen-alert.ok { color: #0c6d45; background: #e9fff4; border: 1px solid #aee8cd; }
    @media (max-width: 1100px) { .rops-kitchen-columns { grid-template-columns: 1fr; } .rops-kitchen-column { min-height: 0; } }
</style>

<div id="rops-kitchen-board"
     class="rops-kitchen-shell"
     data-feed-url="{{ route('naxas.restaurantops.kitchen.feed') }}"
     data-status-url-template="{{ route('naxas.restaurantops.kitchen.items.status', ['item' => '__ITEM__']) }}">
    <div class="rops-kitchen-topbar">
        <div class="rops-kitchen-title">
            <h1>Kitchen Workspace</h1>
            <p>Live kitchen tickets from POS orders. Completed items leave this board.</p>
        </div>
        <div class="rops-kitchen-actions">
            <span class="rops-kitchen-branch">{{ $activeLocation?->location_name ?? 'No branch selected' }}</span>
            <button class="rops-kitchen-refresh" type="button" data-kitchen-refresh>Refresh</button>
        </div>
    </div>

    <div class="rops-kitchen-alert" data-kitchen-alert></div>

    <div class="rops-kitchen-columns">
        @foreach(['kitchen_pending' => 'Pending', 'preparing' => 'Preparing', 'ready' => 'Ready'] as $status => $title)
            <section class="rops-kitchen-column" data-kitchen-column="{{ $status }}">
                <div class="rops-kitchen-column-title">
                    <span>{{ $title }}</span>
                    <span class="rops-kitchen-count" data-kitchen-count="{{ $status }}">0</span>
                </div>
                <div class="rops-kitchen-stack" data-kitchen-stack="{{ $status }}"></div>
            </section>
        @endforeach
    </div>
</div>

<script>
(function () {
    const root = document.getElementById('rops-kitchen-board');
    if (!root) return;

    const token = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
    const feedUrl = root.dataset.feedUrl;
    const statusTemplate = root.dataset.statusUrlTemplate;
    const stacks = {
        kitchen_pending: root.querySelector('[data-kitchen-stack="kitchen_pending"]'),
        preparing: root.querySelector('[data-kitchen-stack="preparing"]'),
        ready: root.querySelector('[data-kitchen-stack="ready"]'),
    };
    const counts = {
        kitchen_pending: root.querySelector('[data-kitchen-count="kitchen_pending"]'),
        preparing: root.querySelector('[data-kitchen-count="preparing"]'),
        ready: root.querySelector('[data-kitchen-count="ready"]'),
    };
    const alertBox = root.querySelector('[data-kitchen-alert]');

    let tickets = @json($tickets);

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
    }

    function show(message, type = 'ok') {
        alertBox.className = 'rops-kitchen-alert show ' + type;
        alertBox.textContent = message;
        setTimeout(() => alertBox.className = 'rops-kitchen-alert', 3000);
    }

    function itemButtons(item) {
        if (item.status === 'kitchen_pending') {
            return `<button class="rops-kitchen-button" data-kitchen-status="preparing" data-item-id="${item.id}">Accept</button>
                <button class="rops-kitchen-button ready" data-kitchen-status="ready" data-item-id="${item.id}">Ready</button>`;
        }

        if (item.status === 'preparing') {
            return `<button class="rops-kitchen-button ready" data-kitchen-status="ready" data-item-id="${item.id}">Ready</button>`;
        }

        return `<button class="rops-kitchen-button complete" data-kitchen-status="completed" data-item-id="${item.id}">Complete</button>`;
    }

    function itemHtml(item) {
        const modifiers = (item.modifiers || []).map(modifier => {
            const qty = Number(modifier.quantity || 1) > 1 ? `${modifier.quantity}x ` : '';
            return `<li>${escapeHtml(modifier.group)}: ${qty}${escapeHtml(modifier.name)}</li>`;
        }).join('');
        const note = item.note ? `<div class="rops-kitchen-note">Note: ${escapeHtml(item.note)}</div>` : '';
        const variant = item.variantName && item.variantName !== item.menuName ? `<div class="rops-kitchen-sub">${escapeHtml(item.variantName)}</div>` : '';

        return `<div class="rops-kitchen-item">
            <div class="rops-kitchen-item-main">
                <div class="rops-kitchen-qty">${escapeHtml(item.quantity)}x</div>
                <div>
                    <div class="rops-kitchen-name">${escapeHtml(item.name)}</div>
                    ${variant}
                    <div class="rops-kitchen-sub">${escapeHtml(item.statusLabel)}</div>
                </div>
                <div class="rops-kitchen-buttons">${itemButtons(item)}</div>
            </div>
            ${modifiers ? `<ul class="rops-kitchen-mods">${modifiers}</ul>` : ''}
            ${note}
        </div>`;
    }

    function ticketHtml(ticket, status) {
        const items = ticket.items.filter(item => item.status === status);
        if (!items.length) return '';
        const table = ticket.tableLabel ? `<span class="rops-kitchen-pill">Table ${escapeHtml(ticket.tableLabel)}</span>` : '';
        const waiter = ticket.waiterName ? `<span class="rops-kitchen-pill">Waiter ${escapeHtml(ticket.waiterName)}</span>` : '';
        const guests = ticket.guestCount ? `<span class="rops-kitchen-pill">${escapeHtml(ticket.guestCount)} guests</span>` : '';
        const guest = ticket.guestName ? `<span class="rops-kitchen-pill">${escapeHtml(ticket.guestName)}</span>` : '';
        const note = ticket.orderNote ? `<div class="rops-kitchen-note">Order note: ${escapeHtml(ticket.orderNote)}</div>` : '';

        return `<article class="rops-kitchen-ticket">
            <header class="rops-kitchen-ticket-head">
                <div>
                    <div class="rops-kitchen-order">POS #${escapeHtml(ticket.id)}${ticket.officialOrderId ? ` / Order #${escapeHtml(ticket.officialOrderId)}` : ''}</div>
                    <div class="rops-kitchen-meta">
                        <span class="rops-kitchen-pill rops-kitchen-service-${escapeHtml(ticket.serviceType)}">${escapeHtml(ticket.serviceLabel)}</span>
                        ${table}${waiter}${guests}${guest}
                    </div>
                </div>
                <div class="rops-kitchen-age">${escapeHtml(ticket.age)}<br>${escapeHtml(ticket.sentAt)}</div>
            </header>
            <div class="rops-kitchen-items">${items.map(itemHtml).join('')}${note}</div>
        </article>`;
    }

    function render() {
        Object.entries(stacks).forEach(([status, stack]) => {
            const html = tickets.map(ticket => ticketHtml(ticket, status)).filter(Boolean);
            stack.innerHTML = html.length ? html.join('') : '<div class="rops-kitchen-empty">No tickets.</div>';
            counts[status].textContent = html.length;
        });
    }

    async function refresh() {
        const response = await fetch(feedUrl, {headers: {'Accept': 'application/json'}});
        const payload = await response.json();
        if (!response.ok) throw new Error(payload.error?.message || 'Kitchen board could not refresh.');
        tickets = payload.data || [];
        render();
    }

    async function updateItem(itemId, status) {
        const response = await fetch(statusTemplate.replace('__ITEM__', itemId), {
            method: 'POST',
            headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token},
            body: JSON.stringify({status})
        });
        const payload = await response.json();
        if (!response.ok) throw new Error(payload.error?.message || 'Ticket could not be updated.');
        tickets = payload.data?.tickets || [];
        render();
        show('Kitchen ticket updated.');
    }

    root.addEventListener('click', event => {
        const button = event.target.closest('[data-kitchen-status]');
        if (!button) return;
        button.disabled = true;
        updateItem(button.dataset.itemId, button.dataset.kitchenStatus)
            .catch(error => show(error.message, 'error'))
            .finally(() => button.disabled = false);
    });

    root.querySelector('[data-kitchen-refresh]')?.addEventListener('click', () => refresh().catch(error => show(error.message, 'error')));
    render();
    setInterval(() => refresh().catch(() => {}), 15000);
})();
</script>
