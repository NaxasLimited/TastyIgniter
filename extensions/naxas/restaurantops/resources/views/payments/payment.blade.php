@include('Naxas.RestaurantOps::_partials.ops-style')
@php($items = $order->items->whereNotIn('status', ['removed', 'voided'])->values())
@php($payable = number_format((float) $order->outstanding_total, 2, '.', ''))
@php($providerOptions = $providers ?: ['bkash' => 'bKash', 'nagad' => 'Nagad'])

<div
    class="rops-payment-screen"
    id="rops-payment"
    data-store="{{ route('naxas.restaurantops.pos.payments.store', $order) }}"
    data-receipt="{{ route('naxas.restaurantops.pos.receipt.show', $order) }}"
    data-pos="{{ route('naxas.restaurantops.pos') }}"
    data-version="{{ $order->version }}"
    data-total="{{ $payable }}"
>
    <header class="rops-payment-topbar">
        <div class="rops-payment-title">
            <strong>Payment</strong>
            <span>#{{ $order->getKey() }} · {{ str($order->service_type)->replace('_', ' ') }}</span>
        </div>
        <a class="rops-payment-close" href="{{ route('naxas.restaurantops.pos') }}">×</a>
    </header>

    <div id="payment-errors" class="rops-payment-alert is-error"></div>
    <div id="payment-success" class="rops-payment-alert is-ok"></div>

    <main class="rops-payment-grid">
        <section class="rops-payment-order">
            <div class="rops-payment-customer">
                <div class="rops-payment-avatar"></div>
                <span>{{ $order->guest_name ?: 'No customer info' }}</span>
                <button type="button">Add</button>
            </div>

            <div class="rops-payment-items">
                @foreach($items as $item)
                    <div class="rops-payment-item">
                        <strong>{{ $item->quantity }}x</strong>
                        <span>{{ $item->configuration_payload['menu_name'] ?? 'Menu item' }}</span>
                        <b>{{ number_format((float) $item->line_total, 2) }}</b>
                    </div>
                @endforeach
            </div>

            <div class="rops-payment-order-total">
                <span>Sub-total</span>
                <strong id="summary-subtotal">{{ number_format((float) $order->subtotal, 2) }}</strong>
            </div>
            <div class="rops-payment-order-total rops-discount-row" id="summary-discount-row">
                <span>Discount</span>
                <strong id="summary-discount">-0.00</strong>
            </div>
            <div class="rops-payment-grand-total">
                <span>Total</span>
                <strong id="summary-total">£{{ $payable }}</strong>
            </div>
        </section>

        <section class="rops-payment-calculator">
            <div class="rops-payment-balance">
                <strong>Balance due: <span id="balance-due">£{{ $payable }}</span></strong>
                <button type="button" disabled>Split</button>
            </div>

            <div class="rops-payment-quick">
                <button type="button" data-quick="1000">1,000</button>
                <button type="button" data-quick="500">500</button>
                <button type="button" data-quick="{{ $payable }}">All</button>
            </div>

            <div class="rops-payment-entry">
                <label>To pay</label>
                <div id="to-pay">£{{ $payable }}</div>
                <label>Amount tendered</label>
                <input id="tender-amount" inputmode="decimal" value="{{ $payable }}" autocomplete="off">
            </div>

            <div class="rops-payment-keypad">
                <button type="button" data-key="1">1</button>
                <button type="button" data-key="2">2</button>
                <button type="button" data-key="3">3</button>
                <button type="button" class="is-tall" data-key="back">⌫</button>
                <button type="button" data-key="4">4</button>
                <button type="button" data-key="5">5</button>
                <button type="button" data-key="6">6</button>
                <button type="button" data-key="7">7</button>
                <button type="button" data-key="8">8</button>
                <button type="button" data-key="9">9</button>
                <button type="button" class="is-tall is-clear" data-key="clear">C</button>
                <button type="button" data-key=".">.</button>
                <button type="button" data-key="0">0</button>
                <button type="button" data-key="00">00</button>
            </div>
            <div class="rops-payment-tools">
                <button type="button" id="open-discount">Discount</button>
            </div>
        </section>

        <section class="rops-payment-methods">
            <button class="rops-payment-method is-active" type="button" data-method="cash">
                <span>▣</span><strong>Cash</strong>
            </button>
            <button class="rops-payment-method" type="button" data-method="mobile" data-provider="bkash">
                <span>◆</span><strong>bKash</strong>
            </button>
            <button class="rops-payment-method" type="button" data-method="mobile" data-provider="nagad">
                <span>●</span><strong>nagad</strong>
            </button>
            <button class="rops-payment-method" type="button" data-method="card">
                <span>▤</span><strong>Debit / credit cards</strong>
            </button>
            @foreach($providerOptions as $code => $label)
                @continue(in_array($code, ['bkash', 'nagad'], true))
                <button class="rops-payment-method" type="button" data-method="mobile" data-provider="{{ $code }}">
                    <span>◆</span><strong>{{ $label }}</strong>
                </button>
            @endforeach
            <div class="rops-reference-box" id="reference-wrap">
                <label>Reference</label>
                <input id="tender-reference" placeholder="Transaction/Card reference">
            </div>
        </section>
    </main>

    <footer class="rops-payment-footer">
        <button id="complete-payment" type="button">Complete</button>
        <button id="pay-now" type="button">Pay</button>
    </footer>
</div>

<div class="rops-pos-modal" id="discount-modal" aria-hidden="true">
    <div class="rops-pos-dialog rops-discount-dialog">
        <button class="rops-pos-x" type="button" data-close-discount>×</button>
        <h2>Discount</h2>
        <div class="rops-discount-tabs">
            <button type="button" class="is-active" data-discount-type="percent">Percent</button>
            <button type="button" data-discount-type="flat">Flat</button>
        </div>
        <div class="rops-discount-presets" id="discount-presets"></div>
        <input id="discount-value" inputmode="decimal" value="5">
        <strong id="discount-preview">Total: £0.00</strong>
        <div class="rops-discount-actions">
            <button type="button" data-close-discount>Cancel</button>
            <button type="button" id="apply-discount">Ok</button>
        </div>
    </div>
</div>

<div class="rops-pos-modal" id="complete-modal" aria-hidden="true">
    <div class="rops-pos-dialog rops-complete-dialog">
        <button class="rops-pos-x" type="button" data-close-complete>×</button>
        <div class="rops-success-mark">✓</div>
        <h2>Payment successfully received!</h2>
        <div class="rops-complete-summary">
            <span>To pay</span><strong id="complete-to-pay">£{{ $payable }}</strong>
            <span>Amount tendered</span><strong id="complete-tendered">£{{ $payable }}</strong>
        </div>
        <div class="rops-print-options">
            <button type="button" data-print-type="receipt">Receipt</button>
            <button type="button" data-print-type="token">Token</button>
            <button type="button" data-print-type="both">Both</button>
        </div>
        <button class="rops-complete-close" type="button" data-close-complete>Close</button>
    </div>
</div>

<style>
    .rops-payment-screen {
        position: fixed;
        inset: 0;
        z-index: 1040;
        display: grid;
        grid-template-rows: 72px auto minmax(0, 1fr) 76px;
        width: min(1280px, calc(100vw - 48px));
        height: min(760px, calc(100vh - 56px));
        margin: auto;
        background: #f5f5f8;
        border: 1px solid #d8dde6;
        border-radius: 10px;
        box-shadow: 0 24px 70px rgba(15, 23, 42, .24), 0 0 0 9999px rgba(15, 23, 42, .46);
        overflow: hidden;
        color: #172033;
        font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }
    .rops-payment-topbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0 20px;
        background: #fff;
        border-bottom: 1px solid #dde3ed;
    }
    .rops-payment-title strong { display: block; font-size: 21px; }
    .rops-payment-title span { color: #667085; }
    .rops-payment-close {
        width: 44px;
        height: 44px;
        display: grid;
        place-items: center;
        border: 1px solid #dde3ed;
        border-radius: 7px;
        color: #172033;
        text-decoration: none;
        font-size: 28px;
    }
    .rops-payment-alert {
        display: none;
        margin: 10px 20px 0;
        padding: 10px 14px;
        border-radius: 6px;
        font-weight: 700;
    }
    .rops-payment-alert.is-error { color: #b42318; background: #fff3f1; border: 1px solid #f3b8b1; }
    .rops-payment-alert.is-ok { color: #12805c; background: #edf9f5; border: 1px solid #a9decf; }
    .rops-payment-grid {
        display: grid;
        grid-template-columns: minmax(280px, 31%) minmax(460px, 1fr) minmax(320px, 32%);
        min-height: 0;
        overflow: hidden;
        border-top: 1px solid #e4e8ef;
    }
    .rops-payment-order,
    .rops-payment-calculator,
    .rops-payment-methods {
        min-width: 0;
        min-height: 0;
        background: #fff;
        border-right: 1px solid #dde3ed;
    }
    .rops-payment-customer {
        display: grid;
        grid-template-columns: auto 1fr auto;
        align-items: center;
        gap: 14px;
        padding: 14px 18px;
        border-bottom: 1px solid #e4e8ef;
    }
    .rops-payment-avatar {
        width: 34px;
        height: 34px;
        border: 2px solid #344054;
        border-radius: 50%;
    }
    .rops-payment-customer button,
    .rops-payment-balance button {
        border: 1px solid #d8dde6;
        background: #fff;
        border-radius: 6px;
        min-height: 44px;
        padding: 0 18px;
        font-weight: 700;
    }
    .rops-payment-items {
        height: calc(100% - 180px);
        overflow: auto;
        padding: 10px 14px;
    }
    .rops-payment-item {
        display: grid;
        grid-template-columns: 46px 1fr auto;
        gap: 12px;
        align-items: center;
        min-height: 48px;
        padding: 8px 10px;
        border-radius: 6px;
    }
    .rops-payment-item:nth-child(odd) { background: #fafbfc; }
    .rops-payment-order-total,
    .rops-payment-grand-total {
        display: flex;
        justify-content: space-between;
        padding: 14px 24px;
        border-top: 1px solid #e4e8ef;
    }
    .rops-discount-row {
        display: none;
        color: #ef476f;
    }
    .rops-discount-row.is-visible { display: flex; }
    .rops-payment-grand-total {
        font-size: 25px;
        font-weight: 800;
    }
    .rops-payment-calculator {
        padding: 16px 14px 14px;
        display: grid;
        grid-template-rows: auto auto auto 1fr;
        gap: 12px;
    }
    .rops-payment-balance {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 20px;
    }
    .rops-payment-balance button {
        color: #459aa6;
        border: 0;
    }
    .rops-payment-quick {
        display: flex;
        justify-content: flex-end;
        gap: 8px;
        min-width: 0;
    }
    .rops-payment-quick button {
        border: 1px solid #49a8b5;
        color: #49a8b5;
        background: #f2fbfc;
        border-radius: 999px;
        min-width: 88px;
        min-height: 40px;
        font-weight: 800;
    }
    .rops-payment-entry {
        display: grid;
        grid-template-columns: 34% 1fr;
        align-items: center;
        gap: 8px;
        min-width: 0;
    }
    .rops-payment-entry label {
        font-weight: 700;
        color: #344054;
    }
    .rops-payment-entry div,
    .rops-payment-entry input {
        width: 100%;
        min-width: 0;
        min-height: 64px;
        border-radius: 8px;
        border: 1px solid #d8dde6;
        padding: 0 18px;
        font-size: 24px;
        font-weight: 800;
        text-align: right;
    }
    .rops-payment-entry div {
        display: flex;
        align-items: center;
        background: #5f5f5f;
        color: #fff;
        text-align: left;
    }
    .rops-payment-keypad {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        grid-template-rows: repeat(4, minmax(62px, 1fr));
        gap: 8px;
        min-width: 0;
    }
    .rops-payment-keypad button {
        min-width: 0;
        border: 1px solid #d8dde6;
        border-radius: 7px;
        background: #fff;
        color: #263955;
        font-size: 25px;
        font-weight: 700;
    }
    .rops-payment-keypad .is-tall {
        grid-row: span 2;
    }
    .rops-payment-keypad .is-clear {
        color: #c1121f;
    }
    .rops-payment-tools {
        display: flex;
        gap: 10px;
    }
    .rops-payment-tools button {
        width: 42%;
        min-height: 48px;
        border: 1px solid #49a8b5;
        border-radius: 7px;
        background: #fff;
        color: #2f8f9d;
        font-weight: 800;
        font-size: 16px;
    }
    .rops-payment-methods {
        padding: 14px;
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        align-content: start;
        gap: 10px;
        border-right: 0;
    }
    .rops-payment-method {
        min-width: 0;
        min-height: 72px;
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 0 20px;
        border: 1px solid #d8dde6;
        border-radius: 7px;
        background: #fff;
        text-align: left;
        font-size: 17px;
    }
    .rops-payment-method.is-active {
        border-color: #49a8b5;
        background: #eef9fb;
    }
    .rops-payment-method span {
        color: #49a8b5;
        font-size: 24px;
    }
    .rops-reference-box {
        grid-column: 1 / -1;
        display: none;
        padding: 12px;
        border: 1px solid #d8dde6;
        border-radius: 7px;
        background: #fff;
    }
    .rops-reference-box.is-visible { display: grid; gap: 6px; }
    .rops-reference-box label {
        font-weight: 800;
        color: #344054;
    }
    .rops-reference-box input {
        min-height: 44px;
        border: 1px solid #d8dde6;
        border-radius: 6px;
        padding: 0 12px;
    }
    .rops-payment-footer {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
        padding: 14px 16px;
        border-top: 1px solid #dde3ed;
        background: #fff;
        align-self: end;
    }
    .rops-payment-footer button {
        border: 0;
        border-radius: 7px;
        color: #fff;
        font-size: 19px;
        font-weight: 800;
    }
    #complete-payment { background: #4ba8b4; }
    #pay-now { background: #10a75a; }
    .rops-pos-modal {
        position: fixed;
        inset: 0;
        z-index: 1080;
        display: none;
        place-items: center;
        background: rgba(15, 23, 42, .46);
    }
    .rops-pos-modal.is-open { display: grid; }
    .rops-pos-dialog {
        position: relative;
        width: min(520px, calc(100vw - 36px));
        overflow: hidden;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 22px 60px rgba(15, 23, 42, .24);
        color: #172033;
    }
    .rops-pos-dialog h2 {
        margin: 0;
        padding: 20px 24px;
        border-bottom: 1px solid #e4e8ef;
        font-size: 22px;
    }
    .rops-pos-x {
        position: absolute;
        top: 14px;
        right: 16px;
        border: 0;
        background: transparent;
        color: #344054;
        font-size: 32px;
        line-height: 1;
    }
    .rops-discount-tabs,
    .rops-discount-presets,
    .rops-discount-dialog input,
    .rops-discount-dialog strong {
        margin-left: 24px;
        margin-right: 24px;
    }
    .rops-discount-tabs {
        display: inline-flex;
        margin-top: 22px;
        border: 1px solid #d8dde6;
        border-radius: 7px;
        overflow: hidden;
    }
    .rops-discount-tabs button {
        min-width: 92px;
        min-height: 48px;
        border: 0;
        background: #fff;
        font-size: 16px;
    }
    .rops-discount-tabs button.is-active {
        background: #49a8b5;
        color: #fff;
    }
    .rops-discount-presets {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 14px 16px;
        margin-top: 22px;
    }
    .rops-discount-presets button {
        min-height: 50px;
        border: 1px solid #d8dde6;
        border-radius: 999px;
        background: #fff;
        color: #475467;
        font-weight: 800;
    }
    .rops-discount-presets button.is-active {
        border-color: #ef476f;
        background: #ef476f;
        color: #fff;
    }
    .rops-discount-dialog input {
        width: calc(100% - 48px);
        min-height: 50px;
        margin-top: 22px;
        padding: 0 12px;
        border: 1px solid #d8dde6;
        border-radius: 7px;
        font-size: 17px;
    }
    .rops-discount-dialog strong {
        display: block;
        margin-top: 20px;
        margin-bottom: 24px;
        color: #475467;
        font-size: 16px;
    }
    .rops-discount-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        border-top: 1px solid #e4e8ef;
    }
    .rops-discount-actions button {
        min-height: 58px;
        border: 0;
        background: #fff;
        font-size: 18px;
        font-weight: 800;
    }
    .rops-discount-actions button:last-child {
        background: #49a8b5;
        color: #fff;
    }
    .rops-complete-dialog { text-align: center; }
    .rops-success-mark {
        width: 50px;
        height: 50px;
        display: grid;
        place-items: center;
        margin: 28px auto 10px;
        border: 3px solid #17b26a;
        border-radius: 50%;
        color: #17b26a;
        font-size: 32px;
        font-weight: 800;
    }
    .rops-complete-dialog h2 {
        padding: 10px 24px 20px;
        border: 0;
        color: #17b26a;
    }
    .rops-complete-summary {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 12px;
        margin: 0 24px 24px;
        padding: 16px;
        border: 1px solid #e4e8ef;
        border-radius: 7px;
        background: #fafbfc;
        text-align: left;
    }
    .rops-complete-summary span { color: #667085; font-weight: 700; }
    .rops-print-options {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
        margin: 0 24px 24px;
    }
    .rops-print-options button {
        min-height: 48px;
        border: 1px solid #d8dde6;
        border-radius: 7px;
        background: #fff;
        font-weight: 800;
        color: #475467;
    }
    .rops-complete-close {
        width: 100%;
        min-height: 58px;
        border: 0;
        border-top: 1px solid #e4e8ef;
        background: #fff;
        font-size: 18px;
        font-weight: 800;
        color: #344054;
    }
    @media (max-width: 1100px) {
        .rops-payment-grid { grid-template-columns: 1fr; overflow: auto; }
        .rops-payment-screen {
            width: calc(100vw - 24px);
            height: calc(100vh - 24px);
            grid-template-rows: 68px auto 1fr 72px;
        }
        .rops-payment-order, .rops-payment-calculator, .rops-payment-methods { border-right: 0; }
    }
</style>

<script>
(() => {
    const root = document.getElementById('rops-payment');
    const total = Number(root.dataset.total || 0);
    let payable = total;
    const version = Number(root.dataset.version || 0);
    const amount = document.getElementById('tender-amount');
    const reference = document.getElementById('tender-reference');
    const referenceWrap = document.getElementById('reference-wrap');
    const errorBox = document.getElementById('payment-errors');
    const successBox = document.getElementById('payment-success');
    const payButton = document.getElementById('pay-now');
    const completeButton = document.getElementById('complete-payment');
    const discountModal = document.getElementById('discount-modal');
    const completeModal = document.getElementById('complete-modal');
    const balanceDue = document.getElementById('balance-due');
    const toPay = document.getElementById('to-pay');
    const summaryTotal = document.getElementById('summary-total');
    const summaryDiscount = document.getElementById('summary-discount');
    const summaryDiscountRow = document.getElementById('summary-discount-row');
    const discountValue = document.getElementById('discount-value');
    const discountPreview = document.getElementById('discount-preview');
    const discountPresets = document.getElementById('discount-presets');
    const completeToPay = document.getElementById('complete-to-pay');
    const completeTendered = document.getElementById('complete-tendered');
    let selected = {method: 'cash', provider: ''};
    let discount = {type: 'percent', value: 0, amount: 0};
    let busy = false;
    let amountDirty = false;
    const idempotencyKey = window.crypto && crypto.randomUUID ? crypto.randomUUID() : String(Date.now());
    const money = value => Number(value || 0).toFixed(2);
    const tokenItems = @json($tokenItems);
    const tokenMeta = @json($tokenMeta);
    const displayMoney = value => `£${money(value)}`;
    const showError = message => {
        errorBox.textContent = message;
        errorBox.style.display = 'block';
    };
    const showSuccess = message => {
        successBox.textContent = message;
        successBox.style.display = 'block';
    };
    const clearMessages = () => {
        errorBox.style.display = 'none';
        successBox.style.display = 'none';
    };
    const cleanAmount = value => {
        const text = String(value).replace(/[^\d.]/g, '');
        const parts = text.split('.');
        const whole = parts.shift() || '';
        const fraction = parts.length ? parts.join('').slice(0, 2) : null;

        return fraction === null ? whole : `${whole}.${fraction}`;
    };
    const setAmount = (value, dirty = true) => {
        amount.value = cleanAmount(value);
        amountDirty = dirty;
    };
    const validate = () => {
        const tendered = Number(amount.value || 0);
        const needsReference = selected.method !== 'cash';
        payButton.disabled = busy || tendered <= 0 || (selected.method !== 'cash' && tendered > payable) || (needsReference && reference.value.trim() === '');
        completeButton.disabled = payButton.disabled;
    };
    const calculateDiscount = () => {
        const value = Number(discountValue.value || 0);
        const raw = discount.type === 'percent' ? total * Math.min(value, 100) / 100 : value;
        return Math.min(total, Math.max(0, raw));
    };
    const renderPayable = () => {
        payable = Math.max(0, total - Number(discount.amount || 0));
        balanceDue.textContent = displayMoney(payable);
        toPay.textContent = displayMoney(payable);
        summaryTotal.textContent = displayMoney(payable);
        summaryDiscount.textContent = `-${money(discount.amount || 0)}`;
        summaryDiscountRow.classList.toggle('is-visible', Number(discount.amount || 0) > 0);
        if (!amountDirty) setAmount(money(payable), false);
        validate();
    };
    const renderDiscountPreview = () => {
        const discountAmount = calculateDiscount();
        discountPreview.textContent = `Total: ${displayMoney(discountAmount)}${discount.type === 'percent' ? ` (${money(Number(discountValue.value || 0))}%)` : ''}`;
        discountPresets.querySelectorAll('button').forEach(button => button.classList.toggle('is-active', Number(button.dataset.value) === Number(discountValue.value || 0)));
    };
    const setDiscountType = type => {
        discount.type = type;
        document.querySelectorAll('[data-discount-type]').forEach(button => button.classList.toggle('is-active', button.dataset.discountType === type));
        const values = type === 'percent' ? [5, 10, 15, 20, 25, 30, 40, 50] : [1, 2, 5, 10, 20, 50, 100, 200];
        discountPresets.innerHTML = values.map(value => `<button type="button" data-value="${value}">${type === 'percent' ? `${value}%` : money(value)}</button>`).join('');
        discountValue.value = values[0];
        discountPresets.querySelectorAll('button').forEach(button => button.addEventListener('click', () => {
            discountValue.value = button.dataset.value;
            renderDiscountPreview();
        }));
        renderDiscountPreview();
    };
    const selectMethod = button => {
        document.querySelectorAll('.rops-payment-method').forEach(item => item.classList.toggle('is-active', item === button));
        selected = {method: button.dataset.method, provider: button.dataset.provider || ''};
        referenceWrap.classList.toggle('is-visible', selected.method !== 'cash');
        validate();
    };
    document.querySelectorAll('.rops-payment-method').forEach(button => button.addEventListener('click', () => selectMethod(button)));
    document.querySelectorAll('[data-quick]').forEach(button => button.addEventListener('click', () => {
        const quick = button.dataset.quick === root.dataset.total ? payable : button.dataset.quick;
        setAmount(quick, false);
        validate();
    }));
    document.querySelectorAll('[data-key]').forEach(button => button.addEventListener('click', () => {
        const key = button.dataset.key;
        if (key === 'clear') setAmount('', true);
        else if (key === 'back') setAmount(amountDirty ? amount.value.slice(0, -1) : '', true);
        else if (key === '.' && amount.value.includes('.')) return;
        else setAmount((amountDirty ? amount.value : '') + key, true);
        validate();
    }));
    amount.addEventListener('focus', () => amount.select());
    amount.addEventListener('input', () => {
        setAmount(amount.value, true);
        validate();
    });
    reference.addEventListener('input', validate);
    document.getElementById('open-discount').addEventListener('click', () => {
        setDiscountType(discount.type);
        if (Number(discount.value || 0) > 0) {
            discountValue.value = discount.value;
            renderDiscountPreview();
        }
        discountModal.classList.add('is-open');
    });
    document.querySelectorAll('[data-close-discount]').forEach(button => button.addEventListener('click', () => discountModal.classList.remove('is-open')));
    document.querySelectorAll('[data-close-complete]').forEach(button => button.addEventListener('click', () => {
        completeModal.classList.remove('is-open');
        window.location.href = root.dataset.pos;
    }));
    document.querySelectorAll('[data-discount-type]').forEach(button => button.addEventListener('click', () => setDiscountType(button.dataset.discountType)));
    discountValue.addEventListener('input', renderDiscountPreview);
    document.getElementById('apply-discount').addEventListener('click', () => {
        discount.value = Number(discountValue.value || 0);
        discount.amount = Number(money(calculateDiscount()));
        amountDirty = false;
        renderPayable();
        discountModal.classList.remove('is-open');
    });
    const printToken = () => {
        const escapeHtml = value => String(value || '').replace(/[&<>"']/g, character => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[character]));
        const printedAt = new Date().toLocaleString('en-GB', {day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'});
        const rows = tokenItems.map(item => `<tr><td class="qty">${item.quantity}x</td><td><strong>${escapeHtml(item.name)}</strong>${item.variant ? `<span>${escapeHtml(item.variant)}</span>` : ''}${item.note ? `<span>Note: ${escapeHtml(item.note)}</span>` : ''}</td></tr>`).join('');
        const popup = window.open('', 'rops-token-print', 'width=360,height=560');
        if (!popup) return;
        popup.document.write(`<html><head><title>Kitchen Token</title><style>@page{size:80mm auto;margin:4mm}body{width:72mm;margin:0 auto;font:13px/1.35 "Courier New",ui-monospace,monospace;color:#111}.center{text-align:center}h1{margin:0;font-size:17px;text-transform:uppercase}h2{margin:5px 0;font-size:15px;letter-spacing:.5px}.rule{border-top:1px dashed #111;margin:7px 0}.row{display:flex;justify-content:space-between;gap:8px}.large{font-size:23px;font-weight:800}.muted{color:#444}table{width:100%;border-collapse:collapse}td{padding:5px 0;vertical-align:top}.qty{width:34px;font-size:17px;font-weight:800}td span{display:block;color:#444;font-size:12px}.footer{margin-top:8px;text-align:center;font-size:11px}</style></head><body><h1 class="center">${escapeHtml(tokenMeta.restaurant)}</h1><h2 class="center">KITCHEN TOKEN</h2><div class="center large">#${escapeHtml(tokenMeta.order)}</div><div class="rule"></div><div class="row"><span>Date/Time</span><strong>${escapeHtml(printedAt)}</strong></div><div class="row"><span>Service</span><strong>${escapeHtml(tokenMeta.service)}</strong></div><div class="row"><span>Cashier</span><strong>${escapeHtml(tokenMeta.cashier)}</strong></div><div class="rule"></div><table>${rows}</table><div class="rule"></div><div class="footer">Prepare order exactly as listed</div><script>print();<\/script></body></html>`);
        popup.document.close();
    };
    const printReceipt = () => {
        const popup = window.open(root.dataset.receipt, 'rops-receipt-print', 'width=420,height=640');
        if (popup) popup.addEventListener('load', () => popup.print());
    };
    document.querySelectorAll('[data-print-type]').forEach(button => button.addEventListener('click', () => {
        const type = button.dataset.printType;
        if (type === 'receipt') printReceipt();
        if (type === 'token') printToken();
        if (type === 'both') {
            printToken();
            setTimeout(printReceipt, 400);
        }
    }));
    const submitPayment = async () => {
        if (busy) return;
        clearMessages();
        busy = true;
        validate();
        const tendered = Number(amount.value || 0);
        const tenders = [{
            method: selected.method,
            provider: selected.provider,
            reference: selected.method === 'cash' ? '' : reference.value.trim(),
            amount: money(tendered),
        }];
        const discountPayload = Number(discount.amount || 0) > 0 ? {type: discount.type, value: money(discount.value), amount: money(discount.amount)} : null;
        try {
            const response = await fetch(root.dataset.store, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '{{ csrf_token() }}',
                    'Idempotency-Key': idempotencyKey,
                },
                body: JSON.stringify({version, discount: discountPayload, tenders}),
            });
            const json = await response.json();
            if (!response.ok) throw new Error(json.error?.message || Object.values(json.errors || {}).flat().join(' ') || 'Payment failed.');
            showSuccess('Payment successfully received.');
            payButton.textContent = 'Paid';
            payButton.disabled = true;
            completeButton.textContent = 'Receipt';
            completeButton.disabled = false;
            completeButton.onclick = () => window.location.href = root.dataset.receipt;
            completeToPay.textContent = displayMoney(payable);
            completeTendered.textContent = displayMoney(tendered);
            completeModal.classList.add('is-open');
        } catch (error) {
            busy = false;
            showError(error.message);
            validate();
        }
    };
    payButton.addEventListener('click', submitPayment);
    completeButton.addEventListener('click', submitPayment);
    renderPayable();
    validate();
})();
</script>
